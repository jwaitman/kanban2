<?php
require_once __DIR__ . '/../_helpers/database.php';
require_once __DIR__ . '/../_helpers/auth.php';

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
