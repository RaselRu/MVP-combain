<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$user = require_auth();
$project = require_project();
$db = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? 'add';

    if ($action === 'delete') {
        $bookmark_id = get_int($_POST, 'bookmark_id', 0);
        if ($bookmark_id > 0) {
            $stmt = $db->prepare('SELECT * FROM bookmarks WHERE id = :id AND project_id = :project_id');
            $stmt->execute([':id' => $bookmark_id, ':project_id' => $project['id']]);
            $bookmark = $stmt->fetch();
            if ($bookmark && ($bookmark['user_id'] == $user['id'] || $user['is_admin'])) {
                $stmt = $db->prepare('DELETE FROM bookmarks WHERE id = :id');
                $stmt->execute([':id' => $bookmark_id]);
                log_history((int) $project['id'], (int) $user['id'], 'Удалена закладка', [
                    'bookmark_id' => $bookmark_id,
                ]);
                set_flash('success', 'Закладка удалена.');
            }
        }
        redirect('bookmarks.php?project_id=' . $project['id']);
    }

    $title = get_string($_POST, 'title');
    $scope = get_string($_POST, 'scope', 'personal');
    $item_type = get_string($_POST, 'item_type', 'screen');
    $item_ref = get_string($_POST, 'item_ref');

    if ($title === '') {
        set_flash('error', 'Укажите название закладки.');
        redirect('bookmarks.php?project_id=' . $project['id']);
    }

    $stmt = $db->prepare(
        'INSERT INTO bookmarks (project_id, user_id, scope, item_type, item_ref, title, created_at)
         VALUES (:project_id, :user_id, :scope, :item_type, :item_ref, :title, :created_at)'
    );
    $stmt->execute([
        ':project_id' => $project['id'],
        ':user_id' => $user['id'],
        ':scope' => $scope,
        ':item_type' => $item_type,
        ':item_ref' => $item_ref,
        ':title' => $title,
        ':created_at' => date('Y-m-d H:i:s'),
    ]);

    log_history((int) $project['id'], (int) $user['id'], 'Добавлена закладка', [
        'title' => $title,
        'scope' => $scope,
    ]);

    set_flash('success', 'Закладка добавлена.');
    redirect('bookmarks.php?project_id=' . $project['id']);
}

$stmt = $db->prepare(
    'SELECT * FROM bookmarks
     WHERE project_id = :project_id
     ORDER BY created_at DESC'
);
$stmt->execute([':project_id' => $project['id']]);
$bookmarks = $stmt->fetchAll();

render_header('Закладки', $user, 'bookmarks', $project);
?>
<div class="card">
    <h3>Закладки проекта</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Название</th>
                <th>Тип</th>
                <th>Область</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($bookmarks as $bookmark): ?>
            <tr>
                <td><?= e($bookmark['title']) ?></td>
                <td><?= e($bookmark['item_type']) ?></td>
                <td><?= e($bookmark['scope']) ?></td>
                <td>
                    <form method="post" style="display:inline-block;">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="bookmark_id" value="<?= (int) $bookmark['id'] ?>">
                        <button class="btn btn-secondary" type="submit">Удалить</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$bookmarks): ?>
            <tr><td colspan="4" class="muted">Закладки отсутствуют.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="section-title">Новая закладка</div>
<div class="card">
    <form method="post">
        <?= csrf_field() ?>
        <div class="form-row inline-2">
            <div>
                <label for="title">Название</label>
                <input type="text" id="title" name="title" required>
            </div>
            <div>
                <label for="scope">Область</label>
                <select id="scope" name="scope">
                    <option value="personal">Личная</option>
                    <option value="project">Проектная</option>
                </select>
            </div>
        </div>
        <div class="form-row inline-2">
            <div>
                <label for="item_type">Тип</label>
                <select id="item_type" name="item_type">
                    <option value="screen">Экран</option>
                    <option value="project">Проект</option>
                    <option value="calculation">Расчёт</option>
                    <option value="result">Результат</option>
                </select>
            </div>
            <div>
                <label for="item_ref">Идентификатор (опционально)</label>
                <input type="text" id="item_ref" name="item_ref">
            </div>
        </div>
        <div class="actions">
            <button class="btn btn-primary" type="submit">Добавить</button>
        </div>
    </form>
</div>
<?php render_footer(); ?>
