<?php
declare(strict_types=1);

function init_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_name(SESSION_NAME);
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_strict_mode', '1');
    session_start();
}

function current_user(): ?array
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    $stmt = db()->prepare('SELECT u.*, r.name AS role_name FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = :id LIMIT 1');
    $stmt->execute([':id' => $_SESSION['user_id']]);
    $user = $stmt->fetch();
    if (!$user || !$user['is_active']) {
        logout_user();
        return null;
    }
    $user['is_admin'] = ($user['role_name'] === 'admin');
    $cached = $user;
    return $user;
}

function require_auth(): array
{
    $user = current_user();
    if (!$user) {
        redirect('login.php');
    }
    return $user;
}

function require_admin(): array
{
    $user = require_auth();
    if (!$user['is_admin']) {
        http_response_code(403);
        render_header('Доступ ограничен', $user, '');
        echo '<div class="card">';
        echo '<h3>Доступ ограничен</h3>';
        echo '<p class="muted">Эта страница доступна только администраторам.</p>';
        echo '</div>';
        render_footer();
        exit;
    }
    return $user;
}

function authenticate_user(string $email, string $password): ?array
{
    $stmt = db()->prepare('SELECT u.*, r.name AS role_name FROM users u JOIN roles r ON r.id = u.role_id WHERE u.email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();
    if (!$user || !$user['is_active']) {
        return null;
    }
    if (!password_verify($password, $user['password_hash'])) {
        return null;
    }
    $user['is_admin'] = ($user['role_name'] === 'admin');
    return $user;
}

function login_user(int $user_id): void
{
    $_SESSION['user_id'] = $user_id;
}

function logout_user(): void
{
    unset($_SESSION['user_id'], $_SESSION['active_project_id']);
    session_regenerate_id(true);
}
