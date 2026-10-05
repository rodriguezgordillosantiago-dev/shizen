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
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
          <a href="php/promociones.php" class="filter-tab <?= $categoriaFiltro === 0 ? 'active' : '' ?>">Todas</a>
          <?php if (!empty($categorias)): ?>
            <?php foreach ($categorias as $cat): ?>
              <a
                href="php/promociones.php?categoria=<?= (int)$cat['id_categoria'] ?>"
                class="filter-tab <?= $categoriaFiltro === (int)$cat['id_categoria'] ? 'active' : '' ?>"
              >
                <?= htmlspecialchars($cat['icon'] ?? '🌱') ?> <?= htmlspecialchars($cat['nombre']) ?>
              </a>
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
                $imgUrl = function_exists('resolverImagenUrl') ? resolverImagenUrl($rawImg) : ($rawImg ?: 'assets/Imagenes_prueba/plato1.jpg');
                $precioOriginal = (float)($promo['precio'] ?? 0);
                $precioPromo = !empty($promo['precio_promocion']) ? (float)$promo['precio_promocion'] : $precioOriginal;
                $catId = (int)($promo['id_categoria'] ?? 0);
                $catNombre = !empty($promo['categoria_nombre']) ? $promo['categoria_nombre'] : 'Vegano';
                $metaExtra = !empty($promo['fecha_fin']) ? '⏱️ Vence: ' . date('d/m/Y', strtotime($promo['fecha_fin'])) : null;

                $tarjeta = [
                  'id' => (int)($promo['id_menu_item'] ?: $promo['id']),
                  'nombre' => $promo['promo_nombre'],
                  'descripcion' => $promo['promo_desc'] ?? '',
                  'imagen_url' => $imgUrl,
                  'negocio_nombre' => $promo['negocio_nombre'] ?? '',
                  'negocio_id' => (int)($promo['id_negocio'] ?? 0),
                  'precio' => $precioOriginal,
                  'precio_promocion' => $precioPromo,
                  'has_promo' => true,
                  'tag' => $catNombre,
                  'meta_extra' => $metaExtra,
                  'data_cat' => $catId,
                  'extra_class' => 'promo-card',
                  'button_text' => 'Añadir al carrito',
                ];
                include __DIR__ . '/tarjeta_plato.php';
              ?>
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
  </script>
  <script src="js/app.js?v=20260930-cart-clear-1"></script>
</body>
</html>
