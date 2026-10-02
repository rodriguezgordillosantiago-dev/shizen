<?php
declare(strict_types=1);
require_once __DIR__ . '/../BD/conexion.php';

if (session_status() === PHP_SESSION_NONE) { session_name('SHIZEN_CLIENTE_SESSION'); session_start(); }
if (empty($_SESSION['id_usuario'])) {
    header('Location: ../index.php?login_redirect=php%2Fpedidos.php#login');
    exit;
}

$pdo = obtenerConexion();
$stmt = $pdo->prepare(
    'SELECT p.id_pedido, p.descripcion, p.estado, p.fecha_creacion, n.nombre AS negocio_nombre,
            c.total, e.estado AS entrega_estado
     FROM pedido p
     LEFT JOIN negocios n ON n.id_negocio = p.id_negocio
     LEFT JOIN compra c ON c.id_pedido = p.id_pedido
     LEFT JOIN entrega e ON e.id_compra = c.id_compra
     WHERE p.id_usuario = ?
     ORDER BY p.fecha_creacion DESC'
);
$stmt->execute([(int) $_SESSION['id_usuario']]);
$orders = $stmt->fetchAll();
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><base href="../">
  <title>Mis pedidos | Shizen</title>
  <link rel="stylesheet" href="css/styles.css"><link rel="stylesheet" href="css/nav.css?v=20260930-ingreso-icon-1"><link rel="stylesheet" href="css/modals.css?v=20260930-cart-clear-1"><link rel="stylesheet" href="css/orders.css">
</head>
<body>
<?php include __DIR__ . '/../forms/navegacion.php'; ?>
<main class="orders-page">
  <?php $volverHref = 'index.php'; $volverLabel = 'Volver al inicio'; include __DIR__ . '/../forms/boton_volver.php'; ?>
  <h1>Mis pedidos</h1>
  <div class="orders-list">
    <?php foreach ($orders as $order): ?>
      <a class="order-card" href="php/pedido.php?id=<?= (int) $order['id_pedido'] ?>">
        <strong>Pedido #<?= (int) $order['id_pedido'] ?></strong>
        <span><?= htmlspecialchars($order['negocio_nombre'] ?? 'Shizen') ?></span>
        <small><?= htmlspecialchars($order['descripcion'] ?? '') ?></small>
        <b><?= htmlspecialchars($order['entrega_estado'] ?: $order['estado']) ?></b>
      </a>
    <?php endforeach; ?>
    <?php if (!$orders): ?><p>No tienes pedidos registrados.</p><?php endif; ?>
  </div>
</main>
<div id="overlays"><?php include __DIR__ . '/../forms/modales.php'; ?></div>
<script src="js/app.js?v=20260930-cart-clear-1"></script>
</body>
</html>
