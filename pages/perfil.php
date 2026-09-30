<?php
declare(strict_types=1);

require_once __DIR__ . '/../php/config/database.php';
require_once __DIR__ . '/../php/config/auth.php';
require_auth();

$user        = current_user();
$currentPage = 'perfil';
$pageTitle   = 'Perfil del Negocio';

$success = '';
$error   = '';
$db      = database();
$businessId = (int)($_SESSION['business_id'] ?? 0);

// Obtener datos del negocio actual y su configuración
$business = null;
$businessConfig = null;
$ratingAvg = null;
try {
    $bStmt = $db->prepare('SELECT * FROM negocios WHERE id_negocio = :id');
    $bStmt->execute(['id' => $businessId]);
    $business = $bStmt->fetch(PDO::FETCH_ASSOC);

    // Promedio real de calificaciones
    $rStmt = $db->prepare('SELECT AVG(puntuacion) as avg_rating FROM calificacion WHERE id_negocio = :id');
    $rStmt->execute(['id' => $businessId]);
    $rRow = $rStmt->fetch(PDO::FETCH_ASSOC);
    if ($rRow && $rRow['avg_rating'] !== null) {
        $ratingAvg = round((float)$rRow['avg_rating'], 1);
    }

    // Configuración operativa
    $cStmt = $db->prepare('SELECT * FROM negocio_configuracion WHERE id_negocio = :id');
    $cStmt->execute(['id' => $businessId]);
    $businessConfig = $cStmt->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    // 
}

// Procesar actualización de: Foto (Logo), Nombre y Descripción
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'update_business_profile';

    if ($action === 'update_business_profile') {
        $nombreNegocio      = trim((string)($_POST['nombre_negocio'] ?? ''));
        $descripcionNegocio = trim((string)($_POST['descripcion_negocio'] ?? ''));
        $logoUrl            = trim((string)($_POST['logo_url'] ?? ''));

        // Procesar subida de archivo de logo si se seleccionó uno nuevo directamente en shizenhome
        if (!empty($_FILES['logo_imagen']['name']) && ($_FILES['logo_imagen']['error'] ?? 1) === UPLOAD_ERR_OK) {
            $homeUploadPath = realpath(__DIR__ . '/../../shizenhome/assets/Imagenes_prueba') ?: ('C:/xampp/htdocs/shizenhome/assets/Imagenes_prueba');
            if (!is_dir($homeUploadPath)) {
                mkdir($homeUploadPath, 0755, true);
            }
            $uploadDir = rtrim($homeUploadPath, '/\\') . DIRECTORY_SEPARATOR;
            $finfo    = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $_FILES['logo_imagen']['tmp_name']);
            finfo_close($finfo);
            $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            if (!in_array($mimeType, $allowed, true)) {
                $error = 'Tipo de imagen no permitido. Usa JPG, PNG, WebP o GIF.';
            } elseif ($_FILES['logo_imagen']['size'] > 5 * 1024 * 1024) {
                $error = 'La imagen no debe superar los 5 MB.';
            } else {
                $ext      = pathinfo($_FILES['logo_imagen']['name'], PATHINFO_EXTENSION);
                $filename = 'logo_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . strtolower($ext);
                if (move_uploaded_file($_FILES['logo_imagen']['tmp_name'], $uploadDir . $filename)) {
                    $logoUrl = 'assets/Imagenes_prueba/' . rawurlencode($filename);
                } else {
                    $error = 'No se pudo guardar la imagen subida en el servidor.';
                }
            }
        }

        if (!$error) {
            if (mb_strlen($nombreNegocio) < 2) {
                $error = 'Ingresa un nombre de negocio válido.';
            } elseif (mb_strlen($descripcionNegocio) < 5) {
                $error = 'La descripción debe tener al menos 5 caracteres.';
            } else {
                try {
                    $stmt = $db->prepare(
                        'UPDATE negocios SET nombre = :nombre, descripcion = :descripcion, logo_url = :logo WHERE id_negocio = :id'
                    );
                    $stmt->execute([
                        'nombre'      => $nombreNegocio,
                        'descripcion' => $descripcionNegocio,
                        'logo'        => $logoUrl,
                        'id'          => $businessId,
                    ]);

                    if ($business) {
                        $business['nombre']      = $nombreNegocio;
                        $business['descripcion'] = $descripcionNegocio;
                        $business['logo_url']    = $logoUrl;
                    }
                    $_SESSION['business_name'] = $nombreNegocio;
                    $success = 'Perfil del negocio actualizado correctamente.';
                } catch (Throwable $e) {
                    $error = 'Error al actualizar el perfil del negocio: ' . $e->getMessage();
                }
            }
        }
    }
}

// Función auxiliar para resolver la ruta de la imagen desde /pages/
function resolveImgUrl(?string $url): string {
    $url = trim((string)$url);
    if ($url === '') {
        return '../assets/vegano_central_logo.jpg';
    }
    if (preg_match('#^https?://#i', $url) || strpos($url, 'data:image') === 0) {
        return $url;
    }
    if (str_contains($url, 'Imagenes_prueba/')) {
        return '/shizenhome/assets/Imagenes_prueba/' . rawurlencode(basename($url));
    }
    if (str_starts_with($url, '/shizenhome/')) {
        return $url;
    }
    if (strpos($url, '/shizennegocio/') === 0) {
        return '..' . substr($url, strlen('/shizennegocio'));
    }
    if (strpos($url, '/') === 0) {
        return $url;
    }
    return '/shizenhome/' . ltrim($url, '/');
}

$rawLogo         = !empty($business['logo_url']) ? $business['logo_url'] : 'assets/vegano_central_logo.jpg';
$displayLogo     = resolveImgUrl($rawLogo);
$ownerName       = trim(($user['nombre'] ?? '') . ' ' . ($user['apellido'] ?? '')) ?: 'Propietario';
$ownerEmail      = $user['email'] ?? '';
$categoriaText   = !empty($business['categoria']) ? $business['categoria'] : 'Alimentación Vegana';
$direccionText   = !empty($business['direccion']) ? $business['direccion'] : 'Dirección no registrada';
$coberturaText   = !empty($businessConfig['radius']) ? $businessConfig['radius'] . ' km de cobertura' : 'Radio por configurar';
$prepTimeText    = !empty($businessConfig['prep_time']) ? $businessConfig['prep_time'] . ' min' : 'Por definir';
$horarioText     = (!empty($business['hora_apertura']) && !empty($business['hora_cierre'])) 
                     ? date('h:i A', strtotime($business['hora_apertura'])) . ' - ' . date('h:i A', strtotime($business['hora_cierre']))
                     : 'Horario no configurado';
$costoEnvioText  = !empty($businessConfig['min_order']) ? 'Pedido mín: $' . number_format((float)$businessConfig['min_order'], 0, ',', '.') . ' COP' : 'Tarifa estándar';
$descripcionText = (string)($business['descripcion'] ?? '');
$ratingLabel     = $ratingAvg !== null ? number_format($ratingAvg, 1) . ' ★' : 'Nuevo';
?>
<!DOCTYPE html>
<html lang="es">
<?php require_once __DIR__ . '/../php/includes/head.php'; ?>
<body class="layout">

<?php require_once __DIR__ . '/../php/includes/sidebar.php'; ?>

<div class="main">
  <?php require_once __DIR__ . '/../php/includes/topbar.php'; ?>
  <main class="content">

    <div class="page-header">
      <div>
        <h1 class="page-header-title">Perfil del Negocio</h1>
        <p class="page-header-sub">Información general y edición de tu establecimiento</p>
      </div>
    </div>

    <?php if ($success): ?>
      <div class="alert alert-success">
        <i class="bx bx-check-circle" style="font-size:18px;flex-shrink:0"></i>
        <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
      </div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="alert alert-danger">
        <i class="bx bx-error-circle" style="font-size:18px;flex-shrink:0"></i>
        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
      </div>
    <?php endif; ?>

    <div style="max-width:860px">
      
      <!-- SECCIÓN SUPERIOR: LOGO Y DATOS DE ENCABEZADO -->
      <div style="display:flex;align-items:center;gap:20px;padding-bottom:20px;border-bottom:1px solid #f3f4f6;flex-wrap:wrap">
        
        <!-- Logo de la empresa -->
        <div style="width:110px;height:110px;border-radius:12px;overflow:hidden;border:2px solid #e5e7eb;background:#f9fafb;flex-shrink:0;display:flex;align-items:center;justify-content:center">
          <img src="<?= htmlspecialchars($displayLogo, ENT_QUOTES, 'UTF-8') ?>" 
               alt="Logo de <?= htmlspecialchars($business['nombre'] ?? 'Negocio', ENT_QUOTES, 'UTF-8') ?>" 
               style="width:100%;height:100%;object-fit:cover;display:block">
        </div>

        <div style="flex:1">
          <h2 style="font-size:24px;font-weight:800;color:#111827;margin:0 0 6px">
            <?= htmlspecialchars($business['nombre'] ?? 'Restaurante Vegano Central', ENT_QUOTES, 'UTF-8') ?>
          </h2>
          <p style="font-size:14px;color:#4b5563;margin:0 0 10px;line-height:1.4">
            <?= htmlspecialchars($descripcionText, ENT_QUOTES, 'UTF-8') ?>
          </p>
          <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
            <span style="background:#fef3c7;color:#d97706;font-size:12px;font-weight:800;padding:4px 10px;border-radius:6px;display:inline-flex;align-items:center;gap:4px">
              <?= htmlspecialchars($ratingLabel, ENT_QUOTES, 'UTF-8') ?>
            </span>
            <span style="background:#f3f4f6;color:#374151;font-size:12px;font-weight:600;padding:4px 10px;border-radius:6px">
              ⏱️ <?= htmlspecialchars($prepTimeText, ENT_QUOTES, 'UTF-8') ?>
            </span>
            <span style="background:var(--green-light);color:var(--green-dark);font-size:12px;font-weight:700;padding:4px 10px;border-radius:6px">
              🏷️ <?= htmlspecialchars($categoriaText, ENT_QUOTES, 'UTF-8') ?>
            </span>
          </div>
        </div>
      </div>

      <!-- SECCIÓN INTERMEDIA: DATOS DEL NEGOCIO (SOLO LECTURA) -->
      <div style="padding:20px 0;border-bottom:1px solid #f3f4f6">
        <h3 style="font-size:15px;font-weight:800;color:#374151;margin:0 0 14px;text-transform:uppercase;letter-spacing:0.5px">
          📋 Datos del Establecimiento
        </h3>
        
        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));gap:16px">
          <div>
            <span style="font-size:12px;color:#6b7280;display:block">Categoría principal:</span>
            <strong style="font-size:14px;color:#111827"><?= htmlspecialchars($categoriaText, ENT_QUOTES, 'UTF-8') ?></strong>
          </div>
          <div>
            <span style="font-size:12px;color:#6b7280;display:block">Calificación y tiempos:</span>
            <strong style="font-size:14px;color:#111827"><?= htmlspecialchars($ratingLabel, ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars($prepTimeText, ENT_QUOTES, 'UTF-8') ?></strong>
          </div>
          <div>
            <span style="font-size:12px;color:#6b7280;display:block">Dirección / Cobertura:</span>
            <strong style="font-size:14px;color:#111827"><?= htmlspecialchars($direccionText, ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($coberturaText, ENT_QUOTES, 'UTF-8') ?>)</strong>
          </div>
          <div>
            <span style="font-size:12px;color:#6b7280;display:block">Horario de atención:</span>
            <strong style="font-size:14px;color:#111827"><?= htmlspecialchars($horarioText, ENT_QUOTES, 'UTF-8') ?></strong>
          </div>
          <div>
            <span style="font-size:12px;color:#6b7280;display:block">Costo de envío:</span>
            <strong style="font-size:14px;color:var(--green-dark)"><?= htmlspecialchars($costoEnvioText, ENT_QUOTES, 'UTF-8') ?></strong>
          </div>
          <div>
            <span style="font-size:12px;color:#6b7280;display:block">Propietario registrado:</span>
            <strong style="font-size:14px;color:#111827"><?= htmlspecialchars($ownerName, ENT_QUOTES, 'UTF-8') ?></strong>
          </div>
        </div>
      </div>

      <!-- SECCIÓN INFERIOR: FORMULARIO DE EDICIÓN -->
      <div style="padding-top:20px;border-top:1px solid #f3f4f6">
        <?php $isEditing = isset($_GET['edit']) || !empty($error); ?>
        <?php if (!$isEditing): ?>
          <a href="perfil.php?edit=1" class="btn btn-secondary" style="font-weight:700;padding:8px 18px;text-decoration:none;display:inline-flex;align-items:center;gap:6px">
            ✏️ Editar Perfil
          </a>
        <?php else: ?>
          <div id="form-editar-perfil" style="margin-top:10px">

            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
              <h3 style="font-size:16px;font-weight:800;color:#111827;margin:0">Editar Perfil</h3>
              <a href="perfil.php" style="color:#6b7280;font-size:13px;text-decoration:none;padding:4px 8px;border-radius:6px;font-weight:600">
                ✕ Cancelar
              </a>
            </div>

            <form method="post" enctype="multipart/form-data" novalidate>
              <input type="hidden" name="action" value="update_business_profile">
              <input type="hidden" name="logo_url" id="logoUrlHidden" value="<?= htmlspecialchars($rawLogo, ENT_QUOTES, 'UTF-8') ?>">

            <!-- Campo 1: Foto / Logo con el mismo estilo del modal de productos -->
            <div class="form-group" style="margin-bottom:18px">
              <label class="form-label" style="font-weight:600">Logo del negocio</label>
              
              <!-- Vista previa actual -->
              <div id="logoPreviewWrap" style="margin-bottom:10px">
                <img id="logoPreviewImg" src="<?= htmlspecialchars($displayLogo, ENT_QUOTES, 'UTF-8') ?>" 
                     alt="Preview Logo" style="width:110px;height:110px;border-radius:10px;object-fit:cover;border:2px solid #e5e7eb;display:block">
              </div>

              <!-- Caja estilo modal de platos para subir nueva imagen -->
              <label for="logoImagenInput" style="display:flex;align-items:center;gap:12px;cursor:pointer;border:2px dashed #d1d5db;border-radius:10px;padding:14px 16px;background:#f9fafb;transition:border-color .2s" onmouseover="this.style.borderColor='var(--green)'" onmouseout="this.style.borderColor='#d1d5db'">
                <i class="bx bx-image-add" style="font-size:26px;color:var(--green)"></i>
                <div>
                  <div style="font-weight:600;color:#374151;font-size:13px">Subir nueva imagen de logo</div>
                  <div style="font-size:11px;color:#9ca3af">JPG, PNG, WebP o GIF · Máx. 5 MB</div>
                </div>
              </label>
              <input type="file" id="logoImagenInput" name="logo_imagen" accept="image/*" style="display:none" onchange="previewProfileLogo(this)">
            </div>

            <!-- Campo 2: Nombre -->
            <div class="form-group" style="margin-bottom:16px">
              <label class="form-label" for="nombre_negocio" style="font-weight:600">Nombre del negocio *</label>
              <input type="text" id="nombre_negocio" name="nombre_negocio" class="form-control"
                     value="<?= htmlspecialchars($business['nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                     placeholder="Nombre del negocio" required minlength="2">
              <script>
                (function(){var c=document.currentScript.previousElementSibling;if(!c)return;var e=document.createElement('small');e.className='field-error-inline';e.style.cssText='display:block;color:#dc2626;font-size:12px;margin-top:4px;';c.insertAdjacentElement('afterend',e);function v(){var empty=c.required&&!c.value.trim();var i=empty||c.value.trim().length<2;e.textContent=i?(empty?'Este campo es obligatorio.':'Mínimo 2 caracteres.'):'';c.setAttribute('aria-invalid',i?'true':'false');return!i;}['input','change','blur'].forEach(function(t){c.addEventListener(t,v);});if(c.form)c.form.addEventListener('submit',function(a){if(!v())a.preventDefault();});}());
              </script>
            </div>

            <!-- Campo 3: Descripción -->
            <div class="form-group" style="margin-bottom:20px">
              <label class="form-label" for="descripcion_negocio" style="font-weight:600">Descripción corta *</label>
              <textarea id="descripcion_negocio" name="descripcion_negocio" class="form-control" rows="3"
                        placeholder="Describe tu negocio..." required minlength="5"
                        style="padding:10px;border-radius:8px;font-family:inherit"><?= htmlspecialchars($descripcionText, ENT_QUOTES, 'UTF-8') ?></textarea>
              <script>
                (function(){var c=document.currentScript.previousElementSibling;if(!c)return;var e=document.createElement('small');e.className='field-error-inline';e.style.cssText='display:block;color:#dc2626;font-size:12px;margin-top:4px;';c.insertAdjacentElement('afterend',e);function v(){var empty=c.required&&!c.value.trim();var i=empty||c.value.trim().length<5;e.textContent=i?(empty?'Este campo es obligatorio.':'Mínimo 5 caracteres.'):'';c.setAttribute('aria-invalid',i?'true':'false');return!i;}['input','change','blur'].forEach(function(t){c.addEventListener(t,v);});if(c.form)c.form.addEventListener('submit',function(a){if(!v())a.preventDefault();});}());
              </script>
            </div>

            <button type="submit" class="btn btn-primary" style="font-weight:700;padding:10px 22px">
              <i class="bx bx-save"></i> Guardar Cambios
            </button>
          </form>
        </div>
        <?php endif; ?>
      </div>

    </div>

  </main>
</div>

<script>
function previewProfileLogo(input) {
  const previewImg = document.getElementById('logoPreviewImg');
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = function(e) {
      previewImg.src = e.target.result;
    };
    reader.readAsDataURL(input.files[0]);
  }
}
</script>

</body>
</html>
