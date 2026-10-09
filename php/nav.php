<?php
/**
 * nav.php — Componente HTML reutilizable de la barra de navegación inferior (shizen-app)
 * Renderiza el markup HTML con clases semánticas (.nav-barra, .nav-btn, etc.)
 *
 * @param string $active 'inicio' | 'activos' | 'avisos' | 'perfil'
 */
function renderNav(string $active = 'inicio'): void {
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    $inPages = (strpos($uri, '/pages/') !== false);
    $pagesBase = $inPages ? '' : '../pages/';
    $phpBase   = $inPages ? '../php/' : '';

    $items = [
        'inicio' => [
            'label' => 'Inicio',
            'href'  => $pagesBase . 'inicio.html',
            'badge' => null,
            'svg'   => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 11l9-8 9 8"></path><path d="M5 10v10h14V10"></path><path d="M10 20v-6h4v6"></path></svg>'
        ],
        'activos' => [
            'label' => 'Activos',
            'href'  => $pagesBase . 'activos.html',
            'badge' => 'nav-badge-activos',
            'svg'   => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 8l-9-5-9 5 9 5 9-5z"></path><path d="M3 8v8l9 5 9-5V8"></path><path d="M12 13v8"></path></svg>'
        ],
        'avisos' => [
            'label' => 'Avisos',
            'href'  => $pagesBase . 'notificaciones.php',
            'badge' => 'nav-badge-avisos',
            'svg'   => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 17V11a6 6 0 0 1 12 0v6l1.5 2h-15L6 17z"></path><path d="M10 21a2 2 0 0 0 4 0"></path></svg>'
        ],
        'perfil' => [
            'label' => 'Perfil',
            'href'  => $phpBase . 'perfil.php',
            'badge' => null,
            'svg'   => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="4"></circle><path d="M4 20c0-3.9 3.6-6 8-6s8 2.1 8 6"></path></svg>'
        ]
    ];
?>
<nav aria-label="Navegación principal" class="nav-barra">
<?php foreach ($items as $key => $item):
    $isActive = ($key === $active);
    $btnClass = $isActive ? 'nav-btn-activo' : 'nav-btn';
    $labelClass = $isActive ? 'nav-label-activo' : 'nav-label';
    $badgeClass = $isActive ? 'nav-badge-activo' : 'nav-badge';
?>
  <button type="button" aria-label="<?= $item['label'] ?>" class="<?= $btnClass ?>" onclick="window.location.href='<?= $item['href'] ?>'">
    <?php if ($item['badge']): ?>
      <span class="nav-icono-wrap">
        <?= $item['svg'] ?>
        <span class="<?= $badgeClass ?>" id="<?= $item['badge'] ?>" style="display:none">0</span>
      </span>
    <?php else: ?>
      <?= $item['svg'] ?>
    <?php endif; ?>
    <span class="<?= $labelClass ?>"><?= $item['label'] ?></span>
  </button>
<?php endforeach; ?>
</nav>
<?php
}

function getNavHtml(string $active = 'inicio'): string {
    ob_start();
    renderNav($active);
    return ob_get_clean();
}