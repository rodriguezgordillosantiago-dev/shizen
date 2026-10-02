<?php
declare(strict_types=1);

require_once __DIR__ . '/../clases/Negocio.php';
require_once __DIR__ . '/../clases/FileManager.php';
require_once __DIR__ . '/../clases/Validador.php';
session_name('SHIZEN_CLIENTE_SESSION');
session_start();

$registro = $_SESSION['registro_negocio'] ?? null;
if (!$registro) {
    header('Location: registro_negocio.php');
    exit;
}

$errores = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $validador = new Validador($_POST);
    $validador->requerido('cedula')
              ->patron('cedula', '/^[0-9]{6,12}$/', 'La cédula debe tener entre 6 y 12 dígitos.');

    $fileManager = new FileManager();

    // Validar documentos obligatorios en PDF
    $requeridos = ['rut', 'documento_identidad_representante', 'certificado_bancario'];
    foreach ($requeridos as $campo) {
        $archivo = $_FILES[$campo] ?? null;
        if (!$archivo || ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !$fileManager->esPdfValido($archivo)) {
            $validador->agregarError($campo, 'Adjunta todos los documentos requeridos (RUT, Identidad y Certificado bancario) en PDF válido.');
            break;
        }
    }

    // Cámara de Comercio (opcional)
    $camara = $_FILES['certificado_camara_comercio'] ?? null;
    if ($camara && ($camara['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK && !$fileManager->esPdfValido($camara)) {
        $validador->agregarError('certificado_camara_comercio', 'El certificado de Cámara de Comercio debe ser un archivo PDF válido.');
    }

    // Logo obligatorio
    $logo = $_FILES['logo_negocio'] ?? null;
    if (!$logo || ($logo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !$fileManager->esImagenValida($logo)) {
        $validador->agregarError('logo_negocio', 'Debes subir un logo válido (JPG, PNG o WEBP).');
    }

    if ($validador->esValido()) {
        try {
            $cedula = (string)$validador->obtener('cedula');
            $nombreNegocio = (string)($registro['nombre'] ?? $registro['nombre_negocio'] ?? 'negocio');
            $identificador = $nombreNegocio . '_' . $cedula;

            $rutUrl = $fileManager->guardar($_FILES['rut'], 'legales/negocio', 'rut', $identificador, 'pdf');
            $docIdUrl = $fileManager->guardar($_FILES['documento_identidad_representante'], 'legales/negocio', 'documento_identidad_representante', $identificador, 'pdf');
            $certBancarioUrl = $fileManager->guardar($_FILES['certificado_bancario'], 'legales/negocio', 'certificado_bancario', $identificador, 'pdf');

            $certCamaraUrl = ($camara && ($camara['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK)
                ? $fileManager->guardar($camara, 'legales/negocio', 'certificado_camara_comercio', $identificador, 'pdf')
                : null;

            $extLogo = $fileManager->obtenerExtensionImagen($logo);
            $logoUrl = $fileManager->guardar($logo, 'legales/negocio', 'logo_negocio', $identificador, $extLogo);

            $negocio = new Negocio(
                nombreNegocio: $nombreNegocio,
                email: (string)$registro['email'],
                password: (string)$registro['password'],
                direccion: (string)$registro['direccion'],
                cedula: $cedula,
                horaApertura: (string)($registro['horaApertura'] ?? '08:00'),
                horaCierre: (string)($registro['horaCierre'] ?? '20:00')
            );

            $negocio->setDocumentos(
                logoUrl: $logoUrl,
                rutUrl: $rutUrl,
                documentoIdentidadUrl: $docIdUrl,
                certificadoBancarioUrl: $certBancarioUrl,
                certificadoCamaraUrl: $certCamaraUrl
            );

            $negocio->registrarNegocio();

            unset($_SESSION['registro_negocio']);
            header('Location: registro_exitoso.php');
            exit;

        } catch (Throwable $e) {
            error_log('Error en registro de negocio: ' . $e->getMessage());
            $errores[] = 'No fue posible completar el registro. Inténtalo de nuevo.';
        }
    } else {
        $errores = $validador->obtenerErrores();
    }
}
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Documentos del negocio | Shizen</title><link rel="stylesheet" href="../css/styles.css"><link rel="stylesheet" href="../css/registro_base.css"></head><body><main>
<?php if ($errores): ?><p class="form-server-error"><?= htmlspecialchars(implode(' ', $errores), ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
<?php readfile(__DIR__ . '/../forms/documentos_negocio.html'); ?>
</main></body></html>
 
