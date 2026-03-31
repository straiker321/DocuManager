<?php requireLogin(false); ?>
<nav class="navbar">
    <div class="nav-brand">
        <svg width="26" height="26" viewBox="0 0 26 26" fill="none">
            <rect x="3" y="1" width="13" height="17" rx="2" fill="#6366f1"/>
            <rect x="10" y="7" width="13" height="17" rx="2" fill="#818cf8" opacity="0.65"/>
            <path d="M6 7h7M6 11h5M6 15h3" stroke="white" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
        <span>Docu<strong>Manager</strong></span>
    </div>

    <ul class="nav-links">
        <?php if(isAdmin()): ?>
        <li>
            <a href="/documanager/dashboard.php" <?= basename($_SERVER['PHP_SELF'])=='dashboard.php'?'class="active"':'' ?>>
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
                Dashboard
            </a>
        </li>
        <?php endif; ?>
        <li>
            <a href="/documanager/documentos.php" <?= basename($_SERVER['PHP_SELF'])=='documentos.php'?'class="active"':'' ?>>
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                Documentos
            </a>
        </li>
        <li>
            <a href="/documanager/historial.php" <?= basename($_SERVER['PHP_SELF'])=='historial.php'?'class="active"':'' ?>>
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                Historial
            </a>
        </li>
        <?php if(isEditor()): ?>
        <li>
            <a href="/documanager/categorias.php" <?= basename($_SERVER['PHP_SELF'])=='categorias.php'?'class="active"':'' ?>>
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                Categorías
            </a>
        </li>
        <?php endif; ?>
        <?php if(isAdmin()): ?>
        <li>
            <a href="/documanager/usuarios.php" <?= basename($_SERVER['PHP_SELF'])=='usuarios.php'?'class="active"':'' ?>>
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                Usuarios
            </a>
        </li>
        <?php endif; ?>
    </ul>

    <div class="nav-user">
        <div class="user-avatar"><?= strtoupper(substr($_SESSION['nombre'] ?? 'U', 0, 1)) ?></div>
        <div class="user-info">
            <span class="user-name"><?= htmlspecialchars($_SESSION['nombre'] ?? '') ?></span>
            <span class="user-rol badge-rol-<?= strtolower($_SESSION['rol'] ?? '') ?>"><?= $_SESSION['rol'] ?? '' ?></span>
        </div>
        <a href="/documanager/logout.php" class="btn-logout" title="Cerrar sesión">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        </a>
    </div>
</nav>
<script>
(() => {
    const hasExplicitModalState = () => /(?:^|[?&])edit=/.test(window.location.search);
    const normalizeOverlays = () => {
        document.querySelectorAll('.modal-overlay').forEach((overlay) => {
            const isOpen = overlay.classList.contains('open');
            overlay.style.display = isOpen ? 'flex' : 'none';
            overlay.style.pointerEvents = 'none';
            overlay.style.opacity = '1';
            overlay.style.visibility = 'visible';
        });
    };
    const closeStaleOverlays = () => {
        if (hasExplicitModalState()) {
            normalizeOverlays();
            return;
        }
        document.querySelectorAll('.modal-overlay.open').forEach((overlay) => {
            overlay.classList.remove('open');
        });
        normalizeOverlays();
    };
    document.addEventListener('DOMContentLoaded', closeStaleOverlays);
    window.addEventListener('pageshow', closeStaleOverlays);
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeStaleOverlays();
    });
    const observer = new MutationObserver(normalizeOverlays);
    observer.observe(document.documentElement, { childList: true, subtree: true, attributes: true, attributeFilter: ['class'] });
})();
</script>
