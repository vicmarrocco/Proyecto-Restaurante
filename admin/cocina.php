<?php
// ============================================================
// admin/cocina.php
// APP DE COCINA · Kitchen Display System (KDS) Web
// ============================================================
session_start();
require_once(__DIR__ . '/conexion.php');

// Función de respaldo en caso de que no esté declarada globalmente
if (!function_exists('registrarAuditoriaPedido')) {
    function registrarAuditoriaPedido($conn, $idpedido, $nuevoEstado, $accion, $rol, $nombre, $idEmpleado, $motivo = '') {
        $idpedido = intval($idpedido);
        if ($idpedido <= 0) return false;

        $stmtP = $conn->prepare("SELECT estado, fecha, hora_recibido, hora_cocina_inicio, hora_cocina_listo FROM pedido_web WHERE idpedido = ?");
        $stmtP->bind_param("i", $idpedido);
        $stmtP->execute();
        $p = $stmtP->get_result()->fetch_assoc();
        $estadoAnterior = $p['estado'] ?? 'Recibido';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        // 1. Insertar evento inmutable en pedido_auditoria
        $stmtAud = $conn->prepare("
            INSERT INTO pedido_auditoria (idpedido, estado_anterior, estado_nuevo, accion, rol_responsable, idempleado, nombre_responsable, detalles, ip_origen)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        if ($stmtAud) {
            $stmtAud->bind_param("issssisss", $idpedido, $estadoAnterior, $nuevoEstado, $accion, $rol, $idEmpleado, $nombre, $motivo, $ip);
            $stmtAud->execute();
        }

        // 2. Actualizar estado y tiempos en pedido_web
        $updates = ["`estado` = '" . $conn->real_escape_string($nuevoEstado) . "'"];
        if (in_array($nuevoEstado, ['En Cocina', 'En Preparacion', 'En Preparación'])) {
            $updates[] = "`hora_cocina_inicio` = IFNULL(`hora_cocina_inicio`, NOW())";
            $updates[] = "`cocinero_nombre` = '" . $conn->real_escape_string($nombre) . "'";
            if ($idEmpleado) $updates[] = "`idempleado_cocinero` = " . intval($idEmpleado);
        } elseif (in_array($nuevoEstado, ['Listo', 'Listo para Despacho'])) {
            $updates[] = "`hora_cocina_listo` = NOW()";
            $updates[] = "`hora_completado_cocina` = NOW()";
            if (!empty($nombre)) {
                $updates[] = "`cocinero_nombre` = '" . $conn->real_escape_string($nombre) . "'";
                if ($idEmpleado) $updates[] = "`idempleado_cocinero` = " . intval($idEmpleado);
            }
            $updates[] = "`duracion_cocina_minutos` = GREATEST(1, TIMESTAMPDIFF(MINUTE, IFNULL(hora_cocina_inicio, IFNULL(hora_recibido, fecha)), NOW()))";
        }
        $conn->query("UPDATE pedido_web SET " . implode(', ', $updates) . " WHERE idpedido = $idpedido");
        return true;
    }
}

// Procesar inicio de turno del cocinero
if (isset($_POST['login_cocina'])) {
    $nombreCocinero = trim($_POST['nombre_cocinero'] ?? 'Cocinero de Turno');
    $_SESSION['cocinero_nombre'] = $nombreCocinero;
    
    // Registrar horario de ingreso automáticamente
    $stmtReg = $conn->prepare("INSERT INTO registro_turno_empleado (fecha, hora_entrada, estado, origen) VALUES (CURDATE(), NOW(), 'Activo', 'KDS Web')");
    if ($stmtReg) {
        $stmtReg->execute();
        $_SESSION['cocinero_idregistro'] = $stmtReg->insert_id;
    }
    
    header("Location: cocina.php");
    exit();
}

// Procesar cierre de turno
if (isset($_GET['cerrar_turno'])) {
    if (!empty($_SESSION['cocinero_idregistro'])) {
        $conn->query("UPDATE registro_turno_empleado SET estado = 'Finalizado', hora_salida = NOW() WHERE idregistro = " . intval($_SESSION['cocinero_idregistro']));
    }
    unset($_SESSION['cocinero_nombre'], $_SESSION['cocinero_idregistro']);
    header("Location: cocina.php");
    exit();
}

// Procesar cambio de estado de comanda
if (isset($_POST['cambiar_estado'])) {
    $idpedido = intval($_POST['idpedido']);
    $nuevoEstado = $_POST['nuevo_estado'];
    $nombreCocinero = $_SESSION['cocinero_nombre'] ?? 'Cocinero de Turno';
    $idEmpleado = $_SESSION['cocinero_id'] ?? null;
    
    if (in_array($nuevoEstado, ['En Preparacion', 'Listo para Despacho', 'Listo'])) {
        registrarAuditoriaPedido($conn, $idpedido, $nuevoEstado, "Comanda $nuevoEstado (KDS Web)", 'Cocinero', $nombreCocinero, $idEmpleado, "Actualización desde monitor de cocina");
    }
    header("Location: cocina.php?tab=pedidos");
    exit();
}

// Procesar cancelación de comanda por cocina
if (isset($_POST['cancelar_comanda_cocina'])) {
    $idpedido = intval($_POST['idpedido']);
    $motivo = trim($_POST['motivo_cancelacion'] ?? 'Inconveniente en cocina / Insumo faltante');
    $nombreCocinero = $_SESSION['cocinero_nombre'] ?? 'Cocinero de Turno';
    $idEmpleado = $_SESSION['cocinero_id'] ?? null;
    
    registrarAuditoriaPedido($conn, $idpedido, 'Cancelado', 'Cancelación por Cocina', 'Cocinero', $nombreCocinero, $idEmpleado, $motivo);
    
    header("Location: cocina.php?tab=pedidos&msg=cancelado");
    exit();
}

$tabActiva = $_GET['tab'] ?? 'pedidos';
$modoKds = isset($_GET['fullscreen']) || isset($_GET['kds_puro']);
$cocineroLogueado = !empty($_SESSION['cocinero_nombre']);
$nombreCocinero = $_SESSION['cocinero_nombre'] ?? 'Administración / Cocina Central';

// Consultar comandas activas
$comandas = $conn->query(
    "SELECT p.*, u.nombre, u.apellido 
     FROM pedido_web p 
     LEFT JOIN usuario_web u ON p.idusuario = u.idusuario 
     WHERE p.estado IN ('Recibido', 'En Cocina', 'En Preparacion', 'En Preparación', 'Listo para Despacho') 
     ORDER BY p.idpedido ASC"
);

// Consultar stock de ingredientes
$stockInsumos = $conn->query("SELECT * FROM stock_insumo ORDER BY categoria, nombre ASC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>KDS Cocina Web · Mostaza Central</title>
<link rel="stylesheet" href="admin.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
body.kds-body {
    background: #F8FAFC;
    color: #0F172A;
    font-family: 'Montserrat', sans-serif;
    margin: 0;
    padding: 0;
}
.kds-topbar {
    background: #FFFFFF;
    border-bottom: 1px solid #E2E8F0;
    padding: 14px 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: sticky;
    top: 0;
    z-index: 100;
    box-shadow: 0 2px 8px rgba(0,0,0,0.03);
}
.kds-nav-tabs {
    display: flex;
    gap: 8px;
}
.kds-tab-btn {
    background: #FFFFFF;
    color: #475569;
    border: 1px solid #CBD5E1;
    padding: 8px 18px;
    border-radius: 10px;
    font-weight: 700;
    font-size: 0.88rem;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: 0.2s;
}
.kds-tab-btn.active, .kds-tab-btn:hover {
    background: var(--admin-rojo, #A30002);
    color: #fff;
    border-color: var(--admin-rojo, #A30002);
}
.kds-modal {
    display: none;
    position: fixed;
    top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(15,23,42,0.6);
    z-index: 9999;
    align-items: center;
    justify-content: center;
}
.kds-modal.open { display: flex; }
.kds-modal-content {
    background: #FFFFFF;
    color: #0F172A;
    border-radius: 16px;
    max-width: 550px;
    width: 90%;
    max-height: 85vh;
    overflow-y: auto;
    padding: 26px;
    border: 1px solid #CBD5E1;
    box-shadow: 0 20px 40px rgba(0,0,0,0.15);
}
.stock-table {
    width: 100%;
    border-collapse: collapse;
}
.stock-table th, .stock-table td {
    padding: 12px 14px;
    text-align: left;
    border-bottom: 1px solid #E2E8F0;
}
.stock-table th {
    background: #F8FAFC;
    color: #0F172A;
    font-weight: 800;
    font-size: 0.82rem;
    text-transform: uppercase;
}
</style>
</head>
<body class="admin-body <?= $modoKds ? 'kds-fullscreen' : '' ?>">

<?php if (!$modoKds): ?>
<!-- Sidebar Administrativo -->
<aside class="admin-sidebar">
    <div class="sidebar-brand">
        <img src="img/logo.png" alt="Mostaza">
        <div>
            <h2>MOSTAZA POS</h2>
            <span>EPET Nº 7 · Sistema Web</span>
        </div>
    </div>

    <ul class="sidebar-nav">
        <li><a href="index.php"><i class="fa-solid fa-chart-pie"></i> Dashboard</a></li>
        <li><a href="pos.php"><i class="fa-solid fa-cash-register"></i> Punto de Venta (POS)</a></li>
        <li><a href="mesero.php"><i class="fa-solid fa-utensils"></i> Salón (Mesero)</a></li>
        <li><a href="cocina.php" class="active"><i class="fa-solid fa-fire-burner"></i> Cocina (KDS)</a></li>
        <li><a href="repartidor.php"><i class="fa-solid fa-motorcycle"></i> Repartidor</a></li>
        <li><a href="caja.php"><i class="fa-solid fa-vault"></i> Control de Caja</a></li>
        <li><a href="inventario.php"><i class="fa-solid fa-boxes-stacked"></i> Inventario & Stock</a></li>
        <li><a href="reportes.php"><i class="fa-solid fa-file-invoice-dollar"></i> Reportes</a></li>
        <li style="margin-top:20px;"><a href="../mostaza_web/" target="_blank"><i class="fa-solid fa-store"></i> Ir a la Tienda Web</a></li>
    </ul>

    <div class="sidebar-footer">
        Sistema Mostaza v2.5<br>EPET Nº 7 "Vicente Arcadio Salemi"
    </div>
</aside>
<?php endif; ?>

<main class="admin-main" style="<?= $modoKds ? 'margin-left:0;width:100%;padding:0;' : '' ?>">

<!-- TOPBAR COCINA -->
<div class="kds-topbar" style="<?= $modoKds ? 'background:#1E293B;border-bottom-color:#334155;color:#fff;' : '' ?>">
    <div style="display:flex;align-items:center;gap:14px;">
        <img src="img/logo.png" alt="Mostaza" style="width:36px;height:36px;object-fit:contain;">
        <div>
            <h1 style="font-size:1.2rem;font-weight:900;color:<?= $modoKds ? '#FBB728' : 'var(--admin-rojo, #A30002)' ?>;margin:0;letter-spacing:0.5px;">
                MOSTAZA · APP DE COCINA KDS
            </h1>
            <span style="font-size:0.8rem;color:<?= $modoKds ? '#94A3B8' : '#64748B' ?>;">
                Sucursal Formosa Centro · Supervisor / Cocinero: <strong style="color:<?= $modoKds ? '#F8FAFC' : '#0F172A' ?>;"><?= htmlspecialchars($nombreCocinero) ?></strong>
            </span>
        </div>
    </div>

    <!-- Navegación por Pestañas -->
    <div class="kds-nav-tabs">
        <a href="cocina.php?tab=pedidos<?= $modoKds ? '&fullscreen=1' : '' ?>" class="kds-tab-btn <?= $tabActiva === 'pedidos' ? 'active' : '' ?>">
            <i class="fa-solid fa-fire-burner"></i> Comandas Activas (<?= $comandas ? $comandas->num_rows : 0 ?>)
        </a>
        <a href="cocina.php?tab=stock<?= $modoKds ? '&fullscreen=1' : '' ?>" class="kds-tab-btn <?= $tabActiva === 'stock' ? 'active' : '' ?>">
            <i class="fa-solid fa-boxes-stacked"></i> Stock Insumos
        </a>
    </div>

    <!-- Botones de Utilidad -->
    <div style="display:flex;gap:8px;align-items:center;">
        <?php if ($modoKds): ?>
            <a href="cocina.php?tab=<?= $tabActiva ?>" class="kds-tab-btn" title="Volver al panel admin normal">
                <i class="fa-solid fa-compress"></i> Salir Pantalla Completa
            </a>
        <?php else: ?>
            <a href="cocina.php?tab=<?= $tabActiva ?>&fullscreen=1" class="kds-tab-btn" title="Modo Pantalla Completa para Monitor de Cocina TV">
                <i class="fa-solid fa-expand"></i> Pantalla Completa (TV)
            </a>
        <?php endif; ?>

        <button onclick="document.getElementById('modalPoliticas').classList.add('open')" class="kds-tab-btn">
            <i class="fa-solid fa-shield-halved"></i> BPM
        </button>

        <?php if (!$cocineroLogueado): ?>
            <button onclick="document.getElementById('modalLogin').classList.add('open')" class="kds-tab-btn" style="background:var(--admin-rojo, #A30002);color:#fff;border-color:var(--admin-rojo, #A30002);">
                <i class="fa-solid fa-id-badge"></i> Fichar Cocinero
            </button>
        <?php else: ?>
            <a href="cocina.php?cerrar_turno=1" onclick="return confirm('¿Finalizar turno de cocina?')" class="kds-tab-btn" style="background:#DC2626;color:#fff;border-color:#DC2626;">
                <i class="fa-solid fa-door-open"></i> Salir
            </a>
        <?php endif; ?>
    </div>
</div>

<div style="padding: <?= $modoKds ? '20px' : '24px' ?>;">

    <!-- Banner Sincronización con App Móvil Expo -->
    <div style="background:#FFFBEB;border:1.5px solid #FDE68A;border-radius:14px;padding:14px 20px;margin-bottom:22px;display:flex;justify-content:space-between;align-items:center;gap:16px;">
        <div style="display:flex;align-items:center;gap:14px;">
            <span style="font-size:2rem;">📱</span>
            <div>
                <strong style="color:#92400E;font-size:0.95rem;">Aplicación Móvil KDS en Expo Go (Servidor Central: 10.0.5.77)</strong>
                <p style="margin:2px 0 0;font-size:0.82rem;color:#78350F;">
                    Los cocineros operan principalmente desde sus tablets o celulares montados en cocina con la <strong>App Cocina (Expo Go)</strong>. Todo cambio impacta en tiempo real en este panel web administrativo y en la auditoría de pedidos.
                </p>
            </div>
        </div>
        <div style="display:flex;gap:8px;align-items:center;">
            <span style="background:#10B981;color:#fff;font-size:0.75rem;font-weight:800;padding:4px 10px;border-radius:50px;display:inline-flex;align-items:center;gap:5px;">
                <i class="fa-solid fa-circle" style="font-size:0.5rem;"></i> Sincronizado en Vivo
            </span>
        </div>
    <!-- TAB 1: COMANDAS ACTIVAS -->
    <?php if ($tabActiva === 'pedidos'): ?>
        
        <?php if (!$comandas || $comandas->num_rows === 0): ?>
            <div style="text-align:center;padding:80px 20px;background:#FFFFFF;border-radius:16px;border:1px solid #E2E8F0;max-width:700px;margin:30px auto;box-shadow:0 4px 15px rgba(0,0,0,0.02);">
                <i class="fa-solid fa-clipboard-check" style="font-size:4rem;margin-bottom:16px;color:#10B981;"></i>
                <h2 style="color:#0F172A;font-size:1.5rem;font-weight:900;">¡Sin comandas pendientes en cocina!</h2>
                <p style="color:#64748B;font-size:0.9rem;margin-top:6px;">La línea caliente está al día. Los nuevos pedidos de salón, mostrador y delivery aparecerán automáticamente aquí.</p>
            </div>
        <?php else: ?>
            <div class="kds-grid">
                <?php while ($c = $comandas->fetch_assoc()): ?>
                <?php
                $minutos = intval((time() - strtotime($c['fecha'])) / 60);
                $ticketClass = ($minutos >= 15) ? 'urgente' : (($c['estado'] === 'En Preparacion' || $c['estado'] === 'En Cocina') ? 'preparando' : '');
                
                $stmtDet = $conn->prepare("SELECT * FROM detalle_pedido_web WHERE idpedido = ?");
                $stmtDet->bind_param("i", $c['idpedido']);
                $stmtDet->execute();
                $items = $stmtDet->get_result();
                ?>
                <div class="kds-ticket <?= $ticketClass ?>">
                    <div>
                        <div class="kds-ticket-header">
                            <div>
                                <strong style="font-size:1.15rem;">Comanda #<?= $c['idpedido'] ?></strong>
                                <div style="font-size:0.8rem;color:var(--admin-rojo, #A30002);font-weight:800;margin-top:2px;">
                                    <?= strtoupper($c['tipo_entrega']) ?>
                                    <?php if ($c['tipo_entrega'] === 'salon' && !empty($c['numero_mesa'])): ?>
                                        <span style="background:#A30002;color:#fff;font-size:0.75rem;padding:2px 8px;border-radius:4px;font-weight:800;margin-left:4px;">MESA <?= $c['numero_mesa'] ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <span class="kds-timer" style="<?= $minutos >= 15 ? 'background:#FEE2E2;color:#991B1B;' : '' ?>">
                                <i class="fa-regular fa-clock"></i> <?= $minutos ?> min
                            </span>
                        </div>

                        <div style="font-size:0.82rem;color:#64748B;margin-bottom:14px;">
                            Cliente: <strong style="color:#0F172A;"><?= htmlspecialchars(!empty($c['nombre']) ? $c['nombre'] . ' ' . $c['apellido'] : 'Consumidor Final') ?></strong>
                            <?php if ($c['tipo_entrega'] === 'delivery'): ?>
                                <br><span style="color:#2563EB;font-weight:700;">🛵 Delivery · <?= htmlspecialchars($c['direccion_envio'] ?? 'Formosa') ?></span>
                            <?php endif; ?>
                        </div>

                        <div style="border-top:1px dashed #CBD5E1;padding-top:10px;">
                            <?php while ($it = $items->fetch_assoc()): ?>
                            <div class="kds-item-row">
                                <span style="font-weight:900;color:var(--admin-rojo, #A30002);margin-right:8px;"><?= $it['cantidad'] ?>x</span>
                                <div style="flex:1;">
                                    <strong style="color:#0F172A;"><?= htmlspecialchars($it['descripcion']) ?></strong>
                                    <?php if (!empty($it['modificadores'])): ?>
                                        <div class="kds-item-mods"><i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($it['modificadores']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    </div>

                    <!-- Botones de Acción de Cocina -->
                    <div style="display:flex;gap:8px;margin-top:16px;">
                        <?php if ($c['estado'] !== 'En Preparacion'): ?>
                            <form method="post" action="cocina.php" style="flex:1;">
                                <input type="hidden" name="idpedido" value="<?= $c['idpedido'] ?>">
                                <input type="hidden" name="nuevo_estado" value="En Preparacion">
                                <button type="submit" name="cambiar_estado" class="btn-kds btn-kds-cook" style="margin-top:0;">
                                    <i class="fa-solid fa-kitchen-set"></i> Preparando
                                </button>
                            </form>
                        <?php endif; ?>

                        <form method="post" action="cocina.php" style="flex:1.2;">
                            <input type="hidden" name="idpedido" value="<?= $c['idpedido'] ?>">
                            <input type="hidden" name="nuevo_estado" value="Listo para Despacho">
                            <button type="submit" name="cambiar_estado" class="btn-kds btn-kds-ready" style="margin-top:0;">
                                <i class="fa-solid fa-circle-check"></i> ¡Listo!
                            </button>
                        </form>

                        <button onclick="abrirModalCancelar(<?= $c['idpedido'] ?>)" class="btn-admin btn-admin-white" style="padding:10px 14px;" title="Cancelar Comanda">
                            <i class="fa-solid fa-xmark" style="color:#DC2626;"></i>
                        </button>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>

    <!-- TAB 2: STOCK DE INSUMOS -->
    <?php elseif ($tabActiva === 'stock'): ?>
        <div style="max-width:1100px;margin:0 auto;">
            <div class="admin-card">
                <div class="admin-card-header">
                    <div>
                        <h2>Stock de Ingredientes en Cocina</h2>
                        <span style="font-size:0.85rem;color:#64748B;">Control de inventario en tiempo real</span>
                    </div>
                </div>

                <div class="admin-table-wrapper">
                    <table class="stock-table">
                        <thead>
                            <tr>
                                <th>Insumo / Ingrediente</th>
                                <th>Categoría</th>
                                <th>Stock Actual</th>
                                <th>Stock Mínimo</th>
                                <th>Estado</th>
                                <th>Proveedor</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($ins = $stockInsumos->fetch_assoc()): 
                                $esBajo = floatval($ins['stock_actual']) <= floatval($ins['stock_minimo']);
                            ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($ins['nombre']) ?></strong></td>
                                <td><span style="color:#64748B;font-size:0.85rem;"><?= htmlspecialchars($ins['categoria']) ?></span></td>
                                <td><strong style="color:<?= $esBajo ? '#EF4444' : '#10B981' ?>;font-size:0.95rem;"><?= number_format($ins['stock_actual'], 1) ?> <?= htmlspecialchars($ins['unidad_medida']) ?></strong></td>
                                <td style="color:#64748B;"><?= number_format($ins['stock_minimo'], 1) ?> <?= htmlspecialchars($ins['unidad_medida']) ?></td>
                                <td>
                                    <?php if ($esBajo): ?>
                                        <span class="badge badge-danger"><i class="fa-solid fa-triangle-exclamation"></i> REPOSICIÓN URGENTE</span>
                                    <?php else: ?>
                                        <span class="badge badge-success"><i class="fa-solid fa-check"></i> NORMAL</span>
                                    <?php endif; ?>
                                </td>
                                <td style="color:#64748B;font-size:0.85rem;"><?= htmlspecialchars($ins['proveedor']) ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
</main>

<!-- Modal Políticas BPM -->
<div class="kds-modal" id="modalPoliticas">
    <div class="kds-modal-content">
        <div style="display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #E2E8F0;padding-bottom:12px;margin-bottom:16px;">
            <h3 style="color:var(--admin-rojo, #A30002);margin:0;font-size:1.2rem;font-weight:900;"><i class="fa-solid fa-shield-halved"></i> Políticas de Cocina y BPM Mostaza</h3>
            <button onclick="document.getElementById('modalPoliticas').classList.remove('open')" style="background:none;border:none;color:#94A3B8;font-size:1.2rem;cursor:pointer;">✕</button>
        </div>
        <div style="font-size:0.9rem;color:#334155;line-height:1.6;">
            <p><strong>1. Higiene Personal y Uniforme:</strong><br>Uso obligatorio de cofia, delantal limpio y lavado de manos frecuente.</p>
            <p style="margin-top:10px;"><strong>2. Control de Temperaturas:</strong><br>Plancha de medallones a 180°C (temperatura interna ≥ 75°C). Freidora a 175°C.</p>
            <p style="margin-top:10px;"><strong>3. Cadena de Frío:</strong><br>Mantener lácteos y carnes siempre refrigerados a menos de 4°C.</p>
            <p style="margin-top:10px;"><strong>4. Cancelación de Comandas:</strong><br>Solo proceder a cancelar cuando un insumo esté agotado, notificando la causa.</p>
        </div>
        <button onclick="document.getElementById('modalPoliticas').classList.remove('open')" class="btn-admin btn-admin-red" style="width:100%;margin-top:18px;justify-content:center;padding:12px;">
            Entendido y Aceptar
        </button>
    </div>
</div>

<!-- Modal Login Cocinero -->
<div class="kds-modal" id="modalLogin">
    <div class="kds-modal-content" style="max-width:440px;">
        <h3 style="color:var(--admin-rojo, #A30002);margin:0 0 12px;font-size:1.3rem;font-weight:900;">👨‍🍳 Iniciar Turno de Cocina</h3>
        <p style="font-size:0.85rem;color:#64748B;margin-bottom:16px;">Ingresá tu nombre para registrar tu ingreso en el sistema.</p>
        
        <form method="post" action="cocina.php">
            <div class="form-group">
                <label class="form-label">Nombre del Cocinero:</label>
                <input type="text" name="nombre_cocinero" required placeholder="Ej: Marcos Silva" class="form-control">
            </div>

            <button type="submit" name="login_cocina" class="btn-admin btn-admin-red" style="width:100%;justify-content:center;padding:12px;">
                Iniciar Turno y Fichar Entrada
            </button>
        </form>
    </div>
</div>

<!-- Modal Cancelar Comanda -->
<div class="kds-modal" id="modalCancelar">
    <div class="kds-modal-content" style="max-width:440px;">
        <h3 style="color:#EF4444;margin:0 0 12px;font-size:1.3rem;font-weight:900;">❌ Cancelar Comanda</h3>
        <p style="font-size:0.85rem;color:#64748B;margin-bottom:14px;">Ingresá el motivo de la cancelación para el registro.</p>

        <form method="post" action="cocina.php">
            <input type="hidden" name="idpedido" id="canc_idpedido">
            
            <div class="form-group">
                <label class="form-label">Motivo de Cancelación:</label>
                <select name="motivo_cancelacion" class="form-control">
                    <option value="Insumo agotado en cocina">Insumo agotado en cocina</option>
                    <option value="Error en la especificación del pedido">Error en la especificación del pedido</option>
                    <option value="Problema técnico en equipamiento">Problema técnico en equipamiento</option>
                    <option value="Cancelado a solicitud del cliente">Cancelado a solicitud del cliente</option>
                </select>
            </div>

            <div style="display:flex;gap:10px;">
                <button type="button" onclick="document.getElementById('modalCancelar').classList.remove('open')" class="btn-admin btn-admin-white" style="flex:1;justify-content:center;">
                    Volver
                </button>
                <button type="submit" name="cancelar_comanda_cocina" class="btn-admin btn-admin-red" style="flex:1;justify-content:center;">
                    Confirmar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirModalCancelar(idpedido) {
    document.getElementById('canc_idpedido').value = idpedido;
    document.getElementById('modalCancelar').classList.add('open');
}

// Auto-recarga cada 10 segundos en la pestaña de pedidos para tiempo real
<?php if ($tabActiva === 'pedidos'): ?>
setInterval(function() {
    if (!document.querySelector('.kds-modal.open')) {
        window.location.reload();
    }
}, 10000);
<?php endif; ?>
</script>

</body>
</html>