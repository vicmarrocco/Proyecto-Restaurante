<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "restaurante";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Conexión fallida"]);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);
$idpedido = isset($data['idpedido']) ? intval($data['idpedido']) : 0;
$nuevo_estado = isset($data['estado']) ? $data['estado'] : 'Completo';

if ($idpedido <= 0) {
    echo json_encode(["status" => "error", "message" => "ID de pedido inválido"]);
    exit();
}

$sql = "UPDATE pedido SET estado = ? WHERE idpedido = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $nuevo_estado, $idpedido);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Estado actualizado exitosamente"]);
} else {
    echo json_encode(["status" => "error", "message" => "No se pudo actualizar el estado"]);
}

$stmt->close();
$conn->close();
?>