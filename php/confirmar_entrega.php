<?php
declare(strict_types=1);
require_once __DIR__ . '/../BD/conexion.php';
if (session_status() === PHP_SESSION_NONE) {
    session_name('SHIZEN_CLIENTE_SESSION');
    session_start();
}
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$code = trim((string) ($_POST['codigo'] ?? ''));
if (empty($_SESSION['id_usuario']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], (string) ($_POST['csrf_token'] ?? '')) || !$id || !preg_match('/^\d{6}$/', $code)) {
    http_response_code(422);
    exit('Código no válido.');
}
$pdo = obtenerConexion();
$stmt = $pdo->prepare('SELECT e.id_entrega FROM entrega e JOIN compra c ON c.id_compra=e.id_compra JOIN pedido p ON p.id_pedido=c.id_pedido WHERE p.id_pedido=? AND p.id_usuario=? AND e.codigo_entrega=? AND e.fecha_confirmacion IS NULL');
$stmt->execute([$id, (int) $_SESSION['id_usuario'], $code]);
$deliveryId = $stmt->fetchColumn();
if (!$deliveryId) {
    http_response_code(404);
    exit('Entrega no encontrada.');
}
$pdo->prepare("UPDATE entrega SET estado='Entregado', fecha_confirmacion=NOW(), fecha_entrega=NOW() WHERE id_entrega=?")->execute([$deliveryId]);
$pdo->prepare("UPDATE pedido SET estado='Recibido' WHERE id_pedido=?")->execute([$id]);
header('Location: pedido.php?id=' . $id);
exit;