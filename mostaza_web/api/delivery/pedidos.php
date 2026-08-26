<?php
// ============================================================
// api/delivery/pedidos.php
// Consulta de pedidos de delivery en tiempo real para la App de Repartidores.
// ============================================================
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once(__DIR__ . '/../../conexion.php');

$filtro = $_GET['filtro'] ?? 'todos'; // 'pendientes', 'en_camino', 'entregados', 'todos'
$repartidor = trim($_GET['repartidor'] ?? '');

try {
    $where = ["tipo_entrega = 'delivery'"];
    $params = [];
    $types = "";

    if ($filtro === 'pendientes') {
        $where[] = "estado IN ('Recibido', 'En Preparación', 'Listo para Despacho')";
    } elseif ($filtro === 'en_camino') {
        $where[] = "estado = 'En Camino'";
        if (!empty($repartidor)) {
            $where[] = "(repartidor_asignado = ? OR repartidor_asignado IS NULL OR repartidor_asignado = '')";
            $params[] = $repartidor;
            $types .= "s";
        }
    } elseif ($filtro === 'entregados') {
        $where[] = "estado = 'Entregado' AND DATE(fecha) = CURDATE()";
        if (!empty($repartidor)) {
            $where[] = "repartidor_asignado = ?";
            $params[] = $repartidor;
            $types .= "s";
        }
    }

    $whereSql = implode(" AND ", $where);
    $sql = "SELECT p.*, 
                   COALESCE(u.nombre, 'Cliente') as cliente_nombre,
                   COALESCE(u.apellido, '') as cliente_apellido,
                   COALESCE(u.email, '') as cliente_email
            FROM pedido_web p
            LEFT JOIN usuario_web u ON p.idusuario = u.idusuario
            WHERE $whereSql
            ORDER BY 
                CASE 
                    WHEN p.estado = 'Listo para Despacho' THEN 1
                    WHEN p.estado = 'En Preparación' THEN 2
                    WHEN p.estado = 'En Camino' THEN 3
                    WHEN p.estado = 'Recibido' THEN 4
                    ELSE 5
                END, p.idpedido DESC";

    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();

    $pedidos = [];
    while ($p = $res->fetch_assoc()) {
        // Obtener ítems del pedido
        $stmtDet = $conn->prepare("SELECT idproducto, descripcion, modificadores, precio_unitario, cantidad, subtotal 
                                  FROM detalle_pedido_web 
                                  WHERE idpedido = ?");
        $stmtDet->bind_param("i", $p['idpedido']);
        $stmtDet->execute();
        $resDet = $stmtDet->get_result();

        $items = [];
        while ($d = $resDet->fetch_assoc()) {
            $items[] = [
                'idproducto' => $d['idproducto'],
                'descripcion' => $d['descripcion'],
                'modificadores' => $d['modificadores'],
                'precio_unitario' => floatval($d['precio_unitario']),
                'cantidad' => intval($d['cantidad']),
                'subtotal' => floatval($d['subtotal']),
                'subtotal_formateado' => '$' . number_format($d['subtotal'], 0, ',', '.')
            ];
        }

        $nombreCompleto = trim($p['cliente_nombre'] . ' ' . $p['cliente_apellido']);
        if (empty($nombreCompleto) || $nombreCompleto === 'Cliente') {
            $nombreCompleto = "Cliente #" . $p['idpedido'];
        }

        $pedidos[] = [
            'idpedido' => intval($p['idpedido']),
            'idusuario' => $p['idusuario'] ? intval($p['idusuario']) : null,
            'cliente_nombre' => $nombreCompleto,
            'cliente_telefono' => $p['telefono_contacto'] ?: 'No especificado',
            'cliente_direccion' => $p['direccion_envio'] ?: 'Retiro en Sucursal',
            'fecha' => $p['fecha'],
            'hora' => date('H:i', strtotime($p['fecha'])),
            'estado' => $p['estado'],
            'tipo_entrega' => $p['tipo_entrega'],
            'sucursal' => $p['sucursal'],
            'metodo_pago' => $p['metodo_pago'],
            'estado_pago' => $p['estado_pago'],
            'subtotal' => floatval($p['subtotal']),
            'costo_envio' => floatval($p['costo_envio']),
            'descuento' => floatval($p['descuento']),
            'propina' => floatval($p['propina']),
            'total' => floatval($p['total']),
            'total_formateado' => '$' . number_format($p['total'], 0, ',', '.'),
            'paga_con' => $p['paga_con'] ? floatval($p['paga_con']) : floatval($p['total']),
            'vuelto' => $p['vuelto'] ? floatval($p['vuelto']) : 0.00,
            'vuelto_formateado' => '$' . number_format($p['vuelto'] ?? 0, 0, ',', '.'),
            'notas' => $p['notas'] ?: '',
            'repartidor_asignado' => $p['repartidor_asignado'] ?: '',
            'items' => $items,
            'cantidad_items' => count($items)
        ];
    }

    echo json_encode([
        'ok' => true,
        'total' => count($pedidos),
        'filtro' => $filtro,
        'pedidos' => $pedidos
    ]);

} catch (Exception $e) {
    echo json_encode([
        'ok' => false,
        'error' => 'Error al obtener pedidos: ' . $e->getMessage()
    ]);
}
