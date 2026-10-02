<?php
declare(strict_types=1);

require_once __DIR__ . '/../clases/Usuario.php';
require_once __DIR__ . '/../funciones/funciones.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name('SHIZEN_CLIENTE_SESSION');
    session_start();
}

// Solo acepta POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

// Verificar CSRF
$csrfToken = $_POST['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
    header('Location: ../index.php?error=1#login');
    exit;
}

function mostrarErrorLogin(string $redirect = ''): void
{
    $query = $redirect !== '' ? '&login_redirect=' . rawurlencode($redirect) : '';
    header('Location: ../index.php?error=1' . $query . '#login');
    exit;
}

$email    = limpiarTexto($_POST['email']    ?? '');
$password = (string)($_POST['password']    ?? '');
$redirect = (string)($_POST['redirect'] ?? '');
$redirect = preg_match('#^(?:(?:php|forms)/)?[A-Za-z0-9_-]+\.php(?:\?[A-Za-z0-9_=&%-]*)?$#', $redirect) ? $redirect : '';

$emailValido = filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
if (!$emailValido || strlen($email) > 254 || strlen($password) < 8 || strlen($password) > 255) {
    mostrarErrorLogin($redirect);
}

$usuario = Usuario::autenticar($email, $password);
if (!$usuario) {
    mostrarErrorLogin($redirect);
}

$rol = strtolower(trim($usuario['rol'] ?? 'usuario'));

if ($rol !== 'usuario') {
    $_SESSION = [];
    session_regenerate_id(true);
}

if ($rol === 'negocio') {
    $business = Usuario::negocio((int) ($usuario['id_usuario'] ?? $usuario['id'] ?? 0));
    if (!$business) {
        mostrarErrorLogin($redirect);
    }

    header('Location: /shizennegocio/pages/dashboard.php');
    exit;
}

if ($rol === 'repartidor') {
    header('Location: http://localhost/shizen_repartidor/');
    exit;
}

if ($rol !== 'usuario') {
    mostrarErrorLogin($redirect);
}

session_regenerate_id(true);
$_SESSION['id_usuario'] = $usuario['id_usuario'] ?? $usuario['id'] ?? null;
$_SESSION['usuario_nombre'] = $usuario['nombre'];
$_SESSION['usuario_apellido'] = $usuario['apellido'] ?? '';
$_SESSION['usuario_email'] = $usuario['email'];
$_SESSION['usuario_direccion'] = $usuario['direccion'] ?? '';
$_SESSION['usuario_localidad'] = $usuario['localidad'] ?? $usuario['ciudad'] ?? '';
$_SESSION['usuario_rol'] = $rol;

header('Location: ../' . ($redirect !== '' ? $redirect : 'index.php'));
exit;
