<?php
require_once 'includes/config.php';
requireLogin();

$docId = (int)($_GET['doc'] ?? 0);

if ($docId) {
    $res        = api('GET', "/actividad/documento/$docId");
    $actividades = $res['data'] ?? [];
    $docRes     = api('GET', "/documentos/$docId");
    $documento  = $docRes['data'] ?? null;
} else {
    $res        = api('GET', '/actividad/recientes');
    $actividades = $res['data'] ?? [];
    $documento  = null;
}

function iconAccion($accion) {
    return match($accion) {
        'CREAR'    => '✅',
        'EDITAR'   => '✏️',
        'ARCHIVAR' => '📦',
        'DESCARGAR'=> '⬇️',
        'VER'      => '👁️',
        default    => '📋'
    };
}

function colorAccion($accion) {
    return match($accion) {
        'CREAR'    => 'badge-publicado',
        'EDITAR'   => 'badge-revision',
        'ARCHIVAR' => 'badge-archivado',
        'DESCARGAR'=> 'badge-editor',
        default    => 'badge-viewer'
    };
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial — DocuManager</title>
    <link rel="stylesheet" href="/documanager/css/style.css">
    <style>
        .timeline { position: relative; padding-left: 2rem; }
        .timeline::before {
            content: '';
            position: absolute; left: 9px; top: 0; bottom: 0;
            width: 2px; background: var(--border);
        }
        .timeline-item {
            position: relative; margin-bottom: 1.25rem;
        }
        .timeline-dot {
            position: absolute; left: -2rem;
            width: 20px; height: 20px; border-radius: 50%;
            background: var(--bg-card); border: 2px solid var(--border);
            display: flex; align-items: center; justify-content: center;
            font-size: 10px; top: 2px;
        }
        .timeline-dot.crear    { border-color: var(--success); }
        .timeline-dot.editar   { border-color: var(--warning); }
        .timeline-dot.archivar { border-color: var(--danger); }
        .timeline-dot.descargar{ border-color: var(--info); }

        .timeline-card {
            background: var(--bg-card); border: 1px solid var(--border);
            border-radius: var(--radius-sm); padding: 12px 14px;
        }
        .timeline-card:hover { border-color: var(--border-light); }
        .timeline-header {
            display: flex; align-items: center;
            justify-content: space-between; margin-bottom: 4px;
        }
        .timeline-accion { font-size: 0.78rem; font-weight: 600; }
        .timeline-fecha  { font-size: 0.75rem; color: var(--text-3); }
        .timeline-desc   { font-size: 0.85rem; color: var(--text-2); }
        .timeline-meta   { font-size: 0.75rem; color: var(--text-3); margin-top: 4px; }
    </style>
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<div class="page">
<div class="container">

    <div class="page-header">
        <div>
            <h1>Historial de actividad</h1>
            <p>
                <?php if($documento): ?>
                    Cambios del documento: <strong><?= htmlspecialchars(docValue($documento, 'titulo') ?? '') ?></strong>
                <?php else: ?>
                    Últimas 20 actividades del sistema
                <?php endif; ?>
            </p>
        </div>
        <div style="display:flex;gap:8px">
            <?php if($docId): ?>
            <a href="/documanager/historial.php" class="btn btn-secondary">Ver todo el historial</a>
            <a href="/documanager/documentos.php" class="btn btn-secondary">← Documentos</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if(empty($actividades)): ?>
    <div class="card">
        <div class="empty">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/></svg>
            <p>No hay actividad registrada aún</p>
        </div>
    </div>
    <?php else: ?>
    <div class="card">
        <div class="timeline">
            <?php foreach($actividades as $a): ?>
            <div class="timeline-item">
                <div class="timeline-dot <?= strtolower($a['accion'] ?? '') ?>">
                    <?= iconAccion($a['accion'] ?? '') ?>
                </div>
                <div class="timeline-card">
                    <div class="timeline-header">
                        <span class="badge <?= colorAccion($a['accion'] ?? '') ?> timeline-accion">
                            <?= htmlspecialchars($a['accion'] ?? '') ?>
                        </span>
                        <span class="timeline-fecha">
                            <?php
                            $fecha = fieldValue($a, 'createdAt', 'created_at') ?? '';
                            if ($fecha) {
                                $dt = new DateTime($fecha);
                                echo $dt->format('d/m/Y H:i');
                            }
                            ?>
                        </span>
                    </div>
                    <div class="timeline-desc">
                        <?= htmlspecialchars($a['descripcion'] ?? '') ?>
                    </div>
                    <div class="timeline-meta">
                        <?php if(fieldValue($a, 'documentoId', 'documento_id') && !$docId): ?>
                        Doc #<?= fieldValue($a, 'documentoId', 'documento_id') ?> &nbsp;·&nbsp;
                        <a href="?doc=<?= fieldValue($a, 'documentoId', 'documento_id') ?>"
                           style="color:var(--accent)">Ver historial del documento</a>
                        <?php endif; ?>
                        <?php if(fieldValue($a, 'usuarioId', 'usuario_id')): ?>
                        &nbsp;· Usuario #<?= fieldValue($a, 'usuarioId', 'usuario_id') ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <p style="color:var(--text-3);font-size:0.78rem;margin-top:1rem">
            <?= count($actividades) ?> evento(s) registrado(s)
        </p>
    </div>
    <?php endif; ?>

</div>
</div>
</body>
</html>
