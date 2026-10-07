<?php
declare(strict_types=1);
require_once __DIR__ . '/../BD/conexion.php';
require_once __DIR__ . '/../funciones/funciones.php';
if (session_status() === PHP_SESSION_NONE) {
    session_name('SHIZEN_CLIENTE_SESSION');
    session_start();
}
if (
    empty($_SESSION['id_usuario']) || $_SERVER['REQUEST_METHOD'] !== 'POST' ||
    empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], (string) ($_POST['csrf_token'] ?? ''))
) {
    http_response_code(403);
    exit('Solicitud no válida.');
}
$nombre = limpiarTexto($_POST['nombre'] ?? '');
$apellido = limpiarTexto($_POST['apellido'] ?? '');
$direccion = limpiarTexto($_POST['direccion'] ?? '');
$localidad = limpiarTexto($_POST['localidad'] ?? '');
if ($nombre === '' || $apellido === '') {
    http_response_code(422);
    exit('Nombre y apellido son obligatorios.');
}
$avatar = (string) ($_POST['avatar'] ?? '');
$avatarFiles = glob(__DIR__ . '/../assets/Perfil/*.{png,jpg,jpeg,webp}', GLOB_BRACE) ?: [];
$avatarPaths = array_map(static fn(string $file): string => 'assets/Perfil/' . basename($file), $avatarFiles);
if ($avatar !== '' && !in_array($avatar, $avatarPaths, true)) {
    http_response_code(422);
    exit('El avatar seleccionado no es válido.');
}
$pdo = obtenerConexion();
$columns = $pdo->query('SHOW COLUMNS FROM usuario')->fetchAll(PDO::FETCH_COLUMN);
$locationColumn = in_array('localidad', $columns, true)
    ? 'localidad'
    : (in_array('ciudad', $columns, true) ? 'ciudad' : null);
$stmtIcono = $pdo->prepare(
    'SELECT id_icono FROM icono WHERE icono_url = ? LIMIT 1'
);
$iconoUrl = '../Perfil/' . basename($avatar);
$stmtIcono->execute([$iconoUrl]);
$idIcono = $stmtIcono->fetchColumn();

if ($idIcono === false) {
    http_response_code(422);
    exit('No se encontró el icono seleccionado.');
}

$updates = 'nombre=?, apellido=?, direccion=?, id_icono=?';
$values = [$nombre, $apellido, $direccion ?: null, (int) $idIcono];
if ($locationColumn !== null) {
    $updates .= ', ' . $locationColumn . '=?';
    $values[] = $localidad ?: null;
}
$values[] = (int) $_SESSION['id_usuario'];
$stmt = $pdo->prepare('UPDATE usuario SET ' . $updates . ' WHERE id_usuario=?');
$stmt->execute($values);
$_SESSION['usuario_nombre'] = $nombre;
$_SESSION['usuario_apellido'] = $apellido;
$_SESSION['usuario_direccion'] = $direccion;
$_SESSION['usuario_localidad'] = $localidad;
if ($avatar !== '') {
    $_SESSION['usuario_avatar'] = $avatar;
}

header('Location: ../forms/perfil.php');
exit;