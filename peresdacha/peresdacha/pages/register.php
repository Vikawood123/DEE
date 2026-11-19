<?php
session_start();
require_once __DIR__ . '/../helpers/db.php';
if (!empty($_SESSION['user_id'])) { header('Location: index.php'); exit; }

$errors = [];
$login = $fio = $pochta = $telefon = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $fio = trim($_POST['fio'] ?? '');
    $pochta = trim($_POST['pochta'] ?? '');
    $telefon = trim($_POST['telefon'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');

    if (strlen($login) < 6) $errors['login'] = 'Логин должен быть не менее 6 символов.';
    elseif (!preg_match('/^[A-Za-z0-9]+$/', $login)) $errors['login'] = 'Только латиница и цифры.';
    
    if (mb_strlen($password) < 8) $errors['password'] = 'Пароль минимум 8 символов.';
    elseif ($password !== $confirm) $errors['password'] = 'Пароли не совпадают.';
    
    if (!preg_match('/^[А-Яа-яЁё\s]+$/u', $fio)) $errors['fio'] = 'ФИО: только кириллица и пробелы.';
    if (!filter_var($pochta, FILTER_VALIDATE_EMAIL)) $errors['pochta'] = 'Неверный формат почты.';
    if (!preg_match('/^8\(\d{3}\)\d{3}-\d{2}-\d{2}$/', $telefon)) $errors['telefon'] = 'Формат: 8(ХХХ)ХХХ-ХХ-ХХ.';

    if (!$errors) {
        try {
            $pdo = db();
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM ppolzovateli WHERE login = ?');
            $stmt->execute([$login]);
            if ($stmt->fetchColumn() > 0) $errors['login'] = 'Такой логин уже занят.';

            if (!$errors) {
                $stmt = $pdo->prepare('INSERT INTO ppolzovateli (login, parol, fio, telefon, pochta, roli_id) VALUES (?, ?, ?, ?, ?, 1)');
                $stmt->execute([$login, md5($password), $fio, $telefon, $pochta]);
                $_SESSION['user_id'] = $pdo->lastInsertId();
                $_SESSION['login'] = $login;
                $_SESSION['fio'] = $fio;
                $_SESSION['role'] = 'пользователь';
                header('Location: index.php'); exit;
            }
        } catch (Throwable $e) { $errors['common'] = 'Ошибка БД: ' . $e->getMessage(); }
    }
}

$pageTitle = 'Регистрация — Пересдачи';
$breadcrumbs = ['Главная' => 'index.php', 'Регистрация' => null];
ob_start();
?>
<section class="auth">
    <h1 class="auth-title">Регистрация</h1>
    <?php if (!empty($errors['common'])): ?><div class="alert alert-error"><?= htmlspecialchars($errors['common']) ?></div><?php endif; ?>
    <form class="auth-form" method="post">
        <div class="form-group">
            <label>Логин (латиница и цифры, от 6 символов)</label>
            <input type="text" name="login" class="form-input <?= isset($errors['login']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars($login) ?>" required>
            <?php if (isset($errors['login'])): ?><span class="form-error"><?= $errors['login'] ?></span><?php endif; ?>
        </div>
        <div class="form-group">
            <label>Пароль (минимум 8 символов)</label>
            <input type="password" name="password" class="form-input <?= isset($errors['password']) ? 'is-invalid' : '' ?>" required>
            <?php if (isset($errors['password'])): ?><span class="form-error"><?= $errors['password'] ?></span><?php endif; ?>
        </div>
        <div class="form-group">
            <label>Подтверждение пароля</label>
            <input type="password" name="confirm_password" class="form-input" required>
        </div>
        <div class="form-group">
            <label>ФИО (кириллица и пробелы)</label>
            <input type="text" name="fio" class="form-input <?= isset($errors['fio']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars($fio) ?>" required>
            <?php if (isset($errors['fio'])): ?><span class="form-error"><?= $errors['fio'] ?></span><?php endif; ?>
        </div>
        <div class="form-group">
            <label>Телефон</label>
            <input type="text" id="telefon" name="telefon" class="form-input <?= isset($errors['telefon']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars($telefon) ?>" placeholder="8(ХХХ)ХХХ-ХХ-ХХ" required>
            <?php if (isset($errors['telefon'])): ?><span class="form-error"><?= $errors['telefon'] ?></span><?php endif; ?>
        </div>
        <div class="form-group">
            <label>Электронная почта</label>
            <input type="email" name="pochta" class="form-input <?= isset($errors['pochta']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars($pochta) ?>" required>
            <?php if (isset($errors['pochta'])): ?><span class="form-error"><?= $errors['pochta'] ?></span><?php endif; ?>
        </div>
        <button type="submit" class="btn auth-submit">Создать пользователя</button>
        <p class="auth-alt">Уже есть аккаунт? <a href="auth.php">Войти</a></p>
    </form>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';