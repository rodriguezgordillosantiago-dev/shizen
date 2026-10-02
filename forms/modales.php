<?php
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_name('SHIZEN_CLIENTE_SESSION');
    session_start();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = htmlspecialchars((string)($_SESSION['csrf_token'] ?? ''), ENT_QUOTES, 'UTF-8');
$loginRedirect = (string)($_GET['login_redirect'] ?? 'index.php');
$loginRedirect = preg_match('#^(?:(?:php|forms)/)?[A-Za-z0-9_-]+\.php(?:\?[A-Za-z0-9_=&%-]*)?$#', $loginRedirect) ? $loginRedirect : 'index.php';
$profileUser = $_SESSION['id_usuario'] ?? null;
$clientNotifications = [];
if ($profileUser) {
    require_once __DIR__ . '/../BD/conexion.php';
    $notificationStatement = obtenerConexion()->prepare(
        'SELECT titulo, mensaje, leida, fecha_creacion
           FROM notificacion
          WHERE id_usuario = :usuario AND audiencia = "cliente"
          ORDER BY fecha_creacion DESC LIMIT 8'
    );
    $notificationStatement->execute(['usuario' => (int) $profileUser]);
    $clientNotifications = $notificationStatement->fetchAll(PDO::FETCH_ASSOC);
}
?>
<script>
  window.shizenUser = <?= json_encode(!empty($_SESSION['id_usuario']) ? ['id' => (int)$_SESSION['id_usuario'], 'nombre' => (string)($_SESSION['usuario_nombre'] ?? '')] : null) ?>;
  window.loginError = <?= isset($_GET['error']) && $_GET['error'] === '1' ? 'true' : 'false' ?>;
</script>
<?php if ($profileUser): ?>
<div class="modal-overlay" id="profileModal" onclick="handleProfileOverlayClick(event)">
  <div class="modal-card profile-card">
    <button class="modal-close" type="button" onclick="closeProfileModal()" aria-label="Cerrar">×</button>
    <div class="modal-title">Mis datos</div>
    <p class="modal-sub">Consulta y actualiza tu información de Shizen.</p>
    <form method="post" action="php/perfil.php">
      <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
      <input class="modal-input profile-field" name="nombre" value="<?= htmlspecialchars($_SESSION['usuario_nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Nombre" readonly required><script>(function(c){if(!c||c.dataset.validacionCampo)return;c.dataset.validacionCampo='1';var e=c.nextElementSibling&&c.nextElementSibling.classList.contains('field-error-inline')?c.nextElementSibling:document.createElement('small');e.className='field-error-inline';e.style.cssText='display:block;color:#c62828;font-size:12px;margin-top:4px;min-height:1em;';if(!e.parentNode)c.insertAdjacentElement('afterend',e);function v(){var x=c.required&&!c.value.trim(),i=x||!c.validity.valid;e.textContent=i?(x?'Este campo es obligatorio.':c.validationMessage):'';c.setAttribute('aria-invalid',i?'true':'false');return!i}['input','change','blur'].forEach(function(t){c.addEventListener(t,v)});if(c.form)c.form.addEventListener('submit',function(a){if(!v())a.preventDefault()})})(document.currentScript.previousElementSibling);</script>
      <input class="modal-input profile-field" name="apellido" value="<?= htmlspecialchars($_SESSION['usuario_apellido'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Apellido" readonly required><script>(function(c){if(!c||c.dataset.validacionCampo)return;c.dataset.validacionCampo='1';var e=c.nextElementSibling&&c.nextElementSibling.classList.contains('field-error-inline')?c.nextElementSibling:document.createElement('small');e.className='field-error-inline';e.style.cssText='display:block;color:#c62828;font-size:12px;margin-top:4px;min-height:1em;';if(!e.parentNode)c.insertAdjacentElement('afterend',e);function v(){var x=c.required&&!c.value.trim(),i=x||!c.validity.valid;e.textContent=i?(x?'Este campo es obligatorio.':c.validationMessage):'';c.setAttribute('aria-invalid',i?'true':'false');return!i}['input','change','blur'].forEach(function(t){c.addEventListener(t,v)});if(c.form)c.form.addEventListener('submit',function(a){if(!v())a.preventDefault()})})(document.currentScript.previousElementSibling);</script>
      <input class="modal-input profile-wide profile-field" name="direccion" value="<?= htmlspecialchars($_SESSION['usuario_direccion'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Dirección" readonly><script>(function(c){if(!c||c.dataset.validacionCampo)return;c.dataset.validacionCampo='1';var e=c.nextElementSibling&&c.nextElementSibling.classList.contains('field-error-inline')?c.nextElementSibling:document.createElement('small');e.className='field-error-inline';e.style.cssText='display:block;color:#c62828;font-size:12px;margin-top:4px;min-height:1em;';if(!e.parentNode)c.insertAdjacentElement('afterend',e);function v(){var x=c.required&&!c.value.trim(),i=x||!c.validity.valid;e.textContent=i?(x?'Este campo es obligatorio.':c.validationMessage):'';c.setAttribute('aria-invalid',i?'true':'false');return!i}['input','change','blur'].forEach(function(t){c.addEventListener(t,v)});if(c.form)c.form.addEventListener('submit',function(a){if(!v())a.preventDefault()})})(document.currentScript.previousElementSibling);</script>
      <input class="modal-input profile-field" name="localidad" value="<?= htmlspecialchars($_SESSION['usuario_localidad'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Localidad" readonly><script>(function(c){if(!c||c.dataset.validacionCampo)return;c.dataset.validacionCampo='1';var e=c.nextElementSibling&&c.nextElementSibling.classList.contains('field-error-inline')?c.nextElementSibling:document.createElement('small');e.className='field-error-inline';e.style.cssText='display:block;color:#c62828;font-size:12px;margin-top:4px;min-height:1em;';if(!e.parentNode)c.insertAdjacentElement('afterend',e);function v(){var x=c.required&&!c.value.trim(),i=x||!c.validity.valid;e.textContent=i?(x?'Este campo es obligatorio.':c.validationMessage):'';c.setAttribute('aria-invalid',i?'true':'false');return!i}['input','change','blur'].forEach(function(t){c.addEventListener(t,v)});if(c.form)c.form.addEventListener('submit',function(a){if(!v())a.preventDefault()})})(document.currentScript.previousElementSibling);</script>
      <button class="btn-modal-primary" id="profileEditButton" type="button" onclick="enableProfileEditing()">Editar datos</button>
      <button class="btn-modal-primary profile-save-button" id="profileSaveButton" type="submit" hidden>Guardar cambios</button>
    </form>
    <form class="profile-logout-form" action="auth/logout.php" method="post">
      <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
      <button class="profile-logout-button" type="submit"><svg class="logout-icon" width="18" height="18" fill="currentColor" viewBox="0 0 512 512" aria-hidden="true"><path d="M377.9 105.9L500.7 228.7c7.2 7.2 11.3 17.1 11.3 27.3s-4.1 20.1-11.3 27.3L377.9 406.1c-6.4 6.4-15 9.9-24 9.9c-18.7 0-33.9-15.2-33.9-33.9l0-62.1-128 0c-17.7 0-32-14.3-32-32l0-64c0-17.7 14.3-32 32-32l128 0 0-62.1c0-18.7 15.2-33.9 33.9-33.9c9 0 17.6 3.6 24 9.9zM160 96L96 96c-17.7 0-32 14.3-32 32l0 256c0 17.7 14.3 32 32 32l64 0c17.7 0 32 14.3 32 32s-14.3 32-32 32l-64 0c-53 0-96-43-96-96L0 128C0 75 43 32 96 32l64 0c17.7 0 32 14.3 32 32s-14.3 32-32 32z"></path></svg> Cerrar sesión</button>
    </form>
  </div>
</div>
<?php endif; ?>
<?php if (!$profileUser): ?>
<div class="modal-overlay" id="loginModal" onclick="handleLoginOverlayClick(event)">
  <div class="modal-card login-modal-card">
    <button class="modal-close" type="button" onclick="closeLoginModal()" aria-label="Cerrar">×</button>
    <img class="modal-icon-logo login-modal-logo" src="assets/logo.png" alt="Shizen">
    <div class="modal-title">Inicia sesión</div>
    <p class="modal-sub">Accede a tu cuenta Shizen para continuar.</p>
    <form method="post" action="auth/login.php">
      <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
      <input type="hidden" id="loginModalRedirect" name="redirect" value="<?= htmlspecialchars($loginRedirect, ENT_QUOTES, 'UTF-8') ?>">
      <div class="modal-form-group">
        <label for="modal-login-email">Email</label>
        <input class="modal-input" id="modal-login-email" name="email" type="email" placeholder="tu@email.com" autocomplete="email" required>
      </div>
      <div class="modal-form-group">
        <label for="modal-login-password">Contraseña</label>
        <div class="password-field">
          <input class="modal-input" id="modal-login-password" name="password" type="password" placeholder="Contraseña" autocomplete="current-password" minlength="8" required>
          <button class="password-toggle" type="button" onclick="togglePassword(this)" aria-label="Mostrar contraseña" title="Mostrar contraseña">&#128065;</button>
        </div>
      </div>
      <button class="btn-modal-primary" type="submit">Ingresar</button>
    </form>
    <p class="login-terms">Al ingresar aceptas los <a href="legal/terminos.html" target="_blank" rel="noopener">Términos y Condiciones</a>.</p>
    <p class="login-register-prompt">¿Aún no tienes cuenta? <a href="index.php#registro" onclick="goToRegistration(); return false;">Crear cuenta</a></p>
  </div>
</div>
<?php endif; ?>
<?php if ($profileUser): ?>
<div class="modal-overlay" id="notificationModal" onclick="handleNotificationOverlayClick(event)">
  <div class="modal-card notification-modal-card">
    <button class="modal-close" type="button" onclick="closeNotificationModal()" aria-label="Cerrar">×</button>
    <div class="modal-icon">🔔</div>
    <div class="modal-title">Notificaciones</div>
    <p class="modal-sub">Actualizaciones de tus pedidos.</p>
    <div class="notification-modal-list">
      <?php if (!$clientNotifications): ?>
        <p class="notification-modal-empty">No tienes notificaciones todavía.</p>
      <?php else: foreach ($clientNotifications as $notification): ?>
        <article class="notification-modal-item <?= !$notification['leida'] ? 'unread' : '' ?>">
          <strong><?= htmlspecialchars($notification['titulo'], ENT_QUOTES, 'UTF-8') ?></strong>
          <span><?= htmlspecialchars($notification['mensaje'], ENT_QUOTES, 'UTF-8') ?></span>
          <time><?= htmlspecialchars($notification['fecha_creacion'], ENT_QUOTES, 'UTF-8') ?></time>
        </article>
      <?php endforeach; endif; ?>
    </div>
</div>
 </div>
<?php endif; ?>
<button class="chat-bubble" type="button" onclick="toggleChat()" aria-label="Abrir chat">
  <img src="assets/shizen-chat-leaf.png" alt="">
</button>
<div class="chat-panel" id="chatPanel" aria-hidden="true">
  <div class="chat-header"><strong>Ayuda Shizen</strong><button type="button" onclick="toggleChat()" aria-label="Cerrar chat">×</button></div>
  <div class="chat-body"><p>¡Hola! ¿En qué podemos ayudarte?</p><button type="button" onclick="this.textContent='Un asesor te responderá pronto.'">Hablar con un asesor</button></div>
</div>
<div class="modal-overlay" id="cartModal" aria-hidden="true" onclick="handleCartOverlayClick(event)">
  <div class="modal-card cart-card">
    <button class="modal-close" type="button" onclick="closeCart()" aria-label="Cerrar">×</button>
    <div class="modal-title">Tu carrito</div>
    <div id="cartItems" class="cart-items"></div>
    <div class="cart-total-row" id="cartTotalRow">
      <span>Total</span>
      <strong id="cartTotal">$0</strong>
    </div>
    <button class="btn-modal-primary" type="button" id="checkoutButton" onclick="openCheckout()">
      Continuar compra
    </button>
  </div>
</div>
<div class="modal-overlay" id="checkoutModal" onclick="handleCheckoutOverlayClick(event)">
  <div class="modal-card checkout-card">
    <button class="modal-close" type="button" onclick="closeCheckout()" aria-label="Cerrar">×</button>
    <div class="modal-title">Datos de entrega</div>
    <p class="modal-sub">Completa tus datos para registrar el pedido.</p>
    <form method="post" action="php/registrar_pedido.php" onsubmit="prepareCheckout(event)">
      <input type="hidden" name="items" id="checkoutItems">
      <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
      <div class="checkout-fields">
        <input class="modal-input" name="nombre" value="<?= htmlspecialchars($_SESSION['usuario_nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Nombre" required maxlength="60"><script>(function(c){if(!c||c.dataset.validacionCampo)return;c.dataset.validacionCampo='1';var e=c.nextElementSibling&&c.nextElementSibling.classList.contains('field-error-inline')?c.nextElementSibling:document.createElement('small');e.className='field-error-inline';e.style.cssText='display:block;color:#c62828;font-size:12px;margin-top:4px;min-height:1em;';if(!e.parentNode)c.insertAdjacentElement('afterend',e);function v(){var x=c.required&&!c.value.trim(),i=x||!c.validity.valid;e.textContent=i?(x?'Este campo es obligatorio.':c.validationMessage):'';c.setAttribute('aria-invalid',i?'true':'false');return!i}['input','change','blur'].forEach(function(t){c.addEventListener(t,v)});if(c.form)c.form.addEventListener('submit',function(a){if(!v())a.preventDefault()})})(document.currentScript.previousElementSibling);</script>
        <input class="modal-input" name="apellido" value="<?= htmlspecialchars($_SESSION['usuario_apellido'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Apellido" required maxlength="60"><script>(function(c){if(!c||c.dataset.validacionCampo)return;c.dataset.validacionCampo='1';var e=c.nextElementSibling&&c.nextElementSibling.classList.contains('field-error-inline')?c.nextElementSibling:document.createElement('small');e.className='field-error-inline';e.style.cssText='display:block;color:#c62828;font-size:12px;margin-top:4px;min-height:1em;';if(!e.parentNode)c.insertAdjacentElement('afterend',e);function v(){var x=c.required&&!c.value.trim(),i=x||!c.validity.valid;e.textContent=i?(x?'Este campo es obligatorio.':c.validationMessage):'';c.setAttribute('aria-invalid',i?'true':'false');return!i}['input','change','blur'].forEach(function(t){c.addEventListener(t,v)});if(c.form)c.form.addEventListener('submit',function(a){if(!v())a.preventDefault()})})(document.currentScript.previousElementSibling);</script>
        <input class="modal-input" name="numero_documento" placeholder="Número de documento" required maxlength="20" inputmode="numeric"><script>(function(c){if(!c||c.dataset.validacionCampo)return;c.dataset.validacionCampo='1';var e=c.nextElementSibling&&c.nextElementSibling.classList.contains('field-error-inline')?c.nextElementSibling:document.createElement('small');e.className='field-error-inline';e.style.cssText='display:block;color:#c62828;font-size:12px;margin-top:4px;min-height:1em;';if(!e.parentNode)c.insertAdjacentElement('afterend',e);function v(){var x=c.required&&!c.value.trim(),i=x||!c.validity.valid;e.textContent=i?(x?'Este campo es obligatorio.':c.validationMessage):'';c.setAttribute('aria-invalid',i?'true':'false');return!i}['input','change','blur'].forEach(function(t){c.addEventListener(t,v)});if(c.form)c.form.addEventListener('submit',function(a){if(!v())a.preventDefault()})})(document.currentScript.previousElementSibling);</script>

        <input class="modal-input checkout-wide" name="correo" type="email" value="<?= htmlspecialchars($_SESSION['usuario_email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Correo electrónico" required maxlength="120"><script>(function(c){if(!c||c.dataset.validacionCampo)return;c.dataset.validacionCampo='1';var e=c.nextElementSibling&&c.nextElementSibling.classList.contains('field-error-inline')?c.nextElementSibling:document.createElement('small');e.className='field-error-inline';e.style.cssText='display:block;color:#c62828;font-size:12px;margin-top:4px;min-height:1em;';if(!e.parentNode)c.insertAdjacentElement('afterend',e);function v(){var x=c.required&&!c.value.trim(),i=x||!c.validity.valid;e.textContent=i?(x?'Este campo es obligatorio.':c.validationMessage):'';c.setAttribute('aria-invalid',i?'true':'false');return!i}['input','change','blur'].forEach(function(t){c.addEventListener(t,v)});if(c.form)c.form.addEventListener('submit',function(a){if(!v())a.preventDefault()})})(document.currentScript.previousElementSibling);</script>
        <input class="modal-input checkout-wide" name="direccion" value="<?= htmlspecialchars($_SESSION['usuario_direccion'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Dirección de entrega" required maxlength="255"><script>(function(c){if(!c||c.dataset.validacionCampo)return;c.dataset.validacionCampo='1';var e=c.nextElementSibling&&c.nextElementSibling.classList.contains('field-error-inline')?c.nextElementSibling:document.createElement('small');e.className='field-error-inline';e.style.cssText='display:block;color:#c62828;font-size:12px;margin-top:4px;min-height:1em;';if(!e.parentNode)c.insertAdjacentElement('afterend',e);function v(){var x=c.required&&!c.value.trim(),i=x||!c.validity.valid;e.textContent=i?(x?'Este campo es obligatorio.':c.validationMessage):'';c.setAttribute('aria-invalid',i?'true':'false');return!i}['input','change','blur'].forEach(function(t){c.addEventListener(t,v)});if(c.form)c.form.addEventListener('submit',function(a){if(!v())a.preventDefault()})})(document.currentScript.previousElementSibling);</script>
        <input class="modal-input checkout-wide" name="localidad" value="<?= htmlspecialchars($_SESSION['usuario_localidad'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Localidad (opcional)" maxlength="60"><script>(function(c){if(!c||c.dataset.validacionCampo)return;c.dataset.validacionCampo='1';var e=c.nextElementSibling&&c.nextElementSibling.classList.contains('field-error-inline')?c.nextElementSibling:document.createElement('small');e.className='field-error-inline';e.style.cssText='display:block;color:#c62828;font-size:12px;margin-top:4px;min-height:1em;';if(!e.parentNode)c.insertAdjacentElement('afterend',e);function v(){var x=c.required&&!c.value.trim(),i=x||!c.validity.valid;e.textContent=i?(x?'Este campo es obligatorio.':c.validationMessage):'';c.setAttribute('aria-invalid',i?'true':'false');return!i}['input','change','blur'].forEach(function(t){c.addEventListener(t,v)});if(c.form)c.form.addEventListener('submit',function(a){if(!v())a.preventDefault()})})(document.currentScript.previousElementSibling);</script>
        <select class="modal-input checkout-wide" name="metodo_pago" required>
          <option value="" disabled selected>Método de pago</option>
          <option value="Contraentrega">Contraentrega (efectivo)</option>
          <option value="Transferencia">Transferencia bancaria</option>
          <option value="Nequi">Nequi</option>
          <option value="Daviplata">Daviplata</option>
        </select><script>(function(c){if(!c||c.dataset.validacionCampo)return;c.dataset.validacionCampo='1';var e=c.nextElementSibling&&c.nextElementSibling.classList.contains('field-error-inline')?c.nextElementSibling:document.createElement('small');e.className='field-error-inline';e.style.cssText='display:block;color:#c62828;font-size:12px;margin-top:4px;min-height:1em;';if(!e.parentNode)c.insertAdjacentElement('afterend',e);function v(){var x=c.required&&!c.value.trim(),i=x||!c.validity.valid;e.textContent=i?(x?'Este campo es obligatorio.':c.validationMessage):'';c.setAttribute('aria-invalid',i?'true':'false');return!i}['input','change','blur'].forEach(function(t){c.addEventListener(t,v)});if(c.form)c.form.addEventListener('submit',function(a){if(!v())a.preventDefault()})})(document.currentScript.previousElementSibling);</script>
      </div>
      <p id="checkoutError" class="login-error" hidden></p>
      <button class="btn-modal-primary" type="submit">Registrar pedido</button>
    </form>
  </div>
</div>
