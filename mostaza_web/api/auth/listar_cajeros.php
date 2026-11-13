<?php
// ============================================================
// api/auth/listar_cajeros.php
// Perfiles de cajeros para la pantalla de login del POS.
// Devuelve solo id, nombre, rol y email enmascarado (nunca el email completo).
// ============================================================
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    exit(0);
}

// Bloqueo de seguridad solo si proviene de Cloudflare externo explícito
if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'No disponible desde Internet.']);
    exit;
}

require_once(__DIR__ . '/../../conexion.php');

$res = $conn->query("SELECT e.idempleado, e.nombre, e.apellido, e.email, r.rol
                     FROM empleado e LEFT JOIN rol r ON e.fk_id_rol = r.idrol
                     WHERE e.activo = 1 AND e.fk_id_rol = 2
                     ORDER BY e.nombre, e.apellido");
$cajeros = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        [$user, $dom] = array_pad(explode('@', $row['email'], 2), 2, '');
        $cajeros[] = [
            'id'          => intval($row['idempleado']),
            'nombre'      => trim($row['nombre'] . ' ' . $row['apellido']),
            'rol'         => $row['rol'] ?: 'Cajero',
            'email_oculto'=> substr($user, 0, 2) . '***@' . $dom,
        ];
    }
}

echo json_encode(['ok' => true, 'cajeros' => $cajeros], JSON_UNESCAPED_UNICODE);
