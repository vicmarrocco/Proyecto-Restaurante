<?php
session_start();
require('conexion.php');

$volver = $_POST['volver'] ?? ($_SERVER['HTTP_REFERER'] ?? 'productos.php');

if (!isset($_SESSION['usuario_id'])) {
    $_SESSION['redirect_after_login'] = $volver;
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['idproducto'])) {
    $idproducto = intval($_POST['idproducto']);
    $cantidad   = max(1, intval($_POST['cantidad'] ?? 1));

    $stmt = $conn->prepare("SELECT idproducto, nombre, precio, imagen FROM producto WHERE idproducto = ? AND activo = 1");
    $stmt->bind_param("i", $idproducto);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        $p = $res->fetch_assoc();

        if (!isset($_SESSION['carrito'])) {
            $_SESSION['carrito'] = [];
        }

        $key = 'p_' . $p['idproducto'];

        if (isset($_SESSION['carrito'][$key])) {
            $_SESSION['carrito'][$key]['cantidad'] += $cantidad;
        } else {
            $_SESSION['carrito'][$key] = [
                'tipo'       => 'catalogo',
                'idproducto' => $p['idproducto'],
                'nombre'     => $p['nombre'],
                'imagen'     => $p['imagen'],
                'precio'     => (float) $p['precio'],
                'cantidad'   => $cantidad,
            ];
        }
    }
}

header("Location: " . $volver);
exit();
