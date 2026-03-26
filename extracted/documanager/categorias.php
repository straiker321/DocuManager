<?php
require_once 'includes/config.php';
requireLogin();
if(!isEditor()) { header('Location: /documanager/dashboard.php'); exit; }

$msg = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_action'])) {
    $id = (int)($_POST['id'] ?? 0);
    if ($_POST['_action'] === 'crear') {
        $res = api('POST', '/categorias', ['nombre'=>$_POST['nombre'],'color'=>$_POST['color']]);
        $msg = $res['code']===201 ? 'Categoría creada.' : ($res['data']['message'] ?? 'Error al crear.');
    }
    if ($_POST['_action'] === 'editar') {
        $res = api('PUT', "/categorias/$id", ['nombre'=>$_POST['nombre'],'color'=>$_POST['color']]);
        $msg = $res['code']===200 ? 'Categoría actualizada.' : 'Error al actualizar.';
    }
    if ($_POST['_action'] === 'eliminar') {
        $res = api('DELETE', "/categorias/$id");
        $msg = $res['code']===204 ? 'Categoría eliminada.' : 'Error al eliminar.';
    }
}

$cats = api('GET', '/categorias');
$categorias = $cats['data'] ?? [];

$editCat = null;
if (isset($_GET['edit'])) {
    $r = api('GET', '/categorias/'.(int)$_GET['edit']);
    $editCat = $r['data'] ?? null;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categorías — DocuManager</title>
    <link rel="stylesheet" href="/documanager/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<div class="page">
<div class="container">

    <div class="page-header">
        <div>
            <h1>Categorías</h1>
            <p>Organiza los documentos por categoría</p>
        </div>
        <button class="btn btn-primary" onclick="openModal('modalCat')">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Nueva categoría
        </button>
    </div>

    <?php if($msg): ?><div class="alert <?= strpos($msg,'rror')===false?'alert-success':'alert-error' ?>"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

    <?php if(empty($categorias)): ?>
    <div class="card"><div class="empty">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
        <p>No hay categorías creadas aún</p>
    </div></div>
    <?php else: ?>
    <div class="cat-grid">
        <?php foreach($categorias as $c): ?>
        <div class="cat-card">
            <div class="cat-dot" style="background:<?= htmlspecialchars($c['color']) ?>20;color:<?= htmlspecialchars($c['color']) ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
            </div>
            <div class="cat-info">
                <strong><?= htmlspecialchars($c['nombre']) ?></strong>
                <span><?= htmlspecialchars($c['color']) ?></span>
            </div>
            <div class="cat-actions">
                <a href="?edit=<?= $c['id'] ?>" class="btn btn-secondary btn-sm">✏️</a>
                <form method="POST" onsubmit="return confirm('¿Eliminar esta categoría?')">
                    <input type="hidden" name="_action" value="eliminar">
                    <input type="hidden" name="id" value="<?= $c['id'] ?>">
                    <button type="submit" class="btn btn-danger btn-sm">🗑️</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div>
</div>

<div class="modal-overlay <?= $editCat?'open':'' ?>" id="modalCat">
    <div class="modal" style="max-width:420px">
        <div class="modal-header">
            <h2><?= $editCat?'Editar categoría':'Nueva categoría' ?></h2>
            <button class="modal-close" onclick="closeModal('modalCat')">×</button>
        </div>
        <form method="POST">
            <input type="hidden" name="_action" value="<?= $editCat?'editar':'crear' ?>">
            <input type="hidden" name="id" value="<?= $editCat['id']??'' ?>">
            <div class="form-group">
                <label>Nombre *</label>
                <input type="text" name="nombre" class="form-control" required
                       value="<?= htmlspecialchars($editCat['nombre']??'') ?>">
            </div>
            <div class="form-group">
                <label>Color identificador</label>
                <div style="display:flex;gap:10px;align-items:center">
                    <input type="color" name="color"
                           value="<?= htmlspecialchars($editCat['color']??'#6366f1') ?>"
                           style="width:46px;height:38px;border:1px solid var(--border);border-radius:var(--radius-sm);background:none;cursor:pointer;padding:2px">
                    <span style="font-size:0.8rem;color:var(--text-3)">Color para identificar la categoría en el sistema</span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalCat')">Cancelar</button>
                <button type="submit" class="btn btn-primary"><?= $editCat?'Guardar cambios':'Crear categoría' ?></button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) {
    document.getElementById(id).classList.remove('open');
    if(window.location.search.includes('edit=')) window.location='/documanager/categorias.php';
}
document.querySelectorAll('.modal-overlay').forEach(o =>
    o.addEventListener('click', e => { if(e.target===o) closeModal(o.id); })
);
</script>
</body>
</html>
