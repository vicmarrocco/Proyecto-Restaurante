<?php
// ============================================================
// api/cocina/insumos.php
// Consulta de insumos de cocina y envío de solicitudes al admin
// ============================================================
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once(__DIR__ . '/../../conexion.php');

$metodo = $_SERVER['REQUEST_METHOD'];

if ($metodo === 'GET') {
    $categoria = $_GET['categoria'] ?? null;
    $sql = "SELECT * FROM stock_insumo";
    if ($categoria) {
        $sql .= " WHERE categoria = '" . $conn->real_escape_string($categoria) . "'";
    }
    $sql .= " ORDER BY categoria, nombre ASC";

    $res = $conn->query($sql);
    $insumos = [];

    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $actual = floatval($r['stock_actual']);
            $minimo = floatval($r['stock_minimo']);
            $alerta = ($actual <= $minimo);

            $insumos[] = [
                'idinsumo' => intval($r['idinsumo']),
                'nombre' => $r['nombre'],
                'categoria' => $r['categoria'],
                'unidad_medida' => $r['unidad_medida'],
                'stock_actual' => $actual,
                'stock_minimo' => $minimo,
                'alerta' => $alerta,
                'costo_unitario' => floatval($r['costo_unitario']),
                'proveedor' => $r['proveedor'] ?? '',
                'actual_texto' => $actual . ' ' . $r['unidad_medida'],
                'minimo_texto' => $minimo . ' ' . $r['unidad_medida']
            ];
        }
    }

    echo json_encode([
        'ok' => true,
        'insumos' => $insumos,
        'total' => count($insumos),
        'alertas_criticas' => count(array_filter($insumos, fn($i) => $i['alerta']))
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

if ($metodo === 'POST') {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true) ?? $_POST;

    $solicitante = trim($input['solicitante'] ?? 'Cocinero de Turno');
    $nombreInsumo = trim($input['nombre_insumo'] ?? '');
    $cantidad = floatval($input['cantidad'] ?? 1);
    $unidad = trim($input['unidad_medida'] ?? 'un.');
    $prioridad = in_array($input['prioridad'] ?? '', ['Baja', 'Media', 'Alta', 'Urgente']) ? $input['prioridad'] : 'Media';
    $motivo = trim($input['motivo'] ?? 'Stock crítico en turno');

    if (empty($nombreInsumo)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Debe indicar el nombre del insumo']);
        exit();
    }

    $stmt = $conn->prepare("INSERT INTO solicitud_ingrediente (solicitante_nombre, nombre_insumo, cantidad_solicitada, unidad_medida, prioridad, motivo, fecha_solicitud, estado) VALUES (?, ?, ?, ?, ?, ?, NOW(), 'Pendiente')");
    $stmt->bind_param("ssdsss", $solicitante, $nombreInsumo, $cantidad, $unidad, $prioridad, $motivo);

    if ($stmt->execute()) {
        echo json_encode([
            'ok' => true,
            'mensaje' => "Solicitud enviada al Administrador para: $nombreInsumo ($cantidad $unidad)",
            'idsolicitud' => $stmt->insert_id
        ], JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Error al guardar la solicitud: ' . $conn->error]);
    }
    exit();
}
