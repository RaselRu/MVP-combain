<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
date_default_timezone_set(APP_TIMEZONE);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/history.php';
require_once __DIR__ . '/layout.php';

init_session();
