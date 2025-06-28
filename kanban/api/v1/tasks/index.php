<?php
require_once __DIR__ . '/../_helpers/database.php';
require_once __DIR__ . '/../_helpers/auth.php';

function get_all_tasks($db) {
    $stmt = $db->prepare("SELECT * FROM tasks");
    $stmt->execute();
    $result = $stmt->get_result();
    $tasks = $result->fetch_all(MYSQLI_ASSOC);
    send_json_response($tasks);
}

function create_task($db) {
    $data = json_decode(file_get_contents('php://input'), true);
    $stmt = $db->prepare("INSERT INTO tasks (column_id, title, description, due_date, priority, task_order) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('isssii', $data['column_id'], $data['title'], $data['description'], $data['due_date'], $data['priority'], $data['task_order']);
    if ($stmt->execute()) {
        send_json_response(['id' => $stmt->insert_id], 201);
    } else {
        send_json_response(['error' => 'Failed to create task'], 500);
    }
}
