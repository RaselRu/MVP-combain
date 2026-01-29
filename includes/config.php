<?php
declare(strict_types=1);

$local_config = __DIR__ . '/config.local.php';
if (is_file($local_config)) {
    require_once $local_config;
}

if (!defined('DB_HOST')) {
    define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
}
if (!defined('DB_NAME')) {
    define('DB_NAME', getenv('DB_NAME') ?: 'b2b_portal');
}
if (!defined('DB_USER')) {
    define('DB_USER', getenv('DB_USER') ?: 'b2b_user');
}
if (!defined('DB_PASS')) {
    define('DB_PASS', getenv('DB_PASS') ?: 'change_me');
}

if (!defined('SESSION_NAME')) {
    define('SESSION_NAME', 'b2b_portal');
}
if (!defined('CSRF_KEY')) {
    define('CSRF_KEY', 'csrf_token');
}
if (!defined('SETUP_KEY')) {
    define('SETUP_KEY', getenv('SETUP_KEY') ?: '');
}

if (!defined('APP_TIMEZONE')) {
    define('APP_TIMEZONE', getenv('APP_TIMEZONE') ?: 'Europe/Moscow');
}
