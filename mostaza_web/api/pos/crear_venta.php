<?php
// ============================================================
// api/pos/crear_venta.php
// Procesa y cobra una venta desde la Terminal de Cajero POS,
// genera la Factura Fiscal en BD y actualiza el Arqueo de Caja.
// ============================================================
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once(__DIR__ . '/../../conexion.php');

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

$items = $input['items'] ?? [];
if (empty($items)) {
    echo json_encode(['ok' => false, 'error' => 'No hay productos en la comanda.']);
    exit();
}

$idCajero = $_SESSION['cajero_id'] ?? intval($input['cajero_id'] ?? 1);
$nombreCajero = htmlspecialchars(trim($input['cajero_nombre'] ?? ($_SESSION['cajero_nombre'] ?? 'Cajero Mostaza')));
$tipoEntrega = in_array($input['tipo_entrega'] ?? '', ['salon', 'takeaway', 'delivery']) ? $input['tipo_entrega'] : 'takeaway';
$numeroMesa = !empty($input['numero_mesa']) ? intval($input['numero_mesa']) : null;
$metodoPago = in_array($input['metodo_pago'] ?? '', ['efectivo', 'tarjeta', 'mercadopago', 'transferencia']) ? $input['metodo_pago'] : 'efectivo';
$idUsuario = !empty($input['idusuario']) ? intval($input['idusuario']) : null;
$nombreCliente = htmlspecialchars(trim($input['nombre_cliente'] ?? 'Consumidor Final'));
$cuitDniCliente = htmlspecialchars(trim($input['cuit_dni_cliente'] ?? '0'));
$tipoComprobante = in_array($input['tipo_comprobante'] ?? '', ['Factura B', 'Factura A', 'Ticket Fiscal']) ? $input['tipo_comprobante'] : 'Factura B';
$notas = htmlspecialchars(trim($input['notas'] ?? 'Venta generada en Terminal Cajero POS'));

// Calcular Totales
$subtotal = 0;
$itemsValidados = [];
foreach ($items as $it) {
    $cant = max(1, intval($it['cantidad'] ?? 1));
    $precio = floatval($it['precio'] ?? 0);
    $itemTot = $precio * $cant;
    $subtotal += $itemTot;
    $itemsValidados[] = [
        'idproducto' => !empty($it['idproducto']) ? intval($it['idproducto']) : null,
        'descripcion' => $it['nombre'] ?? 'Producto Mostaza',
        'modificadores' => $it['modificadores'] ?? '',
        'precio' => $precio,
        'cantidad' => $cant,
        'subtotal' => $itemTot
    ];
}

$descuento = floatval($input['descuento'] ?? 0);
$total = max(0, $subtotal - $descuento);

$pagaCon = floatval($input['paga_con'] ?? $total);
$vuelto = ($metodoPago === 'efectivo') ? max(0, $pagaCon - $total) : 0;

$direccionEnvio = !empty($input['direccion_envio']) ? htmlspecialchars(trim($input['direccion_envio'])) : null;
$telefonoContacto = !empty($input['telefono_contacto']) ? htmlspecialchars(trim($input['telefono_contacto'])) : null;
$referenciaEntrega = !empty($input['referencia_entrega']) ? htmlspecialchars(trim($input['referencia_entrega'])) : null;
$latitud = !empty($input['latitud']) ? floatval($input['latitud']) : null;
$longitud = !empty($input['longitud']) ? floatval($input['longitud']) : null;

$conn->begin_transaction();
try {
    // 1. Obtener turno de caja abierto
    $idarqueo = null;
    $resCaja = $conn->query("SELECT idarqueo FROM caja_arqueo WHERE estado = 'Abierta' ORDER BY idarqueo DESC LIMIT 1");
    if ($resCaja && $cRow = $resCaja->fetch_assoc()) {
        $idarqueo = intval($cRow['idarqueo']);
    }

    // Calcular secuencia del usuario si es cliente registrado
    $numeroPedidoUsuario = null;
    if ($idUsuario) {
        $resSec = $conn->query("SELECT COUNT(*) AS total_ant FROM pedido_web WHERE idusuario = " . intval($idUsuario));
        $numeroPedidoUsuario = intval($resSec->fetch_assoc()['total_ant'] ?? 0) + 1;
    }

    // 2. Insertar Pedido
    $stmt = $conn->prepare(
        "INSERT INTO pedido_web (
            idusuario, numero_pedido_usuario, idempleado, idarqueo, tipo_entrega, direccion_envio, telefono_contacto,
            referencia_entrega, latitud, longitud, numero_mesa, metodo_pago, estado_pago,
            subtotal, descuento, paga_con, vuelto, notas, total, estado
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Aprobado', ?, ?, ?, ?, ?, ?, 'Recibido')"
    );
    $stmt->bind_param(
        "iiiissssddisddddsd",
        $idUsuario,
        $numeroPedidoUsuario,
        $idCajero,
        $idarqueo,
        $tipoEntrega,
        $direccionEnvio,
        $telefonoContacto,
        $referenciaEntrega,
        $latitud,
        $longitud,
        $numeroMesa,
        $metodoPago,
        $subtotal,
        $descuento,
        $pagaCon,
        $vuelto,
        $notas,
        $total
    );
    $stmt->execute();
    $idPedido = $conn->insert_id;

    // 3. Insertar Detalles de Pedido y Descontar Stock
    $stmtDet = $conn->prepare(
        "INSERT INTO detalle_pedido_web (idpedido, idproducto, descripcion, modificadores, precio_unitario, cantidad, subtotal)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );

    foreach ($itemsValidados as $it) {
        $stmtDet->bind_param("iissdid", $idPedido, $it['idproducto'], $it['descripcion'], $it['modificadores'], $it['precio'], $it['cantidad'], $it['subtotal']);
        $stmtDet->execute();

        // Descuento automático de stock
        $conn->query("UPDATE stock_insumo SET stock_actual = GREATEST(0, stock_actual - " . ($it['cantidad']) . ") WHERE idinsumo IN (1, 3, 11)");
    }

    // 4. Registrar venta en el arqueo de caja activo
    if ($idarqueo) {
        $colVenta = 'ventas_efectivo';
        if ($metodoPago === 'tarjeta') $colVenta = 'ventas_tarjeta';
        if ($metodoPago === 'mercadopago') $colVenta = 'ventas_mercadopago';
        if ($metodoPago === 'transferencia') $colVenta = 'ventas_transferencia';

        $conn->query("UPDATE caja_arqueo SET $colVenta = $colVenta + $total, total_ventas = total_ventas + $total, total_transacciones = total_transacciones + 1 WHERE idarqueo = $idarqueo");
    }

    // 5. Si el cliente está registrado, sumar puntos
    if ($idUsuario) {
        $puntosSumar = intval($total / 100);
        $conn->query("UPDATE usuario_web SET puntos = puntos + $puntosSumar WHERE idusuario = $idUsuario");
    }

    // 6. GENERAR FACTURA FISCAL EN BASE DE DATOS (factura y detalle_factura)
    $facturaFiscal = generarFacturaFiscal($conn, $idPedido, $tipoComprobante, $idCajero, [
        'nombre_cajero' => $nombreCajero,
        'nombre_cliente' => $nombreCliente,
        'cuit_dni_cliente' => $cuitDniCliente
    ]);

    $conn->commit();

    echo json_encode([
        'ok' => true,
        'mensaje' => '¡Venta facturada y comanda registrada en Base de Datos!',
        'idpedido' => $idPedido,
        'idfactura' => $facturaFiscal['idfactura'],
        'numero_factura' => $facturaFiscal['numero_factura'],
        'tipo_comprobante' => $facturaFiscal['tipo_comprobante'],
        'cae' => $facturaFiscal['cae'],
        'cae_vto' => $facturaFiscal['cae_vto'],
        'subtotal' => $subtotal,
        'subtotal_formateado' => '$' . number_format($subtotal, 0, ',', '.'),
        'descuento' => $descuento,
        'neto_gravado' => $facturaFiscal['neto_gravado'],
        'neto_gravado_formateado' => $facturaFiscal['neto_gravado_formateado'],
        'iva_21' => $facturaFiscal['iva_21'],
        'iva_21_formateado' => $facturaFiscal['iva_21_formateado'],
        'total' => $total,
        'total_formateado' => '$' . number_format($total, 0, ',', '.'),
        'paga_con' => $pagaCon,
        'vuelto' => $vuelto,
        'vuelto_formateado' => '$' . number_format($vuelto, 0, ',', '.'),
        'metodo_pago' => $metodoPago,
        'tipo_entrega' => $tipoEntrega,
        'numero_mesa' => $numeroMesa,
        'cajero' => $nombreCajero,
        'cliente' => $nombreCliente,
        'cuit_dni' => $cuitDniCliente,
        'fecha' => date('d/m/Y H:i:s'),
        'items' => $itemsValidados,
        'factura' => $facturaFiscal
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['ok' => false, 'error' => 'Error al registrar la venta y factura: ' . $e->getMessage()]);
}

