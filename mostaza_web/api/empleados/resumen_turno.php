<?php
// ============================================================
// api/empleados/resumen_turno.php
// Devuelve el Ticket de Rendimiento y Balance de Jornada del empleado
// ============================================================
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    exit(0);
}

require_once(__DIR__ . '/../../conexion.php');

$idempleado = intval($_GET['idempleado'] ?? 0);
$idregistro = intval($_GET['idregistro_turno'] ?? 0);

if ($idempleado <= 0) {
    echo json_encode(['ok' => false, 'error' => 'ID de empleado no especificado.']);
    exit();
}

// Consultar datos del empleado
$stmtEmp = $conn->prepare("SELECT idempleado, nombre, apellido, email, fk_id_rol, avatar, fk_id_seccion FROM empleado WHERE idempleado = ?");
$stmtEmp->bind_param("i", $idempleado);
$stmtEmp->execute();
$emp = $stmtEmp->get_result()->fetch_assoc();

if (!$emp) {
    echo json_encode(['ok' => false, 'error' => 'Empleado no encontrado.']);
    exit();
}

$idRol = intval($emp['fk_id_rol']);
$nombreCompleto = $emp['nombre'] . ' ' . $emp['apellido'];

// Consultar registro de turno (activo o último del día)
$turnoSql = "SELECT r.*, t.nombre AS turno_nombre 
             FROM registro_turno_empleado r 
             LEFT JOIN turnos t ON r.fk_id_turno = t.idturnos 
             WHERE r.fk_id_empleado = ? " . ($idregistro > 0 ? "AND r.idregistro = $idregistro " : "") . "
             ORDER BY r.idregistro DESC LIMIT 1";
$stmtT = $conn->prepare($turnoSql);
$stmtT->bind_param("i", $idempleado);
$stmtT->execute();
$turno = $stmtT->get_result()->fetch_assoc();

$horaEntrada = $turno['hora_entrada'] ?? date('H:i:s');
$horaSalida = $turno['hora_salida'] ?? date('H:i:s');
$horasTrabajadas = floatval($turno['horas_trabajadas'] ?? 0);

if ($horasTrabajadas <= 0 && !empty($turno['hora_entrada'])) {
    $segundos = max(60, time() - strtotime($turno['fecha'] . ' ' . $turno['hora_entrada']));
    $horasTrabajadas = round($segundos / 3600, 2);
}

// Métricas de Rendimiento específicas por Rol
$metricas = [];

if ($idRol === 3) {
    // 👨‍🍳 Cocinero (Rol 3)
    $stmtCoc = $conn->prepare("
        SELECT 
            COUNT(idpedido) as total_comandas,
            IFNULL(AVG(duracion_cocina_minutos), 0) as promedio_minutos
        FROM pedido_web 
        WHERE idempleado_cocinero = ? AND (DATE(fecha) = CURDATE() OR DATE(hora_cocina_listo) = CURDATE())
    ");
    $stmtCoc->bind_param("i", $idempleado);
    $stmtCoc->execute();
    $rCoc = $stmtCoc->get_result()->fetch_assoc();

    // Ítems cocinados
    $stmtItems = $conn->prepare("
        SELECT IFNULL(SUM(d.cantidad), 0) as total_items
        FROM detalle_pedido_web d
        JOIN pedido_web p ON d.idpedido = p.idpedido
        WHERE p.idempleado_cocinero = ? AND (DATE(p.fecha) = CURDATE() OR DATE(p.hora_cocina_listo) = CURDATE())
    ");
    $stmtItems->bind_param("i", $idempleado);
    $stmtItems->execute();
    $rItems = $stmtItems->get_result()->fetch_assoc();

    $comandasCount = intval($rCoc['total_comandas'] ?? 0);
    $itemsCount = intval($rItems['total_items'] ?? 0);
    $tiempoProm = round(floatval($rCoc['promedio_minutos'] ?? 0), 1);

    $metricas = [
        'rol_titulo' => 'Cocinero de Plancha & Cocina KDS',
        'comandas_elaboradas' => $comandasCount,
        'items_cocinados' => $itemsCount,
        'tiempo_promedio_minutos' => $tiempoProm,
        'tiempo_promedio_texto' => $tiempoProm > 0 ? "{$tiempoProm} min" : 'En curso'
    ];

} elseif ($idRol === 4) {
    // 🤵 Mesero / Salón (Rol 4)
    $stmtMes = $conn->prepare("
        SELECT 
            COUNT(idpedido) as total_atenciones,
            IFNULL(SUM(total), 0) as total_facturado,
            IFNULL(AVG(duracion_total_minutos), 0) as promedio_atencion
        FROM pedido_web 
        WHERE (idempleado_mesero = ? OR idempleado = ?) AND DATE(fecha) = CURDATE()
    ");
    $stmtMes->bind_param("ii", $idempleado, $idempleado);
    $stmtMes->execute();
    $rMes = $stmtMes->get_result()->fetch_assoc();

    $atencionesCount = intval($rMes['total_atenciones'] ?? 0);
    $totalFacturado = floatval($rMes['total_facturado'] ?? 0);
    $promAtencion = round(floatval($rMes['promedio_atencion'] ?? 0), 1);

    $metricas = [
        'rol_titulo' => 'Mesero de Salón y Atención en Mesas',
        'mesas_atendidas' => $atencionesCount,
        'total_recaudado' => $totalFacturado,
        'total_recaudado_formateado' => '$' . number_format($totalFacturado, 0, ',', '.'),
        'ticket_promedio' => $atencionesCount > 0 ? '$' . number_format($totalFacturado / $atencionesCount, 0, ',', '.') : '$0',
        'tiempo_promedio_servicio' => $promAtencion > 0 ? "{$promAtencion} min" : 'En curso'
    ];

} elseif ($idRol === 6) {
    // 🛵 Repartidor / Delivery (Rol 6)
    $stmtRep = $conn->prepare("
        SELECT 
            COUNT(idpedido) as viajes_completados,
            IFNULL(SUM(CASE WHEN metodo_pago = 'efectivo' THEN total ELSE 0 END), 0) as efectivo_recaudado,
            IFNULL(AVG(duracion_entrega_minutos), 0) as promedio_viaje
        FROM pedido_web 
        WHERE idempleado_repartidor = ? AND estado = 'Entregado' AND DATE(fecha) = CURDATE()
    ");
    $stmtRep->bind_param("i", $idempleado);
    $stmtRep->execute();
    $rRep = $stmtRep->get_result()->fetch_assoc();

    $viajes = intval($rRep['viajes_completados'] ?? 0);
    $efectivo = floatval($rRep['efectivo_recaudado'] ?? 0);
    $promViaje = round(floatval($rRep['promedio_viaje'] ?? 0), 1);

    $metricas = [
        'rol_titulo' => 'Repartidor Oficial de Flota Delivery',
        'viajes_completados' => $viajes,
        'efectivo_a_rendir' => $efectivo,
        'efectivo_a_rendir_formateado' => '$' . number_format($efectivo, 0, ',', '.'),
        'tiempo_promedio_viaje' => $promViaje > 0 ? "{$promViaje} min" : 'En curso'
    ];

} else {
    // 👨‍💼 Cajero (Rol 2 u otros)
    $stmtCaj = $conn->prepare("
        SELECT 
            COUNT(idpedido) as total_ventas,
            IFNULL(SUM(total), 0) as total_facturado,
            IFNULL(SUM(CASE WHEN metodo_pago = 'efectivo' THEN total ELSE 0 END), 0) as ventas_efectivo,
            IFNULL(SUM(CASE WHEN metodo_pago IN ('tarjeta_debito', 'tarjeta_credito', 'tarjeta') THEN total ELSE 0 END), 0) as ventas_tarjeta,
            IFNULL(SUM(CASE WHEN metodo_pago = 'mercadopago' THEN total ELSE 0 END), 0) as ventas_mp,
            IFNULL(SUM(CASE WHEN metodo_pago = 'transferencia' THEN total ELSE 0 END), 0) as ventas_transferencia
        FROM pedido_web 
        WHERE (idempleado_cajero = ? OR idempleado = ?) AND DATE(fecha) = CURDATE()
    ");
    $stmtCaj->bind_param("ii", $idempleado, $idempleado);
    $stmtCaj->execute();
    $rCaj = $stmtCaj->get_result()->fetch_assoc();

    $ventas = intval($rCaj['total_ventas'] ?? 0);
    $totalFac = floatval($rCaj['total_facturado'] ?? 0);

    $metricas = [
        'rol_titulo' => 'Cajero de Mostrador POS',
        'tickets_emitidos' => $ventas,
        'total_facturado' => $totalFac,
        'total_facturado_formateado' => '$' . number_format($totalFac, 0, ',', '.'),
        'ventas_efectivo' => floatval($rCaj['ventas_efectivo'] ?? 0),
        'ventas_tarjeta' => floatval($rCaj['ventas_tarjeta'] ?? 0),
        'ventas_mercadopago' => floatval($rCaj['ventas_mercadopago'] ?? 0),
        'ventas_transferencia' => floatval($rCaj['ventas_transferencia'] ?? 0)
    ];
}

echo json_encode([
    'ok' => true,
    'empleado' => [
        'idempleado' => $idempleado,
        'nombre_completo' => $nombreCompleto,
        'avatar' => $emp['avatar'] ?: '👤',
        'rol_id' => $idRol
    ],
    'turno' => [
        'idregistro' => intval($turno['idregistro'] ?? 0),
        'nombre_turno' => $turno['turno_nombre'] ?? 'Turno General',
        'hora_entrada' => $horaEntrada,
        'hora_actual' => date('H:i:s'),
        'horas_trabajadas' => $horasTrabajadas,
        'horas_trabajadas_texto' => number_format($horasTrabajadas, 2) . ' hs'
    ],
    'rendimiento' => $metricas
]);
