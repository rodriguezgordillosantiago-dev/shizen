<?php
declare(strict_types=1);

/**
 * Database
 * ---------------------------------------------------------
 * Gestión centralizada de la conexión a la base de datos MySQL
 * mediante el patrón Singleton con PDO.
 * ---------------------------------------------------------
 */
class Database
{
    private const DB_HOST = 'localhost';
    private const DB_NAME = 'shizen';
    private const DB_USER = 'root';
    private const DB_PASS = '';
    private const DB_CHARSET = 'utf8mb4';

    private static ?PDO $instance = null;

    /** Constructor privado para impedir instanciación directa (Singleton) */
    private function __construct()
    {
    }

    /** Obtiene la instancia única de conexión PDO */
    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                self::DB_HOST,
                self::DB_NAME,
                self::DB_CHARSET
            );

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            try {
                self::$instance = new PDO($dsn, self::DB_USER, self::DB_PASS, $options);
            } catch (PDOException $e) {
                error_log('Error de conexión en Database: ' . $e->getMessage());
                throw new RuntimeException('No fue posible conectar con la base de datos.');
            }
        }

        return self::$instance;
    }
}