<?php
// api/v1/_helpers/auth.php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/response.php'; // Include for send_json_response

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// --- Constants Check ---
// Ensure constants from config/app.php are defined to avoid fatal errors.
if (!defined('JWT_SECRET')) die('JWT_SECRET is not defined. Please check config/app.php');
if (!defined('JWT_EXP')) define('JWT_EXP', 3600);
if (!defined('REFRESH_TOKEN_EXP')) define('REFRESH_TOKEN_EXP', 604800);
if (!defined('APP_URL')) die('APP_URL is not defined. Please check config/app.php');
if (!defined('APP_ENV')) define('APP_ENV', 'production');

// --- Password Security ---
function hash_password(string $password): string {
    // Use PASSWORD_DEFAULT which is guaranteed to be strong and updated in future PHP versions.
    // Argon2id is the default since PHP 7.4.
    return password_hash($password, PASSWORD_DEFAULT);
}

function verify_password(string $password, string $hash): bool {
    return password_verify($password, $hash);
}

// --- JWT Generation ---
function generate_jwt(int $user_id, string $username, string $role): string {
    $issuedAt = time();
    $expirationTime = $issuedAt + JWT_EXP;
    $payload = [
        'iat' => $issuedAt,
        'exp' => $expirationTime,
        'iss' => APP_URL, // Issuer
        'aud' => APP_URL, // Audience
        'data' => [
            'user_id' => $user_id,
            'username' => $username,
            'role' => $role
        ]
    ];

    return JWT::encode($payload, JWT_SECRET, 'HS256');
}

function generate_refresh_token(int $user_id): string {
    $issuedAt = time();
    $expirationTime = $issuedAt + REFRESH_TOKEN_EXP;
    $payload = [
        'iat' => $issuedAt,
        'exp' => $expirationTime,
        'iss' => APP_URL,
        'aud' => APP_URL,
        'data' => [
            'user_id' => $user_id
        ]
    ];
    // In a real-world scenario, you might store this token in a database
    // to allow for revocation.
    return JWT::encode($payload, JWT_SECRET, 'HS256'); 
}

function set_refresh_token_cookie(string $token): void {
    setcookie('refresh_token', $token, [
        'expires' => time() + REFRESH_TOKEN_EXP,
        'httponly' => true,
        'secure' => APP_ENV === 'production', // Use secure cookies in production
        'samesite' => 'Strict' // Or 'Lax'
    ]);
}

// --- JWT Validation & Authorization ---
function get_jwt_from_header(): ?string {
    $headers = getallheaders();
    if (isset($headers['Authorization'])) {
        $authHeader = $headers['Authorization'];
        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return $matches[1];
        }
    }
    return null;
}

function validate_jwt(?string $jwt): ?object {
    if (!$jwt) {
        return null;
    }
    try {
        return JWT::decode($jwt, new Key(JWT_SECRET, 'HS256'));
    } catch (Exception $e) {
        // Log error: $e->getMessage()
        return null;
    }
}

function get_current_user_data(): ?object {
    $jwt = get_jwt_from_header();
    $decoded = validate_jwt($jwt);
    return $decoded->data ?? null;
}

function require_auth(array $allowed_roles = []): object {
    $user_data = get_current_user_data();

    if (!$user_data) {
        send_json_response(['error' => 'Authentication required'], 401);
    }

    if (!empty($allowed_roles) && !in_array($user_data->role, $allowed_roles)) {
        send_json_response(['error' => 'Forbidden: Insufficient permissions'], 403);
    }

    return $user_data;
}
