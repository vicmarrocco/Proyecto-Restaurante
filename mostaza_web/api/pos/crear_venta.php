<?php
// ============================================================
// api/pos/crear_venta.php
// Procesa y cobra una venta desde la Terminal de Cajero POS (Caja 1 o Caja 2),
// genera la Factura Fiscal en BD y actualiza el Arqueo de la Caja respectiva.
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

$numeroCaja = intval($input['numero_caja'] ?? ($_SESSION['numero_caja'] ?? 1));
if ($numeroCaja <= 0) $numeroCaja = 1;
$terminalNombre = $input['terminal_nombre'] ?? ($numeroCaja == 1 ? 'Caja 1 - Principal / Mostrador' : 'Caja 2 - Express / Salón');

$idCajero = !empty($input['idempleado_cajero']) ? intval($input['idempleado_cajero']) : (!empty($input['cajero_id']) ? intval($input['cajero_id']) : ($_SESSION['cajero_id'] ?? 1));
$nombreCajero = htmlspecialchars(trim($input['cajero_nombre'] ?? ($_SESSION['cajero_nombre'] ?? 'Cajero Mostaza')));

$idMesero = !empty($input['idempleado_mesero']) ? intval($input['idempleado_mesero']) : (!empty($input['idempleado']) ? intval($input['idempleado']) : ($_SESSION['mesero_id'] ?? null));
$nombreMesero = !empty($input['mesero_nombre']) ? htmlspecialchars(trim($input['mesero_nombre'])) : ($_SESSION['mesero_nombre'] ?? null);

$tipoEntrega = in_array($input['tipo_entrega'] ?? '', ['salon', 'takeaway', 'delivery']) ? $input['tipo_entrega'] : 'takeaway';
$numeroMesa = !empty($input['numero_mesa']) ? intval($input['numero_mesa']) : null;
$metodoPago = in_array($input['metodo_pago'] ?? '', ['efectivo', 'tarjeta', 'mercadopago', 'transferencia']) ? $input['metodo_pago'] : 'efectivo';
$idUsuario = !empty($input['idusuario']) ? intval($input['idusuario']) : null;
$nombreCliente = htmlspecialchars(trim($input['nombre_cliente'] ?? 'Consumidor Final'));
$cuitDniCliente = htmlspecialchars(trim($input['cuit_dni_cliente'] ?? '0'));
$tipoComprobante = in_array($input['tipo_comprobante'] ?? '', ['Factura B', 'Factura A', 'Ticket Fiscal']) ? $input['tipo_comprobante'] : 'Factura B';
$notas = htmlspecialchars(trim($input['notas'] ?? "Venta generada en Terminal $terminalNombre"));

// Calcular Totales
$subtotal = 0;
$itemsValidados = [];
foreach ($items as $it) {
    $cant = max(1, intval($it['cantidad'] ?? 1));
    $precio = floatval($it['precio'] ?? ($it['precio_unitario'] ?? 0));
    $itemTot = $precio * $cant;
    $subtotal += $itemTot;
    $itemsValidados[] = [
        'idproducto' => !empty($it['idproducto']) ? intval($it['idproducto']) : null,
        'descripcion' => $it['nombre'] ?? ($it['descripcion'] ?? 'Producto Mostaza'),
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

// Captura de comprobantes y referencias específicas por medio de pago
$mpPaymentId = !empty($input['mp_payment_id']) ? htmlspecialchars(trim($input['mp_payment_id'])) : null;
$tarjetaAuthCode = !empty($input['tarjeta_auth_code']) ? htmlspecialchars(trim($input['tarjeta_auth_code'])) : null;
$tarjetaCuotas = intval($input['tarjeta_cuotas'] ?? 1);
$tarjetaNumero = !empty($input['tarjeta_numero']) ? htmlspecialchars(trim($input['tarjeta_numero'])) : null;
$tarjetaNombre = !empty($input['tarjeta_nombre']) ? htmlspecialchars(trim($input['tarjeta_nombre'])) : null;
$tarjetaDni = !empty($input['tarjeta_dni']) ? htmlspecialchars(trim($input['tarjeta_dni'])) : null;
$comprobanteTransf = !empty($input['comprobante_transferencia']) ? htmlspecialchars(trim($input['comprobante_transferencia'])) : null;

$tarjetaLast4 = null;
$notasArr = [$notas];
if ($metodoPago === 'mercadopago') {
    if (empty($mpPaymentId)) $mpPaymentId = 'MP-' . mt_rand(100000000, 999999999);
    $notasArr[] = "Mercado Pago Aprobado · Operación #$mpPaymentId";
    if (empty($comprobanteTransf)) $comprobanteTransf = "Mercado Pago: #$mpPaymentId";
} elseif ($metodoPago === 'tarjeta') {
    if (empty($tarjetaAuthCode)) $tarjetaAuthCode = 'AUTH-POS-' . mt_rand(100000, 999999);
    $tarjetaLast4 = $tarjetaNumero ? substr(preg_replace('/\D/', '', $tarjetaNumero), -4) : 'XXXX';
    $titularNota = $tarjetaNombre ? " · $tarjetaNombre" : "";
    $notasArr[] = "Tarjeta (terminada en $tarjetaLast4$titularNota) en $tarjetaCuotas cuota(s) · Auth: #$tarjetaAuthCode";
    if ((empty($nombreCliente) || $nombreCliente === 'Consumidor Final') && !empty($tarjetaNombre)) {
        $nombreCliente = $tarjetaNombre;
    }
    if ((empty($cuitDniCliente) || $cuitDniCliente === '0') && !empty($tarjetaDni)) {
        $cuitDniCliente = $tarjetaDni;
    }
} elseif ($metodoPago === 'transferencia') {
    if (empty($comprobanteTransf)) $comprobanteTransf = 'TRANSF-' . date('Ymd') . '-' . mt_rand(1000, 9999);
    $notasArr[] = "Transferencia Bancaria · Comprobante: $comprobanteTransf";
}
$notasFinales = implode(' | ', $notasArr);

$conn->begin_transaction();
try {
    // 1. Obtener turno de caja abierto para esta terminal específica
    $idarqueo = null;
    $stmtCaja = $conn->prepare("SELECT idarqueo FROM caja_arqueo WHERE estado = 'Abierta' AND numero_caja = ? ORDER BY idarqueo DESC LIMIT 1");
    $stmtCaja->bind_param("i", $numeroCaja);
    $stmtCaja->execute();
    $resCaja = $stmtCaja->get_result();

    if ($resCaja && $cRow = $resCaja->fetch_assoc()) {
        $idarqueo = intval($cRow['idarqueo']);
    } else {
        // Auto-apertura si aún no se abrió formalmente
        $stmtInsCaja = $conn->prepare("INSERT INTO caja_arqueo (numero_caja, terminal_nombre, idempleado, cajero, turno, monto_inicial, estado) VALUES (?, ?, ?, ?, 'Turno Actual', 15000.00, 'Abierta')");
        $stmtInsCaja->bind_param("isis", $numeroCaja, $terminalNombre, $idCajero, $nombreCajero);
        $stmtInsCaja->execute();
        $idarqueo = $conn->insert_id;
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
            idusuario, numero_pedido_usuario, idempleado, idarqueo, numero_caja, 
            cajero_nombre, idempleado_cajero, mesero_nombre, idempleado_mesero,
            tipo_entrega, direccion_envio, telefono_contacto,
            referencia_entrega, latitud, longitud, numero_mesa, metodo_pago, estado_pago,
            subtotal, descuento, paga_con, vuelto, notas, comprobante_transferencia, total, estado
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Aprobado', ?, ?, ?, ?, ?, ?, ?, 'Recibido')"
    );
    $stmt->bind_param(
        "iiiiisisissssddisddddssd",
        $idUsuario,
        $numeroPedidoUsuario,
        $idCajero,
        $idarqueo,
        $numeroCaja,
        $nombreCajero,
        $idCajero,
        $nombreMesero,
        $idMesero,
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
        $notasFinales,
        $comprobanteTransf,
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

        // Descuento automático de stock de insumos
        $conn->query("UPDATE stock_insumo SET stock_actual = GREATEST(0, stock_actual - " . ($it['cantidad']) . ") WHERE idinsumo IN (1, 3, 11)");
    }

    // 4. Registrar venta en el arqueo de caja activo correspondiente
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

    // 5.5 Registrar Auditoría Estricta de Empleado
    $rolAudit = ($tipoEntrega === 'salon' && $nombreMesero) ? 'Mesero' : 'Cajero';
    $nombreResp = ($tipoEntrega === 'salon' && $nombreMesero) ? $nombreMesero : $nombreCajero;
    $idResp = ($tipoEntrega === 'salon' && $idMesero) ? $idMesero : $idCajero;
    $accionAudit = ($tipoEntrega === 'salon' && $nombreMesero) ? "Comanda de Salón tomada en Mesa #$numeroMesa" : "Venta Registrada en $terminalNombre";
    $detallesAudit = "Terminal: Caja $numeroCaja | Método: $metodoPago | Total: $$total | Modalidad: $tipoEntrega" . ($numeroMesa ? " | Mesa $numeroMesa" : "");
    registrarAuditoriaPedido($conn, $idPedido, 'Recibido', $accionAudit, $rolAudit, $nombreResp, $idResp, $detallesAudit);

    // 6. GENERAR FACTURA FISCAL EN BASE DE DATOS (factura y detalle_factura)
    $facturaFiscal = generarFacturaFiscal($conn, $idPedido, $tipoComprobante, $idCajero, [
        'nombre_cajero' => $nombreCajero,
        'nombre_cliente' => $nombreCliente,
        'cuit_dni_cliente' => $cuitDniCliente,
        'numero_caja' => $numeroCaja
    ]);

    $conn->commit();

    echo json_encode([
        'ok' => true,
        'mensaje' => "¡Venta facturada y comanda registrada en $terminalNombre!",
        'idpedido' => $idPedido,
        'numero_caja' => $numeroCaja,
        'terminal_nombre' => $terminalNombre,
        'numero_factura' => $facturaFiscal['numero_factura'] ?? sprintf("FAC-B-%04d-%08d", $numeroCaja, $idPedido),
        'cae' => $facturaFiscal['cae'] ?? '74123456789012',
        'cae_vto' => $facturaFiscal['cae_vto'] ?? date('d/m/Y', strtotime('+10 days')),
        'total' => $total,
        'paga_con' => $pagaCon,
        'vuelto' => $vuelto,
        'items' => $itemsValidados,
        'metodo_pago' => $metodoPago,
        'mp_payment_id' => $mpPaymentId,
        'fecha' => date('d/m/Y H:i:s')
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['ok' => false, 'error' => 'Error al registrar venta: ' . $e->getMessage()]);
}
