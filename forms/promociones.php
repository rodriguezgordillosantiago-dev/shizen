<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <base href="../" />
  <title>Promociones | Shizen</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="css/styles.css" />
  <link rel="stylesheet" href="css/nav.css?v=20260930-ingreso-icon-1" />
  <link rel="stylesheet" href="css/pages.css" />
  <link rel="stylesheet" href="css/modals.css?v=20260930-cart-clear-1" />
</head>
<body>

  <!-- Componente de navegación reutilizable -->
  <header id="navigation">
    <?php include __DIR__ . '/navegacion.php'; ?>
  </header>

  <main id="app-content">
    <section class="promos-page">

      <div class="page-header">
        <?php $volverHref = 'index.php'; include __DIR__ . '/boton_volver.php'; ?>
        <div>
          <h2>Promociones</h2>
          <p>Descuentos exclusivos en restaurantes veganos</p>
        </div>
      </div>

      <div class="promos-inner">
        <div class="filter-tabs" id="promoFilterTabs">
          <button type="button" class="filter-tab active" data-cat="all">Todas</button>
          <?php if (!empty($categorias)): ?>
            <?php foreach ($categorias as $cat): ?>
              <button type="button" class="filter-tab" data-cat="<?= (int)$cat['id_categoria'] ?>">
                <?= htmlspecialchars($cat['icon'] ?? '🌱') ?> <?= htmlspecialchars($cat['nombre']) ?>
              </button>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <div class="promos-grid" id="promosGrid">
          <?php if (empty($promociones)): ?>
            <p class="cart-empty" style="grid-column: 1 / -1; text-align: center; padding: 40px 20px; color: #666; font-size: 1.05rem;">
              No hay promociones activas en este momento. ¡Vuelve pronto para disfrutar de ofertas exclusivas!
            </p>
          <?php else: ?>
            <?php foreach ($promociones as $promo): ?>
              <?php
                $rawImg = trim($promo['imagen_url'] ?? '');
                $imgUrl = htmlspecialchars(function_exists('resolverImagenUrl') ? resolverImagenUrl($rawImg) : ($rawImg ?: 'assets/Imagenes_prueba/plato1.jpg'));
                $precioOriginal = (float)($promo['precio'] ?? 0);
                $precioPromo = !empty($promo['precio_promocion']) ? (float)$promo['precio_promocion'] : $precioOriginal;
                $descuentoPct = ($precioOriginal > 0 && $precioPromo < $precioOriginal) 
                    ? (int)round((($precioOriginal - $precioPromo) / $precioOriginal) * 100) 
                    : 0;
                $catId = (int)($promo['id_categoria'] ?? 0);
                $catNombre = !empty($promo['categoria_nombre']) ? $promo['categoria_nombre'] : 'Vegano';
              ?>
              <div class="promo-card" data-cat="<?= $catId ?>">
                <div class="promo-card-img" style="background-image: url('<?= $imgUrl ?>')">
                  <span class="promo-badge" style="background: linear-gradient(135deg, #ea580c, #f97316);">
                    <?= $descuentoPct > 0 ? $descuentoPct . '% OFF' : '🔥 OFERTA' ?>
                  </span>
                  <span class="promo-tag-top"><?= htmlspecialchars($catNombre) ?></span>
                </div>
                <div class="promo-card-body">
                  <h3><?= htmlspecialchars($promo['promo_nombre']) ?></h3>
                  <div class="promo-restaurant">🏬 <?= htmlspecialchars($promo['negocio_nombre']) ?></div>
                  <?php if (!empty($promo['promo_desc'])): ?>
                    <p class="promo-desc"><?= htmlspecialchars($promo['promo_desc']) ?></p>
                  <?php endif; ?>
                  <?php if (!empty($promo['fecha_fin'])): ?>
                    <div class="promo-timer">⏱️ Vence: <?= htmlspecialchars(date('d/m/Y', strtotime($promo['fecha_fin']))) ?></div>
                  <?php endif; ?>
                  <div class="promo-prices">
                    <span class="promo-price-new">$<?= number_format($precioPromo, 0, ',', '.') ?></span>
                    <?php if ($precioOriginal > $precioPromo): ?>
                      <span class="promo-price-old">$<?= number_format($precioOriginal, 0, ',', '.') ?></span>
                      <?php if ($descuentoPct > 0): ?>
                        <span class="promo-discount">-<?= $descuentoPct ?>%</span>
                      <?php endif; ?>
                    <?php endif; ?>
                  </div>
                  <button
                    class="btn-promo-cart"
                    type="button"
                    onclick='addToCart(<?= json_encode([
                      "id" => (int) ($promo["id_menu_item"] ?: $promo["id"]),
                      "name" => $promo["promo_nombre"],
                      "price" => $precioPromo,
                      "restaurant" => $promo["negocio_nombre"],
                      "businessId" => (int) ($promo["id_negocio"] ?? 0),
                      "image" => $imgUrl
                    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'
                  >Añadir al carrito 🛒</button>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

    </section>
  </main>

  <!-- Componente de modales reutilizable -->
  <div id="overlays">
    <?php include __DIR__ . '/modales.php'; ?>
  </div>

  <script>
    window.shizenLayoutReady = Promise.resolve();
    window.DB_PROMOS = <?= json_encode($promociones ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

    document.addEventListener('DOMContentLoaded', function () {
      var tabs = document.querySelectorAll('#promoFilterTabs .filter-tab');
      var cards = document.querySelectorAll('#promosGrid .promo-card');

      tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
          tabs.forEach(function (t) { t.classList.remove('active'); });
          tab.classList.add('active');

          var selectedCat = tab.getAttribute('data-cat');
          cards.forEach(function (card) {
            if (selectedCat === 'all' || card.getAttribute('data-cat') === selectedCat) {
              card.style.display = '';
            } else {
              card.style.display = 'none';
            }
          });
        });
      });
    });
  </script>
  <script src="js/app.js?v=20260930-cart-clear-1"></script>
</body>
</html>
