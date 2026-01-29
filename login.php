<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$db = db();
$stmt = $db->query('SELECT COUNT(*) FROM users');
$has_users = ((int) $stmt->fetchColumn()) > 0;

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? 'login';

    if ($action === 'setup') {
        if ($has_users) {
            $error = 'Первичный пользователь уже создан.';
        } elseif (SETUP_KEY === '') {
            $error = 'SETUP_KEY не настроен на сервере.';
        } elseif (!hash_equals(SETUP_KEY, (string) ($_POST['setup_key'] ?? ''))) {
            $error = 'Неверный ключ первичной настройки.';
        } else {
            $name = get_string($_POST, 'name');
            $email = get_string($_POST, 'email');
            $password = (string) ($_POST['password'] ?? '');
            if ($name === '' || $email === '' || $password === '') {
                $error = 'Заполните все поля.';
            } else {
                $stmt = $db->prepare('SELECT id FROM roles WHERE name = :name LIMIT 1');
                $stmt->execute([':name' => 'admin']);
                $role_id = (int) $stmt->fetchColumn();
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
                $user_id = (int) $db->lastInsertId();
                login_user($user_id);
                redirect('index.php');
            }
        }
    } else {
        $email = get_string($_POST, 'email');
        $password = (string) ($_POST['password'] ?? '');
        $user = authenticate_user($email, $password);
        if (!$user) {
            $error = 'Неверные учетные данные или доступ отключен.';
        } else {
            login_user((int) $user['id']);
            redirect('index.php');
        }
    }
}
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Вход</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
<div class="login-layout">
    <div class="card login-card">
        <h2>Доступ в портал</h2>
        <?php if ($error): ?>
            <div class="flash error"><?= e($error) ?></div>
        <?php endif; ?>
        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="login">
            <div class="form-row">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-row">
                <label for="password">Пароль</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="actions">
                <button class="btn btn-primary" type="submit">Войти</button>
            </div>
        </form>

        <?php if (!$has_users): ?>
            <div class="section-title">Первичная настройка</div>
            <p class="notice">Создайте первого администратора. Требуется SETUP_KEY.</p>
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="setup">
                <div class="form-row">
                    <label for="setup_key">SETUP_KEY</label>
                    <input type="password" id="setup_key" name="setup_key" required>
                </div>
                <div class="form-row">
                    <label for="name">Имя</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-row">
                    <label for="setup_email">Email</label>
                    <input type="email" id="setup_email" name="email" required>
                </div>
                <div class="form-row">
                    <label for="setup_password">Пароль</label>
                    <input type="password" id="setup_password" name="password" required>
                </div>
                <div class="actions">
                    <button class="btn btn-secondary" type="submit">Создать администратора</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
