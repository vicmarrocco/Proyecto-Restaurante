<?php
// ============================================================
// detalle_producto.php
// R12: Vista de detalles ampliada de producto con ingredientes,
// alérgenos, opciones de tamaño y agregado al carrito.
// ============================================================
session_start();
require('conexion.php');
$paginaActiva = 'productos';

$idproducto = intval($_GET['id'] ?? 0);
if ($idproducto <= 0) {
    header("Location: productos.php");
    exit();
}

$stmt = $conn->prepare("SELECT * FROM producto WHERE idproducto = ? AND activo = 1");
$stmt->bind_param("i", $idproducto);
$stmt->execute();
$prod = $stmt->get_result()->fetch_assoc();

if (!$prod) {
    header("Location: productos.php");
    exit();
}

$logueado = isset($_SESSION['usuario_id']);

// Productos recomendados de la misma categoría
$stmtRel = $conn->prepare("SELECT * FROM producto WHERE categoria = ? AND idproducto != ? AND activo = 1 LIMIT 3");
$stmtRel->bind_param("si", $prod['categoria'], $idproducto);
$stmtRel->execute();
$relacionados = $stmtRel->get_result();

$imgSrc = $prod['imagen'] ?? 'image-removebg-preview.png';
if (strpos($imgSrc, 'img/') !== 0 && strpos($imgSrc, 'http') !== 0) {
    $imgSrc = 'img/' . $imgSrc;
}

$alergenos = $prod['alergenos'] ?: 'Gluten, Lácteos, Huevo';
$calorias = $prod['calorias'] ?: 650;
$ingredientes = $prod['ingredientes'] ?: 'Carne 100% vacuna, queso seleccionado, salsa especial Mostaza y pan recién horneado.';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($prod['nombre']) ?> · Detalles · Mostaza</title>
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="tienda.css">
<link rel="stylesheet" href="checkout.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800;900&family=Playfair+Display:wght@700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
.detail-container {
    max-width: 1100px;
    margin: 40px auto 60px;
    padding: 0 20px;
}
.detail-grid {
    display: grid;
    grid-template-columns: 1fr 1.1fr;
    gap: 40px;
    background: #fff;
    border-radius: 24px;
    padding: 36px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.06);
    border: 1px solid #E2E8F0;
}
@media (max-width: 850px) {
    .detail-grid { grid-template-columns: 1fr; gap: 24px; padding: 20px; }
}
.detail-image-box {
    background: linear-gradient(135deg, #FFFBEB 0%, #FEF3C7 100%);
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 30px;
    border: 2px dashed #FDE68A;
}
.detail-image-box img {
    max-width: 100%;
    max-height: 380px;
    object-fit: contain;
    filter: drop-shadow(0 12px 20px rgba(0,0,0,0.15));
    transition: transform 0.3s;
}
.detail-image-box img:hover {
    transform: scale(1.04);
}
.detail-category-badge {
    display: inline-block;
    background: #DCFCE7;
    color: #16A34A;
    font-size: 0.8rem;
    font-weight: 800;
    text-transform: uppercase;
    padding: 4px 12px;
    border-radius: 20px;
    margin-bottom: 12px;
}
.detail-title {
    font-size: 2.2rem;
    font-weight: 900;
    color: var(--dark);
    line-height: 1.2;
    margin-bottom: 12px;
}
.detail-price {
    font-size: 2rem;
    font-weight: 900;
    color: var(--rojo);
    margin-bottom: 20px;
}
.detail-desc {
    font-size: 1.05rem;
    color: #4B5563;
    line-height: 1.6;
    margin-bottom: 24px;
}
.info-section {
    background: #F8FAFC;
    border-radius: 14px;
    padding: 18px 20px;
    margin-bottom: 20px;
    border: 1px solid #E2E8F0;
}
.info-section h4 {
    font-size: 0.95rem;
    font-weight: 800;
    color: #0F172A;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.info-section p {
    font-size: 0.9rem;
    color: #475569;
    line-height: 1.5;
    margin: 0;
}
.allergens-tag {
    background: #FEE2E2;
    color: #991B1B;
    font-size: 0.8rem;
    font-weight: 700;
    padding: 3px 8px;
    border-radius: 6px;
    display: inline-block;
    margin-top: 6px;
}
.size-selector {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
}
.size-btn {
    flex: 1;
    border: 2px solid #E2E8F0;
    background: #fff;
    padding: 10px 14px;
    border-radius: 12px;
    cursor: pointer;
    font-weight: 700;
    font-size: 0.85rem;
    text-align: center;
    color: #334155;
    transition: 0.2s;
}
.size-btn.active {
    border-color: var(--rojo);
    background: #FFFDFD;
    color: var(--rojo);
}
.add-form {
    display: flex;
    gap: 14px;
    align-items: stretch;
    margin-top: 24px;
}
.qty-input-detail {
    width: 80px;
    border-radius: 12px;
    border: 2px solid #CBD5E1;
    font-size: 1.2rem;
    font-weight: 800;
    text-align: center;
}
.btn-add-detail {
    flex: 1;
    background: var(--rojo);
    color: #fff;
    border: none;
    border-radius: 12px;
    font-size: 1.1rem;
    font-weight: 900;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    cursor: pointer;
    padding: 14px 24px;
    transition: background 0.2s;
    text-decoration: none;
}
.btn-add-detail:hover { background: #870002; color: #fff; }
</style>
</head>
<body class="checkout-page">

<?php include('includes/header.php'); ?>

<div class="detail-container">
    <div style="margin-bottom: 20px;">
        <a href="categoria.php?cat=<?= urlencode($prod['categoria']) ?>" style="color:#64748B;text-decoration:none;font-weight:700;font-size:0.9rem;">
            ← Volver a <?= htmlspecialchars(ucfirst($prod['categoria'])) ?>
        </a>
    </div>

    <div class="detail-grid">
        <!-- Imagen -->
        <div class="detail-image-box">
            <img src="<?= htmlspecialchars($imgSrc) ?>" alt="<?= htmlspecialchars($prod['nombre']) ?>" onerror="this.src='img/image-removebg-preview.png'">
        </div>

        <!-- Información -->
        <div>
            <span class="detail-category-badge"><?= htmlspecialchars($prod['categoria']) ?></span>
            <h1 class="detail-title"><?= htmlspecialchars($prod['nombre']) ?></h1>
            <div class="detail-price">$<?= number_format($prod['precio'], 0, ',', '.') ?></div>

            <p class="detail-desc">
                <?= htmlspecialchars($prod['descripcion'] ?: 'Preparada en el momento con ingredientes de primera calidad para garantizar el mejor sabor tradicional de Mostaza.') ?>
            </p>

            <!-- Ingredientes (R12) -->
            <div class="info-section">
                <h4><i class="fa-solid fa-utensils" style="color:var(--rojo);"></i> Ingredientes de la Receta:</h4>
                <p><?= htmlspecialchars($ingredientes) ?></p>
            </div>

            <!-- Alérgenos e Información Nutricional (R12) -->
            <div class="info-section">
                <h4><i class="fa-solid fa-heart-pulse" style="color:#2563EB;"></i> Información Nutricional y Alérgenos:</h4>
                <p>
                    🔥 Valor energético: <strong>~<?= $calorias ?> kcal</strong><br>
                    <span class="allergens-tag">⚠️ Alérgenos: <?= htmlspecialchars($alergenos) ?></span>
                </p>
            </div>

            <!-- Formulario de Agregado al Carrito (R11) -->
            <?php if ($logueado): ?>
                <form method="post" action="agregar_carrito.php">
                    <input type="hidden" name="idproducto" value="<?= $prod['idproducto'] ?>">
                    <input type="hidden" name="volver" value="detalle_producto.php?id=<?= $prod['idproducto'] ?>">

                    <div style="margin-bottom:12px;">
                        <label style="font-size:0.85rem;font-weight:700;color:#475569;display:block;margin-bottom:6px;">Aclaraciones para la cocina (Opcional):</label>
                        <input type="text" name="modificadores" placeholder="Ej: Sin cebolla, extra aderezo, etc." 
                               style="width:100%;padding:10px 14px;border-radius:10px;border:1px solid #CBD5E1;font-size:0.9rem;">
                    </div>

                    <div class="add-form">
                        <input type="number" name="cantidad" value="1" min="1" max="20" class="qty-input-detail">
                        <button type="submit" class="btn-add-detail">
                            <i class="fa-solid fa-cart-plus"></i> Agregar al Carrito
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <div style="background:#FFFBEB;border:1px solid #FDE68A;padding:16px;border-radius:12px;text-align:center;margin-top:20px;">
                    <p style="color:#92400E;font-size:0.95rem;margin-bottom:10px;font-weight:600;">
                        Iniciá sesión en tu cuenta para pedir esta deliciosa opción.
                    </p>
                    <a href="login.php" class="btn-add-detail" style="display:inline-flex;padding:10px 24px;font-size:0.95rem;">
                        <i class="fa-solid fa-lock"></i> Iniciar Sesión para Pedir
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include('includes/footer.php'); ?>
</body>
</html>
