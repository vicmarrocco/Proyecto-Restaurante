<?php
// ============================================================
// api/empleados/listar.php
// Lista de empleados oficiales activos filtrados por aplicación o rol
// ============================================================
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once(__DIR__ . '/../../conexion.php');

$app = strtolower(trim($_GET['app'] ?? ''));
$rolParam = intval($_GET['rol'] ?? 0);

// Mapeo estricto de rol por aplicación (cada app solo muestra perfiles de su propio rol)
// 1: Cliente, 2: Cajero, 3: Cocinero, 4: Mesero, 5: Administrador, 6: Repartidor
$rolesPorApp = [
    'cajero'   => [2],
    'pos'      => [2],
    'caja1'    => [2],
    'caja2'    => [2],
    'cocina'   => [3],
    'kds'      => [3],
    'mesero'   => [4],
    'salon'    => [4],
    'delivery' => [6],
    'rider'    => [6],
    'admin'    => [5]
];

$rolesPermitidos = [];
if (!empty($app) && isset($rolesPorApp[$app])) {
    $rolesPermitidos = $rolesPorApp[$app];
} elseif ($rolParam > 0) {
    $rolesPermitidos = [$rolParam];
}

$sql = "SELECT e.idempleado, e.nombre, e.apellido, e.dni, e.email, e.fk_id_rol, r.rol, e.avatar, e.vehiculo, e.patente, e.telefono, e.calificacion, e.viajes_totales, e.estado_servicio, e.sucursal_base, e.fk_id_seccion, e.fk_id_seccion_2 
        FROM empleado e 
        LEFT JOIN rol r ON e.fk_id_rol = r.idrol 
        WHERE e.activo = 1";

if (!empty($rolesPermitidos)) {
    $rolesIn = implode(',', array_map('intval', $rolesPermitidos));
    $sql .= " AND e.fk_id_rol IN ($rolesIn)";
}

if ($app === 'mesero' || $app === 'salon') {
    $sql .= " AND e.email NOT LIKE '%cajero%'";
}

$sql .= " ORDER BY e.fk_id_rol ASC, e.idempleado ASC";

$res = $conn->query($sql);
$empleados = [];

if ($res) {
    while ($r = $res->fetch_assoc()) {
        $nombreCompleto = trim($r['nombre'] . ' ' . $r['apellido']);
        $empleados[] = [
            'id' => intval($r['idempleado']),
            'idempleado' => intval($r['idempleado']),
            'nombre' => $r['nombre'],
            'apellido' => $r['apellido'],
            'nombre_completo' => $nombreCompleto,
            'email' => $r['email'],
            'idrol' => intval($r['fk_id_rol']),
            'rol' => $r['rol'] ?? 'Empleado',
            'avatar' => $r['avatar'] ?: '👤',
            'vehiculo' => $r['vehiculo'] ?: '',
            'patente' => $r['patente'] ?: '',
            'telefono' => $r['telefono'] ?: '',
            'calificacion' => floatval($r['calificacion'] ?? 4.95),
            'viajes_totales' => intval($r['viajes_totales'] ?? 0),
            'estado_servicio' => $r['estado_servicio'] ?: 'Disponible',
            'sucursal_base' => $r['sucursal_base'] ?: 'Mostaza Formosa Centro',
            'fk_id_seccion' => (int)($r['fk_id_seccion'] ?? 1),
            'fk_id_seccion_2' => !empty($r['fk_id_seccion_2']) ? (int)$r['fk_id_seccion_2'] : null,
            'secciones_asignadas' => array_values(array_filter([(int)($r['fk_id_seccion'] ?? 1), !empty($r['fk_id_seccion_2']) ? (int)$r['fk_id_seccion_2'] : null]))
        ];
    }
}

echo json_encode([
    'ok' => true,
    'total' => count($empleados),
    'app' => $app,
    'empleados' => $empleados
]);
