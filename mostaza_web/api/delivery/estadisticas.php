<?php
// ============================================================
// api/delivery/estadisticas.php
// Métricas del turno para repartidores (entregas, efectivo a rendir, propinas).
// ============================================================
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once(__DIR__ . '/../../conexion.php');

$repartidor = trim($_GET['repartidor'] ?? '');
$idempleado = intval($_GET['idempleado'] ?? 0);

try {
    // 1. Pedidos entregados en el turno actual (soporta turnos nocturnos que cruzan la medianoche)
    $sqlEntregados = "SELECT COUNT(*) as cant, 
                             COALESCE(SUM(total), 0) as total_facturado,
                             COALESCE(SUM(CASE WHEN LOWER(metodo_pago) = 'efectivo' THEN total ELSE 0 END), 0) as efectivo_recaudado,
                             COALESCE(SUM(propina), 0) as total_propinas
                      FROM pedido_web 
                      WHERE tipo_entrega = 'delivery' 
                        AND estado = 'Entregado' 
                        AND (
                            DATE(fecha) = CURDATE() 
                            OR DATE(hora_entregado) = CURDATE() 
                            OR hora_entregado >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                            OR fecha >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                        )";
    
    $conds = [];
    $params = [];
    $types = "";
    if (!empty($repartidor)) {
        $conds[] = "(repartidor_asignado = ? OR repartidor_asignado LIKE ?)";
        $params[] = $repartidor;
        $params[] = "%$repartidor%";
        $types .= "ss";
    }
    if ($idempleado > 0) {
        $conds[] = "idempleado_repartidor = ?";
        $params[] = $idempleado;
        $types .= "i";
    }

    if (!empty($conds)) {
        $stmt = $conn->prepare($sqlEntregados . " AND (" . implode(" OR ", $conds) . ")");
        $stmt->bind_param($types, ...$params);
    } else {
        $stmt = $conn->prepare($sqlEntregados);
    }
    $stmt->execute();
    $stats = $stmt->get_result()->fetch_assoc();

    // 2. Pedidos en camino actualmente
    $sqlEnCamino = "SELECT COUNT(*) as cant_en_camino 
                    FROM pedido_web 
                    WHERE tipo_entrega = 'delivery' AND estado = 'En Camino'";
    if (!empty($conds)) {
        $stmtC = $conn->prepare($sqlEnCamino . " AND (" . implode(" OR ", $conds) . ")");
        $stmtC->bind_param($types, ...$params);
    } else {
        $stmtC = $conn->prepare($sqlEnCamino);
    }
    $stmtC->execute();
    $enCamino = $stmtC->get_result()->fetch_assoc();

    // 3. Métricas históricas completas del repartidor
    $sqlTotal = "SELECT COUNT(*) as cant_total,
                        COALESCE(SUM(total), 0) as total_historico
                 FROM pedido_web
                 WHERE tipo_entrega = 'delivery' AND estado = 'Entregado'";
    if (!empty($conds)) {
        $stmtT = $conn->prepare($sqlTotal . " AND (" . implode(" OR ", $conds) . ")");
        $stmtT->bind_param($types, ...$params);
    } else {
        $stmtT = $conn->prepare($sqlTotal);
    }
    $stmtT->execute();
    $totales = $stmtT->get_result()->fetch_assoc();

    echo json_encode([
        'ok' => true,
        'repartidor' => $repartidor ?: 'General',
        'entregados_hoy' => intval($stats['cant'] ?? 0),
        'entregados_total' => intval($totales['cant_total'] ?? 0),
        'en_camino_actual' => intval($enCamino['cant_en_camino'] ?? 0),
        'efectivo_a_rendir' => floatval($stats['efectivo_recaudado'] ?? 0),
        'efectivo_a_rendir_formateado' => '$' . number_format($stats['efectivo_recaudado'] ?? 0, 0, ',', '.'),
        'total_propinas' => floatval($stats['total_propinas'] ?? 0),
        'total_propinas_formateado' => '$' . number_format($stats['total_propinas'] ?? 0, 0, ',', '.'),
        'total_facturado' => floatval($stats['total_facturado'] ?? 0),
        'total_facturado_formateado' => '$' . number_format($stats['total_facturado'] ?? 0, 0, ',', '.'),
        'total_historico' => floatval($totales['total_historico'] ?? 0),
        'total_historico_formateado' => '$' . number_format($totales['total_historico'] ?? 0, 0, ',', '.')
    ]);

} catch (Exception $e) {
    echo json_encode([
        'ok' => false,
        'error' => 'Error al obtener estadísticas: ' . $e->getMessage()
    ]);
}
