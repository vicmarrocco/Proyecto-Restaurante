<?php
session_start();
require('conexion.php');

if (isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';

function validar($data) {
    return htmlspecialchars(trim(stripslashes($data)));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre    = validar($_POST['nombre'] ?? '');
    $apellido  = validar($_POST['apellido'] ?? '');
    $telefono  = validar($_POST['telefono'] ?? '');
    $direccion = validar($_POST['direccion'] ?? '');
    $email     = validar($_POST['email'] ?? '');

    if (empty($nombre) || empty($apellido) || empty($email)) {
        $error = 'Nombre, apellido y correo son obligatorios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Ingresá un correo electrónico válido.';
    } else {
        $stmt = $conn->prepare("SELECT idusuario FROM usuario_web WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows > 0) {
            $error = 'Ya existe una cuenta con ese correo. Iniciá sesión.';
        } else {
            $ins = $conn->prepare("INSERT INTO usuario_web (nombre, apellido, telefono, direccion, email) VALUES (?, ?, ?, ?, ?)");
            $ins->bind_param("sssss", $nombre, $apellido, $telefono, $direccion, $email);

            if ($ins->execute()) {
                // Lo mandamos directo al login para que reciba su primer código
                $_SESSION['login_email'] = $email;
                header("Location: login.php");
                exit();
            } else {
                $error = 'Ocurrió un error al crear la cuenta. Intentá de nuevo.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mostaza - Registro</title>

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
    <div class="auth-box registro-box">

        <div class="auth-top">
            <i class="fa-solid fa-user-plus"></i>
            <h2>Crear Cuenta</h2>
            <p>Registrate y disfrutá Mostaza. Vas a ingresar con tu correo, sin contraseña.</p>
        </div>

        <?php if ($error): ?><div class="flash flash-error"><?= $error ?></div><?php endif; ?>

        <form method="post" action="registro.php">

            <div class="input-group">
                <i class="fa-solid fa-user"></i>
                <input type="text" name="nombre" placeholder="Nombre" required value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>">
            </div>

            <div class="input-group">
                <i class="fa-solid fa-user"></i>
                <input type="text" name="apellido" placeholder="Apellido" required value="<?= htmlspecialchars($_POST['apellido'] ?? '') ?>">
            </div>

            <div class="input-group">
                <i class="fa-solid fa-phone"></i>
                <input type="text" name="telefono" placeholder="Teléfono" value="<?= htmlspecialchars($_POST['telefono'] ?? '') ?>">
            </div>

            <div class="input-group">
                <i class="fa-solid fa-location-dot"></i>
                <input type="text" name="direccion" placeholder="Dirección" value="<?= htmlspecialchars($_POST['direccion'] ?? '') ?>">
            </div>

            <div class="input-group">
                <i class="fa-solid fa-envelope"></i>
                <input type="email" name="email" placeholder="Correo electrónico" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>

            <button type="submit">Registrarme</button>

        </form>

        <div class="auth-footer">
            <p>¿Ya tenés cuenta?</p>
            <a href="login.php">Iniciar sesión</a>
        </div>

    </div>
</section>

</body>
</html>
