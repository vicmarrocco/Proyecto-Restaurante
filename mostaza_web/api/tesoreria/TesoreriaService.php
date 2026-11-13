<?php
// ============================================================
// api/tesoreria/TesoreriaService.php
// Servicio centralizador de fondos del restaurante Mostaza
// Gestiona la Bóveda en Efectivo, Cuenta Mercado Pago y Banco
// ============================================================

class TesoreriaService {

    /**
     * Registra un movimiento en tesorería y actualiza el saldo correspondiente de forma atómica.
     */
    public static function registrarMovimiento(
        mysqli $conn,
        string $tipo,          // 'Ingreso' | 'Egreso'
        string $categoria,     // ej: 'Cierre de Caja', 'Retiro de Caja / Sangría', 'Rendición Mesero', 'Venta Web MP', 'Reembolso MP', 'Pago a Proveedor'
        string $medioPago,     // 'Efectivo' | 'MercadoPago' | 'Banco'
        float $monto,
        string $origenDestino, // ej: 'Caja 1 - Federico Álvarez', 'Proveedor Arcor', 'Cliente Juan Pérez'
        string $concepto,      // Descripción del movimiento
        array $opts = []       // ['idarqueo' => ..., 'idpedido' => ..., 'idempleado' => ..., 'comprobante' => ...]
    ): array {
        $monto = round(abs($monto), 2);
        if ($monto <= 0) {
            return ['ok' => false, 'error' => 'El monto debe ser mayor a cero.'];
        }

        $tipo = ucfirst(strtolower($tipo));
        if (!in_array($tipo, ['Ingreso', 'Egreso'])) {
            return ['ok' => false, 'error' => 'Tipo de movimiento inválido. Debe ser Ingreso o Egreso.'];
        }

        // Mapear medio de pago a columna de saldo
        $medioMap = [
            'efectivo'    => ['col' => 'saldo_efectivo',    'enum' => 'Efectivo'],
            'mercadopago' => ['col' => 'saldo_mercadopago', 'enum' => 'MercadoPago'],
            'mp'          => ['col' => 'saldo_mercadopago', 'enum' => 'MercadoPago'],
            'banco'       => ['col' => 'saldo_banco',       'enum' => 'Banco'],
            'tarjeta'     => ['col' => 'saldo_banco',       'enum' => 'Banco'],
            'transferencia' => ['col' => 'saldo_banco',     'enum' => 'Banco']
        ];
        $keyMedio = strtolower(trim($medioPago));
        $configMedio = $medioMap[$keyMedio] ?? $medioMap['efectivo'];
        $colSaldo = $configMedio['col'];
        $enumMedio = $configMedio['enum'];

        // Obtener saldo actual bloqueando la fila para consistencia
        $stmtSel = $conn->prepare("SELECT idtesoreria, saldo_efectivo, saldo_mercadopago, saldo_banco, total_fondos FROM tesoreria WHERE idtesoreria = 1 FOR UPDATE");
        $stmtSel->execute();
        $resT = $stmtSel->get_result();
        
        if ($resT->num_rows === 0) {
            // Si por alguna razón no existía la fila 1
            $conn->query("INSERT INTO tesoreria (idtesoreria, nombre, saldo_efectivo, saldo_mercadopago, saldo_banco) VALUES (1, 'Tesorería Central Mostaza', 0, 0, 0)");
            $saldoAnteriorMedio = 0.00;
        } else {
            $tes = $resT->fetch_assoc();
            $saldoAnteriorMedio = floatval($tes[$colSaldo]);
        }

        if ($tipo === 'Ingreso') {
            $saldoPosteriorMedio = $saldoAnteriorMedio + $monto;
        } else {
            // Egreso
            $saldoPosteriorMedio = max(0, $saldoAnteriorMedio - $monto);
        }

        // Actualizar el saldo específico en tesorería
        $stmtUpd = $conn->prepare("UPDATE tesoreria SET {$colSaldo} = ? WHERE idtesoreria = 1");
        $stmtUpd->bind_param("d", $saldoPosteriorMedio);
        $stmtUpd->execute();

        // Opciones adicionales
        $idarqueo = !empty($opts['idarqueo']) ? intval($opts['idarqueo']) : null;
        $idpedido = !empty($opts['idpedido']) ? intval($opts['idpedido']) : null;
        $idempleado = !empty($opts['idempleado']) ? intval($opts['idempleado']) : null;
        $comprobante = !empty($opts['comprobante']) ? trim($opts['comprobante']) : null;

        // Registrar en el libro de movimientos
        $stmtMov = $conn->prepare("
            INSERT INTO tesoreria_movimiento (
                idtesoreria, tipo, categoria, medio_pago, monto, 
                saldo_anterior, saldo_posterior, idarqueo, idpedido, 
                idempleado, origen_destino, concepto, comprobante
            ) VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmtMov->bind_param(
            "sssdddiissss",
            $tipo,
            $categoria,
            $enumMedio,
            $monto,
            $saldoAnteriorMedio,
            $saldoPosteriorMedio,
            $idarqueo,
            $idpedido,
            $idempleado,
            $origenDestino,
            $concepto,
            $comprobante
        );
        $stmtMov->execute();
        $idmovimiento = $conn->insert_id;

        return [
            'ok' => true,
            'idmovimiento' => $idmovimiento,
            'tipo' => $tipo,
            'categoria' => $categoria,
            'medio_pago' => $enumMedio,
            'monto' => $monto,
            'saldo_anterior' => $saldoAnteriorMedio,
            'saldo_posterior' => $saldoPosteriorMedio
        ];
    }

    /**
     * Registra un retiro de caja / sangría desde una terminal hacia la caja fuerte de Tesorería.
     */
    public static function ingresarRetiroCaja(mysqli $conn, int $idarqueo, int $numeroCaja, float $monto, string $cajero, string $motivo, ?int $idempleado = null): array {
        return self::registrarMovimiento(
            $conn,
            'Ingreso',
            'Retiro de Caja / Sangría',
            'Efectivo',
            $monto,
            "Caja $numeroCaja · $cajero",
            "Sangría de seguridad: $motivo (Arqueo #$idarqueo)",
            ['idarqueo' => $idarqueo, 'idempleado' => $idempleado]
        );
    }

    /**
     * Registra el ingreso de efectivo por Cierre Z definitivo de una caja.
     */
    public static function ingresarCierreCaja(mysqli $conn, int $idarqueo, int $numeroCaja, float $montoFisico, string $cajero, ?int $idempleado = null): array {
        return self::registrarMovimiento(
            $conn,
            'Ingreso',
            'Cierre de Caja',
            'Efectivo',
            $montoFisico,
            "Caja $numeroCaja · $cajero",
            "Cierre Z definitivo de turno (Arqueo #$idarqueo)",
            ['idarqueo' => $idarqueo, 'idempleado' => $idempleado]
        );
    }

    /**
     * Registra una venta aprobada vía Mercado Pago en los fondos digitales de Tesorería.
     */
    public static function ingresarVentaDigitalMP(mysqli $conn, int $idpedido, float $monto, string $clienteNombre, ?string $paymentId = null): array {
        return self::registrarMovimiento(
            $conn,
            'Ingreso',
            'Venta Web MP',
            'MercadoPago',
            $monto,
            "Cliente: $clienteNombre",
            "Cobro digital acreditado Pedido Web #$idpedido" . ($paymentId ? " (Op. #$paymentId)" : ""),
            ['idpedido' => $idpedido, 'comprobante' => $paymentId]
        );
    }

    /**
     * Registra un reembolso debitado de los fondos de Mercado Pago en Tesorería.
     */
    public static function egresarReembolsoMP(mysqli $conn, int $idpedido, float $monto, string $clienteNombre, ?string $refundId = null): array {
        return self::registrarMovimiento(
            $conn,
            'Egreso',
            'Reembolso MP',
            'MercadoPago',
            $monto,
            "Cliente: $clienteNombre",
            "Reembolso emitido por cancelación de Pedido Web #$idpedido" . ($refundId ? " (Ref: $refundId)" : ""),
            ['idpedido' => $idpedido, 'comprobante' => $refundId]
        );
    }

    /**
     * Registra la rendición de efectivo que entrega un mesero o delivery al terminar su jornada.
     */
    public static function ingresarRendicionEmpleado(mysqli $conn, int $idempleado, string $nombre, string $rol, float $monto, string $concepto): array {
        $cat = (stripos($rol, 'delivery') !== false || stripos($rol, 'repartidor') !== false) 
            ? 'Rendición Delivery' 
            : 'Rendición Mesero';

        return self::registrarMovimiento(
            $conn,
            'Ingreso',
            $cat,
            'Efectivo',
            $monto,
            "$rol: $nombre",
            $concepto,
            ['idempleado' => $idempleado]
        );
    }

    /**
     * Devuelve el estado integral y balances consolidados de Tesorería.
     */
    public static function obtenerEstado(mysqli $conn, int $limiteMovimientos = 20): array {
        $resT = $conn->query("SELECT * FROM tesoreria WHERE idtesoreria = 1 LIMIT 1");
        $tes = $resT ? $resT->fetch_assoc() : [
            'saldo_efectivo' => 0,
            'saldo_mercadopago' => 0,
            'saldo_banco' => 0,
            'total_fondos' => 0,
            'fecha_actualizacion' => date('Y-m-d H:i:s')
        ];

        // Obtener últimos movimientos
        $stmtM = $conn->prepare("
            SELECT m.*, e.nombre as empleado_nombre, e.apellido as empleado_apellido
            FROM tesoreria_movimiento m
            LEFT JOIN empleado e ON m.idempleado = e.idempleado
            ORDER BY m.idmovimiento DESC
            LIMIT ?
        ");
        $stmtM->bind_param("i", $limiteMovimientos);
        $stmtM->execute();
        $resM = $stmtM->get_result();
        $movimientos = [];

        while ($r = $resM->fetch_assoc()) {
            $movimientos[] = [
                'idmovimiento' => intval($r['idmovimiento']),
                'tipo' => $r['tipo'],
                'categoria' => $r['categoria'],
                'medio_pago' => $r['medio_pago'],
                'monto' => floatval($r['monto']),
                'monto_formateado' => '$' . number_format($r['monto'], 2, ',', '.'),
                'saldo_anterior' => floatval($r['saldo_anterior']),
                'saldo_posterior' => floatval($r['saldo_posterior']),
                'origen_destino' => $r['origen_destino'],
                'concepto' => $r['concepto'],
                'comprobante' => $r['comprobante'],
                'fecha_hora' => $r['fecha_hora'],
                'fecha_formateada' => date('d/m/Y H:i', strtotime($r['fecha_hora']))
            ];
        }

        // Resumen del día
        $hoy = date('Y-m-d');
        $qDia = $conn->query("
            SELECT 
                SUM(CASE WHEN tipo = 'Ingreso' THEN monto ELSE 0 END) as ingresos_hoy,
                SUM(CASE WHEN tipo = 'Egreso' THEN monto ELSE 0 END) as egresos_hoy
            FROM tesoreria_movimiento
            WHERE DATE(fecha_hora) = '$hoy'
        ");
        $rDia = $qDia ? $qDia->fetch_assoc() : ['ingresos_hoy' => 0, 'egresos_hoy' => 0];

        return [
            'ok' => true,
            'tesoreria' => [
                'saldo_efectivo' => floatval($tes['saldo_efectivo']),
                'saldo_efectivo_formateado' => '$' . number_format($tes['saldo_efectivo'], 2, ',', '.'),
                'saldo_mercadopago' => floatval($tes['saldo_mercadopago']),
                'saldo_mercadopago_formateado' => '$' . number_format($tes['saldo_mercadopago'], 2, ',', '.'),
                'saldo_banco' => floatval($tes['saldo_banco']),
                'saldo_banco_formateado' => '$' . number_format($tes['saldo_banco'], 2, ',', '.'),
                'total_fondos' => floatval($tes['total_fondos']),
                'total_fondos_formateado' => '$' . number_format($tes['total_fondos'], 2, ',', '.'),
                'fecha_actualizacion' => $tes['fecha_actualizacion']
            ],
            'resumen_hoy' => [
                'ingresos_hoy' => floatval($rDia['ingresos_hoy'] ?? 0),
                'ingresos_hoy_formateado' => '$' . number_format($rDia['ingresos_hoy'] ?? 0, 2, ',', '.'),
                'egresos_hoy' => floatval($rDia['egresos_hoy'] ?? 0),
                'egresos_hoy_formateado' => '$' . number_format($rDia['egresos_hoy'] ?? 0, 2, ',', '.')
            ],
            'movimientos' => $movimientos
        ];
    }
}
