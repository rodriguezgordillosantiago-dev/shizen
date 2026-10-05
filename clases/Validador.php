<?php
declare(strict_types=1);

/**
 * Validador
 * ---------------------------------------------------------
 * Clase para sanitizar y validar datos de formularios
 * con interfaz encadenable (Fluent Interface).
 * ---------------------------------------------------------
 */
class Validador
{
    private array $datos;
    private array $errores = [];
    private array $datosLimpios = [];

    public function __construct(array $datos)
    {
        $this->datos = $datos;
        foreach ($datos as $clave => $valor) {
            if (is_string($valor)) {
                $this->datosLimpios[$clave] = htmlspecialchars(trim($valor), ENT_QUOTES, 'UTF-8');
            } else {
                $this->datosLimpios[$clave] = $valor;
            }
        }
    }

    /** Valida que uno o más campos no estén vacíos */
    public function requerido(string ...$campos): self
    {
        foreach ($campos as $campo) {
            $valor = $this->datos[$campo] ?? '';
            if (is_string($valor) ? trim($valor) === '' : empty($valor)) {
                $this->errores[$campo] = "El campo '{$campo}' es obligatorio.";
            }
        }
        return $this;
    }

    /** Valida formato de correo electrónico */
    public function email(string $campo, string $mensaje = 'Ingresa un correo electrónico válido.'): self
    {
        $valor = trim((string) ($this->datos[$campo] ?? ''));
        if ($valor !== '' && !filter_var($valor, FILTER_VALIDATE_EMAIL)) {
            $this->errores[$campo] = $mensaje;
        }
        return $this;
    }

    /** Valida longitud mínima */
    public function longitudMinima(string $campo, int $min, ?string $mensaje = null): self
    {
        $valor = (string) ($this->datos[$campo] ?? '');
        if (strlen($valor) < $min) {
            $this->errores[$campo] = $mensaje ?? "Debe tener al menos {$min} caracteres.";
        }
        return $this;
    }

    /** Valida que dos campos sean idénticos (ej. contraseñas) */
    public function coinciden(string $campo1, string $campo2, string $mensaje = 'Las contraseñas no coinciden.'): self
    {
        $val1 = (string) ($this->datos[$campo1] ?? '');
        $val2 = (string) ($this->datos[$campo2] ?? '');
        if ($val1 !== $val2) {
            $this->errores[$campo2] = $mensaje;
        }
        return $this;
    }

    /** Valida formato de texto con patrón regex */
    public function patron(string $campo, string $regex, string $mensaje): self
    {
        $valor = trim((string) ($this->datos[$campo] ?? ''));
        if ($valor !== '' && !preg_match($regex, $valor)) {
            $this->errores[$campo] = $mensaje;
        }
        return $this;
    }

    /** Valida que el valor esté dentro de un arreglo de opciones permitidas */
    public function enLista(string $campo, array $permitidos, string $mensaje = 'Selecciona una opción válida.'): self
    {
        $valor = (string) ($this->datos[$campo] ?? '');
        if (!in_array($valor, $permitidos, true)) {
            $this->errores[$campo] = $mensaje;
        }
        return $this;
    }

    /** Agrega un error personalizado */
    public function agregarError(string $campo, string $mensaje): self
    {
        $this->errores[$campo] = $mensaje;
        return $this;
    }

    /** Indica si no hay errores */
    public function esValido(): bool
    {
        return empty($this->errores);
    }

    /** Devuelve la lista de errores */
    public function obtenerErrores(): array
    {
        return array_values($this->errores);
    }

    /** Devuelve el valor limpio y sanitizado de un campo */
    public function obtener(string $campo, mixed $porDefecto = null): mixed
    {
        return $this->datosLimpios[$campo] ?? $porDefecto;
    }

    /** Devuelve todos los datos sanitizados */
    public function obtenerTodos(): array
    {
        return $this->datosLimpios;
    }
}