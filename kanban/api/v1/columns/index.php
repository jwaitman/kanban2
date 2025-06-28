<?php
// api/v1/columns/index.php

require_once __DIR__ . '/../_helpers/cors.php';
require_once __DIR__ . '/../_helpers/database.php';
require_once __DIR__ . '/../_helpers/response.php';
require_once __DIR__ . '/../_helpers/auth.php';

$db = connect_to_database();
$request_method = $_SERVER["REQUEST_METHOD"];
$current_user = require_auth();

// For simplicity, this endpoint will handle all column actions based on query params and method.
// A more RESTful approach might have /boards/{id}/columns, but this is simpler for now.

switch ($request_method) {
    case 'GET':
        handle_get_columns($db, $current_user);
        break;
    case 'POST':
        handle_create_column($db, $current_user);
        break;
    case 'PUT':
        handle_update_column($db, $current_user);
        break;
    case 'DELETE':
        handle_delete_column($db, $current_user);
        break;
    default:
        send_json_response(['error' => 'Invalid request method'], 405);
        break;
}

function handle_get_columns($db, $current_user) {
    if (!isset($_GET['board_id'])) {
        send_json_response(['error' => 'Board ID is required'], 400);
    }
    $board_id = (int)$_GET['board_id'];

    // Check if user has access to this board
    $stmt = $db->prepare("SELECT owner_id FROM boards WHERE id = ?");
    $stmt->bind_param('i', $board_id);
    $stmt->execute();
    $board = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$board) {
        send_json_response(['error' => 'Board not found'], 404);
    }

    if ($board['owner_id'] !== $current_user->user_id && !in_array($current_user->role, ['admin', 'manager'])) {
        send_json_response(['error' => 'Forbidden'], 403);
    }

    // Fetch columns and their tasks
    $stmt = $db->prepare("SELECT * FROM columns WHERE board_id = ? ORDER BY column_order ASC");
    $stmt->bind_param('i', $board_id);
    $stmt->execute();
    $columns = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    foreach ($columns as &$column) {
        $stmt = $db->prepare("SELECT * FROM tasks WHERE column_id = ? ORDER BY task_order ASC");
        $stmt->bind_param('i', $column['id']);
        $stmt->execute();
        $tasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        $column['tasks'] = $tasks;
    }

    send_json_response($columns);
}

function handle_create_column($db, $current_user) {
    $data = json_decode(file_get_contents('php://input'));

    if (!isset($data->board_id) || !isset($data->name)) {
        send_json_response(['error' => 'Board ID and column name are required'], 400);
    }

    // Permission check (similar to get)
    $board_id = (int)$data->board_id;
    $stmt = $db->prepare("SELECT owner_id FROM boards WHERE id = ?");
    $stmt->bind_param('i', $board_id);
    $stmt->execute();
    $board = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$board || ($board['owner_id'] !== $current_user->user_id && !in_array($current_user->role, ['admin', 'manager']))) {
        send_json_response(['error' => 'Forbidden'], 403);
    }
    
    // Get max column_order and increment
    $stmt = $db->prepare("SELECT MAX(column_order) as max_order FROM columns WHERE board_id = ?");
    $stmt->bind_param('i', $board_id);
    $stmt->execute();
    $max_order = $stmt->get_result()->fetch_assoc()['max_order'] ?? 0;
    $stmt->close();
    $new_order = $max_order + 1;

    $stmt = $db->prepare("INSERT INTO columns (board_id, name, column_order) VALUES (?, ?, ?)");
    $stmt->bind_param('isi', $board_id, $data->name, $new_order);

    if ($stmt->execute()) {
        send_json_response(['message' => 'Column created', 'id' => $stmt->insert_id], 201);
    } else {
        send_json_response(['error' => 'Failed to create column'], 500);
    }
    $stmt->close();
}

function handle_update_column($db, $current_user) {
    $data = json_decode(file_get_contents('php://input'));

    if (!isset($data->id)) {
        send_json_response(['error' => 'Column ID is required'], 400);
    }

    $column_id = (int)$data->id;

    // Permission Check
    $stmt = $db->prepare("SELECT c.board_id, b.owner_id FROM columns c JOIN boards b ON c.board_id = b.id WHERE c.id = ?");
    $stmt->bind_param('i', $column_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$result) {
        send_json_response(['error' => 'Column not found'], 404);
    }

    if ($result['owner_id'] !== $current_user->user_id && !in_array($current_user->role, ['admin', 'manager'])) {
        send_json_response(['error' => 'Forbidden'], 403);
    }

    // Update name
    if (isset($data->name)) {
        $stmt = $db->prepare("UPDATE columns SET name = ? WHERE id = ?");
        $stmt->bind_param('si', $data->name, $column_id);
        $stmt->execute();
        $stmt->close();
    }

    send_json_response(['message' => 'Column updated successfully']);
}

function handle_delete_column($db, $current_user) {
    $data = json_decode(file_get_contents('php://input'));
    if (!isset($data->id)) {
        send_json_response(['error' => 'Column ID is required'], 400);
    }
    $column_id = (int)$data->id;

    // Permission Check (same as update)
    $stmt = $db->prepare("SELECT b.owner_id FROM columns c JOIN boards b ON c.board_id = b.id WHERE c.id = ?");
    $stmt->bind_param('i', $column_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$result) {
        send_json_response(['error' => 'Column not found'], 404);
    }

    if ($result['owner_id'] !== $current_user->user_id && !in_array($current_user->role, ['admin', 'manager'])) {
        send_json_response(['error' => 'Forbidden'], 403);
    }

    // Deleting a column will also delete all its tasks due to FOREIGN KEY ON DELETE CASCADE
    $stmt = $db->prepare("DELETE FROM columns WHERE id = ?");
    $stmt->bind_param('i', $column_id);

    if ($stmt->execute()) {
        send_json_response(['message' => 'Column deleted successfully']);
    } else {
        send_json_response(['error' => 'Failed to delete column'], 500);
    }
    $stmt->close();
}

