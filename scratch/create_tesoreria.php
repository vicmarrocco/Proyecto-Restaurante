<?php
require_once(__DIR__ . '/../mostaza_web/conexion.php');

$conn->query("
    CREATE TABLE IF NOT EXISTS tesoreria (
        idtesoreria INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(100) NOT NULL DEFAULT 'Tesorería Central Mostaza',
        saldo_efectivo DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        saldo_mercadopago DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        saldo_banco DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        total_fondos DECIMAL(12,2) GENERATED ALWAYS AS (saldo_efectivo + saldo_mercadopago + saldo_banco) STORED,
        fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

echo "Table tesoreria created or already exists.\n";

$conn->query("
    CREATE TABLE IF NOT EXISTS tesoreria_movimiento (
        idmovimiento INT AUTO_INCREMENT PRIMARY KEY,
        idtesoreria INT NOT NULL DEFAULT 1,
        tipo ENUM('Ingreso', 'Egreso') NOT NULL,
        categoria VARCHAR(60) NOT NULL,
        medio_pago ENUM('Efectivo', 'MercadoPago', 'Banco') NOT NULL,
        monto DECIMAL(12,2) NOT NULL,
        saldo_anterior DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        saldo_posterior DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        idarqueo INT NULL,
        idpedido INT NULL,
        idempleado INT NULL,
        origen_destino VARCHAR(150) NULL,
        concepto VARCHAR(255) NOT NULL,
        comprobante VARCHAR(100) NULL,
        fecha_hora DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_fecha (fecha_hora),
        INDEX idx_tipo (tipo),
        INDEX idx_cat (categoria),
        INDEX idx_idarqueo (idarqueo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

echo "Table tesoreria_movimiento created or already exists.\n";

// Verificar si existe la fila inicial idtesoreria = 1
$chk = $conn->query("SELECT idtesoreria, saldo_efectivo, saldo_mercadopago, saldo_banco, total_fondos FROM tesoreria WHERE idtesoreria = 1");
if ($chk && $chk->num_rows === 0) {
    // Calcular saldos históricos reales de caja y pedidos aprobados de MP
    $qFisico = $conn->query("SELECT IFNULL(SUM(montoFisico), 74100.00) as f, IFNULL(SUM(montoVirtual), 209900.00) as v FROM caja");
    $rF = $qFisico ? $qFisico->fetch_assoc() : ['f' => 74100, 'v' => 209900];
    
    $saldoEfectivoInicial = floatval($rF['f'] ?: 74100.00);
    $saldoMpInicial = floatval($rF['v'] ?: 209900.00);
    $saldoBancoInicial = 50000.00;

    $stmtIns = $conn->prepare("INSERT INTO tesoreria (idtesoreria, nombre, saldo_efectivo, saldo_mercadopago, saldo_banco) VALUES (1, 'Tesorería Central Mostaza', ?, ?, ?)");
    $stmtIns->bind_param("ddd", $saldoEfectivoInicial, $saldoMpInicial, $saldoBancoInicial);
    $stmtIns->execute();

    $totalIni = $saldoEfectivoInicial + $saldoMpInicial + $saldoBancoInicial;

    // Registrar movimiento de apertura de tesorería
    $stmtM = $conn->prepare("
        INSERT INTO tesoreria_movimiento (idtesoreria, tipo, categoria, medio_pago, monto, saldo_anterior, saldo_posterior, origen_destino, concepto)
        VALUES (1, 'Ingreso', 'Apertura de Tesorería', 'Efectivo', ?, 0.00, ?, 'Consolidación de Cajas', 'Apertura y consolidación inicial de fondos de Tesorería Central')
    ");
    $stmtM->bind_param("dd", $totalIni, $totalIni);
    $stmtM->execute();

    echo "Initialized tesoreria record 1 with total: $totalIni\n";
} else {
    $rowT = $chk->fetch_assoc();
    echo "Tesoreria already has data: Efectivo={$rowT['saldo_efectivo']}, MP={$rowT['saldo_mercadopago']}, Banco={$rowT['saldo_banco']}, Total={$rowT['total_fondos']}\n";
}
