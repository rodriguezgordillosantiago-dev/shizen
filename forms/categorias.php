<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($categoria['nombre'] ?? 'Categorías') ?> | Shizen</title>
  <base href="../">
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="css/styles.css" />
  <link rel="stylesheet" href="css/nav.css?v=20260930-ingreso-icon-1" />
  <link rel="stylesheet" href="css/home.css?v=20260927-3" />
  <link rel="stylesheet" href="css/pages.css" />
  <link rel="stylesheet" href="css/modals.css?v=20260930-cart-clear-1" />
  <link rel="stylesheet" href="css/business-menu.css?v=20260927-1" />
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
  <header id="navigation">
    <?php include __DIR__ . '/navegacion.php'; ?>
  </header>
  <?php
    require_once __DIR__ . '/../BD/conexion.php';
    if (empty($_SESSION['csrf_token'])) {
      $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    $pdo = obtenerConexion();
    $favoriteItemIds = [];
    if (!empty($_SESSION['id_usuario'])) {
      $favoriteStatement = $pdo->prepare('SELECT id_menu_item FROM favorito WHERE id_usuario = ? AND id_menu_item IS NOT NULL');
      $favoriteStatement->execute([(int) $_SESSION['id_usuario']]);
      $favoriteItemIds = array_map('intval', $favoriteStatement->fetchAll(PDO::FETCH_COLUMN));
    }
  ?>

  <main id="app-content">
    <section class="cat-page">
      <?php
        $defaultCovers = [
          1 => '../assets/Imagenes_prueba/plato1.jpg',
          2 => '../assets/Imagenes_prueba/plato1.jpg',
          3 => '../assets/Imagenes_prueba/plato1.jpg',
          4 => '../assets/Imagenes_prueba/plato1.jpg',
          5 => '../assets/Imagenes_prueba/plato1.jpg',
          6 => '../assets/Imagenes_prueba/plato1.jpg',
          7 => '../assets/Imagenes_prueba/plato1.jpg',
        ];

        $catId = (int)($categoria['id'] ?? 1);
        $rawCover = trim($categoria['cover_img'] ?? '');
        $coverImg = '';

        if (!empty($rawCover)) {
          if (preg_match('#^https?://#i', $rawCover)) {
            $coverImg = $rawCover;
          } else {
            $clean = preg_replace('#^\.\.?/#', '', $rawCover);
            if (file_exists(__DIR__ . '/../' . $clean)) {
              $coverImg = $clean;
            } elseif (file_exists(__DIR__ . '/../../shizen_movil/' . $clean)) {
              $coverImg = '../shizen_movil/' . $clean;
            }
          }
        }

        if (empty($coverImg)) {
          $coverImg = $defaultCovers[$catId] ?? $defaultCovers[1];
        }

        $coverImg  = htmlspecialchars($coverImg);
        $coverImg  = htmlspecialchars($coverImg);
        $catIcon   = !empty($categoria['icon'])         ? htmlspecialchars($categoria['icon'])         : '🌱';
        $catName   = !empty($categoria['nombre'])       ? htmlspecialchars($categoria['nombre'])       : 'Categoría';
        $catDesc   = !empty($categoria['descripcion'])  ? htmlspecialchars($categoria['descripcion'])  : '';
      ?>
      <div class="cat-hero" id="catHero"
           style="background-image:url('<?= $coverImg ?>')">
        <div class="cat-hero-overlay"></div>
        <?php $volverHref = 'index.php'; $volverClass = 'cat-hero-back'; include __DIR__ . '/boton_volver.php'; ?>
        <div class="cat-hero-info">
          <div class="cat-hero-icon-name">
            <span class="cat-hero-icon" id="catIcon"><?= $catIcon ?></span>
            <span class="cat-hero-name" id="catName"><?= $catName ?></span>
          </div>
          <p class="cat-hero-desc" id="catDesc"><?= $catDesc ?></p>
        </div>
      </div>

      <div class="cat-filter-bar">
        <span>Ordenar por:</span>
        <select class="sort-select" id="sortSelect" onchange="location.href='php/categorias.php?categoria=<?= (int)$catId ?>&orden=' + encodeURIComponent(this.value)">
          <option value="relevance" <?= ($orden ?? '') === 'relevance' ? 'selected' : '' ?>>Relevancia</option>
          <option value="low" <?= ($orden ?? '') === 'low' ? 'selected' : '' ?>>Precio: menor</option>
          <option value="high" <?= ($orden ?? '') === 'high' ? 'selected' : '' ?>>Precio: mayor</option>
        </select><script>(function(c){if(!c||c.dataset.validacionCampo)return;c.dataset.validacionCampo='1';var e=c.nextElementSibling&&c.nextElementSibling.classList.contains('field-error-inline')?c.nextElementSibling:document.createElement('small');e.className='field-error-inline';e.style.cssText='display:block;color:#c62828;font-size:12px;margin-top:4px;min-height:1em;';if(!e.parentNode)c.insertAdjacentElement('afterend',e);function v(){var x=c.required&&!c.value.trim(),i=x||!c.validity.valid;e.textContent=i?(x?'Este campo es obligatorio.':c.validationMessage):'';c.setAttribute('aria-invalid',i?'true':'false');return!i}['input','change','blur'].forEach(function(t){c.addEventListener(t,v)});if(c.form)c.form.addEventListener('submit',function(a){if(!v())a.preventDefault()})})(document.currentScript.previousElementSibling);</script>
      </div>
      <div class="cat-items-inner">
        <div class="items-grid" id="itemsGrid">

          <?php if (empty($platos)): ?>
            <p class="cat-empty" style="grid-column: 1 / -1; text-align: center; padding: 40px 20px; color: #666; font-size: 1.05rem;">No hay platos registrados en esta categoría.</p>
          <?php else: ?>
            <?php foreach ($platos as $plato): ?>
              <?php
                $dishId = (int)($plato['id'] ?? 0);
                $dishBusinessId = (int)($plato['negocio_id'] ?? 0);
                $dishIsFavorite = $dishId > 0 && in_array($dishId, $favoriteItemIds, true);
                $rawImg = trim($plato['imagen_url'] ?? '');
                $imgUrl = function_exists('resolverImagenUrl') ? resolverImagenUrl($rawImg) : ($rawImg ?: '../assets/Imagenes_prueba/plato1.jpg');
                $hasPromo = !empty($plato['on_promo']) && !empty($plato['precio_promocion']);
                $ratingNegocio = !empty($plato['negocio_calificacion']) ? number_format((float)$plato['negocio_calificacion'], 1) : null;

                $tarjeta = [
                  'id' => $dishId,
                  'nombre' => $plato['plato_nombre'],
                  'descripcion' => $plato['plato_desc'] ?? '',
                  'imagen_url' => $imgUrl,
                  'negocio_nombre' => $plato['negocio_nombre'] ?? '',
                  'negocio_id' => $dishBusinessId,
                  'precio' => (float)$plato['precio'],
                  'precio_promocion' => $hasPromo ? (float)$plato['precio_promocion'] : null,
                  'has_promo' => $hasPromo,
                  'rating' => $ratingNegocio ?: 'Nuevo',
                  'show_favorite' => true,
                  'is_favorite' => $dishIsFavorite,
                  'csrf_token' => (string)($_SESSION['csrf_token'] ?? ''),
                  'redirect_url' => 'categorias.php?categoria=' . (int)$catId,
                ];
                include __DIR__ . '/tarjeta_plato.php';
              ?>
            <?php endforeach; ?>
          <?php endif; ?>

        </div>
      </div>
    </section>
  </main>

  <!-- Modales reutilizables -->
  <div id="overlays">
    <?php include __DIR__ . '/modales.php'; ?>
  </div>

  <script>
    window.shizenLayoutReady = Promise.resolve();
  </script>
  <script src="js/app.js?v=20260930-cart-clear-1"></script>
</body>
</html>
