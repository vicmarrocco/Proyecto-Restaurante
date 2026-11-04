<?php
// ============================================================
// api/pos/mesas.php
// Devuelve las secciones del restaurante y el estado en tiempo
// real de cada mesa (disponible / ocupada con comanda activa).
// ============================================================
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once(__DIR__ . '/../../conexion.php');

// Manejo de acciones POST (ocupar / desocupar mesas desde apps o API)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true) ?? $_POST;
    $accion = $input['accion'] ?? '';
    $numeroMesa = intval($input['numero_mesa'] ?? 0);
    $idMesero = intval($input['idempleado_mesero'] ?? ($input['idempleado'] ?? 0));
    $nombreMesero = trim($input['mesero_nombre'] ?? '');
    $idSeccionMesero = intval($input['fk_id_seccion'] ?? ($input['idseccion'] ?? 0));
    
    if ($numeroMesa <= 0) {
        echo json_encode(['ok' => false, 'error' => 'Número de mesa inválido']);
        exit;
    }

    // Obtener datos del mesero si faltan
    if ($idMesero > 0 && ($idSeccionMesero <= 0 || empty($nombreMesero))) {
        $resEmp = $conn->query("SELECT fk_id_seccion, CONCAT(nombre, ' ', apellido) AS full_nombre FROM empleado WHERE idempleado = $idMesero");
        if ($resEmp && $rowEmp = $resEmp->fetch_assoc()) {
            if ($idSeccionMesero <= 0) $idSeccionMesero = intval($rowEmp['fk_id_seccion']);
            if (empty($nombreMesero)) $nombreMesero = $rowEmp['full_nombre'];
        }
    }

    // Validar existencia de mesa y sección
    $stmtMesa = $conn->prepare("SELECT m.idmesa, m.fk_id_seccion, m.estado, m.idempleado_mesero, m.mesero_nombre, s.nombre AS seccion_nombre FROM mesa m JOIN seccion_mesa s ON m.fk_id_seccion = s.idseccion WHERE m.numero_mesa = ?");
    $stmtMesa->bind_param("i", $numeroMesa);
    $stmtMesa->execute();
    $mesaObj = $stmtMesa->get_result()->fetch_assoc();

    if (!$mesaObj) {
        echo json_encode(['ok' => false, 'error' => "La Mesa #$numeroMesa no existe"]);
        exit;
    }

    // BLOQUEO ESTRICTO POR SECCIÓN (Opción A):
    if ($idSeccionMesero > 0 && intval($mesaObj['fk_id_seccion']) !== $idSeccionMesero) {
        $resSecNombre = $conn->query("SELECT nombre FROM seccion_mesa WHERE idseccion = $idSeccionMesero");
        $miSecNombre = ($resSecNombre && $rSN = $resSecNombre->fetch_assoc()) ? $rSN['nombre'] : "Sección #$idSeccionMesero";
        echo json_encode([
            'ok' => false,
            'error' => "Bloqueo Anticolisión: La Mesa #$numeroMesa pertenece a '{$mesaObj['seccion_nombre']}'. Tu sector asignado es '$miSecNombre'. Solo podés operar mesas de tu sector."
        ]);
        exit;
    }
    
    if ($accion === 'ocupar') {
        $stmtUpd = $conn->prepare("UPDATE mesa SET estado = 'ocupada', idempleado_mesero = ?, mesero_nombre = ? WHERE numero_mesa = ?");
        $stmtUpd->bind_param("isi", $idMesero, $nombreMesero, $numeroMesa);
        $stmtUpd->execute();
        echo json_encode([
            'ok' => true,
            'mensaje' => "Mesa #$numeroMesa ocupada por " . ($nombreMesero ?: 'Mesero'),
            'idempleado_mesero' => $idMesero,
            'mesero_nombre' => $nombreMesero
        ]);
        exit;
    }
    
    if ($accion === 'desocupar' || $accion === 'liberar') {
        $conn->query("UPDATE mesa SET estado = 'disponible', idempleado_mesero = NULL, mesero_nombre = NULL WHERE numero_mesa = $numeroMesa");
        $conn->query("UPDATE pedido_web SET estado = 'Entregado' WHERE tipo_entrega = 'salon' AND numero_mesa = $numeroMesa AND estado IN ('Recibido', 'En Cocina', 'En Preparacion', 'Listo para Despacho', 'Listo')");
        echo json_encode(['ok' => true, 'mensaje' => "Mesa #$numeroMesa desocupada y liberada"]);
        exit;
    }
    
    echo json_encode(['ok' => false, 'error' => 'Acción no reconocida: ' . htmlspecialchars($accion)]);
    exit;
}

// 1. Obtener comandas activas en salón
$stmtActive = $conn->query(
    "SELECT p.idpedido, p.numero_mesa, p.total, p.fecha, p.estado, p.idusuario, p.metodo_pago,
            p.idempleado_mesero, p.mesero_nombre,
            u.nombre, u.apellido
     FROM pedido_web p
     LEFT JOIN usuario_web u ON p.idusuario = u.idusuario
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
            $mesasOcupadas[$num] = [
                'idpedido' => intval($m['idpedido']),
                'numero_mesa' => $num,
                'cliente' => !empty($m['nombre']) ? ($m['nombre'] . ' ' . $m['apellido']) : 'Cliente Salón',
                'total' => floatval($m['total']),
                'total_formateado' => '$' . number_format($m['total'], 0, ',', '.'),
                'hora' => date('H:i', strtotime($m['fecha'])),
                'minutos_transcurridos' => max(0, $tiempoMinutos),
                'tiempo_texto' => ($tiempoMinutos < 1) ? 'Recién sentados' : "Hace $tiempoMinutos min",
                'estado' => $m['estado'],
                'metodo_pago' => $m['metodo_pago'],
                'idempleado_mesero' => !empty($m['idempleado_mesero']) ? intval($m['idempleado_mesero']) : null,
                'mesero_nombre' => !empty($m['mesero_nombre']) ? $m['mesero_nombre'] : null
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
            'nombre' => $sec['nombre'],
            'descripcion' => $sec['descripcion'],
            'icono' => $sec['icono']
        ];
    }
} else {
    // Fallback estándar si aún no se inicializó la tabla
    $secciones = [
        ['idseccion' => 1, 'nombre' => 'Salón Principal', 'descripcion' => 'Área central', 'icono' => 'fa-utensils'],
        ['idseccion' => 2, 'nombre' => 'Terraza Exterior', 'descripcion' => 'Mesas al aire libre', 'icono' => 'fa-umbrella-beach'],
        ['idseccion' => 3, 'nombre' => 'Planta Alta / VIP', 'descripcion' => 'Área VIP', 'icono' => 'fa-crown'],
        ['idseccion' => 4, 'nombre' => 'Barra & Boxes', 'descripcion' => 'Boxes y barra', 'icono' => 'fa-wine-glass']
    ];
}

// 3. Obtener Mesas con sus Secciones y Mesero Asignado
$stmtMesas = $conn->query("SELECT m.idmesa, m.numero_mesa, m.fk_id_seccion, m.capacidad, m.estado AS mesa_estado, m.idempleado_mesero, m.mesero_nombre, s.nombre AS seccion_nombre, s.icono AS seccion_icono FROM mesa m JOIN seccion_mesa s ON m.fk_id_seccion = s.idseccion WHERE m.activa = 1 ORDER BY m.numero_mesa ASC");

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

        $mesas[] = [
            'idmesa' => intval($r['idmesa']),
            'numero' => $numMesa,
            'numero_mesa' => $numMesa,
            'idseccion' => intval($r['fk_id_seccion']),
            'seccion_nombre' => $r['seccion_nombre'],
            'seccion_icono' => $r['seccion_icono'],
            'capacidad' => intval($r['capacidad']),
            'ocupada' => $ocupada,
            'estado_texto' => $ocupada ? 'OCUPADA' : 'DISPONIBLE',
            'idempleado_mesero' => $meseroIdMesa ? intval($meseroIdMesa) : null,
            'mesero_nombre' => $meseroNomMesa ?: null,
            'info' => $tieneComanda ? $mesasOcupadas[$numMesa] : ($ocupada ? [
                'cliente' => 'Clientes en mesa',
                'idpedido' => '-',
                'total_formateado' => '$0',
                'estado' => 'Ocupada sin comanda',
                'tiempo_texto' => 'En espera',
                'idempleado_mesero' => $meseroIdMesa ? intval($meseroIdMesa) : null,
                'mesero_nombre' => $meseroNomMesa ?: null
            ] : null)
        ];
    }
} else {
    // Fallback para 24 mesas
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

// Agrupar mesas por sección para consumo directo de frontend
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

echo json_encode([
    'ok' => true,
    'total_mesas' => count($mesas),
    'total_libres' => $totalLibres,
    'total_ocupadas' => $totalOcupadas,
    'secciones' => $secciones,
    'mesas' => $mesas,
    'mesas_por_seccion' => $mesasPorSeccion
]);

