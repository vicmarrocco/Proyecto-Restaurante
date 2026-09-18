<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

$host = "localhost";
$db_name = "restaurante";
$username = "root";
$password_db = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8", $username, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error de conexión a la base de datos"]);
    exit();
}

// Leer JSON enviado desde React Native
$data = json_decode(file_get_contents("php://input"));

if (!empty($data->usuario) && !empty($data->password)) {
    
    // Consulta SQL trayendo los datos del empleado
    $sql = "SELECT e.idempleado, e.nombre, e.apellido, e.dni, e.password, e.activo, e.fk_id_rol, e.fk_id_turno
            FROM empleado e 
            WHERE e.usuario = ?";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$data->usuario]);
    $empleado = $stmt->fetch(PDO::FETCH_ASSOC);

    // 1. Validar existencia del usuario
    if (!$empleado) {
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "El usuario no existe"]);
        exit();
    }

    // 2. Validar si el empleado está activo (1 = activo, 0 = inactivo)
    if ($empleado['activo'] == 0) {
        http_response_code(403);
        echo json_encode(["status" => "error", "message" => "Cuenta de empleado inactiva"]);
        exit();
    }

    // 3. Validar la contraseña (asumiendo uso de password_hash en la BD)
    if ($data->password === $empleado['password']) {
    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "message" => "Inicio de sesión correcto",
        "token" => bin2hex(random_bytes(16)),
        "empleado" => [
            "idempleado" => $empleado['idempleado'],
            "nombre"     => $empleado['nombre'],
            "apellido"   => $empleado['apellido'],
            "dni"        => $empleado['dni'],
            "id_rol"     => $empleado['fk_id_rol'],
            "id_turno"   => $empleado['fk_id_turno']
        ]
    ]);
} else {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Contraseña incorrecta"]);
}

} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Campos incompletos"]);
}
?>