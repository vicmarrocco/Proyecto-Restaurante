<?php
// ============================================================
// tracking_estado_ajax.php
// Devuelve el estado actual de un pedido en tiempo real con mapeo
// detallado de progreso, títulos, descripciones y datos del personal asignado.
// ============================================================
session_start();
header('Content-Type: application/json');
require('conexion.php');

$idpedido = intval($_GET['id'] ?? 0);
$idusuario = intval($_SESSION['usuario_id'] ?? 0);

if ($idpedido <= 0) {
    echo json_encode(['ok' => false, 'error' => 'ID de pedido inválido']);
    exit();
}

$stmt = $conn->prepare("
    SELECT idpedido, estado, tipo_entrega, fecha, repartidor_asignado, cocinero_nombre, 
           cajero_nombre, mesero_nombre, duracion_cocina_minutos, duracion_entrega_minutos, 
           numero_mesa, motivo_cancelacion, cancelado_por, direccion_envio, numero_caja
    FROM pedido_web 
    WHERE idpedido = ? AND (idusuario = ? OR idusuario IS NULL OR ? = 0)
");
$stmt->bind_param("iii", $idpedido, $idusuario, $idusuario);
$stmt->execute();
$pedido = $stmt->get_result()->fetch_assoc();

if (!$pedido) {
    echo json_encode(['ok' => false, 'error' => 'Pedido no encontrado']);
    exit();
}

$estado = trim($pedido['estado'] ?? 'Recibido');
$tipoEntrega = $pedido['tipo_entrega'] ?? 'delivery';
$repartidor = trim($pedido['repartidor_asignado'] ?? '');
$cocinero = trim($pedido['cocinero_nombre'] ?? '');
$cajero = trim($pedido['cajero_nombre'] ?? '');
$mesero = trim($pedido['mesero_nombre'] ?? '');
$mesa = $pedido['numero_mesa'];

// Buscar datos del vehículo del repartidor si está asignado
$vehiculoInfo = '';
if (!empty($repartidor)) {
    $stmtRep = $conn->prepare("SELECT vehiculo, patente, avatar FROM empleado WHERE fk_id_rol = 6 AND (CONCAT(nombre, ' ', apellido) = ? OR nombre = ?) LIMIT 1");
    $stmtRep->bind_param("ss", $repartidor, $repartidor);
    $stmtRep->execute();
    $rInfo = $stmtRep->get_result()->fetch_assoc();
    if ($rInfo && !empty($rInfo['vehiculo'])) {
        $vehiculoInfo = $rInfo['vehiculo'] . ($rInfo['patente'] ? " · Patente {$rInfo['patente']}" : "");
    }
}

$paso = 1;
$porcentajeFill = '15%';
$titulo = '¡Tu pedido fue recibido!';
$descripcion = 'El local ya tiene tu orden y está a punto de comenzar a prepararla en cocina.';
$esCancelado = false;

if ($estado === 'Recibido') {
    $paso = 1;
    $porcentajeFill = '15%';
    $titulo = '¡Tu pedido fue recibido!';
    $origenTxt = $cajero ? "Recibido en Mostrador por <strong>" . htmlspecialchars($cajero) . "</strong>." : "Recibido vía web en nuestro sistema central.";
    $descripcion = "$origenTxt Ya está en cola para ingresar a la parrilla.";
} elseif (in_array($estado, ['En Cocina', 'En Preparacion', 'En Preparación', 'Preparando'])) {
    $paso = 2;
    $porcentajeFill = '48%';
    $titulo = '¡Marchando en Cocina!';
    $cocTxt = $cocinero ? "El parrillero <strong>" . htmlspecialchars($cocinero) . "</strong>" : "Nuestros parrilleros";
    $descripcion = "$cocTxt está preparando tus hamburguesas con la mejor calidad Mostaza.";
} elseif (in_array($estado, ['Listo', 'Listo para Despacho'])) {
    if ($tipoEntrega === 'delivery') {
        $paso = 2;
        $porcentajeFill = '60%';
        $titulo = '¡Listo en Cocina! Empaquetado para despacho';
        $cocTxt = $cocinero ? "Comanda terminada por <strong>" . htmlspecialchars($cocinero) . "</strong>" : "Tu pedido ya salió de la parrilla";
        $descripcion = "$cocTxt y se encuentra empaquetado esperando la salida del repartidor.";
    } else {
        $paso = 3;
        $porcentajeFill = '78%';
        if ($tipoEntrega === 'salon') {
            $titulo = '¡Tu comanda está lista en Cocina!';
            $meseroTxt = !empty($mesero) ? "Tu mesero <strong>" . htmlspecialchars($mesero) . "</strong>" : "Un mesero del salón";
            $mesaTxt = !empty($mesa) ? " a tu Mesa #$mesa" : " a tu mesa";
            $descripcion = "¡Tu comanda salió de la parrilla! $meseroTxt ya la lleva en bandeja$mesaTxt.";
        } else {
            $titulo = '¡Tu pedido está listo para retirar!';
            $descripcion = 'Podés acercarte al mostrador con tu número de comanda para retirarlo.';
        }
    }
} elseif ($estado === 'En Camino') {
    $paso = 3;
    $porcentajeFill = '78%';
    $titulo = '¡El repartidor va en camino!';
    $nomRep = !empty($repartidor) ? "El repartidor <strong>" . htmlspecialchars($repartidor) . "</strong>" : "El repartidor";
    $vehTxt = !empty($vehiculoInfo) ? " en $vehiculoInfo" : "";
    $descripcion = "$nomRep$vehTxt salió de la sucursal y se dirige a tu domicilio.";
} elseif ($estado === 'Entregado') {
    $paso = 4;
    $porcentajeFill = '100%';
    $durTot = $pedido['duracion_total_minutos'] ? " Tiempo total: {$pedido['duracion_total_minutos']} min." : "";

    if ($tipoEntrega === 'salon') {
        $titulo = '¡Comanda Servida en tu Mesa!';
        $meseroTxt = !empty($mesero) ? "Servido por el mesero <strong>" . htmlspecialchars($mesero) . "</strong>." : "Servido por el personal de salón.";
        $mesaTxt = !empty($mesa) ? " en la Mesa #$mesa" : "";
        $descripcion = "¡Tu comida fue entregada$mesaTxt! $meseroTxt ¡Que lo disfrutes!$durTot";
    } else {
        $titulo = '¡Pedido Entregado! ¡Que lo disfrutes!';
        $repTxt = !empty($repartidor) ? "Entregado por <strong>" . htmlspecialchars($repartidor) . "</strong>. " : "";
        $descripcion = "{$repTxt}¡Gracias por elegir Mostaza!$durTot";
    }
} elseif ($estado === 'Cancelado') {
    $esCancelado = true;
    $paso = 0;
    $porcentajeFill = '0%';
    $titulo = 'Pedido Cancelado';
    $motivo = $pedido['motivo_cancelacion'] ?? 'Cancelado por el establecimiento';
    $cancPor = $pedido['cancelado_por'] ? " por {$pedido['cancelado_por']}" : "";
    $descripcion = "El pedido fue cancelado$cancPor: $motivo.";
}

echo json_encode([
    'ok' => true,
    'idpedido' => $pedido['idpedido'],
    'estado' => $estado,
    'tipo_entrega' => $tipoEntrega,
    'paso' => $paso,
    'porcentajeFill' => $porcentajeFill,
    'titulo' => $titulo,
    'descripcion' => $descripcion,
    'cajero' => $cajero ?: null,
    'cocinero' => $cocinero ?: null,
    'repartidor' => $repartidor ?: null,
    'vehiculo_repartidor' => $vehiculoInfo ?: null,
    'mesero' => $mesero ?: null,
    'numero_mesa' => $mesa,
    'numero_caja' => $pedido['numero_caja'] ?? 1,
    'duracion_cocina_minutos' => $pedido['duracion_cocina_minutos'],
    'duracion_entrega_minutos' => $pedido['duracion_entrega_minutos'],
    'es_cancelado' => $esCancelado
]);
