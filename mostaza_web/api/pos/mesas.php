<?php
// ============================================================
// api/pos/mesas.php
// Devuelve las secciones del restaurante y el estado en tiempo
// real de cada mesa (disponible / ocupada con comanda activa).
// ============================================================
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once(__DIR__ . '/../../conexion.php');

// Asegurar columnas de cliente ubicado y mesero responsable en tabla mesa
$colsMesaCheck = $conn->query("SHOW COLUMNS FROM `mesa`");
if ($colsMesaCheck) {
    $mesaColsArr = [];
    while ($rMC = $colsMesaCheck->fetch_assoc()) {
        $mesaColsArr[] = $rMC['Field'];
    }
    $extraColsMesa = [
        'idempleado_mesero' => "INT(11) DEFAULT NULL",
        'mesero_nombre' => "VARCHAR(100) DEFAULT NULL",
        'cliente_nombre' => "VARCHAR(100) DEFAULT NULL",
        'comensales' => "INT(11) DEFAULT NULL"
    ];
    foreach ($extraColsMesa as $cMName => $cMDef) {
        if (!in_array($cMName, $mesaColsArr)) {
            $conn->query("ALTER TABLE `mesa` ADD `$cMName` $cMDef");
        }
    }
}

// Manejo de acciones POST (ocupar / ubicar cliente / desocupar mesas desde apps o API)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true) ?? $_POST;
    $accion = $input['accion'] ?? '';
    $numeroMesa = intval($input['numero_mesa'] ?? 0);
    $idMesero = intval($input['idempleado_mesero'] ?? ($input['idempleado'] ?? 0));
    $nombreMesero = repararTildesTexto(trim($input['mesero_nombre'] ?? ''));
    $idSeccionMesero = intval($input['fk_id_seccion'] ?? ($input['idseccion'] ?? 0));
    $clienteNombre = repararTildesTexto(trim($input['cliente_nombre'] ?? ($input['nombre_cliente'] ?? '')));
    $comensales = intval($input['comensales'] ?? 0);
    
    if ($numeroMesa <= 0) {
        echo json_encode(['ok' => false, 'error' => 'Número de mesa inválido'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Obtener datos y secciones asignadas del mesero
    $seccionesPermitidasMesero = [];
    $miSecNombre = '';
    if (!empty($input['secciones_asignadas']) && is_array($input['secciones_asignadas'])) {
        $seccionesPermitidasMesero = array_values(array_filter(array_map('intval', $input['secciones_asignadas'])));
    }
    if ($idMesero > 0) {
        $resEmp = $conn->query("SELECT fk_id_seccion, CONCAT(nombre, ' ', apellido) AS full_nombre FROM empleado WHERE idempleado = $idMesero");
        if ($resEmp && $rowEmp = $resEmp->fetch_assoc()) {
            if ($idSeccionMesero <= 0) $idSeccionMesero = intval($rowEmp['fk_id_seccion']);
            if (empty($nombreMesero)) $nombreMesero = repararTildesTexto($rowEmp['full_nombre']);
        }
        $infoSecM = obtenerSeccionesMesero($conn, $idMesero);
        if (!empty($infoSecM['ids'])) {
            $seccionesPermitidasMesero = array_values(array_unique(array_merge($seccionesPermitidasMesero, $infoSecM['ids'])));
            $miSecNombre = repararTildesTexto($infoSecM['texto']);
        }
    }
    if (empty($seccionesPermitidasMesero) && $idSeccionMesero > 0) {
        $seccionesPermitidasMesero = [$idSeccionMesero];
    }

    // Validar existencia de mesa y sección
    $stmtMesa = $conn->prepare("SELECT m.idmesa, m.fk_id_seccion, m.capacidad, m.estado, m.idempleado_mesero, m.mesero_nombre, m.cliente_nombre, m.comensales, s.nombre AS seccion_nombre FROM mesa m JOIN seccion_mesa s ON m.fk_id_seccion = s.idseccion WHERE m.numero_mesa = ?");
    $stmtMesa->bind_param("i", $numeroMesa);
    $stmtMesa->execute();
    $mesaObj = $stmtMesa->get_result()->fetch_assoc();

    if (!$mesaObj) {
        echo json_encode(['ok' => false, 'error' => "La Mesa #$numeroMesa no existe"], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $secMesaNombre = repararTildesTexto($mesaObj['seccion_nombre']);

    // BLOQUEO ESTRICTO POR SECCIONES ASIGNADAS (Soporta Múltiples Sectores):
    if (!empty($seccionesPermitidasMesero) && !in_array(intval($mesaObj['fk_id_seccion']), $seccionesPermitidasMesero, true)) {
        if (empty($miSecNombre)) {
            $idsStr = implode(',', array_map('intval', $seccionesPermitidasMesero));
            $resSecNombre = $conn->query("SELECT GROUP_CONCAT(nombre SEPARATOR ' + ') AS nombres FROM seccion_mesa WHERE idseccion IN ($idsStr)");
            $miSecNombre = ($resSecNombre && ($rSN = $resSecNombre->fetch_assoc()) && !empty($rSN['nombres'])) ? repararTildesTexto($rSN['nombres']) : "Sector asignado";
        }
        echo json_encode([
            'ok' => false,
            'error' => "Bloqueo Anticolisión: La Mesa #$numeroMesa pertenece a '$secMesaNombre'. Tus sectores asignados son: '$miSecNombre'. Solo podés operar mesas de tus sectores."
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    if ($accion === 'ocupar') {
        if ($comensales <= 0) {
            $comensales = intval($mesaObj['capacidad'] ?? 2);
        }
        if (empty($clienteNombre)) {
            $clienteNombre = "Clientes Mesa #$numeroMesa";
        }
        $stmtUpd = $conn->prepare("UPDATE mesa SET estado = 'ocupada', idempleado_mesero = ?, mesero_nombre = ?, cliente_nombre = ?, comensales = ? WHERE numero_mesa = ?");
        $stmtUpd->bind_param("issii", $idMesero, $nombreMesero, $clienteNombre, $comensales, $numeroMesa);
        $stmtUpd->execute();
        echo json_encode([
            'ok' => true,
            'mensaje' => "Cliente ubicado en Mesa #$numeroMesa ($secMesaNombre) · Atiende: " . ($nombreMesero ?: 'Mesero'),
            'numero_mesa' => $numeroMesa,
            'idempleado_mesero' => $idMesero,
            'mesero_nombre' => $nombreMesero,
            'cliente_nombre' => $clienteNombre,
            'comensales' => $comensales
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    if ($accion === 'desocupar' || $accion === 'liberar') {
        $conn->query("UPDATE mesa SET estado = 'disponible', idempleado_mesero = NULL, mesero_nombre = NULL, cliente_nombre = NULL, comensales = NULL WHERE numero_mesa = $numeroMesa");
        $conn->query("UPDATE pedido_web SET estado = 'Entregado' WHERE tipo_entrega = 'salon' AND numero_mesa = $numeroMesa AND estado IN ('Recibido', 'En Cocina', 'En Preparacion', 'En Preparación', 'Listo para Despacho', 'Listo')");
        echo json_encode(['ok' => true, 'mensaje' => "Mesa #$numeroMesa desocupada y liberada"], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    echo json_encode(['ok' => false, 'error' => 'Acción no reconocida: ' . htmlspecialchars($accion)], JSON_UNESCAPED_UNICODE);
    exit;
}

// 1. Obtener comandas activas en salón
$stmtActive = $conn->query(
    "SELECT p.idpedido, p.numero_mesa, p.total, p.fecha, p.estado, p.idusuario, p.metodo_pago,
            p.idempleado_mesero, p.mesero_nombre, p.notas,
            u.nombre, u.apellido, f.nombre_cliente AS factura_cliente
     FROM pedido_web p
     LEFT JOIN usuario_web u ON p.idusuario = u.idusuario
     LEFT JOIN factura f ON p.idpedido = f.idpedido
     WHERE p.tipo_entrega = 'salon' 
       AND p.estado IN ('Recibido', 'En Cocina', 'En Preparacion', 'En Preparación', 'Listo para Despacho', 'Listo') 
       AND p.numero_mesa IS NOT NULL
     ORDER BY p.idpedido DESC"
);

$mesasOcupadas = [];
if ($stmtActive) {
    while ($m = $stmtActive->fetch_assoc()) {
        $num = intval($m['numero_mesa']);
        if (!isset($mesasOcupadas[$num])) {
            $tiempoMinutos = round((time() - strtotime($m['fecha'])) / 60);
            $cliNom = !empty($m['nombre'])
                ? ($m['nombre'] . ' ' . $m['apellido'])
                : (!empty($m['factura_cliente']) && $m['factura_cliente'] !== 'Consumidor Final' ? $m['factura_cliente'] : 'Cliente Salón');
            $mesasOcupadas[$num] = [
                'idpedido' => intval($m['idpedido']),
                'numero_mesa' => $num,
                'cliente' => repararTildesTexto($cliNom),
                'total' => floatval($m['total']),
                'total_formateado' => '$' . number_format($m['total'], 0, ',', '.'),
                'hora' => date('H:i', strtotime($m['fecha'])),
                'minutos_transcurridos' => max(0, $tiempoMinutos),
                'tiempo_texto' => ($tiempoMinutos < 1) ? 'Recién sentados' : "Hace $tiempoMinutos min",
                'estado' => repararTildesTexto($m['estado']),
                'metodo_pago' => $m['metodo_pago'],
                'idempleado_mesero' => !empty($m['idempleado_mesero']) ? intval($m['idempleado_mesero']) : null,
                'mesero_nombre' => !empty($m['mesero_nombre']) ? repararTildesTexto($m['mesero_nombre']) : null
            ];
        }
    }
}

// 2. Obtener Secciones
$stmtSec = $conn->query("SELECT idseccion, nombre, descripcion, icono, orden FROM seccion_mesa ORDER BY orden, idseccion");
$secciones = [];
if ($stmtSec && $stmtSec->num_rows > 0) {
    while ($sec = $stmtSec->fetch_assoc()) {
        $secciones[] = [
            'idseccion' => intval($sec['idseccion']),
            'nombre' => repararTildesTexto($sec['nombre']),
            'descripcion' => repararTildesTexto($sec['descripcion']),
            'icono' => $sec['icono']
        ];
    }
} else {
    $secciones = [
        ['idseccion' => 1, 'nombre' => 'Salón Principal', 'descripcion' => 'Área central', 'icono' => 'fa-utensils'],
        ['idseccion' => 2, 'nombre' => 'Terraza Exterior', 'descripcion' => 'Mesas al aire libre', 'icono' => 'fa-umbrella-beach'],
        ['idseccion' => 3, 'nombre' => 'Planta Alta / VIP', 'descripcion' => 'Área VIP', 'icono' => 'fa-crown'],
        ['idseccion' => 4, 'nombre' => 'Barra & Boxes', 'descripcion' => 'Boxes y barra', 'icono' => 'fa-wine-glass']
    ];
}

// 3. Obtener Mesas con sus Secciones, Cliente Ubicado y Mesero Asignado
$stmtMesas = $conn->query("SELECT m.idmesa, m.numero_mesa, m.fk_id_seccion, m.capacidad, m.estado AS mesa_estado, m.idempleado_mesero, m.mesero_nombre, m.cliente_nombre, m.comensales, s.nombre AS seccion_nombre, s.icono AS seccion_icono FROM mesa m JOIN seccion_mesa s ON m.fk_id_seccion = s.idseccion WHERE m.activa = 1 ORDER BY m.numero_mesa ASC");

$mesas = [];
$totalLibres = 0;
$totalOcupadas = 0;

if ($stmtMesas && $stmtMesas->num_rows > 0) {
    while ($r = $stmtMesas->fetch_assoc()) {
        $numMesa = intval($r['numero_mesa']);
        $tieneComanda = isset($mesasOcupadas[$numMesa]);
        $ocupada = $tieneComanda || ($r['mesa_estado'] === 'ocupada');
        if ($ocupada) $totalOcupadas++; else $totalLibres++;

        $meseroIdMesa = $tieneComanda ? ($mesasOcupadas[$numMesa]['idempleado_mesero'] ?? $r['idempleado_mesero']) : $r['idempleado_mesero'];
        $meseroNomMesa = $tieneComanda ? ($mesasOcupadas[$numMesa]['mesero_nombre'] ?? $r['mesero_nombre']) : $r['mesero_nombre'];
        $meseroNomMesa = $meseroNomMesa ? repararTildesTexto($meseroNomMesa) : null;
        $clienteMesa = !empty($r['cliente_nombre']) ? repararTildesTexto($r['cliente_nombre']) : null;
        $comensalesMesa = !empty($r['comensales']) ? intval($r['comensales']) : null;
        $secNomClean = repararTildesTexto($r['seccion_nombre']);

        $infoObj = null;
        if ($tieneComanda) {
            $infoObj = $mesasOcupadas[$numMesa];
            if ($clienteMesa && ($infoObj['cliente'] === 'Cliente Salón' || $infoObj['cliente'] === 'Consumidor Final')) {
                $infoObj['cliente'] = $clienteMesa;
            }
            if ($comensalesMesa) {
                $infoObj['comensales'] = $comensalesMesa;
            }
        } elseif ($ocupada) {
            $infoObj = [
                'cliente' => $clienteMesa ?: 'Clientes en mesa',
                'comensales' => $comensalesMesa ?: intval($r['capacidad']),
                'idpedido' => '-',
                'total_formateado' => '$0',
                'estado' => 'Esperando pedido',
                'tiempo_texto' => 'Cliente ubicado en mesa',
                'idempleado_mesero' => $meseroIdMesa ? intval($meseroIdMesa) : null,
                'mesero_nombre' => $meseroNomMesa
            ];
        }

        $mesas[] = [
            'idmesa' => intval($r['idmesa']),
            'numero' => $numMesa,
            'numero_mesa' => $numMesa,
            'idseccion' => intval($r['fk_id_seccion']),
            'seccion_nombre' => $secNomClean,
            'seccion_icono' => $r['seccion_icono'],
            'capacidad' => intval($r['capacidad']),
            'ocupada' => $ocupada,
            'estado_texto' => $ocupada ? 'OCUPADA' : 'DISPONIBLE',
            'idempleado_mesero' => $meseroIdMesa ? intval($meseroIdMesa) : null,
            'mesero_nombre' => $meseroNomMesa,
            'cliente_nombre' => $clienteMesa,
            'comensales' => $comensalesMesa,
            'info' => $infoObj
        ];
    }
} else {
    for ($i = 1; $i <= 24; $i++) {
        $secId = ($i <= 8) ? 1 : (($i <= 14) ? 2 : (($i <= 20) ? 3 : 4));
        $secNom = ($secId == 1) ? 'Salón Principal' : (($secId == 2) ? 'Terraza Exterior' : (($secId == 3) ? 'Planta Alta / VIP' : 'Barra & Boxes'));
        $ocupada = isset($mesasOcupadas[$i]);
        if ($ocupada) $totalOcupadas++; else $totalLibres++;

        $mesas[] = [
            'idmesa' => $i,
            'numero' => $i,
            'numero_mesa' => $i,
            'idseccion' => $secId,
            'seccion_nombre' => $secNom,
            'seccion_icono' => 'fa-chair',
            'capacidad' => ($i % 4 == 0) ? 6 : (($i % 3 == 0) ? 2 : 4),
            'ocupada' => $ocupada,
            'estado_texto' => $ocupada ? 'OCUPADA' : 'DISPONIBLE',
            'info' => $ocupada ? $mesasOcupadas[$i] : null
        ];
    }
}

$mesasPorSeccion = [];
foreach ($secciones as $sec) {
    $mesasPorSeccion[$sec['idseccion']] = [
        'idseccion' => $sec['idseccion'],
        'nombre' => $sec['nombre'],
        'icono' => $sec['icono'],
        'mesas' => array_values(array_filter($mesas, function($m) use ($sec) {
            return $m['idseccion'] === $sec['idseccion'];
        }))
    ];
}

echo json_encode(repararTildesArray([
    'ok' => true,
    'total_mesas' => count($mesas),
    'total_libres' => $totalLibres,
    'total_ocupadas' => $totalOcupadas,
    'secciones' => $secciones,
    'mesas' => $mesas,
    'mesas_por_seccion' => $mesasPorSeccion
]), JSON_UNESCAPED_UNICODE);
