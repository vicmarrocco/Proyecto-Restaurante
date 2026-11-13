<?php
// ============================================================
// mostaza_web/api/cliente/index.php
// API REST para la App Cliente (Expo). Rutas por ?action=
// Reutiliza conexion.php (generarFacturaFiscal, registrarAuditoriaPedido)
// y mail/enviar_correo.php (enviarTokenPorCorreo).
// ============================================================
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Auth-Token');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

require_once __DIR__ . '/../../conexion.php';
require_once __DIR__ . '/../../mail/enviar_correo.php';

// CAMBIAR en producción. DEV_MODE=true devuelve el código en la respuesta si falla el mail.
const APP_SECRET   = 'cambiar-esta-clave-secreta-mostaza';
const DEV_MODE     = true;
const TOKEN_DIAS   = 30;
const ENVIO_COSTO  = 1200;
const ENVIO_GRATIS = 25000;

$input  = json_decode(file_get_contents('php://input'), true) ?: [];
$action = $_GET['action'] ?? ($input['action'] ?? '');

function out($data, $code = 200) { http_response_code($code); echo json_encode($data, JSON_UNESCAPED_UNICODE); exit; }
function fail($msg, $code = 200) { out(['ok' => false, 'error' => $msg], $code); }
function money($n) { return '$' . number_format((float)$n, 0, ',', '.'); }

// ---------- Token firmado (sin tablas nuevas) ----------
function firmarToken($idusuario) {
    $exp = time() + TOKEN_DIAS * 86400;
    $payload = $idusuario . '|' . $exp;
    return base64_encode($payload) . '.' . hash_hmac('sha256', $payload, APP_SECRET);
}
function leerToken() {
    global $input;
    $h = $_SERVER['HTTP_AUTHORIZATION']
        ?? ($_SERVER['REDIRECT_HTTP_AUTHORIZATION']
        ?? ($_SERVER['HTTP_X_AUTH_TOKEN']
        ?? ($_GET['auth']
        ?? ($input['auth'] ?? ($input['auth_token'] ?? '')))));
    if (stripos($h, 'Bearer ') === 0) $h = substr($h, 7);
    if (!$h || strpos($h, '.') === false) return 0;
    [$b64, $sig] = explode('.', $h, 2);
    $payload = base64_decode($b64);
    if (!$payload || strpos($payload, '|') === false) return 0;
    $secrets = array_unique([APP_SECRET, 'cambiar-esta-clave-secreta-mostaza', 'etiv pwmy znxa ldmb']);
    $valido = false;
    foreach ($secrets as $sec) {
        if (hash_equals(hash_hmac('sha256', $payload, $sec), $sig)) { $valido = true; break; }
    }
    if (!$valido) return 0;
    [$id, $exp] = array_map('intval', explode('|', $payload));
    return $exp >= time() ? $id : 0;
}
function requerirUsuario($conn) {
    global $input;
    $id = leerToken();
    if (!$id) {
        $idFallback = (int)($input['idusuario'] ?? ($_GET['idusuario'] ?? 0));
        if ($idFallback > 0) $id = $idFallback;
    }
    if (!$id) fail('Sesión vencida. Volvé a iniciar sesión.', 401);
    $st = $conn->prepare("SELECT * FROM usuario_web WHERE idusuario = ?");
    $st->bind_param("i", $id); $st->execute();
    $u = $st->get_result()->fetch_assoc();
    if (!$u) fail('Usuario no encontrado.', 401);
    return $u;
}
function obtenerOCrearUsuarioPedido($conn, $input) {
    $id = leerToken();
    if (!$id && !empty($input['idusuario'])) $id = (int)$input['idusuario'];
    if ($id > 0) {
        $st = $conn->prepare("SELECT * FROM usuario_web WHERE idusuario = ?");
        $st->bind_param("i", $id); $st->execute();
        if ($u = $st->get_result()->fetch_assoc()) return $u;
    }
    $email = strtolower(trim($input['email_cliente'] ?? ''));
    if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $st = $conn->prepare("SELECT * FROM usuario_web WHERE email = ? LIMIT 1");
        $st->bind_param("s", $email); $st->execute();
        if ($u = $st->get_result()->fetch_assoc()) return $u;
    }
    $nombreCompleto = trim($input['nombre_cliente'] ?? '');
    $partes = preg_split('/\s+/', $nombreCompleto, 2);
    $nombre = !empty($partes[0]) ? $partes[0] : 'Cliente';
    $apellido = !empty($partes[1]) ? $partes[1] : 'Mostaza';
    $tel = trim($input['telefono_contacto'] ?? '');
    $dir = trim($input['direccion_envio'] ?? '');
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $resFirst = $conn->query("SELECT * FROM usuario_web ORDER BY idusuario ASC LIMIT 1");
        if ($resFirst && ($uFirst = $resFirst->fetch_assoc())) {
            return $uFirst;
        }
        $email = 'cliente.app@mostaza.com.ar';
    }
    $ins = $conn->prepare("INSERT INTO usuario_web (nombre, apellido, telefono, direccion, email, verificado) VALUES (?, ?, ?, ?, ?, 1)");
    $ins->bind_param("sssss", $nombre, $apellido, $tel, $dir, $email);
    if ($ins->execute()) {
        $nuevoId = (int)$conn->insert_id;
        $st = $conn->prepare("SELECT * FROM usuario_web WHERE idusuario = ?");
        $st->bind_param("i", $nuevoId); $st->execute();
        if ($u = $st->get_result()->fetch_assoc()) return $u;
    }
    fail('No se pudo identificar al cliente para el pedido.', 401);
}
function usuarioPublico($u) {
    return [
        'idusuario' => (int)$u['idusuario'], 'nombre' => $u['nombre'], 'apellido' => $u['apellido'],
        'email' => $u['email'], 'telefono' => $u['telefono'], 'direccion' => $u['direccion'],
        'direccion_favorita' => $u['direccion_favorita'], 'telefono_favorito' => $u['telefono_favorito'],
        'puntos' => (int)$u['puntos'], 'fecha_registro' => $u['fecha_registro'],
    ];
}
function baseWeb() {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    return $scheme . '://' . $_SERVER['HTTP_HOST'] . dirname(dirname(dirname($_SERVER['SCRIPT_NAME'])));
}

// ---------- Catálogo ----------
$CATEGORIAS = ( === 'mercadopago') ? 'ventas_mercadopago' : 'ventas_efectivo';
        
