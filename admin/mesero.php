<?php
// ============================================================
// admin/mesero.php
// APP DE MESERO · Salón y Toma de Comandas en Vivo
// ============================================================
session_start();
require_once(__DIR__ . '/conexion.php');

// Función de respaldo en caso de que no esté declarada globalmente
if (!function_exists('registrarAuditoriaPedido')) {
    function registrarAuditoriaPedido($conn, $idpedido, $nuevoEstado, $accion, $rol, $nombre, $idEmpleado, $motivo = '') {
        $idpedido = intval($idpedido);
        if ($idpedido <= 0) return false;
        $stmtP = $conn->prepare("SELECT estado FROM pedido_web WHERE idpedido = ?");
        $stmtP->bind_param("i", $idpedido);
        $stmtP->execute();
        $p = $stmtP->get_result()->fetch_assoc();
        $estadoAnterior = $p['estado'] ?? 'Recibido';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $stmtAud = $conn->prepare("INSERT INTO pedido_auditoria (idpedido, estado_anterior, estado_nuevo, accion, rol_responsable, idempleado, nombre_responsable, detalles, ip_origen) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmtAud) {
            $stmtAud->bind_param("issssisss", $idpedido, $estadoAnterior, $nuevoEstado, $accion, $rol, $idEmpleado, $nombre, $motivo, $ip);
            $stmtAud->execute();
        }
        $conn->query("UPDATE pedido_web SET estado = '" . $conn->real_escape_string($nuevoEstado) . "' WHERE idpedido = $idpedido");
        return true;
    }
}

// ============================================================
// ADMINISTRACIÓN: ASIGNAR MÚLTIPLES SECTORES AL MESERO
// ============================================================
if (isset($_POST['guardar_sectores_mesero'])) {
    $idEmpMesero = intval($_POST['idempleado'] ?? 0);
    $sectoresSeleccionados = isset($_POST['sectores']) && is_array($_POST['sectores']) ? array_values(array_filter(array_map('intval', $_POST['sectores']))) : [];

    if ($idEmpMesero > 0) {
        if (empty($sectoresSeleccionados)) {
            header("Location: mesero.php?msg=error_sin_sector&panel=sectores");
            exit();
        }

        // Limpiar sectores anteriores y asignar todos los nuevos seleccionados
        $stmtDel = $conn->prepare("DELETE FROM empleado_seccion WHERE idempleado = ?");
        $stmtDel->bind_param("i", $idEmpMesero);
        $stmtDel->execute();

        $stmtIns = $conn->prepare("INSERT IGNORE INTO empleado_seccion (idempleado, idseccion) VALUES (?, ?)");
        foreach ($sectoresSeleccionados as $idSec) {
            $stmtIns->bind_param("ii", $idEmpMesero, $idSec);
            $stmtIns->execute();
        }

        // Actualizar fk_id_seccion principal con el primer sector seleccionado para compatibilidad
        $secPrincipal = intval($sectoresSeleccionados[0]);
        $stmtUpdEmp = $conn->prepare("UPDATE empleado SET fk_id_seccion = ? WHERE idempleado = ?");
        $stmtUpdEmp->bind_param("ii", $secPrincipal, $idEmpMesero);
        $stmtUpdEmp->execute();

        // Si el mesero editado es el que está en sesión actual, actualizar su sesión
        if (!empty($_SESSION['mesero_id']) && intval($_SESSION['mesero_id']) === $idEmpMesero) {
            $infoActualizada = obtenerSeccionesMesero($conn, $idEmpMesero);
            $_SESSION['mesero_secciones'] = $infoActualizada['ids'];
            $_SESSION['mesero_seccion_nombre'] = $infoActualizada['texto'];
        }

        header("Location: mesero.php?msg=sectores_actualizados&panel=sectores");
        exit();
    }
}

// Crear nuevo mesero desde el panel admin con múltiples sectores
if (isset($_POST['crear_nuevo_mesero'])) {
    $nom = trim($_POST['nombre'] ?? '');
    $ape = trim($_POST['apellido'] ?? '');
    $emailM = trim($_POST['email'] ?? '');
    $telM = trim($_POST['telefono'] ?? '');
    $sectoresNuevos = isset($_POST['sectores']) && is_array($_POST['sectores']) ? array_values(array_filter(array_map('intval', $_POST['sectores']))) : [1];
    if (empty($sectoresNuevos)) $sectoresNuevos = [1];

    if (!empty($nom) && !empty($emailM)) {
        $secPrin = intval($sectoresNuevos[0]);
        $stmtNewM = $conn->prepare("INSERT INTO empleado (nombre, apellido, email, telefono, fk_id_rol, fk_id_seccion, avatar, activo) VALUES (?, ?, ?, ?, 4, ?, '🤵', 1)");
        if ($stmtNewM) {
            $stmtNewM->bind_param("ssssi", $nom, $ape, $emailM, $telM, $secPrin);
            if ($stmtNewM->execute()) {
                $newIdEmp = $stmtNewM->insert_id;
                $stmtInsS = $conn->prepare("INSERT IGNORE INTO empleado_seccion (idempleado, idseccion) VALUES (?, ?)");
                foreach ($sectoresNuevos as $sId) {
                    $stmtInsS->bind_param("ii", $newIdEmp, $sId);
                    $stmtInsS->execute();
                }
                header("Location: mesero.php?msg=mesero_creado&panel=sectores");
                exit();
            }
        }
    }
    header("Location: mesero.php?msg=error_crear_mesero&panel=sectores");
    exit();
}

// Identificación del mesero
if (isset($_POST['login_mesero'])) {
    $idEmpSel = intval($_POST['idempleado_mesero'] ?? 0);
    $nombre = trim($_POST['nombre_mesero'] ?? 'Mesero de Turno');

    if ($idEmpSel > 0) {
        $resEM = $conn->query("SELECT idempleado, nombre, apellido FROM empleado WHERE idempleado = $idEmpSel LIMIT 1");
        if ($resEM && $rowEM = $resEM->fetch_assoc()) {
            $nombre = trim($rowEM['nombre'] . ' ' . $rowEM['apellido']);
            $_SESSION['mesero_id'] = intval($rowEM['idempleado']);
            $infoSecLogin = obtenerSeccionesMesero($conn, $idEmpSel);
            $_SESSION['mesero_secciones'] = $infoSecLogin['ids'];
            $_SESSION['mesero_seccion_nombre'] = $infoSecLogin['texto'];
        }
    }
    $_SESSION['mesero_nombre'] = $nombre;
    
    // Registro de entrada
    $idEmpReg = $_SESSION['mesero_id'] ?? 1;
    $stmtReg = $conn->prepare("INSERT INTO registro_turno_empleado (fk_id_empleado, fk_id_turno, fecha, hora_entrada, estado, origen) VALUES (?, 1, CURDATE(), NOW(), 'Activo', 'Mesero Web')");
    if ($stmtReg) {
        $stmtReg->bind_param("i", $idEmpReg);
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
    unset($_SESSION['mesero_nombre'], $_SESSION['mesero_id'], $_SESSION['mesero_idregistro'], $_SESSION['mesero_secciones'], $_SESSION['mesero_seccion_nombre']);
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
$sectoresMeseroSesion = $_SESSION['mesero_seccion_nombre'] ?? 'Todos los Sectores';

// Obtener todas las secciones del salón con su rango de mesas
$resSecciones = $conn->query("
    SELECT s.*, 
           MIN(m.numero_mesa) AS min_mesa, 
           MAX(m.numero_mesa) AS max_mesa, 
           COUNT(m.idmesa) AS cant_mesas
    FROM seccion_mesa s
    LEFT JOIN mesa m ON s.idseccion = m.fk_id_seccion AND m.activa = 1
    GROUP BY s.idseccion
    ORDER BY s.orden ASC, s.idseccion ASC
");
$listaSecciones = [];
if ($resSecciones) {
    while ($sec = $resSecciones->fetch_assoc()) {
        $listaSecciones[] = $sec;
    }
}

// Obtener todos los meseros oficiales (Rol 4) y sus múltiples sectores asignados
$resMeserosStaff = $conn->query("SELECT * FROM empleado WHERE fk_id_rol = 4 AND activo = 1 ORDER BY idempleado ASC");
$listaMeserosStaff = [];
if ($resMeserosStaff) {
    while ($empM = $resMeserosStaff->fetch_assoc()) {
        $infoSecM = obtenerSeccionesMesero($conn, $empM['idempleado']);
        $empM['sectores_ids'] = $infoSecM['ids'];
        $empM['sectores_nombres'] = $infoSecM['nombres'];
        $empM['sectores_texto'] = $infoSecM['texto'];
        $listaMeserosStaff[] = $empM;
    }
}

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
                MOSTAZA · SALÓN Y SECTORES DE MESEROS
            </h1>
            <span style="font-size:0.8rem;color:#64748B;">
                Salón Sucursal Formosa · Mesero: <strong style="color:#0F172A;"><?= htmlspecialchars($nombreMesero) ?></strong>
                <?php if (!empty($sectoresMeseroSesion)): ?>
                    · Sectores: <strong style="color:var(--admin-rojo);"><?= htmlspecialchars($sectoresMeseroSesion) ?></strong>
                <?php endif; ?>
            </span>
        </div>
    </div>

    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
        <button type="button" onclick="togglePanelSectores()" class="btn-admin btn-admin-sm" style="background:#0F172A;color:#FFFFFF;border-color:#0F172A;">
            <i class="fa-solid fa-layer-group" style="color:#FBB728;"></i> Asignar Sectores a Meseros
        </button>
        <button type="button" onclick="document.getElementById('modalNuevoMesero').classList.add('open')" class="btn-admin btn-admin-white btn-admin-sm">
            <i class="fa-solid fa-user-plus" style="color:var(--admin-rojo);"></i> Nuevo Mesero
        </button>
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
    <?php elseif ($_GET['msg'] === 'sectores_actualizados'): ?>
        <div style="background:#DCFCE7;border:1px solid #86EFAC;color:#166534;padding:12px 18px;border-radius:10px;margin:14px 20px 0;font-weight:800;display:flex;align-items:center;gap:10px;">
            <i class="fa-solid fa-circle-check"></i> ¡Sectores del mesero actualizados con éxito! El mesero ya puede operar todas las mesas de los sectores seleccionados en el Salón y en la App Móvil.
        </div>
    <?php elseif ($_GET['msg'] === 'mesero_creado'): ?>
        <div style="background:#DCFCE7;border:1px solid #86EFAC;color:#166534;padding:12px 18px;border-radius:10px;margin:14px 20px 0;font-weight:800;display:flex;align-items:center;gap:10px;">
            <i class="fa-solid fa-user-check"></i> ¡Nuevo mesero registrado y asignado a sus sectores correctamente!
        </div>
    <?php elseif ($_GET['msg'] === 'error_sin_sector'): ?>
        <div style="background:#FEE2E2;border:1px solid #FCA5A5;color:#991B1B;padding:12px 18px;border-radius:10px;margin:14px 20px 0;font-weight:800;display:flex;align-items:center;gap:10px;">
            <i class="fa-solid fa-triangle-exclamation"></i> Debés seleccionar al menos un sector para asignarle al mesero.
        </div>
    <?php endif; ?>
<?php endif; ?>

<!-- ============================================================ -->
<!-- PANEL ADMINISTRATIVO: ASIGNACIÓN MÚLTIPLE DE SECTORES A MESEROS -->
<!-- ============================================================ -->
<div id="panelAdminSectores" style="margin:18px 20px 6px;background:#FFFFFF;border-radius:16px;border:1.5px solid #CBD5E1;padding:20px;box-shadow:0 4px 15px rgba(0,0,0,0.04);">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:16px;border-bottom:1px solid #E2E8F0;padding-bottom:12px;">
        <div>
            <h2 style="font-size:1.15rem;font-weight:900;color:#0F172A;margin:0;display:flex;align-items:center;gap:8px;">
                <i class="fa-solid fa-users-gear" style="color:var(--admin-rojo);"></i>
                Asignación de Sectores por Mesero (Multi-Sector)
            </h2>
            <p style="font-size:0.82rem;color:#64748B;margin:4px 0 0;">
                Marcá uno o más sectores para cada mesero y presioná <strong>Guardar Sectores</strong>. El mesero tendrá permiso simultáneo en todas las secciones marcadas.
            </p>
        </div>
        <div style="display:flex;gap:8px;align-items:center;">
            <span class="badge badge-info" style="font-size:0.75rem;">
                <i class="fa-solid fa-mobile-screen-button"></i> Sincronizado con App Mesero
            </span>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(360px, 1fr));gap:16px;">
        <?php foreach ($listaMeserosStaff as $empM): 
            $idM = intval($empM['idempleado']);
            $nomCompletoM = trim($empM['nombre'] . ' ' . $empM['apellido']);
            $misIdsSec = $empM['sectores_ids'] ?? [1];
        ?>
        <form method="post" action="mesero.php" style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:14px;padding:16px;display:flex;flex-direction:column;justify-content:space-between;gap:12px;">
            <input type="hidden" name="idempleado" value="<?= $idM ?>">
            
            <div>
                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px;margin-bottom:10px;">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <span style="font-size:1.6rem;background:#FFFFFF;width:44px;height:44px;border-radius:12px;display:inline-flex;align-items:center;justify-content:center;border:1px solid #E2E8F0;">
                            <?= htmlspecialchars($empM['avatar'] ?: '🤵') ?>
                        </span>
                        <div>
                            <strong style="font-size:0.98rem;color:#0F172A;display:block;"><?= htmlspecialchars($nomCompletoM) ?></strong>
                            <span style="font-size:0.78rem;color:#64748B;"><?= htmlspecialchars($empM['email']) ?></span>
                        </div>
                    </div>
                    <span class="badge" style="background:#FEF3C7;color:#92400E;border:1px solid #FDE68A;">
                        <?= count($misIdsSec) ?> <?= count($misIdsSec) === 1 ? 'Sector' : 'Sectores' ?>
                    </span>
                </div>

                <div style="font-size:0.78rem;font-weight:800;color:#475569;margin-bottom:8px;text-transform:uppercase;display:flex;justify-content:space-between;align-items:center;">
                    <span>Sectores Habilitados:</span>
                    <button type="button" onclick="seleccionarTodosSectores(this)" style="background:none;border:none;color:var(--admin-rojo);font-weight:800;font-size:0.74rem;cursor:pointer;text-decoration:underline;">
                        Marcar todos
                    </button>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                    <?php foreach ($listaSecciones as $sec): 
                        $secId = intval($sec['idseccion']);
                        $checked = in_array($secId, $misIdsSec, true);
                        $rangoTxt = ($sec['min_mesa'] && $sec['max_mesa']) ? "Mesas {$sec['min_mesa']}-{$sec['max_mesa']}" : "{$sec['cant_mesas']} mesas";
                    ?>
                    <label style="display:flex;align-items:flex-start;gap:8px;padding:8px 10px;border-radius:10px;background:<?= $checked ? '#FEF2F2' : '#FFFFFF' ?>;border:1.5px solid <?= $checked ? '#A30002' : '#CBD5E1' ?>;cursor:pointer;font-size:0.8rem;transition:0.15s;" onchange="actualizarEstiloCheck(this)">
                        <input type="checkbox" name="sectores[]" value="<?= $secId ?>" <?= $checked ? 'checked' : '' ?> style="margin-top:2px;accent-color:#A30002;width:15px;height:15px;">
                        <div style="line-height:1.25;">
                            <strong style="display:block;color:<?= $checked ? '#A30002' : '#0F172A' ?>;font-size:0.8rem;">
                                <?= htmlspecialchars($sec['nombre']) ?>
                            </strong>
                            <small style="color:#64748B;font-size:0.7rem;"><?= htmlspecialchars($rangoTxt) ?></small>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div style="display:flex;justify-content:space-between;align-items:center;padding-top:10px;border-top:1px solid #E2E8F0;margin-top:4px;">
                <span style="font-size:0.75rem;color:#64748B;max-width:60%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= htmlspecialchars($empM['sectores_texto']) ?>">
                    📍 Actual: <strong style="color:#0F172A;"><?= htmlspecialchars($empM['sectores_texto']) ?></strong>
                </span>
                <button type="submit" name="guardar_sectores_mesero" class="btn-admin btn-admin-red btn-admin-sm" style="padding:7px 14px;">
                    <i class="fa-solid fa-floppy-disk"></i> Guardar Sectores
                </button>
            </div>
        </form>
        <?php endforeach; ?>
    </div>
</div>

<div style="padding:16px 20px 0;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
    <div>
        <h2 style="font-size:1.2rem;font-weight:900;color:var(--admin-dark);margin:0 0 4px;">
            <i class="fa-solid fa-map-location-dot" style="color:var(--admin-rojo);"></i> Mapa de Mesas en Salón
        </h2>
        <p style="font-size:0.85rem;color:#64748B;margin:0;">Hacé clic en una mesa libre para abrir comanda y marchar a cocina, o en una mesa ocupada para ver su estado.</p>
    </div>
    <div style="display:flex;gap:6px;flex-wrap:wrap;">
        <button type="button" class="btn-admin btn-admin-red btn-admin-sm" id="btnFiltroSec_todas" onclick="filtrarMapaPorSeccion('todas')">
            Todas (<?= $resMesas ? $resMesas->num_rows : 0 ?>)
        </button>
        <?php foreach ($listaSecciones as $sec): ?>
            <button type="button" class="btn-admin btn-admin-white btn-admin-sm" id="btnFiltroSec_<?= $sec['idseccion'] ?>" onclick="filtrarMapaPorSeccion(<?= $sec['idseccion'] ?>)">
                <i class="fa-solid <?= htmlspecialchars($sec['icono'] ?: 'fa-chair') ?>"></i> <?= htmlspecialchars($sec['nombre']) ?>
            </button>
        <?php endforeach; ?>
    </div>
</div>

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
    <div class="mesa-card <?= $estadoClase ?>" data-seccion="<?= intval($m['fk_id_seccion'] ?? 1) ?>" onclick="clickMesa(<?= $num ?>, '<?= htmlspecialchars($m['seccion_nombre'] ?? 'Salón Principal') ?>', <?= $m['capacidad'] ?? 4 ?>, <?= $estaOcupada ? 'true' : 'false' ?>, <?= $tieneComanda ? htmlspecialchars(json_encode($comanda)) : 'null' ?>)">
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
    <div class="modal-comanda-box" style="max-width:440px;padding:24px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
            <h3 style="color:var(--admin-rojo);margin:0;font-size:1.25rem;font-weight:900;">🍽️ Identificación de Mesero</h3>
            <button onclick="document.getElementById('modalLoginMesero').classList.remove('open')" style="background:none;border:none;color:#64748B;font-size:1.3rem;cursor:pointer;">✕</button>
        </div>
        <p style="font-size:0.85rem;color:#64748B;margin-bottom:16px;">Seleccioná un mesero oficial con sus sectores asignados o ingresá tu nombre.</p>
        
        <form method="post" action="mesero.php">
            <div class="form-group">
                <label class="form-label">Seleccionar Mesero Oficial:</label>
                <select name="idempleado_mesero" class="form-control" onchange="if(this.selectedIndex>0) document.getElementById('inpNomMeseroLogin').value = this.options[this.selectedIndex].getAttribute('data-nom');">
                    <option value="0">-- Seleccionar de la nómina --</option>
                    <?php foreach ($listaMeserosStaff as $empM): 
                        $nComp = trim($empM['nombre'] . ' ' . $empM['apellido']);
                    ?>
                        <option value="<?= $empM['idempleado'] ?>" data-nom="<?= htmlspecialchars($nComp) ?>">
                            <?= htmlspecialchars($nComp) ?> (<?= htmlspecialchars($empM['sectores_texto']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Nombre del Mesero en Turno:</label>
                <input type="text" name="nombre_mesero" id="inpNomMeseroLogin" required placeholder="Ej: Franco Morales" class="form-control">
            </div>

            <button type="submit" name="login_mesero" class="btn-admin btn-admin-red" style="width:100%;justify-content:center;padding:12px;">
                Entrar al Salón
            </button>
        </form>
    </div>
</div>

<!-- Modal Alta de Nuevo Mesero con Múltiples Sectores -->
<div class="modal-comanda" id="modalNuevoMesero">
    <div class="modal-comanda-box" style="max-width:500px;padding:24px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
            <h3 style="color:var(--admin-rojo);margin:0;font-size:1.25rem;font-weight:900;">
                <i class="fa-solid fa-user-plus"></i> Alta de Nuevo Mesero
            </h3>
            <button onclick="document.getElementById('modalNuevoMesero').classList.remove('open')" style="background:none;border:none;color:#64748B;font-size:1.3rem;cursor:pointer;">✕</button>
        </div>
        <p style="font-size:0.84rem;color:#64748B;margin-bottom:16px;">
            Registrá un nuevo mesero y asignale uno o más sectores de salón desde el inicio.
        </p>

        <form method="post" action="mesero.php">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div class="form-group">
                    <label class="form-label">Nombre *</label>
                    <input type="text" name="nombre" required placeholder="Ej: Matías" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Apellido *</label>
                    <input type="text" name="apellido" required placeholder="Ej: Fernández" class="form-control">
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1.2fr 1fr;gap:12px;">
                <div class="form-group">
                    <label class="form-label">Correo (Login App) *</label>
                    <input type="email" name="email" required placeholder="ej: mesero3@mostaza.com.ar" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Teléfono</label>
                    <input type="text" name="telefono" placeholder="3704-000000" class="form-control">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Sectores Asignados (Podés marcar más de uno) *</label>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:4px;">
                    <?php foreach ($listaSecciones as $idxS => $sec): ?>
                    <label style="display:flex;align-items:center;gap:8px;padding:9px 10px;border-radius:10px;background:#F8FAFC;border:1.5px solid #CBD5E1;cursor:pointer;font-size:0.82rem;">
                        <input type="checkbox" name="sectores[]" value="<?= $sec['idseccion'] ?>" <?= $idxS === 0 ? 'checked' : '' ?> style="accent-color:#A30002;width:16px;height:16px;">
                        <span style="font-weight:700;color:#0F172A;"><?= htmlspecialchars($sec['nombre']) ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div style="display:flex;gap:10px;margin-top:18px;">
                <button type="button" onclick="document.getElementById('modalNuevoMesero').classList.remove('open')" class="btn-admin btn-admin-white" style="flex:1;justify-content:center;">
                    Cancelar
                </button>
                <button type="submit" name="crear_nuevo_mesero" class="btn-admin btn-admin-red" style="flex:1.4;justify-content:center;">
                    <i class="fa-solid fa-check"></i> Crear y Asignar Sectores
                </button>
            </div>
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

function togglePanelSectores() {
    const panel = document.getElementById('panelAdminSectores');
    if (!panel) return;
    if (panel.style.display === 'none') {
        panel.style.display = 'block';
        panel.scrollIntoView({ behavior: 'smooth' });
    } else {
        panel.style.display = 'none';
    }
}

function actualizarEstiloCheck(lbl) {
    const chk = lbl.querySelector('input[type="checkbox"]');
    const title = lbl.querySelector('strong');
    if (!chk) return;
    if (chk.checked) {
        lbl.style.background = '#FEF2F2';
        lbl.style.borderColor = '#A30002';
        if (title) title.style.color = '#A30002';
    } else {
        lbl.style.background = '#FFFFFF';
        lbl.style.borderColor = '#CBD5E1';
        if (title) title.style.color = '#0F172A';
    }
}

function seleccionarTodosSectores(btn) {
    const form = btn.closest('form');
    if (!form) return;
    const checks = form.querySelectorAll('input[name="sectores[]"]');
    const allChecked = Array.from(checks).every(c => c.checked);
    checks.forEach(c => {
        c.checked = !allChecked;
        const lbl = c.closest('label');
        if (lbl) actualizarEstiloCheck(lbl);
    });
    btn.textContent = allChecked ? 'Marcar todos' : 'Desmarcar todos';
}

function filtrarMapaPorSeccion(secId) {
    document.querySelectorAll('[id^="btnFiltroSec_"]').forEach(b => {
        b.classList.remove('btn-admin-red');
        b.classList.add('btn-admin-white');
    });
    const btnAct = document.getElementById('btnFiltroSec_' + secId);
    if (btnAct) {
        btnAct.classList.remove('btn-admin-white');
        btnAct.classList.add('btn-admin-red');
    }

    document.querySelectorAll('.mesa-card').forEach(card => {
        if (secId === 'todas' || String(card.dataset.seccion) === String(secId)) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

setInterval(function() {
    const algunInputFoco = document.activeElement && (document.activeElement.tagName === 'INPUT' || document.activeElement.tagName === 'SELECT');
    if (!document.querySelector('.modal-comanda.open') && !algunInputFoco) {
        window.location.reload();
    }
}, 15000);
</script>

</body>
</html>
