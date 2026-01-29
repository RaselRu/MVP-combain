<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$user = require_auth();
$project = require_project();
$db = db();

$stmt = $db->prepare(
    'SELECT h.*, u.name AS user_name
     FROM history_entries h
     JOIN users u ON u.id = h.user_id
     WHERE h.project_id = :project_id
     ORDER BY h.created_at DESC
     LIMIT 100'
);
$stmt->execute([':project_id' => $project['id']]);
$history = $stmt->fetchAll();

render_header('История операций', $user, 'history', $project);
?>
<div class="card">
    <h3>Журнал проекта</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Дата</th>
                <th>Событие</th>
                <th>Детали</th>
                <th>Пользователь</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($history as $entry): ?>
            <tr>
                <td><?= e($entry['created_at']) ?></td>
                <td><?= e($entry['action']) ?></td>
                <td class="muted"><?= e($entry['details_json'] ?? '') ?></td>
                <td><?= e($entry['user_name']) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$history): ?>
            <tr><td colspan="4" class="muted">История пока пуста.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
<?php render_footer(); ?>
