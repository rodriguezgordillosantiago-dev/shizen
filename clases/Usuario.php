<?php
declare(strict_types=1);

require_once __DIR__ . '/Database.php';

/**
 * Usuario
 * ---------------------------------------------------------
 * Modelo base de usuario con encapsulamiento de datos,
 * registro, verificación de credenciales y consultas.
 * ---------------------------------------------------------
 */
class Usuario
{
    protected ?int $id = null;
    protected string $nombre;
    protected string $apellido;
    protected string $email;
    protected string $password;
    protected string $rol;
    protected ?string $direccion = null;
    protected ?string $telefono = null;
    protected ?string $localidad = null;

    public function __construct(
        string $nombre,
        string $apellido,
        string $email,
        string $password,
        string $rol = 'usuario',
        ?string $direccion = null,
        ?string $telefono = null,
        ?string $localidad = null
    ) {
        $this->nombre = $nombre;
        $this->apellido = $apellido;
        $this->email = $email;
        $this->password = $password;
        $this->rol = $rol;
        $this->direccion = $direccion;
        $this->telefono = $telefono;
        $this->localidad = $localidad;
    }

    /** Registra un nuevo usuario en la base de datos y retorna su ID */
    public function registrar(?PDO $conexion = null): int
    {
        $pdo = $conexion ?? Database::getConnection();
        $sql = 'INSERT INTO usuario (nombre, apellido, email, password_hash, rol, direccion, localidad)
                VALUES (:nombre, :apellido, :email, :password_hash, :rol, :direccion, :localidad)';

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nombre' => $this->nombre,
            ':apellido' => $this->apellido,
            ':email' => $this->email,
            ':password_hash' => password_hash($this->password, PASSWORD_DEFAULT),
            ':rol' => $this->rol,
            ':direccion' => $this->direccion,
            ':localidad' => $this->localidad,
        ]);

        $this->id = (int) $pdo->lastInsertId();
        return $this->id;
    }

    /** Autentica credenciales de usuario para inicio de sesión */
    public static function autenticar(string $email, string $password): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM usuario WHERE email = ?');
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();

        if ($usuario && password_verify($password, $usuario['password_hash'])) {
            return $usuario;
        }

        return null;
    }

    public static function negocio(int $userId): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT id_negocio, nombre
             FROM negocios
             WHERE id_usuario = ?
             LIMIT 1'
        );
        $stmt->execute([$userId]);
        $negocio = $stmt->fetch();

        if ($negocio) {
            return $negocio;
        }

        return null;
    }

    /** Comprueba si un correo ya se encuentra registrado */
    public static function emailExiste(string $email): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT 1 FROM usuario WHERE email = ?');
        $stmt->execute([$email]);
        return (bool) $stmt->fetch();
    }

    /** Getters */
    public function getId(): ?int
    {
        return $this->id;
    }
    public function getNombre(): string
    {
        return $this->nombre;
    }
    public function getApellido(): string
    {
        return $this->apellido;
    }
    public function getEmail(): string
    {
        return $this->email;
    }
    public function getRol(): string
    {
        return $this->rol;
    }
}