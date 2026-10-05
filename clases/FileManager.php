<?php
declare(strict_types=1);

/**
 * FileManager
 * ---------------------------------------------------------
 * Gestión orientada a objetos para validar, sanitizar y
 * almacenar archivos subidos (PDFs e imágenes).
 * ---------------------------------------------------------
 */
class FileManager
{
    private string $basePath;

    public function __construct(?string $basePath = null)
    {
        $this->basePath = $basePath ?? (__DIR__ . '/..');
    }

    /** Valida si el archivo subido es un PDF real */
    public function esPdfValido(array $archivo): bool
    {
        if (!isset($archivo['tmp_name']) || !is_uploaded_file($archivo['tmp_name'])) {
            return false;
        }
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($archivo['tmp_name']);
        if ($mime !== 'application/pdf') {
            return false;
        }
        // Verificar cabecera mágica de PDF
        return file_get_contents($archivo['tmp_name'], false, null, 0, 5) === '%PDF-';
    }

    /** Valida si el archivo subido es una imagen real (JPG, PNG, WEBP) */
    public function esImagenValida(array $archivo): bool
    {
        if (!isset($archivo['tmp_name']) || !is_uploaded_file($archivo['tmp_name'])) {
            return false;
        }
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($archivo['tmp_name']);
        return in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true);
    }

    /** Obtiene la extensión recomendada a partir del tipo MIME de la imagen */
    public function obtenerExtensionImagen(array $archivo): string
    {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($archivo['tmp_name']);
        $mapa = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];
        return $mapa[$mime] ?? 'jpg';
    }

    /** Convierte un texto en un slug seguro para nombres de carpetas y archivos */
    public function nombreSeguro(string $texto): string
    {
        $texto = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto) ?: $texto;
        $texto = strtolower((string) preg_replace('/[^a-zA-Z0-9]+/', '_', $texto));
        return trim($texto, '_') ?: 'archivo';
    }

    /**
     * Guarda un archivo en el directorio correspondiente y retorna su ruta relativa.
     */
    public function guardar(
        array $archivo,
        string $carpetaRelativa,
        string $tipo,
        string $identificador,
        ?string $extension = null
    ): string {
        $ext = $extension ?? (pathinfo($archivo['name'] ?? '', PATHINFO_EXTENSION) ?: 'pdf');
        $idSeguro = $this->nombreSeguro($identificador);
        $directorioDestino = $this->basePath . '/' . trim($carpetaRelativa, '/') . '/' . $idSeguro;

        if (!is_dir($directorioDestino) && !mkdir($directorioDestino, 0755, true) && !is_dir($directorioDestino)) {
            throw new RuntimeException("No fue posible crear el directorio de destino: {$directorioDestino}");
        }

        $nombreArchivo = sprintf('%s_%s_%s.%s', $idSeguro, $tipo, date('YmdHis'), $ext);
        $rutaCompleta = $directorioDestino . '/' . $nombreArchivo;

        if (!move_uploaded_file($archivo['tmp_name'], $rutaCompleta)) {
            throw new RuntimeException("No fue posible guardar el archivo: {$tipo}");
        }

        return trim($carpetaRelativa, '/') . '/' . $idSeguro . '/' . $nombreArchivo;
    }
}