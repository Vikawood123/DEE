<?php
session_start();
require_once __DIR__ . '/../helpers/db.php';
if (!empty($_SESSION['user_id'])) { header('Location: index.php'); exit; }

$errors = [];
$login = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    if ($login === '' || $password === '') {
        $errors['common'] = 'Заполните все поля.';
    } else {
        try {
            $pdo = db();
            $stmt = $pdo->prepare('SELECT p.*, r.rol AS role_name FROM ppolzovateli p LEFT JOIN rroli r ON r.id_roli = p.roli_id WHERE p.login = ? LIMIT 1');
            $stmt->execute([$login]);
            $user = $stmt->fetch();

            // Проверяем, существует ли пользователь и совпадает ли пароль
            if (!$user || !hash_equals((string)$user['parol'], $password)) {
                $errors['common'] = 'Неверный логин или пароль.';
            } else {
                // Сохраняем данные в сессию
                $_SESSION['user_id'] = $user['id_polzovateli'];
                $_SESSION['login'] = $user['login'];
                $_SESSION['fio'] = $user['fio'];
                $_SESSION['role'] = $user['role_name'];
                
                // Перенаправляем: админа в админку, остальных на главную
                if ($user['role_name'] === 'администратор') {
                    header('Location: admin.php');
                } else {
                    header('Location: index.php');
                }
                exit;
            }
        } catch (Throwable $e) { 
            $errors['common'] = 'Ошибка БД: ' . $e->getMessage(); 
        }
    }
}

$pageTitle = 'Авторизация — Пересдачи';
$breadcrumbs = ['Главная' => 'index.php', 'Вход' => null];
ob_start();
?>
<section class="auth">
    <h1 class="auth-title">Вход</h1>
    <?php if (!empty($errors['common'])): ?><div class="alert alert-error"><?= htmlspecialchars($errors['common']) ?></div><?php endif; ?>
    <form class="auth-form" method="post">
        <div class="form-group">
            <label>Логин</label>
            <input type="text" name="login" class="form-input" value="<?= htmlspecialchars($login) ?>" required autofocus>
        </div>
        <div class="form-group">
            <label>Пароль</label>
            <input type="password" name="password" class="form-input" required>
        </div>
        <button type="submit" class="btn auth-submit">Войти</button>
        <p class="auth-alt">Еще не зарегистрированы? <a href="register.php">Регистрация</a></p>
    </form>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';