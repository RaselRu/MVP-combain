<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$user = require_auth();
$project = require_project();
$db = db();

$stmt = $db->prepare(
    'SELECT c.*, t.code AS tnved_code
     FROM ddp_calculations c
     LEFT JOIN tnved_codes t ON t.id = c.tnved_code_id
     WHERE c.project_id = :project_id
     ORDER BY c.created_at DESC
     LIMIT 5'
);
$stmt->execute([':project_id' => $project['id']]);
$recent_calculations = $stmt->fetchAll();

$stmt = $db->prepare(
    'SELECT t.code, t.description, COUNT(*) AS usage_count
     FROM ddp_calculations c
     JOIN tnved_codes t ON t.id = c.tnved_code_id
     WHERE c.project_id = :project_id
     GROUP BY t.code, t.description
     ORDER BY usage_count DESC
     LIMIT 5'
);
$stmt->execute([':project_id' => $project['id']]);
$tnved_usage = $stmt->fetchAll();

$stmt = $db->prepare(
    'SELECT h.*, u.name AS user_name
     FROM history_entries h
     JOIN users u ON u.id = h.user_id
     WHERE h.project_id = :project_id
     ORDER BY h.created_at DESC
     LIMIT 6'
);
$stmt->execute([':project_id' => $project['id']]);
$recent_history = $stmt->fetchAll();

$stmt = $db->prepare(
    'SELECT * FROM bookmarks
     WHERE project_id = :project_id AND scope = :scope
     ORDER BY created_at DESC
     LIMIT 5'
);
$stmt->execute([':project_id' => $project['id'], ':scope' => 'project']);
$project_bookmarks = $stmt->fetchAll();

render_header('Обзор проекта', $user, 'projects', $project);
?>
<div class="grid grid-2">
    <div class="card">
        <h3>Профиль проекта</h3>
        <p><strong><?= e($project['name']) ?></strong></p>
        <p class="muted"><?= e($project['description'] ?? '') ?></p>
        <p><span class="badge"><?= e($project['status']) ?></span></p>
    </div>
    <div class="card">
        <h3>Быстрые действия</h3>
        <div class="actions">
            <a class="btn btn-primary" href="ddp_new.php?project_id=<?= (int) $project['id'] ?>">Новый расчёт DDP</a>
            <a class="btn btn-secondary" href="tnved.php?project_id=<?= (int) $project['id'] ?>">ТН ВЭД</a>
            <a class="btn btn-secondary" href="history.php?project_id=<?= (int) $project['id'] ?>">История</a>
        </div>
    </div>
</div>

<div class="section-title">Последние расчёты</div>
<div class="card">
    <table class="table">
        <thead>
            <tr>
                <th>Дата</th>
                <th>ТН ВЭД</th>
                <th>Итог</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($recent_calculations as $calc): ?>
            <tr>
                <td><?= e($calc['created_at']) ?></td>
                <td><?= e($calc['tnved_code'] ?? '—') ?></td>
                <td><?= e(format_money((float) $calc['total_amount'], 'RUB')) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$recent_calculations): ?>
            <tr><td colspan="3" class="muted">Расчётов пока нет.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="section-title">ТН ВЭД в проекте</div>
<div class="card">
    <table class="table">
        <thead>
            <tr>
                <th>Код</th>
                <th>Описание</th>
                <th>Использований</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($tnved_usage as $code): ?>
            <tr>
                <td><?= e($code['code']) ?></td>
                <td><?= e($code['description']) ?></td>
                <td><?= e($code['usage_count']) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$tnved_usage): ?>
            <tr><td colspan="3" class="muted">ТН ВЭД ещё не использовались.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="grid grid-2">
    <div class="card">
        <h3>Последняя история</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>Дата</th>
                    <th>Событие</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($recent_history as $entry): ?>
                <tr>
                    <td><?= e($entry['created_at']) ?></td>
                    <td><?= e($entry['action']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$recent_history): ?>
                <tr><td colspan="2" class="muted">История пуста.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card">
        <h3>Проектные закладки</h3>
        <ul>
            <?php foreach ($project_bookmarks as $bookmark): ?>
                <li><?= e($bookmark['title']) ?></li>
            <?php endforeach; ?>
            <?php if (!$project_bookmarks): ?>
                <li class="muted">Закладок пока нет.</li>
            <?php endif; ?>
        </ul>
    </div>
</div>

<?php render_footer(); ?>
