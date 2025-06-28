<?php
require_once __DIR__ . '/../_helpers/auth.php';

function handle_login($db) {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['email']) || !isset($data['password'])) {
        send_json_response(['error' => 'Email and password are required'], 400);
    }

    $stmt = $db->prepare("SELECT id, password_hash FROM users WHERE email = ?");
    $stmt->bind_param('s', $data['email']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($data['password'], $user['password_hash'])) {
        $jwt = generate_jwt($user['id']);
        $refresh_token = generate_refresh_token($user['id']);

        setcookie('refresh_token', $refresh_token, [
            'expires' => time() + REFRESH_TOKEN_EXP,
            'httponly' => true,
            'secure' => APP_ENV === 'production',
            'samesite' => 'Strict'
        ]);

        send_json_response(['access_token' => $jwt]);
    } else {
        send_json_response(['error' => 'Invalid credentials'], 401);
    }
}

function handle_refresh_token($db) {
    if (!isset($_COOKIE['refresh_token'])) {
        send_json_response(['error' => 'Refresh token not found'], 401);
    }

    try {
        $decoded = JWT::decode($_COOKIE['refresh_token'], new Key(JWT_SECRET, 'HS256'));
        $user_id = $decoded->sub;

        $stmt = $db->prepare("SELECT id FROM users WHERE id = ?");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $jwt = generate_jwt($user_id);
            send_json_response(['access_token' => $jwt]);
        } else {
            send_json_response(['error' => 'Invalid refresh token'], 401);
        }
    } catch (Exception $e) {
        send_json_response(['error' => 'Invalid refresh token'], 401);
    }
}

function handle_logout() {
    setcookie('refresh_token', '', [
        'expires' => time() - 3600,
        'httponly' => true,
        'secure' => APP_ENV === 'production',
        'samesite' => 'Strict'
    ]);
    send_json_response(['message' => 'Logged out successfully']);
}
