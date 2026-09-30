<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';

$currentRol = strtolower((string)($_SESSION['usuario_rol'] ?? ''));
if (!empty($_SESSION['id_usuario']) && !empty($_SESSION['business_id']) && $currentRol === 'negocio') {
    header('Location: ../pages/dashboard.php');
    exit;
}

$error = '';
$registered = !empty($_GET['registered']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'La sesion del formulario expiro. Intenta nuevamente.';
    } else {
        $email = filter_var(trim((string)($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL);
        $password = (string)($_POST['password'] ?? '');

        if (!$email || strlen($password) < 8 || strlen($password) > 255) {
            $error = 'Credenciales inválidas, inténtalo de nuevo';
        } else {
            try {
                $account = Usuario::autenticar($email, $password);
                $business = $account ? Usuario::negocio((int) $account['id_usuario']) : null;

                if (!$account || !$business || strtolower((string) $account['rol']) !== 'negocio') {
                    throw new RuntimeException('Credenciales inválidas, inténtalo de nuevo');
                }

                session_regenerate_id(true);
                $_SESSION['id_usuario'] = (int) $account['id_usuario'];
                $_SESSION['usuario_nombre'] = (string) $account['nombre'];
                $_SESSION['usuario_apellido'] = (string) ($account['apellido'] ?? '');
                $_SESSION['usuario_email'] = (string) $account['email'];
                $_SESSION['usuario_rol'] = strtolower((string) $account['rol']);
                $_SESSION['business_id'] = (int) $business['id_negocio'];
                header('Location: ../pages/dashboard.php');
                exit;
            } catch (Throwable $e) {
                $error = 'Credenciales inválidas, inténtalo de nuevo';
            }
        }
    }
}

$csrfToken = csrf_token();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Iniciar sesion | SHIZEN Negocio</title>
  <meta name="robots" content="noindex, nofollow">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/app.css">
</head>
<body>
<div class="login-page">

  <!-- Panel izquierdo con imagen de fondo y tarjeta estetica -->
  <div class="login-left" style="justify-content:flex-end;padding-bottom:48px">
    <div style="background:rgba(0,0,0,0.52);backdrop-filter:blur(10px);-webkit-backdrop-filter:blur(10px);border:1px solid rgba(255,255,255,0.18);border-radius:20px;padding:32px 28px;box-shadow:0 10px 30px rgba(0,0,0,0.3)">
      
      <h1 style="font-size:25px;font-weight:800;color:#ffffff;line-height:1.3;margin-bottom:10px;letter-spacing:-0.3px">
        Gestiona tu negocio con <span style="color:#6ee7b7">inteligencia</span>
      </h1>
      
      <p style="font-size:14px;color:rgba(255,255,255,0.85);line-height:1.6;margin-bottom:22px;font-weight:400">
        Plataforma integral para restaurantes vegetarianos y veganos. Controla pedidos, productos y clientes desde un solo lugar.
      </p>

      <div style="display:flex;flex-direction:column;gap:10px">
        <div style="display:flex;align-items:center;gap:12px;background:rgba(255,255,255,0.08);padding:10px 14px;border-radius:10px;border:1px solid rgba(255,255,255,0.08)">
          <div style="width:30px;height:30px;border-radius:8px;background:rgba(110,231,183,0.2);color:#6ee7b7;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0">
            <i class="bx bx-line-chart"></i>
          </div>
          <span style="font-size:13.5px;color:#ffffff;font-weight:500">Panel de control en tiempo real</span>
        </div>

        <div style="display:flex;align-items:center;gap:12px;background:rgba(255,255,255,0.08);padding:10px 14px;border-radius:10px;border:1px solid rgba(255,255,255,0.08)">
          <div style="width:30px;height:30px;border-radius:8px;background:rgba(110,231,183,0.2);color:#6ee7b7;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0">
            <i class="bx bx-package"></i>
          </div>
          <span style="font-size:13.5px;color:#ffffff;font-weight:500">Gestion de productos y stock</span>
        </div>

        <div style="display:flex;align-items:center;gap:12px;background:rgba(255,255,255,0.08);padding:10px 14px;border-radius:10px;border:1px solid rgba(255,255,255,0.08)">
          <div style="width:30px;height:30px;border-radius:8px;background:rgba(110,231,183,0.2);color:#6ee7b7;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0">
            <i class="bx bx-dish"></i>
          </div>
          <span style="font-size:13.5px;color:#ffffff;font-weight:500">Seguimiento de pedidos en tiempo real</span>
        </div>

        <div style="display:flex;align-items:center;gap:12px;background:rgba(255,255,255,0.08);padding:10px 14px;border-radius:10px;border:1px solid rgba(255,255,255,0.08)">
          <div style="width:30px;height:30px;border-radius:8px;background:rgba(110,231,183,0.2);color:#6ee7b7;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0">
            <i class="bx bx-group"></i>
          </div>
          <span style="font-size:13.5px;color:#ffffff;font-weight:500">Historial de clientes</span>
        </div>

        <div style="display:flex;align-items:center;gap:12px;background:rgba(255,255,255,0.08);padding:10px 14px;border-radius:10px;border:1px solid rgba(255,255,255,0.08)">
          <div style="width:30px;height:30px;border-radius:8px;background:rgba(110,231,183,0.2);color:#6ee7b7;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0">
            <i class="bx bx-pie-chart-alt-2"></i>
          </div>
          <span style="font-size:13.5px;color:#ffffff;font-weight:500">Reportes y estadisticas</span>
        </div>
      </div>

    </div>
  </div>

  <!-- Panel derecho con formulario -->
  <div class="login-right">
    <div class="login-form-wrap">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
        <a href="/shizenhome/index.php" style="display:inline-flex;align-items:center;gap:6px;color:#6b7280;font-size:13px;font-weight:600;text-decoration:none;transition:color .15s" onmouseover="this.style.color='#2e7d32'" onmouseout="this.style.color='#6b7280'">
          <i class="bx bx-left-arrow-alt" style="font-size:18px"></i> Volver a Shizen
        </a>
      </div>

      <div style="margin-bottom:20px;text-align:center">
        <img src="../assets/logo.png" alt="SHIZEN Negocio" style="height:48px;width:auto;display:inline-block;filter:brightness(0)">
      </div>
      <h2 class="login-form-title">Bienvenido de vuelta</h2>
      <p class="login-form-sub">Ingresa tus credenciales para continuar</p>

      <?php if ($registered): ?>
        <div class="alert alert-success">
          <i class="bx bx-check-circle" style="font-size:18px;flex-shrink:0;margin-top:1px"></i>
          Cuenta creada exitosamente. Ya puedes iniciar sesión.
        </div>
      <?php endif; ?>

      <?php if ($error): ?>
        <div class="alert alert-danger">
          <i class="bx bx-error-circle" style="font-size:18px;flex-shrink:0;margin-top:1px"></i>
          <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
      <?php endif; ?>

      <form method="post" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

        <div class="form-group">
          <label class="form-label" for="email">Correo electrónico</label>
          <div class="input-icon-wrap">
            <i class="bx bx-envelope"></i>
            <input type="email" id="email" name="email" class="form-control"
                   placeholder="tu@correo.com"
                   value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                   required>
<script>
(function () {
  const field = document.currentScript.previousElementSibling;
  const message = document.createElement('small');
  message.className = 'field-error-inline';
  message.style.cssText = 'display:block;color:#dc2626;font-size:12px;margin-top:6px';
  field.insertAdjacentElement('afterend', message);
  function validate() {
    const valid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(field.value.trim());
    field.setCustomValidity(valid ? '' : 'Ingresa un correo válido.');
    message.textContent = field.dataset.touched === 'true' && !valid ? 'Ingresa un correo válido.' : '';
  }
  field.addEventListener('input', function () { field.dataset.touched = 'true'; validate(); });
  field.addEventListener('blur', function () { field.dataset.touched = 'true'; validate(); });
  field.form.addEventListener('submit', function () { field.dataset.touched = 'true'; validate(); });
}());
</script>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="password">Contraseña</label>
          <div class="input-icon-wrap">
            <i class="bx bx-lock-alt"></i>
            <input type="password" id="password" name="password" class="form-control"
                   placeholder="••••••••" required minlength="8">
<script>
(function () {
  const field = document.currentScript.previousElementSibling;
  const message = document.createElement('small');
  message.className = 'field-error-inline';
  message.style.cssText = 'display:block;color:#dc2626;font-size:12px;margin-top:6px';
  field.insertAdjacentElement('afterend', message);
  function validate() {
    const empty = field.required && !field.value.trim();
    const inv = empty || field.value.length < 8;
    field.setCustomValidity(inv ? (empty ? 'Este campo es obligatorio.' : 'La contraseña debe tener al menos 8 caracteres.') : '');
    message.textContent = field.dataset.touched === 'true' && inv ? (empty ? 'Este campo es obligatorio.' : 'La contraseña debe tener al menos 8 caracteres.') : '';
  }
  field.addEventListener('input', function () { field.dataset.touched = 'true'; validate(); });
  field.addEventListener('blur', function () { field.dataset.touched = 'true'; validate(); });
  field.form.addEventListener('submit', function () { field.dataset.touched = 'true'; validate(); });
}());
</script>
          </div>
        </div>

        <button type="submit" class="btn btn-primary btn-lg" style="margin-top:8px">
          <i class="bx bx-log-in"></i>
          Iniciar sesión
        </button>
      </form>

      <div style="margin-top:18px;padding-top:16px;border-top:1px solid #f3f4f6;text-align:center">
        <p class="login-link" style="margin:0 0 8px">
          ¿No tienes cuenta? <a href="/shizenhome/forms/registro_negocio.html" style="font-weight:700">Regístrate aquí</a>
        </p>

        <p style="font-size:11.5px;color:#9ca3af;margin:0;line-height:1.4">
          Al iniciar sesión, aceptas nuestros <a href="/shizenhome/legal/terminos.html" target="_blank" rel="noopener noreferrer" style="color:#2e7d32;font-weight:600;text-decoration:underline">Términos y Condiciones</a>.
        </p>
      </div>
    </div>
  </div>

</div>
</body>
</html>
