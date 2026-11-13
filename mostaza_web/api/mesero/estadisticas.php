<?php
// ============================================================
// api/mesero/estadisticas.php
// Métricas del turno para Meseros (mesas atendidas, efectivo a rendir, recaudación).
// ============================================================
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    exit(0);
}

require_once(__DIR__ . '/../../conexion.php');

$meseroParam = trim($_GET['mesero'] ?? '');
$idEmpleado = intval($_GET['idempleado'] ?? 0);

try {
    // Si viene nombre o ID, buscar datos del mesero
    if ($idEmpleado <= 0 && !empty($meseroParam)) {
        if (is_numeric($meseroParam)) {
            $idEmpleado = intval($meseroParam);
        } else {
            $stmtM = $conn->prepare("SELECT idempleado FROM empleado WHERE (nombre LIKE ? OR CONCAT(nombre, ' ', apellido) LIKE ?) AND fk_id_rol = 4 LIMIT 1");
            $like = "%$meseroParam%";
            $stmtM->bind_param("ss", $like, $like);
            $stmtM->execute();
            $rM = $stmtM->get_result()->fetch_assoc();
            if ($rM) {
                $idEmpleado = intval($rM['idempleado']);
            }
        }
    }

    // 1. Pedidos atendidos hoy en salón
    $sqlHoy = "SELECT 
                    COUNT(idpedido) as cant,
                    COALESCE(SUM(total), 0) as total_facturado,
                    COALESCE(SUM(CASE WHEN LOWER(metodo_pago) = 'efectivo' THEN total ELSE 0 END), 0) as efectivo_recaudado
               FROM pedido_web 
               WHERE (tipo_entrega = 'salon' OR tipo_entrega IS NULL)
                 AND estado IN ('Listo', 'Listo para Despacho', 'Entregado', 'Cobrado', 'En Preparacion', 'En Cocina')
                 AND DATE(fecha) = CURDATE()";

    if ($idEmpleado > 0) {
        $sqlHoy .= " AND (idempleado_mesero = $idEmpleado OR idempleado = $idEmpleado)";
    } elseif (!empty($meseroParam)) {
        $meseroEsc = $conn->real_escape_string($meseroParam);
        $sqlHoy .= " AND (mesero_nombre LIKE '%$meseroEsc%')";
    }

    $resHoy = $conn->query($sqlHoy);
    $stats = $resHoy ? $resHoy->fetch_assoc() : [];

    $cantHoy = intval($stats['cant'] ?? 0);
    $totalFac = floatval($stats['total_facturado'] ?? 0);
    $efectivo = floatval($stats['efectivo_recaudado'] ?? 0);

    // 2. Total histórico de atención
    $sqlTotal = "SELECT 
                    COUNT(idpedido) as cant_total,
                    COALESCE(SUM(total), 0) as total_historico
                 FROM pedido_web
                 WHERE (tipo_entrega = 'salon' OR tipo_entrega IS NULL)";
    if ($idEmpleado > 0) {
        $sqlTotal .= " AND (idempleado_mesero = $idEmpleado OR idempleado = $idEmpleado)";
    } elseif (!empty($meseroParam)) {
        $meseroEsc = $conn->real_escape_string($meseroParam);
        $sqlTotal .= " AND (mesero_nombre LIKE '%$meseroEsc%')";
    }

    $resTotal = $conn->query($sqlTotal);
    $totales = $resTotal ? $resTotal->fetch_assoc() : [];

    $cantTotal = intval($totales['cant_total'] ?? 0);
    $totalHist = floatval($totales['total_historico'] ?? 0);

    echo json_encode([
        'ok' => true,
        'mesero' => $meseroParam ?: ($idEmpleado > 0 ? "Mesero #$idEmpleado" : 'General'),
        'idempleado' => $idEmpleado,
        'mesas_atendidas' => $cantHoy,
        'total_recaudado' => $totalFac,
        'total_recaudado_formateado' => '$' . number_format($totalFac, 0, ',', '.'),
        'efectivo_a_rendir' => $efectivo,
        'efectivo_a_rendir_formateado' => '$' . number_format($efectivo, 0, ',', '.'),
        'ticket_promedio' => $cantHoy > 0 ? '$' . number_format($totalFac / $cantHoy, 0, ',', '.') : '$0',
        'atenciones_total' => $cantTotal,
        'total_historico' => $totalHist,
        'total_historico_formateado' => '$' . number_format($totalHist, 0, ',', '.')
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'ok' => false,
        'error' => 'Error al obtener estadísticas del mesero: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
