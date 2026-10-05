<?php
declare(strict_types=1);

require_once __DIR__ . '/Database.php';

/**
 * Plato
 * ---------------------------------------------------------
 * Modelo de Plato / Producto del menú y promociones.
 * ---------------------------------------------------------
 */
class Plato {
    /** Obtiene los platos asociados a una categoría con orden opcional */
    public static function obtenerPorCategoria(int $categoriaId, string $orden = 'relevance'): array {
        if ($categoriaId <= 0) return [];

        $orderBy = match ($orden) {
            'low'  => 'COALESCE(NULLIF(m.precio_promocion, 0), m.precio) ASC, m.nombre ASC',
            'high' => 'COALESCE(NULLIF(m.precio_promocion, 0), m.precio) DESC, m.nombre ASC',
            default => 'm.nombre ASC'
        };

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT
                m.id_menu_item AS id,
                m.nombre        AS plato_nombre,
                m.descripcion   AS plato_desc,
                m.precio,
                m.stock,
                m.imagen_url,
                m.on_promo,
                m.precio_promocion,
                n.id_negocio AS negocio_id,
                COALESCE(n.nombre, 'Restaurante Shizen') AS negocio_nombre
            FROM menu_items m
            LEFT JOIN negocios n ON m.id_negocio = n.id_negocio
            WHERE m.id_categoria = ?
            ORDER BY {$orderBy}
        ");
        $stmt->execute([$categoriaId]);
        return $stmt->fetchAll();
    }

    /** Obtiene todas las promociones activas desde la tabla promociones */
    public static function obtenerPromocionesActivas(): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('
            SELECT 
                p.id_promocion AS id,
                p.id_menu_item,
                COALESCE(p.id_negocio, m.id_negocio) AS id_negocio,
                COALESCE(p.nombre, m.nombre) AS promo_nombre,
                COALESCE(p.descripcion, m.descripcion) AS promo_desc,
                COALESCE(p.imagen_url, m.imagen_url) AS imagen_url,
                m.precio,
                COALESCE(m.precio_promocion, m.precio) AS precio_promocion,
                m.id_categoria,
                c.nombre AS categoria_nombre,
                c.icon AS categoria_icono,
                p.fecha_inicio,
                p.fecha_fin,
                COALESCE(n.nombre, \'Restaurante Shizen\') AS negocio_nombre
            FROM promociones p
            LEFT JOIN menu_items m ON p.id_menu_item = m.id_menu_item
            LEFT JOIN negocios n ON n.id_negocio = COALESCE(p.id_negocio, m.id_negocio)
            LEFT JOIN categorias c ON m.id_categoria = c.id_categoria
            WHERE p.activo = 1
              AND (p.fecha_inicio IS NULL OR p.fecha_inicio <= CURDATE())
              AND (p.fecha_fin IS NULL OR p.fecha_fin >= CURDATE())
            ORDER BY p.id_promocion DESC
        ');
        return $stmt->fetchAll();
    }
}
