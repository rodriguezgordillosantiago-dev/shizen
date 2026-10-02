<?php
declare(strict_types=1);

require_once __DIR__ . '/../clases/Validador.php';
require_once __DIR__ . '/../clases/Usuario.php';
session_name('SHIZEN_CLIENTE_SESSION');
session_start();

$errores = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $validador = new Validador($_POST);
  $validador->requerido('nombre', 'apellido', 'vehiculo', 'direccion', 'email', 'password', 'password_confirmation', 'terminos')
            ->patron('nombre', '/^[\p{L} .\x27-]{2,}$/u', 'Ingresa un nombre válido.')
            ->patron('apellido', '/^[\p{L} .\x27-]{2,}$/u', 'Ingresa un apellido válido.')
            ->enLista('vehiculo', ['Bicicleta', 'Moto', 'Patineta electrica', 'Carro'], 'Selecciona un vehículo válido.')
            ->patron('direccion', '/^[\p{L}0-9 .,#\x27-]{5,150}$/u', 'Ingresa una dirección válida.')
            ->email('email')
            ->longitudMinima('password', 8, 'La contraseña debe tener un mínimo de 8 caracteres.')
            ->coinciden('password', 'password_confirmation', 'Las contraseñas no coinciden.');

  if ($validador->esValido()) {
    $email = (string)$validador->obtener('email');
    if (Usuario::emailExiste($email)) {
      $errores[] = 'Ese correo ya está registrado.';
    } else {
      $nombre = (string)$validador->obtener('nombre');
      $apellido = (string)$validador->obtener('apellido');
      $vehiculo = (string)$validador->obtener('vehiculo');
      $direccion = (string)$validador->obtener('direccion');
      $password = (string)($_POST['password'] ?? '');

      $_SESSION['registro_repartidor'] = compact('nombre', 'apellido', 'vehiculo', 'direccion', 'email', 'password');
      header('Location: documentos_repartidor.php');
      exit;
    }
  } else {
    $errores = $validador->obtenerErrores();
  }
}
?>
<!DOCTYPE html>
<html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Registro de repartidor | Shizen</title><link rel="stylesheet" href="../css/styles.css"><link rel="stylesheet" href="../css/registro_base.css"><link rel="stylesheet" href="../css/registro_repartidor.css"><style>.view { display: block; }</style></head><body><main>
<?php if ($errores): ?><p class="form-server-error"><?= htmlspecialchars(implode(' ', $errores), ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
<?php readfile(__DIR__ . '/../forms/registro_repartidor.html'); ?>
</main></body></html>
