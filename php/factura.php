<?php
date_default_timezone_set('America/Bogota');

// Librería del QR (la carpeta vendor/ está en la raíz del proyecto, un nivel arriba).
// Si no existe, la factura abre igual, solo que sin QR.
$autoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoload)) {
    require $autoload;
}

if (session_status() === PHP_SESSION_NONE) {
    session_name('SHIZEN_CLIENTE_SESSION');
    session_start();
}
if (empty($_SESSION['id_usuario'])) {
    header('Location: ../index.php?login_redirect=forms%2Fperfil.php#login');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/../BD/conexion.php';
require_once __DIR__ . '/../funciones/funciones.php';

// Funciones auxiliares (protegidas por si ya existen en funciones.php)
if (!function_exists('e')) {
    function e($t) { return htmlspecialchars((string)$t, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('cop')) {
    function cop($n) { return '$ ' . number_format((float)$n, 0, ',', '.'); }
}
function fecha_larga($ts) {
    $meses = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
    return date('d', $ts) . ' de ' . $meses[date('n', $ts) - 1] . ' de ' . date('Y', $ts);
}

$pdo      = obtenerConexion();
$userId   = (int)($_SESSION['id_usuario'] ?? 0);
$idPedido = (int)($_GET['id'] ?? 0);

// ============================================================
// DATOS DEL PEDIDO (solo si pertenece al usuario con sesión iniciada)
// ============================================================
$stmt = $pdo->prepare(
    'SELECT p.id_pedido, p.descripcion, p.fecha_creacion, n.nombre AS negocio_nombre,
            c.total AS factura_total, c.id_compra, e.id_repartidor
     FROM pedido p
     LEFT JOIN negocios n ON n.id_negocio = p.id_negocio
     LEFT JOIN compra c ON c.id_pedido = p.id_pedido
     LEFT JOIN entrega e ON e.id_compra = c.id_compra
     WHERE p.id_pedido = ? AND p.id_usuario = ?
     ORDER BY p.fecha_creacion DESC'
);
$stmt->execute([$idPedido, $userId]);
$orders = $stmt->fetch();

if (!$orders) {
    http_response_code(404);
    exit('Factura no encontrada');
}

$stmt = $pdo->prepare(
    'SELECT m.nombre AS plato, d.cantidad, d.valor AS precio
     FROM detalle_pedido d
     JOIN menu_items m ON m.id_menu_item = d.id_menu_item
     WHERE d.id_pedido = ?'
);
$stmt->execute([$idPedido]);
$platos = $stmt->fetchAll();

// ============================================================
// QR: enlace a esta misma factura (solo la abre el dueño del pedido)
// ============================================================
$esquema = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$carpeta = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
$enlace  = $esquema . '://' . $_SERVER['HTTP_HOST'] . $carpeta . '/' . basename($_SERVER['SCRIPT_NAME']) . '?id=' . $idPedido;

$qr = '';
if (class_exists('\chillerlan\QRCode\QRCode')) {
    $qr = (new \chillerlan\QRCode\QRCode)->render($enlace);
}

// ============================================================
// ARREGLO CON LOS DATOS PARA LA FACTURA
// ============================================================
$tsPedido = !empty($orders['fecha_creacion']) ? strtotime($orders['fecha_creacion']) : time();

$f = [
  'numero'     => $orders['id_compra'] ?? $orders['id_pedido'],
  'cliente'    => trim(($_SESSION['usuario_nombre'] ?? '') . ' ' . ($_SESSION['usuario_apellido'] ?? '')),
  'negocio'    => $orders['negocio_nombre'] ?? '',
  'pedido'     => '#' . $orders['id_pedido'],
  'repartidor' => !empty($orders['id_repartidor']) ? '#' . $orders['id_repartidor'] : 'No asignado',
  'hora'       => date('h:i A', $tsPedido),   // OJO: es la hora de creación del pedido (ver nota)
  'qr'         => $qr,
  'items'      => $platos,
];

$fechaFactura = fecha_larga($tsPedido);   // fecha de la compra
$hoy          = fecha_larga(time());      // fecha en que se genera/imprime

$total = 0;
foreach ($f['items'] as $i) { $total += $i['cantidad'] * $i['precio']; }

$logo = '../assets/logo_negro.png';
?><!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <base href="../">
  <title>Shizen · Factura de compra <?= e($f['numero']) ?></title>
  <link rel="stylesheet" href="css/styles.css">
  <link rel="stylesheet" href="css/nav.css?v=20260930-ingreso-icon-1">
  <link rel="stylesheet" href="css/modals.css?v=20261003-rating-fix-1">
  <link rel="stylesheet" href="css/factura.css">
</head>
<body>
  <?php include __DIR__ . '/../forms/navegacion.php'; ?>
<div class="factura-nav no-print">
  <?php include __DIR__ . '/../forms/boton_volver.php'; ?>
  <button class="btn-print" type="button" onclick="window.print()">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
    Imprimir / Guardar PDF
  </button>
</div>
<main class="page">
  <img id="marca" src="<?= e($logo) ?>" alt="">
  <header>
    <img src="<?= e($logo) ?>" alt="Shizen Food">
    <div class="meta">
      <h1>Factura de compra</h1>
      <p>N.º <?= e($f['numero']) ?></p>
      <p>Fecha: <?= e($fechaFactura) ?></p>
      <p>Bogotá, D.C., Colombia</p>
    </div>
  </header>

  <section class="info">
    <div><label>Cliente</label><div><?= e($f['cliente']) ?></div></div>
    <div><label>Negocio</label><div><?= e($f['negocio']) ?></div></div>
    <div><label>N.º de pedido</label><div><?= e($f['pedido']) ?></div></div>
    <div><label>ID del repartidor</label><div><?= e($f['repartidor']) ?></div></div>
    <div><label>Hora de entrega</label><div><?= e($f['hora']) ?></div></div>
  </section>

  <table>
    <thead><tr><th>Plato</th><th class="r q">Cant.</th><th class="r m">Precio unit.</th><th class="r m">Subtotal</th></tr></thead>
    <tbody>
    <?php foreach ($f['items'] as $i): ?>
      <tr>
        <td><?= e($i['plato']) ?></td>
        <td class="r q"><?= (int)$i['cantidad'] ?></td>
        <td class="r m"><?= cop($i['precio']) ?></td>
        <td class="r m"><?= cop($i['cantidad'] * $i['precio']) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot><tr><td colspan="3" class="r">Total (COP)</td><td class="r"><?= cop($total) ?></td></tr></tfoot>
  </table>

  <div id="qr"><?php if ($f['qr']): ?><img src="<?= e($f['qr']) ?>" alt="QR"><?php endif; ?></div>

  <footer>
    <span>Shizen Food · Bogotá, Colombia</span>
    <span>Generado el <?= e($hoy) ?></span>
  </footer>
</main>
</body>
</html>