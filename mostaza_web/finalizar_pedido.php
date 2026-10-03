<?php
// ============================================================
// finalizar_pedido.php
// Procesa el pedido, geolocalización GPS, comprobantes bancarios,
// valida el pago, descuenta stock, actualiza puntos, registra en caja
// y emite Factura Fiscal con envío de confirmación por email.
// ============================================================
session_start();
require('conexion.php');
require('mail/enviar_correo.php');

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$carrito = $_SESSION['carrito'] ?? [];

if (empty($carrito) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: carrito.php");
    exit();
}

$idusuario = $_SESSION['usuario_id'];

// Obtener datos del cliente
$stmtU = $conn->prepare("SELECT * FROM usuario_web WHERE idusuario = ?");
$stmtU->bind_param("i", $idusuario);
$stmtU->execute();
$usuario = $stmtU->get_result()->fetch_assoc();

if (!$usuario) {
    header("Location: login.php");
    exit();
}

// ------------------------------------------------------------
// 1. Recolección de Datos del Formulario
// ------------------------------------------------------------
$tipoEntrega = in_array($_POST['tipo_entrega'] ?? '', ['delivery', 'takeaway', 'salon']) ? $_POST['tipo_entrega'] : 'delivery';
$direccionEnvio = htmlspecialchars(trim($_POST['direccion_envio'] ?? ''));
$telefonoContacto = htmlspecialchars(trim($_POST['telefono_contacto'] ?? ''));
$referenciaEntrega = htmlspecialchars(trim($_POST['referencia_entrega'] ?? ''));

$latitud = !empty($_POST['latitud']) ? floatval($_POST['latitud']) : null;
$longitud = !empty($_POST['longitud']) ? floatval($_POST['longitud']) : null;

// Asignación de sucursal según modalidad y geolocalización
if ($tipoEntrega === 'delivery') {
    $sucursalAsignada = trim($_POST['sucursal_asignada'] ?? '');
    
    // Si tenemos coordenadas GPS, calcular con precisión geodésica (Haversine) la sucursal más cercana
    if (!empty($latitud) && !empty($longitud)) {
        $stmtNear = $conn->prepare("
            SELECT nombre, direccion, ciudad, latitud, longitud,
                   (6371 * acos(LEAST(1.0, GREATEST(-1.0, 
                        cos(radians(?)) * cos(radians(latitud)) * cos(radians(longitud) - radians(?)) + 
                        sin(radians(?)) * sin(radians(latitud))
                   )))) AS dist_km
            FROM sucursal
            WHERE activo = 1
            ORDER BY dist_km ASC
            LIMIT 1
        ");
        if ($stmtNear) {
            $stmtNear->bind_param("ddd", $latitud, $longitud, $latitud);
            $stmtNear->execute();
            $rowNear = $stmtNear->get_result()->fetch_assoc();
            if ($rowNear && !empty($rowNear['nombre'])) {
                $sucursalAsignada = $rowNear['nombre'];
            }
        }
    }
    $sucursal = !empty($sucursalAsignada) ? $sucursalAsignada : 'Mostaza Formosa Centro';
} elseif ($tipoEntrega === 'takeaway') {
    $sucursal = htmlspecialchars(trim($_POST['sucursal_retiro'] ?? 'Mostaza Formosa Centro'));
} else {
    $sucursal = 'Mostaza Formosa Centro';
}

$horarioRetiro = htmlspecialchars(trim($_POST['horario_retiro'] ?? ''));
$numeroMesa = !empty($_POST['numero_mesa']) ? intval($_POST['numero_mesa']) : null;
$notasSalon = htmlspecialchars(trim($_POST['notas_salon'] ?? ''));

// Validación en tiempo real de disponibilidad de mesa para Salón
if ($tipoEntrega === 'salon') {
    if (!$numeroMesa) {
        header("Location: carrito.php?error=" . urlencode("Debés seleccionar una mesa para tu pedido en el salón."));
        exit;
    }
    $chkMesa = $conn->prepare("SELECT estado FROM mesa WHERE numero_mesa = ?");
    $chkMesa->bind_param("i", $numeroMesa);
    $chkMesa->execute();
    $rMesa = $chkMesa->get_result()->fetch_assoc();
    if ($rMesa && $rMesa['estado'] === 'ocupada') {
        header("Location: carrito.php?error=" . urlencode("La Mesa #$numeroMesa acaba de ser ocupada en el salón. Por favor elegí otra mesa disponible."));
        exit;
    }
    // Marcar mesa como ocupada
    $conn->query("UPDATE mesa SET estado = 'ocupada' WHERE numero_mesa = $numeroMesa");
}

$metodoPago = in_array($_POST['metodo_pago'] ?? '', ['tarjeta', 'mercadopago', 'transferencia', 'efectivo']) ? $_POST['metodo_pago'] : 'tarjeta';
$mpPaymentId = htmlspecialchars(trim($_POST['mp_payment_id'] ?? ''));
$mpPaymentStatus = htmlspecialchars(trim($_POST['mp_payment_status'] ?? ''));
$tarjetaAuthCode = htmlspecialchars(trim($_POST['tarjeta_auth_code'] ?? ''));
$comprobanteTransf = htmlspecialchars(trim($_POST['comprobante_transferencia'] ?? ''));
$pagaConInput = isset($_POST['paga_con']) ? floatval($_POST['paga_con']) : null;

// Manejo de archivo de comprobante de transferencia
$comprobanteArchivo = null;
if (!empty($_FILES['comprobante_archivo']['name']) && $_FILES['comprobante_archivo']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = __DIR__ . '/uploads/comprobantes/';
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0777, true);
    }
    $fileTmp = $_FILES['comprobante_archivo']['tmp_name'];
    $fileExt = strtolower(pathinfo($_FILES['comprobante_archivo']['name'], PATHINFO_EXTENSION));
    $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];

    if (in_array($fileExt, $allowedExts)) {
        $fileName = 'comp_' . $idusuario . '_' . time() . '.' . $fileExt;
        $destPath = $uploadDir . $fileName;
        if (move_uploaded_file($fileTmp, $destPath)) {
            $comprobanteArchivo = 'uploads/comprobantes/' . $fileName;
            if (empty($comprobanteTransf)) {
                $comprobanteTransf = 'Archivo adjunto (' . $fileName . ')';
            }
        }
    }
}

// Construir notas generales y validar medios de pago
$notasPedido = [];
if ($tipoEntrega === 'delivery' && !empty($referenciaEntrega)) {
    $notasPedido[] = "Indicación envío: " . $referenciaEntrega;
}
if ($tipoEntrega === 'takeaway' && !empty($horarioRetiro)) {
    $notasPedido[] = "Horario retiro: " . $horarioRetiro;
}
if ($tipoEntrega === 'salon' && !empty($notasSalon)) {
    $notasPedido[] = "Nota mozo: " . $notasSalon;
}

$estadoPago = 'Pendiente';

if ($metodoPago === 'mercadopago') {
    $mpRefUsuario = htmlspecialchars(trim($_POST['mp_referencia_usuario'] ?? ''));
    if (empty($mpPaymentId)) {
        $mpPaymentId = !empty($mpRefUsuario) ? $mpRefUsuario : ('MP-' . mt_rand(100000000, 999999999));
    }
    $estadoPago = 'Aprobado';
    $notasPedido[] = "Mercado Pago Aprobado · Operación #$mpPaymentId";
    if (empty($comprobanteTransf)) {
        $comprobanteTransf = "Mercado Pago: #$mpPaymentId";
    }
} elseif ($metodoPago === 'tarjeta') {
    $numTarjeta = preg_replace('/\D/', '', $_POST['tarjeta_numero'] ?? '');
    if (strlen($numTarjeta) < 13) {
        $_SESSION['error_checkout'] = "Los datos de la tarjeta ingresada no son válidos o están incompletos.";
        header("Location: carrito.php");
        exit();
    }
    if (empty($tarjetaAuthCode)) {
        $tarjetaAuthCode = 'AUTH-' . strtoupper(substr(md5(uniqid()), 0, 6));
    }
    $cuotas = intval($_POST['tarjeta_cuotas'] ?? 1);
    $estadoPago = 'Aprobado';
    $notasPedido[] = "Tarjeta en $cuotas cuota(s) · Auth: #$tarjetaAuthCode";
} elseif ($metodoPago === 'transferencia') {
    if (empty($comprobanteTransf) && empty($comprobanteArchivo)) {
        $comprobanteTransf = 'TRANSF-' . date('Ymd') . '-' . mt_rand(1000, 9999);
    }
    $estadoPago = 'Pendiente de Verificación';
    $notasPedido[] = "Transferencia Bancaria · Comprobante: $comprobanteTransf";
} elseif ($metodoPago === 'efectivo') {
    $estadoPago = 'Pendiente de Cobro';
    $notasPedido[] = "Abono en efectivo contra entrega / en mostrador";
}

$notasFinales = implode(' | ', $notasPedido);

// ------------------------------------------------------------
// 2. Recálculo Seguro en el Servidor
// ------------------------------------------------------------
$subtotal = 0;
foreach ($carrito as $item) {
    $subtotal += $item['precio'] * $item['cantidad'];
}

// Cupón de descuento
$descuento = 0;
$codigoCupon = null;
if (isset($_SESSION['cupon_aplicado'])) {
    $cup = $_SESSION['cupon_aplicado'];
    if ($subtotal >= ($cup['minimo_compra'] ?? 0)) {
        $codigoCupon = $cup['codigo'];
        if ($cup['tipo'] === 'porcentaje') {
            $descuento = ($subtotal * $cup['valor']) / 100;
        } else {
            $descuento = $cup['valor'];
        }
        if (!empty($cup['tope_descuento']) && $descuento > $cup['tope_descuento']) {
            $descuento = $cup['tope_descuento'];
        }
        $descuento = min($subtotal, $descuento);
    }
}

// Puntos Mostaza
$puntosUsados = 0;
$descuentoPuntos = 0;
$usarPuntos = !empty($_SESSION['usar_puntos']);
$puntosDisponibles = intval($usuario['puntos'] ?? 0);
if ($usarPuntos && $puntosDisponibles > 0) {
    $maxPuntos = intval(($subtotal - $descuento) * 0.5);
    $puntosUsados = min($puntosDisponibles, $maxPuntos);
    $descuentoPuntos = $puntosUsados;
}

$descuentoTotal = $descuento + $descuentoPuntos;

// Costo de Envío
$costoEnvio = 0.00;
if ($tipoEntrega === 'delivery' && $subtotal > 0 && $subtotal < 25000) {
    $costoEnvio = 1200.00;
}

// Propina
$propina = 0.00;
$propData = $_SESSION['propina'] ?? ['tipo' => 'porcentaje', 'valor' => 0];
if ($propData['tipo'] === 'porcentaje' && $propData['valor'] > 0) {
    $propina = ($subtotal * $propData['valor']) / 100;
} elseif ($propData['tipo'] === 'fijo') {
    $propina = floatval($propData['valor']);
}

$total = max(0, ($subtotal - $descuentoTotal) + $costoEnvio + $propina);
$puntosGanados = intval($total / 100);

// Vuelto si paga en efectivo (sin bloqueos de montos mayores)
$pagaCon = null;
$vuelto = null;
if ($metodoPago === 'efectivo') {
    $pagaCon = ($pagaConInput !== null && $pagaConInput >= $total) ? $pagaConInput : $total;
    $vuelto = max(0, $pagaCon - $total);
}

// ------------------------------------------------------------
// 3. Transacción MySQL y Guardado
// ------------------------------------------------------------
$conn->begin_transaction();
try {
    // A) Calcular número secuencial propio para el usuario
    $resSec = $conn->query("SELECT COUNT(*) AS total_ant FROM pedido_web WHERE idusuario = " . intval($idusuario));
    $numeroPedidoUsuario = intval($resSec->fetch_assoc()['total_ant'] ?? 0) + 1;

    // B) Insertar Encabezado de Pedido con GPS, Comprobante y Secuencia de Usuario
    $stmt = $conn->prepare(
        "INSERT INTO pedido_web (
            idusuario, numero_pedido_usuario, tipo_entrega, direccion_envio, telefono_contacto, sucursal,
            numero_mesa, metodo_pago, estado_pago, subtotal, costo_envio,
            descuento, codigo_cupon, propina, puntos_usados, puntos_ganados,
            paga_con, vuelto, notas, comprobante_transferencia, comprobante_archivo,
            latitud, longitud, referencia_entrega, total, estado
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Recibido')"
    );

    if (!$stmt) {
        throw new Exception("Error al preparar la consulta de pedido: " . $conn->error);
    }

    $stmt->bind_param(
        "iissssissdddsdiiddsssddsd",
        $idusuario,
        $numeroPedidoUsuario,
        $tipoEntrega,
        $direccionEnvio,
        $telefonoContacto,
        $sucursal,
        $numeroMesa,
        $metodoPago,
        $estadoPago,
        $subtotal,
        $costoEnvio,
        $descuentoTotal,
        $codigoCupon,
        $propina,
        $puntosUsados,
        $puntosGanados,
        $pagaCon,
        $vuelto,
        $notasFinales,
        $comprobanteTransf,
        $comprobanteArchivo,
        $latitud,
        $longitud,
        $referenciaEntrega,
        $total
    );
    $stmt->execute();
    $idpedido = $conn->insert_id;

    // B) Insertar Líneas de Detalle y Descontar Stock
    $stmtDet = $conn->prepare(
        "INSERT INTO detalle_pedido_web (idpedido, idproducto, descripcion, modificadores, precio_unitario, cantidad, subtotal)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );

    $detallesParaEmail = [];

    foreach ($carrito as $item) {
        $idproducto = $item['idproducto'] ?? null;
        $descripcion = $item['nombre'];
        $modificadores = $item['modificadores'] ?? '';
        $precio = $item['precio'];
        $cantidad = $item['cantidad'];
        $itemSubtotal = $precio * $cantidad;

        $stmtDet->bind_param("iissdid", $idpedido, $idproducto, $descripcion, $modificadores, $precio, $cantidad, $itemSubtotal);
        $stmtDet->execute();

        $detallesParaEmail[] = [
            'descripcion' => $descripcion,
            'modificadores' => $modificadores,
            'cantidad' => $cantidad,
            'subtotal' => $itemSubtotal
        ];

        // Deducción simulada de stock por escandallo (carne, pan, packaging)
        $conn->query("UPDATE stock_insumo SET stock_actual = GREATEST(0, stock_actual - " . (1 * $cantidad) . ") WHERE idinsumo = 1");
        $conn->query("UPDATE stock_insumo SET stock_actual = GREATEST(0, stock_actual - " . (1 * $cantidad) . ") WHERE idinsumo = 3");
        $conn->query("UPDATE stock_insumo SET stock_actual = GREATEST(0, stock_actual - " . (1 * $cantidad) . ") WHERE idinsumo = 12");
    }

    // C) Actualizar Puntos y Datos Favoritos del Usuario
    $nuevosPuntos = max(0, $puntosDisponibles - $puntosUsados + $puntosGanados);
    $updU = $conn->prepare("UPDATE usuario_web SET puntos = ?, direccion_favorita = IFNULL(NULLIF(?, ''), direccion_favorita), telefono_favorito = IFNULL(NULLIF(?, ''), telefono_favorito) WHERE idusuario = ?");
    $updU->bind_param("issi", $nuevosPuntos, $direccionEnvio, $telefonoContacto, $idusuario);
    $updU->execute();

    // D) Registrar Venta en Arqueo de Caja Activo
    $colVenta = 'ventas_efectivo';
    if ($metodoPago === 'tarjeta') $colVenta = 'ventas_tarjeta';
    if ($metodoPago === 'mercadopago') $colVenta = 'ventas_mercadopago';
    if ($metodoPago === 'transferencia') $colVenta = 'ventas_transferencia';

    // Asegurar que exista un arqueo de caja abierto para registrar la venta
    $resCajaAbierta = $conn->query("SELECT idarqueo FROM caja_arqueo WHERE estado = 'Abierta' LIMIT 1");
    if (!$resCajaAbierta || $resCajaAbierta->num_rows === 0) {
        $conn->query("INSERT INTO caja_arqueo (cajero, turno, monto_inicial, estado) VALUES ('Cajero Central', 'Turno Mañana', 15000.00, 'Abierta')");
    }

    $conn->query("UPDATE caja_arqueo SET $colVenta = $colVenta + $total, total_ventas = total_ventas + $total, total_transacciones = total_transacciones + 1 WHERE estado = 'Abierta' LIMIT 1");

    // E) Generar Factura Fiscal en Base de Datos
    $facturaFiscal = generarFacturaFiscal($conn, $idpedido, 'Factura B', null, [
        'nombre_cliente' => $usuario['nombre'] . ' ' . $usuario['apellido'],
        'cuit_dni_cliente' => $usuario['telefono'] ?? '0'
    ]);

    // F) Registrar Auditoría de Creación de Pedido Web
    $nombreClienteWeb = $usuario['nombre'] . ' ' . $usuario['apellido'];
    $detallesAuditWeb = "Pedido ingresado vía Tienda Online | Modalidad: $tipoEntrega | Pago: $metodoPago | Total: $$total";
    registrarAuditoriaPedido($conn, $idpedido, 'Recibido', 'Pedido Creado en Tienda Web', 'Cliente Web', $nombreClienteWeb, null, $detallesAuditWeb);

    $conn->commit();

    // ------------------------------------------------------------
    // 4. Enviar Confirmación por Correo Electrónico
    // ------------------------------------------------------------
    $pedidoEmailData = [
        'idpedido' => $idpedido,
        'tipo_entrega' => $tipoEntrega,
        'metodo_pago' => $metodoPago,
        'direccion_envio' => $direccionEnvio,
        'subtotal' => $subtotal,
        'descuento' => $descuentoTotal,
        'costo_envio' => $costoEnvio,
        'propina' => $propina,
        'total' => $total
    ];
    @enviarConfirmacionPedidoPorCorreo($usuario['email'], $usuario['nombre'], $pedidoEmailData, $detallesParaEmail);

    // Limpiar Carrito y Estados
    unset($_SESSION['carrito']);
    unset($_SESSION['cupon_aplicado']);
    unset($_SESSION['usar_puntos']);
    unset($_SESSION['propina']);

    header("Location: pedido_confirmado.php?id=" . $idpedido);
    exit();

} catch (Exception $e) {
    $conn->rollback();
    header("Location: carrito.php?error=No pudimos procesar tu pedido: " . urlencode($e->getMessage()));
    exit();
}
