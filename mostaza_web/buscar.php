<?php
// ============================================================
// buscar.php
// Redirige al catálogo de productos con el parámetro de búsqueda
// ============================================================
$q = trim($_GET['q'] ?? '');
if ($q !== '') {
    header("Location: productos.php?q=" . urlencode($q));
    exit();
}
header("Location: productos.php");
exit();
