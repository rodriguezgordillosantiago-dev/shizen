<?php
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

$pdo = obtenerConexion();
$userId = (int)($_SESSION['id_usuario'] ?? 0);

$nombrePerfil = trim(($_SESSION['usuario_nombre'] ?? '') . ' ' . ($_SESSION['usuario_apellido'] ?? ''));
$correoPerfil = $_SESSION['usuario_email'] ?? '';
$avatarFiles = glob(__DIR__ . '/../assets/Perfil/*.{png,jpg,jpeg,webp}', GLOB_BRACE) ?: [];
$defaultAvatar = $avatarFiles ? 'assets/Perfil/' . basename($avatarFiles[0]) : '';
$avatarPerfil = $_SESSION['usuario_avatar'] ?? $defaultAvatar;

$stmtOrders = $pdo->prepare(
    "SELECT p.id_pedido, p.descripcion, p.estado, p.fecha_creacion, n.nombre AS negocio_nombre,
            c.total, e.estado AS entrega_estado
     FROM pedido p
     LEFT JOIN negocios n ON n.id_negocio = p.id_negocio
     LEFT JOIN compra c ON c.id_pedido = p.id_pedido
     LEFT JOIN entrega e ON e.id_compra = c.id_compra
     WHERE p.id_usuario = ?
     AND (
    LOWER(p.estado) IN ('en camino', 'entregado') OR LOWER (COALESCE(e.estado, '')) IN ('en camino', 'entregado'))
     ORDER BY p.fecha_creacion DESC"
);
$stmtOrders->execute([$userId]);
$orders = $stmtOrders->fetchAll();

$stmtReviews = $pdo->prepare(
    'SELECT cal.id_calificacion, cal.puntuacion, cal.comentario, cal.fecha, n.nombre AS negocio_nombre, n.logo_url
     FROM calificacion cal
     JOIN negocios n ON n.id_negocio = cal.id_negocio
     WHERE cal.id_usuario = ?
     ORDER BY cal.fecha DESC'
);
$stmtReviews->execute([$userId]);
$reviews = $stmtReviews->fetchAll();
foreach ($reviews as &$r) $r['logo_url'] = resolverImagenUrl($r['logo_url'] ?? '');
unset($r);

$stmtBiz = $pdo->prepare('SELECT n.id_negocio, n.nombre, n.logo_url, COALESCE(AVG(c.puntuacion), 0) rating FROM favorito f JOIN negocios n ON n.id_negocio = f.id_negocio LEFT JOIN calificacion c ON c.id_negocio = n.id_negocio WHERE f.id_usuario = ? AND f.id_menu_item IS NULL GROUP BY n.id_negocio, n.nombre, n.logo_url ORDER BY n.nombre');
$stmtBiz->execute([$userId]);
$businesses = $stmtBiz->fetchAll();
foreach ($businesses as &$b) $b['logo_url'] = resolverImagenUrl($b['logo_url'] ?? '');
unset($b);

$stmtDishes = $pdo->prepare('SELECT m.id_menu_item, m.nombre AS plato_nombre, m.precio, m.imagen_url, m.id_categoria, n.id_negocio, n.nombre AS negocio_nombre FROM favorito f JOIN menu_items m ON m.id_menu_item = f.id_menu_item LEFT JOIN negocios n ON n.id_negocio = m.id_negocio WHERE f.id_usuario = ? AND f.id_menu_item IS NOT NULL ORDER BY m.nombre');
$stmtDishes->execute([$userId]);
$dishes = $stmtDishes->fetchAll();
foreach ($dishes as &$d) $d['imagen_url'] = resolverImagenUrl($d['imagen_url'] ?? '');
unset($d);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Mi perfil | Shizen</title>
    <base href="../">
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="css/styles.css" />
    <link rel="stylesheet" href="css/nav.css?v=20260930-ingreso-icon-1" />
    <link rel="stylesheet" href="css/pages.css" />
    <link rel="stylesheet" href="css/modals.css?v=20260930-cart-clear-1" />
    <link rel="stylesheet" href="css/perfil.css?v=logout-animation-1" />
</head>

<body>
    <header id="navigation">
        <?php include __DIR__ . '/navegacion.php'; ?>
    </header>

    <main id="app-content">
        <div class="view active" id="view-user-profile">
            <div class="profile-page">
                <div class="profile-banner profile-banner--user">
                    <?php $volverHref = 'index.php'; $volverClass = 'profile-back'; $volverLabel = 'Volver al inicio'; include __DIR__ . '/boton_volver.php'; ?>
                    <div class="profile-banner-overlay"></div>
                </div>
                <div class="profile-header-wrap">
                    <div class="profile-avatar-ring profile-avatar-ring--user">
                        <div class="profile-avatar" id="uprofileEmoji">
                            <?php if ($avatarPerfil !== ''): ?>
                            <img src="<?= htmlspecialchars($avatarPerfil, ENT_QUOTES, 'UTF-8') ?>"
                                alt="Avatar de perfil">
                            <?php else: ?>
                            <span aria-hidden="true">&#128100;</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="profile-meta">
                        <div class="profile-name-row">
                            <h1 class="profile-name" id="uprofileName">
                                <?= htmlspecialchars($nombrePerfil ?: 'Mi cuenta', ENT_QUOTES, 'UTF-8') ?></h1>
                            <span class="profile-badge">&#128100; Usuario</span>
                        </div>
                        <div class="profile-handle" id="uprofileEmail">
                            <?= htmlspecialchars($correoPerfil, ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="profile-handle" id="uprofileJoined" style="margin-top:2px">&#127807; Cliente Shizen
                        </div>
                        <p class="profile-bio" id="uprofileBio" style="display:none"></p>
                        <div class="profile-links" id="uprofileLinks"></div>
                        <div class="uprofile-quick-links">
                        </div>
                    </div>
                    <button class="btn-follow profile-edit-button" type="button"
                        onclick="openEditProfileModal()">&#9998; Editar perfil</button>
                </div>

                <div class="profile-stats-bar">
                    <div class="profile-stat">
                        <div class="profile-stat-num" id="ustatPurchases"><?= count($orders) ?></div>
                        <div class="profile-stat-label">Pedidos</div>
                    </div>
                    <div class="profile-stat">
                        <div class="profile-stat-num" id="ustatReviews"><?= count($reviews) ?></div>
                        <div class="profile-stat-label">Reseñas</div>
                    </div>
                    <div class="profile-stat">
                        <div class="profile-stat-num" id="ustatFollowing"><?= count($businesses) + count($dishes) ?>
                        </div>
                        <div class="profile-stat-label">Favoritos</div>
                    </div>
                </div>

                <div class="profile-tabs-wrap">
                    <div class="profile-tabs">
                        <button class="profile-tab active" data-section="purchases" type="button"
                            onclick="openUprofileSection('purchases', this)">🧾 Facturas</button>
                        <button class="profile-tab" data-section="reviews" type="button"
                            onclick="openUprofileSection('reviews', this)">&#11088; Reseñas</button>
                        <button class="profile-tab" data-section="following" type="button"
                            onclick="openUprofileSection('following', this)"><?php include __DIR__ . '/icono_corazon.php'; ?>
                            Favoritos</button>
                    </div>
                </div>

                <div class="profile-tab-content active" id="uprofile-purchases">
                    <div class="profile-section">
                        <?php if ($orders): ?>
                        <div class="orders-list" style="display:grid;gap:12px;">
                            <?php foreach ($orders as $order): ?>
                            <a class="order-card" href="php/factura.php?id=<?= (int)$order['id_pedido'] ?>"
                                style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:14px 18px;display:flex;justify-content:space-between;align-items:center;text-decoration:none;color:inherit;box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                                <div>
                                    <strong style="color:#1b3a1d;display:block;">Pedido #<?= (int)$order['id_pedido'] ?>
                                        · <?= htmlspecialchars($order['negocio_nombre'] ?? 'Shizen') ?></strong>
                                    <small
                                        style="color:#6b7280;"><?= htmlspecialchars($order['descripcion'] ?? '') ?></small>
                                </div>
                                <div style="text-align:right;">
                                    <span
                                        style="font-weight:700;color:#16a34a;display:block;"><?= htmlspecialchars($order['entrega_estado'] === 'en camino' ? 'Ver factura' : $order['estado']) ?></span>
                                    <small
                                        style="color:#9ca3af;"><?= htmlspecialchars($order['fecha_creacion'] ?? '') ?></small>
                                </div>
                            </a>
                            <?php endforeach; unset($order); ?>
                        </div>
                        <?php else: ?>
                        <div class="uprofile-empty" id="upurchasesEmpty">
                            <div class="uprofile-empty-icon">&#128722;</div>
                            <p>Aún no has realizado pedidos.</p>
                            <a class="btn-primary-full" href="index.php">Explorar platos y restaurantes</a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="profile-tab-content" id="uprofile-reviews">
                    <div class="profile-section">
                        <?php if ($reviews): ?>
                        <div class="reviews-list" style="display:grid;gap:12px;">
                            <?php foreach ($reviews as $rev): ?>
                            <div class="review-card"
                                style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:14px 18px;box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                                <div
                                    style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                                    <strong
                                        style="color:#1b3a1d;"><?= htmlspecialchars($rev['negocio_nombre']) ?></strong>
                                    <span style="color:#f59e0b;font-weight:700;">&#9733;
                                        <?= (int)$rev['puntuacion'] ?>/5</span>
                                </div>
                                <?php if (!empty($rev['comentario'])): ?>
                                <p style="color:#4b5563;font-size:0.95rem;margin:4px 0;">
                                    <?= htmlspecialchars($rev['comentario']) ?></p>
                                <?php endif; ?>
                                <small style="color:#9ca3af;"><?= htmlspecialchars($rev['fecha'] ?? '') ?></small>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <div class="uprofile-empty" id="ureviewsEmpty">
                            <div class="uprofile-empty-icon">&#11088;</div>
                            <p>Aún no tienes calificaciones.<br>Cuando califiques un negocio, aparecerán aquí.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="profile-tab-content" id="uprofile-following">
                    <div class="profile-section">
                        <?php if ($businesses || $dishes): ?>
                        <?php if ($businesses): ?>
                        <h3 style="font-size:1.05rem;font-weight:700;color:#1b3a1d;margin:8px 0 12px;">Restaurantes
                            Favoritos</h3>
                        <div
                            style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:12px;margin-bottom:20px;">
                            <?php foreach ($businesses as $b): ?>
                            <a href="php/negocio.php?id=<?= (int)$b['id_negocio'] ?>"
                                style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:12px;display:flex;align-items:center;gap:12px;text-decoration:none;color:inherit;box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                                <img src="<?= htmlspecialchars($b['logo_url'] ?: 'assets/logo.png') ?>" alt=""
                                    style="width:44px;height:44px;border-radius:8px;object-fit:cover;">
                                <div>
                                    <strong
                                        style="display:block;font-size:0.95rem;color:#1b3a1d;"><?= htmlspecialchars($b['nombre']) ?></strong>
                                    <small style="color:#f59e0b;">&#9733;
                                        <?= number_format((float)$b['rating'], 1) ?></small>
                                </div>
                            </a>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        <?php if ($dishes): ?>
                        <h3 style="font-size:1.05rem;font-weight:700;color:#1b3a1d;margin:8px 0 12px;">Platos Favoritos
                        </h3>
                        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:12px;">
                            <?php foreach ($dishes as $d): ?>
                            <a href="php/categorias.php?categoria=<?= (int)($d['id_categoria'] ?? 1) ?>"
                                style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:12px;display:flex;align-items:center;gap:12px;text-decoration:none;color:inherit;box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                                <img src="<?= htmlspecialchars($d['imagen_url'] ?: 'assets/logo.png') ?>" alt=""
                                    style="width:44px;height:44px;border-radius:8px;object-fit:cover;">
                                <div>
                                    <strong
                                        style="display:block;font-size:0.95rem;color:#1b3a1d;"><?= htmlspecialchars($d['plato_nombre']) ?></strong>
                                    <small
                                        style="color:#16a34a;font-weight:600;">$<?= number_format((float)$d['precio'], 0, ',', '.') ?></small>
                                </div>
                            </a>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        <?php else: ?>
                        <div class="uprofile-empty" id="ufollowingEmpty">
                            <div class="uprofile-empty-icon"><?php include __DIR__ . '/icono_corazon.php'; ?></div>
                            <p>Aún no tienes favoritos.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <div class="modal-overlay" id="editProfileModal" onclick="handleEditProfileOverlay(event)" aria-hidden="true">
        <div class="modal-card edit-profile-card" role="dialog" aria-modal="true" aria-labelledby="editProfileTitle">
            <button class="modal-close" type="button" onclick="closeEditProfileModal()"
                aria-label="Cerrar">&#10005;</button>
            <h2 class="edit-profile-title" id="editProfileTitle">Editar perfil</h2>
            <form method="post" action="php/perfil.php" id="editProfileForm">
                <input type="hidden" name="csrf_token"
                    value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="avatar" id="editProfileAvatar"
                    value="<?= htmlspecialchars($avatarPerfil, ENT_QUOTES, 'UTF-8') ?>">
                <div class="edit-profile-label">Avatar</div>
                <div class="edit-emoji-picker" id="editEmojiPicker">
                    <?php foreach ($avatarFiles as $avatarFile): ?>
                    <?php $avatarPath = 'assets/Perfil/' . basename($avatarFile); ?>
                    <button class="edit-emoji-opt" type="button"
                        data-avatar="<?= htmlspecialchars($avatarPath, ENT_QUOTES, 'UTF-8') ?>"
                        aria-label="Elegir avatar <?= htmlspecialchars(pathinfo($avatarFile, PATHINFO_FILENAME), ENT_QUOTES, 'UTF-8') ?>">
                        <img src="<?= htmlspecialchars($avatarPath, ENT_QUOTES, 'UTF-8') ?>" alt="">
                    </button>
                    <?php endforeach; ?>
                </div>

                <label class="edit-profile-label" for="editProfileName">Nombre</label>
                <input class="form-input" id="editProfileName" name="nombre" type="text"
                    value="<?= htmlspecialchars($_SESSION['usuario_nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    placeholder="Tu nombre" maxlength="100" required>

                <label class="edit-profile-label" for="editProfileLastName">Apellido</label>
                <input class="form-input" id="editProfileLastName" name="apellido" type="text"
                    value="<?= htmlspecialchars($_SESSION['usuario_apellido'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    placeholder="Tu apellido" maxlength="100" required>

                <label class="edit-profile-label" for="editProfileAddress">Dirección</label>
                <input class="form-input" id="editProfileAddress" name="direccion" type="text"
                    value="<?= htmlspecialchars($_SESSION['usuario_direccion'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    placeholder="Dirección de entrega" maxlength="255">

                <div class="edit-profile-readonly">
                    <span>&#128274; Cuenta:
                        <strong><?= htmlspecialchars($correoPerfil, ENT_QUOTES, 'UTF-8') ?></strong></span>
                </div>

                <button class="btn-dish-pedir edit-profile-save" type="submit">Guardar cambios</button>
            </form>
        </div>
    </div>

    <div id="overlays"><?php include __DIR__ . '/modales.php'; ?></div>
    <script>
    (function() {
        var selectedAvatar = <?= json_encode($avatarPerfil) ?>;
        var modal = document.getElementById('editProfileModal');
        var picker = document.getElementById('editEmojiPicker');

        window.openUprofileSection = function(section, button) {
            document.querySelectorAll('#view-user-profile .profile-tab-content').forEach(function(panel) {
                panel.classList.remove('active');
            });
            document.querySelectorAll('#view-user-profile .profile-tab').forEach(function(tab) {
                tab.classList.remove('active');
            });
            var panel = document.getElementById('uprofile-' + section);
            if (panel) panel.classList.add('active');
            var activeTab = button || document.querySelector('#view-user-profile .profile-tab[data-section="' +
                section + '"]');
            if (activeTab) activeTab.classList.add('active');
        };

        window.openEditProfileModal = function() {
            modal.classList.add('open');
            modal.setAttribute('aria-hidden', 'false');
        };

        window.closeEditProfileModal = function() {
            modal.classList.remove('open');
            modal.setAttribute('aria-hidden', 'true');
        };

        window.handleEditProfileOverlay = function(event) {
            if (event.target === modal) window.closeEditProfileModal();
        };

        var avatarInput = document.getElementById('editProfileAvatar');
        picker.addEventListener('click', function(event) {
            var option = event.target.closest('[data-avatar]');
            if (!option) return;
            selectedAvatar = option.getAttribute('data-avatar');
            avatarInput.value = selectedAvatar;
            picker.querySelectorAll('.edit-emoji-opt').forEach(function(item) {
                item.classList.remove('selected');
            });
            option.classList.add('selected');
        });

        picker.querySelectorAll('[data-avatar]').forEach(function(option) {
            if (option.getAttribute('data-avatar') === selectedAvatar) option.classList.add('selected');
        });

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && modal.classList.contains('open')) window.closeEditProfileModal();
        });
    })();
    </script>
    <script src="js/app.js?v=20260930-cart-clear-1"></script>
</body>

</html>