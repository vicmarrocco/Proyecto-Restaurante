<?php
// ============================================================
// api/mesero/auth.php
// Login, Turno y Registro de Entrada/Salida para Meseros
// ============================================================
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once(__DIR__ . '/../../conexion.php');

$metodo = $_SERVER['REQUEST_METHOD'];

if ($metodo === 'GET') {
    $res = $conn->query("SELECT * FROM registro_turno_empleado WHERE rol = 'Mesero' AND estado = 'Activo' ORDER BY idregistro DESC LIMIT 1");
    if ($res && $row = $res->fetch_assoc()) {
        echo json_encode([
            'ok' => true,
            'turno_activo' => true,
            'mesero' => [
                'idregistro' => intval($row['idregistro']),
                'nombre' => $row['nombre_empleado'],
                'rol' => $row['rol'],
                'hora_entrada' => date('H:i', strtotime($row['hora_entrada'])),
                'fecha' => $row['fecha']
            ]
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'ok' => true,
            'turno_activo' => false,
            'mesero' => null
        ], JSON_UNESCAPED_UNICODE);
    }
    exit();
}

if ($metodo === 'POST') {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true) ?? $_POST;
    $accion = $input['accion'] ?? 'login';

    if ($accion === 'login') {
        $nombre = trim($input['nombre'] ?? 'Lucas Benítez');
        $rol = 'Mesero';
        $origen = 'App Mesero';

        $stmt = $conn->prepare("INSERT INTO registro_turno_empleado (nombre_empleado, rol, fecha, hora_entrada, estado, origen) VALUES (?, ?, CURDATE(), NOW(), 'Activo', ?)");
        $stmt->bind_param("sss", $nombre, $rol, $origen);
        $stmt->execute();
        $idreg = $stmt->insert_id;

        echo json_encode([
            'ok' => true,
            'mensaje' => "Bienvenido $nombre. Turno de Salón iniciado.",
            'mesero' => [
                'idregistro' => $idreg,
                'nombre' => $nombre,
                'rol' => $rol,
                'hora_entrada' => date('H:i')
            ]
        ], JSON_UNESCAPED_UNICODE);
        exit();
    } elseif ($accion === 'logout') {
        $idreg = intval($input['idregistro'] ?? 0);
        if ($idreg > 0) {
            $conn->query("UPDATE registro_turno_empleado SET estado = 'Finalizado', hora_salida = NOW() WHERE idregistro = $idreg");
        } else {
            $conn->query("UPDATE registro_turno_empleado SET estado = 'Finalizado', hora_salida = NOW() WHERE rol = 'Mesero' AND estado = 'Activo'");
        }

        echo json_encode([
            'ok' => true,
            'mensaje' => "Turno de salón cerrado correctamente."
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }
}
