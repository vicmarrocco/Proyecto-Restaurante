<?php
// ============================================================
// admin/cocina.php
// APP DE COCINA · Kitchen Display System (KDS)
// Cumple con los requerimientos R26 a R37 del PDF.
// ============================================================
session_start();
require_once(__DIR__ . '/../conexion.php');

// Procesar inicio de turno del cocinero (R28, R29, R30)
if (isset($_POST['login_cocina'])) {
    $nombreCocinero = trim($_POST['nombre_cocinero'] ?? 'Cocinero de Turno');
    $_SESSION['cocinero_nombre'] = $nombreCocinero;
    
    // Registrar horario de ingreso automáticamente (R30)
    $stmtReg = $conn->prepare("INSERT INTO registro_turno_empleado (nombre_empleado, rol, fecha, hora_entrada, estado, origen) VALUES (?, 'Cocinero', CURDATE(), NOW(), 'Activo', 'App Cocina')");
    $stmtReg->bind_param("s", $nombreCocinero);
    $stmtReg->execute();
    $_SESSION['cocinero_idregistro'] = $stmtReg->insert_id;
    
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

// Procesar cambio de estado de comanda (R33)
if (isset($_POST['cambiar_estado'])) {
    $idpedido = intval($_POST['idpedido']);
    $nuevoEstado = $_POST['nuevo_estado'];
    
    if (in_array($nuevoEstado, ['En Preparacion', 'Listo para Despacho', 'Listo'])) {
        $stmtUpd = $conn->prepare("UPDATE pedido_web SET estado = ?, hora_completado_cocina = NOW() WHERE idpedido = ?");
        $stmtUpd->bind_param("si", $nuevoEstado, $idpedido);
        $stmtUpd->execute();
    }
    header("Location: cocina.php?tab=pedidos");
    exit();
}

// Procesar cancelación de comanda por cocina (R34)
if (isset($_POST['cancelar_comanda_cocina'])) {
    $idpedido = intval($_POST['idpedido']);
    $motivo = trim($_POST['motivo_cancelacion'] ?? 'Inconveniente en cocina / Insumo faltante');
    $canceladoPor = "Cocina (" . ($_SESSION['cocinero_nombre'] ?? 'Cocinero') . ")";
    
    $stmtCanc = $conn->prepare("UPDATE pedido_web SET estado = 'Cancelado', motivo_cancelacion = ?, cancelado_por = ? WHERE idpedido = ?");
    $stmtCanc->bind_param("ssi", $motivo, $canceladoPor, $idpedido);
    $stmtCanc->execute();
    
    header("Location: cocina.php?tab=pedidos&msg=cancelado");
    exit();
}

// Procesar solicitud de insumos al admin (R37)
if (isset($_POST['solicitar_insumo'])) {
    $insumoNombre = trim($_POST['insumo_nombre']);
    $cantidad = floatval($_POST['cantidad']);
    $unidad = trim($_POST['unidad']);
    $prioridad = $_POST['prioridad'] ?? 'Media';
    $motivo = trim($_POST['motivo'] ?? 'Stock crítico en turno');
    $solicitante = $_SESSION['cocinero_nombre'] ?? 'Cocinero de Turno';
    
    $stmtSol = $conn->prepare("INSERT INTO solicitud_ingrediente (solicitante_nombre, nombre_insumo, cantidad_solicitada, unidad_medida, prioridad, motivo) VALUES (?, ?, ?, ?, ?, ?)");
    $stmtSol->bind_param("ssdsss", $solicitante, $insumoNombre, $cantidad, $unidad, $prioridad, $motivo);
    $stmtSol->execute();
    
    header("Location: cocina.php?tab=stock&msg=solicitud_enviada");
    exit();
}

$tabActiva = $_GET['tab'] ?? 'pedidos';
$cocineroLogueado = !empty($_SESSION['cocinero_nombre']);
$nombreCocinero = $_SESSION['cocinero_nombre'] ?? 'Cocinero Invitado';

// Consultar comandas activas (R32)
$comandas = $conn->query(
    "SELECT p.*, u.nombre, u.apellido 
     FROM pedido_web p 
     LEFT JOIN usuario_web u ON p.idusuario = u.idusuario 
     WHERE p.estado IN ('Recibido', 'En Cocina', 'En Preparacion', 'Listo para Despacho') 
     ORDER BY p.idpedido ASC"
);

// Consultar recetas (R35)
$recetas = $conn->query("SELECT * FROM receta ORDER BY categoria, nombre_plato ASC");

// Consultar stock de ingredientes (R36)
$stockInsumos = $conn->query("SELECT * FROM stock_insumo ORDER BY categoria, nombre ASC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>App Cocina · Mostaza KDS</title>
<link rel="stylesheet" href="admin.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800;900&family=Playfair+Display:wght@700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
:root {
    --rojo: #A30002;
    --amarillo: #FBB728;
    --dark: #0F172A;
    --card-bg: #1E293B;
}
body.kds-body {
    background: #0B0F19;
    color: #F8FAFC;
    font-family: 'Montserrat', sans-serif;
    margin: 0;
    padding: 0;
}
.kds-topbar {
    background: #0F172A;
    border-bottom: 2px solid #1E293B;
    padding: 14px 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: sticky;
    top: 0;
    z-index: 100;
}
.kds-nav-tabs {
    display: flex;
    gap: 8px;
}
.kds-tab-btn {
    background: #1E293B;
    color: #94A3B8;
    border: 1px solid #334155;
    padding: 8px 18px;
    border-radius: 10px;
    font-weight: 700;
    font-size: 0.9rem;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: 0.2s;
}
.kds-tab-btn.active, .kds-tab-btn:hover {
    background: var(--rojo);
    color: #fff;
    border-color: var(--rojo);
}
.kds-modal {
    display: none;
    position: fixed;
    top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,0.8);
    z-index: 9999;
    align-items: center;
    justify-content: center;
}
.kds-modal.open { display: flex; }
.kds-modal-content {
    background: #1E293B;
    color: #F8FAFC;
    border-radius: 18px;
    max-width: 600px;
    width: 90%;
    max-height: 85vh;
    overflow-y: auto;
    padding: 28px;
    border: 1px solid #334155;
    box-shadow: 0 20px 40px rgba(0,0,0,0.5);
}
.receta-card {
    background: #1E293B;
    border-radius: 14px;
    padding: 20px;
    border: 1px solid #334155;
    margin-bottom: 20px;
}
.stock-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 14px;
}
.stock-table th, .stock-table td {
    padding: 12px 14px;
    text-align: left;
    border-bottom: 1px solid #334155;
}
.stock-table th {
    background: #0F172A;
    color: #FBB728;
    font-weight: 800;
    font-size: 0.85rem;
    text-transform: uppercase;
}
.stock-badge-low {
    background: #7F1D1D;
    color: #FECACA;
    padding: 4px 8px;
    border-radius: 6px;
    font-weight: 800;
    font-size: 0.75rem;
}
.stock-badge-ok {
    background: #064E3B;
    color: #A7F3D0;
    padding: 4px 8px;
    border-radius: 6px;
    font-weight: 800;
    font-size: 0.75rem;
}
</style>
</head>
<body class="kds-body">

<!-- TOPBAR COCINA (R26: Bienvenida Cocina) -->
<div class="kds-topbar">
    <div style="display:flex;align-items:center;gap:16px;">
        <div style="font-size:1.8rem;">👨‍🍳</div>
        <div>
            <h1 style="font-size:1.3rem;font-weight:900;color:var(--amarillo);margin:0;letter-spacing:0.5px;">
                MOSTAZA · APP DE COCINA KDS
            </h1>
            <span style="font-size:0.8rem;color:#94A3B8;">
                Sucursal Formosa Centro · Cocinero: <strong style="color:#fff;"><?= htmlspecialchars($nombreCocinero) ?></strong>
            </span>
        </div>
    </div>

    <!-- Navegación por Pestañas (R31: Página Principal Cocina Iniciada) -->
    <div class="kds-nav-tabs">
        <a href="cocina.php?tab=pedidos" class="kds-tab-btn <?= $tabActiva === 'pedidos' ? 'active' : '' ?>">
            <i class="fa-solid fa-fire-burner"></i> Comandas Activas (<?= $comandas->num_rows ?>)
        </a>
        <a href="cocina.php?tab=recetas" class="kds-tab-btn <?= $tabActiva === 'recetas' ? 'active' : '' ?>">
            <i class="fa-solid fa-book-open"></i> Libro de Recetas
        </a>
        <a href="cocina.php?tab=stock" class="kds-tab-btn <?= $tabActiva === 'stock' ? 'active' : '' ?>">
            <i class="fa-solid fa-boxes-stacked"></i> Stock Insumos
        </a>
    </div>

    <!-- Botones de Utilidad -->
    <div style="display:flex;gap:10px;align-items:center;">
        <button onclick="document.getElementById('modalPoliticas').classList.add('open')" class="kds-tab-btn" style="background:#334155;color:#fff;">
            <i class="fa-solid fa-shield-halved"></i> Políticas BPM
        </button>

        <?php if (!$cocineroLogueado): ?>
            <button onclick="document.getElementById('modalLogin').classList.add('open')" class="kds-tab-btn" style="background:var(--rojo);color:#fff;">
                <i class="fa-solid fa-id-badge"></i> Iniciar Turno
            </button>
        <?php else: ?>
            <a href="cocina.php?cerrar_turno=1" onclick="return confirm('¿Finalizar turno de cocina?')" class="kds-tab-btn" style="background:#DC2626;color:#fff;">
                <i class="fa-solid fa-door-open"></i> Salir
            </a>
        <?php endif; ?>
    </div>
</div>

<div style="padding:24px;">

    <!-- ============================================================ -->
    <!-- TAB 1: COMANDAS ACTIVAS (R32, R33, R34) -->
    <!-- ============================================================ -->
    <?php if ($tabActiva === 'pedidos'): ?>
        
        <?php if ($comandas->num_rows === 0): ?>
            <div style="text-align:center;padding:100px 20px;color:#64748B;">
                <i class="fa-solid fa-clipboard-check" style="font-size:4.5rem;margin-bottom:16px;color:#1E293B;"></i>
                <h2 style="color:#94A3B8;font-size:1.6rem;">¡Sin comandas pendientes en cocina!</h2>
                <p>La cocina está al día. Los nuevos pedidos de salón, mostrador y delivery aparecerán automáticamente aquí.</p>
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
                                <strong style="font-size:1.1rem;">Comanda #<?= $c['idpedido'] ?></strong>
                                <div style="font-size:0.8rem;color:#FBB728;margin-top:2px;">
                                    <?= strtoupper($c['tipo_entrega']) ?>
                                    <?php if ($c['tipo_entrega'] === 'salon' && $c['numero_mesa']): ?>
                                        <span style="background:#A30002;color:#fff;font-size:0.75rem;padding:2px 8px;border-radius:4px;font-weight:800;margin-left:4px;">MESA <?= $c['numero_mesa'] ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <span class="kds-timer" style="<?= $minutos >= 15 ? 'background:#EF4444;' : '' ?>">
                                <i class="fa-regular fa-clock"></i> <?= $minutos ?> min
                            </span>
                        </div>

                        <div style="font-size:0.8rem;color:#94A3B8;margin-bottom:12px;">
                            Cliente: <?= htmlspecialchars($c['nombre'] ? $c['nombre'] . ' ' . $c['apellido'] : 'Consumidor Final') ?>
                            <?php if ($c['tipo_entrega'] === 'delivery'): ?>
                                <br><span style="color:#38BDF8;">🛵 Delivery · <?= htmlspecialchars($c['direccion_envio'] ?? 'Formosa') ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="kds-items">
                            <?php while ($it = $items->fetch_assoc()): ?>
                            <div class="kds-item-row">
                                <span class="kds-item-qty"><?= $it['cantidad'] ?>x</span>
                                <div class="kds-item-name">
                                    <strong><?= htmlspecialchars($it['descripcion']) ?></strong>
                                    <?php if (!empty($it['modificadores'])): ?>
                                        <div class="kds-item-mod"><i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($it['modificadores']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    </div>

                    <!-- Botones de Acción de Cocina (R33: Marcar Completado, R34: Cancelar) -->
                    <div style="display:flex;gap:8px;margin-top:14px;">
                        <?php if ($c['estado'] !== 'En Preparacion'): ?>
                            <form method="post" action="cocina.php" style="flex:1;">
                                <input type="hidden" name="idpedido" value="<?= $c['idpedido'] ?>">
                                <input type="hidden" name="nuevo_estado" value="En Preparacion">
                                <button type="submit" name="cambiar_estado" class="btn-kds btn-kds-prep" style="width:100%;">
                                    <i class="fa-solid fa-kitchen-set"></i> Preparando
                                </button>
                            </form>
                        <?php endif; ?>

                        <form method="post" action="cocina.php" style="flex:1.2;">
                            <input type="hidden" name="idpedido" value="<?= $c['idpedido'] ?>">
                            <input type="hidden" name="nuevo_estado" value="Listo para Despacho">
                            <button type="submit" name="cambiar_estado" class="btn-kds btn-kds-ready" style="width:100%;">
                                <i class="fa-solid fa-circle-check"></i> ¡Listo!
                            </button>
                        </form>

                        <button onclick="abrirModalCancelar(<?= $c['idpedido'] ?>)" class="btn-kds" style="background:#7F1D1D;color:#FECACA;padding:8px 12px;" title="Cancelar Comanda">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>

    <!-- ============================================================ -->
    <!-- TAB 2: LIBRO DE RECETAS DE COCINA (R35) -->
    <!-- ============================================================ -->
    <?php elseif ($tabActiva === 'recetas'): ?>
        <div style="max-width:1100px;margin:0 auto;">
            <h2 style="color:var(--amarillo);font-size:1.5rem;font-weight:900;margin-bottom:20px;">
                <i class="fa-solid fa-book-open"></i> Fichas Técnicas y Recetas Estándar Mostaza
            </h2>

            <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(480px, 1fr));gap:20px;">
                <?php while ($r = $recetas->fetch_assoc()): ?>
                <div class="receta-card">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px;">
                        <div>
                            <span style="background:#0F172A;color:#38BDF8;font-size:0.75rem;font-weight:800;padding:3px 10px;border-radius:20px;text-transform:uppercase;">
                                <?= htmlspecialchars($r['categoria']) ?>
                            </span>
                            <h3 style="font-size:1.3rem;font-weight:900;color:#fff;margin:8px 0 2px;">
                                <?= htmlspecialchars($r['nombre_plato']) ?>
                            </h3>
                            <span style="font-size:0.8rem;color:#94A3B8;">⏱️ Tiempo estimado: <strong><?= $r['tiempo_preparacion_min'] ?> min</strong> · Dificultad: <strong><?= $r['dificultad'] ?></strong></span>
                        </div>
                        <span style="background:#0F172A;color:#FBB728;font-size:0.75rem;padding:4px 8px;border-radius:6px;border:1px solid #334155;">
                            🔥 <?= htmlspecialchars($r['temperatura_coccion'] ?? '180°C') ?>
                        </span>
                    </div>

                    <div style="background:#0F172A;padding:12px;border-radius:10px;margin-bottom:12px;border-left:3px solid var(--amarillo);">
                        <strong style="color:#FBB728;font-size:0.85rem;display:block;margin-bottom:4px;">🛒 Ingredientes y Gramajes:</strong>
                        <p style="font-size:0.85rem;color:#CBD5E1;margin:0;line-height:1.5;"><?= nl2br(htmlspecialchars($r['ingredientes_lista'])) ?></p>
                    </div>

                    <div style="background:#0F172A;padding:12px;border-radius:10px;border-left:3px solid var(--rojo);">
                        <strong style="color:#F87171;font-size:0.85rem;display:block;margin-bottom:4px;">👨‍🍳 Pasos de Preparación y Armado:</strong>
                        <p style="font-size:0.85rem;color:#E2E8F0;margin:0;line-height:1.6;white-space:pre-line;"><?= htmlspecialchars($r['pasos_preparacion']) ?></p>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>

    <!-- ============================================================ -->
    <!-- TAB 3: STOCK DE INGREDIENTES E INSUMOS (R36, R37) -->
    <!-- ============================================================ -->
    <?php elseif ($tabActiva === 'stock'): ?>
        <div style="max-width:1100px;margin:0 auto;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
                <div>
                    <h2 style="color:var(--amarillo);font-size:1.5rem;font-weight:900;margin:0;">
                        <i class="fa-solid fa-boxes-stacked"></i> Stock de Ingredientes en Cocina
                    </h2>
                    <p style="font-size:0.85rem;color:#94A3B8;margin:4px 0 0;">Control de inventario en tiempo real y solicitud de reposición al administrador.</p>
                </div>

                <!-- Botón Solicitar Insumos al Admin (R37) -->
                <button onclick="document.getElementById('modalSolicitud').classList.add('open')" class="kds-tab-btn" style="background:var(--rojo);color:#fff;padding:10px 20px;font-size:0.95rem;">
                    <i class="fa-solid fa-cart-plus"></i> Solicitar Insumos al Admin
                </button>
            </div>

            <div style="background:#1E293B;border-radius:14px;overflow:hidden;border:1px solid #334155;">
                <table class="stock-table">
                    <thead>
                        <tr>
                            <th>Insumo / Ingrediente</th>
                            <th>Categoría</th>
                            <th>Stock Actual</th>
                            <th>Stock Mínimo</th>
                            <th>Estado</th>
                            <th>Proveedor</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($ins = $stockInsumos->fetch_assoc()): 
                            $esBajo = floatval($ins['stock_actual']) <= floatval($ins['stock_minimo']);
                        ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($ins['nombre']) ?></strong></td>
                            <td><span style="color:#94A3B8;font-size:0.85rem;"><?= htmlspecialchars($ins['categoria']) ?></span></td>
                            <td><strong style="color:<?= $esBajo ? '#EF4444' : '#10B981' ?>;font-size:1rem;"><?= number_format($ins['stock_actual'], 1) ?> <?= htmlspecialchars($ins['unidad_medida']) ?></strong></td>
                            <td style="color:#94A3B8;"><?= number_format($ins['stock_minimo'], 1) ?> <?= htmlspecialchars($ins['unidad_medida']) ?></td>
                            <td>
                                <?php if ($esBajo): ?>
                                    <span class="stock-badge-low"><i class="fa-solid fa-triangle-exclamation"></i> REPOSICIÓN URGENTE</span>
                                <?php else: ?>
                                    <span class="stock-badge-ok"><i class="fa-solid fa-check"></i> NORMAL</span>
                                <?php endif; ?>
                            </td>
                            <td style="color:#64748B;font-size:0.8rem;"><?= htmlspecialchars($ins['proveedor']) ?></td>
                            <td>
                                <button onclick="prellenarSolicitud('<?= addslashes($ins['nombre']) ?>', '<?= addslashes($ins['unidad_medida']) ?>')" 
                                        style="background:#334155;border:none;color:#fff;padding:4px 10px;border-radius:6px;font-size:0.8rem;cursor:pointer;font-weight:700;">
                                    Pedir +
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php endif; ?>

</div>

<!-- ============================================================ -->
<!-- MODALES: POLÍTICAS, LOGIN, SOLICITUD Y CANCELACIÓN -->
<!-- ============================================================ -->

<!-- Modal Políticas BPM (R27) -->
<div class="kds-modal" id="modalPoliticas">
    <div class="kds-modal-content">
        <div style="display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #334155;padding-bottom:12px;margin-bottom:16px;">
            <h3 style="color:var(--amarillo);margin:0;font-size:1.2rem;"><i class="fa-solid fa-shield-halved"></i> Políticas de Cocina y BPM Mostaza</h3>
            <button onclick="document.getElementById('modalPoliticas').classList.remove('open')" style="background:none;border:none;color:#94A3B8;font-size:1.2rem;cursor:pointer;">✕</button>
        </div>
        <div style="font-size:0.9rem;color:#CBD5E1;line-height:1.6;">
            <p><strong>1. Higiene Personal y Uniforme:</strong><br>Uso obligatorio de cofia, barbijo, delantal limpio y lavado de manos cada 20 minutos con jabón antibacterial.</p>
            <p><strong>2. Control de Temperaturas:</strong><br>Plancha de medallones a 180°C (temperatura interna del medallón ≥ 75°C). Freidora a 175°C.</p>
            <p><strong>3. Cadena de Frío:</strong><br>No dejar insumos lácteos o cárnicos fuera de la heladera/cámara por más de 15 minutos.</p>
            <p><strong>4. Cancelación de Comandas:</strong><br>Solo proceder a cancelar cuando un insumo esté completamente agotado, justificando el motivo en el sistema para dar aviso inmediato al cajero.</p>
        </div>
        <button onclick="document.getElementById('modalPoliticas').classList.remove('open')" style="width:100%;margin-top:16px;background:var(--rojo);color:#fff;border:none;padding:12px;border-radius:10px;font-weight:800;cursor:pointer;">
            Entendido y Aceptar
        </button>
    </div>
</div>

<!-- Modal Login Cocinero (R28, R29, R30) -->
<div class="kds-modal" id="modalLogin">
    <div class="kds-modal-content" style="max-width:440px;">
        <h3 style="color:var(--amarillo);margin:0 0 12px;font-size:1.3rem;">👨‍🍳 Iniciar Turno de Cocina</h3>
        <p style="font-size:0.85rem;color:#94A3B8;margin-bottom:16px;">Ingresá tu nombre para registrar automáticamente el horario de entrada en el sistema.</p>
        
        <form method="post" action="cocina.php">
            <label style="font-size:0.85rem;font-weight:700;display:block;margin-bottom:6px;">Nombre del Cocinero / Responsable:</label>
            <input type="text" name="nombre_cocinero" required placeholder="Ej: Marcos Silva" 
                   style="width:100%;padding:10px 14px;border-radius:8px;border:1px solid #334155;background:#0F172A;color:#fff;font-size:1rem;margin-bottom:16px;">

            <button type="submit" name="login_cocina" style="width:100%;background:var(--rojo);color:#fff;border:none;padding:12px;border-radius:10px;font-weight:800;cursor:pointer;">
                Iniciar Turno y Fichar Entrada
            </button>
        </form>
    </div>
</div>

<!-- Modal Solicitar Insumo al Admin (R37) -->
<div class="kds-modal" id="modalSolicitud">
    <div class="kds-modal-content" style="max-width:480px;">
        <h3 style="color:var(--amarillo);margin:0 0 12px;font-size:1.3rem;">📦 Solicitar Reposición de Insumos</h3>
        
        <form method="post" action="cocina.php">
            <div style="margin-bottom:12px;">
                <label style="font-size:0.85rem;font-weight:700;display:block;margin-bottom:6px;">Nombre del Insumo / Ingrediente:</label>
                <input type="text" name="insumo_nombre" id="sol_insumo_nombre" required placeholder="Ej: Queso Cheddar en Fetas"
                       style="width:100%;padding:10px;border-radius:8px;border:1px solid #334155;background:#0F172A;color:#fff;">
            </div>

            <div style="display:flex;gap:10px;margin-bottom:12px;">
                <div style="flex:1;">
                    <label style="font-size:0.85rem;font-weight:700;display:block;margin-bottom:6px;">Cantidad:</label>
                    <input type="number" step="0.5" name="cantidad" required value="10"
                           style="width:100%;padding:10px;border-radius:8px;border:1px solid #334155;background:#0F172A;color:#fff;">
                </div>
                <div style="flex:1;">
                    <label style="font-size:0.85rem;font-weight:700;display:block;margin-bottom:6px;">Unidad:</label>
                    <input type="text" name="unidad" id="sol_insumo_unidad" required value="kg"
                           style="width:100%;padding:10px;border-radius:8px;border:1px solid #334155;background:#0F172A;color:#fff;">
                </div>
            </div>

            <div style="margin-bottom:12px;">
                <label style="font-size:0.85rem;font-weight:700;display:block;margin-bottom:6px;">Prioridad:</label>
                <select name="prioridad" style="width:100%;padding:10px;border-radius:8px;border:1px solid #334155;background:#0F172A;color:#fff;">
                    <option value="Media">Media (Durante el turno)</option>
                    <option value="Alta">Alta (Queda menos de 1 hora)</option>
                    <option value="Urgente">🚨 Urgente (Stock agotado)</option>
                </select>
            </div>

            <div style="margin-bottom:16px;">
                <label style="font-size:0.85rem;font-weight:700;display:block;margin-bottom:6px;">Motivo / Observación:</label>
                <input type="text" name="motivo" value="Stock crítico en cocina"
                       style="width:100%;padding:10px;border-radius:8px;border:1px solid #334155;background:#0F172A;color:#fff;">
            </div>

            <div style="display:flex;gap:10px;">
                <button type="button" onclick="document.getElementById('modalSolicitud').classList.remove('open')" style="flex:1;background:#334155;color:#fff;border:none;padding:12px;border-radius:10px;font-weight:700;cursor:pointer;">
                    Cancelar
                </button>
                <button type="submit" name="solicitar_insumo" style="flex:1;background:var(--rojo);color:#fff;border:none;padding:12px;border-radius:10px;font-weight:800;cursor:pointer;">
                    Enviar al Admin
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Cancelar Comanda (R34) -->
<div class="kds-modal" id="modalCancelar">
    <div class="kds-modal-content" style="max-width:440px;">
        <h3 style="color:#EF4444;margin:0 0 12px;font-size:1.3rem;">❌ Cancelar Comanda</h3>
        <p style="font-size:0.85rem;color:#94A3B8;margin-bottom:14px;">Ingresá el motivo de la cancelación para notificar al cajero.</p>

        <form method="post" action="cocina.php">
            <input type="hidden" name="idpedido" id="canc_idpedido">
            
            <label style="font-size:0.85rem;font-weight:700;display:block;margin-bottom:6px;">Motivo de Cancelación:</label>
            <select name="motivo_cancelacion" style="width:100%;padding:10px;border-radius:8px;border:1px solid #334155;background:#0F172A;color:#fff;margin-bottom:16px;">
                <option value="Insumo agotado en cocina">Insumo agotado en cocina</option>
                <option value="Error en la especificación del pedido">Error en la especificación del pedido</option>
                <option value="Problema técnico en equipamiento">Problema técnico en equipamiento</option>
                <option value="Cancelado a solicitud del cliente">Cancelado a solicitud del cliente</option>
            </select>

            <div style="display:flex;gap:10px;">
                <button type="button" onclick="document.getElementById('modalCancelar').classList.remove('open')" style="flex:1;background:#334155;color:#fff;border:none;padding:12px;border-radius:10px;font-weight:700;cursor:pointer;">
                    Volver
                </button>
                <button type="submit" name="cancelar_comanda_cocina" style="flex:1;background:#DC2626;color:#fff;border:none;padding:12px;border-radius:10px;font-weight:800;cursor:pointer;">
                    Confirmar Cancelación
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

function prellenarSolicitud(nombre, unidad) {
    document.getElementById('sol_insumo_nombre').value = nombre;
    document.getElementById('sol_insumo_unidad').value = unidad;
    document.getElementById('modalSolicitud').classList.add('open');
}

// Auto-recarga cada 10 segundos en la pestaña de pedidos para tiempo real
<?php if ($tabActiva === 'pedidos'): ?>
setInterval(function() {
    // Si no hay modales abiertos, recargar
    if (!document.querySelector('.kds-modal.open')) {
        window.location.reload();
    }
}, 10000);
<?php endif; ?>
</script>

</body>
</html>
