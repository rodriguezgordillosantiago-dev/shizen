<?php
declare(strict_types=1);
require_once __DIR__ . '/../BD/conexion.php';
if (session_status() === PHP_SESSION_NONE) { session_name('SHIZEN_CLIENTE_SESSION'); session_start(); }
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$score = filter_input(INPUT_POST, 'puntuacion', FILTER_VALIDATE_INT);
$comment = trim((string)($_POST['comentario'] ?? ''));
if (empty($_SESSION['id_usuario']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], (string)($_POST['csrf_token'] ?? '')) || !$id || !$score || $score < 1 || $score > 5) { http_response_code(422); exit('Calificación no válida.'); }
$pdo = obtenerConexion();
$stmt = $pdo->prepare(
    "SELECT p.id_pedido, e.id_entrega, e.id_repartidor
     FROM pedido p
     JOIN compra c ON c.id_pedido = p.id_pedido
     JOIN entrega e ON e.id_compra = c.id_compra
     WHERE p.id_pedido=? AND p.id_usuario=? AND p.estado IN ('Recibido', 'Entregado')
       AND e.fecha_confirmacion IS NOT NULL"
);
$stmt->execute([$id, (int)$_SESSION['id_usuario']]);
$delivery = $stmt->fetch();
if (!$delivery) { http_response_code(404); exit('Pedido no disponible para calificar al repartidor.'); }

$repartidorId = (int)($delivery['id_repartidor'] ?? 0);
if ($repartidorId <= 0) {
    $defaultRep = $pdo->query('SELECT id_repartidor FROM repartidor ORDER BY id_repartidor ASC LIMIT 1')->fetchColumn();
    $repartidorId = (int)($defaultRep ?: 1);
    $pdo->prepare('UPDATE entrega SET id_repartidor = ? WHERE id_entrega = ? AND id_repartidor IS NULL')
        ->execute([$repartidorId, (int)$delivery['id_entrega']]);
}

$pdo->prepare(
    'INSERT INTO calificacion_repartidor
        (id_repartidor,id_usuario,id_pedido,comentario,fecha,puntuacion)
     VALUES (?,?,?,?,?,?)
     ON DUPLICATE KEY UPDATE comentario=VALUES(comentario),fecha=VALUES(fecha),puntuacion=VALUES(puntuacion)'
)->execute([
    $repartidorId,
    (int)$_SESSION['id_usuario'],
    $id,
    $comment ?: null,
    date('Y-m-d H:i:s'),
    $score,
]);
header('Location: ../index.php'); exit;