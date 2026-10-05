<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_name('SHIZEN_CLIENTE_SESSION');
    session_start();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/../clases/Categoria.php';
require_once __DIR__ . '/../clases/Plato.php';
require_once __DIR__ . '/../funciones/funciones.php';

$rawCategoria = $_GET['categoria'] ?? '';
$orden = trim((string) ($_GET['orden'] ?? 'relevance'));
if (!in_array($orden, ['relevance', 'low', 'high'], true)) {
    $orden = 'relevance';
}
$categoria = null;
$platos = [];

try {
    $categoria = Categoria::buscar($rawCategoria);
    $categoriaId = (int) ($categoria['id'] ?? 0);

    if ($categoriaId > 0) {
        $platos = Plato::obtenerPorCategoria($categoriaId, $orden);
    }
} catch (Throwable $e) {
    error_log('Error al cargar categoría en POO: ' . $e->getMessage());
    $categoria = null;
    $platos = [];
}

include __DIR__ . '/../forms/categorias.php';
?>