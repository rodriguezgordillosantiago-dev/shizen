<?php
declare(strict_types=1);

require_once __DIR__ . '/Usuario.php';

/**
 * Negocio
 * ---------------------------------------------------------
 * Modelo de Negocio que hereda de Usuario (Herencia en POO)
 * y gestiona los datos comerciales y documentos legales.
 * ---------------------------------------------------------
 */
class Negocio extends Usuario
{
    private string $cedula;
    private string $horaApertura;
    private string $horaCierre;
    private ?string $logoUrl = null;
    private ?string $rutUrl = null;
    private ?string $documentoIdentidadUrl = null;
    private ?string $certificadoBancarioUrl = null;
    private ?string $certificadoCamaraUrl = null;

    public function __construct(
        string $nombreNegocio,
        string $email,
        string $password,
        string $direccion,
        string $cedula = '',
        string $horaApertura = '08:00',
        string $horaCierre = '20:00'
    ) {
        parent::__construct(
            nombre: $nombreNegocio,
            apellido: 'Negocio',
            email: $email,
            password: $password,
            rol: 'Negocio',
            direccion: $direccion
        );

        $this->cedula = $cedula;
        $this->horaApertura = $horaApertura;
        $this->horaCierre = $horaCierre;
    }

    /** Asigna las rutas de los documentos cargados */
    public function setDocumentos(
        string $logoUrl,
        string $rutUrl,
        string $documentoIdentidadUrl,
        string $certificadoBancarioUrl,
        ?string $certificadoCamaraUrl = null
    ): self {
        $this->logoUrl = $logoUrl;
        $this->rutUrl = $rutUrl;
        $this->documentoIdentidadUrl = $documentoIdentidadUrl;
        $this->certificadoBancarioUrl = $certificadoBancarioUrl;
        $this->certificadoCamaraUrl = $certificadoCamaraUrl;
        return $this;
    }

    /** Registra la cuenta de usuario y el registro de negocio en una sola transacción */
    public function registrarNegocio(): int
    {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            // 1. Registrar usuario base (herencia)
            $idUsuario = parent::registrar($pdo);

            // 2. Registrar datos del comercio
            $sql = 'INSERT INTO negocios (
                id_usuario, gmail_negocio, nombre, direccion, cedula,
                hora_apertura, hora_cierre, logo_url, rut_url,
                documento_identidad_representante_url, certificado_bancario_url,
                certificado_camara_comercio_url
            ) VALUES (
                :id_usuario, :gmail_negocio, :nombre, :direccion, :cedula,
                :hora_apertura, :hora_cierre, :logo_url, :rut_url,
                :documento_identidad_representante_url, :certificado_bancario_url,
                :certificado_camara_comercio_url
            )';

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':id_usuario' => $idUsuario,
                ':gmail_negocio' => $this->email,
                ':nombre' => $this->nombre,
                ':direccion' => $this->direccion,
                ':cedula' => $this->cedula,
                ':hora_apertura' => $this->horaApertura,
                ':hora_cierre' => $this->horaCierre,
                ':logo_url' => $this->logoUrl,
                ':rut_url' => $this->rutUrl,
                ':documento_identidad_representante_url' => $this->documentoIdentidadUrl,
                ':certificado_bancario_url' => $this->certificadoBancarioUrl,
                ':certificado_camara_comercio_url' => $this->certificadoCamaraUrl,
            ]);

            $idNegocio = (int) $pdo->lastInsertId();
            $pdo->commit();
            return $idNegocio;

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Error al registrar negocio: ' . $e->getMessage());
            throw new RuntimeException('No fue posible completar el registro del negocio.');
        }
    }
}