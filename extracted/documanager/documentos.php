<?php
require_once 'includes/config.php';
requireLogin();

$msg = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_action'])) {
    if (!canManageDocuments()) { $error = 'No tienes permisos para esta acción.'; goto show; }

    $id = (int)($_POST['id'] ?? 0);
    $action = $_POST['_action'] ?? '';

    if ($action === 'archivar') {
        $res = api('DELETE', "/documentos/$id");
        if ($res['code'] === 204) {
            header('Location: /documanager/documentos.php?msg=' . urlencode('Documento archivado correctamente.'));
            exit;
        }
        $error = $res['data']['message'] ?? 'Error al archivar.';
    }

    if (in_array($action, ['crear','editar'], true)) {
        $fechaDoc = trim($_POST['fechaDoc'] ?? '');
        if ($action === 'crear' && $fechaDoc === '') {
            $fechaDoc = date('Y-m-d');
        }

        $categoriaSeleccionada = (isset($_POST['categoriaId']) && $_POST['categoriaId'] !== '') ? (int)$_POST['categoriaId'] : null;
        $data = [
            'titulo'       => trim($_POST['titulo'] ?? ''),
            'descripcion'  => trim($_POST['descripcion'] ?? ''),
            'tipo'         => $_POST['tipo'] ?? '',
            'estado'       => $_POST['estado'] ?? 'BORRADOR',
            // enviar ambas claves para compatibilidad (camelCase y snake_case)
            'categoriaId'  => $categoriaSeleccionada,
            'categoria_id' => $categoriaSeleccionada,
            'cliente'      => trim($_POST['cliente'] ?? ''),
            'fechaDoc'     => $fechaDoc !== '' ? $fechaDoc : null,
            'etiquetas'    => trim($_POST['etiquetas'] ?? ''),
            'confidencial' => isset($_POST['confidencial']),
        ];

        if ($action === 'crear') {
            $res = api('POST', '/documentos', $data);
            $docId = (int)($res['data']['id'] ?? 0);
            $successCode = $res['code'] === 201;
            $successMsg = 'Documento creado correctamente.';
        } else {
            $res = api('PUT', "/documentos/$id", $data);
            $docId = $id;
            $successCode = $res['code'] === 200;
            $successMsg = 'Documento actualizado correctamente.';
        }

        if ($successCode && isset($_FILES['archivo']) && $_FILES['archivo']['error'] === 0 && $docId > 0) {
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

        if ($successCode) {
            header('Location: /documanager/documentos.php?msg=' . urlencode($successMsg));
            exit;
        }

        $error = $res['data']['message'] ?? ($action === 'crear' ? 'Error al crear el documento.' : 'Error al actualizar el documento.');
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
if ($catFil) { $params['categoriaId'] = $catFil; $params['categoria_id'] = $catFil; }
if ($fechaDesde) $params['fechaDesde']  = $fechaDesde;
if ($fechaHasta) $params['fechaHasta']  = $fechaHasta;

$endpoint   = '/documentos' . (!empty($params) ? '?' . http_build_query($params) : '');
$docs       = api('GET', $endpoint);
$documentosRaw = is_array($docs['data'] ?? null) ? $docs['data'] : [];
$isPresidente = isPresidente();
$documentos = array_values(array_filter($documentosRaw, function($d) use ($isPresidente) {
    $soloPresidente = (bool)(docValue((array)$d, 'confidencial') ?? false);
    return $isPresidente || !$soloPresidente;
}));
$ocultosSoloPresidente = max(0, count($documentosRaw) - count($documentos));

$cats       = api('GET', '/categorias');
$categorias = $cats['data'] ?? [];

$editDoc = null;
$canManage = canManageDocuments();
if (isset($_GET['edit']) && $canManage) {
    $r       = api('GET', '/documentos/' . (int)$_GET['edit']);
    $editDoc = $r['data'] ?? null;
    if (!$isPresidente && (bool)(docValue((array)$editDoc, 'confidencial') ?? false)) {
        $editDoc = null;
        $error = 'Este documento está marcado como solo visible para Presidencia.';
    }
}

$hayFiltros = $buscar || $tipoFil || $estadoFil || $clienteFil || $catFil || $fechaDesde || $fechaHasta;
$msg = $_GET['msg'] ?? $msg;
$tiposDocumento = ['CONTRATO','FACTURA','REPORTE','FORMULARIO','MANUAL','PDF_ESCANEADO','ACUERDO','OTRO'];
$estadosDocumento = ['BORRADOR','PUBLICADO','ARCHIVADO'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documentos — DocuManager</title>
    <link rel="stylesheet" href="<?= assetUrl('/documanager/css/style.css') ?>">
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
        .docs-table td { vertical-align: top; }
        .docs-table .col-id { min-width: 56px; }
        .docs-table .col-titulo { min-width: 220px; }
        .docs-table .col-tipo { min-width: 140px; }
        .docs-table .col-estado { min-width: 120px; }
        .docs-table .col-cliente { min-width: 170px; }
        .docs-table .col-fecha, .docs-table .col-vistas { min-width: 90px; }
        .docs-table .col-archivo, .docs-table .col-historial, .docs-table .col-acciones { min-width: 170px; }
        .row-actions, .archivo-actions {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            align-items: center;
        }
        .single-action { display: inline-flex; }
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
            <?php if($canManage): ?>
            <button class="btn btn-primary" onclick="openModal('modalDoc')">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Nuevo documento
            </button>
            <?php endif; ?>
        </div>
    </div>

    <?php if($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <?php if($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if(!$isPresidente && $ocultosSoloPresidente > 0): ?>
    <div class="alert alert-info">Hay <?= (int)$ocultosSoloPresidente ?> documento(s) restringido(s) para Presidencia que no se muestran en este listado.</div>
    <?php endif; ?>

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
                <?php foreach($estadosDocumento as $e): ?>
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
                    <?php foreach($tiposDocumento as $t): ?>
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
            <table class="docs-table">
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
                        <?php if($canManage): ?><th>Acciones</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                <?php foreach($documentos as $d): ?>
                <tr>
                    <td class="col-id" style="color:var(--text-3)"><?= docValue($d, 'id') ?></td>
                    <td class="col-titulo">
                        <strong><?= htmlspecialchars(docValue($d, 'titulo') ?? '') ?></strong>
                        <?php if((bool)(docValue($d, 'confidencial') ?? false)): ?>
                        <span style="color:var(--warning);margin-left:4px" title="Confidencial">🔒</span>
                        <span style="color:var(--warning);font-size:0.72rem;margin-left:4px">Solo Presidencia</span>
                        <?php endif; ?>
                        <?php if(!empty(docValue($d, 'etiquetas'))): ?>
                        <br><span style="font-size:0.72rem;color:var(--text-3)"><?= htmlspecialchars(docValue($d, 'etiquetas') ?? '') ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="col-tipo"><?= htmlspecialchars(docValue($d, 'tipo') ?? '-') ?></td>
                    <td class="col-estado"><span class="badge badge-<?= strtolower(docValue($d, 'estado') ?? 'borrador') ?>"><?= docValue($d, 'estado') ?? 'BORRADOR' ?></span></td>
                    <td class="col-cliente"><?= htmlspecialchars(docValue($d, 'cliente') ?? '-') ?></td>
                    <td class="col-fecha"><?= ($fechaDocFila = docValue($d, 'fecha')) ? date('d/m/Y', strtotime($fechaDocFila)) : '-' ?></td>
                    <td class="col-vistas"><?= (int)(docValue($d, 'vistas') ?? 0) ?></td>
                    <td class="col-archivo">
                        <?php if(docValue($d, 'archivoNombre')): ?>
                        <div class="archivo-actions">
                            <a href="/documanager/ver_documento.php?id=<?= docValue($d, 'id') ?>"
                               class="btn btn-secondary btn-sm">Ver detalles</a>
                            <a href="http://localhost:8080/archivos/descargar/<?= docValue($d, 'id') ?>"
                               class="btn btn-success btn-sm" target="_blank">⬇️ Descargar</a>
                        </div>
                        <?php else: ?>
                        <span style="color:var(--text-3);font-size:0.78rem">Sin archivo</span>
                        <?php endif; ?>
                    </td>
                    <td class="col-historial">
                        <a href="/documanager/historial.php?doc=<?= docValue($d, 'id') ?>"
                           class="btn btn-secondary btn-sm single-action" title="Ver historial">📋</a>
                    </td>
                    <?php if($canManage): ?>
                    <td class="col-acciones">
                        <div class="row-actions">
                            <a href="?edit=<?= docValue($d, 'id') ?>" class="btn btn-secondary btn-sm">Editar</a>
                            <?php if((docValue($d, 'estado') ?? '') !== 'ARCHIVADO'): ?>
                            <form method="POST" onsubmit="return confirm('¿Archivar este documento?')">
                                <input type="hidden" name="_action" value="archivar">
                                <input type="hidden" name="id" value="<?= docValue($d, 'id') ?>">
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

<?php if($canManage): ?>
<!-- Modal Crear / Editar -->
<div class="modal-overlay <?= $editDoc ? 'open' : '' ?>" id="modalDoc">
    <div class="modal">
        <div class="modal-header">
            <h2><?= $editDoc ? 'Editar documento' : 'Nuevo documento' ?></h2>
            <button class="modal-close" onclick="closeModal('modalDoc')">×</button>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="_action" value="<?= $editDoc ? 'editar' : 'crear' ?>">
            <input type="hidden" name="id" value="<?= docValue($editDoc ?? [], 'id') ?? '' ?>">

            <div class="form-group">
                <label>Título *</label>
                <input type="text" name="titulo" class="form-control" required
                       value="<?= htmlspecialchars(docValue($editDoc ?? [], 'titulo') ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Descripción</label>
                <textarea name="descripcion" class="form-control"><?= htmlspecialchars(docValue($editDoc ?? [], 'descripcion') ?? '') ?></textarea>
            </div>
            <div class="grid-2">
                <div class="form-group">
                    <label>Tipo de documento</label>
                    <select name="tipo" class="form-control">
                        <?php foreach($tiposDocumento as $t): ?>
                        <option value="<?=$t?>" <?= (docValue($editDoc ?? [], 'tipo') ?? '') === $t ? 'selected' : '' ?>><?=$t?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Estado</label>
                    <select name="estado" class="form-control">
                        <?php foreach($estadosDocumento as $e): ?>
                        <option value="<?=$e?>" <?= (docValue($editDoc ?? [], 'estado') ?? 'BORRADOR') === $e ? 'selected' : '' ?>><?=$e?></option>
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
                        <option value="<?=$c['id']?>" <?= (docValue($editDoc ?? [], 'categoria') ?? '') == $c['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['nombre']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Cliente / Empresa</label>
                    <input type="text" name="cliente" class="form-control"
                           value="<?= htmlspecialchars(docValue($editDoc ?? [], 'cliente') ?? '') ?>">
                </div>
            </div>
            <div class="grid-2">
                <div class="form-group">
                    <label>Fecha del documento</label>
                    <input type="date" name="fechaDoc" id="fechaDocInput" class="form-control"
                           value="<?= htmlspecialchars(docValue($editDoc ?? [], 'fecha') ?? date('Y-m-d')) ?>"
                           placeholder="<?= $editDoc ? '' : 'Se guardará la fecha del archivo o la fecha de hoy' ?>">
                    <?php if(!$editDoc): ?>
                    <small style="display:block;margin-top:6px;color:var(--text-3);font-size:0.75rem">Si seleccionas un archivo nuevo, tomaremos su fecha. Si no, se guardará la fecha de hoy.</small>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label>Etiquetas</label>
                    <input type="text" name="etiquetas" class="form-control"
                           placeholder="urgente, legal, 2025..."
                           value="<?= htmlspecialchars(docValue($editDoc ?? [], 'etiquetas') ?? '') ?>">
                </div>
            </div>
            <div class="checkbox-row">
                <input type="checkbox" name="confidencial" id="conf"
                       <?= (docValue($editDoc ?? [], 'confidencial') ?? false) ? 'checked' : '' ?>>
                <label for="conf">👑 Solo Presidencia (el documento solo será visible para ese rol)</label>
            </div>
            <div class="form-group" style="margin-top:1rem">
                <label>Archivo adjunto (PDF, Word, Excel, imágenes)</label>
                <input type="file" name="archivo" id="archivoInput" accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.png,.jpg,.jpeg,.webp,.gif"
                       class="form-control" style="padding:6px">
                <small style="display:block;margin-top:6px;color:var(--text-3);font-size:0.75rem">Formatos soportados: PDF, Word, Excel, CSV e imágenes PNG/JPG/JPEG/WEBP/GIF.</small>
                <?php if(docValue($editDoc ?? [], 'archivoNombre')): ?>
                <div style="margin-top:8px;display:flex;align-items:center;gap:10px">
                    <span style="font-size:0.8rem;color:var(--text-2)">
                        📎 <?= htmlspecialchars(docValue($editDoc ?? [], 'archivoNombre') ?? '') ?>
                    </span>
                    <a href="http://localhost:8080/archivos/descargar/<?= docValue($editDoc ?? [], 'id') ?>"
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

const archivoInput = document.getElementById('archivoInput');
const fechaDocInput = document.getElementById('fechaDocInput');
if (archivoInput && fechaDocInput) {
    archivoInput.addEventListener('change', () => {
        const archivo = archivoInput.files && archivoInput.files[0];
        if (!archivo || fechaDocInput.dataset.manual === '1') return;

        if (archivo.lastModified) {
            const fecha = new Date(archivo.lastModified);
            const yyyy = fecha.getFullYear();
            const mm = String(fecha.getMonth() + 1).padStart(2, '0');
            const dd = String(fecha.getDate()).padStart(2, '0');
            fechaDocInput.value = `${yyyy}-${mm}-${dd}`;
        }
    });

    fechaDocInput.addEventListener('input', () => {
        fechaDocInput.dataset.manual = '1';
    });
}
</script>
</body>
</html>
