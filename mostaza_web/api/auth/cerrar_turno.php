<?php
// ============================================================
// api/auth/cerrar_turno.php
// Registra la hora de salida del empleado y calcula las horas trabajadas.
// ============================================================
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once(__DIR__ . '/../../conexion.php');

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$idempleado = (int)($input['idempleado'] ?? $_SESSION['cajero_id'] ?? 0);
$idregistro = (int)($input['idregistro_turno'] ?? $_SESSION['idregistro_turno'] ?? 0);
$observaciones = htmlspecialchars(trim($input['observaciones'] ?? 'Turno cerrado desde App Cajero'));

if ($idempleado <= 0 && $idregistro <= 0) {
    echo json_encode(['ok' => false, 'error' => 'No se especificó el empleado o registro de turno.']);
    exit();
}

// Actualizar registro activo
if ($idregistro > 0) {
    $stmt = $conn->prepare("
        UPDATE registro_turno_empleado 
        SET hora_salida = NOW(),
            horas_trabajadas = ROUND(TIMESTAMPDIFF(SECOND, hora_entrada, NOW()) / 3600, 2),
            estado = 'Finalizado',
            observaciones = ?
        WHERE idregistro = ?
    ");
    $stmt->bind_param("si", $observaciones, $idregistro);
    $stmt->execute();
} else {
    $stmt = $conn->prepare("
        UPDATE registro_turno_empleado 
        SET hora_salida = NOW(),
            horas_trabajadas = ROUND(TIMESTAMPDIFF(SECOND, hora_entrada, NOW()) / 3600, 2),
            estado = 'Finalizado',
            observaciones = ?
        WHERE fk_id_empleado = ? AND estado = 'Activo'
    ");
    $stmt->bind_param("si", $observaciones, $idempleado);
    $stmt->execute();
}

// Consultar datos del registro finalizado
$query = "
    SELECT r.idregistro, r.fecha, r.hora_entrada, r.hora_salida, r.horas_trabajadas, r.estado,
           e.nombre, e.apellido, t.nombre AS turno_nombre
    FROM registro_turno_empleado r
    JOIN empleado e ON r.fk_id_empleado = e.idempleado
    JOIN turnos t ON r.fk_id_turno = t.idturnos
    WHERE " . ($idregistro > 0 ? "r.idregistro = $idregistro" : "r.fk_id_empleado = $idempleado") . "
    ORDER BY r.idregistro DESC
    LIMIT 1
";

$res = $conn->query($query);
$turnoFinalizado = ($res && $res->num_rows > 0) ? $res->fetch_assoc() : null;

// Destruir sesión en el servidor
session_unset();
session_destroy();

echo json_encode([
    'ok' => true,
    'mensaje' => '¡Salida registrada y turno cerrado exitosamente!',
    'resumen' => $turnoFinalizado
]);
