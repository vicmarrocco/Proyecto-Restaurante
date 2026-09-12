<?php
// ============================================================
// tracking_estado_ajax.php
// Devuelve el estado actual de un pedido en tiempo real.
// ============================================================
session_start();
header('Content-Type: application/json');
require('conexion.php');

$idpedido = intval($_GET['id'] ?? 0);
$idusuario = $_SESSION['usuario_id'] ?? 0;

if (!$idpedido || !$idusuario) {
    echo json_encode(['ok' => false, 'error' => 'No autorizado']);
    exit();
}

$stmt = $conn->prepare("SELECT idpedido, estado, tipo_entrega, fecha, repartidor_asignado FROM pedido_web WHERE idpedido = ? AND idusuario = ?");
$stmt->bind_param("ii", $idpedido, $idusuario);
$stmt->execute();
$pedido = $stmt->get_result()->fetch_assoc();

if (!$pedido) {
    echo json_encode(['ok' => false, 'error' => 'Pedido no encontrado']);
    exit();
}

// Mapeo de paso de progreso (1 a 4)
$estado = $pedido['estado'];
$paso = 1;
$mensaje = 'Recibimos tu pedido en el sistema';

if ($estado === 'En Cocina' || $estado === 'En Preparacion') {
    $paso = 2;
    $mensaje = 'Tu pedido se está preparando en la cocina';
} elseif ($estado === 'Listo' || $estado === 'En Camino') {
    $paso = 3;
    $mensaje = ($pedido['tipo_entrega'] === 'delivery') ? '¡El repartidor está en camino a tu domicilio!' : '¡Tu pedido ya está listo para retirar!';
} elseif ($estado === 'Entregado') {
    $paso = 4;
    $mensaje = '¡Pedido entregado con éxito! ¡Que lo disfrutes!';
}

echo json_encode([
    'ok' => true,
    'idpedido' => $pedido['idpedido'],
    'estado' => $estado,
    'paso' => $paso,
    'mensaje' => $mensaje,
    'repartidor' => $pedido['repartidor_asignado'] ?? 'Asignando repartidor...',
    'hora' => date('H:i', strtotime($pedido['fecha']))
]);
