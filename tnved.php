<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$user = require_auth();
$project = require_project();
$db = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (!$user['is_admin']) {
        set_flash('error', 'Добавление кодов доступно только администраторам.');
        redirect('tnved.php?project_id=' . $project['id']);
    }
    $code = get_string($_POST, 'code');
    $description = get_string($_POST, 'description');
    $duty_rate = get_float($_POST, 'duty_rate');
    $vat_rate = get_float($_POST, 'vat_rate');
    $comments = get_string($_POST, 'comments');
    $tags = get_string($_POST, 'tags');

    if ($code === '' || $description === '') {
        set_flash('error', 'Заполните код и описание.');
        redirect('tnved.php?project_id=' . $project['id']);
    }

    $stmt = $db->prepare(
        'INSERT INTO tnved_codes (code, description, duty_rate, vat_rate, comments, tags, created_at, updated_at)
         VALUES (:code, :description, :duty_rate, :vat_rate, :comments, :tags, :created_at, :updated_at)'
    );
    $now = date('Y-m-d H:i:s');
    $stmt->execute([
        ':code' => $code,
        ':description' => $description,
        ':duty_rate' => $duty_rate,
        ':vat_rate' => $vat_rate,
        ':comments' => $comments,
        ':tags' => $tags,
        ':created_at' => $now,
        ':updated_at' => $now,
    ]);

    log_history((int) $project['id'], (int) $user['id'], 'Добавлен код ТН ВЭД', [
        'code' => $code,
    ]);

    set_flash('success', 'Код ТН ВЭД добавлен.');
    redirect('tnved.php?project_id=' . $project['id']);
}

$stmt = $db->query('SELECT * FROM tnved_codes ORDER BY code');
$codes = $stmt->fetchAll();

render_header('ТН ВЭД', $user, 'tnved', $project);
?>
<div class="card">
    <h3>Реестр ТН ВЭД</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Код</th>
                <th>Описание</th>
                <th>Пошлина</th>
                <th>НДС</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($codes as $code): ?>
            <tr>
                <td><?= e($code['code']) ?></td>
                <td><?= e($code['description']) ?></td>
                <td><?= e($code['duty_rate']) ?>%</td>
                <td><?= e($code['vat_rate']) ?>%</td>
                <td>
                    <a class="link-muted" href="tnved_card.php?project_id=<?= (int) $project['id'] ?>&code_id=<?= (int) $code['id'] ?>">Карточка</a>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$codes): ?>
            <tr><td colspan="5" class="muted">Реестр пуст.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if ($user['is_admin']): ?>
    <div class="section-title">Добавить код</div>
    <div class="card">
        <form method="post">
            <?= csrf_field() ?>
            <div class="form-row inline-2">
                <div>
                    <label for="code">Код</label>
                    <input type="text" id="code" name="code" required>
                </div>
                <div>
                    <label for="description">Описание</label>
                    <input type="text" id="description" name="description" required>
                </div>
            </div>
            <div class="form-row inline-2">
                <div>
                    <label for="duty_rate">Пошлина, %</label>
                    <input type="number" step="0.0001" id="duty_rate" name="duty_rate" required>
                </div>
                <div>
                    <label for="vat_rate">НДС, %</label>
                    <input type="number" step="0.0001" id="vat_rate" name="vat_rate" required>
                </div>
            </div>
            <div class="form-row">
                <label for="comments">Комментарии</label>
                <textarea id="comments" name="comments"></textarea>
            </div>
            <div class="form-row">
                <label for="tags">Теги</label>
                <input type="text" id="tags" name="tags" placeholder="например: электроника, комплектующие">
            </div>
            <div class="actions">
                <button class="btn btn-primary" type="submit">Сохранить</button>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php render_footer(); ?>
