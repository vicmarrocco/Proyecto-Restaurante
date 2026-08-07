<?php
session_start();
require('conexion.php');

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$carrito = $_SESSION['carrito'] ?? [];

if (empty($carrito) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: carrito.php");
    exit();
}

$idusuario = $_SESSION['usuario_id'];
$total = 0;
foreach ($carrito as $item) {
    $total += $item['precio'] * $item['cantidad'];
}

$conn->begin_transaction();
try {
    $stmt = $conn->prepare("INSERT INTO pedido_web (idusuario, total, estado) VALUES (?, ?, 'Recibido')");
    $stmt->bind_param("id", $idusuario, $total);
    $stmt->execute();
    $idpedido = $conn->insert_id;

    $stmtDet = $conn->prepare(
        "INSERT INTO detalle_pedido_web (idpedido, idproducto, descripcion, precio_unitario, cantidad, subtotal)
         VALUES (?, ?, ?, ?, ?, ?)"
    );

    foreach ($carrito as $item) {
        $idproducto = $item['idproducto'] ?? null;
        $descripcion = $item['nombre'];
        $precio = $item['precio'];
        $cantidad = $item['cantidad'];
        $subtotal = $precio * $cantidad;

        $stmtDet->bind_param("iisdid", $idpedido, $idproducto, $descripcion, $precio, $cantidad, $subtotal);
        $stmtDet->execute();
    }

    $conn->commit();
    unset($_SESSION['carrito']);

    header("Location: pedido_confirmado.php?id=" . $idpedido);
    exit();
} catch (Exception $e) {
    $conn->rollback();
    header("Location: carrito.php?error=No pudimos procesar tu pedido, intentá de nuevo");
    exit();
}
