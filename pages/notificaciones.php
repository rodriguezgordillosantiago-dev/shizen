<?php
declare(strict_types=1);
require_once __DIR__ . '/../BD/conexion.php';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('SHIZEN_REPARTIDOR_SESSION');
    session_start();
}
$userId = (int)($_SESSION['id_usuario'] ?? 0);
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
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Notificaciones</title>
<link rel="stylesheet" href="styles/navigation.css?v=nav-stable-4"><style>
body{margin:0 auto;max-width:430px;overflow:hidden;background:#f3f4f6;font-family:"Inter",sans-serif;color:#1f2937}.page{height:100dvh;overflow-y:auto;padding:28px 18px 90px}
.notifications-header{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:18px}.notifications-header h1{margin:0;font-size:28px;line-height:1.1}.notifications-header form{flex:0 1 150px;margin:0}.notifications-header button{display:block;width:100%;min-height:48px;border:0;border-radius:12px;padding:10px 14px;background:#4c9540;color:#fff;font-weight:700;font-size:14px;line-height:1.15}
.item{background:#fff;border-radius:14px;padding:16px;margin:12px 0;box-shadow:0 2px 8px #0000000d}.unread{border-left:4px solid #4c9540}.item h2{font-size:15px;margin:0 0 7px}.item p{font-size:13px;margin:0 0 8px;color:#4b5563}.item time{font-size:11px;color:#9ca3af}
.bottom-nav button{float:none}
@media (max-width:390px){.page{padding:20px 12px 84px}.notifications-header{align-items:stretch;gap:10px}.notifications-header h1{font-size:25px;align-self:center}.notifications-header form{flex:1 1 auto;max-width:145px}.notifications-header button{min-height:52px;padding:8px 10px;font-size:13px}}
</style></head>
<body><main class="page"><div class="notifications-header"><h1>Notificaciones</h1><?php if (array_filter($items, fn(array $n): bool => !(bool)$n['leida'])): ?><form method="post"><button type="submit">Marcar<br>leídas</button></form><?php endif; ?></div>
<?php if (!$items): ?><div class="item">No tienes notificaciones todavía.</div><?php else: foreach ($items as $item): ?><article class="item <?= !$item['leida'] ? 'unread' : '' ?>"><h2><?= htmlspecialchars($item['titulo'], ENT_QUOTES, 'UTF-8') ?></h2><p><?= htmlspecialchars($item['mensaje'], ENT_QUOTES, 'UTF-8') ?></p><time><?= htmlspecialchars($item['fecha_creacion'], ENT_QUOTES, 'UTF-8') ?></time></article><?php endforeach; endif; ?>
</main>
<nav class="bottom-nav" aria-label="Navegación principal">
  <a href="inicio.html" class="nav-item" aria-label="Inicio">
    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10.5 12 3l9 7.5v8.25A2.25 2.25 0 0 1 18.75 21H5.25A2.25 2.25 0 0 1 3 18.75V10.5Z"/></svg>
    <span>Inicio</span>
  </a>
  <a href="activos.html" class="nav-item" aria-label="Activos">
    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4 7 8-4 8 4-8 4-8-4Zm0 0v10l8 4 8-4V7M12 11v10"/></svg>
    <span>Activos</span>
    <b class="badge" id="badge-activos"></b>
  </a>
  <button id="delivery-status-toggle" class="nav-status-toggle" type="button" aria-pressed="false">
    <span class="nav-status-icon"><svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" d="M12 2v10m0 0 4-4m-4 4-4-4M5 13v4a3 3 0 0 0 3 3h8a3 3 0 0 0 3-3v-4"/></svg></span>
    <span class="nav-status-label">Activo</span>
  </button>
  <a href="notificaciones.php" class="nav-item is-active" aria-current="page" aria-label="Notificaciones">
    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9Zm-8 13h4"/></svg>
    <span>Notificaciones</span>
    <b class="badge" id="badge-notificaciones"></b>
  </a>
  <a href="../php/perfil.php" class="nav-item" aria-label="Perfil">
    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0Zm-12 14a8 8 0 0 1 16 0"/></svg>
    <span>Perfil</span>
  </a>
</nav>
<script src="../js/data.js"></script>
<script>
  iniciarBotonEstadoRepartidor();
</script>
</body></html>
