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

try {
    // 1. Pedidos entregados hoy
    $sqlEntregados = "SELECT COUNT(*) as cant, 
                             COALESCE(SUM(total), 0) as total_facturado,
                             COALESCE(SUM(CASE WHEN metodo_pago = 'efectivo' THEN total ELSE 0 END), 0) as efectivo_recaudado,
                             COALESCE(SUM(propina), 0) as total_propinas
                      FROM pedido_web 
                      WHERE tipo_entrega = 'delivery' 
                        AND estado = 'Entregado' 
                        AND DATE(fecha) = CURDATE()";
    
    if (!empty($repartidor)) {
        $stmt = $conn->prepare($sqlEntregados . " AND repartidor_asignado = ?");
        $stmt->bind_param("s", $repartidor);
    } else {
        $stmt = $conn->prepare($sqlEntregados);
    }
    $stmt->execute();
    $stats = $stmt->get_result()->fetch_assoc();

    // 2. Pedidos en camino actualmente
    $sqlEnCamino = "SELECT COUNT(*) as cant_en_camino 
                    FROM pedido_web 
                    WHERE tipo_entrega = 'delivery' AND estado = 'En Camino'";
    if (!empty($repartidor)) {
        $stmtC = $conn->prepare($sqlEnCamino . " AND repartidor_asignado = ?");
        $stmtC->bind_param("s", $repartidor);
    } else {
        $stmtC = $conn->prepare($sqlEnCamino);
    }
    $stmtC->execute();
    $enCamino = $stmtC->get_result()->fetch_assoc();

    echo json_encode([
        'ok' => true,
        'repartidor' => $repartidor ?: 'General',
        'entregados_hoy' => intval($stats['cant'] ?? 0),
        'en_camino_actual' => intval($enCamino['cant_en_camino'] ?? 0),
        'efectivo_a_rendir' => floatval($stats['efectivo_recaudado'] ?? 0),
        'efectivo_a_rendir_formateado' => '$' . number_format($stats['efectivo_recaudado'] ?? 0, 0, ',', '.'),
        'total_propinas' => floatval($stats['total_propinas'] ?? 0),
        'total_propinas_formateado' => '$' . number_format($stats['total_propinas'] ?? 0, 0, ',', '.'),
        'total_facturado' => floatval($stats['total_facturado'] ?? 0),
        'total_facturado_formateado' => '$' . number_format($stats['total_facturado'] ?? 0, 0, ',', '.')
    ]);

} catch (Exception $e) {
    echo json_encode([
        'ok' => false,
        'error' => 'Error al obtener estadísticas: ' . $e->getMessage()
    ]);
}
