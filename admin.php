<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$user = require_admin();
$db = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create_user') {
        $name = get_string($_POST, 'name');
        $email = get_string($_POST, 'email');
        $password = (string) ($_POST['password'] ?? '');
        $role_id = get_int($_POST, 'role_id', 0);
        if ($name === '' || $email === '' || $password === '' || $role_id <= 0) {
            set_flash('error', 'Заполните все поля пользователя.');
            redirect('admin.php');
        }
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare(
            'INSERT INTO users (name, email, password_hash, role_id, is_active, created_at)
             VALUES (:name, :email, :password_hash, :role_id, 1, :created_at)'
        );
        $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':password_hash' => $hash,
            ':role_id' => $role_id,
            ':created_at' => date('Y-m-d H:i:s'),
        ]);
        set_flash('success', 'Пользователь создан.');
        redirect('admin.php');
    }

    if ($action === 'toggle_user') {
        $user_id = get_int($_POST, 'user_id', 0);
        $is_active = get_int($_POST, 'is_active', 0);
        if ($user_id > 0) {
            $stmt = $db->prepare('UPDATE users SET is_active = :is_active WHERE id = :id');
            $stmt->execute([
                ':is_active' => $is_active ? 1 : 0,
                ':id' => $user_id,
            ]);
            set_flash('success', 'Статус пользователя обновлен.');
        }
        redirect('admin.php');
    }

    if ($action === 'create_role') {
        $name = get_string($_POST, 'role_name');
        $description = get_string($_POST, 'role_description');
        if ($name === '') {
            set_flash('error', 'Введите название роли.');
            redirect('admin.php');
        }
        $stmt = $db->prepare('INSERT INTO roles (name, description) VALUES (:name, :description)');
        $stmt->execute([
            ':name' => $name,
            ':description' => $description,
        ]);
        set_flash('success', 'Роль создана.');
        redirect('admin.php');
    }

    if ($action === 'create_reference') {
        $ref_type = get_string($_POST, 'ref_type');
        $code = get_string($_POST, 'ref_code');
        $label = get_string($_POST, 'ref_label');
        $sort_order = get_int($_POST, 'sort_order', 0);
        if ($ref_type === '' || $code === '' || $label === '') {
            set_flash('error', 'Заполните справочник полностью.');
            redirect('admin.php');
        }
        $stmt = $db->prepare(
            'INSERT INTO reference_values (ref_type, code, label, is_active, sort_order, created_at)
             VALUES (:ref_type, :code, :label, 1, :sort_order, :created_at)'
        );
        $stmt->execute([
            ':ref_type' => $ref_type,
            ':code' => $code,
            ':label' => $label,
            ':sort_order' => $sort_order,
            ':created_at' => date('Y-m-d H:i:s'),
        ]);
        set_flash('success', 'Запись справочника добавлена.');
        redirect('admin.php');
    }
}

$roles = $db->query('SELECT * FROM roles ORDER BY name')->fetchAll();
$users = $db->query(
    'SELECT u.*, r.name AS role_name
     FROM users u
     JOIN roles r ON r.id = u.role_id
     ORDER BY u.created_at DESC'
)->fetchAll();
$references = $db->query('SELECT * FROM reference_values ORDER BY ref_type, sort_order, label')->fetchAll();

render_header('Админка', $user, 'admin');
?>
<div class="grid grid-2">
    <div class="card">
        <h3>Пользователи</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>Имя</th>
                    <th>Email</th>
                    <th>Роль</th>
                    <th>Статус</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $row): ?>
                <tr>
                    <td><?= e($row['name']) ?></td>
                    <td><?= e($row['email']) ?></td>
                    <td><?= e($row['role_name']) ?></td>
                    <td>
                        <form method="post">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="toggle_user">
                            <input type="hidden" name="user_id" value="<?= (int) $row['id'] ?>">
                            <input type="hidden" name="is_active" value="<?= $row['is_active'] ? 0 : 1 ?>">
                            <button class="btn btn-secondary" type="submit">
                                <?= $row['is_active'] ? 'Активен' : 'Отключен' ?>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$users): ?>
                <tr><td colspan="4" class="muted">Пользователи отсутствуют.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card">
        <h3>Создать пользователя</h3>
        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create_user">
            <div class="form-row">
                <label for="name">Имя</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="form-row">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-row">
                <label for="password">Пароль</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-row">
                <label for="role_id">Роль</label>
                <select id="role_id" name="role_id" required>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?= (int) $role['id'] ?>"><?= e($role['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="actions">
                <button class="btn btn-primary" type="submit">Создать</button>
            </div>
        </form>
    </div>
</div>

<div class="section-title">Роли</div>
<div class="grid grid-2">
    <div class="card">
        <table class="table">
            <thead>
                <tr>
                    <th>Роль</th>
                    <th>Описание</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($roles as $role): ?>
                <tr>
                    <td><?= e($role['name']) ?></td>
                    <td><?= e($role['description'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="card">
        <h3>Добавить роль</h3>
        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create_role">
            <div class="form-row">
                <label for="role_name">Название роли</label>
                <input type="text" id="role_name" name="role_name" required>
            </div>
            <div class="form-row">
                <label for="role_description">Описание</label>
                <input type="text" id="role_description" name="role_description">
            </div>
            <div class="actions">
                <button class="btn btn-primary" type="submit">Добавить</button>
            </div>
        </form>
    </div>
</div>

<div class="section-title">Справочники</div>
<div class="grid grid-2">
    <div class="card">
        <table class="table">
            <thead>
                <tr>
                    <th>Тип</th>
                    <th>Код</th>
                    <th>Значение</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($references as $ref): ?>
                <tr>
                    <td><?= e($ref['ref_type']) ?></td>
                    <td><?= e($ref['code']) ?></td>
                    <td><?= e($ref['label']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$references): ?>
                <tr><td colspan="3" class="muted">Справочники пусты.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card">
        <h3>Добавить запись</h3>
        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create_reference">
            <div class="form-row">
                <label for="ref_type">Тип (например, currency)</label>
                <input type="text" id="ref_type" name="ref_type" required>
            </div>
            <div class="form-row inline-2">
                <div>
                    <label for="ref_code">Код</label>
                    <input type="text" id="ref_code" name="ref_code" required>
                </div>
                <div>
                    <label for="ref_label">Название</label>
                    <input type="text" id="ref_label" name="ref_label" required>
                </div>
            </div>
            <div class="form-row">
                <label for="sort_order">Сортировка</label>
                <input type="number" id="sort_order" name="sort_order" value="0">
            </div>
            <div class="actions">
                <button class="btn btn-primary" type="submit">Сохранить</button>
            </div>
        </form>
    </div>
</div>

<?php render_footer(); ?>
