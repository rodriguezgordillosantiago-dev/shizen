<?php
declare(strict_types=1);

require_once __DIR__ . '/../clases/Database.php';

/**
 * Función puente para compatibilidad hacia atrás.
 * Utiliza la clase Database (Singleton POO).
 */
function obtenerConexion(): PDO
{
    return Database::getConnection();
}