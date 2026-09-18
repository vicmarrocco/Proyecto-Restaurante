<?php
// ============================================================
// api/auth/verificar_codigo.php
// Valida el código de 6 dígitos e inicia la sesión del empleado
// según la aplicación solicitada (Cajero, Cocina, Mesero, Delivery).
// ============================================================
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once(__DIR__ . '/../../conexion.php');

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$email = htmlspecialchars(trim($input['email'] ?? ''));
$codigo = htmlspecialchars(trim($input['codigo'] ?? ''));
$app = strtolower(trim($input['app'] ?? 'cajero'));

if (empty($email) || empty($codigo)) {
    echo json_encode(['ok' => false, 'error' => 'Correo y código de 6 dígitos son obligatorios.']);
    exit();
}

// Mapeo de roles permitidos por aplicación
$rolesPorApp = [
    'cajero'   => ['nombre' => 'Cajero / POS', 'roles' => [2, 5]],
    'pos'      => ['nombre' => 'Cajero / POS', 'roles' => [2, 5]],
    'cocina'   => ['nombre' => 'Cocina (KDS)', 'roles' => [3, 5]],
    'kds'      => ['nombre' => 'Cocina (KDS)', 'roles' => [3, 5]],
    'mesero'   => ['nombre' => 'Salón (Mesero)', 'roles' => [4, 5]],
    'salon'    => ['nombre' => 'Salón (Mesero)', 'roles' => [4, 5]],
    'delivery' => ['nombre' => 'Repartidor Delivery', 'roles' => [6, 5]],
    'rider'    => ['nombre' => 'Repartidor Delivery', 'roles' => [6, 5]],
    'admin'    => ['nombre' => 'Administración', 'roles' => [5]]
];

$configApp = $rolesPorApp[$app] ?? $rolesPorApp['cajero'];

$stmt = $conn->prepare("
    SELECT e.*, r.rol 
    FROM empleado e 
    LEFT JOIN rol r ON e.fk_id_rol = r.idrol 
    WHERE e.email = ? AND e.activo = 1 
    LIMIT 1
");
$stmt->bind_param("s", $email);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo json_encode(['ok' => false, 'error' => 'No encontramos tu cuenta de empleado activa.']);
    exit();
}

$emp = $res->fetch_assoc();
$idRol = intval($emp['fk_id_rol']);
$rolNombre = $emp['rol'] ?? 'Empleado';

// Validar que el rol corresponda a la aplicación
if (!in_array($idRol, $configApp['roles'])) {
    echo json_encode([
        'ok' => false, 
        'error' => "Acceso restringido: Tu perfil es '$rolNombre' y no cuenta con permisos para operar la App de {$configApp['nombre']}."
    ]);
    exit();
}

if ($emp['token_intentos'] >= 5) {
    echo json_encode(['ok' => false, 'error' => 'Superaste el límite de intentos permitidos. Solicitá un nuevo código.']);
    exit();
}

if (empty($emp['token']) || strtotime($emp['token_expira']) < time()) {
    echo json_encode(['ok' => false, 'error' => 'El código de seguridad venció o ya fue utilizado. Solicitá uno nuevo.']);
    exit();
}

if ($codigo !== $emp['token']) {
    $conn->query("UPDATE empleado SET token_intentos = token_intentos + 1 WHERE idempleado = " . (int)$emp['idempleado']);
    echo json_encode(['ok' => false, 'error' => 'Código de acceso incorrecto. Verificá los 6 dígitos.']);
    exit();
}

// Token válido: limpiar token
$conn->query("UPDATE empleado SET token = NULL, token_expira = NULL, token_intentos = 0 WHERE idempleado = " . (int)$emp['idempleado']);

// Determinar turno asignado
$idturno = (int)($emp['fk_id_turno'] ?? 1);
$nombreTurno = 'Turno Mañana';

$resT = $conn->query("SELECT idturnos, nombre, horaInicio, horaFin FROM turnos WHERE idturnos = $idturno");
if ($resT && $rowT = $resT->fetch_assoc()) {
    $nombreTurno = $rowT['nombre'];
} else {
    $horaActual = date('H:i:s');
    $resT2 = $conn->query("SELECT idturnos, nombre FROM turnos WHERE '$horaActual' BETWEEN horaInicio AND horaFin LIMIT 1");
    if ($resT2 && $rowT2 = $resT2->fetch_assoc()) {
        $idturno = (int)$rowT2['idturnos'];
        $nombreTurno = $rowT2['nombre'];
    }
}

$nombreAppOrigen = 'App ' . ucfirst($app);

// Cerrar sesiones activas previas del empleado
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

// Registrar Entrada en la tabla de turnos
$stmtReg = $conn->prepare("
    INSERT INTO registro_turno_empleado (fk_id_empleado, fk_id_turno, fecha, hora_entrada, estado, origen)
    VALUES (?, ?, CURDATE(), NOW(), 'Activo', ?)
");
$stmtReg->bind_param("iis", $emp['idempleado'], $idturno, $nombreAppOrigen);
$stmtReg->execute();
$idregistro_turno = $stmtReg->insert_id;
$horaEntrada = date('H:i:s');

// Variables de sesión web
$_SESSION['empleado_id'] = $emp['idempleado'];
$_SESSION['empleado_nombre'] = $emp['nombre'] . ' ' . $emp['apellido'];
$_SESSION['empleado_email'] = $emp['email'];
$_SESSION['empleado_rol'] = $rolNombre;
$_SESSION['idregistro_turno'] = $idregistro_turno;

// Compatibilidad con apps específicas
$_SESSION['cajero_id'] = $emp['idempleado'];
$_SESSION['cajero_nombre'] = $emp['nombre'] . ' ' . $emp['apellido'];
$_SESSION['cajero_email'] = $emp['email'];
$_SESSION['cajero_rol'] = $idRol;

$_SESSION['cocinero_id'] = $emp['idempleado'];
$_SESSION['cocinero_nombre'] = $emp['nombre'] . ' ' . $emp['apellido'];
$_SESSION['cocinero_idregistro'] = $idregistro_turno;

$_SESSION['mesero_id'] = $emp['idempleado'];
$_SESSION['mesero_nombre'] = $emp['nombre'] . ' ' . $emp['apellido'];
$_SESSION['mesero_idregistro'] = $idregistro_turno;

$nombreCompleto = $emp['nombre'] . ' ' . $emp['apellido'];

$empleadoData = [
    'id' => (int)$emp['idempleado'],
    'idempleado' => (int)$emp['idempleado'],
    'nombre' => $emp['nombre'],
    'apellido' => $emp['apellido'],
    'nombre_completo' => $nombreCompleto,
    'email' => $emp['email'],
    'rol' => $rolNombre,
    'idrol' => $idRol,
    'avatar' => $emp['avatar'] ?: '👤',
    'dni' => $emp['dni'] ?: '',
    'telefono' => $emp['telefono'] ?: '',
    'vehiculo' => $emp['vehiculo'] ?: '',
    'patente' => $emp['patente'] ?: '',
    'sucursal_base' => $emp['sucursal_base'] ?: 'Mostaza Formosa Centro',
    'calificacion' => floatval($emp['calificacion'] ?? 4.95),
    'viajes_totales' => intval($emp['viajes_totales'] ?? 0),
    'estado_servicio' => $emp['estado_servicio'] ?: 'Disponible',
    'idregistro_turno' => $idregistro_turno,
    'idturno' => $idturno,
    'turno' => $nombreTurno,
    'hora_entrada' => $horaEntrada,
    'app' => $app
];

echo json_encode([
    'ok' => true,
    'mensaje' => "¡Identificación exitosa! Bienvenido a la App de {$configApp['nombre']}, $nombreCompleto.",
    'empleado' => $empleadoData,
    'cajero' => $empleadoData,
    'repartidor' => $empleadoData,
    'cocinero' => $empleadoData,
    'mesero' => $empleadoData,
    'token' => 'tok_emp_' . $emp['idempleado'] . '_' . bin2hex(random_bytes(8))
]);

