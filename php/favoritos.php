<?php
declare(strict_types=1);
require_once __DIR__ . '/../BD/conexion.php';
require_once __DIR__ . '/../funciones/funciones.php';
if (session_status() === PHP_SESSION_NONE) { session_name('SHIZEN_CLIENTE_SESSION'); session_start(); }
if (empty($_SESSION['id_usuario'])) { header('Location: ../index.php?login_redirect=php%2Ffavoritos.php#login'); exit; }
$pdo = obtenerConexion();
$userId = (int)$_SESSION['id_usuario'];
$stmtBiz = $pdo->prepare('SELECT n.id_negocio, n.nombre, n.logo_url, COALESCE(AVG(c.puntuacion), 0) rating FROM favorito f JOIN negocios n ON n.id_negocio = f.id_negocio LEFT JOIN calificacion c ON c.id_negocio = n.id_negocio WHERE f.id_usuario = ? AND f.id_menu_item IS NULL GROUP BY n.id_negocio, n.nombre, n.logo_url ORDER BY n.nombre');
$stmtBiz->execute([$userId]);
$businesses = $stmtBiz->fetchAll();
foreach ($businesses as &$b) $b['logo_url'] = resolverImagenUrl($b['logo_url'] ?? '');
unset($b);

$stmtDishes = $pdo->prepare('SELECT m.id_menu_item, m.nombre AS plato_nombre, m.precio, m.imagen_url, m.id_categoria, n.id_negocio, n.nombre AS negocio_nombre FROM favorito f JOIN menu_items m ON m.id_menu_item = f.id_menu_item LEFT JOIN negocios n ON n.id_negocio = m.id_negocio WHERE f.id_usuario = ? AND f.id_menu_item IS NOT NULL ORDER BY m.nombre');
$stmtDishes->execute([$userId]);
$dishes = $stmtDishes->fetchAll();
foreach ($dishes as &$d) $d['imagen_url'] = resolverImagenUrl($d['imagen_url'] ?? '');
unset($d);

$csrfToken = htmlspecialchars((string)($_SESSION['csrf_token'] ?? ''), ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <base href="../">
  <title>Favoritos | Shizen</title>
  <link rel="stylesheet" href="css/styles.css">
  <link rel="stylesheet" href="css/nav.css?v=20260930-ingreso-icon-1">
  <link rel="stylesheet" href="css/modals.css?v=20260930-cart-clear-1">
  <link rel="stylesheet" href="css/favorites.css">
</head>
<body>
<?php include __DIR__ . '/../forms/navegacion.php'; ?>
<main class="favorites-page">
  <?php $volverHref = 'index.php'; $volverLabel = 'Volver al inicio'; include __DIR__ . '/../forms/boton_volver.php'; ?>
  <h1>Mis favoritos</h1>
  <p class="favorites-subtitle">Tus negocios y platos favoritos, siempre a mano.</p>

  <h2 style="font-size:1.2rem; font-weight:700; color:#1b3a1d; margin:24px 0 14px;">Restaurantes Favoritos</h2>
  <div class="favorites-grid">
    <?php foreach ($businesses as $business): ?>
      <div class="favorite-card-wrap" style="position:relative;">
        <a class="favorite-card" href="php/negocio.php?id=<?= (int)$business['id_negocio'] ?>">
          <img src="<?= htmlspecialchars($business['logo_url'] ?: '../assets/Imagenes_prueba/plato1.jpg') ?>" alt="Logo de <?= htmlspecialchars($business['nombre']) ?>">
          <span>
            <strong><?= htmlspecialchars($business['nombre']) ?></strong>
            <span>&#9733; <?= number_format((float)$business['rating'], 1) ?></span>
          </span>
        </a>
        <form method="post" action="php/favorito.php" style="position:absolute; top:12px; right:12px;">
          <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
          <input type="hidden" name="id_negocio" value="<?= (int)$business['id_negocio'] ?>">
          <input type="hidden" name="redirect" value="favoritos.php">
          <button class="favorite-button is-favorite" type="submit" aria-label="Quitar de favoritos" title="Quitar de favoritos" style="background:rgba(255,255,255,0.9); border:none; border-radius:50%; width:32px; height:32px; color:#e53935; font-size:16px; cursor:pointer; box-shadow:0 2px 8px rgba(0,0,0,0.15);"><svg class="heart-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M16.5 3C19.538 3 22 5.5 22 9c0 7-7.5 11-10 12.5C9.5 20 2 16 2 9c0-3.5 2.5-6 5.5-6C9.36 3 11 4 12 5c1-1 2.64-2 4.5-2zm-3.566 15.604c.881-.556 1.676-1.109 2.42-1.701C18.335 14.533 20 11.943 20 9c0-2.36-1.537-4-3.5-4-1.076 0-2.24.57-3.086 1.414L12 7.828l-1.414-1.414C9.74 5.57 8.576 5 7.5 5 5.56 5 4 6.656 4 9c0 2.944 1.666 5.533 4.645 7.903.745.592 1.54 1.145 2.421 1.7.299.189.595.37.934.572.339-.202.635-.383.934-.571z"/></svg></button>
        </form>
      </div>
    <?php endforeach; ?>
    <?php if (!$businesses): ?>
      <p class="favorites-empty">Aún no tienes restaurantes favoritos.</p>
    <?php endif; ?>
  </div>

  <?php if ($dishes): ?>
    <h2 style="font-size:1.2rem; font-weight:700; color:#1b3a1d; margin:36px 0 14px;">Platos Favoritos</h2>
    <div class="favorites-grid">
      <?php foreach ($dishes as $dish): ?>
        <div class="favorite-card-wrap" style="position:relative;">
          <a class="favorite-card" href="php/categorias.php?categoria=<?= (int)($dish['id_categoria'] ?? 1) ?>">
            <img src="<?= htmlspecialchars($dish['imagen_url'] ?: '../assets/Imagenes_prueba/plato1.jpg') ?>" alt="Imagen de <?= htmlspecialchars($dish['plato_nombre']) ?>">
            <span>
              <strong><?= htmlspecialchars($dish['plato_nombre']) ?></strong>
              <small style="color:#666; font-size:0.85rem; font-weight:normal; display:block; margin-top:2px;"><?= htmlspecialchars($dish['negocio_nombre'] ?: 'Shizen') ?></small>
              <span style="color:#3a8c3f; font-weight:700; margin-top:4px;">$<?= number_format((float)$dish['precio'], 0, ',', '.') ?></span>
            </span>
          </a>
          <form method="post" action="php/favorito.php" style="position:absolute; top:12px; right:12px;">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="id_menu_item" value="<?= (int)$dish['id_menu_item'] ?>">
            <input type="hidden" name="id_negocio" value="<?= (int)$dish['id_negocio'] ?>">
            <input type="hidden" name="redirect" value="favoritos.php">
            <button class="favorite-button is-favorite" type="submit" aria-label="Quitar de favoritos" title="Quitar de favoritos" style="background:rgba(255,255,255,0.9); border:none; border-radius:50%; width:32px; height:32px; color:#e53935; font-size:16px; cursor:pointer; box-shadow:0 2px 8px rgba(0,0,0,0.15);"><svg class="heart-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M16.5 3C19.538 3 22 5.5 22 9c0 7-7.5 11-10 12.5C9.5 20 2 16 2 9c0-3.5 2.5-6 5.5-6C9.36 3 11 4 12 5c1-1 2.64-2 4.5-2zm-3.566 15.604c.881-.556 1.676-1.109 2.42-1.701C18.335 14.533 20 11.943 20 9c0-2.36-1.537-4-3.5-4-1.076 0-2.24.57-3.086 1.414L12 7.828l-1.414-1.414C9.74 5.57 8.576 5 7.5 5 5.56 5 4 6.656 4 9c0 2.944 1.666 5.533 4.645 7.903.745.592 1.54 1.145 2.421 1.7.299.189.595.37.934.572.339-.202.635-.383.934-.571z"/></svg></button>
          </form>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</main>
<div id="overlays"><?php include __DIR__ . '/../forms/modales.php'; ?></div>
<script src="js/app.js?v=20260930-cart-clear-1"></script>
</body>
</html>
