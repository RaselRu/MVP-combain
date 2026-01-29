<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$user = require_auth();
$db = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = get_string($_POST, 'name');
    $description = get_string($_POST, 'description');
    if ($name === '') {
        set_flash('error', 'Укажите название проекта.');
        redirect('projects.php');
    }

    $stmt = $db->prepare(
        'INSERT INTO projects (name, description, status, created_at, updated_at)
         VALUES (:name, :description, :status, :created_at, :updated_at)'
    );
    $now = date('Y-m-d H:i:s');
    $stmt->execute([
        ':name' => $name,
        ':description' => $description,
        ':status' => 'active',
        ':created_at' => $now,
        ':updated_at' => $now,
    ]);
    $project_id = (int) $db->lastInsertId();

    $stmt = $db->prepare(
        'INSERT INTO project_members (project_id, user_id, member_role, created_at)
         VALUES (:project_id, :user_id, :member_role, :created_at)'
    );
    $stmt->execute([
        ':project_id' => $project_id,
        ':user_id' => $user['id'],
        ':member_role' => $user['is_admin'] ? 'owner' : 'member',
        ':created_at' => $now,
    ]);

    log_history($project_id, (int) $user['id'], 'Создан проект', [
        'project_name' => $name,
    ]);

    set_active_project_id($project_id);
    set_flash('success', 'Проект создан.');
    redirect('project.php?project_id=' . $project_id);
}

if ($user['is_admin']) {
    $stmt = $db->query('SELECT * FROM projects ORDER BY updated_at DESC');
    $projects = $stmt->fetchAll();
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

render_header('Проекты', $user, 'projects');
?>
<div class="grid grid-2">
    <div class="card">
        <h3>Список проектов</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>Название</th>
                    <th>Статус</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($projects as $project): ?>
                <tr>
                    <td><?= e($project['name']) ?></td>
                    <td><span class="badge"><?= e($project['status']) ?></span></td>
                    <td>
                        <a class="link-muted" href="project.php?project_id=<?= (int) $project['id'] ?>">Открыть</a>
                        ·
                        <a class="link-muted" href="index.php?project_id=<?= (int) $project['id'] ?>">Сделать активным</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$projects): ?>
                <tr><td colspan="3" class="muted">Проекты отсутствуют.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card">
        <h3>Новый проект</h3>
        <form method="post">
            <?= csrf_field() ?>
            <div class="form-row">
                <label for="name">Название проекта</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="form-row">
                <label for="description">Описание</label>
                <textarea id="description" name="description"></textarea>
            </div>
            <div class="actions">
                <button class="btn btn-primary" type="submit">Создать</button>
            </div>
        </form>
    </div>
</div>
<?php render_footer(); ?>
