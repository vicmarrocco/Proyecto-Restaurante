<?php
// ============================================================
// api/delivery/auth.php
// Autenticación de Repartidores Oficiales de Mostaza, Control de Turnos y Flota
// ============================================================
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    exit(0);
}

require_once(__DIR__ . '/../../conexion.php');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$accion = trim($input['accion'] ?? ($_GET['accion'] ?? ''));

try {
    // 1. Listar Flota Oficial de Repartidores
    if ($accion === 'lista_repartidores' || ($method === 'GET' && empty($accion))) {
        $stmtFleet = $conn->query("
            SELECT idrepartidor, nombre, dni, telefono, vehiculo, patente, avatar, sucursal_base, calificacion, viajes_totales, estado_servicio
            FROM repartidor 
            WHERE activo = 1 
            ORDER BY idrepartidor ASC
        ");
        
        $repartidores = [];
        if ($stmtFleet) {
            while ($r = $stmtFleet->fetch_assoc()) {
                $repartidores[] = [
                    'id' => intval($r['idrepartidor']),
                    'nombre' => $r['nombre'],
                    'dni' => $r['dni'],
                    'telefono' => $r['telefono'],
                    'vehiculo' => $r['vehiculo'],
                    'patente' => $r['patente'],
                    'avatar' => $r['avatar'] ?: '🛵',
                    'sucursal_base' => $r['sucursal_base'],
                    'calificacion' => floatval($r['calificacion']),
                    'viajes_totales' => intval($r['viajes_totales']),
                    'estado_servicio' => $r['estado_servicio']
                ];
            }
        }
        
        echo json_encode([
            'ok' => true,
            'empresa' => 'Mostaza Argentina S.A.',
            'flota' => $repartidores
        ]);
        exit();
    }

    // 2. Inicio de Sesión de Repartidor con PIN
    if ($accion === 'login' || ($method === 'POST' && (empty($accion) || $accion === 'iniciar_turno'))) {
        $repartidorId = intval($input['repartidor_id'] ?? ($input['id'] ?? 0));
        $nombre = trim($input['nombre'] ?? '');
        $pin = trim($input['pin'] ?? '');

        if ($repartidorId <= 0 && empty($nombre)) {
            echo json_encode(['ok' => false, 'error' => 'Debe seleccionar un repartidor de la empresa.']);
            exit();
        }

        // Buscar repartidor en la tabla oficial
        if ($repartidorId > 0) {
            $stmtRider = $conn->prepare("SELECT * FROM repartidor WHERE idrepartidor = ? AND activo = 1 LIMIT 1");
            $stmtRider->bind_param("i", $repartidorId);
        } else {
            $stmtRider = $conn->prepare("SELECT * FROM repartidor WHERE nombre = ? AND activo = 1 LIMIT 1");
            $stmtRider->bind_param("s", $nombre);
        }
        $stmtRider->execute();
        $rider = $stmtRider->get_result()->fetch_assoc();

        if (!$rider) {
            echo json_encode(['ok' => false, 'error' => 'El repartidor no pertenece a la flota activa de Mostaza.']);
            exit();
        }

        // Validación de PIN
        if (!empty($rider['pin']) && !empty($pin) && $pin !== $rider['pin']) {
            echo json_encode(['ok' => false, 'error' => 'El PIN ingresado es incorrecto. Verifique sus credenciales.']);
            exit();
        }

        // Generar token de sesión único
        $token = 'tok_del_' . bin2hex(random_bytes(16));

        // Registrar o verificar turno en registro_turno_empleado
        $nombreRider = $rider['nombre'];
        $stmtCheck = $conn->prepare("
            SELECT idregistro, hora_entrada 
            FROM registro_turno_empleado 
            WHERE nombre_empleado = ? AND rol = 'Delivery' AND fecha = CURDATE() AND estado = 'Activo' 
            ORDER BY idregistro DESC LIMIT 1
        ");
        $stmtCheck->bind_param("s", $nombreRider);
        $stmtCheck->execute();
        $resCheck = $stmtCheck->get_result();

        $idregistro = null;
        $horaEntrada = date('H:i');

        if ($resCheck && $resCheck->num_rows > 0) {
            $fila = $resCheck->fetch_assoc();
            $idregistro = intval($fila['idregistro']);
            $horaEntrada = date('H:i', strtotime($fila['hora_entrada']));
        } else {
            $stmtInsert = $conn->prepare("
                INSERT INTO registro_turno_empleado (fk_id_empleado, fk_id_turno, nombre_empleado, rol, fecha, hora_entrada, estado, origen, observaciones) 
                VALUES (1, 1, ?, 'Delivery', CURDATE(), NOW(), 'Activo', 'App Delivery Oficial', 'Fichada de ingreso y turno activo')
            ");
            $stmtInsert->bind_param("s", $nombreRider);
            $stmtInsert->execute();
            $idregistro = $stmtInsert->insert_id;
        }

        // Actualizar estado a 'Disponible' en la flota
        $conn->query("UPDATE repartidor SET estado_servicio = 'Disponible' WHERE idrepartidor = " . intval($rider['idrepartidor']));

        echo json_encode([
            'ok' => true,
            'mensaje' => 'Bienvenido ' . $rider['nombre'] . '. Tu turno fue fichado con éxito.',
            'token' => $token,
            'idregistro' => $idregistro,
            'hora_ingreso' => $horaEntrada,
            'fecha' => date('Y-m-d'),
            'repartidor' => [
                'id' => intval($rider['idrepartidor']),
                'nombre' => $rider['nombre'],
                'dni' => $rider['dni'],
                'telefono' => $rider['telefono'],
                'vehiculo' => $rider['vehiculo'],
                'patente' => $rider['patente'],
                'avatar' => $rider['avatar'] ?: '🛵',
                'sucursal_base' => $rider['sucursal_base'],
                'calificacion' => floatval($rider['calificacion']),
                'viajes_totales' => intval($rider['viajes_totales']),
                'estado' => 'Disponible'
            ]
        ]);
        exit();
    }

    // 3. Cierre de Turno y Salida
    if ($accion === 'cerrar_turno') {
        $idregistro = intval($input['idregistro'] ?? 0);
        $nombre = trim($input['nombre'] ?? '');
        $repartidorId = intval($input['repartidor_id'] ?? 0);

        if ($idregistro > 0) {
            $stmtCierre = $conn->prepare("
                UPDATE registro_turno_empleado 
                SET hora_salida = NOW(), estado = 'Finalizado', horas_trabajadas = ROUND(TIMESTAMPDIFF(MINUTE, hora_entrada, NOW()) / 60, 2) 
                WHERE idregistro = ?
            ");
            $stmtCierre->bind_param("i", $idregistro);
            $stmtCierre->execute();
        } elseif (!empty($nombre)) {
            $stmtCierre = $conn->prepare("
                UPDATE registro_turno_empleado 
                SET hora_salida = NOW(), estado = 'Finalizado', horas_trabajadas = ROUND(TIMESTAMPDIFF(MINUTE, hora_entrada, NOW()) / 60, 2) 
                WHERE nombre_empleado = ? AND estado = 'Activo' AND fecha = CURDATE()
            ");
            $stmtCierre->bind_param("s", $nombre);
            $stmtCierre->execute();
        }

        if ($repartidorId > 0) {
            $conn->query("UPDATE repartidor SET estado_servicio = 'Desconectado' WHERE idrepartidor = " . $repartidorId);
        } elseif (!empty($nombre)) {
            $conn->query("UPDATE repartidor SET estado_servicio = 'Desconectado' WHERE nombre = '" . $conn->real_escape_string($nombre) . "'");
        }

        echo json_encode([
            'ok' => true,
            'mensaje' => 'Turno de reparto finalizado y hora de salida registrada con éxito.',
            'hora_salida' => date('H:i')
        ]);
        exit();
    }

    // 4. Consulta de Estado de Turno
    if ($accion === 'estado_turno') {
        $nombre = trim($_GET['nombre'] ?? ($input['nombre'] ?? ''));
        $stmtS = $conn->prepare("
            SELECT idregistro, hora_entrada, estado 
            FROM registro_turno_empleado 
            WHERE nombre_empleado = ? AND rol = 'Delivery' AND fecha = CURDATE() AND estado = 'Activo' 
            ORDER BY idregistro DESC LIMIT 1
        ");
        $stmtS->bind_param("s", $nombre);
        $stmtS->execute();
        $resS = $stmtS->get_result();

        if ($resS && $resS->num_rows > 0) {
            $t = $resS->fetch_assoc();
            echo json_encode([
                'ok' => true,
                'activo' => true,
                'idregistro' => intval($t['idregistro']),
                'hora_entrada' => date('H:i', strtotime($t['hora_entrada']))
            ]);
        } else {
            echo json_encode([
                'ok' => true,
                'activo' => false,
                'mensaje' => 'No hay turno activo para hoy.'
            ]);
        }
        exit();
    }

    echo json_encode(['ok' => false, 'error' => 'Acción no reconocida.']);

} catch (Exception $e) {
    echo json_encode(['ok' => false, 'error' => 'Error en autenticación: ' . $e->getMessage()]);
}
