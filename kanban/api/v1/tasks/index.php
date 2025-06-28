<?php
// api/v1/tasks/index.php

require_once __DIR__ . '/../_helpers/database.php';
require_once __DIR__ . '/../_helpers/response.php';
require_once __DIR__ . '/../_helpers/auth.php';

// Set common headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

$db = connect_to_database();
$request_method = $_SERVER["REQUEST_METHOD"];
$current_user = require_auth();

// Extract task ID from the URL, if present
$parts = explode('/', $_SERVER['REQUEST_URI']);
$task_id = is_numeric(end($parts)) ? (int)end($parts) : null;

switch ($request_method) {
    case 'GET':
        // GET is handled by columns endpoint for now to fetch tasks with columns
        send_json_response(['error' => 'Not supported. Use /api/v1/columns?board_id=X'], 405);
        break;
    case 'POST':
        handle_create_task($db, $current_user);
        break;
    case 'PUT':
        handle_update_task($db, $task_id, $current_user);
        break;
    case 'DELETE':
        handle_delete_task($db, $task_id, $current_user);
        break;
    default:
        send_json_response(['error' => 'Invalid request method'], 405);
        break;
}

function check_task_permission($db, $user, $column_id) {
    $stmt = $db->prepare("SELECT b.owner_id FROM columns c JOIN boards b ON c.board_id = b.id WHERE c.id = ?");
    $stmt->bind_param('i', $column_id);
    $stmt->execute();
    $board = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$board) return false; // Column or board not found
    if ($board['owner_id'] === $user->user_id) return true;
    if (in_array($user->role, ['admin', 'manager'])) return true;

    return false;
}

function handle_create_task($db, $current_user) {
    $data = json_decode(file_get_contents('php://input'));

    if (!isset($data->title) || !isset($data->column_id)) {
        send_json_response(['error' => 'Title and column_id are required'], 400);
    }

    if (!check_task_permission($db, $current_user, $data->column_id)) {
        send_json_response(['error' => 'Forbidden'], 403);
    }

    $stmt = $db->prepare("SELECT MAX(task_order) as max_order FROM tasks WHERE column_id = ?");
    $stmt->bind_param('i', $data->column_id);
    $stmt->execute();
    $max_order = $stmt->get_result()->fetch_assoc()['max_order'] ?? 0;
    $stmt->close();
    $new_order = $max_order + 1;

    $description = $data->description ?? null;
    $due_date = $data->due_date ?? null;

    $stmt = $db->prepare("INSERT INTO tasks (column_id, title, description, due_date, task_order) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param('isssi', $data->column_id, $data->title, $description, $due_date, $new_order);

    if ($stmt->execute()) {
        $new_task_id = $stmt->insert_id;
        send_json_response(['message' => 'Task created successfully', 'task_id' => $new_task_id], 201);
    } else {
        send_json_response(['error' => 'Failed to create task', 'details' => $stmt->error], 500);
    }
    $stmt->close();
}

function handle_update_task($db, $task_id, $current_user) {
    if (!$task_id) {
        send_json_response(['error' => 'Task ID is required'], 400);
    }
    $data = json_decode(file_get_contents('php://input'));

    $stmt = $db->prepare("SELECT column_id FROM tasks WHERE id = ?");
    $stmt->bind_param('i', $task_id);
    $stmt->execute();
    $task = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$task) {
        send_json_response(['error' => 'Task not found'], 404);
    }

    if (!check_task_permission($db, $current_user, $task['column_id'])) {
        send_json_response(['error' => 'Forbidden'], 403);
    }

    // For now, only allow updating title, description, and due_date
    if (!isset($data->title)) {
        send_json_response(['error' => 'Title is required'], 400);
    }
    $description = $data->description ?? null;
    $due_date = $data->due_date ?? null;

    $stmt = $db->prepare("UPDATE tasks SET title = ?, description = ?, due_date = ? WHERE id = ?");
    $stmt->bind_param('sssi', $data->title, $description, $due_date, $task_id);

    if ($stmt->execute()) {
        send_json_response(['message' => 'Task updated successfully']);
    } else {
        send_json_response(['error' => 'Failed to update task', 'details' => $stmt->error], 500);
    }
    $stmt->close();
}

function handle_delete_task($db, $task_id, $current_user) {
    if (!$task_id) {
        send_json_response(['error' => 'Task ID is required'], 400);
    }

    $stmt = $db->prepare("SELECT column_id FROM tasks WHERE id = ?");
    $stmt->bind_param('i', $task_id);
    $stmt->execute();
    $task = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$task) {
        send_json_response(['error' => 'Task not found'], 404);
    }

    if (!check_task_permission($db, $current_user, $task['column_id'])) {
        send_json_response(['error' => 'Forbidden'], 403);
    }

    $stmt = $db->prepare("DELETE FROM tasks WHERE id = ?");
    $stmt->bind_param('i', $task_id);

    if ($stmt->execute()) {
        send_json_response(['message' => 'Task deleted successfully']);
    } else {
        send_json_response(['error' => 'Failed to delete task', 'details' => $stmt->error], 500);
    }
    $stmt->close();
}
