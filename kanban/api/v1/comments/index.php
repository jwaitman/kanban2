<?php
require_once __DIR__ . '/../_helpers/database.php';
require_once __DIR__ . '/../_helpers/auth.php';

function get_all_comments($db) {
    $stmt = $db->prepare("SELECT * FROM comments");
    $stmt->execute();
    $result = $stmt->get_result();
    $comments = $result->fetch_all(MYSQLI_ASSOC);
    send_json_response($comments);
}

function create_comment($db) {
    $data = json_decode(file_get_contents('php://input'), true);
    $stmt = $db->prepare("INSERT INTO comments (task_id, user_id, content) VALUES (?, ?, ?)");
    $stmt->bind_param('iis', $data['task_id'], $data['user_id'], $data['content']);
    if ($stmt->execute()) {
        send_json_response(['id' => $stmt->insert_id], 201);
    } else {
        send_json_response(['error' => 'Failed to create comment'], 500);
    }
}
