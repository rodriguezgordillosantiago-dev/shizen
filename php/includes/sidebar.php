<?php
$navItems = [
  ['href' => '../pages/dashboard.php',      'icon' => 'bx bx-home-alt-2',  'label' => 'Dashboard',       'key' => 'dashboard'],
  ['href' => '../pages/productos.php',      'icon' => 'bx bx-package',      'label' => 'Productos',       'key' => 'productos'],
  ['href' => '../pages/pedidos.php',        'icon' => 'bx bx-receipt',      'label' => 'Pedidos',         'key' => 'pedidos'],
  ['href' => '../pages/clientes.php',       'icon' => 'bx bx-group',        'label' => 'Clientes',        'key' => 'clientes'],
  ['href' => '../pages/notificaciones.php', 'icon' => 'bx bx-bell',         'label' => 'Notificaciones',  'key' => 'notificaciones'],
  ['href' => '../pages/configuracion.php',  'icon' => 'bx bx-cog',          'label' => 'Configuracion',   'key' => 'configuracion'],
  ['href' => '../pages/perfil.php',         'icon' => 'bx bx-user',         'label' => 'Mi Perfil',       'key' => 'perfil'],
];
$currentPage = $currentPage ?? '';
?>
<nav class="sidebar">
  <div class="sidebar-brand" style="padding:16px 20px">
    <a href="../pages/dashboard.php" style="display:block">
      <img src="../assets/logo.png" alt="SHIZEN Negocio" style="max-height:48px;width:auto;display:block;margin:0 auto">
    </a>
  </div>

  <div class="sidebar-nav">
    <p class="sidebar-label">Menu principal</p>
    <?php foreach ($navItems as $item): ?>
      <a href="<?= $item['href'] ?>"
         class="nav-item <?= $currentPage === $item['key'] ? 'active' : '' ?>">
        <i class="<?= $item['icon'] ?>"></i>
        <?= $item['label'] ?>
      </a>
    <?php endforeach; ?>
  </div>

  <div class="sidebar-footer">
    <a href="../auth/logout.php" class="nav-item nav-item-logout">
      <svg class="logout-icon" width="18" height="18" fill="currentColor" viewBox="0 0 512 512" aria-hidden="true"><path d="M377.9 105.9L500.7 228.7c7.2 7.2 11.3 17.1 11.3 27.3s-4.1 20.1-11.3 27.3L377.9 406.1c-6.4 6.4-15 9.9-24 9.9c-18.7 0-33.9-15.2-33.9-33.9l0-62.1-128 0c-17.7 0-32-14.3-32-32l0-64c0-17.7 14.3-32 32-32l128 0 0-62.1c0-18.7 15.2-33.9 33.9-33.9c9 0 17.6 3.6 24 9.9zM160 96L96 96c-17.7 0-32 14.3-32 32l0 256c0 17.7 14.3 32 32 32l64 0c17.7 0 32 14.3 32 32s-14.3 32-32 32l-64 0c-53 0-96-43-96-96L0 128C0 75 43 32 96 32l64 0c17.7 0 32 14.3 32 32s-14.3 32-32 32z"></path></svg>
      Cerrar sesion
    </a>
  </div>
</nav>
