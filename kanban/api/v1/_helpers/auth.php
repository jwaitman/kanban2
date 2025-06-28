<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/database.php';

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

function get_user_from_token($db) {
    $headers = getallheaders();
    if (!isset($headers['Authorization'])) {
        return null;
    }

    $auth_header = $headers['Authorization'];
    if (strpos($auth_header, 'Bearer ') !== 0) {
        return null;
    }

    $token = substr($auth_header, 7);

    try {
        $decoded = JWT::decode($token, new Key(JWT_SECRET, 'HS256'));
        $user_id = $decoded->sub;

        $stmt = $db->prepare("SELECT id, username, role FROM users WHERE id = ?");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    } catch (Exception $e) {
        // This includes token expiration, invalid signature, etc.
        error_log("JWT Validation Error: " . $e->getMessage());
        return null;
    }
}

function get_user_or_exit($db) {
    $user = get_user_from_token($db);
    if (!$user) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Unauthorized']);
        exit();
    }
    return $user;
}
