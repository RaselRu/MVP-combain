<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

verify_csrf();
logout_user();
redirect('login.php');
