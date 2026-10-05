<?php
/**
 * tarjeta_plato.php — Componente reutilizable para tarjetas de platos en categorias y promociones.
 *
 * Variables esperadas (vía array $tarjeta o variables individuales):
 *   $cardId             (int)    ID del plato / menú item
 *   $cardNombre         (string) Nombre del plato o promoción
 *   $cardDesc           (string) Descripción corta (opcional)
 *   $cardImgUrl         (string) URL de la imagen (resuelta)
 *   $cardNegocioNombre  (string) Nombre del restaurante/negocio
 *   $cardNegocioId      (int)    ID del negocio
 *   $cardPrecio         (float)  Precio base u original
 *   $cardPrecioPromo    (float)  Precio con descuento (opcional)
 *   $cardHasPromo       (bool)   Indica si está en promoción
 *   $cardDescuentoPct   (int)    Porcentaje de descuento (opcional, calculado si no se pasa)
 *   $cardTag            (string) Etiqueta superior derecha (ej. categoría: "Vegano", "Bowls")
 *   $cardRating         (string) Calificación (ej. "4.8" o "Nuevo")
 *   $cardMetaExtra      (string) Información extra en meta (ej. "⏱️ Vence: 12/10/2026")
 *   $cardShowFavorite   (bool)   Mostrar botón de favoritos (default: false)
 *   $cardIsFavorite     (bool)   Si el usuario ya lo tiene en favoritos
 *   $cardCsrfToken      (string) Token CSRF para formulario de favoritos
 *   $cardRedirectUrl    (string) URL de redirección tras favorito / login
 *   $cardDataCat        (string) Atributo data-cat para filtrado por pestañas
 *   $cardExtraClass     (string) Clases CSS adicionales para el contenedor
 *   $cardButtonText     (string) Texto del botón (default: "Añadir al carrito")
 *
 * Uso rápido:
 *   <?php $tarjeta = [ ... ]; include __DIR__ . '/tarjeta_plato.php'; ?>
 */

if (!empty($tarjeta) && is_array($tarjeta)) {
    $cardId            = $tarjeta['id'] ?? $cardId ?? 0;
    $cardNombre        = $tarjeta['nombre'] ?? $cardNombre ?? '';
    $cardDesc          = $tarjeta['descripcion'] ?? $cardDesc ?? '';
    $cardImgUrl        = $tarjeta['imagen_url'] ?? $cardImgUrl ?? '';
    $cardNegocioNombre = $tarjeta['negocio_nombre'] ?? $cardNegocioNombre ?? '';
    $cardNegocioId     = $tarjeta['negocio_id'] ?? $cardNegocioId ?? 0;
    $cardPrecio        = $tarjeta['precio'] ?? $cardPrecio ?? 0.0;
    $cardPrecioPromo   = $tarjeta['precio_promocion'] ?? $cardPrecioPromo ?? null;
    $cardHasPromo      = $tarjeta['has_promo'] ?? $cardHasPromo ?? false;
    $cardDescuentoPct  = $tarjeta['descuento_pct'] ?? $cardDescuentoPct ?? null;
    $cardTag           = $tarjeta['tag'] ?? $cardTag ?? null;
    $cardRating        = $tarjeta['rating'] ?? $cardRating ?? null;
    $cardMetaExtra     = $tarjeta['meta_extra'] ?? $cardMetaExtra ?? null;
    $cardShowFavorite  = $tarjeta['show_favorite'] ?? $cardShowFavorite ?? false;
    $cardIsFavorite    = $tarjeta['is_favorite'] ?? $cardIsFavorite ?? false;
    $cardCsrfToken     = $tarjeta['csrf_token'] ?? $cardCsrfToken ?? '';
    $cardRedirectUrl   = $tarjeta['redirect_url'] ?? $cardRedirectUrl ?? '';
    $cardDataCat       = $tarjeta['data_cat'] ?? $cardDataCat ?? null;
    $cardExtraClass    = $tarjeta['extra_class'] ?? $cardExtraClass ?? '';
    $cardButtonText    = $tarjeta['button_text'] ?? $cardButtonText ?? 'Añadir al carrito';
}

$_c_id           = (int)($cardId ?? 0);
$_c_nombre       = (string)($cardNombre ?? '');
$_c_desc         = (string)($cardDesc ?? '');
$_c_img          = (string)($cardImgUrl ?? '');
$_c_negocio      = (string)($cardNegocioNombre ?? '');
$_c_negocio_id   = (int)($cardNegocioId ?? 0);
$_c_precio_orig  = (float)($cardPrecio ?? 0);
$_c_has_promo    = !empty($cardHasPromo) && !empty($cardPrecioPromo) && ((float)$cardPrecioPromo < $_c_precio_orig || $_c_precio_orig <= 0);
$_c_precio_final = $_c_has_promo ? (float)$cardPrecioPromo : $_c_precio_orig;

if ($_c_has_promo && empty($cardDescuentoPct) && $_c_precio_orig > 0 && $_c_precio_final < $_c_precio_orig) {
    $_c_desc_pct = (int)round((($_c_precio_orig - $_c_precio_final) / $_c_precio_orig) * 100);
} else {
    $_c_desc_pct = !empty($cardDescuentoPct) ? (int)$cardDescuentoPct : 0;
}

$_c_tag          = !empty($cardTag) ? (string)$cardTag : '';
$_c_rating       = !empty($cardRating) ? (string)$cardRating : '';
$_c_meta_extra   = !empty($cardMetaExtra) ? (string)$cardMetaExtra : '';
$_c_show_fav     = !empty($cardShowFavorite);
$_c_is_fav       = !empty($cardIsFavorite);
$_c_csrf         = (string)($cardCsrfToken ?? '');
$_c_redirect     = (string)($cardRedirectUrl ?? '');
$_c_data_cat     = isset($cardDataCat) && $cardDataCat !== '' ? ' data-cat="' . htmlspecialchars((string)$cardDataCat, ENT_QUOTES, 'UTF-8') . '"' : '';
$_c_extra_class  = !empty($cardExtraClass) ? ' ' . htmlspecialchars((string)$cardExtraClass, ENT_QUOTES, 'UTF-8') : '';
$_c_btn_text     = htmlspecialchars($cardButtonText ?? 'Añadir al carrito', ENT_QUOTES, 'UTF-8');
?>
<article class="dish-card<?= $_c_extra_class ?>"<?= $_c_data_cat ?>>
  <div class="dish-img" style="background-image: url('<?= htmlspecialchars($_c_img, ENT_QUOTES, 'UTF-8') ?>')">
    <?php if ($_c_has_promo): ?>
      <span class="dish-promo-badge">
        <?= $_c_desc_pct > 0 ? '-' . $_c_desc_pct . '%' : '🔥 PROMOCIÓN' ?>
      </span>
    <?php endif; ?>

    <?php if ($_c_tag !== ''): ?>
      <span class="dish-tag-badge"><?= htmlspecialchars($_c_tag, ENT_QUOTES, 'UTF-8') ?></span>
    <?php endif; ?>

    <?php if ($_c_show_fav && $_c_id > 0): ?>
      <?php if (!empty($_SESSION['id_usuario'])): ?>
        <form method="post" action="php/favorito.php" class="dish-favorite-form">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_c_csrf, ENT_QUOTES, 'UTF-8') ?>">
          <input type="hidden" name="id_menu_item" value="<?= $_c_id ?>">
          <input type="hidden" name="id_negocio" value="<?= $_c_negocio_id ?>">
          <input type="hidden" name="redirect" value="<?= htmlspecialchars($_c_redirect, ENT_QUOTES, 'UTF-8') ?>">
          <button class="favorite-button dish-favorite-button <?= $_c_is_fav ? 'is-favorite' : '' ?>" type="submit" aria-label="<?= $_c_is_fav ? 'Quitar plato de favoritos' : 'Agregar plato a favoritos' ?>">
            <?php include __DIR__ . '/icono_corazon.php'; ?>
          </button>
        </form>
      <?php else: ?>
        <a class="favorite-button dish-favorite-button" href="#login" onclick="openAccessModal('<?= htmlspecialchars($_c_redirect, ENT_QUOTES, 'UTF-8') ?>'); return false;" aria-label="Inicia sesión para agregar el plato a favoritos" title="Inicia sesión para agregar el plato a favoritos">
          <?php include __DIR__ . '/icono_corazon.php'; ?>
        </a>
      <?php endif; ?>
    <?php endif; ?>
  </div>

  <div class="dish-body">
    <div class="dish-name"><?= htmlspecialchars($_c_nombre, ENT_QUOTES, 'UTF-8') ?></div>
    <?php if ($_c_negocio !== ''): ?>
      <div class="dish-restaurant">🏪 <?= htmlspecialchars($_c_negocio, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <?php if ($_c_desc !== ''): ?>
      <p class="dish-description"><?= htmlspecialchars($_c_desc, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
    <?php if ($_c_rating !== '' || $_c_meta_extra !== ''): ?>
      <div class="dish-meta">
        <?php if ($_c_rating !== ''): ?>
          <span>⭐ <?= htmlspecialchars($_c_rating, ENT_QUOTES, 'UTF-8') ?></span>
        <?php endif; ?>
        <?php if ($_c_meta_extra !== ''): ?>
          <span><?= htmlspecialchars($_c_meta_extra, ENT_QUOTES, 'UTF-8') ?></span>
        <?php endif; ?>
      </div>
    <?php endif; ?>
    <div class="dish-price-row">
      <div class="dish-prices">
        <?php if ($_c_has_promo): ?>
          <span class="dish-price" style="color:#ea580c">$<?= number_format($_c_precio_final, 0, ',', '.') ?></span>
          <span class="dish-orig">$<?= number_format($_c_precio_orig, 0, ',', '.') ?></span>
        <?php else: ?>
          <span class="dish-price">$<?= number_format($_c_precio_final, 0, ',', '.') ?></span>
        <?php endif; ?>
      </div>
      <button
        class="btn-add-cart"
        type="button"
        onclick='addToCart(<?= json_encode([
          "id" => $_c_id,
          "name" => $_c_nombre,
          "price" => $_c_precio_final,
          "restaurant" => $_c_negocio,
          "businessId" => $_c_negocio_id,
          "image" => $_c_img
        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'
      ><?= $_c_btn_text ?></button>
    </div>
  </div>
</article>
<?php
// Limpieza de variables para evitar fugas en iteraciones
unset(
    $_c_id, $_c_nombre, $_c_desc, $_c_img, $_c_negocio, $_c_negocio_id,
    $_c_precio_orig, $_c_has_promo, $_c_precio_final, $_c_desc_pct,
    $_c_tag, $_c_rating, $_c_meta_extra, $_c_show_fav, $_c_is_fav,
    $_c_csrf, $_c_redirect, $_c_data_cat, $_c_extra_class, $_c_btn_text,
    $tarjeta, $cardId, $cardNombre, $cardDesc, $cardImgUrl, $cardNegocioNombre,
    $cardNegocioId, $cardPrecio, $cardPrecioPromo, $cardHasPromo, $cardDescuentoPct,
    $cardTag, $cardRating, $cardMetaExtra, $cardShowFavorite, $cardIsFavorite,
    $cardCsrfToken, $cardRedirectUrl, $cardDataCat, $cardExtraClass, $cardButtonText
);
?>
