<?php
require_once __DIR__ . '/../BD/conexion.php';
require_once __DIR__ . '/../funciones/funciones.php';
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
        $errores[] = 'El vehículo es obligatorio.';
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
    'SELECT u.nombre, u.apellido, u.email, r.vehiculo, r.foto_url,
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
$iniciales = strtoupper(substr($perfil['nombre'], 0, 1) . substr($perfil['apellido'], 0, 1));
$fotoUrl = rutaDocumento($perfil['foto_url'] ?? '');
$fotoHtml = $fotoUrl !== ''
    ? '<img src="' . htmlspecialchars($fotoUrl, ENT_QUOTES, 'UTF-8') . '" alt="Foto de ' . htmlspecialchars($nombreCompleto, ENT_QUOTES, 'UTF-8') . '">'
    : '<span>' . htmlspecialchars($iniciales, ENT_QUOTES, 'UTF-8') . '</span>';
$vehiculo = $perfil['vehiculo'] ?: 'No registrado';
$mensaje = isset($_GET['mensaje']) ? 'Perfil actualizado correctamente.' : '';
$alertasHtml = '';
if ($mensaje !== '') {
    $alertasHtml .= '<p class="profile-alert profile-alert--success">' .
        htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') . '</p>';
}
foreach ($errores as $error) {
    $alertasHtml .= '<p class="profile-alert profile-alert--error">' .
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
        : sprintf('<a href="%s" target="_blank" rel="noopener">Abrir documento</a>', $rutaSegura);
    $documentosHtml .= sprintf(
        '<article class="vehicle-document-card"><label>%s</label><input class="field" type="text" value="%s" readonly aria-readonly="true"><script>(function(c){if(!c||c.dataset.validacionCampo)return;c.dataset.validacionCampo="1";var e=c.nextElementSibling&&c.nextElementSibling.classList.contains("field-error-inline")?c.nextElementSibling:document.createElement("small");e.className="field-error-inline";e.style.cssText="display:block;color:#c62828;font-size:12px;margin-top:4px;min-height:1em;";if(!e.parentNode)c.insertAdjacentElement("afterend",e);function v(){var x=c.required&&!c.value.trim(),i=x||!c.validity.valid;e.textContent=i?(x?"Este campo es obligatorio.":c.validationMessage):"";c.setAttribute("aria-invalid",i?"true":"false");return!i}["input","change","blur"].forEach(function(t){c.addEventListener(t,v)});if(c.form)c.form.addEventListener("submit",function(a){if(!v())a.preventDefault()})})(document.currentScript.previousElementSibling);</script>%s</article>',
        $nombreSeguro,
        $rutaSegura,
        $contenidoDocumento
    );
}
if ($documentosHtml === '') {
    $documentosHtml = '';
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

$template = file_get_contents(__DIR__ . '/../pages/perfil.html');
$replacements = [
    '{{NOMBRE_COMPLETO}}' => htmlspecialchars($nombreCompleto, ENT_QUOTES, 'UTF-8'),
    '{{NOMBRE}}' => htmlspecialchars($perfil['nombre'], ENT_QUOTES, 'UTF-8'),
    '{{APELLIDO}}' => htmlspecialchars($perfil['apellido'], ENT_QUOTES, 'UTF-8'),
    '{{INICIALES}}' => htmlspecialchars($iniciales, ENT_QUOTES, 'UTF-8'),
    '{{FOTO_URL}}' => htmlspecialchars($fotoUrl, ENT_QUOTES, 'UTF-8'),
    '{{FOTO_HTML}}' => $fotoHtml,
    '{{EMAIL}}' => htmlspecialchars($perfil['email'], ENT_QUOTES, 'UTF-8'),
    '{{VEHICULO}}' => htmlspecialchars($vehiculo, ENT_QUOTES, 'UTF-8'),
    '{{DOCUMENTOS_VEHICULO}}' => $documentosHtml,
    '{{CSRF_TOKEN}}' => htmlspecialchars($tokenCsrf, ENT_QUOTES, 'UTF-8'),
    '{{ALERTAS}}' => $alertasHtml,
    '{{ENTREGAS_HOY}}' => (string) $entregasHoy,
    '{{GANANCIAS_HOY}}' => htmlspecialchars($gananciasHoy, ENT_QUOTES, 'UTF-8'),
];
echo strtr($template, $replacements);
