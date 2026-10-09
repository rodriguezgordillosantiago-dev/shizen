<?php
declare(strict_types=1);
require_once __DIR__ . '/../BD/conexion.php';
session_name('SHIZEN_REPARTIDOR_SESSION');
session_start();

$userId = (int)($_SESSION['id_usuario'] ?? 0);
if ($userId <= 0 || strtolower((string)($_SESSION['usuario_rol'] ?? '')) !== 'repartidor') {
    header('Location: ../index.html');
    exit;
}

header('Location: ../pages/inicio.html');
exit;
