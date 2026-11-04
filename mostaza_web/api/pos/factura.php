<?php
// ============================================================
// api/pos/factura.php
// Consulta el detalle fiscal completo de una factura por id o idpedido.
// ============================================================
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once(__DIR__ . '/../../conexion.php');

$idFactura = !empty($_GET['idfactura']) ? intval($_GET['idfactura']) : null;
$idPedido = !empty($_GET['idpedido']) ? intval($_GET['idpedido']) : null;

if (!$idFactura && !$idPedido) {
    echo json_encode(['ok' => false, 'error' => 'Se requiere idfactura o idpedido.']);
    exit();
}

$sql = "SELECT f.*, p.tipo_entrega, p.numero_mesa,
               e.nombre AS empleado_nombre, e.apellido AS empleado_apellido,
               u.nombre AS usuario_nombre, u.apellido AS usuario_apellido
        FROM factura f
        JOIN pedido_web p ON f.idpedido = p.idpedido
        LEFT JOIN empleado e ON f.idempleado = e.idempleado
        LEFT JOIN usuario_web u ON f.idusuario = u.idusuario
        WHERE " . ($idFactura ? "f.idfactura = ?" : "f.idpedido = ?") . " LIMIT 1";

$stmt = $conn->prepare($sql);
$paramVal = $idFactura ? $idFactura : $idPedido;
$stmt->bind_param("i", $paramVal);
$stmt->execute();
$res = $stmt->get_result();
$fac = $res->fetch_assoc();

if (!$fac) {
    echo json_encode(['ok' => false, 'error' => 'Factura no encontrada.']);
    exit();
}

// Obtener items de detalle_factura
$stmtItems = $conn->prepare("SELECT * FROM detalle_factura WHERE idfactura = ?");
$stmtItems->bind_param("i", $fac['idfactura']);
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
        'subtotal' => floatval($it['subtotal']),
        'subtotal_formateado' => '$' . number_format($it['subtotal'], 0, ',', '.')
    ];
}

$cajeroTxt = !empty($fac['empleado_nombre']) ? ($fac['empleado_nombre'] . ' ' . $fac['empleado_apellido']) : 'Cajero Central';

echo json_encode([
    'ok' => true,
    'factura' => [
        'idfactura' => intval($fac['idfactura']),
        'idpedido' => intval($fac['idpedido']),
        'numero_factura' => $fac['numero_factura'],
        'tipo_comprobante' => $fac['tipo_comprobante'],
        'cae' => $fac['cae'],
        'cae_vto' => date('d/m/Y', strtotime($fac['cae_vto'])),
        'fecha_emision' => date('d/m/Y H:i:s', strtotime($fac['fecha_emision'])),
        'nombre_cliente' => $fac['nombre_cliente'],
        'cuit_dni_cliente' => $fac['cuit_dni_cliente'],
        'cuit_emisor' => $fac['cuit_emisor'],
        'razon_social_emisor' => $fac['razon_social_emisor'],
        'metodo_pago' => $fac['metodo_pago'],
        'tipo_entrega' => $fac['tipo_entrega'],
        'numero_mesa' => $fac['numero_mesa'],
        'cajero' => $cajeroTxt,
        'subtotal' => floatval($fac['subtotal']),
        'subtotal_formateado' => '$' . number_format($fac['subtotal'], 0, ',', '.'),
        'descuento' => floatval($fac['descuento']),
        'descuento_formateado' => '$' . number_format($fac['descuento'], 0, ',', '.'),
        'neto_gravado' => floatval($fac['neto_gravado']),
        'neto_gravado_formateado' => '$' . number_format($fac['neto_gravado'], 2, ',', '.'),
        'iva_21' => floatval($fac['iva_21']),
        'iva_21_formateado' => '$' . number_format($fac['iva_21'], 2, ',', '.'),
        'total' => floatval($fac['total']),
        'total_formateado' => '$' . number_format($fac['total'], 0, ',', '.'),
        'paga_con' => floatval($fac['paga_con']),
        'vuelto' => floatval($fac['vuelto']),
        'vuelto_formateado' => '$' . number_format($fac['vuelto'], 0, ',', '.'),
        'items' => $items
    ]
]);
