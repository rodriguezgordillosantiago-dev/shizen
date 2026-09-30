<?php
declare(strict_types=1);

require_once __DIR__ . '/../BD/conexion.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('SHIZEN_REPARTIDOR_SESSION');
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

$userId = (int)($_SESSION['id_usuario'] ?? 0);
if ($userId <= 0 || strtolower((string)($_SESSION['usuario_rol'] ?? '')) !== 'repartidor') {
    http_response_code(401);
    echo json_encode(['error' => 'Debes iniciar sesión como repartidor.']);
    exit;
}

$db = obtenerConexion();
$courier = $db->prepare('SELECT id_repartidor FROM repartidor WHERE id_usuario = :id_usuario');
$courier->execute(['id_usuario' => $userId]);
$courierId = (int)$courier->fetchColumn();

if ($courierId <= 0) {
    http_response_code(403);
    echo json_encode(['error' => 'La cuenta no tiene un registro de repartidor activo.']);
    exit;
}

// ─── Helpers ──────────────────────────────────────────────────────────────────
// Genera el CÓDIGO_R (repartidor → negocio) a partir del id_entrega
function generarCodigoRepartidor(int $idEntrega): string {
    return sprintf('%06d', ($idEntrega * 265443) % 900000 + 100000);
}

$action = (string)($_POST['action'] ?? $_GET['action'] ?? 'list');

if ($action === 'stats') {
    $stmt = $db->prepare(
        'SELECT u.nombre, u.apellido,
                COUNT(CASE WHEN e.fecha_entrega IS NOT NULL AND DATE(e.fecha_entrega) = CURDATE() THEN 1 END) AS entregas_hoy,
                COALESCE(SUM(CASE WHEN e.fecha_entrega IS NOT NULL AND DATE(e.fecha_entrega) = CURDATE() THEN c.total ELSE 0 END), 0) AS ganancias_hoy,
                COALESCE(AVG(cr.puntuacion), 0) AS calificacion
           FROM repartidor r
           JOIN usuario u ON u.id_usuario = r.id_usuario
           LEFT JOIN entrega e ON e.id_repartidor = r.id_repartidor
           LEFT JOIN compra c ON c.id_compra = e.id_compra
           LEFT JOIN calificacion_repartidor cr ON cr.id_repartidor = r.id_repartidor
          WHERE r.id_repartidor = :courier
          GROUP BY r.id_repartidor, u.nombre, u.apellido'
    );
    $stmt->execute(['courier' => $courierId]);
    $stats = $stmt->fetch() ?: [];
    echo json_encode([
        'nombre' => $stats['nombre'] ?? '',
        'apellido' => $stats['apellido'] ?? '',
        'entregasHoy' => (int)($stats['entregas_hoy'] ?? 0),
        'gananciasHoy' => (float)($stats['ganancias_hoy'] ?? 0),
        'calificacion' => round((float)($stats['calificacion'] ?? 0), 1),
    ]);
    exit;
}

// ─── Toggle disponibilidad ────────────────────────────────────────────────────
if ($action === 'toggle') {
    $_SESSION['repartidor_online'] = !((bool)($_SESSION['repartidor_online'] ?? true));
    echo json_encode(['online' => (bool)$_SESSION['repartidor_online']]);
    exit;
}

// ─── Aceptar pedido → generar CÓDIGO_R sin reemplazar el código del cliente ──
if ($action === 'accept') {
    $deliveryId = (int)($_POST['id_entrega'] ?? 0);

    $active = $db->prepare('SELECT COUNT(*) FROM entrega WHERE id_repartidor = :id AND fecha_confirmacion IS NULL AND fecha_entrega IS NULL');
    $active->execute(['id' => $courierId]);
    if ((int)$active->fetchColumn() >= 4) {
        http_response_code(422);
        echo json_encode(['error' => 'Ya tienes el máximo de cuatro pedidos activos.']);
        exit;
    }

    // Este código es solo para validar la recogida en el negocio. El código
    // almacenado en codigo_entrega pertenece al cliente y no debe alterarse.
    $codigoR = generarCodigoRepartidor($deliveryId);

    $stmt = $db->prepare(
        "UPDATE entrega
            SET id_repartidor = :courier,
                fecha_asignacion = NOW(),
                estado = 'Asignado'
          WHERE id_entrega = :delivery
            AND id_repartidor IS NULL
            AND estado = 'Pendiente'"
    );
    $stmt->execute(['courier' => $courierId, 'delivery' => $deliveryId]);

    if ($stmt->rowCount() !== 1) {
        http_response_code(409);
        echo json_encode(['error' => 'El pedido ya fue tomado por otro repartidor.']);
        exit;
    }

    $recipients = $db->prepare(
        'SELECT p.id_usuario AS cliente_id, n.id_usuario AS negocio_id, p.id_pedido, p.estado AS pedido_estado,
                p.tiempo_preparacion, p.hora_estimada_listo
           FROM entrega e
           JOIN compra c ON c.id_compra = e.id_compra
           JOIN pedido p ON p.id_pedido = c.id_pedido
           JOIN negocios n ON n.id_negocio = p.id_negocio
          WHERE e.id_entrega = :delivery'
    );
    $recipients->execute(['delivery' => $deliveryId]);
    $recipient = $recipients->fetch(PDO::FETCH_ASSOC);
    if ($recipient) {
        $notify = $db->prepare(
            'INSERT INTO notificacion
                (id_usuario, audiencia, id_pedido, tipo, titulo, mensaje)
             SELECT :user_id, :audiencia, p.id_pedido, :tipo, :titulo, :mensaje
               FROM pedido p
              WHERE p.id_pedido = (
                SELECT c.id_pedido FROM entrega e
                JOIN compra c ON c.id_compra = e.id_compra
                WHERE e.id_entrega = :delivery
              )'
        );
        $notify->execute([
            'user_id' => (int)$recipient['cliente_id'],
            'audiencia' => 'cliente',
            'tipo' => 'repartidor_acepto',
            'titulo' => 'Tu pedido fue aceptado',
            'mensaje' => 'Un repartidor aceptó tu pedido y pronto lo recogerá.',
            'delivery' => $deliveryId,
        ]);
        $notify->execute([
            'user_id' => (int)$recipient['negocio_id'],
            'audiencia' => 'negocio',
            'tipo' => 'repartidor_acepto',
            'titulo' => 'Repartidor asignado',
            'mensaje' => 'Un repartidor aceptó el pedido y puede recogerlo cuando esté preparado.',
            'delivery' => $deliveryId,
        ]);

        $courierUser = $db->prepare('SELECT id_usuario FROM repartidor WHERE id_repartidor = :id');
        $courierUser->execute(['id' => $courierId]);
        $repUid = (int)$courierUser->fetchColumn();

        if ($repUid > 0) {
            if (strtolower((string)$recipient['pedido_estado']) === 'preparado') {
                $db->prepare(
                    'INSERT INTO notificacion
                        (id_usuario, audiencia, id_pedido, tipo, titulo, mensaje)
                     VALUES (:user_id, "repartidor", :pedido, "pedido_preparado", :titulo, :mensaje)'
                )->execute([
                    'user_id' => $repUid,
                    'pedido'  => (int)$recipient['id_pedido'],
                    'titulo'  => '✅ ¡Pedido listo para recoger!',
                    'mensaje' => 'El pedido #' . (int)$recipient['id_pedido'] . ' ya está preparado y puedes recogerlo en el negocio.',
                ]);
            } elseif (!empty($recipient['tiempo_preparacion'])) {
                $db->prepare(
                    'INSERT INTO notificacion
                        (id_usuario, audiencia, id_pedido, tipo, titulo, mensaje)
                     VALUES (:user_id, "repartidor", :pedido, "tiempo_preparacion", :titulo, :mensaje)'
                )->execute([
                    'user_id' => $repUid,
                    'pedido'  => (int)$recipient['id_pedido'],
                    'titulo'  => '⏱️ Preparación: ' . (int)$recipient['tiempo_preparacion'] . ' min',
                    'mensaje' => 'El pedido #' . (int)$recipient['id_pedido'] . ' estará listo en aproximadamente ' . (int)$recipient['tiempo_preparacion'] . ' minutos.',
                ]);
            }
        }
    }

    echo json_encode([
        'updated'      => true,
        'codigo_r'     => $codigoR,
        'mensaje'      => '¡Pedido aceptado! Muestra este código al negocio: ' . $codigoR,
    ]);
    exit;
}

// ─── Avanzar estado: Asignado → En camino ────────────────────────────────────
if ($action === 'advance') {
    $deliveryId = (int)($_POST['id_entrega'] ?? 0);
    $stmt = $db->prepare(
        'SELECT e.estado, e.fecha_entrega, c.id_pedido
           FROM entrega e
           JOIN compra c ON c.id_compra = e.id_compra
          WHERE e.id_entrega = :delivery AND e.id_repartidor = :courier'
    );
    $stmt->execute(['delivery' => $deliveryId, 'courier' => $courierId]);
    $delivery = $stmt->fetch();

    if (!$delivery) {
        http_response_code(404);
        echo json_encode(['error' => 'Entrega no encontrada.']);
        exit;
    }

    if ($delivery['fecha_entrega']) {
        echo json_encode(['status' => 'already_completed']);
        exit;
    }

    if ((string)$delivery['estado'] !== 'Recogido en negocio') {
        http_response_code(409);
        echo json_encode(['error' => 'El negocio aún no valida la recogida del pedido.']);
        exit;
    }

    $db->beginTransaction();
    try {
        $db->prepare("UPDATE entrega SET estado = 'En camino' WHERE id_entrega = :delivery")->execute(['delivery' => $deliveryId]);
        $db->prepare("UPDATE pedido SET estado = 'En camino' WHERE id_pedido = :order")->execute(['order' => $delivery['id_pedido']]);
        $db->commit();
        echo json_encode(['status' => 'picked_up']);
    } catch (Throwable $e) {
        $db->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'No fue posible actualizar la entrega.']);
    }
    exit;
}

// ─── Finalizar entrega: repartidor ingresa CÓDIGO_C dado por el cliente ───────
if ($action === 'deliver_client_code') {
    $deliveryId    = (int)($_POST['id_entrega'] ?? 0);
    $codigoIngresado = trim((string)($_POST['codigo_cliente'] ?? ''));

    $stmt = $db->prepare(
        'SELECT e.estado, e.id_entrega, e.codigo_entrega, c.id_pedido
           FROM entrega e
           JOIN compra c ON c.id_compra = e.id_compra
          WHERE e.id_entrega = :delivery AND e.id_repartidor = :courier'
    );
    $stmt->execute(['delivery' => $deliveryId, 'courier' => $courierId]);
    $delivery = $stmt->fetch();

    if (!$delivery) {
        http_response_code(404);
        echo json_encode(['error' => 'Entrega no encontrada.']);
        exit;
    }

    $codigoEsperado = trim((string)$delivery['codigo_entrega']);

    if ($delivery['estado'] !== 'En camino' || !empty($delivery['fecha_confirmacion']) || !hash_equals($codigoEsperado, $codigoIngresado)) {
        http_response_code(422);
        echo json_encode(['error' => 'Código incorrecto. El código esperado es: ' . $codigoEsperado]);
        exit;
    }

    $db->beginTransaction();
    try {
        $update = $db->prepare("UPDATE entrega
            SET estado = 'Entregado', fecha_entrega = NOW(), fecha_confirmacion = NOW()
            WHERE id_entrega = :delivery AND estado = 'En camino' AND fecha_confirmacion IS NULL");
        $update->execute(['delivery' => $deliveryId]);
        if ($update->rowCount() !== 1) {
            throw new RuntimeException('La entrega ya fue confirmada o no está en camino.');
        }
        $db->prepare("UPDATE pedido SET estado = 'Entregado' WHERE id_pedido = :order")->execute(['order' => $delivery['id_pedido']]);
        // Cerrar también la compra como Completada
        $db->prepare("UPDATE compra SET estado = 'Completado' WHERE id_pedido = :order")->execute(['order' => $delivery['id_pedido']]);
        $db->commit();
        echo json_encode(['status' => 'delivered']);
    } catch (Throwable $e) {
        $db->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'No fue posible registrar la entrega.']);
    }
    exit;
}

// ─── Listar entregas ──────────────────────────────────────────────────────────
$type  = (string)($_GET['type'] ?? 'available');
$where = $type === 'available'
    ? 'e.id_repartidor IS NULL AND e.estado = "Pendiente"'
    : ($type === 'history'
        ? 'e.id_repartidor = :courier AND e.fecha_entrega IS NOT NULL'
        : 'e.id_repartidor = :courier AND e.fecha_entrega IS NULL AND e.fecha_confirmacion IS NULL');

$sql = 'SELECT e.id_entrega, e.estado AS entrega_estado, e.fecha_entrega, e.codigo_entrega,
               c.total, p.id_pedido, p.direccion_entrega, p.estado AS pedido_estado,
               p.tiempo_preparacion, p.hora_estimada_listo,
               u.nombre AS cliente_nombre, u.apellido AS cliente_apellido,
               n.nombre AS negocio_nombre
        FROM entrega e
        JOIN compra c ON c.id_compra = e.id_compra
        JOIN pedido p ON p.id_pedido = c.id_pedido
        JOIN usuario u ON u.id_usuario = p.id_usuario
        JOIN negocios n ON n.id_negocio = p.id_negocio
        WHERE ' . $where . ' ORDER BY p.fecha_creacion DESC';

$stmt = $db->prepare($sql);
if ($type !== 'available') {
    $stmt->bindValue(':courier', $courierId, PDO::PARAM_INT);
}
$stmt->execute();
$items = $stmt->fetchAll();

$pedidoIds = array_values(array_unique(array_map(
    static fn (array $item): int => (int) $item['id_pedido'],
    $items
)));
$detallesPorPedido = [];
if ($pedidoIds) {
    $placeholders = implode(',', array_fill(0, count($pedidoIds), '?'));
    $detalleStmt = $db->prepare(
        'SELECT d.id_pedido, d.cantidad, m.nombre
           FROM detalle_pedido d
           JOIN menu_items m ON m.id_menu_item = d.id_menu_item
          WHERE d.id_pedido IN (' . $placeholders . ')
           ORDER BY d.id_pedido, m.nombre'
    );
    $detalleStmt->execute($pedidoIds);
    foreach ($detalleStmt->fetchAll() as $detalle) {
        $detallesPorPedido[(int) $detalle['id_pedido']][] = [
            'nombre' => (string) $detalle['nombre'],
            'cantidad' => (int) $detalle['cantidad'],
        ];
    }
}

// El código del cliente siempre procede de la base de datos; no hay códigos de respaldo.
foreach ($items as &$item) {
    $item['codigo_c'] = (string)$item['codigo_entrega'];
    $item['codigo_recogida'] = generarCodigoRepartidor((int)$item['id_entrega']);
    $item['detalles'] = $detallesPorPedido[(int) $item['id_pedido']] ?? [];

    if (!empty($item['hora_estimada_listo'])) {
        $tsListo = strtotime((string)$item['hora_estimada_listo']);
        $diff = $tsListo - time();
        $item['segundos_restantes'] = max(0, $diff);
        $item['minutos_restantes'] = (int)ceil(max(0, $diff) / 60);
        $item['esta_vencido'] = ($diff <= 0);
    } else {
        $item['segundos_restantes'] = null;
        $item['minutos_restantes'] = null;
        $item['esta_vencido'] = false;
    }
}
unset($item);

echo json_encode([
    'online' => (bool)($_SESSION['repartidor_online'] ?? true),
    'items'  => $items,
]);
