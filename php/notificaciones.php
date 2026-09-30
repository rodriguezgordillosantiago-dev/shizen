<?php
declare(strict_types=1);

require_once __DIR__ . '/../BD/conexion.php';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('SHIZEN_REPARTIDOR_SESSION');
    session_start();
}
$userId = (int)($_SESSION['id_usuario'] ?? 0);
if ($userId <= 0) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Debes iniciar sesión como repartidor.']);
    exit;
}
$db = obtenerConexion();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db->prepare('UPDATE notificacion SET leida = 1 WHERE id_usuario = :usuario AND audiencia = "repartidor"')
       ->execute(['usuario' => $userId]);
}
$stmt = $db->prepare(
    'SELECT id_notificacion, id_pedido, tipo, titulo, mensaje, leida, fecha_creacion
       FROM notificacion
      WHERE id_usuario = :usuario AND audiencia = "repartidor"
      ORDER BY fecha_creacion DESC LIMIT 50'
);
$stmt->execute(['usuario' => $userId]);
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['items' => $stmt->fetchAll(PDO::FETCH_ASSOC)], JSON_UNESCAPED_UNICODE);
