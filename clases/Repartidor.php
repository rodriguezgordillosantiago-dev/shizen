<?php
declare(strict_types=1);

require_once __DIR__ . '/Usuario.php';

/**
 * Repartidor
 * ---------------------------------------------------------
 * Modelo de Repartidor que hereda de Usuario (Herencia en POO)
 * y gestiona los datos de vehículo y documentos de transporte.
 * ---------------------------------------------------------
 */
class Repartidor extends Usuario
{
    private string $cedula;
    private string $vehiculo;
    private ?string $fotoUrl = null;
    private ?string $cedulaDocumentoUrl = null;
    private ?string $licenciaConduccionUrl = null;
    private ?string $soatUrl = null;
    private ?string $tarjetaPropiedadUrl = null;

    public function __construct(
        string $nombre,
        string $apellido,
        string $email,
        string $password,
        string $vehiculo,
        string $direccion,
        string $cedula = ''
    ) {
        parent::__construct(
            nombre: $nombre,
            apellido: $apellido,
            email: $email,
            password: $password,
            rol: 'Repartidor',
            direccion: $direccion
        );

        $this->vehiculo = $vehiculo;
        $this->cedula = $cedula;
    }

    /** Indica si el vehículo requiere documentos de motorizado (Moto o Carro) */
    public function esMotorizado(): bool
    {
        return in_array($this->vehiculo, ['Moto', 'Carro'], true);
    }

    /** Asigna las rutas de los documentos cargados */
    public function setDocumentos(
        string $fotoUrl,
        string $cedulaDocumentoUrl,
        ?string $licenciaConduccionUrl = null,
        ?string $soatUrl = null,
        ?string $tarjetaPropiedadUrl = null
    ): self {
        $this->fotoUrl = $fotoUrl;
        $this->cedulaDocumentoUrl = $cedulaDocumentoUrl;
        $this->licenciaConduccionUrl = $licenciaConduccionUrl;
        $this->soatUrl = $soatUrl;
        $this->tarjetaPropiedadUrl = $tarjetaPropiedadUrl;
        return $this;
    }

    /** Registra la cuenta de usuario y el registro de repartidor en una sola transacción */
    public function registrarRepartidor(): int
    {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            // 1. Registrar usuario base (herencia)
            $idUsuario = parent::registrar($pdo);

            // 2. Registrar datos del repartidor
            $sql = 'INSERT INTO repartidor (
                id_usuario, nombre, apellido, email_repartidor, direccion,
                cedula, vehiculo, foto_url, cedula_documento_url,
                licencia_conduccion_url, soat_url, tarjeta_propiedad_url
            ) VALUES (
                :id_usuario, :nombre, :apellido, :email_repartidor, :direccion,
                :cedula, :vehiculo, :foto_url, :cedula_documento_url,
                :licencia_conduccion_url, :soat_url, :tarjeta_propiedad_url
            )';

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':id_usuario' => $idUsuario,
                ':nombre' => $this->nombre,
                ':apellido' => $this->apellido,
                ':email_repartidor' => $this->email,
                ':direccion' => $this->direccion,
                ':cedula' => $this->cedula,
                ':vehiculo' => $this->vehiculo,
                ':foto_url' => $this->fotoUrl,
                ':cedula_documento_url' => $this->cedulaDocumentoUrl,
                ':licencia_conduccion_url' => $this->licenciaConduccionUrl,
                ':soat_url' => $this->soatUrl,
                ':tarjeta_propiedad_url' => $this->tarjetaPropiedadUrl,
            ]);

            $idRepartidor = (int) $pdo->lastInsertId();
            $pdo->commit();
            return $idRepartidor;

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Error al registrar repartidor: ' . $e->getMessage());
            throw new RuntimeException('No fue posible completar el registro del repartidor.');
        }
    }
}