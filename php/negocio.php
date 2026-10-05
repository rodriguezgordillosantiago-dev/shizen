<?php
declare(strict_types=1);
require_once __DIR__ . '/../BD/conexion.php';
require_once __DIR__ . '/../funciones/funciones.php';
if (session_status() === PHP_SESSION_NONE) {
  session_name('SHIZEN_CLIENTE_SESSION');
  session_start();
}
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: (int) ($_GET['id'] ?? 0);
$pdo = obtenerConexion();
$stmt = $pdo->prepare('SELECT n.id_negocio,n.nombre,n.logo_url,COALESCE(AVG(c.puntuacion),0) rating FROM negocios n LEFT JOIN calificacion c ON c.id_negocio=n.id_negocio WHERE n.id_negocio=? GROUP BY n.id_negocio,n.nombre,n.logo_url');
$stmt->execute([$id]);
$business = $stmt->fetch();
if (!$business) {
  http_response_code(404);
  exit('Negocio no encontrado.');
}
$stmt = $pdo->prepare('SELECT m.*,c.nombre categoria_nombre FROM menu_items m LEFT JOIN categorias c ON c.id_categoria=m.id_categoria WHERE m.id_negocio=? ORDER BY m.nombre');
$stmt->execute([$id]);
$dishes = $stmt->fetchAll();
foreach ($dishes as &$dish)
  $dish['imagen_url'] = resolverImagenUrl($dish['imagen_url'] ?? '');
unset($dish);
$business['logo_url'] = resolverImagenUrl($business['logo_url'] ?? '');
$isBusinessFavorite = false;
$favoriteItemIds = [];
if (!empty($_SESSION['id_usuario'])) {
  $favBiz = $pdo->prepare('SELECT 1 FROM favorito WHERE id_usuario = ? AND id_negocio = ? AND id_menu_item IS NULL');
  $favBiz->execute([(int) $_SESSION['id_usuario'], $id]);
  $isBusinessFavorite = (bool) $favBiz->fetchColumn();

  $favItems = $pdo->prepare('SELECT id_menu_item FROM favorito WHERE id_usuario = ? AND id_menu_item IS NOT NULL');
  $favItems->execute([(int) $_SESSION['id_usuario']]);
  $favoriteItemIds = array_map('intval', $favItems->fetchAll(PDO::FETCH_COLUMN));
}
?>
<!doctype html>
<html lang="es">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <base href="../">
  <title><?= htmlspecialchars($business['nombre']) ?> | Shizen</title>
  <link rel="stylesheet" href="css/styles.css">
  <link rel="stylesheet" href="css/nav.css?v=20260930-ingreso-icon-1">
  <link rel="stylesheet" href="css/pages.css">
  <link rel="stylesheet" href="css/modals.css?v=20260930-cart-clear-1">
  <link rel="stylesheet" href="css/business-menu.css?v=20260923-1">
</head>

<body>
  <?php include __DIR__ . '/../forms/navegacion.php'; ?>
  <main class="business-menu-page">
    <?php $volverHref = 'index.php';
    $volverLabel = 'Volver a negocios';
    include __DIR__ . '/../forms/boton_volver.php'; ?>
    <section class="business-menu-header">
      <img src="<?= htmlspecialchars($business['logo_url']) ?>"
        alt="Logo de <?= htmlspecialchars($business['nombre']) ?>">
      <div>
        <h1><?= htmlspecialchars($business['nombre']) ?></h1>
        <p class="business-rating">&#9733; <?= number_format((float) $business['rating'], 1) ?> · Menú
          disponible
        </p>
      </div>
      <?php if (!empty($_SESSION['id_usuario'])): ?>
        <form method="post" action="php/favorito.php" class="favorite-form">
          <input type="hidden" name="csrf_token"
            value="<?= htmlspecialchars((string) ($_SESSION['csrf_token'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
          <input type="hidden" name="id_negocio" value="<?= (int) $id ?>">
          <button class="favorite-button <?= $isBusinessFavorite ? 'is-favorite' : '' ?>" type="submit"
            aria-label="<?= $isBusinessFavorite ? 'Quitar de favoritos' : 'Agregar a favoritos' ?>"
            title="<?= $isBusinessFavorite ? 'Quitar de favoritos' : 'Agregar a favoritos' ?>">
            <?php include __DIR__ . '/../forms/icono_corazon.php'; ?>
          </button>
        </form>
      <?php else: ?>
        <a class="favorite-button" href="#login"
          onclick="openAccessModal('php/negocio.php?id=<?= (int) $id ?>'); return false;"
          aria-label="Inicia sesión para agregar a favoritos" title="Inicia sesión para agregar a favoritos">
          <?php include __DIR__ . '/../forms/icono_corazon.php'; ?>
        </a>
      <?php endif; ?>
    </section>
    <div class="items-grid">
      <?php foreach ($dishes as $dish): ?>
        <?php
        $tarjeta = [
          'id' => (int) $dish['id_menu_item'],
          'nombre' => (string) $dish['nombre'],
          'descripcion' => (string) ($dish['descripcion'] ?? ''),
          'imagen_url' => (string) $dish['imagen_url'],
          'negocio_nombre' => (string) ($dish['categoria_nombre'] ?: 'Especialidad Shizen'),
          'negocio_id' => (int) $id,
          'precio' => (float) $dish['precio'],
          'precio_promocion' => !empty($dish['precio_promocion']) ? (float) $dish['precio_promocion'] : null,
          'has_promo' => !empty($dish['on_promo']),
          'tag' => 'Vegano',
          'show_favorite' => true,
          'is_favorite' => in_array((int) $dish['id_menu_item'], $favoriteItemIds, true),
          'csrf_token' => (string) ($_SESSION['csrf_token'] ?? ''),
          'redirect_url' => 'php/negocio.php?id=' . (int) $id,
        ];
        include __DIR__ . '/../forms/tarjeta_plato.php';
        ?>
      <?php endforeach; ?>
    </div>
  </main>
  <div id="overlays"><?php include __DIR__ . '/../forms/modales.php'; ?></div>
  <script src="js/app.js?v=20260930-cart-clear-1"></script>
</body>

</html>