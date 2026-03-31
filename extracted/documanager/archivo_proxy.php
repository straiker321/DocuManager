<?php
require_once 'includes/config.php';
requireLogin();

$docId = (int)($_GET['id'] ?? 0);
if ($docId <= 0) {
    http_response_code(400);
    echo 'ID de documento inválido.';
    exit;
}

$download = isset($_GET['download']) && $_GET['download'] === '1';
$fileName = trim((string)($_GET['name'] ?? 'archivo'));
$fileName = preg_replace('/[\\r\\n\"\\\\]+/', '_', $fileName);
$fileName = $fileName !== '' ? $fileName : 'archivo';
$url = API_URL . '/archivos/descargar/' . $docId;

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . ($_SESSION['token'] ?? '')
]);

$body = curl_exec($ch);
$httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE) ?: 'application/octet-stream';
$error = curl_error($ch);
curl_close($ch);

if ($body === false || $httpCode >= 400) {
    http_response_code($httpCode > 0 ? $httpCode : 502);
    header('Content-Type: text/plain; charset=utf-8');
    echo $error ?: 'No se pudo descargar el archivo.';
    exit;
}

header('Content-Type: ' . $contentType);
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Sat, 01 Jan 2000 00:00:00 GMT');
header('X-Content-Type-Options: nosniff');
if ($download) {
    header("Content-Disposition: attachment; filename*=UTF-8''" . rawurlencode($fileName));
} else {
    header("Content-Disposition: inline; filename*=UTF-8''" . rawurlencode($fileName));
}
echo $body;
