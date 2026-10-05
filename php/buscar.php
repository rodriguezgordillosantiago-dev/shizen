<?php
declare(strict_types=1);

require_once __DIR__ . '/../BD/conexion.php';
require_once __DIR__ . '/../funciones/funciones.php';

if (session_status() === PHP_SESSION_NONE) {
  session_name('SHIZEN_CLIENTE_SESSION');
  session_start();
}

$term = trim((string) ($_GET['q'] ?? ''));
$like = '%' . $term . '%';
$pdo = obtenerConexion();

$categoryStmt = $pdo->prepare(
  'SELECT id_categoria, nombre, icon, descripcion
     FROM categorias
     WHERE nombre LIKE ?
     ORDER BY nombre'
);
$categoryStmt->execute([$like]);
$categories = $categoryStmt->fetchAll();

$businessStmt = $pdo->prepare(
  'SELECT n.id_negocio, n.nombre, n.logo_url, n.direccion,
            COALESCE(r.rating, 0) AS rating,
            COALESCE(o.order_count, 0) AS order_count
     FROM negocios n
     LEFT JOIN (
         SELECT id_negocio, AVG(puntuacion) AS rating
         FROM calificacion
         GROUP BY id_negocio
     ) r ON r.id_negocio = n.id_negocio
     LEFT JOIN (
         SELECT id_negocio, COUNT(*) AS order_count
         FROM pedido
         GROUP BY id_negocio
     ) o ON o.id_negocio = n.id_negocio
     WHERE n.nombre LIKE ? OR n.direccion LIKE ?
     GROUP BY n.id_negocio, n.nombre, n.logo_url, n.direccion, r.rating, o.order_count
     ORDER BY rating DESC, n.nombre'
);
$businessStmt->execute([$like, $like]);
$businesses = $businessStmt->fetchAll();
foreach ($businesses as &$business) {
  $business['logo_url_resolved'] = resolverImagenUrl($business['logo_url'] ?? '');
}
unset($business);

$dishStmt = $pdo->prepare(
  'SELECT m.id_menu_item, m.id_negocio, m.nombre, m.descripcion,
            m.precio, m.precio_promocion, m.on_promo, m.imagen_url,
            m.id_categoria, n.nombre AS negocio_nombre
     FROM menu_items m
     LEFT JOIN negocios n ON n.id_negocio = m.id_negocio
     WHERE m.nombre LIKE ? OR m.descripcion LIKE ?
     ORDER BY m.nombre'
);
$dishStmt->execute([$like, $like]);
$dishes = $dishStmt->fetchAll();
foreach ($dishes as &$dish) {
  $dish['imagen_url'] = resolverImagenUrl($dish['imagen_url'] ?? '');
}
unset($dish);
?>
<!doctype html>
<html lang="es">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <base href="../">
  <title>Buscar | Shizen</title>
  <link rel="stylesheet" href="css/styles.css">
  <link rel="stylesheet" href="css/nav.css?v=20260930-ingreso-icon-1">
  <link rel="stylesheet" href="css/home.css?v=20261001-search-cards-1">
  <link rel="stylesheet" href="css/pages.css?v=20261001-search-cards-1">
  <link rel="stylesheet" href="css/modals.css?v=20260930-cart-clear-1">
  <link rel="stylesheet" href="css/search.css?v=20261001-search-cards-1">
</head>

<body>
  <?php include __DIR__ . '/../forms/navegacion.php'; ?>
  <main class="search-page">
    <?php $volverClass = 'search-back';
    $volverLabel = 'Volver a la página anterior';
    include __DIR__ . '/../forms/boton_volver.php'; ?>
    <h1>Resultados de búsqueda</h1>
    <p class="search-page-sub">
      <?php if ($term !== ''): ?>
        Resultados para <strong>“<?= htmlspecialchars($term, ENT_QUOTES, 'UTF-8') ?>”</strong>
      <?php else: ?>
        Escribe algo para encontrar locales y platos.
      <?php endif; ?>
    </p>

    <?php if (!$categories && !$businesses && !$dishes): ?>
      <div class="search-empty">No encontramos resultados. Prueba con otra búsqueda.</div>
    <?php endif; ?>

    <?php if ($businesses): ?>
      <h2 class="search-section-title">Locales</h2>
      <div class="search-results search-results--businesses">
        <?php foreach ($businesses as $business): ?>
          <a class="business-card client-card search-business-card"
            href="php/negocio.php?id=<?= (int) $business['id_negocio'] ?>">
            <div class="card-texture" aria-hidden="true"></div>
            <div class="card-shine" aria-hidden="true"></div>
            <div class="card-content">
              <div class="card-avatar" <?php if (empty($business['logo_url'])): ?>
                  style="background:linear-gradient(145deg,#ff4500,#9b1c00);box-shadow:0 6px 18px #ff450066" <?php endif; ?>>
                <?php if (!empty($business['logo_url'])): ?>
                  <img src="<?= htmlspecialchars($business['logo_url_resolved'], ENT_QUOTES, 'UTF-8') ?>" alt="">
                <?php else: ?>
                  <?= htmlspecialchars(strtoupper(substr((string) $business['nombre'], 0, 1)), ENT_QUOTES, 'UTF-8') ?>
                <?php endif; ?>
              </div>
              <div class="card-info-row">
                <div class="card-info">
                  <h3 class="card-name"><?= htmlspecialchars($business['nombre'], ENT_QUOTES, 'UTF-8') ?></h3>
                  <div class="card-stat">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                      aria-hidden="true">
                      <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"></path>
                      <line x1="3" y1="6" x2="21" y2="6"></line>
                      <path d="M16 10a4 4 0 01-8 0"></path>
                    </svg>
                    <span><?= number_format((int) $business['order_count']) ?> pedidos</span>
                  </div>
                  <div class="card-stat">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                      aria-hidden="true">
                      <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"></path>
                      <circle cx="12" cy="10" r="3"></circle>
                    </svg>
                    <span><?= htmlspecialchars((string) ($business['direccion'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                  </div>
                </div>
                <div class="card-star" aria-label="Calificación <?= number_format((float) $business['rating'], 1) ?> de 5">
                  <svg viewBox="0 0 16 16" fill="#facc15" aria-hidden="true">
                    <path d="M8 1l1.8 3.6 4 .6-2.9 2.8.7 4L8 10l-3.6 2 .7-4L2.2 5.2l4-.6z"></path>
                  </svg>
                  <span><?= (float) $business['rating'] > 0 ? number_format((float) $business['rating'], 1) : 'Nuevo' ?></span>
                </div>
              </div>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if ($categories): ?>
      <h2 class="search-section-title">Categorías</h2>
      <div class="search-results">
        <?php foreach ($categories as $category): ?>
          <a class="search-result-card" href="php/categorias.php?categoria=<?= (int) $category['id_categoria'] ?>">
            <span class="search-result-icon"><?= htmlspecialchars($category['icon'] ?? '🍽', ENT_QUOTES, 'UTF-8') ?></span>
            <span>
              <strong><?= htmlspecialchars($category['nombre'], ENT_QUOTES, 'UTF-8') ?></strong>
              <small><?= htmlspecialchars($category['descripcion'] ?? '', ENT_QUOTES, 'UTF-8') ?></small>
            </span>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if ($dishes): ?>
      <h2 class="search-section-title">Platos</h2>
      <div class="search-results search-results--dishes">
        <?php foreach ($dishes as $dish): ?>
          <?php
          $hasPromo = !empty($dish['on_promo']) && !empty($dish['precio_promocion']);
          $displayPrice = $hasPromo ? (float) $dish['precio_promocion'] : (float) $dish['precio'];
          ?>
          <a class="dish-card search-dish-card" href="php/categorias.php?categoria=<?= (int) $dish['id_categoria'] ?>"
            aria-label="<?= htmlspecialchars($dish['nombre'], ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars($dish['negocio_nombre'] ?: 'Restaurante Shizen', ENT_QUOTES, 'UTF-8') ?>">
            <div class="dish-img"
              style="background-image:url('<?= htmlspecialchars($dish['imagen_url'], ENT_QUOTES, 'UTF-8') ?>')">
              <?php if ($hasPromo): ?>
                <span class="dish-promo-badge">Promoción</span>
              <?php endif; ?>
            </div>
            <div class="dish-body">
              <div class="dish-name"><?= htmlspecialchars($dish['nombre'], ENT_QUOTES, 'UTF-8') ?></div>
              <div class="dish-restaurant">
                <?= htmlspecialchars($dish['negocio_nombre'] ?: 'Restaurante Shizen', ENT_QUOTES, 'UTF-8') ?>
              </div>
              <p class="dish-description"><?= htmlspecialchars($dish['descripcion'] ?? '', ENT_QUOTES, 'UTF-8') ?>
              </p>
              <div class="dish-price-row">
                <div class="dish-prices">
                  <span class="dish-price">$<?= number_format($displayPrice, 0, ',', '.') ?></span>
                  <?php if ($hasPromo): ?>
                    <span class="dish-orig">$<?= number_format((float) $dish['precio'], 0, ',', '.') ?></span>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </main>
  <script src="js/app.js?v=20260930-cart-clear-1"></script>
</body>

</html>