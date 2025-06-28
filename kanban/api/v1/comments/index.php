<?php
// api/v1/comments/index.php
require_once __DIR__ . '/../_helpers/database.php';
require_once __DIR__ . '/../_helpers/response.php';
require_once __DIR__ . '/../_helpers/auth.php';

// Set common headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

$db = connect_to_database();
$request_method = $_SERVER["REQUEST_METHOD"];

// Extract comment ID from the URL for DELETE requests
$parts = explode('/', $_SERVER['REQUEST_URI']);
$comment_id = end($parts) == 'comments' ? null : (int)end($parts);

switch ($request_method) {
    case 'GET':
        // Requires a task_id, e.g., /api/v1/comments?task_id=123
        handle_get_comments_for_task($db);
        break;
    case 'POST':
        handle_create_comment($db);
        break;
    case 'DELETE':
        handle_delete_comment($db, $comment_id);
        break;
    default:
        send_json_response(['error' => 'Invalid request method'], 405);
        break;
}

function handle_get_comments_for_task($db) {
    $current_user = require_auth();
    if (!isset($_GET['task_id'])) {
        send_json_response(['error' => 'task_id parameter is required'], 400);
    }
    $task_id = (int)$_GET['task_id'];

    // TODO: Check if the user has access to the board this task belongs to.

    // Join with users table to get username
    $stmt = $db->prepare("SELECT c.id, c.content, c.created_at, u.username FROM comments c JOIN users u ON c.user_id = u.id WHERE c.task_id = ? ORDER BY c.created_at ASC");
    $stmt->bind_param('i', $task_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $comments = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    send_json_response($comments);
}

function handle_create_comment($db) {
    $current_user = require_auth();
    $data = json_decode(file_get_contents('php://input'));

    if (!isset($data->task_id) || !isset($data->content) || empty(trim($data->content))) {
        send_json_response(['error' => 'Missing or empty required fields: task_id, content'], 400);
    }

    // TODO: Check if user has permission to comment on this task.

    $task_id = (int)$data->task_id;
    $user_id = $current_user->user_id; // Use the ID from the token, not from the client
    $content = htmlspecialchars(strip_tags($data->content)); // Basic sanitization

    $stmt = $db->prepare("INSERT INTO comments (task_id, user_id, content) VALUES (?, ?, ?)");
    $stmt->bind_param('iis', $task_id, $user_id, $content);
    
    if ($stmt->execute()) {
        $new_comment_id = $stmt->insert_id;
        // Fetch the new comment to return it with username
        $new_comment_stmt = $db->prepare("SELECT c.id, c.content, c.created_at, u.username FROM comments c JOIN users u ON c.user_id = u.id WHERE c.id = ?");
        $new_comment_stmt->bind_param('i', $new_comment_id);
        $new_comment_stmt->execute();
        $result = $new_comment_stmt->get_result();
        $new_comment = $result->fetch_assoc();
        $new_comment_stmt->close();

        send_json_response($new_comment, 201);
    } else {
        send_json_response(['error' => 'Failed to create comment', 'details' => $stmt->error], 500);
    }
    $stmt->close();
}

function handle_delete_comment($db, $comment_id) {
    if (!$comment_id) {
        send_json_response(['error' => 'Comment ID is required'], 400);
    }
    $current_user = require_auth();

    // Get comment owner
    $stmt = $db->prepare("SELECT user_id FROM comments WHERE id = ?");
    $stmt->bind_param('i', $comment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $comment = $result->fetch_assoc();
    $stmt->close();

    if (!$comment) {
        send_json_response(['error' => 'Comment not found'], 404);
    }

    // Only the comment owner or an admin can delete
    if ($comment['user_id'] !== $current_user->user_id && $current_user->role !== 'admin') {
        send_json_response(['error' => 'Forbidden: You cannot delete this comment'], 403);
    }

    $delete_stmt = $db->prepare("DELETE FROM comments WHERE id = ?");
    $delete_stmt->bind_param('i', $comment_id);
    if ($delete_stmt->execute()) {
        send_json_response(['message' => 'Comment deleted successfully']);
    } else {
        send_json_response(['error' => 'Failed to delete comment', 'details' => $delete_stmt->error], 500);
    }
    $delete_stmt->close();
}
