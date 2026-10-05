<?php
declare(strict_types=1);
require_once __DIR__ . '/../BD/conexion.php';
if (session_status() === PHP_SESSION_NONE) { session_name('SHIZEN_CLIENTE_SESSION'); session_start(); }
if (empty($_SESSION['id_usuario'])) { header('Location: ../index.php?login_redirect=php%2Fpedidos.php#login'); exit; }

$orderId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$pdo = obtenerConexion();
$stmt = $pdo->prepare(
    'SELECT p.*, c.id_compra, c.total, c.metodo_pago, e.id_entrega, e.estado AS entrega_estado,
            e.codigo_entrega, e.fecha_confirmacion, n.nombre AS negocio_nombre, n.logo_url AS negocio_logo,
            r.nombre AS repartidor_nombre, r.apellido AS repartidor_apellido,
            r.foto_url AS repartidor_foto, r.vehiculo AS repartidor_vehiculo
     FROM pedido p
     LEFT JOIN compra c ON c.id_pedido = p.id_pedido
     LEFT JOIN entrega e ON e.id_compra = c.id_compra
     LEFT JOIN repartidor r ON r.id_repartidor = e.id_repartidor
     LEFT JOIN negocios n ON n.id_negocio = p.id_negocio
     WHERE p.id_pedido = ? AND p.id_usuario = ? LIMIT 1'
);
$stmt->execute([$orderId, (int) $_SESSION['id_usuario']]);
$order = $stmt->fetch();
if (!$order) { http_response_code(404); exit('Pedido no encontrado.'); }

$code = $order['codigo_entrega'] ?? null;
$deliveryStatus = strtolower(trim((string)($order['entrega_estado'] ?? '')));
$orderStatus = strtolower(trim((string)($order['estado'] ?? '')));

// 5 estados de entrega:
// 1. Pedido realizado (Confirmado)
// 2. En preparación (Negocio preparando)
// 3. Recogido (Repartidor recogió pedido en el negocio)
// 4. En camino (Repartidor en ruta hacia dirección)
// 5. Entregado (Entrega completada y confirmada)
$isDelivered = !empty($order['fecha_confirmacion']) || in_array($orderStatus, ['entregado', 'recibido'], true) || in_array($deliveryStatus, ['entregado'], true);
$onRoute = $isDelivered || in_array($deliveryStatus, ['en camino'], true) || in_array($orderStatus, ['en camino'], true);
$courierHasOrder = $onRoute || in_array($deliveryStatus, ['recogido en negocio', 'recogido'], true) || in_array($orderStatus, ['recogido en negocio', 'recogido'], true);
$isPrepared = $courierHasOrder || in_array($orderStatus, ['preparado', 'asignado'], true);

$businessRatingCheck = $pdo->prepare('SELECT 1 FROM calificacion WHERE id_usuario = ? AND id_negocio = ? LIMIT 1');
$businessRatingCheck->execute([(int) $_SESSION['id_usuario'], (int) $order['id_negocio']]);
$businessRated = (bool) $businessRatingCheck->fetchColumn();

$courierRatingCheck = $pdo->prepare('SELECT 1 FROM calificacion_repartidor WHERE id_usuario = ? AND id_pedido = ? LIMIT 1');
$courierRatingCheck->execute([(int) $_SESSION['id_usuario'], (int) $order['id_pedido']]);
$courierRated = (bool) $courierRatingCheck->fetchColumn();

$courierName = trim((string)($order['repartidor_nombre'] ?? '') . ' ' . (string)($order['repartidor_apellido'] ?? ''));
$courierName = $courierName !== '' ? $courierName : 'Repartidor asignado';

$businessLogo = trim((string)($order['negocio_logo'] ?? ''));
if ($businessLogo !== '' && !str_starts_with($businessLogo, 'http') && !str_starts_with($businessLogo, 'data:')) {
    $businessLogo = preg_replace('#^\.\./#', '', $businessLogo);
}
if ($businessLogo === '') {
    $businessLogo = 'assets/logo_negocio.png';
}

$courierPhoto = trim((string)($order['repartidor_foto'] ?? ''));
if ($courierPhoto !== '' && !str_starts_with($courierPhoto, 'http') && !str_starts_with($courierPhoto, 'data:')) {
    $courierPhoto = preg_replace('#^\.\./#', '', $courierPhoto);
}
if ($courierPhoto === '') {
    $courierPhoto = 'assets/logo-repartidor.png';
}
?>
<!doctype html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><base href="../"><title>Pedido #<?= (int)$order['id_pedido'] ?> | Shizen</title><link rel="stylesheet" href="css/styles.css"><link rel="stylesheet" href="css/nav.css?v=20260930-ingreso-icon-1"><link rel="stylesheet" href="css/modals.css?v=20261003-rating-fix-1"><link rel="stylesheet" href="css/orders.css?v=20261003-5steps-1">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head><body>
<?php include __DIR__ . '/../forms/navegacion.php'; ?>
<main class="orders-page"><?php $volverHref = 'php/pedidos.php'; $volverLabel = 'Volver a mis pedidos'; include __DIR__ . '/../forms/boton_volver.php'; ?><section class="order-detail">
<h1>Pedido #<?= (int)$order['id_pedido'] ?></h1><p><?= htmlspecialchars($order['descripcion']) ?></p><p>Estado: <strong><?= htmlspecialchars($isDelivered ? 'Entregado' : $order['estado']) ?></strong></p><p>Dirección: <?= htmlspecialchars($order['direccion_entrega']) ?></p>
<section class="tracking-card" aria-label="Seguimiento del pedido">
  <div class="tracking-map">
    <div class="map-road map-road-one"></div>
    <div class="map-road map-road-two"></div>
    <div class="map-block map-block-one"></div>
    <div class="map-block map-block-two"></div>
    <div class="map-block map-block-three"></div>
    <div class="map-route"></div>
    <div class="map-marker map-marker-start" aria-label="Ubicación del repartidor">&#128692;</div>
    <div class="map-marker map-marker-end" aria-label="Destino de entrega">⌖</div>
    <div class="map-label"><span class="map-pin">⌖</span><span>Destino de entrega</span></div>
  </div>
  <div class="courier-panel">
    <div class="courier-avatar-wrap">
      <img class="courier-avatar" src="<?= htmlspecialchars($courierPhoto, ENT_QUOTES, 'UTF-8') ?>" alt="Foto de <?= htmlspecialchars($courierName, ENT_QUOTES, 'UTF-8') ?>" onerror="this.onerror=null;this.src='assets/logo-repartidor.png'">
    </div>
    <div class="courier-info">
      <span class="courier-kicker">Tu repartidor</span>
      <h2><?= htmlspecialchars($courierName) ?></h2>
      <div class="courier-rating" aria-label="5 de 5 estrellas"><span aria-hidden="true">&#9733;&#9733;&#9733;&#9733;&#9733;</span><small>5.0</small></div>
    </div>
    <div class="courier-status">
      <span class="status-dot"></span>
      <strong><?= $isDelivered ? 'Entregado' : ($onRoute ? 'En camino' : ($courierHasOrder ? 'Pedido recogido' : 'Asignando repartidor')) ?></strong>
      <small><?= $isDelivered ? '¡Pedido entregado con éxito!' : ($onRoute ? 'Tu pedido va hacia ti' : ($courierHasOrder ? 'Pronto iniciará el recorrido' : 'Te avisaremos cuando esté listo')) ?></small>
    </div>
  </div>
  <div class="order-tracking">
    <h2>Rastreo del pedido</h2>
    <div class="tracking-line">
      <!-- Paso 1 -->
      <div class="tracking-step is-complete">
        <span>&#10003;</span>
        <strong>Pedido realizado</strong>
        <small>Confirmado</small>
      </div>

      <!-- Paso 2 -->
      <div class="tracking-step <?= $isPrepared ? 'is-complete' : 'is-current' ?>">
        <span><?= $isPrepared ? '&#10003;' : '2' ?></span>
        <strong>En preparación</strong>
        <small><?= $isPrepared ? 'Negocio preparó tu pedido' : 'Preparando en cocina' ?></small>
      </div>

      <!-- Paso 3 -->
      <div class="tracking-step <?= $courierHasOrder ? 'is-complete' : ($isPrepared ? 'is-current' : '') ?>">
        <span><?= $courierHasOrder ? '&#10003;' : '3' ?></span>
        <strong>Recogido</strong>
        <small><?= $courierHasOrder ? 'Repartidor ya lo recibió' : ($isPrepared ? 'Esperando al repartidor' : 'Pendiente de recogida') ?></small>
      </div>

      <!-- Paso 4 -->
      <div class="tracking-step <?= $isDelivered ? 'is-complete' : ($onRoute ? 'is-current' : '') ?>">
        <span><?= $isDelivered ? '&#10003;' : '4' ?></span>
        <strong>En camino</strong>
        <small><?= $isDelivered ? 'Recorrido finalizado' : ($onRoute ? 'Hacia tu dirección' : 'Pendiente de salida') ?></small>
      </div>

      <!-- Paso 5 -->
      <div class="tracking-step <?= $isDelivered ? 'is-complete' : '' ?>">
        <span><?= $isDelivered ? '&#10003;' : '5' ?></span>
        <strong>Entregado</strong>
        <small><?= $isDelivered ? '¡Pedido entregado!' : 'Por entregar' ?></small>
      </div>
    </div>
  </div>
</section>
<?php if (!$isDelivered): ?>
  <?php if ($courierHasOrder && !$onRoute): ?><p class="order-success">El repartidor ya recogió tu pedido. Pronto iniciará el recorrido hacia tu dirección.</p><?php elseif ($onRoute): ?><p class="order-success">Tu pedido está en camino. Comparte el código al llegar para confirmar la entrega.</p><?php endif; ?>
  <div class="delivery-code-box" style="background:#f0fdf4;border:2px dashed #22c55e;border-radius:12px;padding:16px;text-align:center;margin:20px 0">
    <div style="font-size:14px;color:#15803d;font-weight:700">🔑 Tu Código de Entrega:</div>
    <div style="font-size:28px;font-weight:900;letter-spacing:4px;color:#166534;margin:6px 0"><?= htmlspecialchars((string)$order['codigo_entrega']) ?></div>
    <div style="font-size:12px;color:#4b5563">Entrégaselo al repartidor cuando llegue con tu pedido para confirmar la entrega.</div>
  </div>
<?php else: ?>
  <p class="order-success">&#9989; Pedido entregado. ¡Gracias por elegir Shizen!</p>

  <?php if (!$businessRated || !$courierRated): ?>
    <div class="rating-prompt-box">
      <div class="rating-prompt-info">
        <h3>⭐ Califica tu experiencia</h3>
        <p>Tu opinión es muy importante para nosotros. Califica el pedido de <strong><?= htmlspecialchars((string)($order['negocio_nombre'] ?? 'Shizen')) ?></strong> y la entrega de <strong><?= htmlspecialchars($courierName) ?></strong>.</p>
      </div>
      <a href="#ratingModal" class="btn-launch-rating">
        ★ Calificar pedido
      </a>
    </div>
  <?php else: ?>
    <p class="order-success" style="background:#f0fdf4;border:1px solid #86efac;color:#166534;padding:12px 16px;border-radius:12px;margin:16px 0;">
      ⭐ Ya calificaste el negocio y el repartidor de este pedido. ¡Muchas gracias por tu valoración!
    </p>
  <?php endif; ?>
<?php endif; ?>

</section></main>

<div id="overlays"><?php include __DIR__ . '/../forms/modales.php'; ?></div>
<script src="js/app.js?v=20261003-rating-2"></script>
</body></html>

