<?php
declare(strict_types=1);
require_once __DIR__ . '/../BD/conexion.php';
require_once __DIR__ . '/../php/nav.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
  session_name('SHIZEN_REPARTIDOR_SESSION');
  session_start();
}
$userId = (int) ($_SESSION['id_usuario'] ?? 0);
if ($userId <= 0) {
  header('Location: ../index.html');
  exit;
}
$db = obtenerConexion();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $db->prepare('UPDATE notificacion SET leida = 1 WHERE id_usuario = :usuario AND audiencia = "repartidor"')
    ->execute(['usuario' => $userId]);
}
$stmt = $db->prepare(
  'SELECT titulo, mensaje, leida, fecha_creacion
       FROM notificacion
      WHERE id_usuario = :usuario AND audiencia = "repartidor"
      ORDER BY fecha_creacion DESC LIMIT 50'
);
$stmt->execute(['usuario' => $userId]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="es">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Shizen repartidor · Avisos</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles/styles.css?v=20261009-final">
  <style>
    .notif-wrap {
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      padding: 23px 16px 110px 16px;
      overflow-y: auto;
      box-sizing: border-box;
      display: flex;
      flex-direction: column;
      gap: 12px;
      z-index: 2;
      -webkit-overflow-scrolling: touch;
    }

    .notif-card {
      box-sizing: border-box;
      padding: 14px 16px;
      border-radius: 20px;
      background: #FFFFFF;
      box-shadow: 0 4px 14px rgba(16, 40, 26, 0.08);
      display: flex;
      flex-direction: column;
      gap: 6px;
      border-left: 5px solid transparent;
    }

    .notif-card.unread {
      border-left: 5px solid #4c9540;
    }

    .notif-title {
      margin: 0;
      font-size: 15px;
      font-weight: 700;
      color: #163820;
    }

    .notif-msg {
      margin: 0;
      font-size: 13px;
      line-height: 1.35;
      color: #4b7055;
    }

    .notif-time {
      font-size: 11px;
      font-weight: 500;
      color: #6b8f6e;
    }

    .btn-readall {
      height: 36px;
      padding: 0 14px;
      border-radius: 18px;
      border: 0;
      background: #2d6a31;
      color: #FFFFFF;
      font-size: 12px;
      font-weight: 700;
      cursor: pointer;
    }
  </style>
</head>

<body>
  <div class="pantalla pantalla-marco">

    <!-- Leaf Decoration -->
    <svg width="390" height="300" viewBox="0 0 390 300" class="adorno-hojas-svg" aria-hidden="true">
      <g fill="none" stroke="#DCE5CF" stroke-width="1.5">
        <circle cx="390" cy="0" r="90"></circle>
        <circle cx="390" cy="0" r="140"></circle>
      </g>
      <g transform="translate(346,30) scale(0.7)">
        <path d="M0 80 C 0 40 40 0 80 0 C 80 40 40 80 0 80 Z" fill="#2d6a31"></path>
      </g>
    </svg>

    <div class="notif-wrap">
      <div class="activos-cabecera">
        <div class="activos-logo-fila">
          <img src="logos/shizen-color.png" alt="Shizen food" class="activos-logo-img">
          <?php if (array_filter($items, fn(array $n): bool => !(bool) $n['leida'])): ?>
            <form method="post" style="margin:0">
              <button type="submit" class="btn-readall">Marcar leídas</button>
            </form>
          <?php endif; ?>
        </div>
        <h1 class="activos-titulo">Avisos</h1>
        <p class="activos-resumen">Tus notificaciones y alertas de servicio</p>
      </div>

      <?php if (!$items): ?>
        <div class="tarjeta-detalle-pedido" style="text-align:center;padding:32px 16px;color:#4b7055;">
          <p style="margin:0;font-size:14px;font-weight:700;">No tienes avisos por el momento.</p>
        </div>
      <?php else: ?>
        <?php foreach ($items as $item): ?>
          <article class="notif-card <?= !(bool) $item['leida'] ? 'unread' : '' ?>">
            <h2 class="notif-title"><?= htmlspecialchars((string) $item['titulo'], ENT_QUOTES, 'UTF-8') ?></h2>
            <p class="notif-msg"><?= htmlspecialchars((string) $item['mensaje'], ENT_QUOTES, 'UTF-8') ?></p>
            <time class="notif-time"><?= htmlspecialchars((string) $item['fecha_creacion'], ENT_QUOTES, 'UTF-8') ?></time>
          </article>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- Reusable Native HTML Navbar (Shizen App) -->
    <?php renderNav('avisos'); ?>

  </div>

  <script src="../js/data.js"></script>
</body>

</html>