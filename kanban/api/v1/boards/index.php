<?php
// api/v1/boards/index.php

// Note: Error logging is good for production, but can be commented out for easier debugging.
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '/var/www/html/kanban/storage/logs/php_errors.log');

require_once __DIR__ . '/../_helpers/database.php';
require_once __DIR__ . '/../_helpers/auth.php';
require_once __DIR__ . '/../_helpers/response.php';

function get_all_boards($db) {
    $stmt = $db->prepare("SELECT * FROM boards");
    $stmt->execute();
    $result = $stmt->get_result();
    $boards = $result->fetch_all(MYSQLI_ASSOC);
    send_json_response($boards);
}

function create_board($db) {
    $data = json_decode(file_get_contents('php://input'), true);
    $stmt = $db->prepare("INSERT INTO boards (name, description, owner_id) VALUES (?, ?, ?)");
    $stmt->bind_param('ssi', $data['name'], $data['description'], $data['owner_id']);
    if ($stmt->execute()) {
        send_json_response(['id' => $stmt->insert_id], 201);
    } else {
        send_json_response(['error' => 'Failed to create board'], 500);
    }
}

$db = connect_to_database();
$user = get_user_or_exit($db);

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        get_all_boards($db);
        break;
    case 'POST':
        if ($user['role'] !== 'admin' && $user['role'] !== 'manager') {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            exit();
        }
        create_board($db);
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method Not Allowed']);
        break;
}
