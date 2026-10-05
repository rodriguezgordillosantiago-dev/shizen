<?php
declare(strict_types=1);

require_once __DIR__ . '/../clases/Repartidor.php';
require_once __DIR__ . '/../clases/FileManager.php';
require_once __DIR__ . '/../clases/Validador.php';
session_name('SHIZEN_CLIENTE_SESSION');
session_start();

$registro = $_SESSION['registro_repartidor'] ?? null;
if (!$registro) {
    header('Location: registro_repartidor.php');
    exit;
}

$esMotorizado = in_array($registro['vehiculo'] ?? '', ['Moto', 'Carro'], true);
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $validador = new Validador($_POST);
    $validador->requerido('cedula')
        ->patron('cedula', '/^[0-9]{6,12}$/', 'La cédula debe tener entre 6 y 12 dígitos.');

    $fileManager = new FileManager();

    $requeridos = $esMotorizado
        ? ['documento_cedula', 'licencia_conduccion', 'tarjeta_propiedad', 'soat']
        : ['documento_cedula'];

    foreach ($requeridos as $nombre) {
        $archivo = $_FILES[$nombre] ?? null;
        if (!$archivo || ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !$fileManager->esPdfValido($archivo)) {
            $validador->agregarError($nombre, 'Adjunta todos los documentos requeridos en PDF válido.');
            break;
        }
    }

    $foto = $_FILES['foto_repartidor'] ?? null;
    if (!$foto || ($foto['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !$fileManager->esImagenValida($foto)) {
        $validador->agregarError('foto_repartidor', 'Debes subir una foto de perfil válida (JPG, PNG o WEBP).');
    }

    if ($validador->esValido()) {
        try {
            $cedula = (string) $validador->obtener('cedula');
            $identificador = ($registro['nombre'] ?? 'repartidor') . '_' . ($registro['apellido'] ?? '') . '_' . $cedula;

            $cedulaUrl = $fileManager->guardar($_FILES['documento_cedula'], 'legales/repartidor', 'cedula', $identificador, 'pdf');
            $licenciaUrl = $esMotorizado ? $fileManager->guardar($_FILES['licencia_conduccion'], 'legales/repartidor', 'licencia', $identificador, 'pdf') : null;
            $soatUrl = $esMotorizado ? $fileManager->guardar($_FILES['soat'], 'legales/repartidor', 'soat', $identificador, 'pdf') : null;
            $tarjetaUrl = $esMotorizado ? $fileManager->guardar($_FILES['tarjeta_propiedad'], 'legales/repartidor', 'tarjeta_propiedad', $identificador, 'pdf') : null;

            $extFoto = $fileManager->obtenerExtensionImagen($foto);
            $fotoUrl = $fileManager->guardar($foto, 'legales/repartidor', 'foto_repartidor', $identificador, $extFoto);

            $repartidor = new Repartidor(
                nombre: (string) $registro['nombre'],
                apellido: (string) $registro['apellido'],
                email: (string) $registro['email'],
                password: (string) $registro['password'],
                vehiculo: (string) $registro['vehiculo'],
                direccion: (string) $registro['direccion'],
                cedula: $cedula
            );

            $repartidor->setDocumentos(
                fotoUrl: $fotoUrl,
                cedulaDocumentoUrl: $cedulaUrl,
                licenciaConduccionUrl: $licenciaUrl,
                soatUrl: $soatUrl,
                tarjetaPropiedadUrl: $tarjetaUrl
            );

            $repartidor->registrarRepartidor();

            unset($_SESSION['registro_repartidor']);
            header('Location: registro_exitoso.php');
            exit;

        } catch (Throwable $e) {
            error_log('Error en registro de repartidor: ' . $e->getMessage());
            $errores[] = 'No fue posible guardar la solicitud. Inténtalo de nuevo.';
        }
    } else {
        $errores = $validador->obtenerErrores();
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documentos del repartidor | Shizen</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/registro_base.css">
</head>

<body>
    <main>
        <?php if ($errores): ?>
            <p class="form-server-error">
                <?= htmlspecialchars(implode(' ', $errores), ENT_QUOTES, 'UTF-8') ?>
            </p><?php endif; ?>
        <?php require __DIR__ . '/../forms/documentos_repartidor.php'; ?>
    </main>
</body>

</html>