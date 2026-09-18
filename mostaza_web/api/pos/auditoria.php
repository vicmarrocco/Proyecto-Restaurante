<?php
// ============================================================
// api/pos/auditoria.php
// Endpoint de Trazabilidad y Auditoría Estricta de Pedidos.
// Devuelve la cronología completa de eventos con cada empleado.
// ============================================================
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once(__DIR__ . '/../../conexion.php');

$idpedido = intval($_GET['idpedido'] ?? 0);

if ($idpedido <= 0) {
    echo json_encode(['ok' => false, 'error' => 'Parámetro idpedido inválido o no proporcionado.']);
    exit();
}

try {
    // 1. Obtener datos generales del pedido
    $stmtP = $conn->prepare("
        SELECT 
            p.idpedido, p.tipo_entrega, p.estado, p.metodo_pago, p.total, p.fecha,
            p.cajero_nombre, p.idempleado_cajero,
            p.mesero_nombre, p.idempleado_mesero,
            p.cocinero_nombre, p.idempleado_cocinero,
            p.repartidor_asignado, p.idempleado_repartidor,
            p.hora_recibido, p.hora_cocina_inicio, p.hora_cocina_listo, p.hora_despacho_camino, p.hora_entregado,
            p.duracion_cocina_minutos, p.duracion_entrega_minutos, p.duracion_total_minutos,
            p.numero_mesa, p.direccion_envio, p.cancelado_por, p.motivo_cancelacion,
            u.nombre as user_nombre, u.apellido as user_apellido, u.email as user_email
        FROM pedido_web p
        LEFT JOIN usuario_web u ON p.idusuario = u.idusuario
        WHERE p.idpedido = ?
    ");
    $stmtP->bind_param("i", $idpedido);
    $stmtP->execute();
    $pedido = $stmtP->get_result()->fetch_assoc();

    if (!$pedido) {
        echo json_encode(['ok' => false, 'error' => "El pedido #$idpedido no existe en el sistema."]);
        exit();
    }

    // 2. Obtener lista cronológica de eventos de auditoría
    $stmtAud = $conn->prepare("
        SELECT 
            a.idauditoria, a.idpedido, a.estado_anterior, a.estado_nuevo,
            a.accion, a.rol_responsable, a.idempleado, a.nombre_responsable,
            a.detalles, a.ip_origen, a.fecha_hora,
            e.avatar as empleado_avatar, e.email as empleado_email, e.vehiculo, e.patente
        FROM pedido_auditoria a
        LEFT JOIN empleado e ON a.idempleado = e.idempleado
        WHERE a.idpedido = ?
        ORDER BY a.idauditoria ASC
    ");
    $stmtAud->bind_param("i", $idpedido);
    $stmtAud->execute();
    $resAud = $stmtAud->get_result();

    $eventos = [];
    while ($ev = $resAud->fetch_assoc()) {
        // Asignar icono y color según el rol
        $icono = 'fa-user';
        $badgeClass = 'badge-secondary';
        $rol = $ev['rol_responsable'];

        if (stripos($rol, 'Cajero') !== false) {
            $icono = 'fa-cash-register';
            $badgeClass = 'badge-primary';
        } elseif (stripos($rol, 'Cocinero') !== false || stripos($rol, 'Cocina') !== false) {
            $icono = 'fa-fire-burner';
            $badgeClass = 'badge-warning';
        } elseif (stripos($rol, 'Repartidor') !== false || stripos($rol, 'Delivery') !== false) {
            $icono = 'fa-motorcycle';
            $badgeClass = 'badge-success';
        } elseif (stripos($rol, 'Mesero') !== false) {
            $icono = 'fa-utensils';
            $badgeClass = 'badge-info';
        } elseif (stripos($rol, 'Cliente') !== false) {
            $icono = 'fa-globe';
            $badgeClass = 'badge-dark';
        }

        $eventos[] = [
            'idauditoria' => intval($ev['idauditoria']),
            'fecha_hora' => $ev['fecha_hora'],
            'fecha_hora_formateada' => date('d/m/Y H:i:s', strtotime($ev['fecha_hora'])),
            'hora' => date('H:i:s', strtotime($ev['fecha_hora'])),
            'estado_anterior' => $ev['estado_anterior'],
            'estado_nuevo' => $ev['estado_nuevo'],
            'accion' => $ev['accion'],
            'rol' => $ev['rol_responsable'],
            'idempleado' => $ev['idempleado'] ? intval($ev['idempleado']) : null,
            'nombre_responsable' => $ev['nombre_responsable'],
            'detalles' => $ev['detalles'] ?: '',
            'ip_origen' => $ev['ip_origen'],
            'avatar' => $ev['empleado_avatar'] ?: '👤',
            'email' => $ev['empleado_email'] ?: '',
            'vehiculo' => $ev['vehiculo'] ?: '',
            'patente' => $ev['patente'] ?: '',
            'icono' => $icono,
            'badge_class' => $badgeClass
        ];
    }

    echo json_encode([
        'ok' => true,
        'pedido' => [
            'idpedido' => intval($pedido['idpedido']),
            'tipo_entrega' => $pedido['tipo_entrega'],
            'estado' => $pedido['estado'],
            'metodo_pago' => $pedido['metodo_pago'],
            'total' => floatval($pedido['total']),
            'total_formateado' => '$' . number_format($pedido['total'], 0, ',', '.'),
            'fecha' => $pedido['fecha'],
            'fecha_formateada' => date('d/m/Y H:i', strtotime($pedido['fecha'])),
            'cajero' => $pedido['cajero_nombre'] ?: 'No asignado',
            'cocinero' => $pedido['cocinero_nombre'] ?: 'No asignado',
            'repartidor' => $pedido['repartidor_asignado'] ?: 'No asignado',
            'mesero' => $pedido['mesero_nombre'] ?: 'No asignado',
            'duracion_cocina_minutos' => $pedido['duracion_cocina_minutos'] ? intval($pedido['duracion_cocina_minutos']) : null,
            'duracion_entrega_minutos' => $pedido['duracion_entrega_minutos'] ? intval($pedido['duracion_entrega_minutos']) : null,
            'duracion_total_minutos' => $pedido['duracion_total_minutos'] ? intval($pedido['duracion_total_minutos']) : null,
            'cliente' => trim(($pedido['user_nombre'] ?? '') . ' ' . ($pedido['user_apellido'] ?? '')) ?: 'Cliente Web / Mostrador',
            'direccion' => $pedido['direccion_envio'] ?: 'Entrega en Local / Salón',
            'cancelado_por' => $pedido['cancelado_por'],
            'motivo_cancelacion' => $pedido['motivo_cancelacion']
        ],
        'total_eventos' => count($eventos),
        'timeline' => $eventos,
        'eventos' => $eventos,
        'duraciones' => [
            'cocina_min' => $pedido['duracion_cocina_minutos'] ? intval($pedido['duracion_cocina_minutos']) : null,
            'entrega_min' => $pedido['duracion_entrega_minutos'] ? intval($pedido['duracion_entrega_minutos']) : null,
            'total_min' => $pedido['duracion_total_minutos'] ? intval($pedido['duracion_total_minutos']) : null
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'ok' => false,
        'error' => 'Error al consultar auditoría: ' . $e->getMessage()
    ]);
}
