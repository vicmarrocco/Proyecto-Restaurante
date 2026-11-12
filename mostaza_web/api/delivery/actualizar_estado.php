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
$idEmpleado = !empty($input['idempleado']) ? intval($input['idempleado']) : (!empty($input['repartidor_id']) ? intval($input['repartidor_id']) : null);

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
    $accionAudit = "Actualización de Delivery a $nuevoEstado";
    $detallesAudit = "Gestión por Repartidor Oficial: $repartidor";

    // BLOQUEO ANTI-COLISIÓN EN DELIVERY:
    // Verificar si otro repartidor ya tomó el pedido en viaje
    if ($nuevoEstado === 'En Camino') {
        $stmtChk = $conn->prepare("SELECT idpedido, estado, repartidor_asignado, idempleado_repartidor FROM pedido_web WHERE idpedido = ?");
        $stmtChk->bind_param("i", $idpedido);
        $stmtChk->execute();
        $pRep = $stmtChk->get_result()->fetch_assoc();
        if ($pRep) {
            if ($pRep['estado'] === 'En Camino' && !empty($pRep['idempleado_repartidor']) && $idEmpleado && intval($pRep['idempleado_repartidor']) !== intval($idEmpleado)) {
                echo json_encode([
                    'ok' => false,
                    'error' => "El pedido #$idpedido ya fue tomado por el repartidor {$pRep['repartidor_asignado']}."
                ]);
                exit();
            }
        }
    }

    if ($nuevoEstado === 'En Camino') {
        $accionAudit = "Despachado y en Viaje a Domicilio";
        $detallesAudit = "El repartidor $repartidor retiró el pedido y se dirige al domicilio del cliente.";
    } elseif ($nuevoEstado === 'Entregado') {
        $accionAudit = "Entrega Finalizada en Puerta";
        $detallesAudit = "Pedido entregado con éxito por $repartidor.";

        // Incrementar viajes completados en empleado
        if ($idEmpleado) {
            $conn->query("UPDATE empleado SET viajes_totales = viajes_totales + 1 WHERE idempleado = $idEmpleado");
        } else {
            $conn->query("UPDATE empleado SET viajes_totales = viajes_totales + 1 WHERE CONCAT(nombre, ' ', apellido) = '" . $conn->real_escape_string($repartidor) . "'");
        }
    } elseif ($nuevoEstado === 'Cancelado') {
        $accionAudit = "Cancelación por Delivery";
        $detallesAudit = $motivoCancelacion;
    }

    // Registrar en auditoría central (actualiza pedido_auditoria e hitos en pedido_web)
    registrarAuditoriaPedido($conn, $idpedido, $nuevoEstado, $accionAudit, 'Repartidor', $repartidor, $idEmpleado, $detallesAudit);

    echo json_encode([
        'ok' => true,
        'mensaje' => "Pedido #$idpedido actualizado a '$nuevoEstado' por $repartidor correctamente.",
        'idpedido' => $idpedido,
        'estado' => $nuevoEstado,
        'repartidor' => $repartidor
    ]);

} catch (Exception $e) {
    echo json_encode([
        'ok' => false,
        'error' => 'Error al actualizar pedido: ' . $e->getMessage()
    ]);
}
