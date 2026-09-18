<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "restaurante";

$conn = new mysqli($servername, $username, $password, $dbname);

// Configurar charset UTF-8 para evitar problemas con acentos o caracteres especiales
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    echo json_encode([
        "status" => "error", 
        "message" => "Conexión fallida: " . $conn->connect_error
    ]);
    exit();
}

// Obtener el ID enviado por la URL (ej: perfil.php?id=1)
$id_empleado = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_empleado <= 0) {
    echo json_encode([
        "status" => "error", 
        "message" => "ID de empleado no válido o no proporcionado"
    ]);
    exit();
}

// Consulta con JOINs para traer la información completa del perfil
$sql = "SELECT 
            e.idempleado, 
            e.nombre, 
            e.apellido, 
            e.dni, 
            e.usuario, 
            tel.telefono AS telefono, 
            em.email AS email, 
            r.rol AS rol, 
            g.genero AS genero, 
            t.nombre AS turno 
        FROM empleado e
        LEFT JOIN telefono tel ON e.fk_id_telefono = tel.idtelefono
        LEFT JOIN email em ON e.fk_id_email = em.idemail
        LEFT JOIN rol r ON e.fk_id_rol = r.idrol
        LEFT JOIN genero g ON e.fk_id_genero = g.idgenero
        LEFT JOIN turnos t ON e.fk_id_turno = t.idturnos
        WHERE e.idempleado = ?";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode([
        "status" => "error", 
        "message" => "Error en la consulta SQL: " . $conn->error
    ]);
    exit();
}

$stmt->bind_param("i", $id_empleado);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $empleado = $result->fetch_assoc();
    echo json_encode([
        "status" => "success", 
        "data" => $empleado
    ]);
} else {
    echo json_encode([
        "status" => "error", 
        "message" => "Empleado no encontrado con el ID proporcionado"
    ]);
}

$stmt->close();
$conn->close();
?>