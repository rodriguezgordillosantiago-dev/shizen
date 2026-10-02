<?php
declare(strict_types=1);
require_once __DIR__ . '/../BD/conexion.php';
if (session_status() === PHP_SESSION_NONE) { session_name('SHIZEN_CLIENTE_SESSION'); session_start(); }
if (empty($_SESSION['id_usuario'])) { header('Location: ../index.php?login_redirect=php%2Fpedidos.php#login'); exit; }

$orderId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$pdo = obtenerConexion();
$stmt = $pdo->prepare(
    'SELECT p.*, c.id_compra, c.total, c.metodo_pago, e.id_entrega, e.estado AS entrega_estado,
            e.codigo_entrega, e.fecha_confirmacion, n.nombre AS negocio_nombre,
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
$courierHasOrder = in_array($deliveryStatus, ['recogido en negocio', 'en camino', 'entregado'], true);
$onRoute = in_array($deliveryStatus, ['en camino', 'entregado'], true);
$orderStatus = strtolower(trim((string)($order['estado'] ?? '')));
$isPrepared = in_array($orderStatus, ['preparado', 'recogido en negocio', 'en camino', 'entregado'], true);
$businessRatingCheck = $pdo->prepare('SELECT 1 FROM calificacion WHERE id_usuario = ? AND id_negocio = ? LIMIT 1');
$businessRatingCheck->execute([(int) $_SESSION['id_usuario'], (int) $order['id_negocio']]);
$businessRated = (bool) $businessRatingCheck->fetchColumn();
$courierRatingCheck = $pdo->prepare('SELECT 1 FROM calificacion_repartidor WHERE id_usuario = ? AND id_pedido = ? LIMIT 1');
$courierRatingCheck->execute([(int) $_SESSION['id_usuario'], (int) $order['id_pedido']]);
$courierRated = (bool) $courierRatingCheck->fetchColumn();
$courierName = trim((string)($order['repartidor_nombre'] ?? '') . ' ' . (string)($order['repartidor_apellido'] ?? ''));
$courierName = $courierName !== '' ? $courierName : 'Repartidor asignado';
$courierPhoto = trim((string)($order['repartidor_foto'] ?? ''));
$courierPhoto = $courierPhoto !== '' ? $courierPhoto : 'assets/logo-repartidor.png';
?>
<!doctype html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><base href="../"><title>Pedido #<?= (int)$order['id_pedido'] ?> | Shizen</title><link rel="stylesheet" href="css/styles.css"><link rel="stylesheet" href="css/nav.css?v=20260930-ingreso-icon-1"><link rel="stylesheet" href="css/modals.css?v=20260930-cart-clear-1"><link rel="stylesheet" href="css/orders.css?v=20260924-2329"></head><body>
<?php include __DIR__ . '/../forms/navegacion.php'; ?>
<main class="orders-page"><?php $volverHref = 'php/pedidos.php'; $volverLabel = 'Volver a mis pedidos'; include __DIR__ . '/../forms/boton_volver.php'; ?><section class="order-detail">
<h1>Pedido #<?= (int)$order['id_pedido'] ?></h1><p><?= htmlspecialchars($order['descripcion']) ?></p><p>Estado: <strong><?= htmlspecialchars($order['estado']) ?></strong></p><p>Dirección: <?= htmlspecialchars($order['direccion_entrega']) ?></p>
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
    <div class="courier-status"><span class="status-dot"></span><strong><?= $onRoute ? 'En camino' : ($courierHasOrder ? 'Pedido recogido' : 'Asignando repartidor') ?></strong><small><?= $onRoute ? 'Tu pedido va hacia ti' : ($courierHasOrder ? 'Pronto iniciará el recorrido' : 'Te avisaremos cuando esté listo') ?></small></div>
  </div>
  <div class="order-tracking"><h2>Rastreo del pedido</h2><div class="tracking-line"><div class="tracking-step is-complete"><span>&#10003;</span><strong>Pedido realizado</strong><small>Confirmado</small></div><div class="tracking-step <?= $isPrepared ? 'is-complete' : 'is-current' ?>"><span><?= $isPrepared ? '&#10003;' : '2' ?></span><strong>En preparación</strong><small><?= $isPrepared ? 'El negocio preparó tu pedido' : 'El negocio está preparando tu pedido' ?></small></div><div class="tracking-step <?= ($order['fecha_confirmacion'] || $courierHasOrder) ? 'is-complete' : ($isPrepared ? 'is-current' : '') ?>"><span><?= ($order['fecha_confirmacion'] || $courierHasOrder) ? '&#10003;' : '3' ?></span><strong><?= $courierHasOrder ? 'Recogido por repartidor' : 'Listo para recoger' ?></strong><small><?= $courierHasOrder ? 'El repartidor ya recibió tu pedido' : ($isPrepared ? 'Esperando al repartidor' : 'Pendiente de preparación') ?></small></div><div class="tracking-step <?= $order['fecha_confirmacion'] ? 'is-complete' : ($onRoute ? 'is-current' : '') ?>"><span><?= $order['fecha_confirmacion'] ? '&#10003;' : '4' ?></span><strong>En camino</strong><small><?= $order['fecha_confirmacion'] ? 'Pedido recibido' : ($onRoute ? 'Tu pedido va hacia la dirección de entrega' : 'Pendiente de salida') ?></small></div></div></div>
</section>
<?php if (!$order['fecha_confirmacion']): ?>
  <?php if ($courierHasOrder && !$onRoute): ?><p class="order-success">El repartidor ya recogió tu pedido. Pronto iniciará el recorrido hacia tu dirección.</p><?php elseif ($onRoute): ?><p class="order-success">Tu pedido está en camino. Comparte el código al llegar para confirmar la entrega.</p><?php endif; ?>
  <div class="delivery-code-box" style="background:#f0fdf4;border:2px dashed #22c55e;border-radius:12px;padding:16px;text-align:center;margin:20px 0">
    <div style="font-size:14px;color:#15803d;font-weight:700">🔑 Tu Código de Entrega:</div>
    <div style="font-size:28px;font-weight:900;letter-spacing:4px;color:#166534;margin:6px 0"><?= htmlspecialchars((string)$order['codigo_entrega']) ?></div>
    <div style="font-size:12px;color:#4b5563">Entrégaselo al repartidor cuando llegue con tu pedido para confirmar la entrega.</div>
  </div>
<?php else: ?>
  <p class="order-success">&#9989; Pedido entregado. ¡Gracias por elegir Shizen!</p>
<?php endif; ?>
<?php if ($order['fecha_confirmacion'] && !$courierRated): ?><form class="form" method="post" action="php/calificar_repartidor.php"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)($_SESSION['csrf_token'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="id" value="<?= (int)$order['id_pedido'] ?>"><h2>Califica al repartidor</h2><fieldset class="rating-stars" aria-label="Selecciona una calificación de 1 a 5 estrellas"><?php for($i=1;$i<=5;$i++): ?><label class="rating-star"><input type="radio" name="puntuacion" value="<?= $i ?>" aria-label="<?= $i ?> estrellas" required><script>(function(c){if(!c||c.dataset.validacionCampo)return;c.dataset.validacionCampo='1';var e=c.nextElementSibling&&c.nextElementSibling.classList.contains('field-error-inline')?c.nextElementSibling:document.createElement('small');e.className='field-error-inline';e.style.cssText='display:block;color:#c62828;font-size:12px;margin-top:4px;min-height:1em;';if(!e.parentNode)c.insertAdjacentElement('afterend',e);function v(){var x=c.required&&!c.value.trim(),i=x||!c.validity.valid;e.textContent=i?(x?'Este campo es obligatorio.':c.validationMessage):'';c.setAttribute('aria-invalid',i?'true':'false');return!i}['input','change','blur'].forEach(function(t){c.addEventListener(t,v)});if(c.form)c.form.addEventListener('submit',function(a){if(!v())a.preventDefault()})})(document.currentScript.previousElementSibling);</script><span aria-hidden="true"></span></label><?php endfor; ?></fieldset><textarea class="input" name="comentario" placeholder="Comentario opcional" rows="3"></textarea><script>(function(c){if(!c||c.dataset.validacionCampo)return;c.dataset.validacionCampo='1';var e=c.nextElementSibling&&c.nextElementSibling.classList.contains('field-error-inline')?c.nextElementSibling:document.createElement('small');e.className='field-error-inline';e.style.cssText='display:block;color:#c62828;font-size:12px;margin-top:4px;min-height:1em;';if(!e.parentNode)c.insertAdjacentElement('afterend',e);function v(){var x=c.required&&!c.value.trim(),i=x||!c.validity.valid;e.textContent=i?(x?'Este campo es obligatorio.':c.validationMessage):'';c.setAttribute('aria-invalid',i?'true':'false');return!i}['input','change','blur'].forEach(function(t){c.addEventListener(t,v)});if(c.form)c.form.addEventListener('submit',function(a){if(!v())a.preventDefault()})})(document.currentScript.previousElementSibling);</script><button class="button" type="submit">Enviar calificación</button></form><?php elseif ($order['fecha_confirmacion']): ?><p class="order-success">Ya calificaste al repartidor.</p><?php endif; ?>
<?php if ($order['fecha_confirmacion'] && !$businessRated): ?><form class="form" method="post" action="php/calificar_negocio.php"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)($_SESSION['csrf_token'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="id" value="<?= (int)$order['id_pedido'] ?>"><h2>Califica al negocio</h2><fieldset class="rating-stars" aria-label="Selecciona una calificación de 1 a 5 estrellas"><?php for($i=1;$i<=5;$i++): ?><label class="rating-star"><input type="radio" name="puntuacion" value="<?= $i ?>" aria-label="<?= $i ?> estrellas" required><script>(function(c){if(!c||c.dataset.validacionCampo)return;c.dataset.validacionCampo='1';var e=c.nextElementSibling&&c.nextElementSibling.classList.contains('field-error-inline')?c.nextElementSibling:document.createElement('small');e.className='field-error-inline';e.style.cssText='display:block;color:#c62828;font-size:12px;margin-top:4px;min-height:1em;';if(!e.parentNode)c.insertAdjacentElement('afterend',e);function v(){var x=c.required&&!c.value.trim(),i=x||!c.validity.valid;e.textContent=i?(x?'Este campo es obligatorio.':c.validationMessage):'';c.setAttribute('aria-invalid',i?'true':'false');return!i}['input','change','blur'].forEach(function(t){c.addEventListener(t,v)});if(c.form)c.form.addEventListener('submit',function(a){if(!v())a.preventDefault()})})(document.currentScript.previousElementSibling);</script><span aria-hidden="true"></span></label><?php endfor; ?></fieldset><textarea class="input" name="comentario" placeholder="Comentario opcional" rows="3"></textarea><script>(function(c){if(!c||c.dataset.validacionCampo)return;c.dataset.validacionCampo='1';var e=c.nextElementSibling&&c.nextElementSibling.classList.contains('field-error-inline')?c.nextElementSibling:document.createElement('small');e.className='field-error-inline';e.style.cssText='display:block;color:#c62828;font-size:12px;margin-top:4px;min-height:1em;';if(!e.parentNode)c.insertAdjacentElement('afterend',e);function v(){var x=c.required&&!c.value.trim(),i=x||!c.validity.valid;e.textContent=i?(x?'Este campo es obligatorio.':c.validationMessage):'';c.setAttribute('aria-invalid',i?'true':'false');return!i}['input','change','blur'].forEach(function(t){c.addEventListener(t,v)});if(c.form)c.form.addEventListener('submit',function(a){if(!v())a.preventDefault()})})(document.currentScript.previousElementSibling);</script><button class="button" type="submit">Enviar calificación</button></form><?php elseif ($order['fecha_confirmacion']): ?><p class="order-success">Ya calificaste al negocio.</p><?php endif; ?>
</section></main><div id="overlays"><?php include __DIR__ . '/../forms/modales.php'; ?></div><script src="js/app.js?v=20260930-cart-clear-1"></script></body></html>
