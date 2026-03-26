<?php
require_once 'includes/config.php';
requireLogin();

// Filtros
$estadoFil  = $_GET['estado']    ?? '';
$tipoFil    = $_GET['tipo']      ?? '';
$clienteFil = $_GET['cliente']   ?? '';

// Construir endpoint
$params = [];
if ($estadoFil)  $params['estado']  = $estadoFil;
if ($tipoFil)    $params['tipo']    = $tipoFil;
if ($clienteFil) $params['cliente'] = $clienteFil;

$endpoint   = '/documentos' . (!empty($params) ? '?' . http_build_query($params) : '');
$res        = api('GET', $endpoint);
$documentos = $res['data'] ?? [];

$cats    = api('GET', '/categorias');
$catMap  = [];
foreach (($cats['data'] ?? []) as $c) {
    $catMap[fieldValue($c, 'id')] = fieldValue($c, 'nombre');
}

// URL de descarga Java
$urlExcel = 'http://localhost:8080/reportes/excel';
if (!empty($params)) $urlExcel .= '?' . http_build_query($params);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exportar a Excel — DocuManager</title>
    <link rel="stylesheet" href="/documanager/css/style.css">
    <style>
        .export-header {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 1.5rem 2rem;
            margin-bottom: 1.5rem;
            display: flex; align-items: center;
            justify-content: space-between;
            background-image: radial-gradient(ellipse at right,
                rgba(99,102,241,0.06) 0%, transparent 60%);
        }
        .export-info h2  { font-size: 1.3rem; margin-bottom: 4px; }
        .export-info p   { color: var(--text-2); font-size: 0.85rem; }
        .btn-excel {
            display: inline-flex; align-items: center; gap: 10px;
            padding: 12px 24px;
            background: #10b981; color: #fff;
            border-radius: var(--radius-sm);
            font-family: 'Syne', sans-serif;
            font-size: 0.95rem; font-weight: 700;
            border: none; cursor: pointer;
            transition: all 0.2s; text-decoration: none;
        }
        .btn-excel:hover {
            background: #059669;
            box-shadow: 0 0 20px rgba(16,185,129,0.3);
        }
        .preview-info {
            display: flex; gap: 1rem; margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }
        .info-chip {
            display: flex; align-items: center; gap: 6px;
            padding: 6px 12px;
            background: var(--bg-card); border: 1px solid var(--border);
            border-radius: 20px; font-size: 0.8rem; color: var(--text-2);
        }
        .info-chip strong { color: var(--text); }

        /* Filtros */
        .filter-form {
            background: var(--bg-card); border: 1px solid var(--border);
            border-radius: var(--radius); padding: 1.25rem;
            margin-bottom: 1.5rem;
        }
        .filter-form h3 {
            font-size: 0.85rem; margin-bottom: 1rem;
            color: var(--text-2);
        }

        /* Tabla previa */
        .preview-badge {
            display: inline-block;
            padding: 3px 8px; border-radius: 4px;
            font-size: 0.75rem; font-weight: 600;
            background: var(--accent-soft); color: var(--accent);
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<div class="page">
<div class="container">

    <!-- Encabezado con botón de descarga -->
    <div class="export-header">
        <div class="export-info">
            <h2>📊 Exportar documentos a Excel</h2>
            <p>Vista previa del contenido que se exportará. Usa los filtros para ajustar.</p>
        </div>
        <a href="<?= htmlspecialchars($urlExcel) ?>"
           class="btn-excel" id="btnDescargar">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                <polyline points="7 10 12 15 17 10"/>
                <line x1="12" y1="15" x2="12" y2="3"/>
            </svg>
            Descargar Excel
        </a>
    </div>

    <!-- Chips de info -->
    <div class="preview-info">
        <div class="info-chip">
            📄 <strong><?= count($documentos) ?></strong> documentos
        </div>
        <div class="info-chip">
            📅 Generado el <strong><?= date('d/m/Y H:i') ?></strong>
        </div>
        <div class="info-chip">
            👤 Por <strong><?= htmlspecialchars($_SESSION['nombre']) ?></strong>
        </div>
        <?php if($estadoFil): ?>
        <div class="info-chip">
            Estado: <strong><?= htmlspecialchars($estadoFil) ?></strong>
        </div>
        <?php endif; ?>
        <?php if($tipoFil): ?>
        <div class="info-chip">
            Tipo: <strong><?= htmlspecialchars($tipoFil) ?></strong>
        </div>
        <?php endif; ?>
    </div>

    <!-- Filtros -->
    <div class="filter-form">
        <h3>🔧 Filtrar antes de exportar</h3>
        <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
            <div class="form-group" style="margin:0;flex:1;min-width:150px">
                <label>Estado</label>
                <select name="estado" class="form-control">
                    <option value="">Todos</option>
                    <?php foreach(['BORRADOR','PUBLICADO','ARCHIVADO'] as $e): ?>
                    <option value="<?=$e?>" <?= $estadoFil===$e?'selected':'' ?>><?=$e?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin:0;flex:1;min-width:150px">
                <label>Tipo</label>
                <select name="tipo" class="form-control">
                    <option value="">Todos</option>
                    <?php foreach(['CONTRATO','FACTURA','REPORTE','FORMULARIO','MANUAL','PDF_ESCANEADO','ACUERDO','OTRO'] as $t): ?>
                    <option value="<?=$t?>" <?= $tipoFil===$t?'selected':'' ?>><?=$t?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin:0;flex:1;min-width:150px">
                <label>Cliente</label>
                <input type="text" name="cliente" class="form-control"
                       placeholder="Nombre del cliente..."
                       value="<?= htmlspecialchars($clienteFil) ?>">
            </div>
            <button type="submit" class="btn btn-primary">Aplicar filtro</button>
            <a href="/documanager/exportar.php" class="btn btn-secondary">Limpiar</a>
        </form>
    </div>

    <!-- Vista previa de la tabla -->
    <div class="card">
        <div class="preview-badge">Vista previa — así se verá en el Excel</div>

        <?php if(empty($documentos)): ?>
        <div class="empty">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="1">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
            </svg>
            <p>No hay documentos con esos filtros</p>
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
                        <th>Categoría</th>
                        <th>Fecha</th>
                        <th>Confidencial</th>
                        <th>Vistas</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach($documentos as $d): ?>
                <tr>
                    <td style="color:var(--text-3)"><?= docValue($d, 'id') ?></td>
                    <td>
                        <strong><?= htmlspecialchars(docValue($d, 'titulo') ?? '') ?></strong>
                        <?php if((bool)(docValue($d, 'confidencial') ?? false)): ?>
                        <span style="color:var(--warning)"> 🔒</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars(docValue($d, 'tipo') ?? '-') ?></td>
                    <td>
                        <span class="badge badge-<?= strtolower(docValue($d, 'estado') ?? 'borrador') ?>">
                            <?= docValue($d, 'estado') ?? 'BORRADOR' ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars(docValue($d, 'cliente') ?? '-') ?></td>
                    <td><?= htmlspecialchars($catMap[docValue($d, 'categoria') ?? 0] ?? '-') ?></td>
                    <td><?= ($fechaDocFila = docValue($d, 'fecha')) ? date('d/m/Y', strtotime($fechaDocFila)) : '-' ?></td>
                    <td><?= (docValue($d, 'confidencial') ?? false) ? '🔒 Sí' : 'No' ?></td>
                    <td><?= (int)(docValue($d, 'vistas') ?? 0) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="9" style="text-align:right;font-weight:600;
                            color:var(--text);padding:12px 14px">
                            Total: <?= count($documentos) ?> documentos
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <div style="text-align:center;margin-top:1.5rem">
        <a href="<?= htmlspecialchars($urlExcel) ?>" class="btn-excel">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                <polyline points="7 10 12 15 17 10"/>
                <line x1="12" y1="15" x2="12" y2="3"/>
            </svg>
            Descargar Excel (<?= count($documentos) ?> documentos)
        </a>
    </div>

</div>
</div>
</body>
</html>
