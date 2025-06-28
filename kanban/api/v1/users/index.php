<?php
require_once __DIR__ . '/../_helpers/database.php';
require_once __DIR__ . '/../_helpers/auth.php';

function get_all_users($db) {
    $stmt = $db->prepare("SELECT id, username, email, role FROM users");
    $stmt->execute();
    $result = $stmt->get_result();
    $users = $result->fetch_all(MYSQLI_ASSOC);
    send_json_response($users);
}

function create_user($db) {
    $data = json_decode(file_get_contents('php://input'), true);
    $password_hash = password_hash($data['password'], PASSWORD_BCRYPT);
    $stmt = $db->prepare("INSERT INTO users (username, password_hash, email, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('ssss', $data['username'], $password_hash, $data['email'], $data['role']);
    if ($stmt->execute()) {
        send_json_response(['id' => $stmt->insert_id], 201);
    } else {
        send_json_response(['error' => 'Failed to create user'], 500);
    }
}
