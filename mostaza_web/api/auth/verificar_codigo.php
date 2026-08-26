<?php
// ============================================================
// api/auth/verificar_codigo.php
// Valida el código de 6 dígitos e inicia la sesión del cajero,
// registrando automáticamente la hora de entrada y el turno asignado.
// ============================================================
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once(__DIR__ . '/../../conexion.php');

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$email = htmlspecialchars(trim($input['email'] ?? ''));
$codigo = htmlspecialchars(trim($input['codigo'] ?? ''));

if (empty($email) || empty($codigo)) {
    echo json_encode(['ok' => false, 'error' => 'Correo y código de 6 dígitos son obligatorios.']);
    exit();
}

$stmt = $conn->prepare("SELECT idempleado, nombre, apellido, email, token, token_expira, token_intentos, fk_id_rol, fk_id_turno FROM empleado WHERE email = ? AND activo = 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo json_encode(['ok' => false, 'error' => 'No encontramos tu cuenta de empleado.']);
    exit();
}

$emp = $res->fetch_assoc();

if ($emp['token_intentos'] >= 5) {
    echo json_encode(['ok' => false, 'error' => 'Superaste el límite de intentos. Solicitá un nuevo código.']);
    exit();
}

if (empty($emp['token']) || strtotime($emp['token_expira']) < time()) {
    echo json_encode(['ok' => false, 'error' => 'El código venció o ya fue utilizado. Pedí uno nuevo.']);
    exit();
}

if ($codigo !== $emp['token']) {
    $conn->query("UPDATE empleado SET token_intentos = token_intentos + 1 WHERE idempleado = " . (int)$emp['idempleado']);
    echo json_encode(['ok' => false, 'error' => 'Código de acceso incorrecto.']);
    exit();
}

// Token válido: limpiar token
$conn->query("UPDATE empleado SET token = NULL, token_expira = NULL, token_intentos = 0 WHERE idempleado = " . (int)$emp['idempleado']);

// Determinar el turno asignado
$idturno = (int)($emp['fk_id_turno'] ?? 1);
$nombreTurno = 'Turno Mañana';

$resT = $conn->query("SELECT idturnos, nombre, horaInicio, horaFin FROM turnos WHERE idturnos = $idturno");
if ($resT && $rowT = $resT->fetch_assoc()) {
    $nombreTurno = $rowT['nombre'];
} else {
    // Si no está configurado, asignar turno por hora actual
    $horaActual = date('H:i:s');
    $resT2 = $conn->query("SELECT idturnos, nombre FROM turnos WHERE '$horaActual' BETWEEN horaInicio AND horaFin LIMIT 1");
    if ($resT2 && $rowT2 = $resT2->fetch_assoc()) {
        $idturno = (int)$rowT2['idturnos'];
        $nombreTurno = $rowT2['nombre'];
    }
}

// Cerrar sesiones activas previas que hayan quedado abiertas
$stmtClose = $conn->prepare("
    UPDATE registro_turno_empleado 
    SET estado = 'Finalizado', 
        hora_salida = NOW(), 
        horas_trabajadas = ROUND(TIMESTAMPDIFF(SECOND, hora_entrada, NOW()) / 3600, 2),
        observaciones = IF(observaciones IS NULL, 'Cierre automático por nuevo inicio', CONCAT(observaciones, ' | Cierre automático'))
    WHERE fk_id_empleado = ? AND estado = 'Activo'
");
$stmtClose->bind_param("i", $emp['idempleado']);
$stmtClose->execute();

// Registrar Entrada en la tabla de turnos de empleados
$stmtReg = $conn->prepare("
    INSERT INTO registro_turno_empleado (fk_id_empleado, fk_id_turno, fecha, hora_entrada, estado, origen)
    VALUES (?, ?, CURDATE(), NOW(), 'Activo', 'App Cajero')
");
$stmtReg->bind_param("ii", $emp['idempleado'], $idturno);
$stmtReg->execute();
$idregistro_turno = $stmtReg->insert_id;
$horaEntrada = date('H:i:s');

$_SESSION['cajero_id'] = $emp['idempleado'];
$_SESSION['cajero_nombre'] = $emp['nombre'] . ' ' . $emp['apellido'];
$_SESSION['cajero_email'] = $emp['email'];
$_SESSION['cajero_rol'] = $emp['fk_id_rol'];
$_SESSION['idregistro_turno'] = $idregistro_turno;

echo json_encode([
    'ok' => true,
    'mensaje' => '¡Identificación exitosa! Bienvenido a la Terminal de Cajero Mostaza.',
    'cajero' => [
        'id' => (int)$emp['idempleado'],
        'nombre' => $emp['nombre'],
        'apellido' => $emp['apellido'],
        'nombre_completo' => $emp['nombre'] . ' ' . $emp['apellido'],
        'email' => $emp['email'],
        'rol' => ($emp['fk_id_rol'] == 5) ? 'Administrador' : 'Cajero',
        'idregistro_turno' => $idregistro_turno,
        'idturno' => $idturno,
        'turno' => $nombreTurno,
        'hora_entrada' => $horaEntrada
    ]
]);
