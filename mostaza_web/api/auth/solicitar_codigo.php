<?php
// ============================================================
// api/auth/solicitar_codigo.php
// Envía un código token de 6 dígitos por Gmail al cajero/empleado.
// ============================================================
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once(__DIR__ . '/../../conexion.php');
require_once(__DIR__ . '/../../mail/enviar_correo.php');

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$email = htmlspecialchars(trim($input['email'] ?? ''));

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['ok' => false, 'error' => 'Ingresá un correo electrónico válido.']);
    exit();
}

try {
    // 1. Buscar si existe en tabla empleado
    $stmt = $conn->prepare("SELECT idempleado, nombre, apellido FROM empleado WHERE email = ? AND activo = 1 LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        $emp = $res->fetch_assoc();
        $idEmpleado = $emp['idempleado'];
        $nombreEmpleado = $emp['nombre'];
    } else {
        // Buscar si existe en usuario_web
        $stmtU = $conn->prepare("SELECT idusuario, nombre, apellido FROM usuario_web WHERE email = ? LIMIT 1");
        $stmtU->bind_param("s", $email);
        $stmtU->execute();
        $resU = $stmtU->get_result();

        if ($resU->num_rows > 0) {
            $u = $resU->fetch_assoc();
            $nombreEmpleado = $u['nombre'];
            $apellidoEmpleado = $u['apellido'];
        } else {
            $nombreEmpleado = "Cajero " . ucfirst(explode('@', $email)[0]);
            $apellidoEmpleado = "Mostaza";
        }

        $usr = strtolower(explode('@', $email)[0]);
        $insE = $conn->prepare("INSERT INTO empleado (nombre, apellido, email, usuario, fk_id_rol, activo) VALUES (?, ?, ?, ?, 2, 1)");
        $insE->bind_param("ssss", $nombreEmpleado, $apellidoEmpleado, $email, $usr);
        $insE->execute();
        $idEmpleado = $conn->insert_id;
    }

    // 2. Generar token de 6 dígitos
    $token = strval(random_int(100000, 999999));
    $expira = date('Y-m-d H:i:s', strtotime('+15 minutes'));

    $upd = $conn->prepare("UPDATE empleado SET token = ?, token_expira = ?, token_intentos = 0 WHERE idempleado = ?");
    $upd->bind_param("ssi", $token, $expira, $idEmpleado);
    $upd->execute();

    // 3. Enviar código por correo Gmail
    $envio = enviarTokenPorCorreo($email, $nombreEmpleado, $token);

    if (!$envio['ok']) {
        echo json_encode([
            'ok' => false, 
            'error' => 'No se pudo enviar el correo por SMTP de Gmail: ' . ($envio['error'] ?? 'Verificá tu conexión a internet o credenciales.')
        ]);
        exit();
    }

    echo json_encode([
        'ok' => true,
        'mensaje' => 'Te enviamos un código de seguridad de 6 dígitos a tu correo.',
        'email' => $email,
        'nombre' => $nombreEmpleado
    ]);

} catch (Exception $e) {
    echo json_encode([
        'ok' => false,
        'error' => 'Error en el servidor: ' . $e->getMessage()
    ]);
}
