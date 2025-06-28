<?php
// api/v1/users/index.php
require_once __DIR__ . '/../_helpers/cors.php'; // MUST be the first line

require_once __DIR__ . '/../_helpers/database.php';
require_once __DIR__ . '/../_helpers/response.php';
require_once __DIR__ . '/../_helpers/auth.php';

$db = connect_to_database();
$request_method = $_SERVER["REQUEST_METHOD"];

// Extract user ID from the URL, if present
$parts = explode('/', $_SERVER['REQUEST_URI']);
$user_id = end($parts) == 'users' ? null : (int)end($parts);

switch ($request_method) {
    case 'GET':
        if ($user_id) {
            handle_get_user($db, $user_id);
        } else {
            handle_get_all_users($db);
        }
        break;
    case 'POST':
        handle_create_user($db);
        break;
    case 'PUT':
        handle_update_user($db, $user_id);
        break;
    case 'DELETE':
        handle_delete_user($db, $user_id);
        break;
    default:
        send_json_response(['error' => 'Invalid request method'], 405);
        break;
}

function handle_get_all_users($db) {
    $current_user = require_auth(['admin']); // Only admins can see all users
    $stmt = $db->prepare("SELECT id, username, email, role, created_at, last_login_at FROM users");
    $stmt->execute();
    $result = $stmt->get_result();
    $users = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    send_json_response($users);
}

function handle_get_user($db, $user_id) {
    $current_user = require_auth(); // Any authenticated user can try
    if ($current_user->role !== 'admin' && $current_user->user_id !== $user_id) {
        send_json_response(['error' => 'Forbidden: You can only view your own profile'], 403);
    }

    $stmt = $db->prepare("SELECT id, username, email, role, created_at, last_login_at FROM users WHERE id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if ($user) {
        send_json_response($user);
    } else {
        send_json_response(['error' => 'User not found'], 404);
    }
}

function handle_create_user($db) {
    $current_user = require_auth(['admin']); // Only admins can create users
    $data = json_decode(file_get_contents('php://input'));

    // Basic validation
    if (!isset($data->username) || !isset($data->password) || !isset($data->email)) {
        send_json_response(['error' => 'Missing required fields: username, password, email'], 400);
    }

    $password_hash = hash_password($data->password); // Use the secure helper
    $role = $data->role ?? 'user'; // Default to 'user' role if not provided

    $stmt = $db->prepare("INSERT INTO users (username, password_hash, email, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('ssss', $data->username, $password_hash, $data->email, $role);
    
    if ($stmt->execute()) {
        $new_user_id = $stmt->insert_id;
        send_json_response(['message' => 'User created successfully', 'user_id' => $new_user_id], 201);
    } else {
        // Check for duplicate entry
        if ($stmt->errno == 1062) { 
            send_json_response(['error' => 'Username or email already exists'], 409);
        } else {
            send_json_response(['error' => 'Failed to create user', 'details' => $stmt->error], 500);
        }
    }
    $stmt->close();
}

function handle_update_user($db, $user_id) {
    if (!$user_id) {
        send_json_response(['error' => 'User ID is required'], 400);
    }
    $current_user = require_auth();
    if ($current_user->role !== 'admin' && $current_user->user_id !== $user_id) {
        send_json_response(['error' => 'Forbidden: You can only update your own profile'], 403);
    }

    $data = json_decode(file_get_contents('php://input'));
    // Logic to update user details (e.g., email, role). Password changes should be a separate endpoint.
    // This is a placeholder for a more complete implementation.
    send_json_response(['message' => 'User update functionality not fully implemented']);
}

function handle_delete_user($db, $user_id) {
    if (!$user_id) {
        send_json_response(['error' => 'User ID is required'], 400);
    }
    $current_user = require_auth(['admin']); // Only admins can delete users

    // Prevent admin from deleting themselves
    if ($current_user->user_id === $user_id) {
        send_json_response(['error' => 'Admins cannot delete their own account'], 400);
    }

    $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param('i', $user_id);
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            send_json_response(['message' => 'User deleted successfully']);
        } else {
            send_json_response(['error' => 'User not found'], 404);
        }
    } else {
        send_json_response(['error' => 'Failed to delete user', 'details' => $stmt->error], 500);
    }
    $stmt->close();
}
