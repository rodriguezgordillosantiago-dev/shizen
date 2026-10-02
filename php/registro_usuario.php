<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
  session_name('SHIZEN_CLIENTE_SESSION');
  session_start();
}

require_once __DIR__ . '/../clases/Validador.php';
require_once __DIR__ . '/../clases/Usuario.php';

$csrfToken = $_SESSION['registro_usuario_csrf'] ?? bin2hex(random_bytes(32));
$_SESSION['registro_usuario_csrf'] = $csrfToken;
$errores = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $token = (string)($_POST['csrf_token'] ?? '');
  if (!hash_equals($csrfToken, $token)) {
    http_response_code(403);
    exit('Solicitud no válida.');
  }

  $validador = new Validador($_POST);
  $validador->requerido('nombre', 'apellido', 'email', 'password', 'password_confirmation', 'terminos')
            ->patron('nombre', '/^[\p{L} .\x27-]{2,}$/u', 'Ingresa un nombre válido.')
            ->patron('apellido', '/^[\p{L} .\x27-]{2,}$/u', 'Ingresa un apellido válido.')
            ->email('email')
            ->longitudMinima('password', 8, 'La contraseña debe tener un mínimo de 8 caracteres.')
            ->coinciden('password', 'password_confirmation', 'Las contraseñas no coinciden.');

  if ($validador->esValido()) {
    try {
      $usuario = new Usuario(
        nombre: $validador->obtener('nombre'),
        apellido: $validador->obtener('apellido'),
        email: $validador->obtener('email'),
        password: (string)($_POST['password'] ?? '')
      );
      $usuario->registrar();
      unset($_SESSION['registro_usuario_csrf']);
      header('Location: registro_exitoso.php');
      exit;
    } catch (PDOException $e) {
      error_log('Error al crear cuenta de usuario: ' . $e->getMessage());
      $errores[] = $e->getCode() === '23000' ? 'Este correo ya está registrado.' : 'No fue posible crear la cuenta.';
    } catch (RuntimeException $e) {
      error_log('Error de conexión al crear cuenta de usuario: ' . $e->getMessage());
      $errores[] = 'No fue posible conectar con la base de datos.';
    }
  } else {
    $errores = $validador->obtenerErrores();
  }
}
?>
<!DOCTYPE html>
<html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Registro de usuario | Shizen</title><link rel="stylesheet" href="../css/styles.css"><link rel="stylesheet" href="../css/registro_base.css"><link rel="stylesheet" href="../css/registro_usuario.css"><style>.view { display: block; }</style></head><body><main>
<?php if ($errores): ?><p class="form-server-error"><?= htmlspecialchars(implode(' ', $errores), ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
<?php
  $formulario = file_get_contents(__DIR__ . '/../forms/registro_usuario.html');
  echo str_replace('{{CSRF_TOKEN}}', htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'), $formulario);
?>
</main></body></html>
