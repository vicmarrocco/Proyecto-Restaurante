<?php
// ============================================================
// admin/mesero.php
// APP DE MESERO · Salón y Toma de Comandas en Vivo
// ============================================================
session_start();
require_once(__DIR__ . '/conexion.php');

// Identificación del mesero
if (isset($_POST['login_mesero'])) {
    $nombre = trim($_POST['nombre_mesero'] ?? 'Mesero de Turno');
    $_SESSION['mesero_nombre'] = $nombre;
    
    // Registro de entrada
    $stmtReg = $conn->prepare("INSERT INTO registro_turno_empleado (fecha, hora_entrada, estado, origen) VALUES (CURDATE(), NOW(), 'Activo', 'Mesero Web')");
    if ($stmtReg) {
        $stmtReg->execute();
        $_SESSION['mesero_idregistro'] = $stmtReg->insert_id;
    }
    
    header("Location: mesero.php");
    exit();
}

if (isset($_GET['cerrar_turno'])) {
    if (!empty($_SESSION['mesero_idregistro'])) {
        $conn->query("UPDATE registro_turno_empleado SET estado = 'Finalizado', hora_salida = NOW() WHERE idregistro = " . intval($_SESSION['mesero_idregistro']));
    }
    unset($_SESSION['mesero_nombre'], $_SESSION['mesero_idregistro']);
    header("Location: mesero.php");
    exit();
}

// Accion Ocupar Mesa manualmente
if (isset($_POST['accion_ocupar_mesa'])) {
    $numeroMesa = intval($_POST['numero_mesa']);
    if ($numeroMesa > 0) {
        $conn->query("UPDATE mesa SET estado = 'ocupada' WHERE numero_mesa = $numeroMesa");
    }
    header("Location: mesero.php?msg=mesa_ocupada");
    exit();
}

// Accion Desocupar / Liberar Mesa
if (isset($_POST['accion_desocupar_mesa']) || isset($_GET['desocupar_mesa'])) {
    $numeroMesa = intval($_POST['numero_mesa'] ?? $_GET['desocupar_mesa']);
    if ($numeroMesa > 0) {
        $conn->query("UPDATE mesa SET estado = 'disponible' WHERE numero_mesa = $numeroMesa");
        // Cierra pedidos activos de salón para esta mesa
        $conn->query("UPDATE pedido_web SET estado = 'Entregado' WHERE tipo_entrega = 'salon' AND numero_mesa = $numeroMesa AND estado IN ('Recibido', 'En Cocina', 'En Preparacion', 'Listo para Despacho', 'Listo')");
    }
    header("Location: mesero.php?msg=mesa_liberada");
    exit();
}

// Procesar toma de nueva comanda por mesa
if (isset($_POST['crear_comanda_mesa'])) {
    $numeroMesa = intval($_POST['numero_mesa']);
    $clienteNombre = trim($_POST['cliente_nombre'] ?? "Mesa $numeroMesa");
    $itemsJson = $_POST['items_json'] ?? '[]';
    $items = json_decode($itemsJson, true);
    
    if ($numeroMesa > 0 && !empty($items) && is_array($items)) {
        $subtotal = 0.0;
        foreach ($items as $it) {
            $subtotal += floatval($it['precio']) * intval($it['cantidad']);
        }
        $total = $subtotal;
        
        // Crear pedido en pedido_web
        $stmtP = $conn->prepare("INSERT INTO pedido_web (tipo_entrega, numero_mesa, direccion_envio, sucursal, metodo_pago, estado_pago, subtotal, total, estado, fecha) VALUES ('salon', ?, ?, 'Mostaza Formosa Centro', 'efectivo', 'Pendiente en Caja', ?, ?, 'Recibido', NOW())");
        $dirTxt = "Mesa $numeroMesa (Salón)";
        $stmtP->bind_param("isdd", $numeroMesa, $dirTxt, $subtotal, $total);
        $stmtP->execute();
        $idpedido = $stmtP->insert_id;
        
        // Insertar detalles
        $stmtD = $conn->prepare("INSERT INTO detalle_pedido_web (idpedido, idproducto, descripcion, modificadores, precio_unitario, cantidad, subtotal) VALUES (?, ?, ?, ?, ?, ?, ?)");
        foreach ($items as $it) {
            $idprod = intval($it['idproducto']);
            $desc = $it['nombre'];
            $mods = $it['modificadores'] ?? '';
            $pu = floatval($it['precio']);
            $qty = intval($it['cantidad']);
            $sub = $pu * $qty;
            $stmtD->bind_param("iisssid", $idpedido, $idprod, $desc, $mods, $pu, $qty, $sub);
            $stmtD->execute();
        }
        
        // Actualizar mesa a Ocupada
        $conn->query("UPDATE mesa SET estado = 'ocupada' WHERE numero_mesa = $numeroMesa");

        // Registrar auditoría estricta de comanda de salón
        $nombreMesero = $_SESSION['mesero_nombre'] ?? 'Mesero de Turno';
        $idEmpleadoMesero = $_SESSION['mesero_id'] ?? null;
        registrarAuditoriaPedido($conn, $idpedido, 'Recibido', "Comanda de Salón para Mesa #$numeroMesa", 'Mesero', $nombreMesero, $idEmpleadoMesero, "Comanda tomada por $nombreMesero para Mesa #$numeroMesa");
    }
    
    header("Location: mesero.php?msg=comanda_enviada");
    exit();
}

$meseroLogueado = !empty($_SESSION['mesero_nombre']);
$nombreMesero = $_SESSION['mesero_nombre'] ?? 'Mesero de Turno';

// Obtener catálogo de productos para la comanda
$resCat = $conn->query("SELECT * FROM producto WHERE activo = 1 ORDER BY categoria, nombre ASC");
$productosCatalogo = [];
if ($resCat) {
    while ($p = $resCat->fetch_assoc()) {
        $productosCatalogo[] = $p;
    }
}

// Obtener mesas
$resMesas = $conn->query("
    SELECT m.*, s.nombre AS seccion_nombre
    FROM mesa m
    LEFT JOIN seccion_mesa s ON m.fk_id_seccion = s.idseccion
    WHERE m.activa = 1
    ORDER BY m.numero_mesa ASC
");

// Comandas activas por mesa
$resActivas = $conn->query("
    SELECT p.* 
    FROM pedido_web p 
    WHERE p.tipo_entrega = 'salon' AND p.estado IN ('Recibido', 'En Cocina', 'En Preparacion', 'Listo para Despacho', 'Listo')
    ORDER BY p.idpedido DESC
");
$comandasPorMesa = [];
if ($resActivas) {
    while ($rowA = $resActivas->fetch_assoc()) {
        $num = intval($rowA['numero_mesa']);
        if (!isset($comandasPorMesa[$num])) {
            $comandasPorMesa[$num] = $rowA;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>App Mesero · Mostaza Salón Central</title>
<link rel="stylesheet" href="admin.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
body {
    background: #F8FAFC;
    color: #0F172A;
    font-family: 'Montserrat', sans-serif;
    margin: 0; padding: 0;
}
.mesero-topbar {
    background: #FFFFFF;
    border-bottom: 1px solid #E2E8F0;
    padding: 14px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: sticky; top: 0; z-index: 100;
    box-shadow: 0 2px 8px rgba(0,0,0,0.03);
}
.mesas-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 16px;
    padding: 20px;
}
.mesa-card {
    background: #FFFFFF;
    border-radius: 16px;
    padding: 18px;
    border: 2px solid #E2E8F0;
    cursor: pointer;
    transition: transform 0.2s, border-color 0.2s, box-shadow 0.2s;
    position: relative;
    box-shadow: 0 2px 8px rgba(0,0,0,0.02);
}
.mesa-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.06);
}
.mesa-card.disponible {
    border-color: #10B981;
    background: #FFFFFF;
}
.mesa-card.ocupada {
    border-color: #EF4444;
    background: #FFFDFD;
}
.mesa-card.lista {
    border-color: #F59E0B;
    background: #FFFDF5;
    animation: pulseGlow 1.5s infinite;
}
@keyframes pulseGlow {
    0%, 100% { box-shadow: 0 0 10px rgba(245, 158, 11, 0.3); }
    50% { box-shadow: 0 0 20px rgba(245, 158, 11, 0.6); }
}
.mesa-num {
    font-size: 1.6rem;
    font-weight: 900;
    color: #0F172A;
}
.mesa-status-badge {
    font-size: 0.72rem;
    font-weight: 800;
    padding: 4px 10px;
    border-radius: 20px;
    text-transform: uppercase;
}
.modal-comanda {
    display: none;
    position: fixed;
    top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(15,23,42,0.6);
    z-index: 9999;
    align-items: center; justify-content: center;
}
.modal-comanda.open { display: flex; }
.modal-comanda-box {
    background: #FFFFFF;
    color: #0F172A;
    border-radius: 18px;
    max-width: 880px;
    width: 95%;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    border: 1px solid #CBD5E1;
    overflow: hidden;
    box-shadow: 0 20px 40px rgba(0,0,0,0.15);
}
.catalog-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 10px;
    max-height: 340px;
    overflow-y: auto;
    padding: 10px;
    background: #F8FAFC;
    border-radius: 12px;
    border: 1px solid #E2E8F0;
}
.prod-item-btn {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    padding: 10px;
    border-radius: 10px;
    color: #0F172A;
    cursor: pointer;
    text-align: left;
    transition: 0.2s;
}
.prod-item-btn:hover {
    border-color: var(--admin-rojo);
    box-shadow: 0 2px 6px rgba(0,0,0,0.05);
}
</style>
</head>
<body>

<!-- TOPBAR MESERO -->
<div class="mesero-topbar">
    <div style="display:flex;align-items:center;gap:14px;">
        <img src="img/logo.png" alt="Mostaza" style="width:36px;height:36px;object-fit:contain;">
        <div>
            <h1 style="font-size:1.25rem;font-weight:900;color:var(--admin-rojo);margin:0;">
                MOSTAZA · APP DE MESERO
            </h1>
            <span style="font-size:0.8rem;color:#64748B;">
                Salón Sucursal Formosa · Mesero: <strong style="color:#0F172A;"><?= htmlspecialchars($nombreMesero) ?></strong>
            </span>
        </div>
    </div>

    <div style="display:flex;gap:10px;align-items:center;">
        <a href="index.php" class="btn-admin btn-admin-white btn-admin-sm">
            <i class="fa-solid fa-arrow-left"></i> Panel Admin
        </a>
        <a href="cocina.php" class="btn-admin btn-admin-white btn-admin-sm">
            <i class="fa-solid fa-fire-burner"></i> Cocina
        </a>
        <span class="badge badge-success">
            ● SALÓN ACTIVO
        </span>

        <?php if (!$meseroLogueado): ?>
            <button onclick="document.getElementById('modalLoginMesero').classList.add('open')" class="btn-admin btn-admin-red btn-admin-sm">
                <i class="fa-solid fa-id-badge"></i> Identificarse
            </button>
        <?php else: ?>
            <a href="mesero.php?cerrar_turno=1" onclick="return confirm('¿Finalizar turno de mesero?')" class="btn-admin btn-admin-sm" style="background:#DC2626;color:#fff;">
                <i class="fa-solid fa-door-open"></i> Salir
            </a>
        <?php endif; ?>
    </div>
</div>

<div style="padding:16px 20px 0;">
    <h2 style="font-size:1.2rem;font-weight:900;color:var(--admin-dark);margin:0 0 4px;">
        <i class="fa-solid fa-map-location-dot" style="color:var(--admin-rojo);"></i> Mapa de Mesas en Salón
    </h2>
    <p style="font-size:0.85rem;color:#64748B;margin:0;">Hacé clic en una mesa libre para abrir comanda y marchar a cocina, o en una mesa ocupada para ver su estado.</p>
</div>

<?php if (isset($_GET['msg'])): ?>
    <?php if ($_GET['msg'] === 'mesa_ocupada'): ?>
        <div style="background:#FEF2F2;border:1px solid #FCA5A5;color:#991B1B;padding:12px 18px;border-radius:10px;margin:14px 20px 0;font-weight:700;display:flex;align-items:center;gap:10px;">
            <i class="fa-solid fa-circle-check"></i> La mesa fue marcada como OCUPADA correctamente.
        </div>
    <?php elseif ($_GET['msg'] === 'mesa_liberada'): ?>
        <div style="background:#ECFDF5;border:1px solid #6EE7B7;color:#065F46;padding:12px 18px;border-radius:10px;margin:14px 20px 0;font-weight:700;display:flex;align-items:center;gap:10px;">
            <i class="fa-solid fa-circle-check"></i> La mesa fue DESOCUPADA y quedó LIBRE para nuevos comensales.
        </div>
    <?php elseif ($_GET['msg'] === 'comanda_enviada'): ?>
        <div style="background:#EFF6FF;border:1px solid #93C5FD;color:#1E40AF;padding:12px 18px;border-radius:10px;margin:14px 20px 0;font-weight:700;display:flex;align-items:center;gap:10px;">
            <i class="fa-solid fa-circle-check"></i> Comanda enviada a Cocina KDS exitosamente.
        </div>
    <?php endif; ?>
<?php endif; ?>

<!-- MAPA DE MESAS EN SALÓN -->
<div class="mesas-grid">
    <?php while ($m = $resMesas->fetch_assoc()): 
        $num = intval($m['numero_mesa']);
        $tieneComanda = isset($comandasPorMesa[$num]);
        $comanda = $tieneComanda ? $comandasPorMesa[$num] : null;
        $mesaOcupadaDB = ($m['estado'] === 'ocupada');
        $estaOcupada = $tieneComanda || $mesaOcupadaDB;
        
        $estadoClase = 'disponible';
        $estadoTexto = 'LIBRE';
        if ($estaOcupada) {
            if ($tieneComanda && ($comanda['estado'] === 'Listo para Despacho' || $comanda['estado'] === 'Listo')) {
                $estadoClase = 'lista';
                $estadoTexto = '🔔 ¡PLATOS LISTOS!';
            } elseif ($tieneComanda) {
                $estadoClase = 'ocupada';
                $estadoTexto = '🍳 EN COCINA';
            } else {
                $estadoClase = 'ocupada';
                $estadoTexto = '🔴 OCUPADA';
            }
        }
    ?>
    <div class="mesa-card <?= $estadoClase ?>" onclick="clickMesa(<?= $num ?>, '<?= htmlspecialchars($m['seccion_nombre'] ?? 'Salón Principal') ?>', <?= $m['capacidad'] ?? 4 ?>, <?= $estaOcupada ? 'true' : 'false' ?>, <?= $tieneComanda ? htmlspecialchars(json_encode($comanda)) : 'null' ?>)">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
            <span class="mesa-num">Mesa <?= $num ?></span>
            <span class="mesa-status-badge" style="background:<?= $estaOcupada ? ($estadoClase === 'lista' ? '#FEF3C7' : '#FEE2E2') : '#DCFCE7' ?>;color:<?= $estaOcupada ? ($estadoClase === 'lista' ? '#92400E' : '#991B1B') : '#166534' ?>;">
                <?= $estadoTexto ?>
            </span>
        </div>

        <div style="font-size:0.8rem;color:#64748B;margin-bottom:6px;">
            <i class="fa-solid fa-users"></i> Capacidad: <?= $m['capacidad'] ?? 4 ?> pers. · <?= htmlspecialchars($m['seccion_nombre'] ?? 'Salón Principal') ?>
        </div>

        <?php if ($tieneComanda): ?>
            <div style="background:#F8FAFC;padding:8px 10px;border-radius:8px;margin-top:6px;font-size:0.8rem;border:1px solid #E2E8F0;">
                <strong style="color:var(--admin-rojo);">Comanda #<?= $comanda['idpedido'] ?></strong><br>
                <span>Total: $<?= number_format($comanda['total'], 0, ',', '.') ?></span><br>
                <span style="color:#64748B;">🕒 <?= date('H:i', strtotime($comanda['fecha'])) ?> hs</span>
            </div>
        <?php elseif ($mesaOcupadaDB): ?>
            <div style="background:#FEF2F2;padding:8px 10px;border-radius:8px;margin-top:6px;font-size:0.8rem;border:1px solid #FECACA;color:#991B1B;font-weight:700;">
                <i class="fa-solid fa-user-group"></i> Clientes en mesa (sin pedido aún)
            </div>
        <?php else: ?>
            <div style="margin-top:8px;font-size:0.8rem;color:#059669;font-weight:800;">
                <i class="fa-solid fa-plus-circle"></i> Libre · Tocá para gestionar
            </div>
        <?php endif; ?>
    </div>
    <?php endwhile; ?>
</div>

<!-- MODAL TOMA DE COMANDA DE MESA -->
<div class="modal-comanda" id="modalComandaMesa">
    <div class="modal-comanda-box">
        <div style="padding:16px 22px;background:#FFFFFF;border-bottom:1px solid #E2E8F0;display:flex;justify-content:space-between;align-items:center;">
            <h3 style="color:var(--admin-rojo);margin:0;font-size:1.25rem;font-weight:900;" id="modalMesaTitulo">Comanda Mesa #</h3>
            <button onclick="document.getElementById('modalComandaMesa').classList.remove('open')" style="background:none;border:none;color:#64748B;font-size:1.3rem;cursor:pointer;">✕</button>
        </div>

        <div style="display:grid;grid-template-columns:1.2fr 1fr;gap:20px;padding:20px;overflow-y:auto;flex:1;">
            <!-- Catálogo para agregar -->
            <div>
                <strong style="color:#0F172A;font-size:0.92rem;display:block;margin-bottom:8px;">1. Seleccioná los platos del Menú:</strong>
                <input type="text" id="buscProd" placeholder="🔍 Buscar hamburguesa, papas, bebida..." oninput="filtrarProdsModal()" class="form-control" style="margin-bottom:10px;">
                
                <div class="catalog-list" id="catListModal">
                    <?php foreach ($productosCatalogo as $prod): ?>
                    <div class="prod-item-btn" data-nom="<?= strtolower(htmlspecialchars($prod['nombre'])) ?>" onclick="agregarItemComanda(<?= $prod['idproducto'] ?>, '<?= addslashes($prod['nombre']) ?>', <?= $prod['precio'] ?>)">
                        <strong style="color:#0F172A;display:block;font-size:0.88rem;"><?= htmlspecialchars($prod['nombre']) ?></strong>
                        <span style="font-size:0.8rem;color:var(--admin-rojo);font-weight:800;">$<?= number_format($prod['precio'], 0, ',', '.') ?> · <?= htmlspecialchars($prod['categoria']) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Resumen de Comanda de Mesa -->
            <div style="background:#F8FAFC;border-radius:14px;padding:16px;display:flex;flex-direction:column;border:1px solid #E2E8F0;">
                <strong style="color:#0F172A;font-size:0.92rem;margin-bottom:8px;">2. Resumen de la Mesa:</strong>
                
                <div id="itemsComandaContainer" style="flex:1;max-height:220px;overflow-y:auto;margin-bottom:12px;">
                    <p style="color:#64748B;font-size:0.85rem;text-align:center;padding:20px;">No agregaste ningún ítem a la mesa todavía.</p>
                </div>

                <div style="border-top:1px solid #E2E8F0;padding-top:10px;margin-bottom:14px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;">
                        <span style="font-size:1rem;font-weight:800;color:#334155;">TOTAL MESA:</span>
                        <span style="font-size:1.3rem;font-weight:900;color:var(--admin-rojo);" id="totalComandaTxt">$0</span>
                    </div>
                </div>

                <form method="post" action="mesero.php" id="formComandaMesa">
                    <input type="hidden" name="numero_mesa" id="form_num_mesa">
                    <input type="hidden" name="items_json" id="form_items_json">
                    <button type="submit" name="crear_comanda_mesa" class="btn-admin btn-admin-red" style="width:100%;justify-content:center;padding:12px;font-size:0.95rem;">
                        <i class="fa-solid fa-paper-plane"></i> Enviar Comanda a Cocina KDS
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Accion Mesa Libre -->
<div class="modal-comanda" id="modalMesaLibre">
    <div class="modal-comanda-box" style="max-width:440px;padding:24px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
            <h3 style="color:var(--admin-dark);margin:0;font-size:1.25rem;font-weight:900;" id="freeMesaTitulo">Mesa #</h3>
            <button onclick="document.getElementById('modalMesaLibre').classList.remove('open')" style="background:none;border:none;color:#64748B;font-size:1.3rem;cursor:pointer;">✕</button>
        </div>
        <p style="font-size:0.85rem;color:#64748B;margin-bottom:20px;" id="freeMesaSub">Esta mesa está libre. ¿Qué acción deseás realizar?</p>
        
        <div style="display:flex;flex-direction:column;gap:12px;">
            <button type="button" onclick="abrirModalComandaDirecta()" class="btn-admin btn-admin-red" style="padding:14px;font-size:0.95rem;justify-content:center;">
                <i class="fa-solid fa-utensils"></i> Tomar Comanda / Abrir Pedido
            </button>
            
            <form method="post" action="mesero.php">
                <input type="hidden" name="numero_mesa" id="form_ocupar_num_mesa">
                <button type="submit" name="accion_ocupar_mesa" class="btn-admin btn-admin-white" style="width:100%;padding:14px;font-size:0.95rem;justify-content:center;border-color:#CBD5E1;">
                    <i class="fa-solid fa-user-group" style="color:var(--admin-rojo);"></i> Marcar Mesa como Ocupada (Sin Comanda)
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Modal Accion Mesa Ocupada -->
<div class="modal-comanda" id="modalMesaOcupada">
    <div class="modal-comanda-box" style="max-width:480px;padding:24px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
            <h3 style="color:#DC2626;margin:0;font-size:1.25rem;font-weight:900;" id="occMesaTitulo">Mesa # - Ocupada</h3>
            <button onclick="document.getElementById('modalMesaOcupada').classList.remove('open')" style="background:none;border:none;color:#64748B;font-size:1.3rem;cursor:pointer;">✕</button>
        </div>
        
        <div id="occMesaInfo" style="background:#F8FAFC;padding:14px;border-radius:12px;border:1px solid #E2E8F0;margin-bottom:20px;font-size:0.88rem;">
            <!-- Renderizado dinámicamente -->
        </div>

        <div style="display:flex;flex-direction:column;gap:12px;">
            <button type="button" onclick="abrirModalComandaDirecta()" class="btn-admin btn-admin-white" style="padding:12px;font-size:0.92rem;justify-content:center;border-color:#CBD5E1;">
                <i class="fa-solid fa-plus-circle" style="color:var(--admin-rojo);"></i> Agregar Ítems / Marchar más a Cocina
            </button>
            
            <form method="post" action="mesero.php" onsubmit="return confirm('¿Confirmás desocupar y liberar la mesa? Si tiene comandas abiertas, se darán por finalizadas.')">
                <input type="hidden" name="numero_mesa" id="form_desocupar_num_mesa">
                <button type="submit" name="accion_desocupar_mesa" class="btn-admin" style="width:100%;padding:14px;font-size:0.95rem;justify-content:center;background:#10B981;color:#fff;">
                    <i class="fa-solid fa-door-open"></i> Desocupar y Liberar Mesa
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Modal Login Mesero -->
<div class="modal-comanda" id="modalLoginMesero">
    <div class="modal-comanda-box" style="max-width:400px;padding:24px;">
        <h3 style="color:var(--admin-rojo);margin:0 0 12px;font-size:1.3rem;font-weight:900;">🍽️ Identificación de Mesero</h3>
        <p style="font-size:0.85rem;color:#64748B;margin-bottom:16px;">Ingresá tu nombre para registrar tu turno de atención en salón.</p>
        
        <form method="post" action="mesero.php">
            <div class="form-group">
                <input type="text" name="nombre_mesero" required placeholder="Ej: Lucas Benítez" class="form-control">
            </div>

            <button type="submit" name="login_mesero" class="btn-admin btn-admin-red" style="width:100%;justify-content:center;padding:12px;">
                Entrar al Salón
            </button>
        </form>
    </div>
</div>

<script>
let itemsComanda = [];
let mesaSeleccionada = 0;
let comandaInfoActual = null;

function clickMesa(numMesa, seccion, capacidad, estaOcupada, comandaInfo) {
    mesaSeleccionada = numMesa;
    comandaInfoActual = comandaInfo;
    document.getElementById('form_num_mesa').value = numMesa;
    document.getElementById('modalMesaTitulo').innerText = `Comanda Mesa #${numMesa} (${seccion})`;
    
    if (estaOcupada) {
        document.getElementById('occMesaTitulo').innerText = `Mesa #${numMesa} · OCUPADA`;
        document.getElementById('form_desocupar_num_mesa').value = numMesa;
        
        let html = `
            <div style="display:flex;justify-content:space-between;margin-bottom:8px;">
                <span style="color:#64748B;">Sector:</span>
                <strong>${seccion}</strong>
            </div>
            <div style="display:flex;justify-content:space-between;margin-bottom:8px;">
                <span style="color:#64748B;">Capacidad:</span>
                <strong>${capacidad} personas</strong>
            </div>
        `;
        
        if (comandaInfo) {
            html += `
                <div style="margin-top:10px;padding-top:10px;border-top:1px solid #E2E8F0;">
                    <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
                        <span style="color:var(--admin-rojo);font-weight:800;">Comanda Activa:</span>
                        <strong>#${comandaInfo.idpedido}</strong>
                    </div>
                    <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
                        <span style="color:#64748B;">Total Acumulado:</span>
                        <strong style="color:#15803D;">$${Number(comandaInfo.total).toLocaleString('es-AR')}</strong>
                    </div>
                    <div style="display:flex;justify-content:space-between;">
                        <span style="color:#64748B;">Estado en Cocina:</span>
                        <span class="badge badge-warning">${comandaInfo.estado}</span>
                    </div>
                </div>
            `;
        } else {
            html += `
                <div style="margin-top:8px;padding:8px;background:#FEF2F2;color:#991B1B;border-radius:6px;font-size:0.82rem;">
                    <i class="fa-solid fa-circle-info"></i> Mesa ocupada por clientes (sin comanda ingresada todavía).
                </div>
            `;
        }
        
        document.getElementById('occMesaInfo').innerHTML = html;
        document.getElementById('modalMesaOcupada').classList.add('open');
    } else {
        document.getElementById('freeMesaTitulo').innerText = `Mesa #${numMesa} · DISPONIBLE`;
        document.getElementById('freeMesaSub').innerText = `${seccion} · Capacidad para ${capacidad} personas. Seleccioná una acción:`;
        document.getElementById('form_ocupar_num_mesa').value = numMesa;
        document.getElementById('modalMesaLibre').classList.add('open');
    }
}

function abrirModalComandaDirecta() {
    document.getElementById('modalMesaLibre').classList.remove('open');
    document.getElementById('modalMesaOcupada').classList.remove('open');
    itemsComanda = [];
    renderItemsComanda();
    document.getElementById('modalComandaMesa').classList.add('open');
}

function agregarItemComanda(idproducto, nombre, precio) {
    const exist = itemsComanda.find(x => x.idproducto === idproducto);
    if (exist) {
        exist.cantidad++;
    } else {
        itemsComanda.push({ idproducto, nombre, precio, cantidad: 1, modificadores: '' });
    }
    renderItemsComanda();
}

function renderItemsComanda() {
    const cont = document.getElementById('itemsComandaContainer');
    if (itemsComanda.length === 0) {
        cont.innerHTML = '<p style="color:#64748B;font-size:0.85rem;text-align:center;padding:20px;">No agregaste ningún ítem a la mesa todavía.</p>';
        document.getElementById('totalComandaTxt').innerText = '$0';
        document.getElementById('form_items_json').value = '[]';
        return;
    }

    let html = '';
    let total = 0;

    itemsComanda.forEach((it, idx) => {
        const sub = it.precio * it.cantidad;
        total += sub;
        html += `
            <div style="background:#FFFFFF;padding:8px 10px;border-radius:8px;margin-bottom:6px;border:1px solid #E2E8F0;">
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <strong style="color:#0F172A;font-size:0.85rem;">${it.cantidad}x ${it.nombre}</strong>
                    <span style="color:var(--admin-rojo);font-weight:800;font-size:0.85rem;">$${sub.toLocaleString('es-AR')}</span>
                </div>
                <div style="display:flex;gap:6px;margin-top:6px;align-items:center;">
                    <button type="button" onclick="cambiarQty(${idx}, -1)" style="background:#F1F5F9;border:1px solid #CBD5E1;color:#0F172A;width:24px;height:24px;border-radius:4px;cursor:pointer;font-weight:bold;">-</button>
                    <button type="button" onclick="cambiarQty(${idx}, 1)" style="background:#F1F5F9;border:1px solid #CBD5E1;color:#0F172A;width:24px;height:24px;border-radius:4px;cursor:pointer;font-weight:bold;">+</button>
                    <input type="text" placeholder="Nota: sin sal, etc." value="${it.modificadores || ''}" onchange="setMod(${idx}, this.value)" 
                           style="flex:1;background:#F8FAFC;border:1px solid #CBD5E1;color:#0F172A;font-size:0.75rem;padding:3px 6px;border-radius:4px;">
                </div>
            </div>
        `;
    });

    cont.innerHTML = html;
    document.getElementById('totalComandaTxt').innerText = `$${total.toLocaleString('es-AR')}`;
    document.getElementById('form_items_json').value = JSON.stringify(itemsComanda);
}

function cambiarQty(idx, delta) {
    itemsComanda[idx].cantidad += delta;
    if (itemsComanda[idx].cantidad <= 0) {
        itemsComanda.splice(idx, 1);
    }
    renderItemsComanda();
}

function setMod(idx, val) {
    itemsComanda[idx].modificadores = val;
    document.getElementById('form_items_json').value = JSON.stringify(itemsComanda);
}

function filtrarProdsModal() {
    const q = document.getElementById('buscProd').value.toLowerCase().trim();
    document.querySelectorAll('.prod-item-btn').forEach(btn => {
        const nom = btn.dataset.nom || '';
        btn.style.display = nom.includes(q) ? 'block' : 'none';
    });
}

setInterval(function() {
    if (!document.querySelector('.modal-comanda.open')) {
        window.location.reload();
    }
}, 12000);
</script>

</body>
</html>
