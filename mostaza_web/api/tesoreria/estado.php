<?php
// ============================================================
// api/tesoreria/estado.php
// Consulta el balance consolidado y movimientos de Tesorería
// ============================================================
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once(__DIR__ . '/../../conexion.php');
require_once(__DIR__ . '/TesoreriaService.php');

$limite = intval($_GET['limite'] ?? 30);
$limite = max(5, min(100, $limite));

$estado = TesoreriaService::obtenerEstado($conn, $limite);
echo json_encode($estado);
