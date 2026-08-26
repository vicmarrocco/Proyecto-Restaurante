<?php
// ============================================================
// api/auth/historial_turnos.php
// Consulta el historial de asistencia, entradas y salidas por turno de empleados.
// ============================================================
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once(__DIR__ . '/../../conexion.php');

$idempleado = isset($_GET['idempleado']) ? (int)$_GET['idempleado'] : 0;
$limite = isset($_GET['limite']) ? (int)$_GET['limite'] : 50;

$where = ($idempleado > 0) ? "WHERE r.fk_id_empleado = $idempleado" : "";

$query = "
    SELECT r.idregistro, r.fecha, r.hora_entrada, r.hora_salida, r.horas_trabajadas, r.estado, r.observaciones, r.origen,
           e.idempleado, e.nombre, e.apellido, e.email,
           t.idturnos, t.nombre AS turno_nombre, t.horaInicio, t.horaFin
    FROM registro_turno_empleado r
    JOIN empleado e ON r.fk_id_empleado = e.idempleado
    JOIN turnos t ON r.fk_id_turno = t.idturnos
    $where
    ORDER BY r.idregistro DESC
    LIMIT $limite
";

$res = $conn->query($query);
$turnos = [];

if ($res) {
    while ($row = $res->fetch_assoc()) {
        $turnos[] = [
            'idregistro' => (int)$row['idregistro'],
            'idempleado' => (int)$row['idempleado'],
            'empleado' => $row['nombre'] . ' ' . $row['apellido'],
            'email' => $row['email'],
            'idturno' => (int)$row['idturnos'],
            'turno' => $row['turno_nombre'],
            'horario_turno' => substr($row['horaInicio'], 0, 5) . ' a ' . substr($row['horaFin'], 0, 5),
            'fecha' => $row['fecha'],
            'hora_entrada' => $row['hora_entrada'],
            'hora_salida' => $row['hora_salida'],
            'horas_trabajadas' => $row['horas_trabajadas'] ? (float)$row['horas_trabajadas'] : null,
            'estado' => $row['estado'],
            'observaciones' => $row['observaciones'],
            'origen' => $row['origen']
        ];
    }
}

echo json_encode([
    'ok' => true,
    'total' => count($turnos),
    'turnos' => $turnos
]);
