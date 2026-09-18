<?php
session_start();
require('conexion.php');
require('mail/enviar_correo.php');

// Si ya está logueado, no tiene sentido estar acá
if (isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';
$ok = '';
$paso = 'email'; // 'email' o 'codigo'

function validar($data) {
    return htmlspecialchars(trim(stripslashes($data)));
}

function generarTokenYEnviar($conn, $email) {
    $stmt = $conn->prepare("SELECT idusuario, nombre FROM usuario_web WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        return ['ok' => false, 'error' => 'No encontramos una cuenta con ese correo. Registrate primero.'];
    }

    $user = $res->fetch_assoc();
    $token = strval(random_int(100000, 999999));
    $expira = date('Y-m-d H:i:s', strtotime('+10 minutes'));

    $upd = $conn->prepare("UPDATE usuario_web SET token = ?, token_expira = ?, token_intentos = 0 WHERE idusuario = ?");
    $upd->bind_param("ssi", $token, $expira, $user['idusuario']);
    $upd->execute();

    $envio = enviarTokenPorCorreo($email, $user['nombre'], $token);

    if (!$envio['ok']) {
        return ['ok' => false, 'error' => 'No pudimos enviar el correo. ' . $envio['error']];
    }

    return ['ok' => true, 'nombre' => $user['nombre']];
}

// ---------------------------------------------------------
// PASO 1: el usuario envía su email -> generamos y mandamos token
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'enviar_token') {
    $email = validar($_POST['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Ingresá un correo electrónico válido.';
        $paso = 'email';
    } else {
        $resultado = generarTokenYEnviar($conn, $email);
        if ($resultado['ok']) {
            $_SESSION['login_email'] = $email;
            $ok = 'Te enviamos un código de 6 dígitos a tu correo.';
            $paso = 'codigo';
        } else {
            $error = $resultado['error'];
            $paso = 'email';
        }
    }
}

// ---------------------------------------------------------
// Reenviar código (mismo email guardado en sesión)
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'reenviar_token') {
    $email = $_SESSION['login_email'] ?? '';
    if (empty($email)) {
        $error = 'Tu sesión de login expiró, ingresá tu correo de nuevo.';
        $paso = 'email';
    } else {
        $resultado = generarTokenYEnviar($conn, $email);
        if ($resultado['ok']) {
            $ok = 'Te reenviamos un nuevo código a tu correo.';
            $paso = 'codigo';
        } else {
            $error = $resultado['error'];
            $paso = 'email';
        }
    }
}

// ---------------------------------------------------------
// PASO 2: el usuario ingresa el código recibido
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'verificar_token') {
    $email = $_SESSION['login_email'] ?? '';
    $codigo = validar($_POST['codigo'] ?? '');

    if (empty($email)) {
        $error = 'Tu sesión de login expiró, ingresá tu correo de nuevo.';
        $paso = 'email';
    } elseif (empty($codigo)) {
        $error = 'Ingresá el código que te enviamos.';
        $paso = 'codigo';
    } else {
        $stmt = $conn->prepare("SELECT idusuario, nombre, apellido, token, token_expira, token_intentos FROM usuario_web WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows === 0) {
            $error = 'No encontramos tu cuenta.';
            $paso = 'email';
        } else {
            $user = $res->fetch_assoc();

            if ($user['token_intentos'] >= 5) {
                $error = 'Superaste el máximo de intentos. Pedí un nuevo código.';
                $paso = 'codigo';
            } elseif (empty($user['token']) || strtotime($user['token_expira']) < time()) {
                $error = 'El código venció. Pedí uno nuevo.';
                $paso = 'codigo';
            } elseif ($codigo !== $user['token']) {
                $inc = $conn->prepare("UPDATE usuario_web SET token_intentos = token_intentos + 1 WHERE idusuario = ?");
                $inc->bind_param("i", $user['idusuario']);
                $inc->execute();
                $error = 'El código es incorrecto.';
                $paso = 'codigo';
            } else {
                // ¡Login exitoso!
                $limpiar = $conn->prepare("UPDATE usuario_web SET token = NULL, token_expira = NULL, token_intentos = 0, verificado = 1 WHERE idusuario = ?");
                $limpiar->bind_param("i", $user['idusuario']);
                $limpiar->execute();

                $_SESSION['usuario_id'] = $user['idusuario'];
                $_SESSION['usuario_nombre'] = $user['nombre'];
                $_SESSION['usuario_email'] = $email;
                unset($_SESSION['login_email']);

                $destino = $_SESSION['redirect_after_login'] ?? 'index.php';
                unset($_SESSION['redirect_after_login']);
                header("Location: " . $destino);
                exit();
            }
        }
    }
}

// Si ya hay un email pendiente de verificación, mostramos directamente el paso 2
if ($paso === 'email' && isset($_SESSION['login_email']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $paso = 'codigo';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mostaza - Iniciar Sesión</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800;900&family=Playfair+Display:wght@700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="style.css?v=<?= filemtime('style.css') ?>">
<link rel="stylesheet" href="auth.css?v=<?= filemtime('auth.css') ?>">
<link rel="stylesheet" href="tienda.css?v=<?= filemtime('tienda.css') ?>">
</head>
<body>

<header style="display:flex;justify-content:space-between;align-items:center;padding:20px 8%;border-bottom:1px solid #E8DFD1;background:rgba(255,255,255,0.7);backdrop-filter:blur(10px);">
    <div class="logo">
        <a href="index.php" style="display:flex;align-items:center;gap:12px;text-decoration:none;">
            <img src="img/image-removebg-preview.png" alt="Mostaza" style="width:48px;">
            <span style="font-family:'Montserrat',sans-serif;font-weight:900;color:var(--rojo);font-size:1.7rem;letter-spacing:-0.5px;">MOSTAZA</span>
        </a>
    </div>
    <a href="index.php" style="color:#64748B;font-weight:700;font-size:0.88rem;display:flex;align-items:center;gap:8px;text-decoration:none;transition:color 0.2s;">
        <i class="fa-solid fa-arrow-left"></i> Volver a la Tienda
    </a>
</header>

<section class="auth-container">
    <div class="auth-box">

        <?php if ($paso === 'email'): ?>
            <div class="auth-top">
                <i class="fa-solid fa-burger"></i>
                <h2>Bienvenido</h2>
                <p>Ingresá tu correo para recibir tu código de acceso</p>
            </div>

            <?php if ($error): ?><div class="flash flash-error"><?= $error ?></div><?php endif; ?>

            <form method="post" action="login.php">
                <input type="hidden" name="accion" value="enviar_token">
                <div class="input-group">
                    <i class="fa-solid fa-envelope"></i>
                    <input type="email" name="email" placeholder="Correo electrónico" required autofocus>
                </div>
                <button type="submit">Enviar código</button>
            </form>

            <div class="auth-footer">
                <p>¿No tenés cuenta?</p>
                <a href="registro.php">Crear cuenta</a>
            </div>

        <?php else: ?>
            <div class="auth-top">
                <i class="fa-solid fa-shield-halved"></i>
                <h2>Revisá tu correo</h2>
                <p>Ingresá el código de 6 dígitos que te enviamos a<br><strong><?= htmlspecialchars($_SESSION['login_email'] ?? '') ?></strong></p>
            </div>

            <?php if ($error): ?><div class="flash flash-error"><?= $error ?></div><?php endif; ?>
            <?php if ($ok): ?><div class="flash flash-ok"><?= $ok ?></div><?php endif; ?>

            <form method="post" action="login.php" id="form-codigo">
                <input type="hidden" name="accion" value="verificar_token">
                <div class="token-boxes">
                    <?php for ($i = 0; $i < 6; $i++): ?>
                        <input type="text" maxlength="1" inputmode="numeric" class="token-digit" required>
                    <?php endfor; ?>
                </div>
                <input type="hidden" name="codigo" id="codigo-completo">
                <button type="submit">Verificar e ingresar</button>
            </form>

            <div class="reenviar-token">
                <form method="post" action="login.php">
                    <input type="hidden" name="accion" value="reenviar_token">
                    <span>¿No te llegó? </span><button type="submit" style="background:none;border:none;color:var(--rojo);font-weight:700;cursor:pointer;text-decoration:underline;">Reenviar código</button>
                </form>
            </div>

            <div class="auth-footer">
                <a href="login.php?cambiar=1" onclick="return volverAEmail()">Usar otro correo</a>
            </div>
        <?php endif; ?>

    </div>
</section>

<script>
// Autofoco entre las 6 casillas del código y armado del input oculto
const digitos = document.querySelectorAll('.token-digit');
if (digitos.length) {
    digitos.forEach((d, i) => {
        d.addEventListener('input', () => {
            d.value = d.value.replace(/\D/g, '');
            if (d.value && i < digitos.length - 1) digitos[i + 1].focus();
            actualizarCodigo();
        });
        d.addEventListener('keydown', (e) => {
            if (e.key === 'Backspace' && !d.value && i > 0) digitos[i - 1].focus();
        });
    });
    function actualizarCodigo() {
        document.getElementById('codigo-completo').value = Array.from(digitos).map(x => x.value).join('');
    }
    document.getElementById('form-codigo').addEventListener('submit', actualizarCodigo);
}
function volverAEmail() {
    fetch('logout_paso_login.php').finally(() => window.location.href = 'login.php');
    return false;
}
</script>

</body>
</html>
