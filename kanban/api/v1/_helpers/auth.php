<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/app.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

function generate_jwt($user_id) {
    $issued_at = time();
    $expiration_time = $issued_at + JWT_EXP;
    $payload = [
        'iat' => $issued_at,
        'exp' => $expiration_time,
        'sub' => $user_id
    ];
    return JWT::encode($payload, JWT_SECRET, 'HS256');
}

function generate_refresh_token($user_id) {
    $issued_at = time();
    $expiration_time = $issued_at + REFRESH_TOKEN_EXP;
    $payload = [
        'iat' => $issued_at,
        'exp' => $expiration_time,
        'sub' => $user_id
    ];
    return JWT::encode($payload, JWT_SECRET, 'HS256');
}

function send_json_response($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
