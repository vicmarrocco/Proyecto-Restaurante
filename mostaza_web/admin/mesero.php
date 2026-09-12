<?php
// ============================================================
// admin/mesero.php
// APP DE MESERO · Salón y Toma de Comandas en Vivo
// Cumple con el requerimiento R48 del PDF.
// ============================================================
session_start();
require_once(__DIR__ . '/../conexion.php');

// Identificación del mesero
if (isset($_POST['login_mesero'])) {
    $nombre = trim($_POST['nombre_mesero'] ?? 'Mesero de Turno');
    $_SESSION['mesero_nombre'] = $nombre;
    
    // Registro de entrada
    $stmtReg = $conn->prepare("INSERT INTO registro_turno_empleado (nombre_empleado, rol, fecha, hora_entrada, estado, origen) VALUES (?, 'Mesero', CURDATE(), NOW(), 'Activo', 'App Mesero')");
    $stmtReg->bind_param("s", $nombre);
    $stmtReg->execute();
    $_SESSION['mesero_idregistro'] = $stmtReg->insert_id;
    
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
        $conn->query("UPDATE mesa SET estado = 'Ocupada' WHERE numero = $numeroMesa OR numero_mesa = $numeroMesa");
    }
    
    header("Location: mesero.php?msg=comanda_enviada");
    exit();
}

$meseroLogueado = !empty($_SESSION['mesero_nombre']);
$nombreMesero = $_SESSION['mesero_nombre'] ?? 'Mesero Invitado';

// Obtener catálogo de productos para la comanda
$resCat = $conn->query("SELECT * FROM producto WHERE activo = 1 ORDER BY categoria, nombre ASC");
$productosCatalogo = [];
while ($p = $resCat->fetch_assoc()) {
    $productosCatalogo[] = $p;
}

// Obtener mesas y comandas activas
$resMesas = $conn->query("
    SELECT m.*, s.nombre AS seccion_nombre, s.icono AS seccion_icono
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
while ($rowA = $resActivas->fetch_assoc()) {
    $num = intval($rowA['numero_mesa']);
    if (!isset($comandasPorMesa[$num])) {
        $comandasPorMesa[$num] = $rowA;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>App Mesero · Mostaza Salón</title>
<link rel="stylesheet" href="admin.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
:root {
    --rojo: #A30002;
    --amarillo: #FBB728;
    --dark: #0F172A;
    --card: #1E293B;
}
body {
    background: #0B0F19;
    color: #F8FAFC;
    font-family: 'Montserrat', sans-serif;
    margin: 0; padding: 0;
}
.mesero-topbar {
    background: #0F172A;
    border-bottom: 2px solid #1E293B;
    padding: 14px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: sticky; top: 0; z-index: 100;
}
.mesas-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 16px;
    padding: 20px;
}
.mesa-card {
    background: #1E293B;
    border-radius: 16px;
    padding: 18px;
    border: 2px solid #334155;
    cursor: pointer;
    transition: transform 0.2s, border-color 0.2s, box-shadow 0.2s;
    position: relative;
    overflow: hidden;
}
.mesa-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.3);
}
.mesa-card.disponible {
    border-color: #059669;
    background: linear-gradient(145deg, #064E3B 0%, #1E293B 70%);
}
.mesa-card.ocupada {
    border-color: #DC2626;
    background: linear-gradient(145deg, #7F1D1D 0%, #1E293B 70%);
}
.mesa-card.lista {
    border-color: #F59E0B;
    background: linear-gradient(145deg, #78350F 0%, #1E293B 70%);
    animation: pulseGlow 1.5s infinite;
}
@keyframes pulseGlow {
    0%, 100% { box-shadow: 0 0 15px rgba(245, 158, 11, 0.4); }
    50% { box-shadow: 0 0 25px rgba(245, 158, 11, 0.8); }
}
.mesa-num {
    font-size: 1.8rem;
    font-weight: 900;
    color: #fff;
}
.mesa-status-badge {
    font-size: 0.75rem;
    font-weight: 800;
    padding: 4px 10px;
    border-radius: 20px;
    text-transform: uppercase;
}
.modal-comanda {
    display: none;
    position: fixed;
    top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,0.85);
    z-index: 9999;
    align-items: center; justify-content: center;
}
.modal-comanda.open { display: flex; }
.modal-comanda-box {
    background: #1E293B;
    border-radius: 20px;
    max-width: 900px;
    width: 95%;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    border: 1px solid #334155;
    overflow: hidden;
}
.catalog-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 10px;
    max-height: 340px;
    overflow-y: auto;
    padding: 10px;
    background: #0F172A;
    border-radius: 12px;
}
.prod-item-btn {
    background: #1E293B;
    border: 1px solid #334155;
    padding: 10px;
    border-radius: 10px;
    color: #fff;
    cursor: pointer;
    text-align: left;
    transition: 0.2s;
}
.prod-item-btn:hover {
    border-color: var(--amarillo);
    background: #334155;
}
</style>
</head>
<body>

<!-- TOPBAR MESERO (R48) -->
<div class="mesero-topbar">
    <div style="display:flex;align-items:center;gap:14px;">
        <div style="font-size:1.8rem;">🍽️</div>
        <div>
            <h1 style="font-size:1.3rem;font-weight:900;color:var(--amarillo);margin:0;">
                MOSTAZA · APP DE MESERO
            </h1>
            <span style="font-size:0.8rem;color:#94A3B8;">
                Salón Sucursal Formosa · Mesero: <strong style="color:#fff;"><?= htmlspecialchars($nombreMesero) ?></strong>
            </span>
        </div>
    </div>

    <div style="display:flex;gap:10px;align-items:center;">
        <span style="background:#064E3B;color:#A7F3D0;font-weight:800;padding:6px 12px;border-radius:20px;font-size:0.8rem;">
            ● SALÓN ACTIVO
        </span>

        <?php if (!$meseroLogueado): ?>
            <button onclick="document.getElementById('modalLoginMesero').classList.add('open')" style="background:var(--rojo);border:none;color:#fff;padding:8px 16px;border-radius:8px;font-weight:800;cursor:pointer;">
                <i class="fa-solid fa-id-badge"></i> Identificarse
            </button>
        <?php else: ?>
            <a href="mesero.php?cerrar_turno=1" onclick="return confirm('¿Finalizar turno de mesero?')" style="background:#DC2626;color:#fff;text-decoration:none;padding:8px 14px;border-radius:8px;font-weight:700;font-size:0.85rem;">
                <i class="fa-solid fa-door-open"></i> Salir
            </a>
        <?php endif; ?>
    </div>
</div>

<div style="padding:14px 20px 0;">
    <h2 style="font-size:1.2rem;font-weight:800;color:#FBB728;margin:0 0 4px;">
        <i class="fa-solid fa-map-location-dot"></i> Mapa de Mesas en Salón
    </h2>
    <p style="font-size:0.85rem;color:#94A3B8;margin:0;">Tocá una mesa libre para abrir comanda y enviar a cocina, o una mesa ocupada para ver su comanda.</p>
</div>

<!-- MAPA DE MESAS EN SALÓN -->
<div class="mesas-grid">
    <?php while ($m = $resMesas->fetch_assoc()): 
        $num = intval($m['numero_mesa'] ?: $m['numero']);
        $tieneComanda = isset($comandasPorMesa[$num]);
        $comanda = $tieneComanda ? $comandasPorMesa[$num] : null;
        
        $estadoClase = 'disponible';
        $estadoTexto = 'LIBRE';
        if ($tieneComanda) {
            if ($comanda['estado'] === 'Listo para Despacho' || $comanda['estado'] === 'Listo') {
                $estadoClase = 'lista';
                $estadoTexto = '🔔 ¡PLATOS LISTOS!';
            } else {
                $estadoClase = 'ocupada';
                $estadoTexto = '🍳 EN COCINA';
            }
        }
    ?>
    <div class="mesa-card <?= $estadoClase ?>" onclick="abrirMesa(<?= $num ?>, <?= $tieneComanda ? 'true' : 'false' ?>, <?= $tieneComanda ? htmlspecialchars(json_encode($comanda)) : 'null' ?>)">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
            <span class="mesa-num">Mesa <?= $num ?></span>
            <span class="mesa-status-badge" style="background:<?= $tieneComanda ? ($estadoClase === 'lista' ? '#F59E0B' : '#DC2626') : '#059669' ?>;color:#fff;">
                <?= $estadoTexto ?>
            </span>
        </div>

        <div style="font-size:0.8rem;color:#CBD5E1;margin-bottom:6px;">
            <i class="fa-solid fa-users"></i> Capacidad: <?= $m['capacidad'] ?? 4 ?> pers. · <?= htmlspecialchars($m['seccion_nombre'] ?? 'Salón Principal') ?>
        </div>

        <?php if ($tieneComanda): ?>
            <div style="background:rgba(0,0,0,0.3);padding:8px;border-radius:8px;margin-top:6px;font-size:0.8rem;">
                <strong style="color:var(--amarillo);">Comanda #<?= $comanda['idpedido'] ?></strong><br>
                <span>Total: $<?= number_format($comanda['total'], 0, ',', '.') ?></span><br>
                <span style="color:#94A3B8;">🕒 <?= date('H:i', strtotime($comanda['fecha'])) ?> hs</span>
            </div>
        <?php else: ?>
            <div style="margin-top:8px;font-size:0.8rem;color:#6EE7B7;font-weight:700;">
                <i class="fa-solid fa-plus-circle"></i> Tocá para Tomar Comanda
            </div>
        <?php endif; ?>
    </div>
    <?php endwhile; ?>
</div>

<!-- MODAL TOMA DE COMANDA DE MESA -->
<div class="modal-comanda" id="modalComandaMesa">
    <div class="modal-comanda-box">
        <div style="padding:16px 24px;background:#0F172A;border-bottom:1px solid #334155;display:flex;justify-content:space-between;align-items:center;">
            <h3 style="color:var(--amarillo);margin:0;font-size:1.3rem;" id="modalMesaTitulo">Comanda Mesa #</h3>
            <button onclick="document.getElementById('modalComandaMesa').classList.remove('open')" style="background:none;border:none;color:#94A3B8;font-size:1.3rem;cursor:pointer;">✕</button>
        </div>

        <div style="display:grid;grid-template-columns:1.2fr 1fr;gap:20px;padding:20px;overflow-y:auto;flex:1;">
            <!-- Catálogo para agregar -->
            <div>
                <strong style="color:#fff;font-size:0.95rem;display:block;margin-bottom:8px;">1. Seleccioná los platos del Menú:</strong>
                <input type="text" id="buscProd" placeholder="Buscar hamburguesa, papas, bebida..." oninput="filtrarProdsModal()" 
                       style="width:100%;padding:10px;border-radius:8px;border:1px solid #334155;background:#0F172A;color:#fff;margin-bottom:10px;">
                
                <div class="catalog-list" id="catListModal">
                    <?php foreach ($productosCatalogo as $prod): ?>
                    <div class="prod-item-btn" data-nom="<?= strtolower(htmlspecialchars($prod['nombre'])) ?>" onclick="agregarItemComanda(<?= $prod['idproducto'] ?>, '<?= addslashes($prod['nombre']) ?>', <?= $prod['precio'] ?>)">
                        <strong style="color:var(--amarillo);display:block;font-size:0.9rem;"><?= htmlspecialchars($prod['nombre']) ?></strong>
                        <span style="font-size:0.8rem;color:#94A3B8;">$<?= number_format($prod['precio'], 0, ',', '.') ?> · <?= htmlspecialchars($prod['categoria']) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Resumen de Comanda de Mesa -->
            <div style="background:#0F172A;border-radius:14px;padding:16px;display:flex;flex-direction:column;border:1px solid #334155;">
                <strong style="color:#fff;font-size:0.95rem;margin-bottom:8px;">2. Pedido de la Mesa:</strong>
                
                <div id="itemsComandaContainer" style="flex:1;max-height:220px;overflow-y:auto;margin-bottom:12px;">
                    <p style="color:#64748B;font-size:0.85rem;text-align:center;padding:20px;">No agregaste ningún ítem a la mesa todavía.</p>
                </div>

                <div style="border-top:1px solid #334155;padding-top:10px;margin-bottom:14px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;">
                        <span style="font-size:1.1rem;font-weight:800;">TOTAL MESA:</span>
                        <span style="font-size:1.3rem;font-weight:900;color:var(--rojo);" id="totalComandaTxt">$0</span>
                    </div>
                </div>

                <form method="post" action="mesero.php" id="formComandaMesa">
                    <input type="hidden" name="numero_mesa" id="form_num_mesa">
                    <input type="hidden" name="items_json" id="form_items_json">
                    <button type="submit" name="crear_comanda_mesa" class="btn-kds btn-kds-ready" style="width:100%;padding:14px;font-size:1rem;">
                        <i class="fa-solid fa-paper-plane"></i> Enviar Comanda a Cocina
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Login Mesero -->
<div class="modal-comanda" id="modalLoginMesero">
    <div class="modal-comanda-box" style="max-width:400px;padding:24px;">
        <h3 style="color:var(--amarillo);margin:0 0 12px;font-size:1.3rem;">🍽️ Identificación de Mesero</h3>
        <p style="font-size:0.85rem;color:#94A3B8;margin-bottom:16px;">Ingresá tu nombre para registrar tu turno de atención en salón.</p>
        
        <form method="post" action="mesero.php">
            <input type="text" name="nombre_mesero" required placeholder="Ej: Lucas Benítez" 
                   style="width:100%;padding:10px 14px;border-radius:8px;border:1px solid #334155;background:#0F172A;color:#fff;font-size:1rem;margin-bottom:16px;">

            <button type="submit" name="login_mesero" style="width:100%;background:var(--rojo);color:#fff;border:none;padding:12px;border-radius:10px;font-weight:800;cursor:pointer;">
                Entrar al Salón
            </button>
        </form>
    </div>
</div>

<script>
let itemsComanda = [];
let mesaSeleccionada = 0;

function abrirMesa(numMesa, ocupada, comandaInfo) {
    mesaSeleccionada = numMesa;
    document.getElementById('form_num_mesa').value = numMesa;
    document.getElementById('modalMesaTitulo').innerText = `Comanda Mesa #${numMesa}`;
    
    if (ocupada && comandaInfo) {
        alert(`Mesa #${numMesa} ya tiene una comanda en curso (#${comandaInfo.idpedido}) por un total de $${comandaInfo.total}.
Estado: ${comandaInfo.estado}`);
        return;
    }
    
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
            <div style="background:#1E293B;padding:8px 10px;border-radius:8px;margin-bottom:6px;border:1px solid #334155;">
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <strong style="color:#fff;font-size:0.85rem;">${it.cantidad}x ${it.nombre}</strong>
                    <span style="color:var(--amarillo);font-weight:800;font-size:0.85rem;">$${sub.toLocaleString('es-AR')}</span>
                </div>
                <div style="display:flex;gap:6px;margin-top:4px;">
                    <button type="button" onclick="cambiarQty(${idx}, -1)" style="background:#334155;border:none;color:#fff;width:24px;height:24px;border-radius:4px;cursor:pointer;">-</button>
                    <button type="button" onclick="cambiarQty(${idx}, 1)" style="background:#334155;border:none;color:#fff;width:24px;height:24px;border-radius:4px;cursor:pointer;">+</button>
                    <input type="text" placeholder="Nota: sin sal, etc." value="${it.modificadores || ''}" onchange="setMod(${idx}, this.value)" 
                           style="flex:1;background:#0F172A;border:1px solid #334155;color:#fff;font-size:0.75rem;padding:2px 6px;border-radius:4px;">
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

// Auto-recargar cada 12 segundos para actualizar el estado de comandas listas
setInterval(function() {
    if (!document.querySelector('.modal-comanda.open')) {
        window.location.reload();
    }
}, 12000);
</script>

</body>
</html>
