<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_name('SHIZEN_CLIENTE_SESSION');
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!doctype html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Shizen — Comida vegana a tu puerta</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="css/styles.css" />
    <link rel="stylesheet" href="css/nav.css?v=20261001-orders-icon-1" />
    <link rel="stylesheet" href="css/home.css?v=20261001-promo-green-1" />
    <link rel="stylesheet" href="css/pages.css?v=20260816-1" />
    <link rel="stylesheet" href="css/registro_base.css" />
    <link rel="stylesheet" href="css/registro_usuario.css" />
    <link rel="stylesheet" href="css/registro_negocio.css" />
    <link rel="stylesheet" href="css/registro_repartidor.css" />
    <link rel="stylesheet" href="css/modals.css?v=20260930-cart-clear-1" />
  </head>
  <body>
    <header id="navigation">
      <?php include __DIR__ . '/forms/navegacion.php'; ?>
    </header>
    <main id="app-content">
      <?php include __DIR__ . '/forms/home.php'; ?>
    </main>
    <div id="overlays">
      <?php include __DIR__ . '/forms/modales.php'; ?>
    </div>
    <script>
      window.shizenCategoryUrl = 'php/categorias.php';
    </script>
    <script src="js/layout.js?v=20260912-1"></script>
    <script src="js/app.js?v=20260930-cart-clear-1"></script>
  </body>
</html>
