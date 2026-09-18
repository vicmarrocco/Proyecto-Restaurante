<?php
// ============================================================
// api/caja/movimiento.php
// Maneja apertura con fondo inicial por terminal, arqueo físico de billetes,
// sangrías / retiros de seguridad, Cierre X, Cierre Z e Informe Fiscal.
// ============================================================
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once(__DIR__ . '/../../conexion.php');

$rawJson = file_get_contents('php://input');
$input = !empty($rawJson) ? json_decode($rawJson, true) : [];
if (empty($input)) {
    $input = !empty($_POST) ? $_POST : $_GET;
} else {
    $input = array_merge($_GET, $input);
}
$accion = $input['accion'] ?? ($_GET['accion'] ?? '');

// ============================================================
// 1. APERTURA DE CAJA POR TERMINAL
// ============================================================
if ($accion === 'abrir_caja') {
    $numeroCaja = intval($input['numero_caja'] ?? 1);
    $cajero = htmlspecialchars(trim($input['cajero'] ?? ($_SESSION['cajero_nombre'] ?? 'Cajero Mostaza')));
    $turno = htmlspecialchars(trim($input['turno'] ?? 'Turno Mañana'));
    $montoInicial = floatval($input['monto_inicial'] ?? 10000);
    $idEmpleado = !empty($input['idempleado']) ? intval($input['idempleado']) : ($_SESSION['cajero_id'] ?? 1);
    $obsApertura = htmlspecialchars(trim($input['observaciones_apertura'] ?? 'Apertura de turno en terminal POS'));
    $terminalNombre = htmlspecialchars(trim($input['terminal_nombre'] ?? ($numeroCaja == 1 ? 'Caja 1 - Principal / Mostrador' : 'Caja 2 - Express / Salón')));

    // Verificar si esta terminal específica ya tiene una caja abierta
    $checkAbierta = $conn->prepare("SELECT idarqueo, cajero FROM caja_arqueo WHERE estado = 'Abierta' AND numero_caja = ? LIMIT 1");
    $checkAbierta->bind_param("i", $numeroCaja);
    $checkAbierta->execute();
    $resChk = $checkAbierta->get_result();

    if ($resChk && $resChk->num_rows > 0) {
        $existente = $resChk->fetch_assoc();
        echo json_encode([
            'ok' => false,
            'error' => "La terminal $terminalNombre ya tiene un turno abierto (Arqueo #{$existente['idarqueo']} - {$existente['cajero']}). Debés cerrarlo antes de iniciar un nuevo turno."
        ]);
        exit();
    }

    $stmt = $conn->prepare(
        "INSERT INTO caja_arqueo (numero_caja, terminal_nombre, idempleado, cajero, turno, monto_inicial, observaciones_apertura, estado) 
         VALUES (?, ?, ?, ?, ?, ?, ?, 'Abierta')"
    );
    $stmt->bind_param("isisdss", $numeroCaja, $terminalNombre, $idEmpleado, $cajero, $turno, $montoInicial, $obsApertura);
    
    if ($stmt->execute()) {
        $idarqueo = $conn->insert_id;
        echo json_encode([
            'ok' => true,
            'mensaje' => "¡Turno de $terminalNombre abierto con éxito!",
            'idarqueo' => $idarqueo,
            'numero_caja' => $numeroCaja,
            'terminal_nombre' => $terminalNombre,
            'turno' => $turno,
            'cajero' => $cajero,
            'monto_inicial' => $montoInicial,
            'monto_inicial_formateado' => '$' . number_format($montoInicial, 0, ',', '.'),
            'fecha_apertura' => date('d/m/Y H:i:s')
        ]);
    } else {
        echo json_encode(['ok' => false, 'error' => 'Error al abrir la caja en BD: ' . $conn->error]);
    }
    exit();
}

// ============================================================
// 2. RETIRO DE EFECTIVO / SANGRÍA DE SEGURIDAD A TESORERÍA
// ============================================================
elseif ($accion === 'retiro_efectivo' || $accion === 'sangria') {
    $idarqueo = intval($input['idarqueo'] ?? 0);
    $numeroCaja = intval($input['numero_caja'] ?? 1);
    $monto = floatval($input['monto'] ?? 0);
    $motivo = htmlspecialchars(trim($input['motivo'] ?? 'Retiro de seguridad / Excedente a caja fuerte'));
    $cajero = htmlspecialchars(trim($input['cajero'] ?? ($_SESSION['cajero_nombre'] ?? 'Cajero Mostaza')));
    $idEmpleado = !empty($input['idempleado']) ? intval($input['idempleado']) : ($_SESSION['cajero_id'] ?? null);

    if ($monto <= 0) {
        echo json_encode(['ok' => false, 'error' => 'El monto a retirar debe ser mayor a $0.']);
        exit();
    }

    if (!$idarqueo) {
        $rAct = $conn->prepare("SELECT idarqueo FROM caja_arqueo WHERE estado = 'Abierta' AND numero_caja = ? ORDER BY idarqueo DESC LIMIT 1");
        $rAct->bind_param("i", $numeroCaja);
        $rAct->execute();
        $rowA = $rAct->get_result()->fetch_assoc();
        if ($rowA) $idarqueo = intval($rowA['idarqueo']);
    }

    if (!$idarqueo) {
        echo json_encode(['ok' => false, 'error' => 'No hay una caja abierta para registrar el retiro.']);
        exit();
    }

    $stmtRet = $conn->prepare("INSERT INTO caja_retiro (idarqueo, numero_caja, idempleado, cajero, monto, motivo) VALUES (?, ?, ?, ?, ?, ?)");
    $stmtRet->bind_param("iiisds", $idarqueo, $numeroCaja, $idEmpleado, $cajero, $monto, $motivo);
    $stmtRet->execute();
    $idRetiro = $stmtRet->insert_id;

    $conn->query("UPDATE caja_arqueo SET monto_retiros = monto_retiros + $monto WHERE idarqueo = $idarqueo");

    echo json_encode([
        'ok' => true,
        'mensaje' => "Retiro de seguridad por $" . number_format($monto, 0, ',', '.') . " registrado con éxito.",
        'idretiro' => $idRetiro,
        'idarqueo' => $idarqueo,
        'monto_retirado' => $monto,
        'fecha_hora' => date('d/m/Y H:i:s')
    ]);
    exit();
}

// ============================================================
// 3. CIERRE X: ARQUEO PARCIAL / CIEGO EN VIVO (SIN BLOQUEAR CAJA)
// ============================================================
elseif ($accion === 'cierre_x') {
    $numeroCaja = intval($input['numero_caja'] ?? 1);
    $idarqueo = intval($input['idarqueo'] ?? 0);

    if ($idarqueo > 0) {
        $stmtC = $conn->prepare("SELECT * FROM caja_arqueo WHERE idarqueo = ?");
        $stmtC->bind_param("i", $idarqueo);
    } else {
        $stmtC = $conn->prepare("SELECT * FROM caja_arqueo WHERE estado = 'Abierta' AND numero_caja = ? ORDER BY idarqueo DESC LIMIT 1");
        $stmtC->bind_param("i", $numeroCaja);
    }
    $stmtC->execute();
    $caja = $stmtC->get_result()->fetch_assoc();

    if (!$caja) {
        echo json_encode(['ok' => false, 'error' => "No se encontró caja abierta en Caja $numeroCaja."]);
        exit();
    }

    $montoInicial = floatval($caja['monto_inicial']);
    $ventasEf = floatval($caja['ventas_efectivo']);
    $montoRetiros = floatval($caja['monto_retiros'] ?? 0);
    $efectivoEsperado = max(0, $montoInicial + $ventasEf - $montoRetiros);

    echo json_encode([
        'ok' => true,
        'tipo' => 'Cierre X (Arqueo Parcial en Vivo)',
        'caja' => [
            'idarqueo' => intval($caja['idarqueo']),
            'numero_caja' => intval($caja['numero_caja'] ?? 1),
            'terminal_nombre' => $caja['terminal_nombre'] ?: ('Caja ' . ($caja['numero_caja'] ?? 1)),
            'cajero' => $caja['cajero'],
            'turno' => $caja['turno'],
            'fecha_apertura' => date('d/m/Y H:i:s', strtotime($caja['fecha_apertura'])),
            'fecha_emision_x' => date('d/m/Y H:i:s'),
            'monto_inicial' => $montoInicial,
            'ventas_efectivo' => $ventasEf,
            'ventas_tarjeta' => floatval($caja['ventas_tarjeta']),
            'ventas_mercadopago' => floatval($caja['ventas_mercadopago']),
            'ventas_transferencia' => floatval($caja['ventas_transferencia']),
            'monto_retiros' => $montoRetiros,
            'total_ventas' => floatval($caja['total_ventas']),
            'total_transacciones' => intval($caja['total_transacciones'] ?? 0),
            'efectivo_esperado_gaveta' => $efectivoEsperado
        ]
    ]);
    exit();
}

// ============================================================
// 4. CIERRE Z: CIERRE FISCAL DEFINITIVO Y ARQUEO FINAL
// ============================================================
elseif ($accion === 'cerrar_caja' || $accion === 'cierre_z') {
    $idarqueo = intval($input['idarqueo'] ?? 0);
    $numeroCaja = intval($input['numero_caja'] ?? 0);

    if (!$idarqueo) {
        $sqlA = "SELECT idarqueo FROM caja_arqueo WHERE estado = 'Abierta'";
        if ($numeroCaja > 0) $sqlA .= " AND numero_caja = $numeroCaja";
        $sqlA .= " ORDER BY idarqueo DESC LIMIT 1";
        $rAct = $conn->query($sqlA);
        if ($rAct && $rowA = $rAct->fetch_assoc()) {
            $idarqueo = intval($rowA['idarqueo']);
        }
    }

    $stmtCaja = $conn->prepare("SELECT * FROM caja_arqueo WHERE idarqueo = ?");
    $stmtCaja->bind_param("i", $idarqueo);
    $stmtCaja->execute();
    $caja = $stmtCaja->get_result()->fetch_assoc();

    if (!$caja) {
        echo json_encode(['ok' => false, 'error' => 'No se encontró el registro del turno de caja a cerrar.']);
        exit();
    }

    $montoInicial = floatval($caja['monto_inicial']);
    $ventasEfectivo = floatval($caja['ventas_efectivo']);
    $montoRetiros = floatval($caja['monto_retiros'] ?? 0);
    $totalEsperadoSistema = max(0, $montoInicial + $ventasEfectivo - $montoRetiros);
    
    $montoFisico = floatval($input['monto_fisico'] ?? $totalEsperadoSistema);
    $diferencia = $montoFisico - $totalEsperadoSistema;
    $fechaCierre = date('Y-m-d H:i:s');
    $obsCierre = htmlspecialchars(trim($input['observaciones_cierre'] ?? 'Cierre de turno y arqueo Z regular'));

    $stmtUpd = $conn->prepare(
        "UPDATE caja_arqueo 
         SET monto_fisico_cierre = ?, monto_final_sistema = ?, diferencia = ?, 
             observaciones_cierre = ?, estado = 'Cerrada', fecha_cierre = ? 
         WHERE idarqueo = ?"
    );
    $stmtUpd->bind_param("dddssi", $montoFisico, $totalEsperadoSistema, $diferencia, $obsCierre, $fechaCierre, $idarqueo);

    if ($stmtUpd->execute()) {
        $diffTexto = "Cuadre Exacto ($0)";
        if ($diferencia > 0) $diffTexto = "+$" . number_format($diferencia, 0, ',', '.') . " (Sobrante)";
        elseif ($diferencia < 0) $diffTexto = "-$" . number_format(abs($diferencia), 0, ',', '.') . " (Faltante)";

        // Construir objeto formal de Informe de Cierre Z
        $informeZ = [
            'tipo_comprobante' => 'INFORME DE CIERRE DE CAJA (CIERRE Z)',
            'idarqueo' => $idarqueo,
            'numero_caja' => intval($caja['numero_caja'] ?? 1),
            'terminal_nombre' => $caja['terminal_nombre'] ?: ('Caja ' . ($caja['numero_caja'] ?? 1)),
            'pv_afip' => sprintf("%04d", intval($caja['numero_caja'] ?? 1)),
            'cajero' => $caja['cajero'],
            'idempleado' => $caja['idempleado'],
            'turno' => $caja['turno'],
            'fecha_apertura' => date('d/m/Y H:i:s', strtotime($caja['fecha_apertura'])),
            'fecha_cierre' => date('d/m/Y H:i:s', strtotime($fechaCierre)),
            'monto_inicial' => $montoInicial,
            'ventas_efectivo' => $ventasEfectivo,
            'ventas_tarjeta' => floatval($caja['ventas_tarjeta']),
            'ventas_mercadopago' => floatval($caja['ventas_mercadopago']),
            'ventas_transferencia' => floatval($caja['ventas_transferencia']),
            'monto_retiros' => $montoRetiros,
            'total_ventas' => floatval($caja['total_ventas']),
            'total_transacciones' => intval($caja['total_transacciones'] ?? 0),
            'efectivo_esperado' => $totalEsperadoSistema,
            'conteo_fisico_declarado' => $montoFisico,
            'diferencia' => $diferencia,
            'diferencia_texto' => $diffTexto,
            'observaciones' => $obsCierre,
            'firma_digital' => 'Z-' . $idarqueo . '-' . date('YmdHi') . '-' . substr(md5($idarqueo . $fechaCierre), 0, 8)
        ];

        echo json_encode([
            'ok' => true,
            'mensaje' => "¡Caja cerrada exitosamente! Cuadre: $diffTexto",
            'idarqueo' => $idarqueo,
            'numero_caja' => intval($caja['numero_caja'] ?? 1),
            'terminal_nombre' => $informeZ['terminal_nombre'],
            'total_esperado' => $totalEsperadoSistema,
            'monto_fisico' => $montoFisico,
            'diferencia' => $diferencia,
            'diferencia_texto' => $diffTexto,
            'fecha_cierre' => date('d/m/Y H:i:s'),
            'informe_cierre' => $informeZ
        ]);
    } else {
        echo json_encode(['ok' => false, 'error' => 'Error al cerrar el turno en BD: ' . $conn->error]);
    }
    exit();
}

// ============================================================
// 5. OBTENER INFORME DE CIERRE HISTÓRICO PARA IMPRESIÓN
// ============================================================
elseif ($accion === 'informe_cierre') {
    $idarqueo = intval($input['idarqueo'] ?? 0);
    if (!$idarqueo) {
        echo json_encode(['ok' => false, 'error' => 'ID de arqueo requerido.']);
        exit();
    }

    $stmtCaja = $conn->prepare("SELECT * FROM caja_arqueo WHERE idarqueo = ?");
    $stmtCaja->bind_param("i", $idarqueo);
    $stmtCaja->execute();
    $caja = $stmtCaja->get_result()->fetch_assoc();

    if (!$caja) {
        echo json_encode(['ok' => false, 'error' => 'Arqueo no encontrado.']);
        exit();
    }

    $montoInicial = floatval($caja['monto_inicial']);
    $ventasEfectivo = floatval($caja['ventas_efectivo']);
    $montoRetiros = floatval($caja['monto_retiros'] ?? 0);
    $totalEsperado = max(0, $montoInicial + $ventasEfectivo - $montoRetiros);
    $montoFisico = floatval($caja['monto_fisico_cierre'] ?? $totalEsperado);
    $diferencia = floatval($caja['diferencia'] ?? 0);

    $diffTexto = "Cuadre Exacto ($0)";
    if ($diferencia > 0) $diffTexto = "+$" . number_format($diferencia, 0, ',', '.') . " (Sobrante)";
    elseif ($diferencia < 0) $diffTexto = "-$" . number_format(abs($diferencia), 0, ',', '.') . " (Faltante)";

    // Retiros asociados a este turno
    $retirosList = [];
    $resR = $conn->query("SELECT * FROM caja_retiro WHERE idarqueo = $idarqueo ORDER BY idretiro ASC");
    if ($resR) {
        while ($rwR = $resR->fetch_assoc()) {
            $retirosList[] = [
                'idretiro' => intval($rwR['idretiro']),
                'monto' => floatval($rwR['monto']),
                'motivo' => $rwR['motivo'],
                'fecha_hora' => date('d/m/Y H:i', strtotime($rwR['fecha_hora']))
            ];
        }
    }

    $informeObj = [
        'tipo_comprobante' => 'INFORME DE CIERRE DE CAJA (CIERRE Z)',
        'idarqueo' => $idarqueo,
        'numero_caja' => intval($caja['numero_caja'] ?? 1),
        'terminal_nombre' => $caja['terminal_nombre'] ?: ('Caja ' . ($caja['numero_caja'] ?? 1)),
        'pv_afip' => sprintf("%04d", intval($caja['numero_caja'] ?? 1)),
        'cajero' => $caja['cajero'],
        'idempleado' => $caja['idempleado'],
        'turno' => $caja['turno'],
        'estado' => $caja['estado'],
        'fecha_apertura' => date('d/m/Y H:i:s', strtotime($caja['fecha_apertura'])),
        'fecha_cierre' => $caja['fecha_cierre'] ? date('d/m/Y H:i:s', strtotime($caja['fecha_cierre'])) : 'En Curso (Cierre X)',
        'monto_inicial' => $montoInicial,
        'ventas_efectivo' => $ventasEfectivo,
        'ventas_tarjeta' => floatval($caja['ventas_tarjeta']),
        'ventas_mercadopago' => floatval($caja['ventas_mercadopago']),
        'ventas_transferencia' => floatval($caja['ventas_transferencia']),
        'monto_retiros' => $montoRetiros,
        'retiros' => $retirosList,
        'total_ventas' => floatval($caja['total_ventas']),
        'total_transacciones' => intval($caja['total_transacciones'] ?? 0),
        'efectivo_esperado' => $totalEsperado,
        'conteo_fisico_declarado' => $montoFisico,
        'diferencia' => $diferencia,
        'diferencia_texto' => $diffTexto,
        'observaciones' => $caja['observaciones_cierre'] ?: 'Sin observaciones',
        'firma_digital' => 'Z-' . $idarqueo . '-' . date('YmdHi') . '-' . substr(md5($idarqueo . ($caja['fecha_cierre'] ?: 'open')), 0, 8)
    ];

    echo json_encode([
        'ok' => true,
        'informe' => $informeObj,
        'informe_cierre' => $informeObj
    ]);
    exit();
}

else {
    echo json_encode(['ok' => false, 'error' => "Acción no reconocida: '$accion'."]);
}
