<?php
declare(strict_types=1);
require_once __DIR__ . '/../BD/conexion.php';
require_once __DIR__ . '/../funciones/funciones.php';
if (session_status() === PHP_SESSION_NONE) { session_name('SHIZEN_CLIENTE_SESSION'); session_start(); }
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: (int)($_GET['id'] ?? 0);
$pdo = obtenerConexion();
$stmt = $pdo->prepare('SELECT n.id_negocio,n.nombre,n.logo_url,COALESCE(AVG(c.puntuacion),0) rating FROM negocios n LEFT JOIN calificacion c ON c.id_negocio=n.id_negocio WHERE n.id_negocio=? GROUP BY n.id_negocio,n.nombre,n.logo_url');
$stmt->execute([$id]); $business = $stmt->fetch();
if (!$business) { http_response_code(404); exit('Negocio no encontrado.'); }
$stmt = $pdo->prepare('SELECT m.*,c.nombre categoria_nombre FROM menu_items m LEFT JOIN categorias c ON c.id_categoria=m.id_categoria WHERE m.id_negocio=? ORDER BY m.nombre');
$stmt->execute([$id]); $dishes = $stmt->fetchAll();
foreach ($dishes as &$dish) $dish['imagen_url'] = resolverImagenUrl($dish['imagen_url'] ?? '');
unset($dish);
$business['logo_url'] = resolverImagenUrl($business['logo_url'] ?? '');
$isBusinessFavorite = false;
$favoriteItemIds = [];
if (!empty($_SESSION['id_usuario'])) {
    $favBiz = $pdo->prepare('SELECT 1 FROM favorito WHERE id_usuario = ? AND id_negocio = ? AND id_menu_item IS NULL');
    $favBiz->execute([(int)$_SESSION['id_usuario'], $id]);
    $isBusinessFavorite = (bool)$favBiz->fetchColumn();

    $favItems = $pdo->prepare('SELECT id_menu_item FROM favorito WHERE id_usuario = ? AND id_menu_item IS NOT NULL');
    $favItems->execute([(int)$_SESSION['id_usuario']]);
    $favoriteItemIds = array_map('intval', $favItems->fetchAll(PDO::FETCH_COLUMN));
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><base href="../"><title><?= htmlspecialchars($business['nombre']) ?> | Shizen</title><link rel="stylesheet" href="css/styles.css"><link rel="stylesheet" href="css/nav.css?v=20260930-ingreso-icon-1"><link rel="stylesheet" href="css/pages.css"><link rel="stylesheet" href="css/modals.css?v=20260930-cart-clear-1"><link rel="stylesheet" href="css/business-menu.css?v=20260923-1"></head><body>
<?php include __DIR__ . '/../forms/navegacion.php'; ?>
<main class="business-menu-page">
  <?php $volverHref = 'index.php'; $volverLabel = 'Volver a negocios'; include __DIR__ . '/../forms/boton_volver.php'; ?>
  <section class="business-menu-header">
    <img src="<?= htmlspecialchars($business['logo_url']) ?>" alt="Logo de <?= htmlspecialchars($business['nombre']) ?>">
    <div>
      <h1><?= htmlspecialchars($business['nombre']) ?></h1>
      <p class="business-rating">&#9733; <?= number_format((float)$business['rating'],1) ?> · Menú disponible</p>
    </div>
    <?php if (!empty($_SESSION['id_usuario'])): ?>
      <form method="post" action="php/favorito.php" class="favorite-form">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)($_SESSION['csrf_token'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="id_negocio" value="<?= (int)$id ?>">
        <button class="favorite-button <?= $isBusinessFavorite ? 'is-favorite' : '' ?>" type="submit" aria-label="<?= $isBusinessFavorite ? 'Quitar de favoritos' : 'Agregar a favoritos' ?>" title="<?= $isBusinessFavorite ? 'Quitar de favoritos' : 'Agregar a favoritos' ?>">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78L12 21.23l8.84-8.84a5.5 5.5 0 0 0 0-7.78Z"></path></svg>
        </button>
      </form>
    <?php else: ?>
      <a class="favorite-button" href="#login" onclick="openAccessModal('php/negocio.php?id=<?= (int)$id ?>'); return false;" aria-label="Inicia sesión para agregar a favoritos" title="Inicia sesión para agregar a favoritos">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78L12 21.23l8.84-8.84a5.5 5.5 0 0 0 0-7.78Z"></path></svg>
      </a>
    <?php endif; ?>
  </section>
  <div class="items-grid">
    <?php foreach($dishes as $dish): ?>
      <?php
        $dishId = (int)$dish['id_menu_item'];
        $dishIsFavorite = in_array($dishId, $favoriteItemIds, true);
      ?>
      <?php
        $hasPromo = !empty($dish['on_promo']) && !empty($dish['precio_promocion']);
        $precioReal = $hasPromo ? (float)$dish['precio_promocion'] : (float)$dish['precio'];
      ?>
      <article class="dish-card">
        <div class="dish-img" style="background-image:url('<?= htmlspecialchars($dish['imagen_url']) ?>')">
          <span class="dish-tag-badge">Vegano</span>
          <?php if ($hasPromo): ?>
            <span class="dish-promo-badge" style="position:absolute;top:10px;left:10px;background:#ea580c;color:#fff;font-weight:700;padding:4px 8px;border-radius:6px;font-size:11px;box-shadow:0 2px 6px rgba(234,88,12,.5)">🔥 PROMOCIÓN</span>
          <?php endif; ?>
          <?php if (!empty($_SESSION['id_usuario'])): ?>
            <form method="post" action="php/favorito.php" class="dish-favorite-form">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)($_SESSION['csrf_token'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
              <input type="hidden" name="id_menu_item" value="<?= $dishId ?>">
              <input type="hidden" name="id_negocio" value="<?= (int)$id ?>">
              <input type="hidden" name="redirect" value="negocio.php?id=<?= (int)$id ?>">
              <button class="favorite-button dish-favorite-button <?= $dishIsFavorite ? 'is-favorite' : '' ?>" type="submit" aria-label="<?= $dishIsFavorite ? 'Quitar plato de favoritos' : 'Agregar plato a favoritos' ?>"><svg class="heart-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M16.5 3C19.538 3 22 5.5 22 9c0 7-7.5 11-10 12.5C9.5 20 2 16 2 9c0-3.5 2.5-6 5.5-6C9.36 3 11 4 12 5c1-1 2.64-2 4.5-2zm-3.566 15.604c.881-.556 1.676-1.109 2.42-1.701C18.335 14.533 20 11.943 20 9c0-2.36-1.537-4-3.5-4-1.076 0-2.24.57-3.086 1.414L12 7.828l-1.414-1.414C9.74 5.57 8.576 5 7.5 5 5.56 5 4 6.656 4 9c0 2.944 1.666 5.533 4.645 7.903.745.592 1.54 1.145 2.421 1.7.299.189.595.37.934.572.339-.202.635-.383.934-.571z"/></svg></button>
            </form>
          <?php else: ?>
            <a class="favorite-button dish-favorite-button" href="#login" onclick="openAccessModal('php/negocio.php?id=<?= (int)$id ?>'); return false;" aria-label="Inicia sesión para agregar el plato a favoritos" title="Inicia sesión para agregar el plato a favoritos"><svg class="heart-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M16.5 3C19.538 3 22 5.5 22 9c0 7-7.5 11-10 12.5C9.5 20 2 16 2 9c0-3.5 2.5-6 5.5-6C9.36 3 11 4 12 5c1-1 2.64-2 4.5-2zm-3.566 15.604c.881-.556 1.676-1.109 2.42-1.701C18.335 14.533 20 11.943 20 9c0-2.36-1.537-4-3.5-4-1.076 0-2.24.57-3.086 1.414L12 7.828l-1.414-1.414C9.74 5.57 8.576 5 7.5 5 5.56 5 4 6.656 4 9c0 2.944 1.666 5.533 4.645 7.903.745.592 1.54 1.145 2.421 1.7.299.189.595.37.934.572.339-.202.635-.383.934-.571z"/></svg></a>
          <?php endif; ?>
        </div>
        <div class="dish-body">
          <div class="dish-name"><?= htmlspecialchars($dish['nombre']) ?></div>
          <div class="dish-restaurant"><?= htmlspecialchars($dish['categoria_nombre'] ?: 'Especialidad Shizen') ?></div>
          <p class="dish-description"><?= htmlspecialchars($dish['descripcion'] ?? '') ?></p>
          <div class="dish-price-row">
            <div class="dish-prices">
              <?php if ($hasPromo): ?>
                <span class="dish-price" style="color:#ea580c">$<?= number_format((float)$dish['precio_promocion'],0,',','.') ?></span>
                <span style="font-size:12px;color:#9ca3af;text-decoration:line-through;margin-left:6px">$<?= number_format((float)$dish['precio'],0,',','.') ?></span>
              <?php else: ?>
                <span class="dish-price">$<?= number_format((float)$dish['precio'],0,',','.') ?></span>
              <?php endif; ?>
            </div>
            <button class="btn-add-cart" type="button" onclick='addToCart(<?= json_encode(["id"=>(int)$dish["id_menu_item"],"name"=>$dish["nombre"],"price"=>$precioReal,"restaurant"=>$business["nombre"],"businessId"=>(int)$id,"image"=>$dish["imagen_url"]], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>)'>Añadir al carrito</button>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</main>
<div id="overlays"><?php include __DIR__ . '/../forms/modales.php'; ?></div>
<script src="js/app.js?v=20260930-cart-clear-1"></script></body></html>
