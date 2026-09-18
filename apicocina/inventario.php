<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "restaurante";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(["error" => "Conexión fallida: " . $conn->connect_error]);
    exit();
}

// Consulta para obtener los productos y su stock
$sql = "SELECT idinsumo, nombre, categoria, stock, precio FROM insumo";
$result = $conn->query($sql);

$productos = array();

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $productos[] = array(
            "id"        => $row["idinsumo"],
            "nombre"    => $row["nombre"],
            "categoria" => $row["categoria"],
            "stock"     => $row["stock"],
            "precio"    => $row["precio"]
        );
    }
}

echo json_encode($productos);
?>