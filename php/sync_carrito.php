<?php
declare(strict_types=1);

require_once __DIR__ . '/../BD/conexion.php';
require_once __DIR__ . '/../funciones/funciones.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name('SHIZEN_CLIENTE_SESSION');
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

$userId = (int)($_SESSION['id_usuario'] ?? 0);
if ($userId <= 0) {
    echo json_encode(['logged_in' => false, 'items' => []]);
    exit;
}

$pdo = obtenerConexion();
$action = $_SERVER['REQUEST_METHOD'] === 'POST' ? 'save' : 'load';

if ($action === 'save') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    $items = is_array($data) ? $data : [];

    $pdo->beginTransaction();
    try {
        // Limpiar carrito previo del usuario
        $del = $pdo->prepare('DELETE FROM carrito WHERE id_usuario = :u_id');
        $del->execute(['u_id' => $userId]);

        // Insertar ítems ordenados (de más reciente a más viejo)
        if (!empty($items)) {
            $ins = $pdo->prepare('
                INSERT INTO carrito (id_usuario, id_negocio, id_menu_item, cantidad, precio_unitario, fecha_actualizacion)
                VALUES (:u_id, :n_id, :m_id, :cant, :precio, NOW())
            ');
            foreach ($items as $it) {
                $mId   = (int)($it['id'] ?? 0);
                $bId   = (int)($it['businessId'] ?? $it['id_negocio'] ?? 0);
                $cant  = (int)($it['quantity'] ?? 1);
                $price = (float)($it['price'] ?? 0);

                if ($mId > 0 && $cant > 0) {
                    // Si bId no está definido, intentar inferirlo de menu_items
                    if ($bId <= 0) {
                        $findB = $pdo->prepare('SELECT id_negocio FROM menu_items WHERE id_menu_item = ?');
                        $findB->execute([$mId]);
                        $bId = (int)$findB->fetchColumn();
                    }
                    if ($bId > 0) {
                        $ins->execute([
                            'u_id'   => $userId,
                            'n_id'   => $bId,
                            'm_id'   => $mId,
                            'cant'   => $cant,
                            'precio' => $price,
                        ]);
                    }
                }
            }
        }
        $pdo->commit();
        echo json_encode(['success' => true, 'count' => count($items)]);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Error al guardar carrito en BD: ' . $e->getMessage()]);
    }
    exit;
}

// Cargar carrito desde la base de datos (ordenado de más reciente a más viejo)
try {
    $stmt = $pdo->prepare('
        SELECT c.id_carrito, c.id_menu_item AS id, c.id_negocio AS businessId, c.cantidad AS quantity,
               c.precio_unitario AS price, c.fecha_actualizacion,
               m.nombre AS name, m.imagen_url AS image, n.nombre AS restaurant
        FROM carrito c
        JOIN menu_items m ON m.id_menu_item = c.id_menu_item
        JOIN negocios n ON n.id_negocio = c.id_negocio
        WHERE c.id_usuario = :u_id
        ORDER BY c.fecha_actualizacion DESC, c.id_carrito DESC
    ');
    $stmt->execute(['u_id' => $userId]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($items as &$it) {
        $it['id'] = (int)$it['id'];
        $it['businessId'] = (int)$it['businessId'];
        $it['quantity'] = (int)$it['quantity'];
        $it['price'] = (float)$it['price'];
        $it['addedAt'] = strtotime($it['fecha_actualizacion']) * 1000;
        $it['image'] = resolverImagenUrl($it['image'] ?? '');
    }
    unset($it);

    echo json_encode(['logged_in' => true, 'items' => $items]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al cargar carrito: ' . $e->getMessage()]);
}
