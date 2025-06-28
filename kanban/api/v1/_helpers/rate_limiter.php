<?php
// api/v1/_helpers/rate_limiter.php

require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/response.php';

// --- Constants ---
if (!defined('RATE_LIMIT_ATTEMPTS')) define('RATE_LIMIT_ATTEMPTS', 5); // Max attempts
if (!defined('RATE_LIMIT_PERIOD')) define('RATE_LIMIT_PERIOD', 300); // Time period in seconds (5 minutes)

function check_rate_limit(string $ip_address, string $action): void {
    $storage_path = __DIR__ . '/../../../storage/cache/';
    $record_file = $storage_path . 'rate_limit_' . md5($ip_address . '_' . $action) . '.json';

    $current_time = time();
    $records = [];

    if (file_exists($record_file)) {
        $records = json_decode(file_get_contents($record_file), true);
    }

    // Filter out records that are outside the current time window
    $recent_records = array_filter($records, function($timestamp) use ($current_time) {
        return ($current_time - $timestamp) < RATE_LIMIT_PERIOD;
    });

    if (count($recent_records) >= RATE_LIMIT_ATTEMPTS) {
        send_json_response(['error' => 'Too many requests. Please try again later.'], 429);
    }

    // Add current request timestamp and save
    $recent_records[] = $current_time;
    file_put_contents($record_file, json_encode($recent_records));
}
