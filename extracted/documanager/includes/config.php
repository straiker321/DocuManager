<?php
define('API_URL', 'http://localhost:8080');
session_start();

function api($method, $endpoint, $data = null) {
    $url = API_URL . $endpoint;
    $ch  = curl_init($url);
    $headers = ['Accept: application/json'];

    if ($data !== null) {
        $headers[] = 'Content-Type: application/json';
    }
    if (isset($_SESSION['token'])) {
        $headers[] = 'Authorization: Bearer ' . $_SESSION['token'];
    }

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    if ($data !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) {
        return [
            'code' => 0,
            'data' => [
                'message' => 'No se pudo conectar con el microservicio PHP/Java. Verifica que el backend esté corriendo en ' . API_URL . '.',
                'detail' => $curlError,
            ],
        ];
    }

    $decoded = json_decode($response, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        return ['code' => $httpCode, 'data' => $decoded];
    }

    return [
        'code' => $httpCode,
        'data' => [
            'message' => 'El backend respondió con un formato inválido.',
            'raw' => $response,
        ],
    ];
}


function fieldValue(array $row, string ...$keys) {
    foreach ($keys as $key) {
        if (array_key_exists($key, $row) && $row[$key] !== null) {
            return $row[$key];
        }
    }
    return null;
}

function docValue(array $doc, string $field) {
    return match($field) {
        'categoria' => (function() use ($doc) {
            $categoria = fieldValue($doc, 'categoriaId', 'categoria_id', 'categoria');
            if (is_array($categoria)) {
                return fieldValue($categoria, 'id', 'categoria_id');
            }
            return $categoria;
        })(),
        'fecha' => fieldValue($doc, 'fechaDoc', 'fecha_doc'),
        'archivoNombre' => fieldValue($doc, 'archivoNombre', 'archivo_nombre'),
        'archivoRuta' => fieldValue($doc, 'archivoRuta', 'archivo_ruta'),
        'archivoTipo' => fieldValue($doc, 'archivoTipo', 'archivo_tipo'),
        'archivoTamano' => fieldValue($doc, 'archivoTamano', 'archivo_tamano'),
        default => fieldValue($doc, $field, strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $field)))
    };
}

function sendNoCacheHeaders() {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: Sat, 01 Jan 2000 00:00:00 GMT');
}

function requireLogin(bool $sendHeaders = true) {
    if ($sendHeaders) {
        sendNoCacheHeaders();
    }
    if (!isset($_SESSION['token'])) {
        header('Location: /documanager/index.php');
        exit;
    }
}

function assetUrl(string $webPath): string {
    $baseDir = dirname(__DIR__);
    $localPath = $baseDir . str_replace('/documanager', '', $webPath);
    if (is_file($localPath)) {
        return $webPath . '?v=' . filemtime($localPath);
    }
    return $webPath;
}

function currentRole(): string {
    $rawRole = $_SESSION['rol'] ?? '';
    $normalizedRole = strtoupper(trim((string)$rawRole));
    $withoutPrefix = preg_replace('/^ROLE_/', '', $normalizedRole);
    return is_string($withoutPrefix) ? $withoutPrefix : $normalizedRole;
}

function isAdmin()  {
    $role = currentRole();
    return $role === 'ADMIN' || str_contains($role, 'ADMIN');
}

function isEditor() {
    $role = currentRole();
    return $role === 'EDITOR' || str_contains($role, 'EDITOR') || isAdmin();
}

function canManageDocuments(): bool {
    $role = currentRole();
    if (isAdmin() || isEditor()) return true;
    if ($role === '') return false;
    return !str_contains($role, 'VIEWER') && !str_contains($role, 'LECTOR');
}

function isPresidente() {
    $role = currentRole();
    if ($role === 'PRESIDENTE' || str_contains($role, 'PRESIDENTE') || str_contains($role, 'PRESIDENT')) {
        return true;
    }

    $nombre = strtolower(trim((string)($_SESSION['nombre'] ?? '')));
    $email = strtolower(trim((string)($_SESSION['email'] ?? '')));
    if (str_contains($nombre, 'president') || str_contains($email, 'president')) {
        return true;
    }

    return !empty($_SESSION['es_presidente']);
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: /documanager/dashboard.php');
        exit;
    }
}
?>
