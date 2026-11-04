<?php
session_start();
require('conexion.php');

$isAjax = isset($_POST['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
$volver = $_POST['volver'] ?? ($_GET['volver'] ?? ($_SERVER['HTTP_REFERER'] ?? 'productos.php'));

if (!isset($_SESSION['usuario_id'])) {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode([
            'ok' => false,
            'error' => 'Debes iniciar sesión para agregar al carrito.',
            'login_required' => true
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }
    $_SESSION['redirect_after_login'] = $volver;
    header("Location: login.php");
    exit();
}

$idproducto = intval($_POST['idproducto'] ?? ($_GET['id'] ?? 0));
$cantidad   = max(1, intval($_POST['cantidad'] ?? ($_GET['cantidad'] ?? 1)));

if ($idproducto > 0) {
    $stmt = $conn->prepare("SELECT idproducto, nombre, precio, imagen FROM producto WHERE idproducto = ? AND activo = 1");
    $stmt->bind_param("i", $idproducto);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        $p = $res->fetch_assoc();

        if (!isset($_SESSION['carrito'])) {
            $_SESSION['carrito'] = [];
        }

        $modificadores = trim($_POST['modificadores'] ?? ($_GET['modificadores'] ?? ''));
        $key = 'p_' . $p['idproducto'] . ($modificadores !== '' ? '_' . substr(md5($modificadores), 0, 6) : '');

        if (isset($_SESSION['carrito'][$key])) {
            $_SESSION['carrito'][$key]['cantidad'] += $cantidad;
            if ($modificadores !== '') {
                $_SESSION['carrito'][$key]['modificadores'] = $modificadores;
            }
        } else {
            $_SESSION['carrito'][$key] = [
                'tipo'          => 'catalogo',
                'idproducto'    => $p['idproducto'],
                'nombre'        => $p['nombre'],
                'imagen'        => $p['imagen'],
                'precio'        => (float) $p['precio'],
                'cantidad'      => $cantidad,
                'modificadores' => $modificadores,
            ];
        }

        if ($isAjax) {
            header('Content-Type: application/json');
            $totalItems = 0;
            $totalPesos = 0;
            foreach ($_SESSION['carrito'] as $it) {
                $totalItems += $it['cantidad'];
                $totalPesos += ($it['precio'] * $it['cantidad']);
            }
            echo json_encode([
                'ok' => true,
                'mensaje' => '¡' . $p['nombre'] . ' agregado al pedido!',
                'total_items' => $totalItems,
                'total_pesos' => $totalPesos,
                'total_formateado' => '$' . number_format($totalPesos, 0, ',', '.')
            ], JSON_UNESCAPED_UNICODE);
            exit();
        }
    }
}

if ($isAjax) {
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => 'Producto no encontrado.'], JSON_UNESCAPED_UNICODE);
    exit();
}

header("Location: " . $volver);
exit();
