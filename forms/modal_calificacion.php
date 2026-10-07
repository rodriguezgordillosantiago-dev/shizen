<?php
/**
 * Componente Modal de Calificación (Negocio y Repartidor)
 * Funciona con 100% HTML y CSS puro (inputs radio + SVG y selectores CSS :checked).
 */
$modalOrderId = (int)($order['id_pedido'] ?? 0);
if ($modalOrderId <= 0) {
    return;
}
$modalBizName = (string)($order['negocio_nombre'] ?? 'Negocio');
$modalBizLogo = (string)($businessLogo ?? 'assets/logo_negocio.png');
$modalBizInit = mb_strtoupper(mb_substr(trim($modalBizName) ?: 'S', 0, 1));

$modalRepName = (string)($courierName ?? 'Repartidor');
$modalRepFoto = (string)($courierPhoto ?? 'assets/logo-repartidor.png');
$modalRepInit = mb_strtoupper(mb_substr(trim($modalRepName) ?: 'R', 0, 1));
$modalCsrf    = htmlspecialchars((string)($_SESSION['csrf_token'] ?? ''), ENT_QUOTES, 'UTF-8');
$bothRated    = !empty($businessRated) && !empty($courierRated);
if ($bothRated): ?>
<div class="modal-overlay rating-modal-overlay" id="ratingModal" role="dialog" aria-modal="true" aria-labelledby="ratingModalTitle">
  <div class="modal rating-modal" style="text-align:center;padding:32px 24px;max-width:420px;">
    <a href="php/pedido.php?id=<?= $modalOrderId ?>#close" onclick="window.location.hash='close';return false;" class="close-btn" aria-label="Cerrar">✕</a>
    <div style="font-size:42px;margin-bottom:10px;">⭐</div>
    <h2 id="ratingModalTitle" style="font-size:20px;font-weight:800;color:#1b3a1d;margin-bottom:8px;">¡Calificación registrada!</h2>
    <p style="color:#6b7280;font-size:14px;line-height:1.5;margin-bottom:24px;">Ya calificaste el negocio y el repartidor de este pedido. ¡Muchas gracias por tu valoración!</p>
    <a href="php/pedido.php?id=<?= $modalOrderId ?>#close" onclick="window.location.hash='close';return false;" class="btn btn-primary" style="display:inline-block;text-decoration:none;padding:10px 28px;border-radius:999px;">Cerrar</a>
  </div>
</div>
<?php return; endif; ?>
<div class="modal-overlay rating-modal-overlay" id="ratingModal" role="dialog" aria-modal="true" aria-labelledby="ratingModalTitle">
  <form class="modal rating-modal" method="post" action="php/calificar_pedido.php">

    <!-- Hero -->
    <div class="modal-hero">
      <a href="php/pedido.php?id=<?= $modalOrderId ?>#close" onclick="window.location.hash='close';return false;" class="close-btn" aria-label="Cerrar">✕</a>

      <div class="avatars-wrap">
        <div class="avatar-group">
          <div class="avatar avatar-negocio">
            <?php if (!empty($modalBizLogo)): ?>
              <img src="<?= htmlspecialchars($modalBizLogo) ?>" alt="<?= htmlspecialchars($modalBizName) ?>" onerror="this.onerror=null;this.replaceWith(document.createTextNode('<?= $modalBizInit ?>'))">
            <?php else: ?>
              <?= $modalBizInit ?>
            <?php endif; ?>
          </div>
          <div class="avatar avatar-repartidor">
            <?php if (!empty($modalRepFoto)): ?>
              <img src="<?= htmlspecialchars($modalRepFoto) ?>" alt="<?= htmlspecialchars($modalRepName) ?>" onerror="this.onerror=null;this.replaceWith(document.createTextNode('<?= $modalRepInit ?>'))">
            <?php else: ?>
              <?= $modalRepInit ?>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <h2 id="ratingModalTitle">¿Cómo fue tu experiencia?</h2>
      <p>Califica al negocio y al repartidor<br>que hicieron posible tu pedido</p>
    </div>

    <!-- Body -->
    <div class="modal-body">

      <!-- Sección Negocio -->
      <?php if (empty($businessRated)): ?>
      <div class="section section-negocio">
        <div class="section-header">
          <div class="section-avatar section-avatar-negocio">
            <?php if (!empty($modalBizLogo)): ?>
              <img src="<?= htmlspecialchars($modalBizLogo) ?>" alt="<?= htmlspecialchars($modalBizName) ?>" onerror="this.onerror=null;this.replaceWith(document.createTextNode('<?= $modalBizInit ?>'))">
            <?php else: ?>
              <?= $modalBizInit ?>
            <?php endif; ?>
          </div>
          <div class="section-info">
            <div class="section-label">Negocio</div>
            <div class="section-name"><?= htmlspecialchars($modalBizName) ?></div>
          </div>
        </div>

        <div class="stars">
          <?php for ($i = 5; $i >= 1; $i--): ?>
            <input type="radio" id="neg-star-<?= $i ?>" name="puntuacion_negocio" value="<?= $i ?>" class="star-radio">
            <label for="neg-star-<?= $i ?>" class="star" title="<?= $i ?> estrella<?= $i > 1 ? 's' : '' ?>">
              <svg viewBox="0 0 24 24" stroke-width="1.5"><polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26"/></svg>
            </label>
          <?php endfor; ?>
        </div>
        <textarea name="comentario_negocio" rows="2" placeholder="Comentario sobre el negocio (opcional)"></textarea>
      </div>
      <?php endif; ?>

      <!-- Sección Repartidor -->
      <?php if (empty($courierRated)): ?>
      <div class="section section-repartidor">
        <div class="section-header">
          <div class="section-avatar section-avatar-repartidor">
            <?php if (!empty($modalRepFoto)): ?>
              <img src="<?= htmlspecialchars($modalRepFoto) ?>" alt="<?= htmlspecialchars($modalRepName) ?>" onerror="this.onerror=null;this.replaceWith(document.createTextNode('<?= $modalRepInit ?>'))">
            <?php else: ?>
              <?= $modalRepInit ?>
            <?php endif; ?>
          </div>
          <div class="section-info">
            <div class="section-label">Repartidor</div>
            <div class="section-name"><?= htmlspecialchars($modalRepName) ?></div>
          </div>
        </div>

        <div class="stars">
          <?php for ($i = 5; $i >= 1; $i--): ?>
            <input type="radio" id="rep-star-<?= $i ?>" name="puntuacion_repartidor" value="<?= $i ?>" class="star-radio">
            <label for="rep-star-<?= $i ?>" class="star" title="<?= $i ?> estrella<?= $i > 1 ? 's' : '' ?>">
              <svg viewBox="0 0 24 24" stroke-width="1.5"><polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26"/></svg>
            </label>
          <?php endfor; ?>
        </div>
        <textarea name="comentario_repartidor" rows="2" placeholder="Comentario sobre el repartidor (opcional)"></textarea>
      </div>
      <?php endif; ?>

      <!-- Campos ocultos requeridos -->
      <input type="hidden" name="id" value="<?= $modalOrderId ?>">
      <input type="hidden" name="csrf_token" value="<?= $modalCsrf ?>">

      <!-- Acciones -->
      <div class="modal-actions">
        <a href="php/pedido.php?id=<?= $modalOrderId ?>#close" onclick="window.location.hash='close';return false;" class="btn btn-secondary">Ahora no</a>
        <button type="submit" class="btn btn-primary">Enviar</button>
      </div>

    </div>
  </form>
</div>
