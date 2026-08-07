<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['key'])) {
    $key = $_POST['key'];
    if (isset($_SESSION['carrito'][$key])) {
        unset($_SESSION['carrito'][$key]);
    }
}

header("Location: carrito.php");
exit();
