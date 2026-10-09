<?php
require_once __DIR__ . '/../BD/conexion.php';
require_once __DIR__ . '/../funciones/funciones.php';
require_once __DIR__ . '/nav.php';
session_name('SHIZEN_REPARTIDOR_SESSION');
session_start();

$idUsuario = $_SESSION['id_usuario'] ?? null;
if (!$idUsuario || strtolower((string) ($_SESSION['usuario_rol'] ?? '')) !== 'repartidor') {
    header('Location: ../index.html?error=1');
    exit;
}

$conn = obtenerConexion();
$errores = [];
$tokenCsrf = $_SESSION['perfil_csrf'] ?? bin2hex(random_bytes(32));
$_SESSION['perfil_csrf'] = $tokenCsrf;
$stmt = $conn->prepare('SELECT nombre, apellido, email FROM usuario WHERE id_usuario = ?');
$stmt->execute([$idUsuario]);
$usuarioActual = $stmt->fetch();

if (!$usuarioActual) {
    session_destroy();
    header('Location: ../index.html?error=1');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tokenRecibido = (string) ($_POST['csrf_token'] ?? '');
    if (!hash_equals($tokenCsrf, $tokenRecibido)) {
        http_response_code(403);
        exit('Solicitud no válida.');
    }

    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $vehiculo = trim($_POST['vehiculo'] ?? '');

    if ($nombre === '' || $apellido === '' || mb_strlen($nombre) > 100 || mb_strlen($apellido) > 100) {
        $errores[] = 'El nombre y el apellido son obligatorios.';
    }
    $vehiculosPermitidos = ['Moto', 'Bicicleta', 'Carro', 'Monopatín'];
    if (!in_array($vehiculo, $vehiculosPermitidos, true)) {
        $errores[] = 'El vehículo seleccionado no es válido.';
    }

    if (!$errores) {
        $conn->beginTransaction();
        try {
            $stmt = $conn->prepare(
                'SELECT id_repartidor FROM repartidor WHERE id_usuario = ?'
            );
            $stmt->execute([$idUsuario]);
            if (!$stmt->fetch()) {
                throw new RuntimeException('No existe un perfil de repartidor asociado.');
            }

            $stmt = $conn->prepare(
                'UPDATE usuario SET nombre = ?, apellido = ? WHERE id_usuario = ?'
            );
            $stmt->execute([$nombre, $apellido, $idUsuario]);

            $stmt = $conn->prepare(
                'UPDATE repartidor SET nombre = ?, apellido = ?, vehiculo = ? WHERE id_usuario = ?'
            );
            $stmt->execute([$nombre, $apellido, $vehiculo, $idUsuario]);

            $conn->commit();
            $_SESSION['usuario_nombre'] = $nombre;
            header('Location: perfil.php?mensaje=actualizado');
            exit;
        } catch (PDOException | RuntimeException $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            $errores[] = 'No se pudo actualizar el perfil. Intenta de nuevo.';
        }
    }
}

$stmt = $conn->prepare(
    'SELECT u.nombre, u.apellido, u.email, r.id_repartidor, r.vehiculo, r.foto_url,
            r.cedula, r.estado AS repartidor_estado,
            r.licencia_conduccion_url, r.soat_url, r.tarjeta_propiedad_url
     FROM usuario u
     LEFT JOIN repartidor r ON r.id_usuario = u.id_usuario
     WHERE u.id_usuario = ?'
);
$stmt->execute([$idUsuario]);
$perfil = $stmt->fetch();

if (!$perfil) {
    session_destroy();
    header('Location: ../index.html?error=1');
    exit;
}

$nombreCompleto = trim($perfil['nombre'] . ' ' . $perfil['apellido']);
$iniciales = strtoupper(substr((string)$perfil['nombre'], 0, 1) . substr((string)$perfil['apellido'], 0, 1));
$fotoUrl = rutaDocumento($perfil['foto_url'] ?? '');
$fotoHtml = $fotoUrl !== ''
    ? '<img src="' . htmlspecialchars($fotoUrl, ENT_QUOTES, 'UTF-8') . '" alt="Foto de ' . htmlspecialchars($nombreCompleto, ENT_QUOTES, 'UTF-8') . '">'
    : '<span>' . htmlspecialchars($iniciales ?: 'R', ENT_QUOTES, 'UTF-8') . '</span>';
$vehiculo = $perfil['vehiculo'] ?: 'Bicicleta';
$cedula = $perfil['cedula'] ?: 'No registrada';
$estadoRepartidor = $perfil['repartidor_estado'] ?: 'Activo';

$mensaje = isset($_GET['mensaje']) ? 'Perfil actualizado correctamente.' : '';
$alertasHtml = '';
if ($mensaje !== '') {
    $alertasHtml .= '<p class="profile-alert profile-alert--success">&#10003; ' .
        htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') . '</p>';
}
foreach ($errores as $error) {
    $alertasHtml .= '<p class="profile-alert profile-alert--error">&#9888; ' .
        htmlspecialchars($error, ENT_QUOTES, 'UTF-8') . '</p>';
}

function rutaDocumento(?string $ruta): string
{
    $ruta = trim((string) $ruta);
    if ($ruta === '' || preg_match('/[\x00-\x1F\x7F]/', $ruta)) {
        return '';
    }
    if (preg_match('/^https?:\/\//i', $ruta) || preg_match('/^\/(?!\/)/', $ruta)) {
        return $ruta;
    }
    if (preg_match('/^[a-z][a-z0-9+.-]*:/i', $ruta) || str_starts_with($ruta, '//')) {
        return '';
    }
    if (str_starts_with($ruta, 'assets/')) {
        return '/shizenhome/' . ltrim($ruta, './');
    }
    if (str_starts_with($ruta, 'legales/')) {
        return '/shizenhome/' . ltrim($ruta, './');
    }
    return '../' . ltrim($ruta, './');
}

$documentos = [
    'Licencia de conducción' => rutaDocumento($perfil['licencia_conduccion_url']),
    'SOAT' => rutaDocumento($perfil['soat_url']),
    'Tarjeta de propiedad' => rutaDocumento($perfil['tarjeta_propiedad_url']),
];
$documentosHtml = '';
foreach ($documentos as $nombreDocumento => $rutaDocumentoActual) {
    if ($rutaDocumentoActual === '') {
        continue;
    }
    $esImagen = (bool) preg_match('/\.(?:jpe?g|png|webp|gif)(?:[?#].*)?$/i', $rutaDocumentoActual);
    $rutaSegura = htmlspecialchars($rutaDocumentoActual, ENT_QUOTES, 'UTF-8');
    $nombreSeguro = htmlspecialchars($nombreDocumento, ENT_QUOTES, 'UTF-8');
    $contenidoDocumento = $esImagen
        ? sprintf('<img src="%s" alt="%s">', $rutaSegura, $nombreSeguro)
        : sprintf('<a href="%s" target="_blank" rel="noopener">&#128196; Ver documento</a>', $rutaSegura);
    $documentosHtml .= sprintf(
        '<article class="vehicle-document-card"><label>%s</label>%s</article>',
        $nombreSeguro,
        $contenidoDocumento
    );
}
if ($documentosHtml === '') {
    $documentosHtml = '<div class="profile-empty-state"><div class="profile-empty-icon">&#128196;</div><p>No tienes documentos registrados.</p></div>';
}

$stmtStats = $conn->prepare(
    'SELECT COUNT(CASE WHEN e.fecha_entrega IS NOT NULL AND DATE(e.fecha_entrega) = CURDATE() THEN 1 END) AS entregas_hoy,
            COALESCE(SUM(CASE WHEN e.fecha_entrega IS NOT NULL AND DATE(e.fecha_entrega) = CURDATE() THEN c.total ELSE 0 END), 0) AS ganancias_hoy
     FROM repartidor r
     LEFT JOIN entrega e ON e.id_repartidor = r.id_repartidor
     LEFT JOIN compra c ON c.id_compra = e.id_compra
     WHERE r.id_usuario = ?'
);
$stmtStats->execute([$idUsuario]);
$statsRepartidor = $stmtStats->fetch() ?: [];
$entregasHoy = (int)($statsRepartidor['entregas_hoy'] ?? 0);
$gananciasHoy = '$' . number_format((float)($statsRepartidor['ganancias_hoy'] ?? 0), 0, ',', '.');

$stmtRating = $conn->prepare(
    'SELECT COALESCE(AVG(cr.puntuacion), 5.0) AS rating_promedio, COUNT(cr.id_calificacion_repartidor) AS total_resenas
     FROM repartidor r
     LEFT JOIN calificacion_repartidor cr ON cr.id_repartidor = r.id_repartidor
     WHERE r.id_usuario = ?'
);
$stmtRating->execute([$idUsuario]);
$ratingData = $stmtRating->fetch() ?: [];
$ratingPromedio = number_format((float)($ratingData['rating_promedio'] ?? 5.0), 1);
$totalResenas = (int)($ratingData['total_resenas'] ?? 0);

$stmtRecientes = $conn->prepare(
    'SELECT e.id_entrega, e.estado, e.fecha_entrega, c.total, n.nombre AS negocio_nombre, p.descripcion
     FROM repartidor r
     JOIN entrega e ON e.id_repartidor = r.id_repartidor
     JOIN compra c ON c.id_compra = e.id_compra
     JOIN pedido p ON p.id_pedido = c.id_pedido
     LEFT JOIN negocios n ON n.id_negocio = p.id_negocio
     WHERE r.id_usuario = ? AND e.fecha_entrega IS NOT NULL
     ORDER BY e.fecha_entrega DESC
     LIMIT 5'
);
$stmtRecientes->execute([$idUsuario]);
$entregasRecientes = $stmtRecientes->fetchAll() ?: [];

$entregasRecientesHtml = '';
if ($entregasRecientes) {
    foreach ($entregasRecientes as $ent) {
        $negocio = htmlspecialchars((string)($ent['negocio_nombre'] ?? 'Restaurante Shizen'), ENT_QUOTES, 'UTF-8');
        $desc = htmlspecialchars((string)($ent['descripcion'] ?? 'Pedido entregado'), ENT_QUOTES, 'UTF-8');
        $monto = '$' . number_format((float)($ent['total'] ?? 0), 0, ',', '.');
        $fecha = htmlspecialchars((string)($ent['fecha_entrega'] ?? ''), ENT_QUOTES, 'UTF-8');
        $entregasRecientesHtml .= sprintf(
            '<div class="delivery-history-item">
               <div class="delivery-history-info">
                 <strong>%s</strong>
                 <small>%s</small>
               </div>
               <div class="delivery-history-meta">
                 <span>%s</span>
                 <small>%s</small>
               </div>
             </div>',
            $negocio,
            $desc,
            $monto,
            $fecha
        );
    }
} else {
    $entregasRecientesHtml = '<div class="profile-empty-state"><div class="profile-empty-icon">&#128692;</div><p>Aún no tienes entregas completadas hoy.</p></div>';
}

$vehiculosSelectHtml = '';
foreach (['Moto', 'Bicicleta', 'Carro', 'Monopatín'] as $opt) {
    $selected = ($opt === $vehiculo) ? ' selected' : '';
    $vehiculosSelectHtml .= sprintf('<option value="%s"%s>%s</option>', $opt, $selected, $opt);
}

$template = file_get_contents(__DIR__ . '/../pages/perfil.html');
$replacements = [
    '{{NOMBRE_COMPLETO}}' => htmlspecialchars($nombreCompleto, ENT_QUOTES, 'UTF-8'),
    '{{NOMBRE}}' => htmlspecialchars((string)$perfil['nombre'], ENT_QUOTES, 'UTF-8'),
    '{{APELLIDO}}' => htmlspecialchars((string)$perfil['apellido'], ENT_QUOTES, 'UTF-8'),
    '{{INICIALES}}' => htmlspecialchars($iniciales ?: 'R', ENT_QUOTES, 'UTF-8'),
    '{{FOTO_URL}}' => htmlspecialchars($fotoUrl, ENT_QUOTES, 'UTF-8'),
    '{{FOTO_HTML}}' => $fotoHtml,
    '{{EMAIL}}' => htmlspecialchars((string)$perfil['email'], ENT_QUOTES, 'UTF-8'),
    '{{VEHICULO}}' => htmlspecialchars($vehiculo, ENT_QUOTES, 'UTF-8'),
    '{{VEHICULOS_OPTIONS}}' => $vehiculosSelectHtml,
    '{{CEDULA}}' => htmlspecialchars($cedula, ENT_QUOTES, 'UTF-8'),
    '{{ESTADO}}' => htmlspecialchars($estadoRepartidor, ENT_QUOTES, 'UTF-8'),
    '{{DOCUMENTOS_VEHICULO}}' => $documentosHtml,
    '{{ENTREGAS_RECIENTES}}' => $entregasRecientesHtml,
    '{{RATING_PROMEDIO}}' => $ratingPromedio,
    '{{TOTAL_RESENAS}}' => (string) $totalResenas,
    '{{CSRF_TOKEN}}' => htmlspecialchars($tokenCsrf, ENT_QUOTES, 'UTF-8'),
    '{{ALERTAS}}' => $alertasHtml,
    '{{ENTREGAS_HOY}}' => (string) $entregasHoy,
    '{{GANANCIAS_HOY}}' => htmlspecialchars($gananciasHoy, ENT_QUOTES, 'UTF-8'),
    '{{NAVBAR}}' => getNavHtml('perfil'),
];
echo strtr($template, $replacements);

