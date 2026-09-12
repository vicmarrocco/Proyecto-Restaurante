<?php
// ============================================================
// api/caja/movimiento.php
// Maneja apertura con fondo inicial, arqueo físico de billetes
// al final del turno, cálculo de diferencias y cierre Z.
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
$accion = $input['accion'] ?? '';

if ($accion === 'abrir_caja') {
    $cajero = htmlspecialchars(trim($input['cajero'] ?? ($_SESSION['cajero_nombre'] ?? 'Cajero Mostaza')));
    $turno = htmlspecialchars(trim($input['turno'] ?? 'Turno Mañana'));
    $montoInicial = floatval($input['monto_inicial'] ?? 10000);
    $idEmpleado = !empty($input['idempleado']) ? intval($input['idempleado']) : ($_SESSION['cajero_id'] ?? 1);
    $obsApertura = htmlspecialchars(trim($input['observaciones_apertura'] ?? 'Apertura de turno en terminal POS'));

    // Verificar si ya hay una caja abierta
    $checkAbierta = $conn->query("SELECT idarqueo FROM caja_arqueo WHERE estado = 'Abierta' LIMIT 1");
    if ($checkAbierta && $checkAbierta->num_rows > 0) {
        $existente = $checkAbierta->fetch_assoc();
        echo json_encode([
            'ok' => false,
            'error' => "Ya existe un turno de caja abierto (N.º #{$existente['idarqueo']}). Debés cerrarlo antes de iniciar un nuevo turno."
        ]);
        exit();
    }

    $stmt = $conn->prepare(
        "INSERT INTO caja_arqueo (idempleado, cajero, turno, monto_inicial, observaciones_apertura, estado) 
         VALUES (?, ?, ?, ?, ?, 'Abierta')"
    );
    $stmt->bind_param("issds", $idEmpleado, $cajero, $turno, $montoInicial, $obsApertura);
    
    if ($stmt->execute()) {
        $idarqueo = $conn->insert_id;
        echo json_encode([
            'ok' => true,
            'mensaje' => '¡Turno de caja abierto con éxito!',
            'idarqueo' => $idarqueo,
            'turno' => $turno,
            'cajero' => $cajero,
            'monto_inicial' => $montoInicial,
            'monto_inicial_formateado' => '$' . number_format($montoInicial, 0, ',', '.'),
            'fecha_apertura' => date('d/m/Y H:i:s')
        ]);
    } else {
        echo json_encode(['ok' => false, 'error' => 'Error al abrir la caja en BD: ' . $conn->error]);
    }

} elseif ($accion === 'cerrar_caja') {
    $idarqueo = intval($input['idarqueo'] ?? 0);
    if (!$idarqueo) {
        $rAct = $conn->query("SELECT idarqueo FROM caja_arqueo WHERE estado = 'Abierta' ORDER BY idarqueo DESC LIMIT 1");
        if ($rAct && $rowA = $rAct->fetch_assoc()) {
            $idarqueo = intval($rowA['idarqueo']);
        }
    }

    $stmtCaja = $conn->prepare("SELECT * FROM caja_arqueo WHERE idarqueo = ?");
    $stmtCaja->bind_param("i", $idarqueo);
    $stmtCaja->execute();
    $caja = $stmtCaja->get_result()->fetch_assoc();

    if (!$caja) {
        echo json_encode(['ok' => false, 'error' => 'No se encontró el registro del turno de caja.']);
        exit();
    }

    $montoInicial = floatval($caja['monto_inicial']);
    $ventasEfectivo = floatval($caja['ventas_efectivo']);
    $totalEsperadoSistema = $montoInicial + $ventasEfectivo;
    
    $montoFisico = floatval($input['monto_fisico'] ?? $totalEsperadoSistema);
    $diferencia = $montoFisico - $totalEsperadoSistema;
    $fechaCierre = date('Y-m-d H:i:s');
    $obsCierre = htmlspecialchars(trim($input['observaciones_cierre'] ?? 'Cierre de turno y arqueo regular'));

    $stmtUpd = $conn->prepare(
        "UPDATE caja_arqueo 
         SET monto_fisico_cierre = ?, monto_final_sistema = ?, diferencia = ?, 
             observaciones_cierre = ?, estado = 'Cerrada', fecha_cierre = ? 
         WHERE idarqueo = ?"
    );
    $stmtUpd->bind_param("dddssi", $montoFisico, $totalEsperadoSistema, $diferencia, $obsCierre, $fechaCierre, $idarqueo);

    if ($stmtUpd->execute()) {
        $diffTexto = "Cuadre Exacto";
        if ($diferencia > 0) $diffTexto = "+$" . number_format($diferencia, 0, ',', '.') . " (Sobrante)";
        elseif ($diferencia < 0) $diffTexto = "-$" . number_format(abs($diferencia), 0, ',', '.') . " (Faltante)";

        echo json_encode([
            'ok' => true,
            'mensaje' => '¡Turno cerrado y arqueo registrado correctamente!',
            'resumen' => [
                'idarqueo' => $idarqueo,
                'cajero' => $caja['cajero'],
                'turno' => $caja['turno'],
                'fecha_apertura' => date('d/m/Y H:i', strtotime($caja['fecha_apertura'])),
                'fecha_cierre' => date('d/m/Y H:i', strtotime($fechaCierre)),
                'monto_inicial' => $montoInicial,
                'monto_inicial_formateado' => '$' . number_format($montoInicial, 0, ',', '.'),
                'ventas_efectivo' => $ventasEfectivo,
                'ventas_efectivo_formateado' => '$' . number_format($ventasEfectivo, 0, ',', '.'),
                'ventas_tarjeta' => floatval($caja['ventas_tarjeta']),
                'ventas_tarjeta_formateado' => '$' . number_format($caja['ventas_tarjeta'], 0, ',', '.'),
                'ventas_mercadopago' => floatval($caja['ventas_mercadopago']),
                'ventas_mercadopago_formateado' => '$' . number_format($caja['ventas_mercadopago'], 0, ',', '.'),
                'ventas_transferencia' => floatval($caja['ventas_transferencia']),
                'ventas_transferencia_formateado' => '$' . number_format($caja['ventas_transferencia'], 0, ',', '.'),
                'total_ventas' => floatval($caja['total_ventas']),
                'total_ventas_formateado' => '$' . number_format($caja['total_ventas'], 0, ',', '.'),
                'monto_final_sistema' => $totalEsperadoSistema,
                'monto_final_sistema_formateado' => '$' . number_format($totalEsperadoSistema, 0, ',', '.'),
                'monto_fisico_cierre' => $montoFisico,
                'monto_fisico_formateado' => '$' . number_format($montoFisico, 0, ',', '.'),
                'diferencia' => $diferencia,
                'diferencia_formateada' => '$' . number_format($diferencia, 0, ',', '.'),
                'diferencia_texto' => $diffTexto,
                'total_transacciones' => intval($caja['total_transacciones'])
            ]
        ]);
    } else {
        echo json_encode(['ok' => false, 'error' => 'Error al cerrar la caja en BD: ' . $conn->error]);
    }
} else {
    echo json_encode(['ok' => false, 'error' => 'Acción no válida.']);
}

