<div class="view active" id="view-home">
  <section class="hero-section">
    <h1>
      Si tienes
      <span> Shizen </span>
      , tienes Todo.
    </h1>
    <p>
      La plataforma de comida vegana que aspira a ser la más
      grande de Colombia. Descubre restaurantes, rastreo en
      tiempo real y envío gratis durante la beta.
    </p>
    <div class="hero-pills">
      <a class="promo-btn" href="php/promociones.php">
        <span class="promo-btn-label">🎉 Ver promociones</span>
        <span class="promo-confetti" aria-hidden="true">
          <i style="--x:6%;--d:0s;--c:#fff"></i>
          <i style="--x:14%;--d:.3s;--c:#ffb400"></i>
          <i style="--x:22%;--d:.1s;--c:#2ec4b6"></i>
          <i style="--x:30%;--d:.5s;--c:#7b5cff"></i>
          <i style="--x:38%;--d:.2s;--c:#4cc9f0"></i>
          <i style="--x:46%;--d:.6s;--c:#fff"></i>
          <i style="--x:54%;--d:.05s;--c:#ffb400"></i>
          <i style="--x:62%;--d:.4s;--c:#2ec4b6"></i>
          <i style="--x:70%;--d:.15s;--c:#7b5cff"></i>
          <i style="--x:78%;--d:.55s;--c:#4cc9f0"></i>
          <i style="--x:86%;--d:.25s;--c:#fff"></i>
          <i style="--x:94%;--d:.45s;--c:#ffb400"></i>
        </span>
      </a>
    </div>
  </section>
  <section class="info-section textured">
    <div class="info-bg"></div>
    <div class="info-overlay"></div>
    <div class="info-content">
      <h2>
        Nuestra misión: alimentar Colombia con conciencia
      </h2>
      <p>
        Conectamos a restaurantes veganos, comedores
        conscientes y repartidores éticos para llevar comida
        plant-based a cada rincón del país, con
        transparencia y compromiso ambiental.
      </p>
      <div class="glass-cards">
        <div class="glass-card">
          <div class="card-icon">🗺</div>
          <h3>Mapa en tiempo real</h3>
          <p>
            Sigue tu pedido en el mapa y sabe exactamente
            cuándo llegará tu comida.
          </p>
        </div>
        <div class="glass-card">
          <div class="card-icon">📱</div>
          <h3>Seguimiento inteligente</h3>
          <p>
            Notificaciones al instante desde que el chef
            empieza hasta que tocan tu puerta.
          </p>
        </div>
        <div class="glass-card">
          <div class="card-icon">♻</div>
          <h3>Compromiso sostenible</h3>
          <p>
            Empaques biodegradables, rutas eficientes y cero
            plástico de un solo uso.
          </p>
        </div>
      </div>
      <div class="stat-cards">
        <div class="stat-card">
          <div class="stat-icon">📍</div>
          <div class="stat-text">
            Bogotá &amp; ciudades principales
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">🚵</div>
          <div class="stat-text">Envío gratis en beta</div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">🌱</div>
          <div class="stat-text">100% Plant-based</div>
        </div>
      </div>
    </div>
  </section>
  <section
    class="category-band"
    aria-labelledby="category-band-title"
  >
    <div class="category-band-inner">
      <h2 id="category-band-title">¿Qué quieres comer hoy?</h2>
      <p class="category-band-sub">
        Explora negocios y categorías para encontrar tu próxima comida vegana favorita.
      </p>
      <h3 class="category-subtitle">Categorías</h3>
      <p class="category-section-text">Elige una categoría y descubre lo mejor de la cocina vegana colombiana.</p>
      <div class="filter-section">
        <div class="filter-track-wrapper">
          <div class="filter-track" id="filterTrack" role="region" aria-label="Categorías de platos">
        <?php
        require_once __DIR__ . "/../clases/Categoria.php";
        $allCategories = [];
        $categoryCounts = [];
        try {
            $allCategories = Categoria::obtenerTodas();
        } catch (Throwable $e) {
            $allCategories = [];
        }
        try {
            $countRows = Database::getConnection()->query(
                "SELECT id_categoria, COUNT(*) AS total FROM menu_items WHERE id_categoria IS NOT NULL GROUP BY id_categoria"
            )->fetchAll();
            foreach ($countRows as $row) {
                $categoryCounts[(int)$row["id_categoria"]] = (int)$row["total"];
            }
        } catch (Throwable $e) {
            $categoryCounts = [];
        }
        $cardThemes = ["card-comidas", "card-cenas", "card-bowls", "card-postres", "card-bebidas", "card-desayunos", "card-todos"];
        $spotlightColors = ["rgba(255,100,0,.40)", "rgba(168,0,255,.38)", "rgba(0,180,216,.38)", "rgba(247,37,133,.40)", "rgba(0,100,255,.38)", "rgba(255,160,0,.40)", "rgba(99,102,241,.35)"];
        foreach ($allCategories as $idx => $cat):
          $catId = (int)($cat["id_categoria"] ?? $cat["id"] ?? 1);
          $catName = htmlspecialchars($cat["nombre"] ?? "Categoría", ENT_QUOTES, "UTF-8");
          $catIcon = htmlspecialchars((string)($cat["icon"] ?? ""), ENT_QUOTES, "UTF-8");
          if ($catIcon === "") $catIcon = "&#129368;";
          $theme = $cardThemes[$idx % count($cardThemes)];
          $spotlight = $spotlightColors[$idx % count($spotlightColors)];
          $dishCount = $categoryCounts[$catId] ?? 0;
        ?>
          <a class="filter-card <?= $theme ?>" href="php/categorias.php?categoria=<?= $catId ?>" aria-label="Ver <?= $dishCount ?> platos de <?= $catName ?>">
            <span class="card-ring" aria-hidden="true"></span>
            <span class="card-count"><span><?= $dishCount ?></span></span>
            <span class="card-emoji-area">
              <span class="card-spotlight" style="background:<?= $spotlight ?>" aria-hidden="true"></span>
              <span class="card-emoji"><?= $catIcon ?></span>
            </span>
            <span class="card-bottom">
              <span class="card-label"><?= $catName ?></span>
              <span class="card-pill">Ver platos</span>
            </span>
          </a>
        <?php endforeach; ?>
          </div>
        </div>
      </div>
      <div class="business-section-divider" aria-hidden="true"></div>
      <div class="business-inline" aria-labelledby="business-band-title">
        <h3 id="business-band-title">Negocios mejor calificados</h3>
        <p class="business-band-sub">Descubre los favoritos de nuestra comunidad.</p>
        <div class="business-scroll">
          <?php
          require_once __DIR__ . '/../BD/conexion.php';
          require_once __DIR__ . '/../funciones/funciones.php';
          $businesses = obtenerConexion()->query(
              "SELECT n.id_negocio, n.nombre, n.logo_url, n.direccion,
                      COALESCE(r.rating, 0) AS rating, COALESCE(o.order_count, 0) AS order_count
               FROM negocios n
               JOIN menu_items m ON m.id_negocio = n.id_negocio
               LEFT JOIN (
                   SELECT id_negocio, AVG(puntuacion) AS rating
                   FROM calificacion GROUP BY id_negocio
               ) r ON r.id_negocio = n.id_negocio
               LEFT JOIN (
                   SELECT id_negocio, COUNT(*) AS order_count
                   FROM pedido GROUP BY id_negocio
               ) o ON o.id_negocio = n.id_negocio
               GROUP BY n.id_negocio, n.nombre, n.logo_url, n.direccion, r.rating, o.order_count
               ORDER BY rating DESC, n.nombre"
          )->fetchAll();
          foreach ($businesses as $business):
            $logo = resolverImagenUrl($business['logo_url'] ?? '');
          ?>
            <a class="business-card client-card" href="php/negocio.php?id=<?= (int)$business['id_negocio'] ?>">
              <div class="card-texture" aria-hidden="true"></div>
              <div class="card-shine" aria-hidden="true"></div>
              <div class="card-content">
                <div class="card-avatar"<?= !empty($business['logo_url']) ? '' : ' style="background:linear-gradient(145deg,#ff4500,#9b1c00);box-shadow:0 6px 18px #ff450066"' ?>>
                  <?php if (!empty($business['logo_url'])): ?>
                    <img src="<?= htmlspecialchars($logo, ENT_QUOTES, 'UTF-8') ?>" alt="">
                  <?php else: ?>
                    <?= htmlspecialchars(strtoupper(substr((string)$business['nombre'], 0, 1)), ENT_QUOTES, 'UTF-8') ?>
                  <?php endif; ?>
                </div>
                <div class="card-info-row">
                  <div class="card-info">
                    <h3 class="card-name"><?= htmlspecialchars($business['nombre'], ENT_QUOTES, 'UTF-8') ?></h3>
                    <div class="card-stat">
                      <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 01-8 0"></path></svg>
                      <span><?= number_format((int)$business['order_count']) ?> pedidos</span>
                    </div>
                    <div class="card-stat">
                      <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                      <span><?= htmlspecialchars($business['direccion'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                  </div>
                  <div class="card-star" aria-label="Calificación <?= number_format((float)$business['rating'], 1) ?> de 5">
                    <svg viewBox="0 0 16 16" fill="#facc15" aria-hidden="true"><path d="M8 1l1.8 3.6 4 .6-2.9 2.8.7 4L8 10l-3.6 2 .7-4L2.2 5.2l4-.6z"></path></svg>
                    <span><?= $business['rating'] > 0 ? number_format((float)$business['rating'], 1) : 'Nuevo' ?></span>
                  </div>
                </div>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
        <script>
          (function () {
            var track = document.getElementById('filterTrack');
            if (!track) return;
            var dragging = false;
            var moved = false;
            var startX = 0;
            var startScroll = 0;
            track.addEventListener('mousedown', function (event) {
              if (event.button !== 0) return;
              dragging = true;
              moved = false;
              startX = event.pageX;
              startScroll = track.scrollLeft;
            });
            track.addEventListener('mousemove', function (event) {
              if (!dragging) return;
              var distance = event.pageX - startX;
              if (Math.abs(distance) > 5) moved = true;
              if (moved) {
                event.preventDefault();
                track.scrollLeft = startScroll - distance;
              }
            });
            ['mouseup', 'mouseleave'].forEach(function (name) {
              track.addEventListener(name, function () { dragging = false; });
            });
            track.addEventListener('click', function (event) {
              if (!moved) return;
              event.preventDefault();
              event.stopPropagation();
              moved = false;
            }, true);
          })();
        </script>
    </div>
  </section>
  <section class="join-section" id="registro" tabindex="-1">
    <div class="section-header">
      <h2>Únete a Shizen</h2>
      <p>
        Haz parte del movimiento de comida consciente que
        aspira a ser el más grande de Colombia.
      </p>
    </div>
    <div class="join-grid">
      <div class="join-card">
        <div
          class="join-card-img"
          style="
            background-image: url(&quot;https://images.unsplash.com/photo-1572715376701-98568319fd0b?w=800&h=500&fit=crop&auto=format&quot;);
          "
        >
          <div class="join-card-img-overlay"></div>
          <span
            class="join-card-tag"
            style="background: #388e3c"
          >
            Para usuarios
          </span>
        </div>
        <div class="join-card-body">
          <h3>Crea tu cuenta</h3>
          <p>
            Descubre negocios veganos, promociones y
            domicilios en toda Colombia.
          </p>
          <a
            class="btn-join"
            href="php/registro_usuario.php"
          >
            Crear cuenta
          </a>
        </div>
      </div>
      <div class="join-card">
        <div
          class="join-card-img"
          style="
            background-image: url(&quot;https://images.unsplash.com/photo-1542838132-92c53300491e?w=800&h=500&fit=crop&auto=format&quot;);
          "
        >
          <div class="join-card-img-overlay"></div>
          <span
            class="join-card-tag"
            style="background: #de231f"
          >
            Para negocios
          </span>
        </div>
        <div class="join-card-body">
          <h3>Registra tu negocio</h3>
          <p>
            Accede a millones de usuarios de Shizen y
            disfruta de una logística inmediata sin salir de
            tu tienda.
          </p>
          <a
            class="btn-join"
            href="php/registro_negocio.php"
          >
            Empezar registro
          </a>
        </div>
      </div>
      <div class="join-card">
        <div
          class="join-card-img"
          style="
            background-image: url(&quot;https://images.unsplash.com/photo-1611068562065-994ba66501ba?w=800&h=500&fit=crop&auto=format&quot;);
          "
        >
          <div class="join-card-img-overlay"></div>
          <span
            class="join-card-tag"
            style="background: #f57c00"
          >
            Para repartidores
          </span>
        </div>
        <div class="join-card-body">
          <h3>¡Únete como repartidor!</h3>
          <p>
            Gana dinero extra entregando domicilios en
            Colombia. Las mejores tarifas y beneficios.
          </p>
          <a
            class="btn-join"
            href="php/registro_repartidor.php"
          >
            ¡Regístrate ahora!
          </a>
        </div>
      </div>
    </div>
  </section>
</div>
