<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$user = require_auth();
$db = db();

$active_project = null;
$project_id_param = get_int($_GET, 'project_id', 0);
if ($project_id_param > 0 && user_can_access_project($user, $project_id_param)) {
    $active_project = get_project($project_id_param);
    if ($active_project) {
        set_active_project_id((int) $active_project['id']);
    }
} elseif (active_project_id()) {
    $active_project = get_project(active_project_id());
}

if ($user['is_admin']) {
    $projects_stmt = $db->query('SELECT * FROM projects ORDER BY updated_at DESC');
    $projects = $projects_stmt->fetchAll();
} else {
    $stmt = $db->prepare(
        'SELECT p.* FROM projects p
         JOIN project_members pm ON pm.project_id = p.id
         WHERE pm.user_id = :user_id
         ORDER BY p.updated_at DESC'
    );
    $stmt->execute([':user_id' => $user['id']]);
    $projects = $stmt->fetchAll();
}

$project_filter_sql = '';
$project_filter_params = [];
if ($active_project) {
    $project_filter_sql = 'WHERE project_id = :project_id';
    $project_filter_params[':project_id'] = $active_project['id'];
} elseif (!$user['is_admin']) {
    $project_filter_sql = 'WHERE project_id IN (SELECT project_id FROM project_members WHERE user_id = :user_id)';
    $project_filter_params[':user_id'] = $user['id'];
}

$stmt = $db->prepare(
    'SELECT c.*, t.code AS tnved_code
     FROM ddp_calculations c
     LEFT JOIN tnved_codes t ON t.id = c.tnved_code_id
     ' . $project_filter_sql . '
     ORDER BY c.created_at DESC
     LIMIT 5'
);
$stmt->execute($project_filter_params);
$recent_calculations = $stmt->fetchAll();

$stmt = $db->prepare(
    'SELECT h.*, u.name AS user_name
     FROM history_entries h
     JOIN users u ON u.id = h.user_id
     ' . $project_filter_sql . '
     ORDER BY h.created_at DESC
     LIMIT 8'
);
$stmt->execute($project_filter_params);
$recent_history = $stmt->fetchAll();

render_header('Пульт', $user, 'dashboard', $active_project);
?>
<div class="grid grid-2">
    <div class="card">
        <h3>Активный проект</h3>
        <?php if ($active_project): ?>
            <p><strong><?= e($active_project['name']) ?></strong></p>
            <p class="muted"><?= e($active_project['description'] ?? '') ?></p>
            <div class="actions">
                <a class="btn btn-secondary" href="project.php?project_id=<?= (int) $active_project['id'] ?>">Открыть обзор</a>
                <a class="btn btn-primary" href="ddp_new.php?project_id=<?= (int) $active_project['id'] ?>">Новый расчёт DDP</a>
            </div>
        <?php else: ?>
            <p class="muted">Проект не выбран. Все операции выполняются только в контексте проекта.</p>
            <a class="btn btn-primary" href="projects.php">Выбрать проект</a>
        <?php endif; ?>
    </div>
    <div class="card">
        <h3>Проекты</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>Проект</th>
                    <th>Статус</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($projects as $project): ?>
                <tr>
                    <td><?= e($project['name']) ?></td>
                    <td><span class="badge"><?= e($project['status']) ?></span></td>
                    <td><a class="link-muted" href="index.php?project_id=<?= (int) $project['id'] ?>">Сделать активным</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$projects): ?>
                <tr><td colspan="3" class="muted">Проекты отсутствуют.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="section-title">Последние расчёты DDP</div>
<div class="card">
    <table class="table">
        <thead>
            <tr>
                <th>Дата</th>
                <th>ТН ВЭД</th>
                <th>Итог</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($recent_calculations as $calc): ?>
            <tr>
                <td><?= e($calc['created_at']) ?></td>
                <td><?= e($calc['tnved_code'] ?? '—') ?></td>
                <td><?= e(format_money((float) $calc['total_amount'], 'RUB')) ?></td>
                <td><a class="link-muted" href="ddp_new.php?project_id=<?= (int) $calc['project_id'] ?>">Открыть</a></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$recent_calculations): ?>
            <tr><td colspan="4" class="muted">Расчёты отсутствуют.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="section-title">Последние операции</div>
<div class="card">
    <table class="table">
        <thead>
            <tr>
                <th>Дата</th>
                <th>Событие</th>
                <th>Пользователь</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($recent_history as $entry): ?>
            <tr>
                <td><?= e($entry['created_at']) ?></td>
                <td><?= e($entry['action']) ?></td>
                <td><?= e($entry['user_name']) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$recent_history): ?>
            <tr><td colspan="3" class="muted">История пока пуста.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php render_footer(); ?>
