<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "restaurante";

$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Conexión fallida"]);
    exit();
}

// Consulta pedidos 'Incompleto' (o 'Pendiente') uniendo los detalles del pedido y productos
$sql = "SELECT 
            p.idpedido, 
            p.fecha_hora, 
            p.estado,
            p.mesa,
            GROUP_CONCAT(CONCAT(dp.cantidad, 'x ', pr.nombre) SEPARATOR ', ') AS productos_detalle,
            SUM(dp.cantidad * pr.precio) AS total
        FROM pedido p
        INNER JOIN detalle_pedido dp ON p.idpedido = dp.fk_id_pedido
        INNER JOIN producto pr ON dp.fk_id_producto = pr.idproducto
        WHERE p.estado = 'Incompleto'
        GROUP BY p.idpedido
        ORDER BY p.fecha_hora ASC";

$result = $conn->query($sql);
$pedidos = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $pedidos[] = $row;
    }
    echo json_encode(["status" => "success", "data" => $pedidos]);
} else {
    echo json_encode(["status" => "error", "message" => $conn->error]);
}

$conn->close();
?>