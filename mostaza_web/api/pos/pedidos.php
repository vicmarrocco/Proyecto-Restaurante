<?php
// ============================================================
// api/pos/pedidos.php
// Consulta historial de pedidos y facturas emitidas de la jornada.
// ============================================================
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once(__DIR__ . '/../../conexion.php');

// Manejar Acciones de Pedido (POST) - Cambiar Estado y Cancelación
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $idpedido = intval($input['idpedido'] ?? 0);
    $accion = trim($input['accion'] ?? 'cancelar_pedido');

    if ($idpedido <= 0) {
        echo json_encode(['ok' => false, 'error' => 'ID de pedido inválido.']);
        exit();
    }

    // Verificar pedido
    $stmtCheck = $conn->prepare("SELECT idpedido, estado, numero_mesa, tipo_entrega FROM pedido_web WHERE idpedido = ?");
    $stmtCheck->bind_param("i", $idpedido);
    $stmtCheck->execute();
    $p = $stmtCheck->get_result()->fetch_assoc();

    if (!$p) {
        echo json_encode(['ok' => false, 'error' => 'El pedido no existe.']);
        exit();
    }

    // 1. Acción: Cambiar Estado (Cocina / POS / Mesero)
    if ($accion === 'cambiar_estado') {
        $nuevoEstado = trim($input['nuevo_estado'] ?? '');
        $estadosValidos = ['Recibido', 'En Preparacion', 'En Preparación', 'En Cocina', 'Listo para Despacho', 'Listo', 'En Camino', 'Entregado'];
        if (!in_array($nuevoEstado, $estadosValidos)) {
            echo json_encode(['ok' => false, 'error' => "Estado no válido: $nuevoEstado"]);
            exit();
        }

        // Identificar empleado y rol
        $idEmpleado = !empty($input['idempleado']) ? intval($input['idempleado']) : ($_SESSION['cocinero_id'] ?? ($_SESSION['cajero_id'] ?? null));
        $nombreResp = trim($input['empleado_nombre'] ?? ($input['cocinero_nombre'] ?? ($_SESSION['cocinero_nombre'] ?? ($_SESSION['cajero_nombre'] ?? 'Personal de Cocina'))));
        $rolResp = trim($input['rol'] ?? (in_array($nuevoEstado, ['En Preparacion', 'En Preparación', 'En Cocina', 'Listo para Despacho', 'Listo']) ? 'Cocinero' : 'Cajero'));
        $detalles = trim($input['detalles'] ?? "Actualización de comanda a '$nuevoEstado'");

        // Registrar auditoría estricta (actualiza pedido_auditoria e hitos en pedido_web)
        registrarAuditoriaPedido($conn, $idpedido, $nuevoEstado, "Comanda $nuevoEstado", $rolResp, $nombreResp, $idEmpleado, $detalles);

        echo json_encode([
            'ok' => true,
            'mensaje' => "Comanda #$idpedido actualizada a '$nuevoEstado' por $nombreResp exitosamente.",
            'idpedido' => $idpedido,
            'nuevo_estado' => $nuevoEstado,
            'responsable' => $nombreResp,
            'rol' => $rolResp
        ]);
        exit();
    }

    // 2. Acción: Cancelar Pedido
    $motivo = trim($input['motivo_cancelacion'] ?? ($input['motivo'] ?? 'Cancelado por solicitud del cliente en caja'));
    $canceladoPor = trim($input['cancelado_por'] ?? ($_SESSION['cajero_nombre'] ?? 'Cajero'));
    $idEmpleado = !empty($input['idempleado']) ? intval($input['idempleado']) : ($_SESSION['cajero_id'] ?? null);
    $rolCanc = trim($input['rol'] ?? 'Cajero');

    if ($p['estado'] === 'Cancelado') {
        echo json_encode(['ok' => false, 'error' => 'El pedido ya se encuentra cancelado.']);
        exit();
    }

    // Registrar auditoría estricta de cancelación
    registrarAuditoriaPedido($conn, $idpedido, 'Cancelado', 'Cancelación de Pedido', $rolCanc, $canceladoPor, $idEmpleado, $motivo);

    // Si tenía mesa ocupada, liberarla
    if (!empty($p['numero_mesa'])) {
        $mesaNum = intval($p['numero_mesa']);
        $conn->query("UPDATE mesa SET estado = 'disponible' WHERE numero_mesa = $mesaNum");
    }

    echo json_encode([
        'ok' => true,
        'mensaje' => "Comanda #$idpedido cancelada exitosamente.",
        'idpedido' => $idpedido,
        'estado' => 'Cancelado',
        'motivo' => $motivo
    ]);
    exit();
}

$fecha = $_GET['fecha'] ?? 'hoy';
$idempleado = !empty($_GET['idempleado']) ? intval($_GET['idempleado']) : null;
$idarqueo = !empty($_GET['idarqueo']) ? intval($_GET['idarqueo']) : null;

$sql = "SELECT p.*, f.idfactura, f.numero_factura, f.tipo_comprobante, f.cae, f.cae_vto, f.neto_gravado, f.iva_21,
               u.nombre AS usuario_nombre, u.apellido AS usuario_apellido,
               e.nombre AS empleado_nombre, e.apellido AS empleado_apellido
        FROM pedido_web p
        LEFT JOIN factura f ON p.idpedido = f.idpedido
        LEFT JOIN usuario_web u ON p.idusuario = u.idusuario
        LEFT JOIN empleado e ON p.idempleado = e.idempleado
        WHERE 1=1";

$params = [];
$types = "";

if ($fecha !== 'todas') {
    $fechaFiltro = ($fecha === 'hoy' || empty($fecha)) ? date('Y-m-d') : $fecha;
    $sql .= " AND (DATE(p.fecha) = ? OR p.estado IN ('Recibido', 'En Cocina', 'En Preparacion', 'En Preparación', 'Listo para Despacho', 'Listo'))";
    $params[] = $fechaFiltro;
    $types .= "s";
}

if ($idarqueo) {
    $sql .= " AND p.idarqueo = ?";
    $params[] = $idarqueo;
    $types .= "i";
} elseif ($idempleado) {
    $sql .= " AND p.idempleado = ?";
    $params[] = $idempleado;
    $types .= "i";
}

$sql .= " ORDER BY p.idpedido DESC LIMIT 200";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$res = $stmt->get_result();

$pedidos = [];
$totalRecaudadoHoy = 0.0;

while ($row = $res->fetch_assoc()) {
    $tot = floatval($row['total']);
    $totalRecaudadoHoy += $tot;

    // Obtener items
    $stmtItems = $conn->prepare("SELECT idproducto, descripcion, modificadores, precio_unitario, cantidad, subtotal FROM detalle_pedido_web WHERE idpedido = ?");
    $stmtItems->bind_param("i", $row['idpedido']);
    $stmtItems->execute();
    $resItems = $stmtItems->get_result();
    $items = [];
    while ($it = $resItems->fetch_assoc()) {
        $items[] = [
            'idproducto' => $it['idproducto'],
            'descripcion' => $it['descripcion'],
            'modificadores' => $it['modificadores'],
            'precio_unitario' => floatval($it['precio_unitario']),
            'cantidad' => intval($it['cantidad']),
            'subtotal' => floatval($it['subtotal'])
        ];
    }

    $cajeroTxt = !empty($row['empleado_nombre']) ? ($row['empleado_nombre'] . ' ' . $row['empleado_apellido']) : 'Cajero Central';
    $clienteTxt = !empty($row['usuario_nombre']) ? ($row['usuario_nombre'] . ' ' . $row['usuario_apellido']) : 'Consumidor Final';

    $pedidos[] = [
        'idpedido' => intval($row['idpedido']),
        'idfactura' => $row['idfactura'] ? intval($row['idfactura']) : null,
        'numero_factura' => $row['numero_factura'] ?? ('FAC-B-0001-' . str_pad($row['idpedido'], 8, '0', STR_PAD_LEFT)),
        'tipo_comprobante' => $row['tipo_comprobante'] ?? 'Factura B',
        'cae' => $row['cae'] ?? '74123456789012',
        'cae_vto' => $row['cae_vto'] ? date('d/m/Y', strtotime($row['cae_vto'])) : date('d/m/Y', strtotime('+10 days')),
        'fecha' => date('d/m/Y H:i:s', strtotime($row['fecha'])),
        'hora' => date('H:i', strtotime($row['fecha'])),
        'tipo_entrega' => $row['tipo_entrega'],
        'numero_mesa' => $row['numero_mesa'] ? intval($row['numero_mesa']) : null,
        'metodo_pago' => $row['metodo_pago'],
        'subtotal' => floatval($row['subtotal']),
        'descuento' => floatval($row['descuento']),
        'neto_gravado' => floatval($row['neto_gravado'] ?? ($tot / 1.21)),
        'iva_21' => floatval($row['iva_21'] ?? ($tot - ($tot / 1.21))),
        'total' => $tot,
        'total_formateado' => '$' . number_format($tot, 0, ',', '.'),
        'paga_con' => floatval($row['paga_con'] ?? $tot),
        'vuelto' => floatval($row['vuelto'] ?? 0),
        'estado' => $row['estado'],
        'cajero' => $cajeroTxt,
        'cliente' => $clienteTxt,
        'items' => $items
    ];
}

echo json_encode([
    'ok' => true,
    'fecha' => $fecha,
    'total_pedidos' => count($pedidos),
    'total_recaudado' => $totalRecaudadoHoy,
    'total_recaudado_formateado' => '$' . number_format($totalRecaudadoHoy, 0, ',', '.'),
    'pedidos' => $pedidos
]);
