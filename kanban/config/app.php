<?php
define('APP_ENV', 'development'); // development, production
define('APP_DEBUG', true);
define('APP_URL', 'http://localhost:8080');
define('API_URL', 'http://localhost:8080/api/v1');

define('JWT_SECRET', 'your-super-secret-jwt-key');
define('JWT_EXP', 3600); // 1 hour
define('REFRESH_TOKEN_EXP', 604800); // 7 days
