<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$user = require_auth();
$project = require_project();
$db = db();

$code_id = get_int($_GET, 'code_id', 0);
if ($code_id <= 0) {
    render_header('Карточка ТН ВЭД', $user, 'tnved', $project);
    echo '<div class="card"><p class="muted">Код не выбран.</p></div>';
    render_footer();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (!$user['is_admin']) {
        set_flash('error', 'Редактирование доступно только администраторам.');
        redirect('tnved_card.php?project_id=' . $project['id'] . '&code_id=' . $code_id);
    }
    $description = get_string($_POST, 'description');
    $duty_rate = get_float($_POST, 'duty_rate');
    $vat_rate = get_float($_POST, 'vat_rate');
    $comments = get_string($_POST, 'comments');
    $tags = get_string($_POST, 'tags');

    $stmt = $db->prepare(
        'UPDATE tnved_codes
         SET description = :description,
             duty_rate = :duty_rate,
             vat_rate = :vat_rate,
             comments = :comments,
             tags = :tags,
             updated_at = :updated_at
         WHERE id = :id'
    );
    $stmt->execute([
        ':description' => $description,
        ':duty_rate' => $duty_rate,
        ':vat_rate' => $vat_rate,
        ':comments' => $comments,
        ':tags' => $tags,
        ':updated_at' => date('Y-m-d H:i:s'),
        ':id' => $code_id,
    ]);

    log_history((int) $project['id'], (int) $user['id'], 'Обновлён код ТН ВЭД', [
        'code_id' => $code_id,
    ]);

    set_flash('success', 'Карточка обновлена.');
    redirect('tnved_card.php?project_id=' . $project['id'] . '&code_id=' . $code_id);
}

$stmt = $db->prepare('SELECT * FROM tnved_codes WHERE id = :id');
$stmt->execute([':id' => $code_id]);
$code = $stmt->fetch();
if (!$code) {
    render_header('Карточка ТН ВЭД', $user, 'tnved', $project);
    echo '<div class="card"><p class="muted">Код не найден.</p></div>';
    render_footer();
    exit;
}

$stmt = $db->prepare(
    'SELECT c.*, u.name AS user_name
     FROM ddp_calculations c
     JOIN users u ON u.id = c.user_id
     WHERE c.project_id = :project_id AND c.tnved_code_id = :code_id
     ORDER BY c.created_at DESC
     LIMIT 10'
);
$stmt->execute([
    ':project_id' => $project['id'],
    ':code_id' => $code_id,
]);
$usage = $stmt->fetchAll();

render_header('Карточка ТН ВЭД', $user, 'tnved', $project);
?>
<div class="grid grid-2">
    <div class="card">
        <h3>Профиль кода</h3>
        <p><strong><?= e($code['code']) ?></strong></p>
        <p class="muted"><?= e($code['description']) ?></p>
        <p>Пошлина: <?= e($code['duty_rate']) ?>%</p>
        <p>НДС: <?= e($code['vat_rate']) ?>%</p>
        <p class="muted">Теги: <?= e($code['tags'] ?? '—') ?></p>
        <p class="muted">Комментарии: <?= e($code['comments'] ?? '—') ?></p>
    </div>
    <div class="card">
        <h3>История применения в проекте</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>Дата</th>
                    <th>Итог</th>
                    <th>Пользователь</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($usage as $row): ?>
                <tr>
                    <td><?= e($row['created_at']) ?></td>
                    <td><?= e(format_money((float) $row['total_amount'], 'RUB')) ?></td>
                    <td><?= e($row['user_name']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$usage): ?>
                <tr><td colspan="3" class="muted">Применений пока нет.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($user['is_admin']): ?>
    <div class="section-title">Редактирование</div>
    <div class="card">
        <form method="post">
            <?= csrf_field() ?>
            <div class="form-row">
                <label for="description">Описание</label>
                <input type="text" id="description" name="description" value="<?= e($code['description']) ?>" required>
            </div>
            <div class="form-row inline-2">
                <div>
                    <label for="duty_rate">Пошлина, %</label>
                    <input type="number" step="0.0001" id="duty_rate" name="duty_rate" value="<?= e($code['duty_rate']) ?>" required>
                </div>
                <div>
                    <label for="vat_rate">НДС, %</label>
                    <input type="number" step="0.0001" id="vat_rate" name="vat_rate" value="<?= e($code['vat_rate']) ?>" required>
                </div>
            </div>
            <div class="form-row">
                <label for="comments">Комментарии</label>
                <textarea id="comments" name="comments"><?= e($code['comments'] ?? '') ?></textarea>
            </div>
            <div class="form-row">
                <label for="tags">Теги</label>
                <input type="text" id="tags" name="tags" value="<?= e($code['tags'] ?? '') ?>">
            </div>
            <div class="actions">
                <button class="btn btn-primary" type="submit">Сохранить</button>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php render_footer(); ?>
