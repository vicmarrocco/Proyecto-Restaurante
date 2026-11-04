<?php
// ============================================================
// api/clientes/buscar.php
// Búsqueda de clientes por DNI, correo o nombre para Club Mostaza.
// ============================================================
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once(__DIR__ . '/../../conexion.php');

$q = htmlspecialchars(trim($_GET['q'] ?? ''));

if (empty($q)) {
    echo json_encode(['ok' => false, 'error' => 'Ingresá un término de búsqueda.']);
    exit();
}

$like = "%$q%";
$stmt = $conn->prepare("SELECT idusuario, nombre, apellido, email, telefono, puntos FROM usuario_web WHERE email LIKE ? OR nombre LIKE ? OR telefono LIKE ? LIMIT 5");
$stmt->bind_param("sss", $like, $like, $like);
$stmt->execute();
$res = $stmt->get_result();

$clientes = [];
while ($u = $res->fetch_assoc()) {
    $clientes[] = [
        'idusuario' => intval($u['idusuario']),
        'nombre_completo' => $u['nombre'] . ' ' . $u['apellido'],
        'email' => $u['email'],
        'telefono' => $u['telefono'],
        'puntos' => intval($u['puntos'])
    ];
}

echo json_encode([
    'ok' => true,
    'total' => count($clientes),
    'clientes' => $clientes
]);
