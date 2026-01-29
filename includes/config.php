<?php
declare(strict_types=1);

define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'b2b_portal');
define('DB_USER', getenv('DB_USER') ?: 'b2b_user');
define('DB_PASS', getenv('DB_PASS') ?: 'change_me');

define('SESSION_NAME', 'b2b_portal');
define('CSRF_KEY', 'csrf_token');
define('SETUP_KEY', getenv('SETUP_KEY') ?: '');

define('APP_TIMEZONE', getenv('APP_TIMEZONE') ?: 'Europe/Moscow');
