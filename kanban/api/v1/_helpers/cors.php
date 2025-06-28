<?php
// api/v1/_helpers/cors.php

// IMPORTANT: This script should be the VERY FIRST thing included in any API endpoint.

// Define allowed origins. For production, this should be a specific domain.
// Example: define('ALLOWED_ORIGIN', 'https://your-frontend-app.com');
define('ALLOWED_ORIGIN', '*'); // For development

header("Access-Control-Allow-Origin: " . ALLOWED_ORIGIN);
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Max-Age: 3600");

// Handle the preflight OPTIONS request. This must be done before any other processing.
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    // If you need to handle credentials, uncomment the line below
    // header("Access-Control-Allow-Credentials: true");
    http_response_code(204); // No Content
    exit();
}
