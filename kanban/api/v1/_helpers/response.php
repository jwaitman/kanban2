<?php
// api/v1/_helpers/response.php

function send_json_response($data, $status_code = 200) {
    // Apply security headers
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; object-src 'none'; frame-ancestors 'none';");
    header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
    header("X-Frame-Options: DENY");
    header("X-Content-Type-Options: nosniff");
    header("X-XSS-Protection: 1; mode=block");
    header("Referrer-Policy: same-origin");

    http_response_code($status_code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit(); // Terminate script after sending response
}
