<?php
require_once 'includes/config.php';
requireLogin();

$msg = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_action'])) {
    if (!isEditor()) { $error = 'No tienes permisos para esta acción.'; goto show; }

    $id = (int)($_POST['id'] ?? 0);

    if ($_POST['_action'] === 'archivar') {
        $res = api('DELETE', "/documentos/$id");
        $msg = $res['code'] === 204 ? 'Documento archivado correctamente.' : 'Error al archivar.';
    }

    if (in_array($_POST['_action'], ['crear','editar'])) {
        $data = [
            'titulo'      => trim($_POST['titulo'] ?? ''),
            'descripcion' => trim($_POST['descripcion'] ?? ''),
            'tipo'        => $_POST['tipo'] ?? '',
            'estado'      => $_POST['estado'] ?? 'BORRADOR',
            'categoriaId' => $_POST['categoriaId'] ? (int)$_POST['categoriaId'] : null,
            'cliente'     => trim($_POST['cliente'] ?? ''),
            'fechaDoc'    => $_POST['fechaDoc'] ?: null,
            'etiquetas'   => trim($_POST['etiquetas'] ?? ''),
            'confidencial'=> isset($_POST['confidencial']),
        ];
        if ($_POST['_action'] === 'crear') {
            $res = api('POST', '/documentos', $data);
            $msg = $res['code'] === 201 ? 'Documento creado correctamente.' : 'Error al crear el documento.';
        } else {
            $res = api('PUT', "/documentos/$id", $data);
            $msg = $res['code'] === 200 ? 'Documento actualizado correctamente.' : 'Error al actualizar.';
        }

        // Subir archivo si se seleccionó uno
        if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] === 0 && isset($res['data']['id'])) {
            $docId = $res['data']['id'];
            $ch = curl_init("http://localhost:8080/archivos/subir/$docId");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $_SESSION['token']]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, [
                'archivo' => new CURLFile(
                    $_FILES['archivo']['tmp_name'],
                    $_FILES['archivo']['type'],
                    $_FILES['archivo']['name']
                )
            ]);
            curl_exec($ch);
            curl_close($ch);
        }
    }
}

show:
// Filtros
$buscar      = trim($_GET['buscar'] ?? '');
$estadoFil   = $_GET['estado']      ?? '';
$catFil      = $_GET['categoria']   ?? '';
$tipoFil     = $_GET['tipo']        ?? '';
$clienteFil  = trim($_GET['cliente']  ?? '');
$fechaDesde  = $_GET['fechaDesde']  ?? '';
$fechaHasta  = $_GET['fechaHasta']  ?? '';

// Construir endpoint con filtros
$params = [];
if ($buscar)     $params['buscar']      = $buscar;
if ($tipoFil)    $params['tipo']        = $tipoFil;
if ($estadoFil)  $params['estado']      = $estadoFil;
if ($clienteFil) $params['cliente']     = $clienteFil;
if ($catFil)     $params['categoriaId'] = $catFil;
if ($fechaDesde) $params['fechaDesde']  = $fechaDesde;
if ($fechaHasta) $params['fechaHasta']  = $fechaHasta;

$endpoint   = '/documentos' . (!empty($params) ? '?' . http_build_query($params) : '');
$docs       = api('GET', $endpoint);
$documentos = $docs['data'] ?? [];

$cats       = api('GET', '/categorias');
$categorias = $cats['data'] ?? [];

$editDoc = null;
if (isset($_GET['edit']) && isEditor()) {
    $r       = api('GET', '/documentos/' . (int)$_GET['edit']);
    $editDoc = $r['data'] ?? null;
}

$hayFiltros = $buscar || $tipoFil || $estadoFil || $clienteFil || $catFil || $fechaDesde || $fechaHasta;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documentos — DocuManager</title>
    <link rel="stylesheet" href="/documanager/css/style.css">
    <style>
        .advanced-filters {
            display: none;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 10px;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid var(--border);
        }
        .advanced-filters.show { display: grid; }
        .toggle-advanced {
            font-size: 0.78rem; color: var(--accent);
            cursor: pointer; background: none; border: none;
            padding: 0; margin-top: 8px;
            display: inline-flex; align-items: center; gap: 5px;
        }
        .toggle-advanced:hover { text-decoration: underline; }
        .filter-active { background: var(--accent-soft) !important; border-color: rgba(99,102,241,0.3) !important; }
        .btn-excel {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 8px 16px; border-radius: var(--radius-sm);
            background: rgba(16,185,129,0.1); color: var(--success);
            border: 1px solid rgba(16,185,129,0.3);
            font-size: 0.85rem; font-weight: 500;
            text-decoration: none; transition: all 0.15s;
        }
        .btn-excel:hover { background: rgba(16,185,129,0.2); }
    </style>
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<div class="page">
<div class="container">

    <div class="page-header">
        <div>
            <h1>Documentos</h1>
            <p>Gestiona y consulta los documentos del sistema</p>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <a href="/documanager/exportar.php<?= !empty($params) ? '?' . http_build_query($params) : '' ?>"
               class="btn-excel">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Exportar Excel
            </a>
            <?php if(isEditor()): ?>
            <button class="btn btn-primary" onclick="openModal('modalDoc')">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Nuevo documento
            </button>
            <?php endif; ?>
        </div>
    </div>

    <?php if(!isEditor()): ?>
    <div class="readonly-notice">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        Estás en modo solo lectura. Contacta a un editor o administrador para modificar documentos.
    </div>
    <?php endif; ?>

    <?php if($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <?php if($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <!-- Filtros -->
    <form method="GET" class="card <?= $hayFiltros ? 'filter-active' : '' ?>"
          style="padding:1rem;margin-bottom:1.25rem">

        <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
            <input type="text" name="buscar" class="form-control"
                   placeholder="🔍 Buscar por título o descripción..."
                   value="<?= htmlspecialchars($buscar) ?>"
                   style="flex:2;min-width:200px">
            <select name="estado" class="form-control" style="flex:1;min-width:130px">
                <option value="">Todos los estados</option>
                <?php foreach(['BORRADOR','REVISION','PUBLICADO','ARCHIVADO'] as $e): ?>
                <option value="<?=$e?>" <?= $estadoFil===$e?'selected':'' ?>><?= $e ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary">Buscar</button>
            <?php if($hayFiltros): ?>
            <a href="/documanager/documentos.php" class="btn btn-secondary">Limpiar</a>
            <?php endif; ?>
        </div>

        <button type="button" class="toggle-advanced" onclick="toggleAvanzado()">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="6" x2="20" y2="6"/><line x1="8" y1="12" x2="16" y2="12"/><line x1="11" y1="18" x2="13" y2="18"/></svg>
            <span id="toggleLabel">Búsqueda avanzada</span>
        </button>

        <div class="advanced-filters <?= ($tipoFil||$clienteFil||$catFil||$fechaDesde||$fechaHasta)?'show':'' ?>"
             id="advancedFilters">
            <div class="form-group" style="margin:0">
                <label>Tipo de documento</label>
                <select name="tipo" class="form-control">
                    <option value="">Todos los tipos</option>
                    <?php foreach(['CONTRATO','FACTURA','REPORTE','MANUAL','FORMULARIO','ACUERDO','MEMORANDO','OFICIO','OTRO'] as $t): ?>
                    <option value="<?=$t?>" <?= $tipoFil===$t?'selected':'' ?>><?=$t?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin:0">
                <label>Cliente / Empresa</label>
                <input type="text" name="cliente" class="form-control"
                       placeholder="Nombre del cliente..."
                       value="<?= htmlspecialchars($clienteFil) ?>">
            </div>
            <div class="form-group" style="margin:0">
                <label>Categoría</label>
                <select name="categoria" class="form-control">
                    <option value="">Todas las categorías</option>
                    <?php foreach($categorias as $c): ?>
                    <option value="<?=$c['id']?>" <?= $catFil==$c['id']?'selected':'' ?>>
                        <?= htmlspecialchars($c['nombre']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin:0">
                <label>Fecha desde</label>
                <input type="date" name="fechaDesde" class="form-control"
                       value="<?= htmlspecialchars($fechaDesde) ?>">
            </div>
            <div class="form-group" style="margin:0">
                <label>Fecha hasta</label>
                <input type="date" name="fechaHasta" class="form-control"
                       value="<?= htmlspecialchars($fechaHasta) ?>">
            </div>
        </div>
    </form>

    <?php if($hayFiltros): ?>
    <div style="margin-bottom:1rem;font-size:0.82rem;color:var(--text-2)">
        Mostrando resultados filtrados —
        <?php
        $filtrosActivos = [];
        if($buscar)     $filtrosActivos[] = "Título: <strong>$buscar</strong>";
        if($tipoFil)    $filtrosActivos[] = "Tipo: <strong>$tipoFil</strong>";
        if($estadoFil)  $filtrosActivos[] = "Estado: <strong>$estadoFil</strong>";
        if($clienteFil) $filtrosActivos[] = "Cliente: <strong>$clienteFil</strong>";
        if($fechaDesde) $filtrosActivos[] = "Desde: <strong>$fechaDesde</strong>";
        if($fechaHasta) $filtrosActivos[] = "Hasta: <strong>$fechaHasta</strong>";
        echo implode(' · ', $filtrosActivos);
        ?>
    </div>
    <?php endif; ?>

    <!-- Tabla -->
    <div class="card">
        <?php if(empty($documentos)): ?>
        <div class="empty">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            <p>No se encontraron documentos</p>
        </div>
        <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Título</th>
                        <th>Tipo</th>
                        <th>Estado</th>
                        <th>Cliente</th>
                        <th>Fecha</th>
                        <th>Vistas</th>
                        <th>Archivo</th>
                        <th>Historial</th>
                        <?php if(isEditor()): ?><th>Acciones</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                <?php foreach($documentos as $d): ?>
                <tr>
                    <td style="color:var(--text-3)"><?= $d['id'] ?></td>
                    <td>
                        <strong><?= htmlspecialchars($d['titulo']) ?></strong>
                        <?php if($d['confidencial'] ?? false): ?>
                        <span style="color:var(--warning);margin-left:4px" title="Confidencial">🔒</span>
                        <?php endif; ?>
                        <?php if(!empty($d['etiquetas'])): ?>
                        <br><span style="font-size:0.72rem;color:var(--text-3)"><?= htmlspecialchars($d['etiquetas']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($d['tipo'] ?? '-') ?></td>
                    <td><span class="badge badge-<?= strtolower($d['estado'] ?? 'borrador') ?>"><?= $d['estado'] ?? 'BORRADOR' ?></span></td>
                    <td><?= htmlspecialchars($d['cliente'] ?? '-') ?></td>
                    <td><?= !empty($d['fechaDoc']) ? date('d/m/Y', strtotime($d['fechaDoc'])) : '-' ?></td>
                    <td><?= $d['vistas'] ?? 0 ?></td>
                    <td>
                        <?php if(!empty($d['archivoNombre'])): ?>
                        <a href="http://localhost:8080/archivos/descargar/<?= $d['id'] ?>"
                           class="btn btn-success btn-sm" target="_blank">⬇️ Descargar</a>
                        <?php else: ?>
                        <span style="color:var(--text-3);font-size:0.78rem">Sin archivo</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="/documanager/historial.php?doc=<?= $d['id'] ?>"
                           class="btn btn-secondary btn-sm" title="Ver historial">📋</a>
                    </td>
                    <?php if(isEditor()): ?>
                    <td>
                        <div style="display:flex;gap:5px">
                            <a href="?edit=<?= $d['id'] ?>" class="btn btn-secondary btn-sm">Editar</a>
                            <?php if(($d['estado'] ?? '') !== 'ARCHIVADO'): ?>
                            <form method="POST" onsubmit="return confirm('¿Archivar este documento?')">
                                <input type="hidden" name="_action" value="archivar">
                                <input type="hidden" name="id" value="<?= $d['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Archivar</button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p style="color:var(--text-3);font-size:0.78rem;margin-top:1rem">
            <?= count($documentos) ?> documento(s) encontrado(s)
        </p>
        <?php endif; ?>
    </div>

</div>
</div>

<?php if(isEditor()): ?>
<!-- Modal Crear / Editar -->
<div class="modal-overlay <?= $editDoc ? 'open' : '' ?>" id="modalDoc">
    <div class="modal">
        <div class="modal-header">
            <h2><?= $editDoc ? 'Editar documento' : 'Nuevo documento' ?></h2>
            <button class="modal-close" onclick="closeModal('modalDoc')">×</button>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="_action" value="<?= $editDoc ? 'editar' : 'crear' ?>">
            <input type="hidden" name="id" value="<?= $editDoc['id'] ?? '' ?>">

            <div class="form-group">
                <label>Título *</label>
                <input type="text" name="titulo" class="form-control" required
                       value="<?= htmlspecialchars($editDoc['titulo'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Descripción</label>
                <textarea name="descripcion" class="form-control"><?= htmlspecialchars($editDoc['descripcion'] ?? '') ?></textarea>
            </div>
            <div class="grid-2">
                <div class="form-group">
                    <label>Tipo de documento</label>
                    <select name="tipo" class="form-control">
                        <?php foreach(['CONTRATO','FACTURA','REPORTE','MANUAL','FORMULARIO','ACUERDO','MEMORANDO','OFICIO','OTRO'] as $t): ?>
                        <option value="<?=$t?>" <?= ($editDoc['tipo'] ?? '') === $t ? 'selected' : '' ?>><?=$t?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Estado</label>
                    <select name="estado" class="form-control">
                        <?php foreach(['BORRADOR','REVISION','PUBLICADO','ARCHIVADO'] as $e): ?>
                        <option value="<?=$e?>" <?= ($editDoc['estado'] ?? 'BORRADOR') === $e ? 'selected' : '' ?>><?=$e?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="grid-2">
                <div class="form-group">
                    <label>Categoría</label>
                    <select name="categoriaId" class="form-control">
                        <option value="">Sin categoría</option>
                        <?php foreach($categorias as $c): ?>
                        <option value="<?=$c['id']?>" <?= ($editDoc['categoriaId'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['nombre']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Cliente / Empresa</label>
                    <input type="text" name="cliente" class="form-control"
                           value="<?= htmlspecialchars($editDoc['cliente'] ?? '') ?>">
                </div>
            </div>
            <div class="grid-2">
                <div class="form-group">
                    <label>Fecha del documento</label>
                    <input type="date" name="fechaDoc" class="form-control"
                           value="<?= $editDoc['fechaDoc'] ?? '' ?>">
                </div>
                <div class="form-group">
                    <label>Etiquetas</label>
                    <input type="text" name="etiquetas" class="form-control"
                           placeholder="urgente, legal, 2025..."
                           value="<?= htmlspecialchars($editDoc['etiquetas'] ?? '') ?>">
                </div>
            </div>
            <div class="checkbox-row">
                <input type="checkbox" name="confidencial" id="conf"
                       <?= ($editDoc['confidencial'] ?? false) ? 'checked' : '' ?>>
                <label for="conf">🔒 Marcar como documento confidencial</label>
            </div>
            <div class="form-group" style="margin-top:1rem">
                <label>Archivo adjunto (PDF, Word, Excel)</label>
                <input type="file" name="archivo" accept=".pdf,.doc,.docx,.xls,.xlsx"
                       class="form-control" style="padding:6px">
                <?php if(!empty($editDoc['archivoNombre'])): ?>
                <div style="margin-top:8px;display:flex;align-items:center;gap:10px">
                    <span style="font-size:0.8rem;color:var(--text-2)">
                        📎 <?= htmlspecialchars($editDoc['archivoNombre']) ?>
                    </span>
                    <a href="http://localhost:8080/archivos/descargar/<?= $editDoc['id'] ?>"
                       class="btn btn-success btn-sm" target="_blank">⬇️ Descargar</a>
                </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalDoc')">Cancelar</button>
                <button type="submit" class="btn btn-primary">
                    <?= $editDoc ? 'Guardar cambios' : 'Crear documento' ?>
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) {
    document.getElementById(id).classList.remove('open');
    if (window.location.search.includes('edit='))
        window.location = '/documanager/documentos.php';
}
document.querySelectorAll('.modal-overlay').forEach(o =>
    o.addEventListener('click', e => { if(e.target === o) closeModal(o.id); })
);
function toggleAvanzado() {
    const panel = document.getElementById('advancedFilters');
    const label = document.getElementById('toggleLabel');
    const open  = panel.classList.toggle('show');
    label.textContent = open ? 'Ocultar búsqueda avanzada' : 'Búsqueda avanzada';
}
</script>
</body>
</html>
