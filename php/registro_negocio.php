<?php
declare(strict_types=1);

require_once __DIR__ . '/../clases/Validador.php';
require_once __DIR__ . '/../clases/Usuario.php';
session_name('SHIZEN_CLIENTE_SESSION');
session_start();

$errores = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $validador = new Validador($_POST);
  $validador->requerido('nombre', 'direccion', 'hora_apertura', 'hora_cierre', 'email', 'password', 'password_confirmation', 'terminos')
            ->patron('nombre', '/^[\p{L}0-9 .,&\x27-]{2,}$/u', 'Ingresa un nombre de negocio válido.')
            ->patron('direccion', '/^[\p{L}0-9 .,#\x27-]{5,150}$/u', 'Ingresa una dirección válida.')
            ->patron('hora_apertura', '/^(?:[01]\d|2[0-3]):[0-5]\d$/', 'Ingresa una hora de apertura válida.')
            ->patron('hora_cierre', '/^(?:[01]\d|2[0-3]):[0-5]\d$/', 'Ingresa una hora de cierre válida.')
            ->email('email')
            ->longitudMinima('password', 8, 'La contraseña debe tener al menos 8 caracteres.')
            ->coinciden('password', 'password_confirmation', 'Las contraseñas no coinciden.');

  if ($validador->esValido()) {
    $email = (string)$validador->obtener('email');
    if (Usuario::emailExiste($email)) {
      $errores[] = 'Este correo ya se encuentra en uso.';
    } else {
      $nombre = (string)$validador->obtener('nombre');
      $nombre_negocio = $nombre;
      $direccion = (string)$validador->obtener('direccion');
      $horaApertura = (string)$validador->obtener('hora_apertura');
      $horaCierre = (string)$validador->obtener('hora_cierre');
      $password = (string)($_POST['password'] ?? '');

      $_SESSION['registro_negocio'] = compact('nombre', 'nombre_negocio', 'direccion', 'horaApertura', 'horaCierre', 'email', 'password');
      header('Location: documentos_negocio.php');
      exit;
    }
  } else {
    $errores = $validador->obtenerErrores();
  }
}
?>
<!DOCTYPE html>
<html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Registro de negocio | Shizen</title><link rel="stylesheet" href="../css/styles.css"><link rel="stylesheet" href="../css/registro_base.css"><link rel="stylesheet" href="../css/registro_negocio.css"><style>.view { display: block; }</style></head><body><main>
<?php if ($errores): ?><p class="form-server-error"><?= htmlspecialchars(implode(' ', $errores), ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
<?php readfile(__DIR__ . '/../forms/registro_negocio.html'); ?>
</main></body></html>
