<?php
// ============================================================
// api/delivery/actualizar_estado.php
// Actualización del estado de un pedido de delivery (En Camino, Entregado).
// ============================================================
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once(__DIR__ . '/../../conexion.php');

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$idpedido = intval($input['idpedido'] ?? 0);
$nuevoEstado = trim($input['nuevo_estado'] ?? '');
$repartidor = trim($input['repartidor'] ?? 'Repartidor Mostaza');

if ($idpedido <= 0 || empty($nuevoEstado)) {
    echo json_encode(['ok' => false, 'error' => 'Datos incompletos para actualizar el pedido.']);
    exit();
}

$estadosPermitidos = ['En Preparación', 'Listo para Despacho', 'En Camino', 'Entregado', 'Cancelado'];
if (!in_array($nuevoEstado, $estadosPermitidos)) {
    echo json_encode(['ok' => false, 'error' => 'Estado no válido.']);
    exit();
}

$motivoCancelacion = trim($input['motivo_cancelacion'] ?? 'Imposibilidad de entrega por delivery');

try {
    if ($nuevoEstado === 'En Camino') {
        $stmt = $conn->prepare("UPDATE pedido_web SET estado = ?, repartidor_asignado = ? WHERE idpedido = ?");
        $stmt->bind_param("ssi", $nuevoEstado, $repartidor, $idpedido);
    } elseif ($nuevoEstado === 'Cancelado') {
        $canceladoPor = "Delivery ($repartidor)";
        $stmt = $conn->prepare("UPDATE pedido_web SET estado = ?, motivo_cancelacion = ?, cancelado_por = ? WHERE idpedido = ?");
        $stmt->bind_param("sssi", $nuevoEstado, $motivoCancelacion, $canceladoPor, $idpedido);
    } else {
        $stmt = $conn->prepare("UPDATE pedido_web SET estado = ? WHERE idpedido = ?");
        $stmt->bind_param("si", $nuevoEstado, $idpedido);
    }
    
    $stmt->execute();

    if ($stmt->affected_rows >= 0) {
        echo json_encode([
            'ok' => true,
            'mensaje' => "Pedido #$idpedido actualizado a '$nuevoEstado' correctamente.",
            'idpedido' => $idpedido,
            'estado' => $nuevoEstado,
            'repartidor' => $repartidor
        ]);
    } else {
        echo json_encode(['ok' => false, 'error' => 'No se encontró el pedido o no hubo cambios.']);
    }

} catch (Exception $e) {
    echo json_encode([
        'ok' => false,
        'error' => 'Error al actualizar pedido: ' . $e->getMessage()
    ]);
}
