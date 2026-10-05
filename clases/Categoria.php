<?php
declare(strict_types=1);

require_once __DIR__ . '/Database.php';

/**
 * Categoria
 * ---------------------------------------------------------
 * Modelo de Categoría para consulta y gestión del catálogo.
 * ---------------------------------------------------------
 */
class Categoria
{
    /** Obtiene todas las categorías registradas */
    public static function obtenerTodas(): array
    {
        $pdo = Database::getConnection();
        return $pdo->query('SELECT *, id_categoria AS id FROM categorias ORDER BY id_categoria ASC')->fetchAll();
    }

    /** Busca una categoría por ID numérico o nombre/slug */
    public static function buscar(string|int $termino): ?array
    {
        $pdo = Database::getConnection();

        if (is_numeric($termino) && (int) $termino > 0) {
            $stmt = $pdo->prepare('SELECT *, id_categoria AS id FROM categorias WHERE id_categoria = ?');
            $stmt->execute([(int) $termino]);
            $resultado = $stmt->fetch();
            if ($resultado)
                return $resultado;
        }

        if (is_string($termino) && trim($termino) !== '') {
            $stmt = $pdo->prepare('SELECT *, id_categoria AS id FROM categorias WHERE LOWER(nombre) LIKE ? LIMIT 1');
            $stmt->execute(['%' . strtolower(trim($termino)) . '%']);
            $resultado = $stmt->fetch();
            if ($resultado)
                return $resultado;
        }

        // Si no se encuentra, retornar la primera disponible
        $primera = $pdo->query('SELECT *, id_categoria AS id FROM categorias ORDER BY id_categoria ASC LIMIT 1')->fetch();
        return $primera ?: null;
    }
}