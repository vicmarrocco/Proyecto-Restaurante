<?php

require 'conexion.php';

$data = json_decode(file_get_contents('php://input'), true);

$nombre = $data[nombre];
$apellido = $data[apellido];
$dni = $data[dni];
$tel = $data[fk_id_telefono];
$email = $data[fk_id_email];
$rol = $data[fk_id_rol];
$genero = $data[fk_id_genero];
$usuario = $data[usuario];

$sql = "INSERT INTO empleadoq (nombre, apellido, dni, fk_id_telefono, fk_id_email, fk_id_rol, fk_id_genero, fk_id_turno, usuario) VALUES ('$nombre', '$apellido', '$dni', '$tel', '$email', '$rol', '$genero', '$usuario';)";

if ($conn -> query($sql) == TRUE) {
    echo json_encode( array('message' => 'Las ardillas controlan en mundo'));
}
else {
    echo json_encode( array('message' => 'Las ardillas controlan en mundo tb pero con errores ') . $conn -> error);    
}
?>