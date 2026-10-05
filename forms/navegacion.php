<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('SHIZEN_CLIENTE_SESSION');
    session_start();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$logueado = !empty($_SESSION['id_usuario']);
$nombreUsuario = htmlspecialchars((string)($_SESSION['usuario_nombre'] ?? 'Mi cuenta'), ENT_QUOTES, 'UTF-8');
$correoUsuario = htmlspecialchars((string)($_SESSION['usuario_email'] ?? ''), ENT_QUOTES, 'UTF-8');
$csrfToken = htmlspecialchars((string)$_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8');
$avatarFiles = glob(__DIR__ . '/../assets/Perfil/*.{png,jpg,jpeg,webp}', GLOB_BRACE) ?: [];
$avatarDefault = $avatarFiles ? 'assets/Perfil/' . basename($avatarFiles[0]) : '';
$avatarUsuario = (string)($_SESSION['usuario_avatar'] ?? $avatarDefault);
?>
<nav class="shizen-nav">
  <div class="nav-inner">
    <a class="nav-logo" href="index.php" aria-label="Shizen, inicio">
      <img src="assets/logo.png" alt="Shizen" />
    </a>

    <form class="nav-search" method="get" action="php/buscar.php" role="search">
      <label class="sr-only" for="navSearch">Buscar en Shizen</label>
            <button type="submit" aria-label="Buscar"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg></button>
      <input id="navSearch" name="q" type="search" placeholder="¿Qué quieres comer?" value="<?= htmlspecialchars((string)($_GET['q'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    </form>

    <div class="nav-right">
      <?php /* ── USUARIO LOGUEADO ────────────────────── */ if ($logueado): ?>
        <div class="nav-user-menu">
          <div class="nav-user-actions" id="navUserActions" aria-hidden="true">
            <button class="nav-tool-button" type="button" onclick="openCart(event)" aria-label="Abrir carrito" title="Carrito">
              <svg class="cartIcon" viewBox="0 0 576 512" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"><path d="M0 24C0 10.7 10.7 0 24 0H69.5c22 0 41.5 12.8 50.6 32h411c26.3 0 45.5 25 38.6 50.4l-41 152.3c-8.5 31.4-37 53.3-69.5 53.3H170.7l5.4 28.5c2.2 11.3 12.1 19.5 23.6 19.5H488c13.3 0 24 10.7 24 24s-10.7 24-24 24H199.7c-34.6 0-64.3-24.6-70.7-58.5L77.4 54.5c-.7-3.8-4-6.5-7.9-6.5H24C10.7 48 0 37.3 0 24zM128 464a48 48 0 1 1 96 0 48 48 0 1 1 -96 0zm336-48a48 48 0 1 1 0 96 48 48 0 1 1 0-96z"/></svg>
              <span id="cartCountMenu">0</span>
            </button>
            <a class="nav-tool-button orders-button" href="php/pedidos.php" aria-label="Mis pedidos" title="Mis pedidos"><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/></svg></a>
            <a class="nav-tool-button button notification-button" href="php/notificaciones.php" aria-label="Notificaciones" title="Notificaciones">
              <svg class="bell" viewBox="0 0 448 512" aria-hidden="true"><path d="M224 0c-17.7 0-32 14.3-32 32V49.9C119.5 61.4 64 124.2 64 200v33.4c0 45.4-15.5 89.5-43.8 124.9L5.3 377c-5.8 7.2-6.9 17.1-2.9 25.4S14.8 416 24 416H424c9.2 0 17.6-5.3 21.6-13.6s2.9-18.2-2.9-25.4l-14.9-18.6C399.5 322.9 384 278.8 384 233.4V200c0-75.8-55.5-138.6-128-150.1V32c0-17.7-14.3-32-32-32zm0 96h8c57.4 0 104 46.6 104 104v33.4c0 47.9 13.9 94.6 39.7 134.6H72.3C98.1 328 112 281.3 112 233.4V200c0-57.4 46.6-104 104-104h8zm64 352H224 160c0 17 6.7 33.3 18.7 45.3s28.3 18.7 45.3 18.7s33.3-6.7 45.3-18.7s18.7-28.3 18.7-45.3z"/></svg>
            </a>
            <form class="nav-logout-form" action="auth/logout.php" method="post">
              <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
              <button class="Btn nav-logout-button" type="submit" aria-label="Cerrar sesión" title="Cerrar sesión">
                <span class="sign" aria-hidden="true"><svg viewBox="0 0 512 512"><path d="M377.9 105.9L500.7 228.7c7.2 7.2 11.3 17.1 11.3 27.3s-4.1 20.1-11.3 27.3L377.9 406.1c-6.4 6.4-15 9.9-24 9.9c-18.7 0-33.9-15.2-33.9-33.9l0-62.1-128 0c-17.7 0-32-14.3-32-32l0-64c0-17.7 14.3-32 32-32l128 0 0-62.1c0-18.7 15.2-33.9 33.9-33.9c9 0 17.6 3.6 24 9.9zM160 96L96 96c-17.7 0-32 14.3-32 32l0 256c0 17.7 14.3 32 32 32l64 0c17.7 0 32 14.3 32 32s-14.3 32-32 32l-64 0c-53 0-96-43-96-96L0 128C0 75 43 32 96 32l64 0c17.7 0 32 14.3 32 32s-14.3 32-32 32z"></path></svg></span>
              </button>
            </form>
          </div>
          <button class="nav-user-toggle" id="userMenuToggle" type="button" aria-label="Mostrar accesos de usuario" aria-expanded="false" aria-controls="navUserActions" onclick="toggleNavUserMenu(event)">
            <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="m13 4.5-5.5 5.5 5.5 5.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </button>
          <a class="nav-avatar-link" href="forms/perfil.php" aria-label="Ir a mi perfil" title="Mi perfil">
            <?php if ($avatarUsuario !== ''): ?>
              <img class="nav-avatar-image" src="<?= htmlspecialchars($avatarUsuario, ENT_QUOTES, 'UTF-8') ?>" alt="Foto de perfil" width="34" height="34">
            <?php else: ?>
              <span class="nav-avatar-fallback" aria-hidden="true">&#128100;</span>
            <?php endif; ?>
            <span class="nav-notification-dot" id="notifBadge" aria-label="Tienes notificaciones sin leer" title="Tienes notificaciones sin leer" hidden></span>
          </a>
        </div>
      <?php /* ── VISITANTE (SIN SESIÓN) ──────────────── */ else: ?>
        <a class="nav-orders-button orders-button" href="#login" onclick="openAccessModal('php/pedidos.php'); return false;" aria-label="Inicia sesión para ver tus pedidos" title="Mis pedidos"><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/></svg></a>
        <a class="btn-ingreso btn-ingreso--user" id="ingresoBtn" href="#login" onclick="event.preventDefault(); openAccessModal()"><svg xmlns="http://www.w3.org/2000/svg" class="ingreso-icon w-5 h-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path></svg>Ingreso</a>
      <?php endif; /* ── FIN CONDICIONAL SESIÓN ──────────────── */ ?>
      <button class="btn-hamburger" id="hamburgerBtn" type="button" onclick="toggleNavMobileMenu()" aria-label="Abrir menú" aria-controls="mobileMenu" aria-expanded="false">
        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 6h16M4 12h16M4 18h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
      </button>
    </div>
  </div>
  <div class="mobile-menu" id="mobileMenu">
    <?php if ($logueado): ?>
      <a class="mobile-menu-btn mobile-profile-link" href="forms/perfil.php">
        <span class="mobile-avatar-wrap"><?php if ($avatarUsuario !== ''): ?><img src="<?= htmlspecialchars($avatarUsuario, ENT_QUOTES, 'UTF-8') ?>" alt="" width="28" height="28"><?php else: ?>&#128100;<?php endif; ?></span>
        Mi perfil <span class="mobile-user-name"><?= $nombreUsuario ?></span>
      </a>
      <a class="mobile-menu-btn" href="php/pedidos.php">&#128666; Mis pedidos</a>
      <a class="mobile-menu-btn" href="php/notificaciones.php"><svg class="notificationIcon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><path fill="none" d="M0 0h24v24H0z"/><path fill="currentColor" d="M20 17h2v2H2v-2h2v-7a8 8 0 1 1 16 0v7zm-2 0v-7a6 6 0 1 0-12 0v7h12zm-9 4h6v2H9v-2z"/></svg> Notificaciones</a>
      <form class="nav-logout-form" action="auth/logout.php" method="post">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
        <button class="mobile-menu-btn" type="submit"><svg class="logout-icon" width="18" height="18" fill="currentColor" viewBox="0 0 512 512" aria-hidden="true"><path d="M377.9 105.9L500.7 228.7c7.2 7.2 11.3 17.1 11.3 27.3s-4.1 20.1-11.3 27.3L377.9 406.1c-6.4 6.4-15 9.9-24 9.9c-18.7 0-33.9-15.2-33.9-33.9l0-62.1-128 0c-17.7 0-32-14.3-32-32l0-64c0-17.7 14.3-32 32-32l128 0 0-62.1c0-18.7 15.2-33.9 33.9-33.9c9 0 17.6 3.6 24 9.9zM160 96L96 96c-17.7 0-32 14.3-32 32l0 256c0 17.7 14.3 32 32 32l64 0c17.7 0 32 14.3 32 32s-14.3 32-32 32l-64 0c-53 0-96-43-96-96L0 128C0 75 43 32 96 32l64 0c17.7 0 32 14.3 32 32s-14.3 32-32 32z"></path></svg> Cerrar sesión</button>
      </form>
    <?php else: ?>
      <a class="mobile-menu-btn" href="#login" onclick="openAccessModal('php/pedidos.php'); toggleNavMobileMenu(); return false;">&#128666; Mis pedidos</a>
      <a class="mobile-menu-btn" href="#login" onclick="openAccessModal(); toggleNavMobileMenu(); return false;">&#128272; Iniciar sesión</a>
    <?php endif; ?>
    <a class="mobile-menu-btn" href="php/promociones.php">&#127881; Promociones</a>
    <a class="mobile-menu-btn" href="php/registro_usuario.php">&#128100; Para usuarios</a>
    <a class="mobile-menu-btn" href="php/registro_negocio.php">&#127978; Para negocios</a>
    <a class="mobile-menu-btn" href="php/registro_repartidor.php">&#128691; Para repartidores</a>
    <a class="mobile-menu-btn" href="/shizennegocio/php/login.php">&#128188; Ingreso negocios</a>
    <a class="mobile-menu-btn" href="/shizen_repartidor/">&#128694; Ingreso repartidores</a>
  </div>
  <div class="nav-underline"></div>
</nav>
<script>
  function toggleNavUserMenu(event) {
    event.stopPropagation();
    var menu = document.querySelector('.nav-user-menu');
    var button = document.getElementById('userMenuToggle');
    if (!menu || !button) return;
    var isOpen = menu.classList.toggle('open');
    button.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    var actions = document.getElementById('navUserActions');
    if (actions) actions.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
  }

  function toggleNavMobileMenu() {
    var menu = document.getElementById('mobileMenu');
    var button = document.getElementById('hamburgerBtn');
    if (!menu || !button) return;
    var isOpen = menu.classList.toggle('open');
    button.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
  }

  document.addEventListener('click', function (event) {
    var menu = document.querySelector('.nav-user-menu');
    var button = document.getElementById('userMenuToggle');
    if (menu && button && !menu.contains(event.target)) {
      menu.classList.remove('open');
      button.setAttribute('aria-expanded', 'false');
      var actions = document.getElementById('navUserActions');
      if (actions) actions.setAttribute('aria-hidden', 'true');
    }
  });

  document.addEventListener('keydown', function (event) {
    if (event.key !== 'Escape') return;
    var menu = document.querySelector('.nav-user-menu');
    var button = document.getElementById('userMenuToggle');
    if (menu && button) {
      menu.classList.remove('open');
      button.setAttribute('aria-expanded', 'false');
      var actions = document.getElementById('navUserActions');
      if (actions) actions.setAttribute('aria-hidden', 'true');
    }
  });
</script>
