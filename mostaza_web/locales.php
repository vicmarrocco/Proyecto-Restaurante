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
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<style>
.locales-hero {
    background: linear-gradient(135deg, #870002 0%, #A30002 40%, #111827 100%);
    color: #fff;
    padding: 70px 20px 55px;
    text-align: center;
    position: relative;
    border-bottom: 3px solid var(--amarillo);
}
.locales-hero h1 {
    font-size: clamp(2rem, 5vw, 3rem);
    font-weight: 900;
    color: #FFFFFF;
    margin-bottom: 12px;
    text-transform: uppercase;
    letter-spacing: -0.5px;
}
.locales-hero h1 span {
    color: var(--amarillo);
}
.locales-hero p {
    font-size: 1.05rem;
    color: #F8FAFC;
    max-width: 680px;
    margin: 0 auto;
    line-height: 1.6;
}
.locales-container {
    max-width: 1240px;
    margin: -25px auto 70px;
    padding: 0 20px;
    display: grid;
    grid-template-columns: 1.2fr 1fr;
    gap: 30px;
    position: relative;
    z-index: 5;
}
@media (max-width: 900px) {
    .locales-container { grid-template-columns: 1fr; }
}
.sucursal-card {
    background: #fff;
    border-radius: 20px;
    padding: 28px;
    margin-bottom: 24px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.06);
    border: 1px solid #E8DFD1;
    transition: transform 0.25s, box-shadow 0.25s, border-color 0.25s;
}
.sucursal-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 15px 35px rgba(163,0,2,0.12);
    border-color: var(--amarillo);
}
.sucursal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #F4ECE0;
    padding-bottom: 16px;
    margin-bottom: 18px;
}
.sucursal-title {
    font-size: 1.4rem;
    font-weight: 900;
    color: var(--text-main);
    text-transform: uppercase;
}
.sucursal-badge {
    background: #FEF3C7;
    color: #92400E;
    border: 1px solid #FDE68A;
    font-size: 0.78rem;
    font-weight: 800;
    padding: 5px 12px;
    border-radius: 20px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.sucursal-info-item {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    margin-bottom: 14px;
    font-size: 0.95rem;
    color: #475569;
}
.sucursal-info-item i {
    color: var(--rojo);
    font-size: 1.15rem;
    margin-top: 3px;
    width: 22px;
}
.amenities-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 8px;
}
.amenity-chip {
    background: #F8FAFC;
    border: 1px solid #E2E8F0;
    border-radius: 8px;
    padding: 4px 10px;
    font-size: 0.78rem;
    font-weight: 700;
    color: #334155;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}
.sucursal-actions {
    display: flex;
    gap: 12px;
    margin-top: 22px;
    flex-wrap: wrap;
}
.btn-sucursal {
    flex: 1;
    min-width: 140px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 18px;
    border-radius: 12px;
    font-weight: 800;
    font-size: 0.88rem;
    text-decoration: none;
    transition: all 0.2s ease;
}
.btn-maps { background: var(--dark); color: #fff; }
.btn-maps:hover { background: #000; color: var(--amarillo); }
.btn-call { background: #F1F5F9; color: var(--text-main); border: 1px solid #CBD5E1; }
.btn-call:hover { background: #E2E8F0; }
.btn-order-here { background: var(--rojo); color: #fff; box-shadow: 0 4px 14px rgba(163,0,2,0.3); }
.btn-order-here:hover { background: #7A0002; color: #fff; }
#mapaLocales {
    width: 100%;
    height: 580px;
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    border: 2px solid #E8DFD1;
    position: sticky;
    top: 100px;
}
.filter-pill {
    background: #F8FAFC;
    border: 1px solid #E2E8F0;
    color: #475569;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-family: 'Montserrat', sans-serif;
}
.filter-pill:hover {
    background: #F1F5F9;
    border-color: #CBD5E1;
    color: var(--text-main);
}
.filter-pill.active {
    background: var(--rojo);
    color: #fff;
    border-color: var(--rojo);
    box-shadow: 0 4px 12px rgba(163,0,2,0.25);
}
.filter-pill.highlight-formosa.active {
    background: var(--amarillo);
    color: var(--dark);
    border-color: var(--amarillo-dark);
    box-shadow: 0 4px 12px rgba(251,183,40,0.35);
}
</style>
</head>
<body class="checkout-page" style="background:var(--crema);">

<?php include('includes/header.php'); ?>

<div class="locales-hero">
    <span class="section-badge" style="background:rgba(251,183,40,0.2);color:#FBB728;margin-bottom:12px;"><i class="fa-solid fa-store"></i> Red Nacional Mostaza · Presencia en Formosa y todo el País</span>
    <h1>Nuestras <span>Sucursales</span></h1>
    <p>Encontrá tu local Mostaza más cercano. Disfrutá de nuestros salones climatizados, AutoMostaza, Take Away o pedí directo por Delivery Express a tu domicilio.</p>
</div>

<!-- Filtros y Buscador Rápido -->
<div style="max-width:1240px; margin:-20px auto 30px; padding: 0 20px; position:relative; z-index:10;">
    <div style="background:#fff; border-radius:18px; padding:16px 20px; border:1px solid #E8DFD1; box-shadow:0 8px 24px rgba(0,0,0,0.06); display:flex; flex-wrap:wrap; gap:14px; align-items:center; justify-content:space-between;">
        <div style="display:flex; flex-wrap:wrap; gap:8px;" id="contenedorFiltros">
            <button class="filter-pill active" data-filter="all" onclick="filtrarRegion('all', this)"><i class="fa-solid fa-earth-americas"></i> Todas (<span id="count-all">30</span>)</button>
            <button class="filter-pill highlight-formosa" data-filter="formosa" onclick="filtrarRegion('formosa', this)"><i class="fa-solid fa-star" style="color:#D97706;"></i> Formosa Capital (3)</button>
            <button class="filter-pill" data-filter="caba" onclick="filtrarRegion('caba', this)"><i class="fa-solid fa-city"></i> CABA / GBA (10)</button>
            <button class="filter-pill" data-filter="cordoba" onclick="filtrarRegion('cordoba', this)"><i class="fa-solid fa-mountain"></i> Córdoba (3)</button>
            <button class="filter-pill" data-filter="santafe" onclick="filtrarRegion('santafe', this)"><i class="fa-solid fa-water"></i> Santa Fe / Rosario (3)</button>
            <button class="filter-pill" data-filter="mendoza" onclick="filtrarRegion('mendoza', this)"><i class="fa-solid fa-wine-glass"></i> Mendoza (2)</button>
            <button class="filter-pill" data-filter="norte" onclick="filtrarRegion('norte', this)"><i class="fa-solid fa-sun"></i> NEA / NOA (6)</button>
            <button class="filter-pill" data-filter="sur" onclick="filtrarRegion('sur', this)"><i class="fa-solid fa-snowflake"></i> Patagonia / Costa (3)</button>
        </div>
        <div style="min-width:240px; flex:1; max-width:340px; position:relative;">
            <i class="fa-solid fa-magnifying-glass" style="position:absolute; left:14px; top:50%; transform:translateY(-50%); color:#94A3B8;"></i>
            <input type="text" id="buscadorSucursales" placeholder="Buscar sucursal, ciudad o calle..." style="width:100%; padding:10px 14px 10px 38px; border-radius:12px; border:1px solid #CBD5E1; font-size:0.88rem; font-family:'Montserrat',sans-serif; outline:none; transition:border-color 0.2s;" onkeyup="buscarSucursal(this.value)">
        </div>
    </div>
</div>

<div class="locales-container" style="margin-top:0;">
    <!-- Lista de Locales -->
    <div id="listaSucursales">
        <?php 
        $sucursalesArray = [];
        while ($s = $resSuc->fetch_assoc()): 
            $sucursalesArray[] = $s;
            $lat = floatval($s['latitud'] ?: -26.185200);
            $lng = floatval($s['longitud'] ?: -58.175600);
            $mapsUrl = "https://www.google.com/maps/dir/?api=1&destination=" . urlencode($lat . "," . $lng);
            $telClean = preg_replace('/[^0-9]/', '', $s['telefono']);
            
            // Determinar región
            $txtSearch = mb_strtolower($s['nombre'] . ' ' . $s['direccion'] . ' ' . $s['ciudad'], 'UTF-8');
            $reg = 'otras';
            if (strpos($txtSearch, 'formosa') !== false) {
                $reg = 'formosa';
            } elseif (strpos($txtSearch, 'caba') !== false || strpos($txtSearch, 'gba') !== false || strpos($txtSearch, 'obelisco') !== false || strpos($txtSearch, 'palermo') !== false || strpos($txtSearch, 'abasto') !== false || strpos($txtSearch, 'aeroparque') !== false || strpos($txtSearch, 'unicenter') !== false || strpos($txtSearch, 'quilmes') !== false || strpos($txtSearch, 'belgrano') !== false || strpos($txtSearch, 'puerto madero') !== false || strpos($txtSearch, 'plaza oeste') !== false) {
                $reg = 'caba';
            } elseif (strpos($txtSearch, 'córdoba') !== false || strpos($txtSearch, 'cordoba') !== false || strpos($txtSearch, 'carlos paz') !== false) {
                $reg = 'cordoba';
            } elseif (strpos($txtSearch, 'rosario') !== false || strpos($txtSearch, 'santa fe') !== false) {
                $reg = 'santafe';
            } elseif (strpos($txtSearch, 'mendoza') !== false) {
                $reg = 'mendoza';
            } elseif (strpos($txtSearch, 'resistencia') !== false || strpos($txtSearch, 'corrientes') !== false || strpos($txtSearch, 'posadas') !== false || strpos($txtSearch, 'tucumán') !== false || strpos($txtSearch, 'tucuman') !== false || strpos($txtSearch, 'salta') !== false || strpos($txtSearch, 'jujuy') !== false) {
                $reg = 'norte';
            } elseif (strpos($txtSearch, 'mar del plata') !== false || strpos($txtSearch, 'bariloche') !== false || strpos($txtSearch, 'neuquén') !== false || strpos($txtSearch, 'neuquen') !== false) {
                $reg = 'sur';
            }
        ?>
        <div class="sucursal-card" id="card-suc-<?= $s['idsucursal'] ?>" data-region="<?= $reg ?>" data-search="<?= htmlspecialchars($txtSearch) ?>" data-lat="<?= $lat ?>" data-lng="<?= $lng ?>" data-nombre="<?= htmlspecialchars($s['nombre']) ?>">
            <div class="sucursal-header">
                <div>
                    <h3 class="sucursal-title" style="cursor:pointer;" onclick="enfocarEnMapa(<?= $lat ?>, <?= $lng ?>, '<?= addslashes($s['nombre']) ?>')">
                        <?= htmlspecialchars($s['nombre']) ?>
                    </h3>
                    <span style="font-size:0.8rem;color:#64748B;"><i class="fa-solid fa-city"></i> <?= htmlspecialchars($s['ciudad'] ?? 'Argentina') ?></span>
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
                <div style="width: 100%;">
                    <strong>Servicios e Instalaciones:</strong>
                    <div class="amenities-badges">
                        <span class="amenity-chip"><i class="fa-solid fa-snowflake" style="color:var(--rojo);"></i> Salón Climatizado</span>
                        <span class="amenity-chip"><i class="fa-solid fa-car" style="color:var(--amarillo-dark);"></i> AutoMostaza</span>
                        <span class="amenity-chip"><i class="fa-solid fa-motorcycle" style="color:var(--rojo);"></i> Delivery 30 min</span>
                        <span class="amenity-chip"><i class="fa-solid fa-bag-shopping" style="color:var(--rojo);"></i> Take Away</span>
                        <span class="amenity-chip"><i class="fa-solid fa-wifi" style="color:var(--dark);"></i> WiFi Free</span>
                        <span class="amenity-chip"><i class="fa-solid fa-child-reaching" style="color:var(--amarillo-dark);"></i> Pelotero Kids</span>
                    </div>
                </div>
            </div>

            <div class="sucursal-actions">
                <button type="button" onclick="enfocarEnMapa(<?= $lat ?>, <?= $lng ?>, '<?= addslashes($s['nombre']) ?>')" class="btn-sucursal btn-maps" style="border:none; cursor:pointer;">
                    <i class="fa-solid fa-location-arrow"></i> Ver en Mapa
                </button>
                <a href="<?= $mapsUrl ?>" target="_blank" class="btn-sucursal btn-maps">
                    <i class="fa-brands fa-google"></i> GPS Google Maps
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
let map = null;
let markers = {};

document.addEventListener('DOMContentLoaded', function() {
    const sucursales = <?= json_encode($sucursalesArray) ?>;
    
    // Iniciar centrado en Formosa Capital
    map = L.map('mapaLocales').setView([-26.185200, -58.175600], 12);
    
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
            <div style="font-family:'Montserrat',sans-serif;padding:6px;min-width:200px;">
                <h4 style="margin:0 0 5px;color:#A30002;font-size:14px;font-weight:900;text-transform:uppercase;">${s.nombre}</h4>
                <p style="margin:0 0 5px;font-size:12px;color:#333;">📍 ${s.direccion}</p>
                <p style="margin:0 0 8px;font-size:11px;color:#666;">🕒 ${s.horario_atencion}</p>
                <div style="display:flex;gap:6px;">
                    <a href="https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}" target="_blank" 
                       style="display:inline-block;background:#A30002;color:#fff;padding:6px 12px;border-radius:8px;font-size:11px;font-weight:bold;text-decoration:none;">
                       🧭 Cómo Llegar
                    </a>
                    <a href="tel:${(s.telefono || '').replace(/[^0-9]/g, '')}" 
                       style="display:inline-block;background:#1E293B;color:#fff;padding:6px 12px;border-radius:8px;font-size:11px;font-weight:bold;text-decoration:none;">
                       📞 Llamar
                    </a>
                </div>
            </div>
        `);
        markers[s.idsucursal] = { marker, lat, lng, nombre: s.nombre };
    });
});

function enfocarEnMapa(lat, lng, nombre) {
    if (!map) return;
    map.flyTo([lat, lng], 15, { duration: 1.2 });
    for (let id in markers) {
        if (markers[id].lat === lat && markers[id].lng === lng) {
            markers[id].marker.openPopup();
            break;
        }
    }
    const mapEl = document.getElementById('mapaLocales');
    if (window.innerWidth < 900) {
        mapEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}

function filtrarRegion(region, btn) {
    document.querySelectorAll('.filter-pill').forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');

    const cards = document.querySelectorAll('.sucursal-card');
    const groupBounds = [];

    cards.forEach(card => {
        const cardReg = card.getAttribute('data-region');
        const lat = parseFloat(card.getAttribute('data-lat'));
        const lng = parseFloat(card.getAttribute('data-lng'));

        if (region === 'all' || cardReg === region) {
            card.style.display = 'block';
            groupBounds.push([lat, lng]);
        } else {
            card.style.display = 'none';
        }
    });

    if (map && groupBounds.length > 0) {
        if (groupBounds.length === 1) {
            map.flyTo(groupBounds[0], 15);
        } else {
            map.flyToBounds(groupBounds, { padding: [40, 40], maxZoom: 14 });
        }
    }
}

function buscarSucursal(query) {
    const q = (query || '').toLowerCase().trim();
    const cards = document.querySelectorAll('.sucursal-card');
    const groupBounds = [];

    cards.forEach(card => {
        const text = card.getAttribute('data-search') || '';
        const lat = parseFloat(card.getAttribute('data-lat'));
        const lng = parseFloat(card.getAttribute('data-lng'));

        if (!q || text.includes(q)) {
            card.style.display = 'block';
            groupBounds.push([lat, lng]);
        } else {
            card.style.display = 'none';
        }
    });

    if (map && groupBounds.length > 0 && q.length > 2) {
        map.flyToBounds(groupBounds, { padding: [40, 40], maxZoom: 15 });
    }
}
</script>

<?php include('includes/footer.php'); ?>
</body>
</html>
