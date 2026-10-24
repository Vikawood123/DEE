<?php
/**
 * register.php — регистрация нового пользователя.
 */

session_start();

require_once __DIR__ . '/../helpers/db.php';

// Если уже вошёл — на главную
if (!empty($_SESSION['user_id'])) {
    header('Location: /DEE/korochki/korochki/pages/index.php');
    exit;
}

$errors = [];
$login   = '';
$fio     = '';
$pochta  = '';
$telefon = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login   = trim($_POST['login']   ?? '');
    $fio     = trim($_POST['fio']     ?? '');
    $pochta  = trim($_POST['pochta']  ?? '');
    $telefon = trim($_POST['telefon'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $confirm_password = (string)($_POST['confirm_password'] ?? '');

    // --- проверки ---
    if ($login === '') {
        $errors['login'] = 'Введите логин.';
    } elseif (!preg_match('/^[A-Za-z0-9_]{3,50}$/', $login)) {
        $errors['login'] = 'Логин: латиница, цифры или «_», от 3 до 50 символов.';
    }

    if ($password === '') {
        $errors['password'] = 'Введите пароль.';
    } elseif (mb_strlen($password) < 8) {
        $errors['password'] = 'Минимум 8 символов.';
    } elseif ($password !== $confirm_password) {
        $errors['password'] = 'Пароли не совпадают.';
    }

    if ($fio === '') {
        $errors['fio'] = 'Введите ФИО.';
    } elseif (!preg_match('/^[А-Яа-яЁё\s\-]+$/u', $fio)) {
        $errors['fio'] = 'Только кириллица и пробелы.';
    }

    if ($pochta === '') {
        $errors['pochta'] = 'Введите адрес электронной почты.';
    } elseif (!filter_var($pochta, FILTER_VALIDATE_EMAIL)) {
        $errors['pochta'] = 'Неверный адрес электронной почты.';
    }

    if ($telefon === '') {
        $errors['telefon'] = 'Введите телефон.';
    } elseif (!preg_match('/^8\(\d{3}\)\d{3}-\d{2}-\d{2}$/', $telefon)) {
        $errors['telefon'] = 'Формат: 8(XXX)XXX-XX-XX.';
    }

    // --- уникальность ---
    if (!$errors) {
        try {
            $pdo = db();

            $stmt = $pdo->prepare('SELECT COUNT(*) FROM polzovateli WHERE login = ?');
            $stmt->execute([$login]);
            if ((int)$stmt->fetchColumn() > 0) {
                $errors['login'] = 'Такой логин уже занят.';
            }

            $stmt = $pdo->prepare('SELECT COUNT(*) FROM polzovateli WHERE pochta = ?');
            $stmt->execute([$pochta]);
            if ((int)$stmt->fetchColumn() > 0) {
                $errors['pochta'] = 'Эта почта уже используется.';
            }
        } catch (Throwable $e) {
            $errors['common'] = 'Ошибка сервера: ' . $e->getMessage();
        }
    }

    // --- сохраняем ---
    if (!$errors) {
        try {
            $pdo = db();

            $stmt = $pdo->prepare(
                'INSERT INTO polzovateli (login, parol, fio, telefon, pochta, roli_id)
                 VALUES (?, ?, ?, ?, ?, 1)'
            );
            $stmt->execute([
                $login,
                md5($password),
                $fio,
                $telefon,
                $pochta,
            ]);

            $_SESSION['user_id'] = (int)$pdo->lastInsertId();
            $_SESSION['login']   = $login;
            $_SESSION['fio']     = $fio;
            $_SESSION['telefon'] = $telefon;
            $_SESSION['pochta']  = $pochta;
            $_SESSION['roli_id'] = 1;
            $_SESSION['role']    = 'пользователь';

            header('Location: /DEE/korochki/korochki/pages/index.php');
            exit;
        } catch (Throwable $e) {
            $errors['common'] = 'Не удалось сохранить: ' . $e->getMessage();
        }
    }
}

$pageTitle   = 'Регистрация — Корочки.есть';
$breadcrumbs = [
    'Главная'     => '/DEE/korochki/korochki/pages/index.php',
    'Регистрация' => null,
];
$navButtons = [
    'вход'   => '/DEE/korochki/korochki/pages/auth.php',
    'главная'=> '/DEE/korochki/korochki/pages/index.php',
];

ob_start();
?>
<section class="auth">
    <h1 class="auth-title">Регистрация</h1>

    <?php if (!empty($errors['common'])): ?>
        <div class="alert alert-error"><?= htmlspecialchars($errors['common']) ?></div>
    <?php endif; ?>

    <form class="auth-form" method="post" novalidate>

        <div class="form-group">
            <label for="login">Логин</label>
            <input type="text" id="login" name="login"
                   class="form-input <?= isset($errors['login']) ? 'is-invalid' : '' ?>"
                   value="<?= htmlspecialchars($login) ?>" autocomplete="username" autofocus>
            <?php if (isset($errors['login'])): ?>
                <span class="form-error"><?= htmlspecialchars($errors['login']) ?></span>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="password">Пароль</label>
            <input type="password" id="password" name="password"
                   class="form-input <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                   autocomplete="new-password">
            <?php if (isset($errors['password'])): ?>
                <span class="form-error"><?= htmlspecialchars($errors['password']) ?></span>
            <?php else: ?>
                <span class="form-hint">Минимум 8 символов</span>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="confirm_password">Подтверждение пароля</label>
            <input type="password" id="confirm_password" name="confirm_password"
                   class="form-input <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                   autocomplete="new-password">
        </div>

        <div class="form-group">
            <label for="fio">ФИО</label>
            <input type="text" id="fio" name="fio"
                   class="form-input <?= isset($errors['fio']) ? 'is-invalid' : '' ?>"
                   value="<?= htmlspecialchars($fio) ?>" autocomplete="name">
            <?php if (isset($errors['fio'])): ?>
                <span class="form-error"><?= htmlspecialchars($errors['fio']) ?></span>
            <?php else: ?>
                <span class="form-hint">Только кириллица и пробелы</span>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="pochta">Адрес электронной почты</label>
            <input type="email" id="pochta" name="pochta"
                   class="form-input <?= isset($errors['pochta']) ? 'is-invalid' : '' ?>"
                   value="<?= htmlspecialchars($pochta) ?>" autocomplete="email">
            <?php if (isset($errors['pochta'])): ?>
                <span class="form-error"><?= htmlspecialchars($errors['pochta']) ?></span>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="telefon">Телефон</label>
            <input type="text" id="telefon" name="telefon"
                   class="form-input <?= isset($errors['telefon']) ? 'is-invalid' : '' ?>"
                   value="<?= htmlspecialchars($telefon) ?>"
                   placeholder="8(XXX)XXX-XX-XX" autocomplete="tel">
            <?php if (isset($errors['telefon'])): ?>
                <span class="form-error"><?= htmlspecialchars($errors['telefon']) ?></span>
            <?php endif; ?>
        </div>

        <button type="submit" class="btn btn-primary auth-submit">Зарегистрироваться</button>

        <p class="auth-alt">
            Уже есть аккаунт?
            <a href="/DEE/korochki/korochki/pages/auth.php">Войти</a>
        </p>
    </form>
</section>

<!-- СКРИПТ МАСКИ ТЕЛЕФОНА -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const phoneInput = document.getElementById('telefon');
    if (phoneInput) {
        phoneInput.addEventListener('input', function (e) {
            let digits = e.target.value.replace(/\D/g, '');
            if (digits.length > 0 && digits[0] !== '8') digits = '8' + digits;
            if (digits.length > 11) digits = digits.substring(0, 11);

            let res = '';
            if (digits.length > 0) res += digits[0]; 
            if (digits.length > 1) res += '(' + digits.substring(1, 4); 
            if (digits.length >= 5) res += ')'; 
            if (digits.length > 4) res += digits.substring(4, 7); 
            if (digits.length > 7) res += '-' + digits.substring(7, 9); 
            if (digits.length > 9) res += '-' + digits.substring(9, 11); 

            e.target.value = res;
        });
        phoneInput.addEventListener('keypress', function(e) {
            if (!/\d/.test(e.key)) e.preventDefault();
        });
    }
});
</script>

<?php
$content = ob_get_clean();

require __DIR__ . '/layout.php';