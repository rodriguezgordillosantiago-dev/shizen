<?php
declare(strict_types=1);

require_once __DIR__ . '/../clases/Pedido.php';
require_once __DIR__ . '/../clases/Validador.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name('SHIZEN_CLIENTE_SESSION');
    session_start();
}

if (empty($_SESSION['id_usuario'])) {
    header('Location: ../auth/login.php?expired=1');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], (string) ($_POST['csrf_token'] ?? ''))) {
    http_response_code(403);
    exit('Solicitud no válida.');
}

$validador = new Validador($_POST);
$validador->requerido('nombre', 'apellido', 'numero_documento', 'correo', 'direccion', 'metodo_pago')
          ->email('correo');

if (!$validador->esValido()) {
    http_response_code(422);
    exit('Completa todos los datos de entrega y método de pago correctamente.');
}

$items = json_decode((string) ($_POST['items'] ?? ''), true);
if (!is_array($items) || empty($items)) {
    http_response_code(422);
    exit('El carrito está vacío.');
}

try {
    $idUsuario = isset($_SESSION['id_usuario']) ? (int)$_SESSION['id_usuario'] : null;
    $orderId = Pedido::registrar(
        datosCliente: $validador->obtenerTodos(),
        items: $items,
        idUsuario: $idUsuario
    );

    header('Location: pedido.php?id=' . $orderId . '&created=1');
    exit;
} catch (Throwable $exception) {
    error_log('Error al registrar pedido: ' . $exception->getMessage());
    http_response_code(422);
    exit('No fue posible registrar el pedido: ' . htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8'));
}
?>
