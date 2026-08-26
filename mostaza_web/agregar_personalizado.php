<?php
session_start();
header('Content-Type: application/json');

// Mismos precios que en script.js — se recalculan acá para no confiar en el cliente.
$INGREDIENTES = [
    'pan'     => ['precio' => 0,    'label' => 'Pan'],
    'carne'   => ['precio' => 2500, 'label' => 'Carne'],
    'pepino'  => ['precio' => 1500, 'label' => 'Pepino'],
    'huevo'   => ['precio' => 500,  'label' => 'Huevo'],
    'queso'   => ['precio' => 1000, 'label' => 'Queso'],
    'lechuga' => ['precio' => 500,  'label' => 'Lechuga'],
    'tomate'  => ['precio' => 600,  'label' => 'Tomate'],
    'tocino'  => ['precio' => 1000, 'label' => 'Tocino'],
    'cebolla' => ['precio' => 900,  'label' => 'Cebolla'],
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
$acomp = $input['acompanamiento'] ?? 'papas';

if (!isset($ACOMPANAMIENTOS[$acomp])) {
    $acomp = 'papas';
}

$nombresElegidos = [];
$total = 0;

foreach ($ingredientesSeleccionados as $ing) {
    if (isset($INGREDIENTES[$ing])) {
        $total += $INGREDIENTES[$ing]['precio'];
        $nombresElegidos[] = $INGREDIENTES[$ing]['label'];
    }
}

if (empty($nombresElegidos)) {
    echo json_encode(['ok' => false, 'error' => 'Elegí al menos un ingrediente.']);
    exit();
}

$total += $ACOMPANAMIENTOS[$acomp]['precio'];
$nombresElegidos[] = $ACOMPANAMIENTOS[$acomp]['label'];

$descripcion = 'Hamburguesa personalizada: ' . implode(', ', $nombresElegidos);

if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

$key = 'custom_' . uniqid();
$_SESSION['carrito'][$key] = [
    'tipo'       => 'personalizado',
    'idproducto' => null,
    'nombre'     => $descripcion,
    'imagen'     => 'descarga.png',
    'precio'     => (float) $total,
    'cantidad'   => 1,
];

echo json_encode(['ok' => true, 'total' => $total, 'descripcion' => $descripcion]);
