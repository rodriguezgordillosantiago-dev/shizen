<?php
declare(strict_types=1);

require_once __DIR__ . '/../php/config/database.php';
require_once __DIR__ . '/../php/config/auth.php';
require_auth();

$user        = current_user();
$currentPage = 'notificaciones';
$pageTitle   = 'Notificaciones';
$db          = database();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db->prepare('UPDATE notificacion SET leida = 1 WHERE id_usuario = :usuario AND audiencia = "negocio"')
       ->execute(['usuario' => (int)$user['id_usuario']]);
}
$stmt = $db->prepare(
    'SELECT id_notificacion, id_pedido, titulo, mensaje, leida, fecha_creacion
       FROM notificacion
      WHERE id_usuario = :usuario AND audiencia = "negocio"
      ORDER BY fecha_creacion DESC LIMIT 50'
);
$stmt->execute(['usuario' => (int)$user['id_usuario']]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
$unread = count(array_filter($notifications, fn(array $n): bool => !(bool)$n['leida']));
?>
<!DOCTYPE html>
<html lang="es">
<?php require_once __DIR__ . '/../php/includes/head.php'; ?>
<body class="layout">

<?php require_once __DIR__ . '/../php/includes/sidebar.php'; ?>

<div class="main">
  <?php require_once __DIR__ . '/../php/includes/topbar.php'; ?>
  <main class="content">

    <div class="page-header">
      <div>
        <h1 class="page-header-title">Notificaciones</h1>
        <p class="page-header-sub">
          <?= $unread ?> sin leer de <?= count($notifications) ?> total
        </p>
      </div>
      <?php if ($unread > 0): ?>
        <form method="post">
          <button class="btn btn-secondary" type="submit"><i class="bx bx-check-double"></i> Marcar todas como leidas</button>
        </form>
      <?php endif; ?>
    </div>

    <div class="card">
      <?php foreach ($notifications as $i => $n): ?>
        <div class="notif-item <?= !$n['leida'] ? 'unread' : '' ?>" id="notif-<?= $i ?>">
          <div class="notif-dot <?= $n['leida'] ? 'read' : '' ?>" id="dot-<?= $i ?>"></div>
          <div class="notif-icon" style="background:#dbeafe;color:#2563eb">
            <i class="bx bx-bell"></i>
          </div>
          <div style="flex:1">
            <div class="notif-title"><?= htmlspecialchars($n['titulo'], ENT_QUOTES, 'UTF-8') ?></div>
            <div class="notif-body"><?= htmlspecialchars($n['mensaje'], ENT_QUOTES, 'UTF-8') ?></div>
            <div class="notif-time">
              <i class="bx bx-time-five"></i>
              <?= htmlspecialchars($n['fecha_creacion'], ENT_QUOTES, 'UTF-8') ?>
            </div>
          </div>
          <?php if (!$n['leida']): ?>
            <button class="btn btn-secondary btn-sm" onclick="markRead(<?= $i ?>)" style="flex-shrink:0">
              Marcar leida
            </button>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
      <?php if (!$notifications): ?><div style="padding:30px;text-align:center;color:#777">No hay notificaciones todavía.</div><?php endif; ?>
    </div>

  </main>
</div>

<script>
function markRead(i) {
  const item = document.getElementById('notif-' + i);
  const dot  = document.getElementById('dot-' + i);
  item.classList.remove('unread');
  dot.classList.add('read');
  item.querySelector('button').remove();
}
function markAllRead() {
  document.querySelectorAll('.notif-item.unread').forEach(item => {
    item.classList.remove('unread');
    item.querySelector('.notif-dot')?.classList.add('read');
    item.querySelector('button')?.remove();
  });
}
</script>
</body>
</html>
