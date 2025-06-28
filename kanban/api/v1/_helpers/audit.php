<?php
// api/v1/_helpers/audit.php

function log_audit($db, $user_id, $action, $target_type = null, $target_id = null, $details = null) {
    // Don't log sensitive details unless necessary and properly secured.
    // For this implementation, we will log basic info.

    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    $stmt = $db->prepare(
        "INSERT INTO audit_log (user_id, action, target_type, target_id, details, ip_address) VALUES (?, ?, ?, ?, ?, ?)"
    );

    // If statement preparation fails, log error and exit. Avoids crashing on DB errors.
    if (false === $stmt) {
        error_log("Prepare failed: (" . $db->errno . ") " . $db->error);
        return;
    }

    $stmt->bind_param('isssis', $user_id, $action, $target_type, $target_id, $details, $ip_address);

    if (!$stmt->execute()) {
        error_log("Execute failed: (" . $stmt->errno . ") " . $stmt->error);
    }

    $stmt->close();
}
