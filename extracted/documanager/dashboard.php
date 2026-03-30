<?php
require_once 'includes/config.php';
requireAdmin();

$backendStatus = null;
$viewerMode = false;

// Obtener todos los documentos
$docsRes    = api('GET', '/documentos');
$docs       = $docsRes['data'] ?? [];
if (!is_array($docs)) {
    $backendStatus = $docsRes['data']['message'] ?? 'No fue posible cargar los documentos.';
    $docs = [];
}

// Calcular estadísticas manualmente
$total      = count($docs);
$publicados = count(array_filter($docs, fn($d) => (docValue($d, 'estado') ?? '') === 'PUBLICADO'));
$borradores = count(array_filter($docs, fn($d) => (docValue($d, 'estado') ?? '') === 'BORRADOR'));
$revision   = 0;
$archivados = count(array_filter($docs, fn($d) => (docValue($d, 'estado') ?? '') === 'ARCHIVADO'));
$conArchivo    = count(array_filter($docs, fn($d) => !empty(docValue($d, 'archivoNombre'))));
$confidenciales= count(array_filter($docs, fn($d) => !empty(docValue($d, 'confidencial'))));

// Más vistos (top 5 publicados)
$masVistos = array_filter($docs, fn($d) => (docValue($d, 'estado') ?? '') === 'PUBLICADO');
usort($masVistos, fn($a,$b) => (docValue($b, 'vistas') ?? 0) - (docValue($a, 'vistas') ?? 0));
$masVistos = array_slice(array_values($masVistos), 0, 5);

$docsPublicados = array_values(array_filter($docs, fn($d) => (docValue($d, 'estado') ?? '') === 'PUBLICADO'));
$viewerConArchivo = count(array_filter($docsPublicados, fn($d) => !empty(docValue($d, 'archivoNombre'))));

// Últimos 6
$ultimos = array_slice(array_reverse($docs), 0, 6);
$ultimosPublicados = array_slice(array_reverse($docsPublicados), 0, 6);

// Categorías
$catsRes    = api('GET', '/categorias');
$categorias = $catsRes['data'] ?? [];
if (!is_array($categorias)) $categorias = [];
$totalCats  = count($categorias);

// Actividad reciente
$actRes    = api('GET', '/actividad/recientes');
$actividad = $actRes['data'] ?? [];
if (!is_array($actividad)) $actividad = [];

function iconAccion($accion) {
    return match($accion) {
        'CREAR'    => '✅',
        'EDITAR'   => '✏️',
        'ARCHIVAR' => '📦',
        'DESCARGAR'=> '⬇️',
        default    => '📋'
    };
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — DocuManager</title>
    <link rel="stylesheet" href="/documanager/css/style.css">
    <style>
        .two-col   { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; }
        .three-col { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.25rem; }

        .section-label {
            font-size: 0.72rem; font-weight: 700; letter-spacing: 0.08em;
            text-transform: uppercase; color: var(--text-3);
            margin-bottom: 1rem;
        }

        .quick-link {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 13px;
            background: var(--bg-card); border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            color: var(--text-2); font-size: 0.83rem;
            text-decoration: none; transition: all 0.15s;
        }
        .quick-link:hover {
            border-color: var(--accent);
            color: var(--accent);
            background: var(--accent-soft);
        }

        .activity-item {
            display: flex; align-items: flex-start; gap: 10px;
            padding: 8px 0; border-bottom: 1px solid var(--border);
        }
        .activity-item:last-child { border-bottom: none; }
        .activity-icon {
            width: 26px; height: 26px; border-radius: 7px;
            background: var(--bg-hover);
            display: flex; align-items: center; justify-content: center;
            font-size: 11px; flex-shrink: 0;
        }
        .activity-desc { font-size: 0.82rem; color: var(--text-2); line-height: 1.4; }
        .activity-time { font-size: 0.72rem; color: var(--text-3); margin-top: 2px; }

        .estado-row {
            display: flex; align-items: center; gap: 10px;
            margin-bottom: 8px;
        }
        .estado-label { font-size: 0.78rem; color: var(--text-2); width: 85px; flex-shrink: 0; }
        .estado-track {
            flex: 1; height: 6px; background: var(--bg-hover);
            border-radius: 3px; overflow: hidden;
        }
        .estado-fill  { height: 100%; border-radius: 3px; }
        .estado-num   { font-size: 0.78rem; color: var(--text-3); width: 22px; text-align: right; }

        .cat-pill {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 4px 10px; border-radius: 20px;
            background: var(--bg-hover); border: 1px solid var(--border);
            font-size: 0.78rem; color: var(--text-2); margin: 3px;
        }
        .cat-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
        .viewer-shell { display:grid; grid-template-columns: 1.35fr 0.95fr; gap:1.25rem; margin-bottom:1.25rem; }
        .viewer-hero { padding: 1.75rem; position: relative; overflow:hidden; }
        .viewer-hero::after { content:''; position:absolute; inset:auto -40px -60px auto; width:180px; height:180px; background:radial-gradient(circle, var(--accent-soft) 0%, transparent 70%); border-radius:50%; pointer-events:none; }
        .viewer-hero h3 { font-size:1.4rem; margin-bottom:0.4rem; }
        .viewer-hero p { color:var(--text-2); max-width:560px; }
        .viewer-actions, .viewer-points { display:flex; gap:0.75rem; flex-wrap:wrap; margin-top:1rem; }
        .viewer-pill { display:inline-flex; align-items:center; gap:8px; padding:7px 12px; border-radius:999px; background:var(--bg-hover); border:1px solid var(--border); color:var(--text-2); font-size:0.8rem; }
        .viewer-side h3 { font-size:0.95rem; margin-bottom:0.35rem; }
        .viewer-side p { color:var(--text-2); font-size:0.82rem; margin-bottom:1rem; }
        .viewer-list { display:grid; gap:0.75rem; }
        .viewer-item { padding:0.9rem 1rem; border-radius:var(--radius-sm); background:var(--bg-surface); border:1px solid var(--border); }
        .viewer-item strong { display:block; margin-bottom:4px; }
        .viewer-item span { color:var(--text-2); font-size:0.78rem; }

        @media(max-width:900px) {
            .viewer-shell { grid-template-columns: 1fr; }
            .two-col, .three-col { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<div class="page">
<div class="container">

    <!-- Banner bienvenida -->
    <?php if($backendStatus): ?>
    <div class="alert alert-error" style="margin-bottom:1rem">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <?= htmlspecialchars($backendStatus) ?>
    </div>
    <?php endif; ?>

    <div class="welcome-banner" style="margin-bottom:1.5rem">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem">
            <div>
                <h2><?= $viewerMode ? 'Tu espacio documental' : 'Bienvenido, ' . htmlspecialchars($_SESSION['nombre']) . ' 👋' ?></h2>
                <p>
                    <?= date('l d \d\e F \d\e Y') ?> &nbsp;·&nbsp;
                    <?php if($viewerMode): ?>
                        Acceso en modo consulta con documentos publicados y accesos rápidos.
                    <?php else: ?>
                        Sesión activa como <strong><?= $_SESSION['rol'] ?></strong>
                    <?php endif; ?>
                </p>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
                <?php if(isEditor()): ?>
                <a href="/documanager/documentos.php" class="btn btn-primary btn-sm">+ Nuevo documento</a>
                <a href="/documanager/exportar.php" class="btn btn-secondary btn-sm">📊 Exportar Excel</a>
                <?php endif; ?>
                <a href="/documanager/historial.php" class="btn btn-secondary btn-sm">📋 Ver historial</a>
            </div>
        </div>
    </div>

    <?php if($viewerMode): ?>
    <div class="stats-grid" style="margin-bottom:1.5rem">
        <div class="stat-card green">
            <div class="stat-num"><?= count($docsPublicados) ?></div>
            <div class="stat-label">Documentos disponibles</div>
        </div>
        <div class="stat-card blue">
            <div class="stat-num"><?= $viewerConArchivo ?></div>
            <div class="stat-label">Con descarga disponible</div>
        </div>
        <div class="stat-card">
            <div class="stat-num"><?= $totalCats ?></div>
            <div class="stat-label">Categorías visibles</div>
        </div>
    </div>

    <div class="viewer-shell">
        <div class="card viewer-hero">
            <h3>Consulta rápida sin herramientas de administración</h3>
            <p>Este panel fue simplificado para usuarios de consulta. Aquí ves solo lo necesario: documentos publicados, descargas disponibles e historial reciente.</p>
            <div class="viewer-actions">
                <a href="/documanager/documentos.php?estado=PUBLICADO" class="btn btn-primary">Ver documentos publicados</a>
                <a href="/documanager/historial.php" class="btn btn-secondary">Abrir historial</a>
            </div>
            <div class="viewer-points">
                <span class="viewer-pill">👁️ Solo lectura</span>
                <span class="viewer-pill">📎 Descargas directas</span>
                <span class="viewer-pill">🔎 Búsqueda rápida</span>
            </div>
        </div>

        <div class="card viewer-side">
            <h3>Qué puedes hacer</h3>
            <p>Tu perfil actual está enfocado en consulta. Eliminé avisos repetitivos para que la vista se sienta más limpia.</p>
            <div class="viewer-list">
                <div class="viewer-item"><strong>Buscar documentos</strong><span>Usa filtros por estado, cliente, categoría o fecha.</span></div>
                <div class="viewer-item"><strong>Descargar archivos</strong><span>Abre documentos con archivo adjunto desde la tabla.</span></div>
                <div class="viewer-item"><strong>Revisar cambios</strong><span>Consulta el historial reciente del sistema o por documento.</span></div>
            </div>
        </div>
    </div>

    <div class="two-col" style="margin-bottom:1.25rem">

        <!-- Últimos documentos -->
        <div class="card">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
                <p class="section-label" style="margin:0"><?= $viewerMode ? 'Documentos publicados recientes' : 'Últimos documentos' ?></p>
                <a href="/documanager/documentos.php" style="font-size:0.78rem;color:var(--accent)">Ver todos →</a>
            </div>
            <?php $docsTabla = $viewerMode ? $ultimosPublicados : $ultimos; ?>
            <?php if(empty($docsTabla)): ?>
            <div class="empty" style="padding:2rem"><p>No hay documentos aún</p></div>
            <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Título</th><th>Tipo</th><th>Estado</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach($docsTabla as $d): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars(docValue($d, 'titulo') ?? '') ?></strong>
                            <?php if(!empty(docValue($d, 'confidencial'))): ?>
                            <span style="color:var(--warning)"> 🔒</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-size:0.78rem"><?= htmlspecialchars(docValue($d, 'tipo') ?? '-') ?></td>
                        <td>
                            <span class="badge badge-<?= strtolower(docValue($d, 'estado') ?? 'borrador') ?>">
                                <?= docValue($d, 'estado') ?? 'BORRADOR' ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- Actividad reciente -->
        <div class="card">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
                <p class="section-label" style="margin:0">Actividad reciente</p>
                <a href="/documanager/historial.php" style="font-size:0.78rem;color:var(--accent)">Ver todo →</a>
            </div>
            <?php if(empty($actividad)): ?>
            <div class="empty" style="padding:2rem"><p>No hay actividad registrada</p></div>
            <?php else: ?>
            <?php foreach(array_slice($actividad, 0, 6) as $a): ?>
            <div class="activity-item">
                <div class="activity-icon"><?= iconAccion($a['accion'] ?? '') ?></div>
                <div>
                    <div class="activity-desc"><?= htmlspecialchars($a['descripcion'] ?? '') ?></div>
                    <div class="activity-time">
                        <?php
                        if ($fechaAct = fieldValue($a, 'createdAt', 'created_at')) {
                            try {
                                $dt = new DateTime($fechaAct);
                                echo $dt->format('d/m/Y H:i');
                            } catch(Exception $e) {
                                echo $fechaAct;
                            }
                        }
                        ?>
                        <?php if(fieldValue($a, 'documentoId', 'documento_id')): ?>
                        &nbsp;·&nbsp;
                        <a href="/documanager/historial.php?doc=<?= fieldValue($a, 'documentoId', 'documento_id') ?>"
                           style="color:var(--accent)">Doc #<?= fieldValue($a, 'documentoId', 'documento_id') ?></a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php else: ?>

    <!-- Stats principales -->
    <div class="stats-grid" style="margin-bottom:1.5rem">
        <div class="stat-card">
            <div class="stat-icon">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            </div>
            <div class="stat-num"><?= $total ?></div>
            <div class="stat-label">Total documentos</div>
        </div>
        <div class="stat-card green">
            <div class="stat-icon">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </div>
            <div class="stat-num"><?= $publicados ?></div>
            <div class="stat-label">Publicados</div>
        </div>
        <div class="stat-card yellow">
            <div class="stat-icon">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
            <div class="stat-num"><?= $revision ?></div>
            <div class="stat-label">En revisión</div>
        </div>
        <div class="stat-card blue">
            <div class="stat-icon">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
            </div>
            <div class="stat-num"><?= $totalCats ?></div>
            <div class="stat-label">Categorías</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            </div>
            <div class="stat-num"><?= $conArchivo ?></div>
            <div class="stat-label">Con archivo adjunto</div>
        </div>
        <div class="stat-card red">
            <div class="stat-icon">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            </div>
            <div class="stat-num"><?= $confidenciales ?></div>
            <div class="stat-label">Confidenciales</div>
        </div>
    </div>

    <!-- Fila principal -->
    <div class="two-col" style="margin-bottom:1.25rem">

        <!-- Últimos documentos -->
        <div class="card">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
                <p class="section-label" style="margin:0">Últimos documentos</p>
                <a href="/documanager/documentos.php" style="font-size:0.78rem;color:var(--accent)">Ver todos →</a>
            </div>
            <?php if(empty($ultimos)): ?>
            <div class="empty" style="padding:2rem"><p>No hay documentos aún</p></div>
            <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Título</th><th>Tipo</th><th>Estado</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach($ultimos as $d): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars(docValue($d, 'titulo') ?? '') ?></strong>
                            <?php if(!empty(docValue($d, 'confidencial'))): ?>
                            <span style="color:var(--warning)"> 🔒</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-size:0.78rem"><?= htmlspecialchars(docValue($d, 'tipo') ?? '-') ?></td>
                        <td>
                            <span class="badge badge-<?= strtolower(docValue($d, 'estado') ?? 'borrador') ?>">
                                <?= docValue($d, 'estado') ?? 'BORRADOR' ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- Actividad reciente -->
        <div class="card">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
                <p class="section-label" style="margin:0">Actividad reciente</p>
                <a href="/documanager/historial.php" style="font-size:0.78rem;color:var(--accent)">Ver todo →</a>
            </div>
            <?php if(empty($actividad)): ?>
            <div class="empty" style="padding:2rem"><p>No hay actividad registrada</p></div>
            <?php else: ?>
            <?php foreach(array_slice($actividad, 0, 6) as $a): ?>
            <div class="activity-item">
                <div class="activity-icon"><?= iconAccion($a['accion'] ?? '') ?></div>
                <div>
                    <div class="activity-desc"><?= htmlspecialchars($a['descripcion'] ?? '') ?></div>
                    <div class="activity-time">
                        <?php
                        if ($fechaAct = fieldValue($a, 'createdAt', 'created_at')) {
                            try {
                                $dt = new DateTime($fechaAct);
                                echo $dt->format('d/m/Y H:i');
                            } catch(Exception $e) {
                                echo $fechaAct;
                            }
                        }
                        ?>
                        <?php if(fieldValue($a, 'documentoId', 'documento_id')): ?>
                        &nbsp;·&nbsp;
                        <a href="/documanager/historial.php?doc=<?= fieldValue($a, 'documentoId', 'documento_id') ?>"
                           style="color:var(--accent)">Doc #<?= fieldValue($a, 'documentoId', 'documento_id') ?></a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Fila secundaria -->
    <div class="three-col">

        <!-- Distribución por estado -->
        <div class="card">
            <p class="section-label">Distribución por estado</p>
            <?php
            $estados = [
                'PUBLICADO' => [$publicados, '#10b981'],
                'BORRADOR'  => [$borradores, '#94a3b8'],
                                'ARCHIVADO' => [$archivados,  '#ef4444'],
            ];
            foreach($estados as $nombre => [$cantidad, $color]):
                $pct = $total > 0 ? round(($cantidad / $total) * 100) : 0;
            ?>
            <div class="estado-row">
                <span class="estado-label"><?= $nombre ?></span>
                <div class="estado-track">
                    <div class="estado-fill" style="width:<?= $pct ?>%;background:<?= $color ?>"></div>
                </div>
                <span class="estado-num"><?= $cantidad ?></span>
            </div>
            <?php endforeach; ?>
            <div style="margin-top:1rem;padding-top:1rem;border-top:1px solid var(--border)">
                <div style="display:flex;justify-content:space-between;font-size:0.78rem;color:var(--text-3)">
                    <span>Borradores</span>
                    <span><?= $total > 0 ? round(($borradores/$total)*100) : 0 ?>%</span>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:0.78rem;color:var(--success);margin-top:4px">
                    <span>Publicados</span>
                    <span><?= $total > 0 ? round(($publicados/$total)*100) : 0 ?>%</span>
                </div>
            </div>
        </div>

        <!-- Más vistos -->
        <div class="card">
            <p class="section-label">Más vistos</p>
            <?php if(empty($masVistos)): ?>
            <div class="empty" style="padding:1.5rem"><p>No hay publicados aún</p></div>
            <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Título</th><th>Vistas</th></tr></thead>
                    <tbody>
                    <?php foreach($masVistos as $d): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars(docValue($d, 'titulo') ?? '') ?></strong></td>
                        <td><?= (int)($d['vistas'] ?? 0) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- Categorías + accesos rápidos -->
        <div class="card">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
                <p class="section-label" style="margin:0">Categorías</p>
                <?php if(isEditor()): ?>
                <a href="/documanager/categorias.php" style="font-size:0.78rem;color:var(--accent)">Gestionar →</a>
                <?php endif; ?>
            </div>
            <?php if(empty($categorias)): ?>
            <div class="empty" style="padding:1rem"><p>Sin categorías</p></div>
            <?php else: ?>
            <div style="display:flex;flex-wrap:wrap;margin-bottom:1rem">
                <?php foreach($categorias as $c): ?>
                <span class="cat-pill">
                    <span class="cat-dot" style="background:<?= htmlspecialchars($c['color'] ?? '#6366f1') ?>"></span>
                    <?= htmlspecialchars($c['nombre'] ?? '') ?>
                </span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div style="border-top:1px solid var(--border);padding-top:1rem">
                <p class="section-label">Accesos rápidos</p>
                <div style="display:flex;flex-direction:column;gap:6px">
                                        <a href="/documanager/documentos.php?estado=BORRADOR" class="quick-link">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/></svg>
                        Borradores (<?= $borradores ?>)
                    </a>
                    <a href="/documanager/exportar.php" class="quick-link">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        Exportar a Excel
                    </a>
                    <a href="/documanager/historial.php" class="quick-link">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/></svg>
                        Historial de actividad
                    </a>
                </div>
            </div>
        </div>

    </div>

    <?php endif; ?>

</div>
</div>
</body>
</html>
