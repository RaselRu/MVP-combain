<?php
declare(strict_types=1);

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function get_flash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function get_int(array $source, string $key, int $default = 0): int
{
    if (!isset($source[$key])) {
        return $default;
    }
    return (int) $source[$key];
}

function get_float(array $source, string $key, float $default = 0.0): float
{
    if (!isset($source[$key])) {
        return $default;
    }
    return (float) str_replace(',', '.', (string) $source[$key]);
}

function get_string(array $source, string $key, string $default = ''): string
{
    if (!isset($source[$key])) {
        return $default;
    }
    return trim((string) $source[$key]);
}

function round_money(float $value): float
{
    return round($value, 2);
}

function format_money(float $value, string $currency = 'RUB'): string
{
    return number_format($value, 2, '.', ' ') . ' ' . $currency;
}

function active_project_id(): ?int
{
    if (isset($_SESSION['active_project_id'])) {
        return (int) $_SESSION['active_project_id'];
    }
    return null;
}

function set_active_project_id(int $project_id): void
{
    $_SESSION['active_project_id'] = $project_id;
}

function user_can_access_project(array $user, int $project_id): bool
{
    if ($user['is_admin']) {
        return true;
    }
    $stmt = db()->prepare('SELECT 1 FROM project_members WHERE user_id = :user_id AND project_id = :project_id');
    $stmt->execute([
        ':user_id' => $user['id'],
        ':project_id' => $project_id,
    ]);
    return (bool) $stmt->fetchColumn();
}

function get_project(int $project_id): ?array
{
    $stmt = db()->prepare('SELECT * FROM projects WHERE id = :id');
    $stmt->execute([':id' => $project_id]);
    $project = $stmt->fetch();
    return $project ?: null;
}

function require_project(): array
{
    $user = current_user();
    if (!$user) {
        redirect('login.php');
    }

    $project_id = get_int($_GET, 'project_id', 0);
    if ($project_id <= 0) {
        $project_id = get_int($_POST, 'project_id', 0);
    }
    if ($project_id <= 0) {
        $project_id = active_project_id() ?? 0;
    }

    if ($project_id <= 0) {
        render_header('Проект не выбран', $user, '');
        echo '<div class="card">';
        echo '<h3>Проект не выбран</h3>';
        echo '<p class="muted">Все операции выполняются только в контексте проекта.</p>';
        echo '<div class="actions">';
        echo '<a class="btn btn-primary" href="projects.php">Перейти к списку проектов</a>';
        echo '</div>';
        echo '</div>';
        render_footer();
        exit;
    }

    if (!user_can_access_project($user, $project_id)) {
        http_response_code(403);
        render_header('Доступ ограничен', $user, '');
        echo '<div class="card">';
        echo '<h3>Доступ ограничен</h3>';
        echo '<p class="muted">У вас нет прав на выбранный проект.</p>';
        echo '</div>';
        render_footer();
        exit;
    }

    $project = get_project($project_id);
    if (!$project) {
        render_header('Проект не найден', $user, '');
        echo '<div class="card">';
        echo '<h3>Проект не найден</h3>';
        echo '<p class="muted">Проверьте идентификатор проекта.</p>';
        echo '</div>';
        render_footer();
        exit;
    }

    set_active_project_id($project_id);
    return $project;
}

function fetch_reference_values(string $type): array
{
    $stmt = db()->prepare('SELECT * FROM reference_values WHERE ref_type = :ref_type AND is_active = 1 ORDER BY sort_order, label');
    $stmt->execute([':ref_type' => $type]);
    return $stmt->fetchAll();
}
