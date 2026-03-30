<?php
require_once 'includes/config.php';
requireAdmin();

$msg = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_action'])) {
    if ($_POST['_action'] === 'crear') {
        $pass = trim($_POST['password'] ?? '');
        if (strlen($pass) < 6) {
            $error = 'La contraseña debe tener mínimo 6 caracteres.';
        } else {
            $res = api('POST', '/auth/register', [
                'nombre'   => trim($_POST['nombre']),
                'email'    => trim($_POST['email']),
                'password' => $pass,
                'rol'      => $_POST['rol'] ?? 'VIEWER'
            ]);
            if ($res['code'] === 200 || $res['code'] === 201) {
                $msg = 'Usuario creado correctamente.';
            } else {
                $error = $res['data']['message'] ?? 'Error al crear el usuario. El email puede estar en uso.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios — DocuManager</title>
    <link rel="stylesheet" href="<?= assetUrl('/documanager/css/style.css') ?>">
    <style>
        .roles-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem; margin-top: 1.5rem;
        }
        .role-card {
            background: var(--bg-card); border: 1px solid var(--border);
            border-radius: var(--radius); padding: 1.25rem;
        }
        .role-card h4 { margin-bottom: 8px; font-size: 0.9rem; }
        .role-card ul { padding-left: 1.1rem; color: var(--text-2); font-size: 0.82rem; }
        .role-card ul li { margin-bottom: 4px; }
        .current-user-card {
            display: flex; align-items: center; gap: 16px;
            padding: 1.25rem 1.5rem;
            background: var(--bg-card); border: 1px solid var(--border);
            border-radius: var(--radius); margin-bottom: 1.5rem;
        }
        .big-avatar {
            width: 52px; height: 52px; border-radius: 50%;
            background: var(--accent); color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-family: 'Syne', sans-serif; font-size: 1.3rem; font-weight: 800;
            flex-shrink: 0;
        }
    </style>
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<div class="page">
<div class="container">

    <div class="page-header">
        <div>
            <h1>Gestión de usuarios</h1>
            <p>Solo los administradores pueden crear y gestionar usuarios</p>
        </div>
        <button class="btn btn-primary" onclick="openModal('modalUser')">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Nuevo usuario
        </button>
    </div>

    <?php if($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <?php if($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <!-- Tu cuenta -->
    <div class="current-user-card">
        <div class="big-avatar"><?= strtoupper(substr($_SESSION['nombre']??'A',0,1)) ?></div>
        <div>
            <strong style="font-size:1rem"><?= htmlspecialchars($_SESSION['nombre']) ?></strong>
            <p style="color:var(--text-2);font-size:0.85rem;margin-top:2px"><?= htmlspecialchars($_SESSION['email']) ?></p>
            <span class="badge badge-admin" style="margin-top:6px">ADMIN</span>
        </div>
    </div>

    <!-- Roles del sistema -->
    <div class="card">
        <h3 style="font-size:1rem;margin-bottom:4px">Roles del sistema</h3>
        <p style="color:var(--text-2);font-size:0.85rem;margin-bottom:1rem">Cada usuario tiene un rol que define qué puede hacer en el sistema.</p>

        <div class="roles-info">
            <div class="role-card">
                <h4><span class="badge badge-admin">ADMIN</span></h4>
                <ul>
                    <li>Ver todos los documentos</li>
                    <li>Crear y editar documentos</li>
                    <li>Gestionar categorías</li>
                    <li>Crear y gestionar usuarios</li>
                    <li>Acceso completo al sistema</li>
                </ul>
            </div>
            <div class="role-card">
                <h4><span class="badge badge-editor">EDITOR</span></h4>
                <ul>
                    <li>Ver todos los documentos</li>
                    <li>Crear y editar documentos</li>
                    <li>Gestionar categorías</li>
                    <li>No puede gestionar usuarios</li>
                </ul>
            </div>
            <div class="role-card">
                <h4><span class="badge badge-viewer">VIEWER</span></h4>
                <ul>
                    <li>Solo puede ver documentos</li>
                    <li>No puede crear ni editar</li>
                    <li>No ve la sección de categorías</li>
                    <li>No ve la sección de usuarios</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="alert alert-info" style="margin-top:1.25rem">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        Para listar todos los usuarios del sistema necesitas agregar <code style="background:rgba(0,0,0,0.3);padding:1px 6px;border-radius:4px">GET /usuarios</code> en el backend Java. Puedes crear nuevos usuarios con el botón de arriba.
    </div>

</div>
</div>

<!-- Modal nuevo usuario -->
<div class="modal-overlay" id="modalUser">
    <div class="modal" style="max-width:460px">
        <div class="modal-header">
            <h2>Crear nuevo usuario</h2>
            <button class="modal-close" onclick="closeModal('modalUser')">×</button>
        </div>
        <form method="POST">
            <input type="hidden" name="_action" value="crear">
            <div class="form-group">
                <label>Nombre completo *</label>
                <input type="text" name="nombre" class="form-control" required placeholder="Juan Pérez">
            </div>
            <div class="form-group">
                <label>Correo electrónico *</label>
                <input type="email" name="email" class="form-control" required placeholder="juan@empresa.com">
            </div>
            <div class="form-group">
                <label>Contraseña * (mínimo 6 caracteres)</label>
                <input type="password" name="password" class="form-control" required minlength="6" placeholder="••••••••">
            </div>
            <div class="form-group">
                <label>Rol del usuario</label>
                <select name="rol" class="form-control">
                    <option value="VIEWER">VIEWER — Solo puede ver documentos</option>
                    <option value="EDITOR">EDITOR — Puede crear y editar documentos</option>
                    <option value="ADMIN">ADMIN — Acceso completo al sistema</option>
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalUser')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Crear usuario</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.modal-overlay').forEach(o =>
    o.addEventListener('click', e => { if(e.target===o) closeModal(o.id); })
);
</script>
</body>
</html>
