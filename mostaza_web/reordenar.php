<?php
// ============================================================
// reordenar.php
// Carga los productos de un pedido anterior en el carrito y va a checkout.
// ============================================================
session_start();
require('conexion.php');

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$idpedido = intval($_GET['id'] ?? 0);
$idusuario = $_SESSION['usuario_id'];

$stmt = $conn->prepare("SELECT * FROM pedido_web WHERE idpedido = ? AND idusuario = ?");
$stmt->bind_param("ii", $idpedido, $idusuario);
$stmt->execute();
$pedido = $stmt->get_result()->fetch_assoc();

if (!$pedido) {
    header("Location: mis_pedidos.php");
    exit();
}

$stmtDet = $conn->prepare("SELECT * FROM detalle_pedido_web WHERE idpedido = ?");
$stmtDet->bind_param("i", $idpedido);
$stmtDet->execute();
$detalles = $stmtDet->get_result();

if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

while ($d = $detalles->fetch_assoc()) {
    $idproducto = $d['idproducto'];
    $img = 'descarga.png';

    if ($idproducto) {
        $stmtP = $conn->prepare("SELECT imagen FROM producto WHERE idproducto = ?");
        $stmtP->bind_param("i", $idproducto);
        $stmtP->execute();
        $pRes = $stmtP->get_result()->fetch_assoc();
        if ($pRes && !empty($pRes['imagen'])) {
            $img = $pRes['imagen'];
        }
        $key = 'p_' . $idproducto;
    } else {
        $key = 'custom_' . uniqid();
    }

    $_SESSION['carrito'][$key] = [
        'tipo'          => $idproducto ? 'catalogo' : 'personalizado',
        'idproducto'    => $idproducto,
        'nombre'        => $d['descripcion'],
        'imagen'        => $img,
        'precio'        => floatval($d['precio_unitario']),
        'cantidad'      => intval($d['cantidad']),
        'modificadores' => $d['modificadores'] ?? ''
    ];
}

header("Location: carrito.php");
exit();
