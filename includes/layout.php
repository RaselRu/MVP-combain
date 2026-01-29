<?php
declare(strict_types=1);

function project_query(?array $project): string
{
    if (!$project) {
        return '';
    }
    return '?project_id=' . (int) $project['id'];
}

function render_header(string $title, array $user, string $active_page, ?array $project = null): void
{
    $flash = get_flash();
    $project_label = $project ? $project['name'] : 'Проект не выбран';
    $project_status = $project ? $project['status'] : '';
    $project_query = project_query($project);
    $project_links_disabled = !$project;

    echo '<!doctype html>';
    echo '<html lang="ru">';
    echo '<head>';
    echo '<meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>' . e($title) . '</title>';
    echo '<link rel="stylesheet" href="assets/styles.css">';
    echo '</head>';
    echo '<body>';
    echo '<div class="layout">';
    echo '<aside class="sidebar">';
    echo '<div class="brand">B2B Portal</div>';
    echo '<div class="nav-section">';
    echo '<div class="nav-title">Основное</div>';
    echo nav_link('Пульт', 'index.php', $active_page === 'dashboard');
    echo nav_link('Проекты', 'projects.php', $active_page === 'projects');
    echo '</div>';
    echo '<div class="nav-section">';
    echo '<div class="nav-title">Проект</div>';
    echo nav_link('Новый расчёт DDP', 'ddp_new.php' . $project_query, $active_page === 'ddp', $project_links_disabled);
    echo nav_link('ТН ВЭД', 'tnved.php' . $project_query, $active_page === 'tnved', $project_links_disabled);
    echo nav_link('История операций', 'history.php' . $project_query, $active_page === 'history', $project_links_disabled);
    echo nav_link('Закладки', 'bookmarks.php' . $project_query, $active_page === 'bookmarks', $project_links_disabled);
    echo '</div>';
    if ($user['is_admin']) {
        echo '<div class="nav-section">';
        echo '<div class="nav-title">Администрирование</div>';
        echo nav_link('Админка', 'admin.php', $active_page === 'admin');
        echo '</div>';
    }
    echo '</aside>';
    echo '<main class="main">';
    echo '<div class="topbar">';
    echo '<div>';
    echo '<h1 class="page-title">' . e($title) . '</h1>';
    echo '<div class="meta">';
    echo 'Проект: <strong>' . e($project_label) . '</strong>';
    if ($project_status) {
        echo ' <span class="badge">' . e($project_status) . '</span>';
    }
    echo '</div>';
    echo '</div>';
    echo '<div class="meta">';
    echo e($user['name']) . ' · ' . e($user['role_name']);
    echo '<form method="post" action="logout.php" style="display:inline-block;margin-left:12px;">';
    echo csrf_field();
    echo '<button class="btn btn-secondary" type="submit">Выйти</button>';
    echo '</form>';
    echo '</div>';
    echo '</div>';
    if ($flash) {
        echo '<div class="flash ' . e($flash['type']) . '">' . e($flash['message']) . '</div>';
    }
}

function render_footer(): void
{
    echo '</main>';
    echo '</div>';
    echo '</body>';
    echo '</html>';
}

function nav_link(string $label, string $href, bool $active, bool $disabled = false): string
{
    $classes = ['nav-link'];
    if ($active) {
        $classes[] = 'active';
    }
    if ($disabled) {
        $classes[] = 'disabled';
        $href = 'projects.php';
    }
    return '<a class="' . e(implode(' ', $classes)) . '" href="' . e($href) . '">' . e($label) . '</a>';
}
