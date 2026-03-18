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

function requireLogin() {
    if (!isset($_SESSION['token'])) {
        header('Location: /documanager/index.php');
        exit;
    }
}

function isAdmin()  { return isset($_SESSION['rol']) && $_SESSION['rol'] === 'ADMIN'; }
function isEditor() { return isset($_SESSION['rol']) && in_array($_SESSION['rol'], ['ADMIN','EDITOR']); }

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: /documanager/dashboard.php');
        exit;
    }
}
?>
