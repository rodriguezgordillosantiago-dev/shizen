<?php
/**
 * boton_volver.php — Botón "Volver" reutilizable para toda la interfaz de usuario.
 *
 * Variables opcionales antes de incluir este archivo:
 *   $volverHref  — URL destino (por defecto: javascript:history.back())
 *   $volverLabel — Texto para aria-label (por defecto: "Volver")
 *   $volverClass — Clases CSS adicionales (opcional)
 *
 * Uso:
 *   <?php include __DIR__ . '/boton_volver.php'; ?>
 *
 *   // O con destino explícito:
 *   <?php $volverHref = 'index.php'; include __DIR__ . '/boton_volver.php'; ?>
 */

$_vol_href  = htmlspecialchars($volverHref ?? 'javascript:history.back()', ENT_QUOTES, 'UTF-8');
$_vol_label = htmlspecialchars($volverLabel ?? 'Volver', ENT_QUOTES, 'UTF-8');
$_vol_class = !empty($volverClass) ? ' ' . htmlspecialchars($volverClass, ENT_QUOTES, 'UTF-8') : '';
$_vol_tag   = str_starts_with($_vol_href, 'javascript:') ? 'button' : 'a';
?>
<?php if ($_vol_tag === 'button'): ?>
<button class="vol<?= $_vol_class ?>" type="button" onclick="history.back()" aria-label="<?= $_vol_label ?>" title="<?= $_vol_label ?>">
<?php else: ?>
<a class="vol<?= $_vol_class ?>" href="<?= $_vol_href ?>" aria-label="<?= $_vol_label ?>" title="<?= $_vol_label ?>">
<?php endif; ?>
  <svg viewBox="0 0 32 32" width="38" height="38" fill="none" stroke-linecap="round"
       stroke-linejoin="round" aria-hidden="true">
    <g stroke="currentColor" stroke-width="4">
      <path d="m15 22-6-6 6-6"/><path d="M24 16H9"/>
    </g>
  </svg>
<?php if ($_vol_tag === 'button'): ?>
</button>
<?php else: ?>
</a>
<?php endif; ?>
<?php unset($_vol_href, $_vol_label, $_vol_class, $_vol_tag, $volverHref, $volverLabel, $volverClass); ?>
