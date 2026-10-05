<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_name('SHIZEN_CLIENTE_SESSION');
    session_start();
}

require_once __DIR__ . '/../clases/Plato.php';
require_once __DIR__ . '/../clases/Categoria.php';
require_once __DIR__ . '/../funciones/funciones.php';

$categoriaFiltro = isset($_GET['categoria']) ? (int)$_GET['categoria'] : 0;

try {
    $promociones = Plato::obtenerPromocionesActivas();
    if ($categoriaFiltro > 0) {
        $promociones = array_values(array_filter($promociones, function ($p) use ($categoriaFiltro) {
            return (int)($p['id_categoria'] ?? 0) === $categoriaFiltro;
        }));
    }
    foreach ($promociones as &$p) {
        $p['imagen_url'] = resolverImagenUrl($p['imagen_url'] ?? '');
    }
    unset($p);

    $categorias = Categoria::obtenerTodas();
} catch (Throwable $e) {
    error_log('Error cargando promociones: ' . $e->getMessage());
    $promociones = [];
    $categorias = [];
}

// Llamamos a la vista que dibuja el diseño
include __DIR__ . '/../forms/promociones.php';
?>
