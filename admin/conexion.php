<?php
// ============================================================
// admin/conexion.php
// Conexión a la Base de Datos y Funciones Maestras para Panel Admin
// ============================================================

$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "rest";

$conn = @new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Error de conexión con la base de datos: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// Función fiscal en caso de ejecutarse de forma independiente
if (!function_exists('generarFacturaFiscal')) {
    function generarFacturaFiscal($conn, $idPedido, $tipoComprobante = 'Factura B', $idCajero = null, $datosExtra = []) {
        $stmtP = $conn->prepare("SELECT p.*, u.nombre, u.apellido, u.email, u.telefono FROM pedido_web p LEFT JOIN usuario_web u ON p.idusuario = u.idusuario WHERE p.idpedido = ?");
        $stmtP->bind_param("i", $idPedido);
        $stmtP->execute();
        $pedido = $stmtP->get_result()->fetch_assoc();

        if (!$pedido) {
            throw new Exception("Pedido #$idPedido no encontrado.");
        }

        $idarqueo = $pedido['idarqueo'] ?? null;
        if (!$idarqueo) {
            $resCaja = $conn->query("SELECT idarqueo FROM caja_arqueo WHERE estado = 'Abierta' ORDER BY idarqueo DESC LIMIT 1");
            if ($resCaja && $cRow = $resCaja->fetch_assoc()) {
                $idarqueo = $cRow['idarqueo'];
                $conn->query("UPDATE pedido_web SET idarqueo = $idarqueo WHERE idpedido = $idPedido");
            }
        }

        $idEmpleado = $idCajero ?? ($pedido['idempleado'] ?? 1);
        $nombreCliente = !empty($pedido['nombre']) ? ($pedido['nombre'] . ' ' . $pedido['apellido']) : ($datosExtra['nombre_cliente'] ?? 'Consumidor Final');
        $cuitDni = $datosExtra['cuit_dni_cliente'] ?? '0';

        $subtotal = floatval($pedido['subtotal']);
        $descuento = floatval($pedido['descuento']);
        $total = floatval($pedido['total']);
        $netoGravado = round($total / 1.21, 2);
        $iva21 = round($total - $netoGravado, 2);
        $pagaCon = floatval($pedido['paga_con'] ?? $total);
        $vuelto = floatval($pedido['vuelto'] ?? 0);
        $metodoPago = $pedido['metodo_pago'] ?? 'efectivo';

        $stmtCount = $conn->query("SELECT IFNULL(MAX(idfactura), 0) + 1 AS prox FROM factura");
        $proxId = ($stmtCount && $r = $stmtCount->fetch_assoc()) ? intval($r['prox']) : $idPedido;
        $puntoVenta = "0001";
        $numCorrelativo = str_pad($proxId, 8, '0', STR_PAD_LEFT);
        $numeroFactura = "FAC-B-$puntoVenta-$numCorrelativo";

        $cae = "74" . str_pad(mt_rand(1000000000, 9999999999), 11, '0', STR_PAD_LEFT) . "3";
        $caeVto = date('Y-m-d', strtotime('+10 days'));

        $stmtF = $conn->prepare(
            "INSERT INTO factura (
                numero_factura, tipo_comprobante, idpedido, idarqueo, idempleado, idusuario,
                nombre_cliente, cuit_dni_cliente, metodo_pago, subtotal, descuento,
                neto_gravado, iva_21, total, paga_con, vuelto, cae, cae_vto, estado
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Emitida')"
        );

        $stmtF->bind_param(
            "ssiiiisssddddddsss",
            $numeroFactura,
            $tipoComprobante,
            $idPedido,
            $idarqueo,
            $idEmpleado,
            $pedido['idusuario'],
            $nombreCliente,
            $cuitDni,
            $metodoPago,
            $subtotal,
            $descuento,
            $netoGravado,
            $iva21,
            $total,
            $pagaCon,
            $vuelto,
            $cae,
            $caeVto
        );

        $stmtF->execute();
        $idFactura = $conn->insert_id;

        // Copiar items
        $stmtDetPed = $conn->prepare("SELECT * FROM detalle_pedido_web WHERE idpedido = ?");
        $stmtDetPed->bind_param("i", $idPedido);
        $stmtDetPed->execute();
        $resDet = $stmtDetPed->get_result();

        $stmtDetFac = $conn->prepare(
            "INSERT INTO detalle_factura (idfactura, idproducto, descripcion, modificadores, precio_unitario, cantidad, subtotal)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );

        while ($it = $resDet->fetch_assoc()) {
            $stmtDetFac->bind_param("iissdid", $idFactura, $it['idproducto'], $it['descripcion'], $it['modificadores'], $it['precio_unitario'], $it['cantidad'], $it['subtotal']);
            $stmtDetFac->execute();
        }

        if ($idarqueo) {
            $conn->query("UPDATE caja_arqueo SET total_transacciones = total_transacciones + 1 WHERE idarqueo = $idarqueo");
        }

        return [
            'idfactura' => $idFactura,
            'numero_factura' => $numeroFactura,
            'tipo_comprobante' => $tipoComprobante,
            'total' => $total,
            'cae' => $cae,
            'cae_vto' => $caeVto
        ];
    }
}
