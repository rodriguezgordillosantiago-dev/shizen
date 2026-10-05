<?php
declare(strict_types=1);
require_once __DIR__ . '/../BD/conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name('SHIZEN_CLIENTE_SESSION');
    session_start();
}

if (empty($_SESSION['id_usuario']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], (string) ($_POST['csrf_token'] ?? ''))) {
    http_response_code(403);
    exit('Solicitud no válida.');
}

$userId = (int) $_SESSION['id_usuario'];
$menuItemId = filter_input(INPUT_POST, 'id_menu_item', FILTER_VALIDATE_INT) ?: null;
$businessId = filter_input(INPUT_POST, 'id_negocio', FILTER_VALIDATE_INT) ?: null;
$pdo = obtenerConexion();

if ($menuItemId) {
    if (!$businessId) {
        $stmtItem = $pdo->prepare('SELECT id_negocio FROM menu_items WHERE id_menu_item = ?');
        $stmtItem->execute([$menuItemId]);
        $businessId = (int) ($stmtItem->fetchColumn() ?: 0) ?: null;
    }

    $check = $pdo->prepare('SELECT id_favorito FROM favorito WHERE id_usuario = ? AND id_menu_item = ?');
    $check->execute([$userId, $menuItemId]);
    $favId = $check->fetchColumn();

    if ($favId) {
        $pdo->prepare('DELETE FROM favorito WHERE id_favorito = ?')->execute([$favId]);
    } else {
        $pdo->prepare('INSERT INTO favorito (id_usuario, id_negocio, id_menu_item, fecha_reg) VALUES (?, ?, ?, CURDATE()) ON DUPLICATE KEY UPDATE id_menu_item = VALUES(id_menu_item), fecha_reg = CURDATE()')
            ->execute([$userId, $businessId, $menuItemId]);
    }
} elseif ($businessId) {
    $check = $pdo->prepare('SELECT id_favorito FROM favorito WHERE id_usuario = ? AND id_negocio = ? AND id_menu_item IS NULL');
    $check->execute([$userId, $businessId]);
    $favId = $check->fetchColumn();

    if ($favId) {
        $pdo->prepare('DELETE FROM favorito WHERE id_favorito = ?')->execute([$favId]);
    } else {
        $pdo->prepare('INSERT INTO favorito (id_usuario, id_negocio, id_menu_item, fecha_reg) VALUES (?, ?, NULL, CURDATE()) ON DUPLICATE KEY UPDATE id_menu_item = NULL, fecha_reg = CURDATE()')
            ->execute([$userId, $businessId]);
    }
} else {
    http_response_code(400);
    exit('Parámetros no válidos.');
}

$redirect = (string) ($_POST['redirect'] ?? '');
$redirect = preg_replace('#^php/#', '', $redirect);
if ($redirect === '' || str_contains($redirect, '://') || str_starts_with($redirect, '//') || str_contains($redirect, "\r") || str_contains($redirect, "\n")) {
    $redirect = $businessId ? 'negocio.php?id=' . (int) $businessId : 'favoritos.php';
}

header('Location: ' . $redirect);
exit;