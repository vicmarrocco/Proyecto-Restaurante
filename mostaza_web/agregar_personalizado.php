<?php
session_start();
header('Content-Type: application/json');

// Mismos precios que en script.js — se recalculan acá para seguridad del servidor.
$INGREDIENTES = [
    'pan'     => ['precio' => 0,    'label' => 'Pan Tostado'],
    'carne'   => ['precio' => 2500, 'label' => 'Carne a la Parrilla'],
    'pepino'  => ['precio' => 1500, 'label' => 'Pepinillos'],
    'huevo'   => ['precio' => 500,  'label' => 'Huevo'],
    'queso'   => ['precio' => 1000, 'label' => 'Queso Cheddar'],
    'lechuga' => ['precio' => 500,  'label' => 'Lechuga'],
    'tomate'  => ['precio' => 600,  'label' => 'Tomate'],
    'tocino'  => ['precio' => 1000, 'label' => 'Tocino / Bacon'],
    'cebolla' => ['precio' => 900,  'label' => 'Cebolla Morada'],
];

$ADEREZOS = [
    'mayonesa'       => ['precio' => 0,   'label' => 'Mayonesa'],
    'ketchup'        => ['precio' => 0,   'label' => 'Ketchup'],
    'mostaza'        => ['precio' => 0,   'label' => 'Mostaza'],
    'barbacoa'       => ['precio' => 300, 'label' => 'Barbacoa BBQ'],
    'cheddar'        => ['precio' => 450, 'label' => 'Salsa Cheddar'],
    'salsa_especial' => ['precio' => 350, 'label' => 'Salsa Especial Mega'],
];

$ACOMPANAMIENTOS = [
    'papas'   => ['precio' => 1500, 'label' => 'Papas Fritas'],
    'nuggets' => ['precio' => 1800, 'label' => 'Nuggets de Pollo'],
];

if (!isset($_SESSION['usuario_id'])) {
    $_SESSION['redirect_after_login'] = 'personalizado.php';
    echo json_encode(['ok' => false, 'redirect' => 'login.php']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$ingredientesSeleccionados = $input['ingredientes'] ?? [];
$aderezosSeleccionados = $input['aderezos'] ?? [];
$acomp = $input['acompanamiento'] ?? 'papas';

// El pan siempre es obligatorio
if (!in_array('pan', $ingredientesSeleccionados)) {
    array_unshift($ingredientesSeleccionados, 'pan');
}

if (!isset($ACOMPANAMIENTOS[$acomp])) {
    $acomp = 'papas';
}

$nombresIng = [];
$total = 0;

foreach ($ingredientesSeleccionados as $ing) {
    if (isset($INGREDIENTES[$ing])) {
        $total += $INGREDIENTES[$ing]['precio'];
        $nombresIng[] = $INGREDIENTES[$ing]['label'];
    }
}

$nombresAde = [];
foreach ($aderezosSeleccionados as $ad) {
    if (isset($ADEREZOS[$ad])) {
        $total += $ADEREZOS[$ad]['precio'];
        $nombresAde[] = $ADEREZOS[$ad]['label'];
    }
}

$total += $ACOMPANAMIENTOS[$acomp]['precio'];

$descripcion = 'Hamburguesa Personalizada: ' . implode(', ', $nombresIng);
if (!empty($nombresAde)) {
    $descripcion .= ' + Salsas: ' . implode(', ', $nombresAde);
}
$descripcion .= ' + Acomp: ' . $ACOMPANAMIENTOS[$acomp]['label'];

if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

$key = 'custom_' . uniqid();
$_SESSION['carrito'][$key] = [
    'tipo'         => 'personalizado',
    'idproducto'   => null,
    'nombre'       => $descripcion,
    'imagen'       => 'descarga.png',
    'precio'       => (float) $total,
    'cantidad'     => 1,
    'modificadores'=> !empty($nombresAde) ? ('Aderezos: ' . implode(', ', $nombresAde)) : '',
];

$totalItems = 0;
$totalPesos = 0;
foreach ($_SESSION['carrito'] as $it) {
    $totalItems += $it['cantidad'];
    $totalPesos += ($it['precio'] * $it['cantidad']);
}

echo json_encode([
    'ok'               => true,
    'total'            => $total,
    'descripcion'      => $descripcion,
    'total_items'      => $totalItems,
    'total_pesos'      => $totalPesos,
    'total_formateado' => '$' . number_format($totalPesos, 0, ',', '.')
], JSON_UNESCAPED_UNICODE);
