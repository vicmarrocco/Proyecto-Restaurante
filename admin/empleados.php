<?php
// ============================================================
// admin/empleados.php
// Gestión de Empleados: alta de personal con su rol y listado.
// Tablas: empleado, rol, turnos, genero, seccion_mesa, empleado_seccion
// ============================================================
session_start();
require('conexion.php');

const ROL_MESERO = 4;
const ROL_REPARTIDOR = 6;

$mensajeOk = '';
$mensajeErr = '';
$mensajeAviso = '';
$old = [];

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function valorPrevio($k) { global $old; return h($old[$k] ?? ''); }

// Devuelve las áreas donde el empleado ya tiene registros (si no hay ninguna, se puede borrar sin perder historial)
function historialEmpleado($conn, $id) {
    $fuentes = [
        'facturas' => [['factura', 'idempleado']],
        'auditoría de pedidos' => [['pedido_auditoria', 'idempleado']],
        'pedidos' => [['pedido_web', 'idempleado'], ['pedido_web', 'idempleado_cajero'], ['pedido_web', 'idempleado_mesero'], ['pedido_web', 'idempleado_cocinero'], ['pedido_web', 'idempleado_repartidor']],
        'arqueos de caja' => [['caja_arqueo', 'idempleado']],
        'retiros de caja' => [['caja_retiro', 'idempleado']],
        'registros de turno' => [['registro_turno_empleado', 'fk_id_empleado']],
        'solicitudes de ingredientes' => [['solicitud_ingrediente', 'fk_id_empleado']],
        'tickets' => [['ticket', 'fk_id_empleado']],
    ];
    $encontrados = [];
    foreach ($fuentes as $etiqueta => $pares) {
        foreach ($pares as [$tabla, $col]) {
            try {
                $st = $conn->prepare("SELECT 1 FROM `$tabla` WHERE `$col` = ? LIMIT 1");
                if (!$st) continue;
                $st->bind_param("i", $id);
                $st->execute();
                if ($st->get_result()->num_rows > 0) { $encontrados[] = $etiqueta; break; }
            } catch (Throwable $ex) {
                continue; // tabla o columna inexistente en esta instalación
            }
        }
    }
    return $encontrados;
}

// ---------- Catálogos ----------
// Roles asignables: se excluyen Cliente (1) y Administrador (5, vive en la tabla `admin`)
$roles = [];
$res = $conn->query("SELECT idrol, rol FROM rol WHERE idrol NOT IN (1, 5) ORDER BY idrol");
if ($res) { while ($r = $res->fetch_assoc()) { $roles[intval($r['idrol'])] = $r['rol']; } }

$turnos = [];
$res = $conn->query("SELECT idturnos, nombre, horaInicio, horaFin, dias FROM turnos ORDER BY idturnos");
if ($res) { while ($r = $res->fetch_assoc()) { $turnos[intval($r['idturnos'])] = $r; } }

$generos = [];
$res = $conn->query("SELECT idgenero, genero FROM genero ORDER BY idgenero");
if ($res) { while ($r = $res->fetch_assoc()) { $generos[intval($r['idgenero'])] = $r['genero']; } }

$secciones = [];
$res = $conn->query("SELECT idseccion, nombre, icono FROM seccion_mesa ORDER BY orden, idseccion");
if ($res) { while ($r = $res->fetch_assoc()) { $secciones[intval($r['idseccion'])] = $r; } }

// Iconos y descripciones por rol (solo presentación)
$iconosRol = [
    'cajero' => ['fa-cash-register', 'Punto de venta y caja'],
    'cocinero' => ['fa-fire-burner', 'Cocina (KDS)'],
    'mesero' => ['fa-utensils', 'Salón y mesas'],
    'repartidor' => ['fa-motorcycle', 'Delivery'],
];
$badgesRol = [2 => 'badge-info', 3 => 'badge-warning', 4 => 'badge-success', 6 => 'badge-muted'];

// ---------- Eliminar empleado ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_empleado'])) {
    $idDel = intval($_POST['idempleado'] ?? 0);
    $stmtE = $conn->prepare("SELECT nombre, apellido, activo FROM empleado WHERE idempleado = ?");
    $stmtE->bind_param("i", $idDel);
    $stmtE->execute();
    $emp = $stmtE->get_result()->fetch_assoc();

    if (!$emp) {
        $flash = ['error', 'El empleado ya no existe.'];
    } else {
        $nomEmp = $emp['nombre'] . ' ' . $emp['apellido'];
        $yaInactivo = (intval($emp['activo']) === 0);
        $hist = historialEmpleado($conn, $idDel);
        $borrado = false;

        if (empty($hist)) {
            try {
                $conn->begin_transaction();
                $st = $conn->prepare("DELETE FROM empleado_seccion WHERE idempleado = ?");
                $st->bind_param("i", $idDel);
                $st->execute();
                $st = $conn->prepare("DELETE FROM empleado WHERE idempleado = ?");
                $st->bind_param("i", $idDel);
                $st->execute();
                $conn->commit();
                $borrado = true;
            } catch (Throwable $ex) {
                $conn->rollback(); // p. ej. una restricción de la base impidió el borrado
            }
        }

        if ($borrado) {
            $flash = ['ok', $nomEmp . ' fue eliminado/a del sistema.'];
        } else {
            $st = $conn->prepare("UPDATE empleado SET activo = 0 WHERE idempleado = ?");
            $st->bind_param("i", $idDel);
            $st->execute();
            $motivo = !empty($hist)
                ? 'porque tiene registros asociados (' . implode(', ', $hist) . ')'
                : 'porque la base de datos no permitió borrarlo';
            if ($yaInactivo) {
                $flash = ['aviso', $nomEmp . ' ya estaba dado/a de baja y no se puede borrar del todo ' . $motivo . '. Sigue inactivo/a.'];
            } else {
                $flash = ['aviso', $nomEmp . ' no se pudo borrar ' . $motivo . ', así que quedó inactivo/a. Se conserva su historial.'];
            }
        }
    }
    $_SESSION['flash_empleado'] = $flash;
    header("Location: empleados.php");
    exit();
}

// ---------- Reactivar empleado dado de baja ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reactivar_empleado'])) {
    $idRea = intval($_POST['idempleado'] ?? 0);
    $st = $conn->prepare("UPDATE empleado SET activo = 1 WHERE idempleado = ?");
    $st->bind_param("i", $idRea);
    $st->execute();
    $_SESSION['flash_empleado'] = ['ok', 'El empleado fue reactivado.'];
    header("Location: empleados.php");
    exit();
}

// ---------- Alta de empleado ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_empleado'])) {
    $old = $_POST;
    $errores = [];

    $nombre   = preg_replace('/\s+/u', ' ', trim($_POST['nombre'] ?? ''));
    $apellido = preg_replace('/\s+/u', ' ', trim($_POST['apellido'] ?? ''));
    $dni      = preg_replace('/\D/', '', $_POST['dni'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $idRol    = intval($_POST['rol'] ?? 0);
    $idTurno  = intval($_POST['turno'] ?? 0);
    $idGenero = intval($_POST['genero'] ?? 0);
    $vehiculo = trim($_POST['vehiculo'] ?? '');
    $patente  = strtoupper(trim($_POST['patente'] ?? ''));
    $sectoresSel = (isset($_POST['sectores']) && is_array($_POST['sectores']))
        ? array_values(array_unique(array_filter(array_map('intval', $_POST['sectores']))))
        : [];

    // Rol
    if (!isset($roles[$idRol])) {
        $errores[] = 'Elegí el rol del empleado.';
    }

    // Datos personales
    $patronNombre = '/^[\p{L}\s\'\.\-]+$/u';
    if ($nombre === '' || mb_strlen($nombre) > 100 || !preg_match($patronNombre, $nombre)) {
        $errores[] = 'Ingresá un nombre válido (solo letras).';
    }
    if ($apellido === '' || mb_strlen($apellido) > 100 || !preg_match($patronNombre, $apellido)) {
        $errores[] = 'Ingresá un apellido válido (solo letras).';
    }
    if (!preg_match('/^\d{7,8}$/', $dni)) {
        $errores[] = 'El DNI debe tener 7 u 8 dígitos, sin puntos.';
    }
    if ($telefono !== '' && !preg_match('/^[0-9+\-\s()]{6,20}$/', $telefono)) {
        $errores[] = 'El teléfono tiene un formato inválido (ej: 3704-112233).';
    }
    if ($idRol === ROL_REPARTIDOR && $telefono === '') {
        $errores[] = 'El teléfono es obligatorio para repartidores.';
    }
    if ($email === '' || mb_strlen($email) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'Ingresá un email válido.';
    }

    // Turno y género
    if (!isset($turnos[$idTurno])) {
        $errores[] = 'Elegí el turno de trabajo.';
    }
    if ($idGenero > 0 && !isset($generos[$idGenero])) {
        $errores[] = 'El género seleccionado no es válido.';
    }

    // Campos según rol
    if ($idRol === ROL_MESERO) {
        $sectoresSel = array_values(array_filter($sectoresSel, function ($s) use ($secciones) { return isset($secciones[$s]); }));
        if (empty($sectoresSel)) {
            $errores[] = 'Asigná al menos un sector del salón al mesero.';
        }
    }
    if ($idRol === ROL_REPARTIDOR) {
        if ($vehiculo === '' || mb_strlen($vehiculo) > 100) {
            $errores[] = 'Indicá el vehículo del repartidor.';
        }
        if (mb_strlen($patente) > 20) {
            $errores[] = 'La patente es demasiado larga.';
        }
    }

    // Duplicados (email de acceso único; DNI único entre empleados activos)
    if (empty($errores)) {
        $stmtDup = $conn->prepare("SELECT nombre, apellido FROM empleado WHERE LOWER(email) = LOWER(?) LIMIT 1");
        $stmtDup->bind_param("s", $email);
        $stmtDup->execute();
        if ($dup = $stmtDup->get_result()->fetch_assoc()) {
            $errores[] = 'Ese email ya está registrado para ' . $dup['nombre'] . ' ' . $dup['apellido'] . '.';
        }
        $stmtDup = $conn->prepare("SELECT nombre, apellido FROM empleado WHERE dni = ? AND activo = 1 LIMIT 1");
        $stmtDup->bind_param("s", $dni);
        $stmtDup->execute();
        if ($dup = $stmtDup->get_result()->fetch_assoc()) {
            $errores[] = 'Ya existe un empleado activo con ese DNI (' . $dup['nombre'] . ' ' . $dup['apellido'] . ').';
        }
    }

    if (empty($errores)) {
        // Avatar según rol (y género en cajero/cocina)
        $esFem = ($idGenero === 2);
        $avatar = '👤';
        if ($idRol === 2) $avatar = $esFem ? '👩‍💼' : '👨‍💼';
        elseif ($idRol === 3) $avatar = $esFem ? '👩‍🍳' : '👨‍🍳';
        elseif ($idRol === ROL_MESERO) $avatar = '🤵';
        elseif ($idRol === ROL_REPARTIDOR) $avatar = '🛵';

        $cols  = ['nombre', 'apellido', 'dni', 'email', 'telefono', 'fk_id_rol', 'fk_id_genero', 'fk_id_turno', 'activo', 'avatar'];
        $vals  = [$nombre, $apellido, $dni, $email, ($telefono !== '' ? $telefono : null), $idRol, ($idGenero > 0 ? $idGenero : null), $idTurno, 1, $avatar];
        $types = 'sssssiiiis';

        if ($idRol === ROL_MESERO) {
            $cols[] = 'fk_id_seccion'; $vals[] = intval($sectoresSel[0]); $types .= 'i';
        }
        if ($idRol === ROL_REPARTIDOR) {
            $cols[] = 'vehiculo'; $vals[] = $vehiculo; $types .= 's';
            $cols[] = 'patente';  $vals[] = ($patente !== '' ? $patente : null); $types .= 's';
        }

        try {
            $conn->begin_transaction();

            $sql = "INSERT INTO empleado (`" . implode('`, `', $cols) . "`) VALUES (" . implode(', ', array_fill(0, count($cols), '?')) . ")";
            $stmtIns = $conn->prepare($sql);
            $stmtIns->bind_param($types, ...$vals);
            if (!$stmtIns->execute()) {
                throw new Exception($stmtIns->error);
            }
            $nuevoId = $stmtIns->insert_id;

            if ($idRol === ROL_MESERO) {
                $stmtSec = $conn->prepare("INSERT IGNORE INTO empleado_seccion (idempleado, idseccion) VALUES (?, ?)");
                foreach ($sectoresSel as $sid) {
                    $stmtSec->bind_param("ii", $nuevoId, $sid);
                    $stmtSec->execute();
                }
            }

            $conn->commit();
            header("Location: empleados.php?creado=" . intval($nuevoId));
            exit();
        } catch (Throwable $e) {
            $conn->rollback();
            $mensajeErr = 'No se pudo registrar al empleado: ' . h($e->getMessage());
        }
    } else {
        $mensajeErr = implode('<br>', array_map('h', $errores));
    }
}

// Mensajes de eliminar / reactivar (guardados en sesión antes de redirigir)
if (!empty($_SESSION['flash_empleado'])) {
    [$tipoFlash, $textoFlash] = $_SESSION['flash_empleado'];
    unset($_SESSION['flash_empleado']);
    if ($tipoFlash === 'ok') { $mensajeOk = h($textoFlash); }
    elseif ($tipoFlash === 'aviso') { $mensajeAviso = h($textoFlash); }
    else { $mensajeErr = h($textoFlash); }
}

// Mensaje de éxito tras el alta (patrón post/redirect/get)
if (isset($_GET['creado'])) {
    $idC = intval($_GET['creado']);
    $stmtC = $conn->prepare("SELECT e.nombre, e.apellido, r.rol FROM empleado e LEFT JOIN rol r ON r.idrol = e.fk_id_rol WHERE e.idempleado = ?");
    $stmtC->bind_param("i", $idC);
    $stmtC->execute();
    if ($c = $stmtC->get_result()->fetch_assoc()) {
        $mensajeOk = '¡' . h($c['nombre'] . ' ' . $c['apellido']) . ' fue registrado/a como ' . h($c['rol']) . '!';
    }
}

// ---------- Listado ----------
$empleados = [];
$resEmp = $conn->query("
    SELECT e.idempleado, e.nombre, e.apellido, e.dni, e.email, e.telefono, e.activo, e.avatar,
           e.fk_id_rol, e.vehiculo, e.patente, r.rol AS rol_nombre, t.nombre AS turno_nombre,
           (SELECT GROUP_CONCAT(s.nombre ORDER BY s.orden SEPARATOR ', ')
              FROM empleado_seccion es JOIN seccion_mesa s ON s.idseccion = es.idseccion
             WHERE es.idempleado = e.idempleado) AS sectores
    FROM empleado e
    LEFT JOIN rol r ON r.idrol = e.fk_id_rol
    LEFT JOIN turnos t ON t.idturnos = e.fk_id_turno
    ORDER BY e.idempleado DESC
");
$conteoPorRol = [];
if ($resEmp) {
    while ($e = $resEmp->fetch_assoc()) {
        $empleados[] = $e;
        $rid = intval($e['fk_id_rol']);
        $conteoPorRol[$rid] = ($conteoPorRol[$rid] ?? 0) + 1;
    }
}
$rolPrevio = intval($old['rol'] ?? 0);
$sectoresPrevios = (isset($old['sectores']) && is_array($old['sectores'])) ? array_map('intval', $old['sectores']) : [1];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Empleados · Mostaza Central</title>
<link rel="stylesheet" href="admin.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
.paso-num {
    display: inline-flex; align-items: center; justify-content: center;
    width: 28px; height: 28px; border-radius: 50%;
    background: var(--admin-rojo); color: #fff;
    font-size: 0.85rem; font-weight: 900; margin-right: 8px;
}
.paso-titulo { font-size: 1.05rem; font-weight: 800; margin: 0 0 12px; color: var(--admin-dark); }
.form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; }
.form-grid .form-group { margin-bottom: 0; }
.req { color: var(--admin-rojo); }

.rol-opciones { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 12px; margin-bottom: 26px; }
.rol-card {
    display: flex; align-items: center; gap: 12px; cursor: pointer;
    background: #FFFFFF; border: 2px solid #CBD5E1; border-radius: 12px;
    padding: 14px 16px; transition: all 0.2s;
}
.rol-card input { accent-color: var(--admin-rojo); width: 18px; height: 18px; flex-shrink: 0; }
.rol-card i { font-size: 1.4rem; color: var(--admin-rojo); width: 28px; text-align: center; }
.rol-card strong { display: block; font-size: 0.95rem; font-weight: 900; }
.rol-card small { display: block; font-size: 0.75rem; color: #64748B; font-weight: 600; }
.rol-card:hover { border-color: var(--admin-rojo); }
.rol-card:has(input:checked) { border-color: var(--admin-rojo); background: #FEF2F2; box-shadow: 0 0 0 3px rgba(163, 0, 2, 0.12); }

.panel-extra {
    display: none; background: #F8FAFC; border: 1px solid #E2E8F0;
    border-radius: 12px; padding: 18px; margin-top: 20px;
}
.panel-extra h4 { font-size: 0.95rem; font-weight: 800; margin-bottom: 12px; color: var(--admin-dark); }
.sectores-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 10px; }
.sector-check {
    display: flex; align-items: center; gap: 10px; cursor: pointer;
    background: #fff; border: 1px solid #CBD5E1; border-radius: 10px;
    padding: 10px 12px; font-weight: 700; font-size: 0.88rem;
}
.sector-check input { accent-color: var(--admin-rojo); width: 16px; height: 16px; }
.sector-check:has(input:checked) { border-color: var(--admin-rojo); background: #FEF2F2; }

.filtro-tabs { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 16px; }
.filtro-tab {
    padding: 8px 16px; border-radius: 8px; background: #FFFFFF;
    border: 1px solid #CBD5E1; font-weight: 700; font-size: 0.85rem;
    cursor: pointer; transition: all 0.2s; color: #475569; font-family: inherit;
}
.filtro-tab.active, .filtro-tab:hover { background: var(--admin-rojo); color: #fff; border-color: var(--admin-rojo); }
.emp-avatar { font-size: 1.6rem; margin-right: 10px; }
.emp-cell { display: flex; align-items: center; }
</style>
</head>
<body class="admin-body">

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
        <li><a href="productos.php"><i class="fa-solid fa-burger"></i> Catálogo & Productos</a></li>
        <li><a href="pos.php"><i class="fa-solid fa-cash-register"></i> Punto de Venta (POS)</a></li>
        <li><a href="mesero.php"><i class="fa-solid fa-utensils"></i> Salón (Mesero)</a></li>
        <li><a href="cocina.php"><i class="fa-solid fa-fire-burner"></i> Cocina (KDS)</a></li>
        <li><a href="repartidor.php"><i class="fa-solid fa-motorcycle"></i> Repartidor</a></li>
        <li><a href="caja.php"><i class="fa-solid fa-vault"></i> Control de Caja</a></li>
        <li><a href="inventario.php"><i class="fa-solid fa-boxes-stacked"></i> Inventario & Stock</a></li>
        <li><a href="reportes.php"><i class="fa-solid fa-file-invoice-dollar"></i> Reportes</a></li>
        <li><a href="empleados.php" class="active"><i class="fa-solid fa-users"></i> Empleados</a></li>
        <li style="margin-top:20px;"><a href="../mostaza_web/" target="_blank"><i class="fa-solid fa-store"></i> Ir a la Tienda Web</a></li>
    </ul>

    <div class="sidebar-footer">
        Sistema Mostaza v2.5<br>EPET Nº 7 "Vicente Arcadio Salemi"
    </div>
</aside>

<main class="admin-main">

    <div class="admin-topbar">
        <div>
            <h1>Empleados</h1>
            <p>Dá de alta al personal nuevo eligiendo su rol y consultá el equipo actual.</p>
        </div>
    </div>

    <?php if ($mensajeOk): ?><div style="background:#DCFCE7;color:#166534;padding:14px;border-radius:10px;margin-bottom:20px;font-weight:700;border:1px solid #BBF7D0;"><?= $mensajeOk ?></div><?php endif; ?>
    <?php if ($mensajeAviso): ?><div style="background:#FEF3C7;color:#92400E;padding:14px;border-radius:10px;margin-bottom:20px;font-weight:700;border:1px solid #FDE68A;"><?= $mensajeAviso ?></div><?php endif; ?>
    <?php if ($mensajeErr): ?><div style="background:#FEE2E2;color:#991B1B;padding:14px;border-radius:10px;margin-bottom:20px;font-weight:700;border:1px solid #FECACA;"><?= $mensajeErr ?></div><?php endif; ?>

    <!-- Alta de empleado -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2><i class="fa-solid fa-user-plus" style="color:var(--admin-rojo);"></i> Nuevo empleado</h2>
        </div>

        <form method="post" action="empleados.php" id="form-empleado" autocomplete="off">
            <input type="hidden" name="crear_empleado" value="1">

            <!-- Paso 1: Rol -->
            <h3 class="paso-titulo"><span class="paso-num">1</span> Rol del empleado</h3>
            <div class="rol-opciones">
                <?php foreach ($roles as $idr => $nomRol): ?>
                    <?php
                    $clave = mb_strtolower($nomRol);
                    $ico = $iconosRol[$clave][0] ?? 'fa-user';
                    $desc = $iconosRol[$clave][1] ?? '';
                    ?>
                    <label class="rol-card">
                        <input type="radio" name="rol" value="<?= $idr ?>" required <?= $rolPrevio === $idr ? 'checked' : '' ?> onchange="actualizarRolEmpleado()">
                        <i class="fa-solid <?= h($ico) ?>"></i>
                        <span><strong><?= h($nomRol) ?></strong><?php if ($desc): ?><small><?= h($desc) ?></small><?php endif; ?></span>
                    </label>
                <?php endforeach; ?>
            </div>

            <!-- Paso 2: Datos -->
            <h3 class="paso-titulo"><span class="paso-num">2</span> Datos personales</h3>
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Nombre <span class="req">*</span></label>
                    <input type="text" name="nombre" class="form-control" maxlength="100" required value="<?= valorPrevio('nombre') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Apellido <span class="req">*</span></label>
                    <input type="text" name="apellido" class="form-control" maxlength="100" required value="<?= valorPrevio('apellido') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">DNI <span class="req">*</span></label>
                    <input type="text" name="dni" class="form-control" inputmode="numeric" maxlength="10" placeholder="Sin puntos" required value="<?= valorPrevio('dni') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Teléfono <span class="req" id="lblTelReq" style="display:none;">*</span></label>
                    <input type="text" name="telefono" id="inputTelefono" class="form-control" maxlength="20" placeholder="Ej: 3704-112233" value="<?= valorPrevio('telefono') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Email <span class="req">*</span></label>
                    <input type="email" name="email" class="form-control" maxlength="150" required value="<?= valorPrevio('email') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Género</label>
                    <select name="genero" class="form-control">
                        <option value="">Sin especificar</option>
                        <?php foreach ($generos as $idg => $nomG): ?>
                            <option value="<?= $idg ?>" <?= intval($old['genero'] ?? 0) === $idg ? 'selected' : '' ?>><?= h($nomG) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Turno de trabajo <span class="req">*</span></label>
                    <select name="turno" class="form-control" required>
                        <option value="">Elegí un turno…</option>
                        <?php foreach ($turnos as $idt => $t): ?>
                            <option value="<?= $idt ?>" <?= intval($old['turno'] ?? 0) === $idt ? 'selected' : '' ?>>
                                <?= h($t['nombre']) ?> · <?= h(substr($t['horaInicio'], 0, 5)) ?>–<?= h(substr($t['horaFin'], 0, 5)) ?> · <?= h($t['dias']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Extra: Mesero -->
            <div class="panel-extra" id="extraMesero">
                <h4><i class="fa-solid fa-chair"></i> Sectores del salón que atiende</h4>
                <div class="sectores-grid">
                    <?php foreach ($secciones as $ids => $sec): ?>
                        <label class="sector-check">
                            <input type="checkbox" name="sectores[]" value="<?= $ids ?>" <?= in_array($ids, $sectoresPrevios, true) ? 'checked' : '' ?>>
                            <i class="fa-solid <?= h($sec['icono'] ?: 'fa-chair') ?>"></i> <?= h($sec['nombre']) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Extra: Repartidor -->
            <div class="panel-extra" id="extraRepartidor">
                <h4><i class="fa-solid fa-motorcycle"></i> Datos del vehículo</h4>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Vehículo <span class="req">*</span></label>
                        <input type="text" name="vehiculo" id="inputVehiculo" class="form-control" maxlength="100" placeholder="Ej: Moto Honda Wave 110cc" value="<?= valorPrevio('vehiculo') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Patente</label>
                        <input type="text" name="patente" class="form-control" maxlength="20" placeholder="Ej: A123BCD" style="text-transform:uppercase;" value="<?= valorPrevio('patente') ?>">
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-admin btn-admin-red" style="width:100%;padding:14px;font-size:1.02rem;justify-content:center;margin-top:24px;">
                <i class="fa-solid fa-user-plus"></i> Registrar empleado
            </button>
        </form>
    </div>

    <!-- Listado -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2><i class="fa-solid fa-users" style="color:var(--admin-rojo);"></i> Equipo actual</h2>
            <span class="badge badge-info"><?= count($empleados) ?> empleados</span>
        </div>

        <div class="filtro-tabs">
            <button type="button" class="filtro-tab active" onclick="filtrarEmpleados('todos', this)">Todos (<?= count($empleados) ?>)</button>
            <?php foreach ($roles as $idr => $nomRol): ?>
                <button type="button" class="filtro-tab" onclick="filtrarEmpleados('<?= $idr ?>', this)"><?= h($nomRol) ?> (<?= $conteoPorRol[$idr] ?? 0 ?>)</button>
            <?php endforeach; ?>
        </div>

        <div class="admin-table-wrapper">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Empleado</th>
                        <th>Rol</th>
                        <th>DNI</th>
                        <th>Contacto</th>
                        <th>Turno</th>
                        <th>Detalle</th>
                        <th>Estado</th>
                        <th style="text-align:right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($empleados)): ?>
                        <tr><td colspan="8" style="text-align:center;color:#64748B;padding:24px;">Todavía no hay empleados registrados.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($empleados as $e): ?>
                        <?php
                        $rid = intval($e['fk_id_rol']);
                        if ($rid === ROL_MESERO) {
                            $detalle = $e['sectores'] ?: '—';
                        } elseif ($rid === ROL_REPARTIDOR) {
                            $detalle = trim(($e['vehiculo'] ?? '') . (!empty($e['patente']) ? ' (' . $e['patente'] . ')' : '')) ?: '—';
                        } else {
                            $detalle = '—';
                        }
                        ?>
                        <tr data-rol="<?= $rid ?>">
                            <td>
                                <div class="emp-cell">
                                    <span class="emp-avatar"><?= h($e['avatar'] ?: '👤') ?></span>
                                    <strong><?= h($e['nombre'] . ' ' . $e['apellido']) ?></strong>
                                </div>
                            </td>
                            <td><span class="badge <?= $badgesRol[$rid] ?? 'badge-muted' ?>"><?= h($e['rol_nombre'] ?? 'Sin rol') ?></span></td>
                            <td><?= h($e['dni'] ?: '—') ?></td>
                            <td style="font-size:0.82rem;">
                                <?= h($e['email']) ?><br>
                                <span style="color:#64748B;"><?= h($e['telefono'] ?: '—') ?></span>
                            </td>
                            <td style="font-size:0.82rem;"><?= h($e['turno_nombre'] ?: '—') ?></td>
                            <td style="font-size:0.82rem;color:#475569;"><?= h($detalle) ?></td>
                            <td>
                                <?php if (intval($e['activo']) === 1): ?>
                                    <span class="badge badge-success">Activo</span>
                                <?php else: ?>
                                    <span class="badge badge-muted">Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td style="white-space:nowrap;text-align:right;">
                                <?php $nomEmp = $e['nombre'] . ' ' . $e['apellido']; ?>
                                <?php if (intval($e['activo']) !== 1): ?>
                                    <form method="post" action="empleados.php" style="display:inline;" onsubmit="return confirm(<?= h(json_encode('¿Reactivar a ' . $nomEmp . '?', JSON_UNESCAPED_UNICODE)) ?>);">
                                        <input type="hidden" name="reactivar_empleado" value="1">
                                        <input type="hidden" name="idempleado" value="<?= intval($e['idempleado']) ?>">
                                        <button type="submit" class="btn-admin btn-admin-white btn-admin-sm" title="Reactivar empleado">
                                            <i class="fa-solid fa-rotate-left" style="color:#16A34A;"></i> Reactivar
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <form method="post" action="empleados.php" style="display:inline;" onsubmit="return confirm(<?= h(json_encode('¿Eliminar a ' . $nomEmp . '? Si ya tiene historial (facturas, pedidos, turnos) no se borra: queda dado/a de baja para no perder esos registros.', JSON_UNESCAPED_UNICODE)) ?>);">
                                    <input type="hidden" name="eliminar_empleado" value="1">
                                    <input type="hidden" name="idempleado" value="<?= intval($e['idempleado']) ?>">
                                    <button type="submit" class="btn-admin btn-admin-white btn-admin-sm" title="Eliminar empleado">
                                        <i class="fa-solid fa-trash" style="color:#DC2626;"></i> Eliminar
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</main>

<script>
const ROL_MESERO = <?= ROL_MESERO ?>;
const ROL_REPARTIDOR = <?= ROL_REPARTIDOR ?>;

function actualizarRolEmpleado() {
    const sel = document.querySelector('input[name="rol"]:checked');
    const rol = sel ? parseInt(sel.value, 10) : 0;

    document.getElementById('extraMesero').style.display = (rol === ROL_MESERO) ? 'block' : 'none';
    document.getElementById('extraRepartidor').style.display = (rol === ROL_REPARTIDOR) ? 'block' : 'none';

    const esRep = (rol === ROL_REPARTIDOR);
    document.getElementById('inputVehiculo').required = esRep;
    document.getElementById('inputTelefono').required = esRep;
    document.getElementById('lblTelReq').style.display = esRep ? 'inline' : 'none';
}

document.getElementById('form-empleado').addEventListener('submit', function (ev) {
    const sel = document.querySelector('input[name="rol"]:checked');
    if (sel && parseInt(sel.value, 10) === ROL_MESERO) {
        if (document.querySelectorAll('input[name="sectores[]"]:checked').length === 0) {
            ev.preventDefault();
            alert('Asigná al menos un sector del salón al mesero.');
        }
    }
});

function filtrarEmpleados(rolId, btn) {
    document.querySelectorAll('.filtro-tab').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('tbody tr[data-rol]').forEach(tr => {
        tr.style.display = (rolId === 'todos' || tr.dataset.rol === String(rolId)) ? '' : 'none';
    });
}

actualizarRolEmpleado();
</script>

</body>
</html>
