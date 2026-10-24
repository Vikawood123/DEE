<?php
/**
 * auth.php — страница авторизации.
 * Путь: /opt/lampp/htdocs/DEE/korochki/korochki/pages/auth.php
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

session_start();

require_once __DIR__ . '/../helpers/db.php';

// --- Уже авторизован? Отправляем дальше по роли ---
if (!empty($_SESSION['user_id'])) {
    $redirect = (($_SESSION['role'] ?? '') === 'администратор')
        ? '/DEE/korochki/korochki/pages/admin.php'
        : '/DEE/korochki/korochki/pages/index.php';
    header('Location: ' . $redirect);
    exit;
}

$errors = [];
$login  = '';

// --- Обработка формы ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login    = trim($_POST['login'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    // 1. Валидация
    if ($login === '') {
        $errors['login'] = 'Введите логин.';
    }
    if ($password === '') {
        $errors['password'] = 'Введите пароль.';
    } elseif (mb_strlen($password) < 8) {
        $errors['password'] = 'Пароль должен содержать минимум 8 символов.';
    }

    // 2. Проверка учётных данных
    if (!$errors) {
        try {
            $pdo = db();
            $sql = 'SELECT p.id_polzovateli,
                           p.login,
                           p.parol,
                           p.fio,
                           p.telefon,
                           p.pochta,
                           p.roli_id,
                           r.rol AS role_name
                    FROM polzovateli AS p
                    LEFT JOIN roli AS r ON r.id_roli = p.roli_id
                    WHERE p.login = :login
                    LIMIT 1';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':login' => $login]);
            $user = $stmt->fetch();

            if (!$user) {
                $errors['login'] = 'Неверный логин.';
            } else {
                $passwordOk = hash_equals(
                    strtolower((string)$user['parol']),
                    md5($password)
                );

                if (!$passwordOk) {
                    $errors['password'] = 'Неверный пароль.';
                } else {
                    $_SESSION['user_id'] = (int)$user['id_polzovateli'];
                    $_SESSION['login']   = $user['login'];
                    $_SESSION['fio']     = $user['fio'];
                    $_SESSION['telefon'] = $user['telefon'];
                    $_SESSION['pochta']  = $user['pochta'];
                    $_SESSION['roli_id'] = (int)($user['roli_id'] ?? 0);
                    $_SESSION['role']    = $user['role_name'] ?? 'пользователь';

                    $redirect = $_SESSION['role'] === 'администратор'
                        ? '/DEE/korochki/korochki/pages/admin.php'
                        : '/DEE/korochki/korochki/pages/index.php';

                    header('Location: ' . $redirect);
                    exit;
                }
            }
        } catch (Throwable $e) {
            $errors['common'] = 'Ошибка сервера: ' . $e->getMessage();
        }
    }
}

// --- Метаданные для layout ---
$pageTitle = 'Авторизация — Корочки.есть';

$breadcrumbs = [
    'Главная'     => '/DEE/korochki/korochki/pages/index.php',
    'Авторизация' => null,
];

$navButtons = [
    'регистрация' => '/DEE/korochki/korochki/pages/register.php',
    'главная'     => '/DEE/korochki/korochki/pages/index.php',
];

// --- Контент страницы ---
ob_start();
?>
<section class="auth">

    <h1 class="auth-title">Авторизация</h1>

    <?php if (!empty($errors['common'])): ?>
        <div class="alert alert-error"><?= htmlspecialchars($errors['common']) ?></div>
    <?php endif; ?>

    <form class="auth-form" method="post" action="" novalidate>

        <div class="form-group">
            <label for="login">Логин</label>
            <input type="text"
                   id="login"
                   name="login"
                   class="form-input <?= isset($errors['login']) ? 'is-invalid' : '' ?>"
                   value="<?= htmlspecialchars($login) ?>"
                   autocomplete="username"
                   autofocus>
            <?php if (isset($errors['login'])): ?>
                <span class="form-error"><?= htmlspecialchars($errors['login']) ?></span>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="password">Пароль</label>
            <input type="password"
                   id="password"
                   name="password"
                   class="form-input <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                   autocomplete="current-password">
            <?php if (isset($errors['password'])): ?>
                <span class="form-error"><?= htmlspecialchars($errors['password']) ?></span>
            <?php endif; ?>
        </div>

        <button type="submit" class="btn btn-primary auth-submit">Войти</button>

        <p class="auth-alt">
            Ещё не зарегистрированы?
            <a href="/DEE/korochki/korochki/pages/register.php">Регистрация</a>
        </p>

    </form>
</section>
<?php
$content = ob_get_clean();

require __DIR__ . '/layout.php';