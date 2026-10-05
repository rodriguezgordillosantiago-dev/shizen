<?php
declare(strict_types=1);

require_once __DIR__ . '/../BD/conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name('SHIZEN_CLIENTE_SESSION');
    session_start();
}

$isJson = isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json');

function responder(bool $success, string $mensaje, int $status = 200, ?int $pedidoId = null, bool $isJson = false): void {
    if ($isJson) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => $success, 'mensaje' => $mensaje], JSON_UNESCAPED_UNICODE);
        exit;
    }
    if ($pedidoId) {
        header('Location: pedido.php?id=' . $pedidoId . ($success ? '&calificado=1' : '&error_calif=' . urlencode($mensaje)));
    } else {
        header('Location: ../index.php');
    }
    exit;
}

if (empty($_SESSION['id_usuario'])) {
    responder(false, 'Debes iniciar sesión para calificar.', 401, null, $isJson);
}

$submittedToken = (string)($_POST['csrf_token'] ?? '');
if (empty($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], $submittedToken)) {
    responder(false, 'Token CSRF inválido o expirado.', 403, null, $isJson);
}

$idPedido = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$idPedido) {
    responder(false, 'ID de pedido no válido.', 422, null, $isJson);
}

$scoreNegocio = filter_input(INPUT_POST, 'puntuacion_negocio', FILTER_VALIDATE_INT);
$comentarioNegocio = trim((string)($_POST['comentario_negocio'] ?? ''));

$scoreRepartidor = filter_input(INPUT_POST, 'puntuacion_repartidor', FILTER_VALIDATE_INT);
$comentarioRepartidor = trim((string)($_POST['comentario_repartidor'] ?? ''));

if ((!$scoreNegocio || $scoreNegocio < 1 || $scoreNegocio > 5) && (!$scoreRepartidor || $scoreRepartidor < 1 || $scoreRepartidor > 5)) {
    responder(false, 'Debes seleccionar al menos una calificación con estrellas.', 422, $idPedido, $isJson);
}

$pdo = obtenerConexion();

// Validar pedido y pertenencia al usuario
$stmt = $pdo->prepare(
    "SELECT p.id_pedido, p.id_negocio, p.estado, e.id_entrega, e.id_repartidor, e.fecha_confirmacion
     FROM pedido p
     LEFT JOIN compra c ON c.id_pedido = p.id_pedido
     LEFT JOIN entrega e ON e.id_compra = c.id_compra
     WHERE p.id_pedido = ? AND p.id_usuario = ? LIMIT 1"
);
$stmt->execute([$idPedido, (int)$_SESSION['id_usuario']]);
$pedido = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pedido) {
    responder(false, 'Pedido no encontrado o no pertenece a tu cuenta.', 404, $idPedido, $isJson);
}

$ahora = date('Y-m-d H:i:s');

// 1. Guardar calificación de negocio si se envió
if ($scoreNegocio && $scoreNegocio >= 1 && $scoreNegocio <= 5 && !empty($pedido['id_negocio'])) {
    $stmtNeg = $pdo->prepare(
        "INSERT INTO calificacion (id_negocio, id_usuario, comentario, fecha, puntuacion)
         VALUES (?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE comentario = VALUES(comentario), fecha = VALUES(fecha), puntuacion = VALUES(puntuacion)"
    );
    $stmtNeg->execute([
        (int)$pedido['id_negocio'],
        (int)$_SESSION['id_usuario'],
        $comentarioNegocio !== '' ? $comentarioNegocio : null,
        $ahora,
        $scoreNegocio
    ]);
}

// 2. Guardar calificación de repartidor si se envió
if ($scoreRepartidor && $scoreRepartidor >= 1 && $scoreRepartidor <= 5) {
    $repartidorId = (int)($pedido['id_repartidor'] ?? 0);
    if ($repartidorId <= 0) {
        $defaultRep = $pdo->query('SELECT id_repartidor FROM repartidor ORDER BY id_repartidor ASC LIMIT 1')->fetchColumn();
        $repartidorId = (int)($defaultRep ?: 1);
        if (!empty($pedido['id_entrega'])) {
            $pdo->prepare('UPDATE entrega SET id_repartidor = ? WHERE id_entrega = ? AND id_repartidor IS NULL')
                ->execute([$repartidorId, (int)$pedido['id_entrega']]);
        }
    }

    $stmtRep = $pdo->prepare(
        "INSERT INTO calificacion_repartidor (id_repartidor, id_usuario, id_pedido, comentario, fecha, puntuacion)
         VALUES (?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE comentario = VALUES(comentario), fecha = VALUES(fecha), puntuacion = VALUES(puntuacion)"
    );
    $stmtRep->execute([
        $repartidorId,
        (int)$_SESSION['id_usuario'],
        $idPedido,
        $comentarioRepartidor !== '' ? $comentarioRepartidor : null,
        $ahora,
        $scoreRepartidor
    ]);
}

responder(true, '¡Gracias por calificar tu experiencia!', 200, $idPedido, $isJson);
