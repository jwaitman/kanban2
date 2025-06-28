<?php
// api/v1/auth/index.php
require_once __DIR__ . '/../_helpers/database.php';
require_once __DIR__ . '/../_helpers/response.php';
require_once __DIR__ . '/../_helpers/auth.php';
require_once __DIR__ . '/../_helpers/audit.php';

// Required for frontend (CORS)
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

$db = connect_to_database();
$request_method = $_SERVER["REQUEST_METHOD"];

// Simple router to handle /login, /refresh, /logout
$request_uri = trim($_SERVER['REQUEST_URI'], '/');
$path_parts = explode('/', $request_uri);
$action = 'login'; // default action
if (isset($path_parts[3]) && in_array($path_parts[3], ['login', 'refresh', 'logout'])) {
    $action = $path_parts[3];
}

switch ($action) {
    case 'login':
        handle_login($db);
        break;
    case 'refresh':
        handle_refresh($db);
        break;
    case 'logout':
        handle_logout();
        break;
    default:
        send_json_response(['error' => 'Invalid auth action'], 404);
        break;
}

function handle_login($db) {
    if ($_SERVER["REQUEST_METHOD"] !== 'POST') {
        send_json_response(['error' => 'Invalid request method for login'], 405);
    }

    $data = json_decode(file_get_contents("php://input"));

    if (!isset($data->username) || !isset($data->password)) {
        send_json_response(['error' => 'Username and password are required'], 400);
    }

    // Use prepared statements to prevent SQL injection
    $stmt = $db->prepare("SELECT id, username, password_hash, role FROM users WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $data->username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if ($user && verify_password($data->password, $user['password_hash'])) {
        // Password is correct, update last login time
        $update_stmt = $db->prepare("UPDATE users SET last_login_at = CURRENT_TIMESTAMP WHERE id = ?");
        $update_stmt->bind_param("i", $user['id']);
        $update_stmt->execute();
        $update_stmt->close();

        // Log the successful login
        log_audit($db, $user['id'], 'login_success');

        // Generate tokens
        $access_token = generate_jwt($user['id'], $user['username'], $user['role']);
        $refresh_token = generate_refresh_token($user['id']);
        
        // Set refresh token in a secure cookie
        set_refresh_token_cookie($refresh_token);

        send_json_response([
            'message' => 'Login successful',
            'access_token' => $access_token
        ]);
    } else {
        // Log the failed login attempt
        $userId = $user ? $user['id'] : null;
        log_audit($db, $userId, 'login_failure', null, null, json_encode(['username' => $data->username]));
        send_json_response(['error' => 'Invalid credentials'], 401);
    }
}

function handle_refresh($db) {
    if (!isset($_COOKIE['refresh_token'])) {
        send_json_response(['error' => 'Refresh token not found'], 401);
    }

    $refresh_token = $_COOKIE['refresh_token'];
    $decoded = validate_jwt($refresh_token);

    if (!$decoded) {
        send_json_response(['error' => 'Invalid refresh token'], 401);
    }

    $user_id = $decoded->data->user_id;

    // Fetch user details again to ensure they still exist and are active
    $stmt = $db->prepare("SELECT id, username, role FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if ($user) {
        $new_access_token = generate_jwt($user['id'], $user['username'], $user['role']);
        send_json_response(['access_token' => $new_access_token]);
    } else {
        send_json_response(['error' => 'User not found'], 401);
    }
}

function handle_logout() {
    // Clear the refresh token cookie
    setcookie('refresh_token', '', [
        'expires' => time() - 3600, // set to a past time
        'httponly' => true,
        'secure' => APP_ENV === 'production',
        'samesite' => 'Strict'
    ]);
    send_json_response(['message' => 'Logout successful']);
}
