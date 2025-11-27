<?php
// utils.php
$config = require __DIR__ . '/config.php';

function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

function getJsonInput() {
    $raw = file_get_contents("php://input");
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function allowCors() {
    // sesuaikan origin di production
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, X-API-KEY");
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}

function requireApiKey() {
    global $config;
    $key = $config['api_key'] ?? null;
    if (!$key) return; // jika kosong, non-aktifkan check
    $headers = getallheaders();
    $provided = $headers['X-API-KEY'] ?? $headers['x-api-key'] ?? null;
    if (!$provided || $provided !== $key) {
        jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
    }
}
