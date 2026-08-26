<?php
// ============================================================
// admin/cocina.php
// Pantalla KDS (Kitchen Display System) para Cocineros en Vivo.
// ============================================================
session_start();
require('../conexion.php');

// Procesar cambio de estado
if (isset($_POST['cambiar_estado'])) {
    $idpedido = intval($_POST['idpedido']);
    $nuevoEstado = $_POST['nuevo_estado'];
    
    if (in_array($nuevoEstado, ['En Cocina', 'Listo', 'Entregado', 'Cancelado'])) {
        $stmtUpd = $conn->prepare("UPDATE pedido_web SET estado = ? WHERE idpedido = ?");
        $stmtUpd->bind_param("si", $nuevoEstado, $idpedido);
        $stmtUpd->execute();
    }
    header("Location: cocina.php");
    exit();
}

// Consultar comandas activas
$comandas = $conn->query(
    "SELECT p.*, u.nombre, u.apellido 
     FROM pedido_web p 
     LEFT JOIN usuario_web u ON p.idusuario = u.idusuario 
     WHERE p.estado IN ('Recibido', 'En Cocina', 'En Preparacion') 
     ORDER BY p.idpedido ASC"
);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>KDS Cocina · Comandas en Tiempo Real</title>
<link rel="stylesheet" href="admin.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="kds-body">

<div style="padding:20px 24px;background:#0F172A;border-bottom:2px solid #1E293B;display:flex;justify-content:space-between;align-items:center;">
    <div style="display:flex;align-items:center;gap:14px;">
        <a href="index.php" style="color:#94A3B8;text-decoration:none;font-size:1.2rem;" title="Volver al Dashboard"><i class="fa-solid fa-arrow-left"></i></a>
        <h1 style="font-size:1.4rem;font-weight:900;color:#FBB728;display:flex;align-items:center;gap:10px;margin:0;">
            <i class="fa-solid fa-fire-burner" style="color:#EF4444;"></i> PANTALLA KDS COCINA MOSTAZA
        </h1>
    </div>
    <div style="display:flex;gap:14px;align-items:center;">
        <span style="background:#1E293B;color:#10B981;font-weight:800;padding:6px 14px;border-radius:50px;font-size:0.85rem;">
            <i class="fa-solid fa-circle-dot fa-beat"></i> TIEMPO REAL ACTIVO
        </span>
        <button onclick="window.location.reload()" style="background:#334155;border:none;color:#fff;padding:8px 14px;border-radius:8px;cursor:pointer;font-weight:700;font-size:0.85rem;">
            <i class="fa-solid fa-arrows-rotate"></i> Actualizar
        </button>
    </div>
</div>

<div style="padding:24px;">

    <?php if ($comandas->num_rows === 0): ?>
        <div style="text-align:center;padding:100px 20px;color:#64748B;">
            <i class="fa-solid fa-clipboard-check" style="font-size:4rem;margin-bottom:16px;color:#1E293B;"></i>
            <h2 style="color:#94A3B8;font-size:1.6rem;">¡Sin comandas pendientes!</h2>
            <p>La cocina está al día. Las nuevas órdenes del sitio web aparecerán automáticamente aquí.</p>
        </div>
    <?php else: ?>

        <div class="kds-grid">
            <?php while ($c = $comandas->fetch_assoc()): ?>
            <?php
            // Calcular minutos transcurridos
            $minutos = intval((time() - strtotime($c['fecha'])) / 60);
            $ticketClass = ($minutos >= 15) ? 'urgente' : (($c['estado'] === 'En Cocina') ? 'preparando' : '');

            // Detalle de ítems
            $stmtDet = $conn->prepare("SELECT * FROM detalle_pedido_web WHERE idpedido = ?");
            $stmtDet->bind_param("i", $c['idpedido']);
            $stmtDet->execute();
            $items = $stmtDet->get_result();
            ?>
            <div class="kds-ticket <?= $ticketClass ?>">
                <div>
                    <div class="kds-ticket-header">
                        <div>
                            <strong>#<?= $c['idpedido'] ?> · <?= strtoupper($c['tipo_entrega']) ?></strong>
                            <?php if ($c['tipo_entrega'] === 'salon' && $c['numero_mesa']): ?>
                                <span style="background:#A30002;color:#fff;font-size:0.75rem;padding:2px 8px;border-radius:4px;font-weight:800;">MESA <?= $c['numero_mesa'] ?></span>
                            <?php endif; ?>
                        </div>
                        <span class="kds-timer <?= $minutos >= 15 ? 'style="background:#EF4444;"' : '' ?>">
                            <i class="fa-regular fa-clock"></i> <?= $minutos ?> min
                        </span>
                    </div>

                    <div style="font-size:0.8rem;color:#94A3B8;margin-bottom:12px;">
                        Cliente: <?= htmlspecialchars($c['nombre'] ? $c['nombre'] . ' ' . $c['apellido'] : 'Cliente Mostaza') ?>
                        <?php if (!empty($c['notas'])): ?>
                            <div style="color:#FBB728;margin-top:4px;font-weight:600;"><i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($c['notas']) ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Lista de Productos a Preparar -->
                    <div style="border-top:1px solid #334155;padding-top:10px;">
                        <?php while ($it = $items->fetch_assoc()): ?>
                        <div style="margin-bottom:10px;padding-bottom:8px;border-bottom:1px dashed #334155;">
                            <div class="kds-item-row">
                                <span style="font-weight:800;color:#fff;font-size:1.05rem;">
                                    <span style="color:#FBB728;"><?= $it['cantidad'] ?>x</span> <?= htmlspecialchars($it['descripcion']) ?>
                                </span>
                            </div>
                            <?php if (!empty($it['modificadores'])): ?>
                                <div class="kds-item-mods">
                                    <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($it['modificadores']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>

                <!-- Botones de Acción KDS -->
                <div>
                    <?php if ($c['estado'] === 'Recibido'): ?>
                        <form method="post" action="cocina.php">
                            <input type="hidden" name="idpedido" value="<?= $c['idpedido'] ?>">
                            <input type="hidden" name="nuevo_estado" value="En Cocina">
                            <button type="submit" name="cambiar_estado" class="btn-kds btn-kds-cook">
                                <i class="fa-solid fa-fire"></i> EMPEZAR A COCINAR
                            </button>
                        </form>
                    <?php else: ?>
                        <form method="post" action="cocina.php">
                            <input type="hidden" name="idpedido" value="<?= $c['idpedido'] ?>">
                            <input type="hidden" name="nuevo_estado" value="Listo">
                            <button type="submit" name="cambiar_estado" class="btn-kds btn-kds-ready">
                                <i class="fa-solid fa-check-double"></i> MARCAR COMO LISTO
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

            </div>
            <?php endwhile; ?>
        </div>

    <?php endif; ?>

</div>

<script>
// Auto-recarga cada 5 segundos para recibir nuevos pedidos
setTimeout(() => window.location.reload(), 5000);
</script>

</body>
</html>
