<?php
// ============================================================
// perfil.php
// Mi Perfil, libreta de direcciones y balance de Puntos Mostaza.
// ============================================================
session_start();
require('conexion.php');
$paginaActiva = 'perfil';

if (!isset($_SESSION['usuario_id'])) {
    $_SESSION['redirect_after_login'] = 'perfil.php';
    header("Location: login.php");
    exit();
}

$idusuario = $_SESSION['usuario_id'];
$mensajeOk = '';
$mensajeErr = '';

// Procesar guardado de perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_perfil'])) {
    $nombre = htmlspecialchars(trim($_POST['nombre'] ?? ''));
    $apellido = htmlspecialchars(trim($_POST['apellido'] ?? ''));
    $telefono = htmlspecialchars(trim($_POST['telefono'] ?? ''));
    $direccion = htmlspecialchars(trim($_POST['direccion'] ?? ''));

    if (empty($nombre) || empty($apellido)) {
        $mensajeErr = 'Nombre y apellido no pueden estar vacíos.';
    } else {
        $upd = $conn->prepare("UPDATE usuario_web SET nombre = ?, apellido = ?, telefono = ?, direccion = ?, direccion_favorita = ?, telefono_favorito = ? WHERE idusuario = ?");
        $upd->bind_param("ssssssi", $nombre, $apellido, $telefono, $direccion, $direccion, $telefono, $idusuario);
        if ($upd->execute()) {
            $_SESSION['usuario_nombre'] = $nombre;
            $mensajeOk = '¡Tus datos fueron actualizados correctamente!';
        } else {
            $mensajeErr = 'Ocurrió un error al actualizar los datos.';
        }
    }
}

// Obtener datos actualizados del usuario
$stmt = $conn->prepare("SELECT * FROM usuario_web WHERE idusuario = ?");
$stmt->bind_param("i", $idusuario);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Métricas del cliente
$stmtCount = $conn->prepare("SELECT COUNT(*) as cant, IFNULL(SUM(total), 0) as gastado FROM pedido_web WHERE idusuario = ?");
$stmtCount->bind_param("i", $idusuario);
$stmtCount->execute();
$stats = $stmtCount->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mi Perfil · Mostaza</title>
<!-- Base Framework (Bootstrap) -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Google Fonts & Font Awesome -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800;900&family=Playfair+Display:wght@700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<!-- Estilos Oficiales Mostaza (Sobrescriben Bootstrap) -->
<link rel="stylesheet" href="style.css?v=<?= filemtime('style.css') ?>">
<link rel="stylesheet" href="tienda.css?v=<?= filemtime('tienda.css') ?>">
<link rel="stylesheet" href="checkout.css?v=<?= filemtime('checkout.css') ?>">
</head>
<body class="checkout-page">

<?php include('includes/header.php'); ?>

<div class="checkout-container" style="max-width: 900px;">

    <div class="checkout-header-title">
        <h1>Mi Cuenta & Club Mostaza</h1>
        <p>Gestioná tus datos personales, direcciones de entrega y canjeá tus puntos acumulados.</p>
    </div>

    <?php if ($mensajeOk): ?><div class="flash flash-ok"><?= $mensajeOk ?></div><?php endif; ?>
    <?php if ($mensajeErr): ?><div class="flash flash-error"><?= $mensajeErr ?></div><?php endif; ?>

    <div class="checkout-grid" style="grid-template-columns: 1fr 1fr;">

        <!-- Tarjeta Puntos Mostaza & Nivel -->
        <div class="card-section" style="background: linear-gradient(135deg, #1f2937, #111827); color:#fff;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
                <div style="font-size:0.85rem;color:#FBB728;font-weight:700;letter-spacing:1px;">CLUB DE BENEFICIOS</div>
                <div style="background:#FBB728;color:#000;font-size:0.75rem;font-weight:900;padding:4px 10px;border-radius:50px;">NIVEL VIP</div>
            </div>

            <div style="font-size:2.8rem;font-weight:900;color:#FBB728;margin-bottom:6px;display:flex;align-items:center;gap:12px;">
                <i class="fa-solid fa-star"></i> <?= intval($user['puntos'] ?? 0) ?> <span style="font-size:1.1rem;color:#E5E7EB;font-weight:500;">pts</span>
            </div>
            <p style="color:#9CA3AF;font-size:0.88rem;margin-bottom:20px;">
                Equivalen a <strong>$<?= number_format(intval($user['puntos'] ?? 0), 0, ',', '.') ?></strong> en descuentos directos para tus próximos pedidos.
            </p>

            <div style="border-top:1px solid #374151;padding-top:16px;display:grid;grid-template-columns:1fr 1fr;gap:12px;text-align:center;">
                <div style="background:#374151;padding:12px;border-radius:10px;">
                    <span style="font-size:0.75rem;color:#9CA3AF;display:block;">Pedidos Realizados</span>
                    <strong style="font-size:1.2rem;color:#fff;"><?= $stats['cant'] ?></strong>
                </div>
                <div style="background:#374151;padding:12px;border-radius:10px;">
                    <span style="font-size:0.75rem;color:#9CA3AF;display:block;">Total Consumido</span>
                    <strong style="font-size:1.2rem;color:#FBB728;">$<?= number_format($stats['gastado'], 0, ',', '.') ?></strong>
                </div>
            </div>
        </div>

        <!-- Formulario Datos Personales -->
        <div class="card-section">
            <div class="card-section-title">
                <i class="fa-solid fa-user-pen"></i>
                <span>Mis Datos Personales</span>
            </div>

            <form method="post" action="perfil.php">
                <input type="hidden" name="guardar_perfil" value="1">
                
                <div class="form-group-custom" style="margin-bottom:12px;">
                    <label>Nombre</label>
                    <input type="text" name="nombre" value="<?= htmlspecialchars($user['nombre']) ?>" required>
                </div>

                <div class="form-group-custom" style="margin-bottom:12px;">
                    <label>Apellido</label>
                    <input type="text" name="apellido" value="<?= htmlspecialchars($user['apellido']) ?>" required>
                </div>

                <div class="form-group-custom" style="margin-bottom:12px;">
                    <label>Correo Electrónico (No editable)</label>
                    <input type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled style="background:#f3f4f6;cursor:not-allowed;">
                </div>

                <div class="form-group-custom" style="margin-bottom:12px;">
                    <label>Teléfono de Contacto Habitual</label>
                    <input type="tel" name="telefono" value="<?= htmlspecialchars($user['telefono'] ?? '') ?>" placeholder="Ej: 11 4567-8901">
                </div>

                <div class="form-group-custom" style="margin-bottom:18px;">
                    <label>Dirección de Envío Habitual</label>
                    <input type="text" name="direccion" value="<?= htmlspecialchars($user['direccion'] ?? '') ?>" placeholder="Ej: Av. Santa Fe 3420">
                </div>

                <button type="submit" class="btn-submit-order" style="width:100%;padding:12px 20px;">
                    <i class="fa-solid fa-floppy-disk"></i> Guardar Cambios
                </button>
            </form>
        </div>

    </div>

</div>

<?php include('includes/footer.php'); ?>

</body>
</html>
