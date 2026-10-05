<?php
declare(strict_types=1);

require_once __DIR__ . '/Database.php';

final class Notificacion
{
    public static function crear(
        int $usuarioId,
        string $audiencia,
        ?int $pedidoId,
        string $tipo,
        string $titulo,
        string $mensaje
    ): void {
        if ($usuarioId <= 0) {
            return;
        }

        $stmt = Database::getConnection()->prepare(
            'INSERT INTO notificacion
                (id_usuario, audiencia, id_pedido, tipo, titulo, mensaje)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$usuarioId, $audiencia, $pedidoId, $tipo, $titulo, $mensaje]);
    }
}