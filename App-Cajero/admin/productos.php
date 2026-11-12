<?php
// ============================================================
// admin/productos.php
// ABML Completo de Productos · Mostaza POS & Web
// Alta, Baja (Lógica y Física), Modificación y Listado
// ============================================================
session_start();
require('conexion.php');

$mensajeOk = '';
$mensajeError = '';

// Carpeta de subida de imágenes (usada por mostaza_web)
$uploadDir = '../mostaza_web/img/';
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0777, true);
}

// ------------------------------------------------------------
// PROCESAMIENTO DE ACCIONES (POST)
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    // 1. CREAR / ALTA DE PRODUCTO
    if ($accion === 'crear') {
        $nombre = trim($_POST['nombre'] ?? '');
        $categoria = trim($_POST['categoria'] ?? '');
        $precio = floatval($_POST['precio'] ?? 0);
        $descripcion = trim($_POST['descripcion'] ?? '');
        $activo = isset($_POST['activo']) ? 1 : 0;
        $imagenNombre = 'default_burger.jpg';

        if (empty($nombre) || empty($categoria) || $precio <= 0) {
            $mensajeError = 'Por favor complete el nombre, categoría y un precio válido.';
        } else {
            // Manejo de archivo de imagen subido
            if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
                $permitidas = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                if (in_array($ext, $permitidas)) {
                    $nuevoNombre = 'prod_' . time() . '_' . rand(100, 999) . '.' . $ext;
                    if (move_uploaded_file($_FILES['imagen']['tmp_name'], $uploadDir . $nuevoNombre)) {
                        $imagenNombre = $nuevoNombre;
                    }
                }
            } elseif (!empty($_POST['imagen_existente'])) {
                $imagenNombre = trim($_POST['imagen_existente']);
            }

            $stmt = $conn->prepare("INSERT INTO producto (nombre, descripcion, precio, imagen, categoria, activo) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssdssi", $nombre, $descripcion, $precio, $imagenNombre, $categoria, $activo);
            if ($stmt->execute()) {
                $mensajeOk = "¡Producto <strong>" . htmlspecialchars($nombre) . "</strong> creado con éxito!";
            } else {
                $mensajeError = "Error al crear producto: " . $conn->error;
            }
        }
    }

    // 2. MODIFICAR / EDICIÓN DE PRODUCTO
    elseif ($accion === 'editar') {
        $idproducto = intval($_POST['idproducto'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $categoria = trim($_POST['categoria'] ?? '');
        $precio = floatval($_POST['precio'] ?? 0);
        $descripcion = trim($_POST['descripcion'] ?? '');
        $activo = isset($_POST['activo']) ? 1 : 0;

        if ($idproducto <= 0 || empty($nombre) || empty($categoria) || $precio <= 0) {
            $mensajeError = 'Datos incompletos para actualizar el producto.';
        } else {
            // Verificar si subió nueva imagen
            $imagenSql = "";
            $params = [$nombre, $descripcion, $precio, $categoria, $activo];
            $types = "ssdsi";

            if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
                $permitidas = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                if (in_array($ext, $permitidas)) {
                    $nuevoNombre = 'prod_' . time() . '_' . rand(100, 999) . '.' . $ext;
                    if (move_uploaded_file($_FILES['imagen']['tmp_name'], $uploadDir . $nuevoNombre)) {
                        $imagenSql = ", imagen = ?";
                        $params[] = $nuevoNombre;
                        $types .= "s";
                    }
                }
            }

            $params[] = $idproducto;
            $types .= "i";

            $sql = "UPDATE producto SET nombre = ?, descripcion = ?, precio = ?, categoria = ?, activo = ? $imagenSql WHERE idproducto = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            if ($stmt->execute()) {
                $mensajeOk = "¡Producto #$idproducto actualizado correctamente!";
            } else {
                $mensajeError = "Error al actualizar: " . $conn->error;
            }
        }
    }

    // 3. TOGGLE ESTADO ACTIVO (BAJA / REACTIVACIÓN LÓGICA RÁPIDA)
    elseif ($accion === 'toggle_activo') {
        $idproducto = intval($_POST['idproducto'] ?? 0);
        $nuevoEstado = intval($_POST['nuevo_estado'] ?? 0);
        $stmt = $conn->prepare("UPDATE producto SET activo = ? WHERE idproducto = ?");
        $stmt->bind_param("ii", $nuevoEstado, $idproducto);
        if ($stmt->execute()) {
            $estadoTxt = $nuevoEstado ? 'activado' : 'pausado/desactivado';
            $mensajeOk = "El producto #$idproducto ahora está $estadoTxt.";
        }
    }

    // 4. ELIMINAR FÍSICO
    elseif ($accion === 'eliminar') {
        $idproducto = intval($_POST['idproducto'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM producto WHERE idproducto = ?");
        $stmt->bind_param("i", $idproducto);
        if ($stmt->execute()) {
            $mensajeOk = "Producto #$idproducto eliminado definitivamente.";
        } else {
            $mensajeError = "No se puede eliminar porque tiene ventas o comandas vinculadas. Puedes desactivarlo (baja lógica).";
        }
    }
}

// ------------------------------------------------------------
// FILTROS Y CONSULTA DE LISTADO
// ------------------------------------------------------------
$filtroCat = trim($_GET['cat'] ?? '');
$filtroBuscar = trim($_GET['q'] ?? '');
$filtroEstado = trim($_GET['estado'] ?? '');

$whereClauses = ["1=1"];
$paramsWhere = [];
$typesWhere = "";

if (!empty($filtroCat)) {
    $whereClauses[] = "categoria = ?";
    $paramsWhere[] = $filtroCat;
    $typesWhere .= "s";
}
if (!empty($filtroBuscar)) {
    $whereClauses[] = "(nombre LIKE ? OR descripcion LIKE ? OR idproducto = ?)";
    $like = "%$filtroBuscar%";
    $paramsWhere[] = $like;
    $paramsWhere[] = $like;
    $idNum = intval($filtroBuscar);
    $paramsWhere[] = $idNum;
    $typesWhere .= "ssi";
}
if ($filtroEstado !== '') {
    $whereClauses[] = "activo = ?";
    $paramsWhere[] = intval($filtroEstado);
    $typesWhere .= "i";
}

$whereSql = implode(" AND ", $whereClauses);
$sqlTotal = "SELECT * FROM producto WHERE $whereSql ORDER BY categoria ASC, nombre ASC";
$stmt = $conn->prepare($sqlTotal);
if (!empty($paramsWhere)) {
    $stmt->bind_param($typesWhere, ...$paramsWhere);
}
$stmt->execute();
$productosRes = $stmt->get_result();

// Categorías disponibles
$categoriasList = ['megacombos', 'exclusivos', 'pollo', 'paravos', 'cajitas', 'ensaladas', 'postres', 'bebidas', 'cafeteria'];

// Resumen de Métricas
$totalProds = $conn->query("SELECT COUNT(*) as c, SUM(activo=1) as activos, SUM(activo=0) as inactivos FROM producto")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Gestión de Productos (ABML) · Mostaza Central</title>
<link rel="stylesheet" href="admin.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
/* Estilos específicos y complementarios para el ABML */
.prod-img-thumb {
    width: 52px;
    height: 52px;
    border-radius: 10px;
    object-fit: cover;
    background: #F1F5F9;
    border: 1px solid #E2E8F0;
}
.badge-cat {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    background: #FEF3C7;
    color: #92400E;
}
.badge-estado {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 700;
}
.badge-activo { background: #DCFCE7; color: #166534; }
.badge-inactivo { background: #FEE2E2; color: #991B1B; }

.modal-backdrop {
    display: none;
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(4px);
    z-index: 1000;
    align-items: center;
    justify-content: center;
    padding: 20px;
}
.modal-backdrop.active { display: flex; }
.modal-content {
    background: #FFFFFF;
    border-radius: 16px;
    width: 100%;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
    border: 1px solid #E2E8F0;
}
.modal-header {
    padding: 20px 24px;
    border-bottom: 1px solid #E2E8F0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.modal-header h2 { font-size: 1.25rem; font-weight: 800; color: #0F172A; }
.modal-close {
    background: transparent;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: #64748B;
}
.modal-body { padding: 24px; }
.form-group { margin-bottom: 18px; }
.form-group label {
    display: block;
    font-size: 0.82rem;
    font-weight: 700;
    color: #334155;
    margin-bottom: 6px;
    text-transform: uppercase;
}
.form-control {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid #CBD5E1;
    border-radius: 8px;
    font-family: inherit;
    font-size: 0.95rem;
    color: #0F172A;
    background: #FFFFFF;
    transition: border-color 0.2s;
}
.form-control:focus {
    outline: none;
    border-color: #A30002;
    box-shadow: 0 0 0 3px rgba(163,0,2,0.1);
}
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}
.btn-primary {
    background: #A30002;
    color: #FFFFFF;
    padding: 10px 20px;
    border-radius: 8px;
    border: none;
    font-weight: 700;
    font-size: 0.9rem;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: background 0.2s;
}
.btn-primary:hover { background: #820002; }
.btn-secondary {
    background: #F1F5F9;
    color: #334155;
    padding: 10px 18px;
    border-radius: 8px;
    border: 1px solid #CBD5E1;
    font-weight: 600;
    cursor: pointer;
}
.btn-icon {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    border: 1px solid #E2E8F0;
    background: #FFFFFF;
    color: #475569;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}
.btn-icon:hover { background: #F8FAFC; color: #A30002; border-color: #CBD5E1; }
.btn-icon.delete:hover { background: #FEE2E2; color: #DC2626; border-color: #FCA5A5; }
.toolbar-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
    margin-bottom: 24px;
}
.search-filter-box {
    display: flex;
    gap: 12px;
    align-items: center;
    flex-wrap: wrap;
}
</style>
</head>
<body class="admin-body">

<!-- Sidebar -->
<aside class="admin-sidebar">
    <div class="sidebar-brand">
        <img src="img/logo.png" alt="Mostaza">
        <div>
            <h2>MOSTAZA POS</h2>
            <span>EPET Nº 7 · Sistema Web</span>
        </div>
    </div>

    <ul class="sidebar-nav">
        <li><a href="index.php"><i class="fa-solid fa-chart-pie"></i> Dashboard</a></li>
        <li><a href="productos.php" class="active"><i class="fa-solid fa-burger"></i> Catálogo & Productos</a></li>
        <li><a href="pos.php"><i class="fa-solid fa-cash-register"></i> Punto de Venta (POS)</a></li>
        <li><a href="mesero.php"><i class="fa-solid fa-utensils"></i> Salón (Mesero)</a></li>
        <li><a href="cocina.php"><i class="fa-solid fa-fire-burner"></i> Cocina (KDS)</a></li>
        <li><a href="repartidor.php"><i class="fa-solid fa-motorcycle"></i> Repartidor</a></li>
        <li><a href="caja.php"><i class="fa-solid fa-vault"></i> Control de Caja</a></li>
        <li><a href="inventario.php"><i class="fa-solid fa-boxes-stacked"></i> Inventario & Stock</a></li>
        <li><a href="reportes.php"><i class="fa-solid fa-file-invoice-dollar"></i> Reportes</a></li>
        <li style="margin-top:20px;"><a href="../mostaza_web/" target="_blank"><i class="fa-solid fa-store"></i> Ir a la Tienda Web</a></li>
    </ul>

    <div class="sidebar-footer">
        Sistema Mostaza v2.5<br>EPET Nº 7 "Vicente Arcadio Salemi"
    </div>
</aside>

<!-- Contenido Principal -->
<main class="admin-main">

    <div class="admin-topbar">
        <div>
            <h1>Administración de Productos & Menú</h1>
            <p>Control total de catálogo: Alta, Modificación, Baja, Precios e Imágenes de todo el sistema.</p>
        </div>
        <div>
            <button class="btn-primary" onclick="abrirModalCrear()">
                <i class="fa-solid fa-plus"></i> Nuevo Producto
            </button>
        </div>
    </div>

    <?php if ($mensajeOk): ?>
        <div style="background:#DCFCE7;color:#166534;padding:14px 18px;border-radius:10px;margin-bottom:20px;font-weight:600;border:1px solid #BBF7D0;display:flex;align-items:center;gap:10px;">
            <i class="fa-solid fa-circle-check"></i> <?= $mensajeOk ?>
        </div>
    <?php endif; ?>

    <?php if ($mensajeError): ?>
        <div style="background:#FEE2E2;color:#991B1B;padding:14px 18px;border-radius:10px;margin-bottom:20px;font-weight:600;border:1px solid #FCA5A5;display:flex;align-items:center;gap:10px;">
            <i class="fa-solid fa-triangle-exclamation"></i> <?= $mensajeError ?>
        </div>
    <?php endif; ?>

    <!-- KPIs Rápidos -->
    <div class="kpi-grid" style="grid-template-columns: repeat(3, 1fr); margin-bottom: 24px;">
        <div class="kpi-card">
            <div class="kpi-icon blue"><i class="fa-solid fa-burger"></i></div>
            <div class="kpi-info">
                <h3><?= $totalProds['c'] ?? 0 ?></h3>
                <span>Productos Totales</span>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon green"><i class="fa-solid fa-check"></i></div>
            <div class="kpi-info">
                <h3><?= $totalProds['activos'] ?? 0 ?></h3>
                <span>En Venta (Activos)</span>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon orange"><i class="fa-solid fa-pause"></i></div>
            <div class="kpi-info">
                <h3><?= $totalProds['inactivos'] ?? 0 ?></h3>
                <span>Pausados / Desactivados</span>
            </div>
        </div>
    </div>

    <!-- Barra de Filtros y Búsqueda -->
    <div class="admin-card" style="margin-bottom: 24px; padding: 18px 24px;">
        <form method="GET" class="toolbar-actions" style="margin-bottom: 0;">
            <div class="search-filter-box">
                <div style="position:relative;">
                    <i class="fa-solid fa-magnifying-glass" style="position:absolute;left:12px;top:12px;color:#94A3B8;"></i>
                    <input type="text" name="q" value="<?= htmlspecialchars($filtroBuscar) ?>" placeholder="Buscar por nombre, ID o descripción..." class="form-control" style="padding-left:36px;min-width:280px;">
                </div>

                <select name="cat" class="form-control" style="min-width:180px;">
                    <option value="">Todas las Categorías</option>
                    <?php foreach ($categoriasList as $cat): ?>
                        <option value="<?= $cat ?>" <?= $filtroCat === $cat ? 'selected' : '' ?>><?= ucfirst($cat) ?></option>
                    <?php endforeach; ?>
                </select>

                <select name="estado" class="form-control" style="min-width:150px;">
                    <option value="">Todos los Estados</option>
                    <option value="1" <?= $filtroEstado === '1' ? 'selected' : '' ?>>Solo Activos</option>
                    <option value="0" <?= $filtroEstado === '0' ? 'selected' : '' ?>>Solo Pausados</option>
                </select>

                <button type="submit" class="btn-primary" style="padding: 10px 16px;">
                    <i class="fa-solid fa-filter"></i> Filtrar
                </button>

                <?php if (!empty($filtroBuscar) || !empty($filtroCat) || $filtroEstado !== ''): ?>
                    <a href="productos.php" class="btn-secondary" style="text-decoration:none;">Limpiar</a>
                <?php endif; ?>
            </div>
            <div>
                <span style="font-size:0.85rem;color:#64748B;font-weight:600;">Mostrando <?= $productosRes->num_rows ?> productos</span>
            </div>
        </form>
    </div>

    <!-- Tabla Principal de Productos -->
    <div class="admin-card">
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th style="width: 70px;">Foto</th>
                        <th style="width: 60px;">ID</th>
                        <th>Nombre y Descripción</th>
                        <th>Categoría</th>
                        <th>Precio Venta</th>
                        <th>Estado</th>
                        <th style="text-align: right; width: 140px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($productosRes->num_rows === 0): ?>
                        <tr>
                            <td colspan="7" style="text-align:center;padding:40px;color:#64748B;">
                                <i class="fa-solid fa-box-open" style="font-size:2.5rem;color:#CBD5E1;margin-bottom:10px;display:block;"></i>
                                No se encontraron productos con los filtros seleccionados.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php while ($prod = $productosRes->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <?php 
                                    $imgFile = $prod['imagen'];
                                    $imgPath = "../mostaza_web/img/" . $imgFile;
                                    $imgUrl = file_exists($imgPath) && !empty($imgFile) ? $imgPath : 'img/logo.png';
                                    ?>
                                    <img src="<?= htmlspecialchars($imgUrl) ?>" alt="Producto" class="prod-img-thumb" onerror="this.src='img/logo.png'">
                                </td>
                                <td><strong>#<?= $prod['idproducto'] ?></strong></td>
                                <td>
                                    <strong style="color:#0F172A;font-size:0.95rem;display:block;"><?= htmlspecialchars($prod['nombre']) ?></strong>
                                    <span style="color:#64748B;font-size:0.8rem;line-height:1.3;display:block;max-width:380px;">
                                        <?= htmlspecialchars(mb_strimwidth($prod['descripcion'] ?? '', 0, 80, '...')) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge-cat"><?= htmlspecialchars($prod['categoria']) ?></span>
                                </td>
                                <td>
                                    <strong style="color:#A30002;font-size:1.05rem;">$<?= number_format($prod['precio'], 2, ',', '.') ?></strong>
                                </td>
                                <td>
                                    <?php if ($prod['activo'] == 1): ?>
                                        <span class="badge-estado badge-activo"><i class="fa-solid fa-circle" style="font-size:0.5rem;"></i> Activo</span>
                                    <?php else: ?>
                                        <span class="badge-estado badge-inactivo"><i class="fa-solid fa-circle" style="font-size:0.5rem;"></i> Pausado</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right;">
                                    <div style="display:inline-flex;gap:6px;">
                                        <!-- Botón Pausar / Reactivar Rápido -->
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="accion" value="toggle_activo">
                                            <input type="hidden" name="idproducto" value="<?= $prod['idproducto'] ?>">
                                            <input type="hidden" name="nuevo_estado" value="<?= $prod['activo'] == 1 ? 0 : 1 ?>">
                                            <button type="submit" class="btn-icon" title="<?= $prod['activo'] == 1 ? 'Pausar venta' : 'Activar venta' ?>">
                                                <i class="fa-solid <?= $prod['activo'] == 1 ? 'fa-pause' : 'fa-play' ?>"></i>
                                            </button>
                                        </form>

                                        <!-- Botón Editar -->
                                        <button class="btn-icon" title="Editar Producto" onclick='abrirModalEditar(<?= json_encode($prod, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>

                                        <!-- Botón Eliminar Físico -->
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('¿Seguro que deseas eliminar definitivamente este producto? (Si tiene ventas asociadas no podrá borrarse por seguridad, en ese caso usa pausar).');">
                                            <input type="hidden" name="accion" value="eliminar">
                                            <input type="hidden" name="idproducto" value="<?= $prod['idproducto'] ?>">
                                            <button type="submit" class="btn-icon delete" title="Eliminar Producto">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</main>

<!-- MODAL CREAR / EDITAR PRODUCTO -->
<div class="modal-backdrop" id="modalProducto">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitulo"><i class="fa-solid fa-burger" style="color:#A30002;margin-right:8px;"></i> Nuevo Producto</h2>
            <button class="modal-close" onclick="cerrarModal()">&times;</button>
        </div>
        <form method="POST" enctype="multipart/form-data" id="formProducto">
            <div class="modal-body">
                <input type="hidden" name="accion" id="formAccion" value="crear">
                <input type="hidden" name="idproducto" id="formIdProducto" value="">

                <div class="form-group">
                    <label>Nombre del Producto *</label>
                    <input type="text" name="nombre" id="formNombre" required class="form-control" placeholder="Ej: Mega Bacon Doble">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Categoría *</label>
                        <select name="categoria" id="formCategoria" required class="form-control">
                            <?php foreach ($categoriasList as $cat): ?>
                                <option value="<?= $cat ?>"><?= ucfirst($cat) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Precio de Venta ($) *</label>
                        <input type="number" step="0.01" min="0" name="precio" id="formPrecio" required class="form-control" placeholder="0.00">
                    </div>
                </div>

                <div class="form-group">
                    <label>Descripción / Ingredientes</label>
                    <textarea name="descripcion" id="formDescripcion" rows="3" class="form-control" placeholder="Detalle de ingredientes, medallones, salsas..."></textarea>
                </div>

                <div class="form-group">
                    <label>Imagen del Producto</label>
                    <input type="file" name="imagen" id="formImagen" accept="image/*" class="form-control" style="padding:7px 10px;">
                    <div id="vistaPreviaImg" style="margin-top:10px;display:none;align-items:center;gap:12px;">
                        <img id="imgPreview" src="" alt="Preview" style="width:60px;height:60px;border-radius:8px;object-fit:cover;border:1px solid #CBD5E1;">
                        <span id="imgPreviewNombre" style="font-size:0.85rem;color:#64748B;"></span>
                    </div>
                    <input type="hidden" name="imagen_existente" id="formImagenExistente" value="">
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                        <input type="checkbox" name="activo" id="formActivo" value="1" checked style="width:18px;height:18px;accent-color:#A30002;">
                        <span>Producto Activo (Disponible para venta en Web, Mesero y POS)</span>
                    </label>
                </div>
            </div>

            <div style="padding:16px 24px;background:#F8FAFC;border-top:1px solid #E2E8F0;display:flex;justify-content:flex-end;gap:12px;border-bottom-left-radius:16px;border-bottom-right-radius:16px;">
                <button type="button" class="btn-secondary" onclick="cerrarModal()">Cancelar</button>
                <button type="submit" class="btn-primary" id="btnGuardar">
                    <i class="fa-solid fa-floppy-disk"></i> Guardar Producto
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const modal = document.getElementById('modalProducto');
const modalTitulo = document.getElementById('modalTitulo');
const formAccion = document.getElementById('formAccion');
const formIdProducto = document.getElementById('formIdProducto');
const formNombre = document.getElementById('formNombre');
const formCategoria = document.getElementById('formCategoria');
const formPrecio = document.getElementById('formPrecio');
const formDescripcion = document.getElementById('formDescripcion');
const formActivo = document.getElementById('formActivo');
const formImagenExistente = document.getElementById('formImagenExistente');
const vistaPreviaImg = document.getElementById('vistaPreviaImg');
const imgPreview = document.getElementById('imgPreview');
const imgPreviewNombre = document.getElementById('imgPreviewNombre');

function abrirModalCrear() {
    modalTitulo.innerHTML = '<i class="fa-solid fa-burger" style="color:#A30002;margin-right:8px;"></i> Nuevo Producto';
    formAccion.value = 'crear';
    formIdProducto.value = '';
    formNombre.value = '';
    formCategoria.value = 'megacombos';
    formPrecio.value = '';
    formDescripcion.value = '';
    formActivo.checked = true;
    formImagenExistente.value = '';
    vistaPreviaImg.style.display = 'none';
    modal.classList.add('active');
}

function abrirModalEditar(p) {
    modalTitulo.innerHTML = '<i class="fa-solid fa-pen-to-square" style="color:#A30002;margin-right:8px;"></i> Editar Producto #' + p.idproducto;
    formAccion.value = 'editar';
    formIdProducto.value = p.idproducto;
    formNombre.value = p.nombre || '';
    formCategoria.value = p.categoria || 'megacombos';
    formPrecio.value = p.precio || '';
    formDescripcion.value = p.descripcion || '';
    formActivo.checked = parseInt(p.activo) === 1;
    formImagenExistente.value = p.imagen || '';

    if (p.imagen) {
        vistaPreviaImg.style.display = 'flex';
        imgPreview.src = '../mostaza_web/img/' + p.imagen;
        imgPreviewNombre.textContent = 'Actual: ' + p.imagen;
    } else {
        vistaPreviaImg.style.display = 'none';
    }

    modal.classList.add('active');
}

function cerrarModal() {
    modal.classList.remove('active');
}

// Cerrar con Escape o click fuera
window.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') cerrarModal();
});
modal.addEventListener('click', (e) => {
    if (e.target === modal) cerrarModal();
});
</script>
</body>
</html>
