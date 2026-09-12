<?php
// ============================================================
// locales.php
// R4: Consulta pública de sucursales y locales de Mostaza.
// ============================================================
session_start();
require('conexion.php');
$paginaActiva = 'locales';

$resSuc = $conn->query("SELECT * FROM sucursal WHERE activo = 1 ORDER BY idsucursal ASC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Nuestras Sucursales · Mostaza</title>
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="tienda.css">
<link rel="stylesheet" href="checkout.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800;900&family=Playfair+Display:wght@700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<style>
.locales-hero {
    background: linear-gradient(135deg, #0F172A 0%, #1E293B 100%);
    color: #fff;
    padding: 60px 20px 40px;
    text-align: center;
}
.locales-hero h1 {
    font-size: 2.6rem;
    font-weight: 900;
    color: var(--amarillo);
    margin-bottom: 8px;
}
.locales-hero p {
    font-size: 1.1rem;
    color: #CBD5E1;
    max-width: 600px;
    margin: 0 auto;
}
.locales-container {
    max-width: 1200px;
    margin: -30px auto 60px;
    padding: 0 20px;
    display: grid;
    grid-template-columns: 1.2fr 1fr;
    gap: 30px;
}
@media (max-width: 900px) {
    .locales-container { grid-template-columns: 1fr; }
}
.sucursal-card {
    background: #fff;
    border-radius: 16px;
    padding: 24px;
    margin-bottom: 20px;
    box-shadow: 0 6px 20px rgba(0,0,0,0.06);
    border: 1px solid #E2E8F0;
    transition: transform 0.2s, box-shadow 0.2s;
}
.sucursal-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    border-color: var(--amarillo);
}
.sucursal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #F1F5F9;
    padding-bottom: 14px;
    margin-bottom: 14px;
}
.sucursal-title {
    font-size: 1.3rem;
    font-weight: 900;
    color: var(--dark);
}
.sucursal-badge {
    background: #DCFCE7;
    color: #16A34A;
    font-size: 0.75rem;
    font-weight: 800;
    padding: 4px 10px;
    border-radius: 20px;
}
.sucursal-info-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 12px;
    font-size: 0.95rem;
    color: #475569;
}
.sucursal-info-item i {
    color: var(--rojo);
    font-size: 1.1rem;
    margin-top: 3px;
    width: 20px;
}
.sucursal-actions {
    display: flex;
    gap: 10px;
    margin-top: 18px;
    flex-wrap: wrap;
}
.btn-sucursal {
    flex: 1;
    min-width: 130px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 16px;
    border-radius: 10px;
    font-weight: 700;
    font-size: 0.85rem;
    text-decoration: none;
    transition: 0.2s;
}
.btn-maps { background: #2563EB; color: #fff; }
.btn-call { background: var(--dark); color: #fff; }
.btn-order-here { background: var(--rojo); color: #fff; }
#mapaLocales {
    width: 100%;
    height: 520px;
    border-radius: 16px;
    box-shadow: 0 6px 20px rgba(0,0,0,0.08);
    border: 2px solid #E2E8F0;
    position: sticky;
    top: 100px;
}
</style>
</head>
<body class="checkout-page">

<?php include('includes/header.php'); ?>

<div class="locales-hero">
    <h1><i class="fa-solid fa-store"></i> Nuestras Sucursales</h1>
    <p>Encontrá tu local Mostaza más cercano en Formosa. Disfrutá de nuestro salón climatizado, Auto-Mostaza, Take Away o pedí directo por Delivery.</p>
</div>

<div class="locales-container">
    <!-- Lista de Locales -->
    <div>
        <?php 
        $sucursalesArray = [];
        while ($s = $resSuc->fetch_assoc()): 
            $sucursalesArray[] = $s;
            $lat = floatval($s['latitud'] ?: -26.185200);
            $lng = floatval($s['longitud'] ?: -58.175600);
            $mapsUrl = "https://www.google.com/maps/dir/?api=1&destination=" . urlencode($lat . "," . $lng);
            $telClean = preg_replace('/[^0-9]/', '', $s['telefono']);
        ?>
        <div class="sucursal-card" id="card-suc-<?= $s['idsucursal'] ?>">
            <div class="sucursal-header">
                <div>
                    <h3 class="sucursal-title"><?= htmlspecialchars($s['nombre']) ?></h3>
                    <span style="font-size:0.8rem;color:#64748B;"><i class="fa-solid fa-city"></i> <?= htmlspecialchars($s['ciudad'] ?? 'Formosa') ?></span>
                </div>
                <span class="sucursal-badge">● ABIERTO AHORA</span>
            </div>

            <div class="sucursal-info-item">
                <i class="fa-solid fa-location-dot"></i>
                <div>
                    <strong>Dirección:</strong><br>
                    <?= htmlspecialchars($s['direccion']) ?>
                </div>
            </div>

            <div class="sucursal-info-item">
                <i class="fa-solid fa-clock"></i>
                <div>
                    <strong>Horario de Atención:</strong><br>
                    <?= htmlspecialchars($s['horario_atencion']) ?>
                </div>
            </div>

            <div class="sucursal-info-item">
                <i class="fa-solid fa-phone"></i>
                <div>
                    <strong>Teléfono de Contacto:</strong><br>
                    <a href="tel:<?= $telClean ?>" style="color:var(--rojo);text-decoration:none;font-weight:700;"><?= htmlspecialchars($s['telefono']) ?></a>
                </div>
            </div>

            <div class="sucursal-info-item">
                <i class="fa-solid fa-bell-concierge"></i>
                <div>
                    <strong>Servicios disponibles:</strong><br>
                    🍔 Salón Climatizado · 🛍️ Take Away · 🛵 Delivery Oficial Mostaza
                </div>
            </div>

            <div class="sucursal-actions">
                <a href="<?= $mapsUrl ?>" target="_blank" class="btn-sucursal btn-maps">
                    <i class="fa-solid fa-location-arrow"></i> Cómo Llegar (GPS)
                </a>
                <a href="tel:<?= $telClean ?>" class="btn-sucursal btn-call">
                    <i class="fa-solid fa-phone"></i> Llamar
                </a>
                <a href="productos.php" class="btn-sucursal btn-order-here">
                    <i class="fa-solid fa-burger"></i> Hacer Pedido
                </a>
            </div>
        </div>
        <?php endwhile; ?>
    </div>

    <!-- Mapa Interactivo Leaflet -->
    <div>
        <div id="mapaLocales"></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sucursales = <?= json_encode($sucursalesArray) ?>;
    
    // Centrar en Formosa Capital
    const map = L.map('mapaLocales').setView([-26.185200, -58.175600], 13);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    const mostazaIcon = L.icon({
        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
        iconSize: [28, 46],
        iconAnchor: [14, 46],
        popupAnchor: [1, -38],
        shadowSize: [41, 41]
    });

    sucursales.forEach(s => {
        const lat = parseFloat(s.latitud) || -26.185200;
        const lng = parseFloat(s.longitud) || -58.175600;
        
        const marker = L.marker([lat, lng], { icon: mostazaIcon }).addTo(map);
        marker.bindPopup(`
            <div style="font-family:sans-serif;padding:4px;">
                <h4 style="margin:0 0 4px;color:#A30002;font-size:14px;font-weight:800;">${s.nombre}</h4>
                <p style="margin:0 0 6px;font-size:12px;color:#333;">📍 ${s.direccion}</p>
                <p style="margin:0 0 8px;font-size:11px;color:#666;">🕒 ${s.horario_atencion}</p>
                <a href="https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}" target="_blank" 
                   style="display:inline-block;background:#A30002;color:#fff;padding:4px 10px;border-radius:6px;font-size:11px;font-weight:bold;text-decoration:none;">
                   🧭 Ir con Google Maps
                </a>
            </div>
        `);
    });
});
</script>

<?php include('includes/footer.php'); ?>
</body>
</html>
