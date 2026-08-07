<?php
// ============================================================
// conexion.php
// Conexión a la base de datos MySQL (misma base que rest__2_.sql).
// Si tu XAMPP/WAMP usa otro usuario/clave, ajustalo acá.
// ============================================================
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "rest";

$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}
