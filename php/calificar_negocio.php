<?php
declare(strict_types=1);
require_once __DIR__ . '/../BD/conexion.php';
if (session_status() === PHP_SESSION_NONE) {
    session_name('SHIZEN_CLIENTE_SESSION');
    session_start();
}
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$score = filter_input(INPUT_POST, 'puntuacion', FILTER_VALIDATE_INT);
$comment = trim((string) ($_POST['comentario'] ?? ''));
if (empty($_SESSION['id_usuario']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], (string) ($_POST['csrf_token'] ?? '')) || !$id || !$score || $score < 1 || $score > 5) {
    http_response_code(422);
    exit('Calificación no válida.');
}
$pdo = obtenerConexion();
$stmt = $pdo->prepare("SELECT id_negocio FROM pedido WHERE id_pedido=? AND id_usuario=? AND estado IN ('Recibido', 'Entregado')");
$stmt->execute([$id, (int) $_SESSION['id_usuario']]);
$businessId = $stmt->fetchColumn();
if (!$businessId) {
    http_response_code(404);
    exit('Pedido no disponible para calificar el negocio.');
}
$pdo->prepare(
    'INSERT INTO calificacion (id_negocio,id_usuario,comentario,fecha,puntuacion)
     VALUES (?,?,?,?,?)
     ON DUPLICATE KEY UPDATE comentario=VALUES(comentario),fecha=VALUES(fecha),puntuacion=VALUES(puntuacion)'
)->execute([(int) $businessId, (int) $_SESSION['id_usuario'], $comment ?: null, date('Y-m-d H:i:s'), $score]);
header('Location: pedido.php?id=' . $id);
exit;