<?php
// ============================================================
// api/auth/solicitar_codigo.php
// Envía un código token de 6 dígitos por Gmail al empleado,
// validando estrictamente que su rol corresponda a la aplicación.
// ============================================================
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once(__DIR__ . '/../../conexion.php');
require_once(__DIR__ . '/../../mail/enviar_correo.php');

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$email = htmlspecialchars(trim($input['email'] ?? ''));
$app = strtolower(trim($input['app'] ?? 'cajero'));

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['ok' => false, 'error' => 'Ingresá un correo electrónico válido.']);
    exit();
}

// Mapeo de roles permitidos por aplicación
// 1: Cliente, 2: Cajero, 3: Cocinero, 4: Mesero, 5: Administrador, 6: Repartidor
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

try {
    // 1. Buscar si existe en tabla empleado
    $stmt = $conn->prepare("
        SELECT e.idempleado, e.nombre, e.apellido, e.email, e.fk_id_rol, r.rol, e.avatar, e.activo 
        FROM empleado e 
        LEFT JOIN rol r ON e.fk_id_rol = r.idrol 
        WHERE e.email = ? AND e.activo = 1 
        LIMIT 1
    ");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        echo json_encode([
            'ok' => false, 
            'error' => "No se encontró ningún empleado activo registrado con el correo '$email'. Contactá a Recursos Humanos o Administración."
        ]);
        exit();
    }

    $emp = $res->fetch_assoc();
    $idEmpleado = intval($emp['idempleado']);
    $nombreEmpleado = $emp['nombre'] . ' ' . $emp['apellido'];
    $rolEmpleado = $emp['rol'] ?? 'Empleado';
    $idRolEmpleado = intval($emp['fk_id_rol']);

    // 2. Validación estricta de Rol por Aplicación
    if (!in_array($idRolEmpleado, $configApp['roles'])) {
        echo json_encode([
            'ok' => false,
            'error' => "Acceso no autorizado: Tu usuario tiene perfil '$rolEmpleado' y no cuenta con permisos para operar la App de {$configApp['nombre']}."
        ]);
        exit();
    }

    // 3. Generar token de seguridad de 6 dígitos
    $token = strval(random_int(100000, 999999));
    $expira = date('Y-m-d H:i:s', strtotime('+15 minutes'));

    $upd = $conn->prepare("UPDATE empleado SET token = ?, token_expira = ?, token_intentos = 0 WHERE idempleado = ?");
    $upd->bind_param("ssi", $token, $expira, $idEmpleado);
    $upd->execute();

    // 4. Enviar código por correo Gmail
    $envio = enviarTokenPorCorreo($email, $nombreEmpleado, $token);

    if ($envio['ok']) {
        echo json_encode([
            'ok' => true,
            'mensaje' => "Te enviamos un código de seguridad de 6 dígitos a tu correo corporativo ($email).",
            'email' => $email,
            'nombre' => $nombreEmpleado,
            'rol' => $rolEmpleado,
            'app' => $app,
            'debug_code' => $token
        ]);
    } else {
        // Fallback para agilidad en entorno local / offline
        echo json_encode([
            'ok' => true,
            'mensaje' => "Código generado correctamente. (Aviso SMTP: " . ($envio['error'] ?? 'Sin conexión externa') . ") Tu código de acceso es: $token",
            'email' => $email,
            'nombre' => $nombreEmpleado,
            'rol' => $rolEmpleado,
            'app' => $app,
            'debug_code' => $token
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'ok' => false,
        'error' => 'Error en el servidor: ' . $e->getMessage()
    ]);
}
