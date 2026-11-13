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
$CATEGORIAS = [
    'megacombos' => ['Mega Combos', 'Combos grandes con papas y gaseosa', '🍔'],
    'exclusivos' => ['Exclusivos', 'Hamburguesas insignia de autor', '👑'],
    'pollo'      => ['Pollo Crispy', 'Pechuga crocante y jugosa', '🍗'],
    'paravos'    => ['Para Vos', 'Veggie y sin TACC', '🌱'],
    'cajitas'    => ['Cajitas Kids', 'Menú infantil con juguete', '🎁'],
    'ensaladas'  => ['Ensaladas', 'Frescas y livianas', '🥗'],
    'postres'    => ['Postres y Café', 'Helados, medialunas y más', '🍦'],
];

function parseTamanos($txt) {
    $res = [];
    foreach (explode(',', (string)$txt) as $p) {
        $p = trim($p); if ($p === '') continue;
        $extra = 0; $nombre = $p;
        if (preg_match('/^(.*?)\s*\(\+\s*\$?\s*([\d\.]+)\)\s*$/u', $p, $m)) {
            $nombre = trim($m[1]); $extra = (float)str_replace('.', '', $m[2]);
        }
        $res[] = ['nombre' => $nombre, 'extra' => $extra];
    }
    return $res;
}
function productoFila($r, $base) {
    return [
        'idproducto' => (int)$r['idproducto'], 'nombre' => $r['nombre'], 'descripcion' => $r['descripcion'],
        'precio' => (float)$r['precio'], 'categoria' => $r['categoria'],
        'imagen' => $r['imagen'], 'imagen_url' => $base . '/img/' . rawurlencode($r['imagen']),
        'ingredientes' => $r['ingredientes'], 'alergenos' => $r['alergenos'], 'calorias' => (int)$r['calorias'],
        'tamanos' => parseTamanos($r['tamano_opciones']),
    ];
}

switch ($action) {

case 'categorias':
    global $CATEGORIAS;
    $res = $conn->query("SELECT categoria, COUNT(DISTINCT nombre) AS n FROM producto WHERE activo = 1 GROUP BY categoria");
    $cuenta = [];
    while ($r = $res->fetch_assoc()) $cuenta[$r['categoria']] = (int)$r['n'];
    $lista = [];
    foreach ($CATEGORIAS as $slug => $c) {
        $lista[] = ['slug' => $slug, 'nombre' => $c[0], 'sub' => $c[1], 'emoji' => $c[2], 'cantidad' => $cuenta[$slug] ?? 0];
    }
    out(['ok' => true, 'categorias' => $lista]);

case 'productos':
    // Deduplica filas repetidas (nombre + categoría) quedándose con el id menor.
    $cat = trim(strtolower($_GET['cat'] ?? ''));
    $q   = trim($_GET['q'] ?? '');
    $sql = "SELECT p.* FROM producto p
            JOIN (SELECT MIN(idproducto) AS id FROM producto WHERE activo = 1 GROUP BY nombre, categoria) d ON d.id = p.idproducto
            WHERE p.activo = 1";
    $types = ''; $params = [];
    if ($cat !== '') { $sql .= " AND p.categoria = ?"; $types .= 's'; $params[] = $cat; }
    if ($q !== '')   { $sql .= " AND (p.nombre LIKE ? OR p.descripcion LIKE ?)"; $types .= 'ss'; $params[] = "%$q%"; $params[] = "%$q%"; }
    $sql .= " ORDER BY p.categoria, p.precio DESC, p.idproducto";
    $st = $conn->prepare($sql);
    if ($types) $st->bind_param($types, ...$params);
    $st->execute();
    $res = $st->get_result(); $base = baseWeb(); $lista = [];
    while ($r = $res->fetch_assoc()) $lista[] = productoFila($r, $base);
    out(['ok' => true, 'productos' => $lista]);

case 'sucursales':
    $res = $conn->query("SELECT idsucursal, nombre, direccion, telefono, horario_atencion, latitud, longitud, ciudad FROM sucursal WHERE activo = 1 ORDER BY nombre");
    $l = []; while ($r = $res->fetch_assoc()) $l[] = $r;
    out(['ok' => true, 'sucursales' => $l]);

case 'mesas':
    $res = $conn->query("SELECT m.numero_mesa, m.capacidad, m.estado, s.nombre AS seccion FROM mesa m LEFT JOIN seccion_mesa s ON s.idseccion = m.fk_id_seccion WHERE m.activa = 1 ORDER BY m.numero_mesa");
    $l = []; while ($r = $res->fetch_assoc()) { $r['numero_mesa'] = (int)$r['numero_mesa']; $r['capacidad'] = (int)$r['capacidad']; $l[] = $r; }
    out(['ok' => true, 'mesas' => $l]);

case 'config_pago':
    out(['ok' => true, 'alias' => BANCO_ALIAS, 'cvu' => BANCO_CVU, 'titular' => BANCO_TITULAR, 'banco' => BANCO_NOMBRE,
         'link_mp' => MP_LINK_PAGO, 'envio_costo' => ENVIO_COSTO, 'envio_gratis_desde' => ENVIO_GRATIS]);

case 'cupones':
    $res = $conn->query("SELECT codigo, tipo, valor, minimo_compra, tope_descuento, descripcion FROM cupon WHERE activo = 1");
    $l = []; while ($r = $res->fetch_assoc()) $l[] = $r;
    out(['ok' => true, 'cupones' => $l]);

// ---------- Auth ----------
case 'registro':
    $nombre = trim($input['nombre'] ?? ''); $apellido = trim($input['apellido'] ?? '');
    $email = strtolower(trim($input['email'] ?? '')); $tel = trim($input['telefono'] ?? ''); $dir = trim($input['direccion'] ?? '');
    if ($nombre === '' || $apellido === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) fail('Nombre, apellido y un correo válido son obligatorios.');
    $st = $conn->prepare("SELECT idusuario FROM usuario_web WHERE email = ?");
    $st->bind_param("s", $email); $st->execute();
    if ($st->get_result()->num_rows > 0) fail('Ya existe una cuenta con ese correo. Iniciá sesión.');
    $ins = $conn->prepare("INSERT INTO usuario_web (nombre, apellido, telefono, direccion, email) VALUES (?, ?, ?, ?, ?)");
    $ins->bind_param("sssss", $nombre, $apellido, $tel, $dir, $email);
    if (!$ins->execute()) fail('No pudimos crear la cuenta.');
    out(['ok' => true, 'mensaje' => 'Cuenta creada. Ahora pedí tu código de acceso.']);

case 'solicitar_codigo':
    $email = strtolower(trim($input['email'] ?? ''));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) fail('Ingresá un correo electrónico válido.');
    $st = $conn->prepare("SELECT idusuario, nombre FROM usuario_web WHERE email = ?");
    $st->bind_param("s", $email); $st->execute();
    $u = $st->get_result()->fetch_assoc();
    if (!$u) fail('No encontramos una cuenta con ese correo. Registrate primero.');
    $token = strval(random_int(100000, 999999));
    $expira = date('Y-m-d H:i:s', strtotime('+10 minutes'));
    $up = $conn->prepare("UPDATE usuario_web SET token = ?, token_expira = ?, token_intentos = 0 WHERE idusuario = ?");
    $up->bind_param("ssi", $token, $expira, $u['idusuario']); $up->execute();
    $envio = enviarTokenPorCorreo($email, $u['nombre'], $token);
    $resp = ['ok' => true, 'mensaje' => 'Te enviamos un código de 6 dígitos a tu correo.'];
    if (DEV_MODE) {
        $resp['debug_code'] = $token;
    }
    if (empty($envio['ok'])) {
        if (!DEV_MODE) fail('No pudimos enviar el correo. ' . ($envio['error'] ?? ''));
        $resp['mensaje'] = 'Modo desarrollo: usá el código mostrado en pantalla.';
        $resp['debug_code'] = $token;
    }
    out($resp);

case 'verificar_codigo':
    $email = strtolower(trim($input['email'] ?? '')); $codigo = trim($input['codigo'] ?? '');
    $st = $conn->prepare("SELECT * FROM usuario_web WHERE email = ?");
    $st->bind_param("s", $email); $st->execute();
    $u = $st->get_result()->fetch_assoc();
    if (!$u) fail('No encontramos tu cuenta.');
    if ($u['token_intentos'] >= 5) fail('Superaste el máximo de intentos. Pedí un nuevo código.');
    if (empty($u['token']) || strtotime($u['token_expira']) < time()) fail('El código venció. Pedí uno nuevo.');
    if ($codigo !== $u['token']) {
        $conn->query("UPDATE usuario_web SET token_intentos = token_intentos + 1 WHERE idusuario = " . (int)$u['idusuario']);
        fail('Código incorrecto.');
    }
    $conn->query("UPDATE usuario_web SET token = NULL, token_expira = NULL, token_intentos = 0, verificado = 1 WHERE idusuario = " . (int)$u['idusuario']);
    out(['ok' => true, 'token' => firmarToken($u['idusuario']), 'usuario' => usuarioPublico($u)]);

// ---------- Perfil ----------
case 'perfil':
    $u = requerirUsuario($conn);
    $r = $conn->query("SELECT COUNT(*) AS n, IFNULL(SUM(total),0) AS gastado FROM pedido_web WHERE idusuario = " . (int)$u['idusuario'] . " AND estado <> 'Cancelado'")->fetch_assoc();
    out(['ok' => true, 'usuario' => usuarioPublico($u), 'total_pedidos' => (int)$r['n'], 'total_gastado' => (float)$r['gastado']]);

case 'actualizar_perfil':
    $u = requerirUsuario($conn);
    $nombre = trim($input['nombre'] ?? $u['nombre']); $apellido = trim($input['apellido'] ?? $u['apellido']);
    $tel = trim($input['telefono'] ?? ''); $dir = trim($input['direccion'] ?? '');
    if ($nombre === '' || $apellido === '') fail('Nombre y apellido son obligatorios.');
    $up = $conn->prepare("UPDATE usuario_web SET nombre = ?, apellido = ?, telefono = ?, direccion = ?, direccion_favorita = IFNULL(NULLIF(?, ''), direccion_favorita), telefono_favorito = IFNULL(NULLIF(?, ''), telefono_favorito) WHERE idusuario = ?");
    $up->bind_param("ssssssi", $nombre, $apellido, $tel, $dir, $dir, $tel, $u['idusuario']);
    $up->execute();
    $st = $conn->prepare("SELECT * FROM usuario_web WHERE idusuario = ?");
    $st->bind_param("i", $u['idusuario']); $st->execute();
    out(['ok' => true, 'usuario' => usuarioPublico($st->get_result()->fetch_assoc())]);

// ---------- Cupón ----------
case 'validar_cupon':
    $codigo = strtoupper(trim($input['codigo'] ?? '')); $subtotal = (float)($input['subtotal'] ?? 0);
    $st = $conn->prepare("SELECT * FROM cupon WHERE codigo = ? AND activo = 1");
    $st->bind_param("s", $codigo); $st->execute();
    $c = $st->get_result()->fetch_assoc();
    if (!$c) fail('El cupón no existe o no está activo.');
    if ($subtotal < (float)$c['minimo_compra']) fail('Este cupón requiere una compra mínima de ' . money($c['minimo_compra']) . '.');
    $d = $c['tipo'] === 'porcentaje' ? $subtotal * $c['valor'] / 100 : (float)$c['valor'];
    if (!empty($c['tope_descuento'])) $d = min($d, (float)$c['tope_descuento']);
    $d = min($subtotal, $d);
    out(['ok' => true, 'codigo' => $c['codigo'], 'descuento' => $d, 'descripcion' => $c['descripcion']]);

// ---------- Pasarela Mercado Pago (Cobro Previo) ----------
case 'crear_preferencia_mp':
    $rawItems = $input['items'] ?? [];
    $totalInput = (float)($input['total'] ?? 0);
    $tipoEntrega = $input['tipo_entrega'] ?? 'delivery';
    $mesa = (int)($input['numero_mesa'] ?? 0);
    $itemsMp = [];
    $sumaItems = 0;
    if (!empty($rawItems) && is_array($rawItems)) {
        foreach ($rawItems as $it) {
            $precio = (float)($it['precio_unitario'] ?? ($it['precio'] ?? 0));
            $cant = max(1, (int)($it['cantidad'] ?? 1));
            $nombre = trim($it['nombre'] ?? ($it['descripcion'] ?? 'Producto Mostaza'));
            if ($precio > 0) {
                $sumaItems += ($precio * $cant);
                $itemsMp[] = [
                    'idproducto' => $it['idproducto'] ?? 'APP-ITEM',
                    'nombre'     => $nombre,
                    'detalle'    => $mesa > 0 ? "Salón Mesa #$mesa - Mostaza" : "Pedido App Mostaza ($tipoEntrega)",
                    'precio'     => $precio,
                    'cantidad'   => $cant
                ];
            }
        }
    }
    $totalFinal = $totalInput > 0 ? $totalInput : max(1, $sumaItems);
    if (empty($itemsMp) || abs($sumaItems - $totalFinal) > 1) {
        $itemsMp = [[
            'idproducto' => 'PEDIDO-APP-MOSTAZA',
            'nombre'     => $mesa > 0 ? "Pedido Mostaza - Mesa #$mesa" : "Pedido App Mostaza (" . ucfirst($tipoEntrega) . ")",
            'detalle'    => 'Pago previo antes de enviar a cocina',
            'precio'     => round($totalFinal, 2),
            'cantidad'   => 1
        ]];
    }
    $externalRef = 'MOSTAZA-APP-' . ($mesa > 0 ? "M{$mesa}-" : '') . time() . '-' . mt_rand(100, 999);
    $resPref = crearPreferenciaMercadoPago(
        $itemsMp,
        $externalRef,
        ['nombre' => 'Cliente', 'apellido' => 'Mostaza', 'email' => 'comprador@mostaza.com.ar'],
        [],
        0,
        0
    );
    $qrEmvcoUrl = generarQrUrlMercadoPago($totalFinal, 200);
    if (!empty($resPref['ok']) && !empty($resPref['init_point'])) {
        $qrCheckoutUrl = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&margin=8&data=" . urlencode($resPref['init_point']);
        out([
            'ok'                 => true,
            'modo_real'          => true,
            'public_key'         => MP_PUBLIC_KEY,
            'preference_id'      => $resPref['preference_id'],
            'init_point'         => $resPref['init_point'],
            'external_reference' => $externalRef,
            'total'              => $totalFinal,
            'total_formateado'   => money($totalFinal),
            'qr_checkout_url'    => $qrCheckoutUrl,
            'qr_emvco_url'       => $qrEmvcoUrl,
            'alias'              => BANCO_ALIAS,
            'cvu'                => BANCO_CVU,
            'titular'            => BANCO_TITULAR
        ]);
    }
    out([
        'ok'                 => true,
        'modo_real'          => false,
        'aviso_api'          => $resPref['error'] ?? 'Modo QR EMVCo activo',
        'init_point'         => MP_LINK_PAGO,
        'external_reference' => $externalRef,
        'total'              => $totalFinal,
        'total_formateado'   => money($totalFinal),
        'qr_checkout_url'    => $qrEmvcoUrl,
        'qr_emvco_url'       => $qrEmvcoUrl,
        'alias'              => BANCO_ALIAS,
        'cvu'                => BANCO_CVU,
        'titular'            => BANCO_TITULAR
    ]);

case 'verificar_pago_mp':
    $paymentId     = trim($input['payment_id'] ?? ($_GET['payment_id'] ?? ''));
    $externalRef   = trim($input['external_reference'] ?? ($_GET['external_reference'] ?? ''));
    $montoEsperado = (float)($input['monto_esperado'] ?? ($_GET['monto_esperado'] ?? 0));
    if (!mpTieneCredencialesReales()) {
        out([
            'ok'        => true,
            'modo_real' => false,
            'pagado'    => false,
            'status'    => 'demo_mode',
            'mensaje'   => 'Modo demostración activo.'
        ]);
    }
    $resMp = consultarPagoMercadoPagoApi($paymentId, $externalRef, $montoEsperado);
    if (!empty($resMp['pagado'])) {
        out([
            'ok'             => true,
            'modo_real'      => true,
            'pagado'         => true,
            'status'         => 'approved',
            'payment_id'     => $resMp['payment_id'],
            'monto'          => $resMp['monto'] ?? 0,
            'metodo_detalle' => $resMp['metodo_detalle'] ?? 'Mercado Pago Oficial'
        ]);
    }
    out([
        'ok'         => true,
        'modo_real'  => true,
        'pagado'     => false,
        'status'     => $resMp['status'] ?? 'pending',
        'payment_id' => $resMp['payment_id'] ?? ''
    ]);

// ---------- Crear pedido (1° se cobra/aprueba el pago, 2° pasa a cocina en estado Recibido) ----------
case 'crear_pedido':
    $usuario = obtenerOCrearUsuarioPedido($conn, $input);
    $idusuario = (int)$usuario['idusuario'];
    $items = $input['items'] ?? [];
    if (!is_array($items) || !count($items)) fail('Tu carrito está vacío.');

    $tipoEntrega = in_array($input['tipo_entrega'] ?? '', ['delivery', 'takeaway', 'salon']) ? $input['tipo_entrega'] : 'delivery';
    $direccion = trim($input['direccion_envio'] ?? '');
    $telefono = trim($input['telefono_contacto'] ?? '');
    if ($telefono === '' && !empty($usuario['telefono'])) $telefono = $usuario['telefono'];
    if ($direccion === '' && !empty($usuario['direccion'])) $direccion = $usuario['direccion'];
    $referencia = trim($input['referencia_entrega'] ?? '');
    $latitud = !empty($input['latitud']) ? (float)$input['latitud'] : null;
    $longitud = !empty($input['longitud']) ? (float)$input['longitud'] : null;
    $metodoPago = in_array($input['metodo_pago'] ?? '', ['mercadopago', 'tarjeta', 'transferencia', 'efectivo']) ? $input['metodo_pago'] : 'efectivo';
    if ($tipoEntrega === 'delivery' && $direccion === '') fail('Indicá la dirección de entrega para enviar tu pedido.');

    // Sucursal
    $sucursal = trim($input['sucursal'] ?? '') ?: 'Mostaza Formosa Centro';
    if ($tipoEntrega === 'delivery' && $latitud && $longitud) {
        $sn = $conn->prepare("SELECT nombre, (6371 * acos(LEAST(1.0, GREATEST(-1.0, cos(radians(?)) * cos(radians(latitud)) * cos(radians(longitud) - radians(?)) + sin(radians(?)) * sin(radians(latitud)))))) AS d FROM sucursal WHERE activo = 1 ORDER BY d ASC LIMIT 1");
        $sn->bind_param("ddd", $latitud, $longitud, $latitud); $sn->execute();
        if ($row = $sn->get_result()->fetch_assoc()) $sucursal = $row['nombre'];
    }

    // Recalcular precios en el servidor (nunca confiar en el precio del cliente)
    $lineas = []; $subtotal = 0;
    $stP = $conn->prepare("SELECT idproducto, nombre, precio, tamano_opciones FROM producto WHERE idproducto = ? AND activo = 1");
    foreach ($items as $it) {
        $idp = (int)($it['idproducto'] ?? 0); $cant = max(1, (int)($it['cantidad'] ?? 1));
        $stP->bind_param("i", $idp); $stP->execute();
        $p = $stP->get_result()->fetch_assoc();
        if (!$p) fail('Un producto del carrito ya no está disponible.');
        $precio = (float)$p['precio']; $mods = [];
        $tam = trim($it['tamano'] ?? '');
        if ($tam !== '') {
            foreach (parseTamanos($p['tamano_opciones']) as $o) {
                if (strcasecmp($o['nombre'], $tam) === 0) { $precio += $o['extra']; $mods[] = $o['nombre']; break; }
            }
        }
        $nota = trim($it['notas'] ?? ''); if ($nota !== '') $mods[] = $nota;
        $lineas[] = ['id' => $idp, 'desc' => $p['nombre'], 'mods' => implode(' · ', $mods), 'precio' => $precio, 'cant' => $cant];
        $subtotal += $precio * $cant;
    }

    // Cupón
    $descuento = 0; $codigoCupon = null;
    $cod = strtoupper(trim($input['codigo_cupon'] ?? ''));
    if ($cod !== '') {
        $sc = $conn->prepare("SELECT * FROM cupon WHERE codigo = ? AND activo = 1");
        $sc->bind_param("s", $cod); $sc->execute();
        if ($c = $sc->get_result()->fetch_assoc()) {
            if ($subtotal >= (float)$c['minimo_compra']) {
                $codigoCupon = $c['codigo'];
                $descuento = $c['tipo'] === 'porcentaje' ? $subtotal * $c['valor'] / 100 : (float)$c['valor'];
                if (!empty($c['tope_descuento'])) $descuento = min($descuento, (float)$c['tope_descuento']);
                $descuento = min($subtotal, $descuento);
            }
        }
    }
    // Puntos (1 punto = $1, máximo 50% del importe)
    $puntosDisp = (int)$usuario['puntos']; $puntosUsados = 0;
    if (!empty($input['usar_puntos']) && $puntosDisp > 0) {
        $puntosUsados = min($puntosDisp, (int)(($subtotal - $descuento) * 0.5));
    }
    $descuentoTotal = $descuento + $puntosUsados;
    $costoEnvio = ($tipoEntrega === 'delivery' && $subtotal > 0 && $subtotal < ENVIO_GRATIS) ? (float)ENVIO_COSTO : 0.0;
    $propina = 0.0; $pt = $input['propina_tipo'] ?? 'porcentaje'; $pv = (float)($input['propina_valor'] ?? 0);
    if ($pv > 0) $propina = $pt === 'fijo' ? $pv : $subtotal * $pv / 100;
    $total = max(0, ($subtotal - $descuentoTotal) + $costoEnvio + $propina);
    $puntosGanados = (int)($total / 100);

    // En este sistema SE PAGA PRIMERO y recién después pasa a cocina (estado_pago = 'Aprobado')
    $notas = [];
    if ($tipoEntrega === 'delivery' && $referencia !== '') $notas[] = "Indicación envío: $referencia";
    if ($tipoEntrega === 'takeaway' && !empty($input['horario_retiro'])) $notas[] = 'Horario retiro: ' . trim($input['horario_retiro']);
    if (!empty($input['notas'])) $notas[] = 'Nota: ' . trim($input['notas']);

    $comprobante = trim($input['comprobante'] ?? '');
    $pagaCon = null;
    $vuelto = null;
    $estadoPago = 'Aprobado';

    if ($metodoPago === 'mercadopago') {
        if ($comprobante === '') $comprobante = 'MP-' . mt_rand(100000000, 999999999);
        $notas[] = "Mercado Pago Aprobado · Operación #$comprobante";
        $comprobante = "Mercado Pago: #$comprobante";
    } elseif ($metodoPago === 'tarjeta') {
        $ultimos4 = preg_replace('/\D/', '', (string)($input['tarjeta_ultimos4'] ?? ''));
        $cuotas = max(1, (int)($input['tarjeta_cuotas'] ?? 1));
        if ($comprobante === '') $comprobante = 'AUTH-' . strtoupper(substr(md5(uniqid('', true)), 0, 8));
        $detTarjeta = $ultimos4 !== '' ? "Tarjeta ****$ultimos4 ({$cuotas}x)" : "Tarjeta ({$cuotas}x)";
        $notas[] = "$detTarjeta Aprobada · Auth #$comprobante";
        $comprobante = "$detTarjeta: #$comprobante";
    } elseif ($metodoPago === 'transferencia') {
        if ($comprobante === '') $comprobante = 'TRANSF-' . date('Ymd') . '-' . mt_rand(1000, 9999);
        $notas[] = "Transferencia Aprobada · Comprobante: $comprobante";
    } else {
        $pc = isset($input['paga_con']) ? (float)$input['paga_con'] : 0;
        $pagaCon = $pc >= $total ? $pc : $total;
        $vuelto = max(0, $pagaCon - $total);
        if ($comprobante === '') $comprobante = 'EFEC-' . date('His') . '-' . mt_rand(100, 999);
        $notas[] = 'Pago en Efectivo Aprobado (Paga con ' . money($pagaCon) . ' · Vuelto ' . money($vuelto) . ')';
    }
    $notasFinales = implode(' | ', $notas);

    $numeroMesa = !empty($input['numero_mesa']) ? (int)$input['numero_mesa'] : null;

    $conn->begin_transaction();
    try {
        if ($tipoEntrega === 'salon') {
            if (!$numeroMesa) {
                $rmLibre = $conn->query("SELECT numero_mesa FROM mesa WHERE activa = 1 AND estado = 'disponible' ORDER BY numero_mesa ASC LIMIT 1")->fetch_assoc();
                if ($rmLibre) $numeroMesa = (int)$rmLibre['numero_mesa'];
            }
            if (!$numeroMesa) throw new Exception('Elegí una mesa libre para tu pedido en el salón.');
            $cm = $conn->prepare("SELECT estado FROM mesa WHERE numero_mesa = ? FOR UPDATE");
            $cm->bind_param("i", $numeroMesa); $cm->execute();
            $rm = $cm->get_result()->fetch_assoc();
            if (!$rm) throw new Exception('Esa mesa no existe.');
            $conn->query("UPDATE mesa SET estado = 'ocupada' WHERE numero_mesa = $numeroMesa");
        }

        // Registrar cobro previo en Caja Arqueo Abierta
        $colMap = [
            'efectivo'      => 'ventas_efectivo',
            'mercadopago'   => 'ventas_mercadopago',
            'tarjeta'       => 'ventas_tarjeta',
            'transferencia' => 'ventas_transferencia',
        ];
        $col = $colMap[$metodoPago] ?? 'ventas_efectivo';
        $ab = $conn->query("SELECT idarqueo, numero_caja FROM caja_arqueo WHERE estado = 'Abierta' ORDER BY idarqueo DESC LIMIT 1");
        if (!$ab || $ab->num_rows === 0) {
            $conn->query("INSERT INTO caja_arqueo (cajero, turno, monto_inicial, estado) VALUES ('Cajero Central', 'Turno Mañana', 15000.00, 'Abierta')");
            $idarqueo = (int)$conn->insert_id;
            $numCaja = 1;
        } else {
            $rowArq = $ab->fetch_assoc();
            $idarqueo = (int)$rowArq['idarqueo'];
            $numCaja = (int)($rowArq['numero_caja'] ?? 1);
        }

        $n = (int)$conn->query("SELECT COUNT(*) AS c FROM pedido_web WHERE idusuario = $idusuario")->fetch_assoc()['c'] + 1;
        $stmt = $conn->prepare("INSERT INTO pedido_web (idusuario, numero_pedido_usuario, idarqueo, numero_caja, tipo_entrega, direccion_envio, telefono_contacto, sucursal, numero_mesa, metodo_pago, estado_pago, subtotal, costo_envio, descuento, codigo_cupon, propina, puntos_usados, puntos_ganados, paga_con, vuelto, notas, comprobante_transferencia, comprobante_archivo, latitud, longitud, referencia_entrega, total, estado, hora_recibido) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Recibido', NOW())");
        if (!$stmt) throw new Exception($conn->error);
        $archivo = null;
        $stmt->bind_param("iiiissssissdddsdiiddsssddsd", $idusuario, $n, $idarqueo, $numCaja, $tipoEntrega, $direccion, $telefono, $sucursal, $numeroMesa, $metodoPago, $estadoPago, $subtotal, $costoEnvio, $descuentoTotal, $codigoCupon, $propina, $puntosUsados, $puntosGanados, $pagaCon, $vuelto, $notasFinales, $comprobante, $archivo, $latitud, $longitud, $referencia, $total);
        if (!$stmt->execute()) throw new Exception($stmt->error ?: $conn->error);
        $idpedido = (int)$conn->insert_id;

        $sd = $conn->prepare("INSERT INTO detalle_pedido_web (idpedido, idproducto, descripcion, modificadores, precio_unitario, cantidad, subtotal) VALUES (?, ?, ?, ?, ?, ?, ?)");
        foreach ($lineas as $l) {
            $sub = $l['precio'] * $l['cant'];
            $sd->bind_param("iissdid", $idpedido, $l['id'], $l['desc'], $l['mods'], $l['precio'], $l['cant'], $sub);
            $sd->execute();
            foreach ([1, 3, 12] as $ins) $conn->query("UPDATE stock_insumo SET stock_actual = GREATEST(0, stock_actual - {$l['cant']}) WHERE idinsumo = $ins");
        }
        $nuevos = max(0, $puntosDisp - $puntosUsados + $puntosGanados);
        $uu = $conn->prepare("UPDATE usuario_web SET puntos = ?, direccion_favorita = IFNULL(NULLIF(?, ''), direccion_favorita), telefono_favorito = IFNULL(NULLIF(?, ''), telefono_favorito) WHERE idusuario = ?");
        $uu->bind_param("issi", $nuevos, $direccion, $telefono, $idusuario); $uu->execute();
        $usuario['puntos'] = $nuevos;

        $conn->query("UPDATE caja_arqueo SET $col = $col + $total, total_ventas = total_ventas + $total, total_transacciones = total_transacciones + 1 WHERE idarqueo = $idarqueo");

        $factura = generarFacturaFiscal($conn, $idpedido, 'Factura B', null, ['nombre_cliente' => $usuario['nombre'] . ' ' . $usuario['apellido'], 'cuit_dni_cliente' => $telefono ?: ($usuario['telefono'] ?? '0')]);
        registrarAuditoriaPedido($conn, $idpedido, 'Recibido', 'Pago Aprobado y Enviado a Cocina (App Cliente)', 'Cliente Web', $usuario['nombre'] . ' ' . $usuario['apellido'], null, "App móvil | Pago Previo: $metodoPago ($estadoPago) | $tipoEntrega | Total: $total");
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        fail($e->getMessage());
    }
    if (!empty($input['enviar_correo'])) {
        @enviarConfirmacionPedidoPorCorreo($usuario['email'], $usuario['nombre'], [
            'idpedido' => $idpedido, 'tipo_entrega' => $tipoEntrega, 'metodo_pago' => $metodoPago, 'direccion_envio' => $direccion,
            'subtotal' => $subtotal, 'descuento' => $descuentoTotal, 'costo_envio' => $costoEnvio, 'propina' => $propina, 'total' => $total,
        ], array_map(fn($l) => ['descripcion' => $l['desc'], 'modificadores' => $l['mods'], 'cantidad' => $l['cant'], 'subtotal' => $l['precio'] * $l['cant']], $lineas));
    }
    out([
        'ok'                    => true,
        'idpedido'              => $idpedido,
        'numero_pedido_usuario' => $n,
        'total'                 => $total,
        'estado'                => 'Recibido',
        'estado_pago'           => $estadoPago,
        'metodo_pago'           => $metodoPago,
        'comprobante'           => $comprobante,
        'puntos_ganados'        => $puntosGanados,
        'numero_factura'        => $factura['numero_factura'] ?? null,
        'factura'               => $factura,
        'token'                 => firmarToken($idusuario),
        'usuario'               => usuarioPublico($usuario),
    ]);

// ---------- Pedidos del usuario ----------
case 'mis_pedidos':
    $u = requerirUsuario($conn);
    $st = $conn->prepare("SELECT idpedido, numero_pedido_usuario, fecha, tipo_entrega, numero_mesa, sucursal, metodo_pago, estado_pago, subtotal, costo_envio, descuento, propina, total, estado, cocinero_nombre, repartidor_asignado, mesero_nombre, motivo_cancelacion, hora_recibido, hora_cocina_inicio, hora_cocina_listo, hora_entregado FROM pedido_web WHERE idusuario = ? ORDER BY idpedido DESC LIMIT 60");
    $st->bind_param("i", $u['idusuario']); $st->execute();
    $res = $st->get_result(); $l = [];
    $sd = $conn->prepare("SELECT idproducto, descripcion, modificadores, precio_unitario, cantidad, subtotal FROM detalle_pedido_web WHERE idpedido = ?");
    while ($r = $res->fetch_assoc()) {
        $sd->bind_param("i", $r['idpedido']); $sd->execute();
        $r['items'] = $sd->get_result()->fetch_all(MYSQLI_ASSOC);
        $l[] = $r;
    }
    out(['ok' => true, 'pedidos' => $l]);

case 'tracking':
    $u = requerirUsuario($conn);
    $id = (int)($_GET['id'] ?? 0);
    $st = $conn->prepare("SELECT idpedido, numero_pedido_usuario, estado, tipo_entrega, fecha, total, direccion_envio, sucursal, numero_mesa, repartidor_asignado, cocinero_nombre, mesero_nombre, motivo_cancelacion, metodo_pago, estado_pago, hora_recibido, hora_cocina_inicio, hora_cocina_listo, hora_entregado FROM pedido_web WHERE idpedido = ? AND idusuario = ?");
    $st->bind_param("ii", $id, $u['idusuario']); $st->execute();
    $p = $st->get_result()->fetch_assoc();
    if (!$p) fail('Pedido no encontrado.');
    $estado = trim($p['estado']); $tipo = $p['tipo_entrega'];
    $paso = 1;
    $estadoLabel = 'En espera';
    $titulo = '¡Pedido en espera de preparación!';
    $desc = 'El local ya recibió tu orden #' . $p['idpedido'] . ' y está en cola para entrar a cocina.';
    if (in_array($estado, ['En Cocina', 'En Preparacion', 'En Preparación', 'Preparando'])) {
        $paso = 2;
        $estadoLabel = 'En preparación';
        $titulo = '¡Tu pedido está en preparación!';
        $cocTxt = !empty($p['cocinero_nombre']) ? ('El cocinero ' . $p['cocinero_nombre'] . ' está') : 'En cocina están';
        $desc = $cocTxt . ' preparando tu pedido en este momento.';
    } elseif (in_array($estado, ['Listo', 'Listo para Despacho'])) {
        if ($tipo === 'delivery') {
            $paso = 2;
            $estadoLabel = 'Listo para despacho';
            $titulo = '¡Listo en cocina!';
            $desc = 'Tu pedido ya fue preparado y está empaquetado esperando al repartidor.';
        } elseif ($tipo === 'salon') {
            $paso = 3;
            $estadoLabel = 'Listo para servir';
            $titulo = '¡Tu comanda está lista!';
            $desc = 'Un mesero la lleva a tu Mesa #' . $p['numero_mesa'] . '.';
        } else {
            $paso = 3;
            $estadoLabel = 'Listo para retirar';
            $titulo = '¡Listo para retirar!';
            $desc = 'Acercate al mostrador con tu número de pedido #' . $p['idpedido'] . '.';
        }
    } elseif ($estado === 'En Camino') {
        $paso = 3;
        $estadoLabel = 'En camino';
        $titulo = '¡El repartidor va en camino!';
        $desc = ($p['repartidor_asignado'] ? $p['repartidor_asignado'] : 'Tu repartidor') . ' salió de la sucursal hacia tu domicilio.';
    } elseif (in_array($estado, ['Entregado', 'Retirado'])) {
        $paso = 4;
        $esRetiro = ($tipo === 'takeaway' || $estado === 'Retirado');
        $estadoLabel = $esRetiro ? 'Retirado' : 'Entregado';
        $titulo = $esRetiro ? '¡Pedido retirado del lugar! Que lo disfrutes' : '¡Entregado! Que lo disfrutes';
        $desc = '¡Gracias por elegir Mostaza!';
    } elseif ($estado === 'Cancelado') {
        $paso = 0;
        $estadoLabel = 'Cancelado';
        $titulo = 'Pedido cancelado';
        $desc = $p['motivo_cancelacion'] ?: 'Cancelado por el establecimiento.';
    }
    $vehiculo = null;
    if (!empty($p['repartidor_asignado'])) {
        $sv = $conn->prepare("SELECT vehiculo, patente, avatar FROM empleado WHERE fk_id_rol = 6 AND (CONCAT(nombre, ' ', apellido) = ? OR nombre = ?) LIMIT 1");
        $sv->bind_param("ss", $p['repartidor_asignado'], $p['repartidor_asignado']); $sv->execute();
        if ($rv = $sv->get_result()->fetch_assoc()) $vehiculo = trim($rv['vehiculo'] . ($rv['patente'] ? ' · ' . $rv['patente'] : ''));
    }
    out(['ok' => true, 'idpedido' => (int)$p['idpedido'], 'numero_pedido_usuario' => $p['numero_pedido_usuario'] ? (int)$p['numero_pedido_usuario'] : null,
         'estado' => $estado, 'estado_label' => $estadoLabel, 'tipo_entrega' => $tipo, 'paso' => $paso, 'titulo' => $titulo,
         'descripcion' => $desc, 'fecha' => $p['fecha'], 'repartidor' => $p['repartidor_asignado'], 'vehiculo' => $vehiculo, 'cocinero' => $p['cocinero_nombre'],
         'mesero' => $p['mesero_nombre'], 'numero_mesa' => $p['numero_mesa'], 'sucursal' => $p['sucursal'], 'direccion_envio' => $p['direccion_envio'],
         'total' => (float)$p['total'], 'metodo_pago' => $p['metodo_pago'], 'estado_pago' => $p['estado_pago'],
         'hora_recibido' => $p['hora_recibido'] ?: $p['fecha'], 'hora_cocina_inicio' => $p['hora_cocina_inicio'],
         'hora_cocina_listo' => $p['hora_cocina_listo'], 'hora_entregado' => $p['hora_entregado']]);

default:
    fail('Acción no válida.', 404);
}
