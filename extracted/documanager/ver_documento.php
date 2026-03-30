<?php
require_once 'includes/config.php';
requireLogin();

$docId = (int)($_GET['id'] ?? 0);
if ($docId <= 0) {
    header('Location: /documanager/documentos.php');
    exit;
}

$res = api('GET', "/documentos/$docId");
$documento = $res['data'] ?? null;
if (!is_array($documento) || empty(docValue($documento, 'id'))) {
    header('Location: /documanager/documentos.php?msg=' . urlencode('No se encontró el documento solicitado.'));
    exit;
}

$archivoNombre = docValue($documento, 'archivoNombre');
$archivoTipo = strtolower((string)(docValue($documento, 'archivoTipo') ?? ''));
$archivoUrl = 'http://localhost:8080/archivos/descargar/' . docValue($documento, 'id');
$extension = strtolower(pathinfo((string)$archivoNombre, PATHINFO_EXTENSION));

$esPdf = $archivoTipo === 'application/pdf' || $extension === 'pdf';
$esImagen = str_starts_with($archivoTipo, 'image/') || in_array($extension, ['png','jpg','jpeg','gif','webp'], true);
$esOffice = in_array($extension, ['doc','docx','xls','xlsx','xlsm','csv'], true);
$archivoUrlEncoded = rawurlencode($archivoUrl);
$officeViewerUrl = 'https://view.officeapps.live.com/op/embed.aspx?src=' . $archivoUrlEncoded;

$host = strtolower((string)parse_url($archivoUrl, PHP_URL_HOST));
$esHostLocal = in_array($host, ['localhost', '127.0.0.1', '::1'], true);
$usarOfficeEmbed = $esOffice && !$esHostLocal;

$cats = api('GET', '/categorias');
$nombreCategoria = '-';
foreach (($cats['data'] ?? []) as $cat) {
    if ((string)fieldValue($cat, 'id') === (string)(docValue($documento, 'categoria') ?? '')) {
        $nombreCategoria = fieldValue($cat, 'nombre') ?? '-';
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver documento — DocuManager</title>
    <link rel="stylesheet" href="<?= assetUrl('/documanager/css/style.css') ?>">
    <style>
        .detail-grid { display:grid; grid-template-columns: 360px 1fr; gap:1.25rem; }
        .detail-meta { display:grid; gap:0.85rem; }
        .meta-item { padding:0.9rem 1rem; border:1px solid var(--border); border-radius:var(--radius-sm); background:var(--bg-surface); }
        .meta-item span { display:block; color:var(--text-3); font-size:0.74rem; text-transform:uppercase; letter-spacing:0.06em; margin-bottom:4px; }
        .meta-item strong { font-size:0.92rem; }
        .preview-box { min-height:72vh; display:flex; align-items:center; justify-content:center; background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); overflow:hidden; }
        .preview-frame { width:100%; height:72vh; border:none; background:#111; }
        .preview-image { display:block; width:100%; max-height:72vh; object-fit:contain; background:#111; }
        .preview-empty { padding:2rem; text-align:center; color:var(--text-2); }
        @media(max-width:980px){ .detail-grid { grid-template-columns:1fr; } }
    </style>
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<div class="page">
<div class="container">

    <div class="page-header">
        <div>
            <h1>Ver detalles del documento</h1>
            <p>Consulta la información del archivo y visualízalo directamente cuando sea compatible.</p>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <a href="/documanager/documentos.php" class="btn btn-secondary">← Volver</a>
            <?php if($archivoNombre): ?>
            <a href="<?= htmlspecialchars($archivoUrl) ?>" class="btn btn-success" target="_blank">⬇️ Descargar archivo</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="detail-grid">
        <div class="card">
            <div class="detail-meta">
                <div class="meta-item"><span>Título</span><strong><?= htmlspecialchars(docValue($documento, 'titulo') ?? '-') ?></strong></div>
                <div class="meta-item"><span>Tipo</span><strong><?= htmlspecialchars(docValue($documento, 'tipo') ?? '-') ?></strong></div>
                <div class="meta-item"><span>Estado</span><strong><?= htmlspecialchars(docValue($documento, 'estado') ?? '-') ?></strong></div>
                <div class="meta-item"><span>Categoría</span><strong><?= htmlspecialchars($nombreCategoria) ?></strong></div>
                <div class="meta-item"><span>Cliente</span><strong><?= htmlspecialchars(docValue($documento, 'cliente') ?? '-') ?></strong></div>
                <div class="meta-item"><span>Fecha</span><strong><?= ($fecha = docValue($documento, 'fecha')) ? date('d/m/Y', strtotime($fecha)) : '-' ?></strong></div>
                <div class="meta-item"><span>Versión</span><strong><?= htmlspecialchars(docValue($documento, 'version') ?? '1.0') ?></strong></div>
                <div class="meta-item"><span>Archivo</span><strong><?= htmlspecialchars($archivoNombre ?: 'Sin archivo adjunto') ?></strong></div>
                <div class="meta-item"><span>Descripción</span><strong><?= nl2br(htmlspecialchars(docValue($documento, 'descripcion') ?? 'Sin descripción')) ?></strong></div>
            </div>
        </div>

        <div class="preview-box">
            <?php if(!$archivoNombre): ?>
                <div class="preview-empty">
                    <h3 style="margin-bottom:8px">Sin archivo adjunto</h3>
                    <p>Este registro no tiene un archivo para mostrar en pantalla.</p>
                </div>
            <?php elseif($esPdf): ?>
                <iframe class="preview-frame" src="<?= htmlspecialchars($archivoUrl) ?>#toolbar=1"></iframe>
            <?php elseif($esImagen): ?>
                <img class="preview-image" src="<?= htmlspecialchars($archivoUrl) ?>" alt="Vista previa del documento">
            <?php elseif($usarOfficeEmbed): ?>
                <iframe class="preview-frame" src="<?= htmlspecialchars($officeViewerUrl) ?>"></iframe>
            <?php elseif($esOffice): ?>
                <div class="preview-empty" id="officePreviewContainer"
                     data-file-url="<?= htmlspecialchars($archivoUrl) ?>"
                     data-extension="<?= htmlspecialchars($extension) ?>">
                    <h3 style="margin-bottom:8px">Cargando vista previa…</h3>
                    <p>Estamos procesando el archivo para mostrarlo dentro del sistema.</p>
                </div>
            <?php else: ?>
                <div class="preview-empty">
                    <h3 style="margin-bottom:8px">Vista previa no disponible</h3>
                    <p>Este tipo de archivo no se puede incrustar directamente en el navegador.</p>
                    <p style="margin-top:10px">Para Word/Excel usamos visor web; si tu servidor es local (localhost), puede que no cargue y debas descargar el archivo.</p>
                    <a href="<?= htmlspecialchars($archivoUrl) ?>" class="btn btn-success" target="_blank" style="margin-top:12px">Descargar archivo</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>
</div>

<?php if($esOffice && $esHostLocal): ?>
<script src="https://cdn.jsdelivr.net/npm/mammoth@1.8.0/mammoth.browser.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script>
(async function() {
    const box = document.getElementById('officePreviewContainer');
    if (!box) return;

    const fileUrl = box.dataset.fileUrl;
    const ext = (box.dataset.extension || '').toLowerCase();

    const setError = (title, msg) => {
        box.innerHTML = `
            <h3 style="margin-bottom:8px">${title}</h3>
            <p>${msg}</p>
            <a href="${fileUrl}" class="btn btn-success" target="_blank" style="margin-top:12px">Descargar archivo</a>
        `;
    };

    try {
        const res = await fetch(fileUrl);
        if (!res.ok) {
            setError('No se pudo cargar el archivo', 'El archivo no respondió correctamente desde el servidor.');
            return;
        }

        if (ext === 'docx') {
            const arrayBuffer = await res.arrayBuffer();
            const result = await mammoth.convertToHtml({ arrayBuffer });
            box.innerHTML = `<div style="text-align:left;max-width:900px;max-height:68vh;overflow:auto;background:#fff;color:#111;padding:1.25rem;border-radius:10px">${result.value}</div>`;
            return;
        }

        if (ext === 'xlsx' || ext === 'xls' || ext === 'csv') {
            const arrayBuffer = await res.arrayBuffer();
            const workbook = XLSX.read(arrayBuffer, { type: 'array' });
            const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
            if (!firstSheet) {
                setError('No se encontró contenido', 'No fue posible leer hojas en el archivo de Excel.');
                return;
            }
            const html = XLSX.utils.sheet_to_html(firstSheet);
            box.innerHTML = `<div style="text-align:left;max-width:100%;max-height:68vh;overflow:auto;background:#fff;color:#111;padding:1rem;border-radius:10px">${html}</div>`;
            return;
        }

        setError('Vista previa no disponible', 'Este formato requiere descarga para abrirse correctamente en tu equipo.');
    } catch (e) {
        setError('Error de visualización', 'No fue posible generar la vista previa local. Puedes descargar el archivo.');
    }
})();
</script>
<?php endif; ?>

</body>
</html>
