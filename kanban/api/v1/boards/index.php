<?php
// api/v1/boards/index.php

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

// Extract board ID from the URL, if present
$parts = explode('/', $_SERVER['REQUEST_URI']);
$board_id = end($parts) == 'boards' ? null : (int)end($parts);

switch ($request_method) {
    case 'GET':
        if ($board_id) {
            handle_get_board($db, $board_id);
        } else {
            handle_get_all_boards($db);
        }
        break;
    case 'POST':
        handle_create_board($db);
        break;
    case 'PUT':
        handle_update_board($db, $board_id);
        break;
    case 'DELETE':
        handle_delete_board($db, $board_id);
        break;
    default:
        send_json_response(['error' => 'Invalid request method'], 405);
        break;
}

function handle_get_all_boards($db) {
    $current_user = require_auth(); // All authenticated users can see their boards
    // This query should be refined to only show boards the user has access to.
    // For now, it shows all boards, which might be a security risk depending on the application logic.
    $stmt = $db->prepare("SELECT id, name, description, owner_id FROM boards");
    $stmt->execute();
    $result = $stmt->get_result();
    $boards = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    send_json_response($boards);
}

function handle_get_board($db, $board_id) {
    $current_user = require_auth();
    // Add logic here to check if the user has permission to view this specific board
    $stmt = $db->prepare("SELECT * FROM boards WHERE id = ?");
    $stmt->bind_param('i', $board_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $board = $result->fetch_assoc();
    $stmt->close();

    if ($board) {
        send_json_response($board);
    } else {
        send_json_response(['error' => 'Board not found'], 404);
    }
}

function handle_create_board($db) {
    $current_user = require_auth(['admin', 'manager']); // Only admins and managers can create boards
    $data = json_decode(file_get_contents('php://input'));

    if (!isset($data->name)) {
        send_json_response(['error' => 'Board name is required'], 400);
    }

    $description = $data->description ?? '';
    $owner_id = $current_user->user_id; // Set owner to the authenticated user

    $stmt = $db->prepare("INSERT INTO boards (name, description, owner_id) VALUES (?, ?, ?)");
    $stmt->bind_param('ssi', $data->name, $description, $owner_id);
    
    if ($stmt->execute()) {
        $new_board_id = $stmt->insert_id;
        send_json_response(['message' => 'Board created successfully', 'board_id' => $new_board_id], 201);
    } else {
        send_json_response(['error' => 'Failed to create board', 'details' => $stmt->error], 500);
    }
    $stmt->close();
}

function handle_update_board($db, $board_id) {
    if (!$board_id) {
        send_json_response(['error' => 'Board ID is required'], 400);
    }
    $current_user = require_auth();
    $data = json_decode(file_get_contents('php://input'));

    // First, verify the user owns the board or is an admin
    $stmt = $db->prepare("SELECT owner_id FROM boards WHERE id = ?");
    $stmt->bind_param('i', $board_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $board = $result->fetch_assoc();
    $stmt->close();

    if (!$board) {
        send_json_response(['error' => 'Board not found'], 404);
    }

    if ($board['owner_id'] !== $current_user->user_id && $current_user->role !== 'admin') {
        send_json_response(['error' => 'Forbidden: You do not own this board'], 403);
    }

    // Update logic here
    $name = $data->name ?? null;
    if (!$name) {
        send_json_response(['error' => 'Board name is required for update'], 400);
    }
    $description = $data->description ?? '';

    $stmt = $db->prepare("UPDATE boards SET name = ?, description = ? WHERE id = ?");
    $stmt->bind_param('ssi', $name, $description, $board_id);
    if ($stmt->execute()) {
        send_json_response(['message' => 'Board updated successfully']);
    } else {
        send_json_response(['error' => 'Failed to update board', 'details' => $stmt->error], 500);
    }
    $stmt->close();
}

function handle_delete_board($db, $board_id) {
    if (!$board_id) {
        send_json_response(['error' => 'Board ID is required'], 400);
    }
    $current_user = require_auth();

    // Verify ownership or admin role before deleting
    $stmt = $db->prepare("SELECT owner_id FROM boards WHERE id = ?");
    $stmt->bind_param('i', $board_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $board = $result->fetch_assoc();
    $stmt->close();

    if (!$board) {
        send_json_response(['error' => 'Board not found'], 404);
    }

    if ($board['owner_id'] !== $current_user->user_id && $current_user->role !== 'admin') {
        send_json_response(['error' => 'Forbidden: You do not own this board'], 403);
    }

    $stmt = $db->prepare("DELETE FROM boards WHERE id = ?");
    $stmt->bind_param('i', $board_id);
    if ($stmt->execute()) {
        send_json_response(['message' => 'Board deleted successfully']);
    } else {
        send_json_response(['error' => 'Failed to delete board', 'details' => $stmt->error], 500);
    }
    $stmt->close();
}
