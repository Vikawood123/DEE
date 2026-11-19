<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($pageTitle))   { $pageTitle = 'Пересдачи'; }
if (!isset($breadcrumbs)) { $breadcrumbs = []; }
$baseUrl = '/DEE/peresdacha/'; // Проверь свой URL в браузере!
$isLoggedIn = !empty($_SESSION['user_id']);
$userName   = $_SESSION['fio'] ?? ($_SESSION['login'] ?? '');
$userRole   = $_SESSION['role'] ?? 'пользователь';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="<?= $baseUrl ?>/styles/style.css">
</head>
<body>
<header class="site-header">
    <div class="header-logo">
        <a href="<?= $baseUrl ?>/pages/index.php">
            <img src="<?= $baseUrl ?>/media/image02.jpg" alt="Логотип" class="logo-img">
        </a>
    </div>
    <div class="header-title">
        <a href="<?= $baseUrl ?>/pages/index.php">Портал Пересдачи</a>
    </div>
    <nav class="header-nav">
        <?php if ($isLoggedIn): ?>
            <span class="user-info"><?= htmlspecialchars($userName) ?></span>
            <?php if ($userRole === 'администратор'): ?>
                <a href="<?= $baseUrl ?>/pages/admin.php" class="nav-btn primary">Панель администратора</a>
            <?php else: ?>
                <a href="<?= $baseUrl ?>/pages/new_zayvka.php" class="nav-btn primary">Создать заявку</a>
                <a href="<?= $baseUrl ?>/pages/zayvki.php" class="nav-btn">Мои заявки</a>
            <?php endif; ?>
            <a href="<?= $baseUrl ?>/pages/logout.php" class="nav-btn">Выход</a>
        <?php else: ?>
            <a href="<?= $baseUrl ?>/pages/register.php" class="nav-btn">Регистрация</a>
            <a href="<?= $baseUrl ?>/pages/auth.php" class="nav-btn primary">Вход</a>
        <?php endif; ?>
    </nav>
    <?php if (!empty($breadcrumbs)): ?>
        <div class="breadcrumbs">
            <?php
            $crumbs = [];
            foreach ($breadcrumbs as $text => $url) {
                if ($url === null) $crumbs[] = '<span class="crumb-current">' . htmlspecialchars($text) . '</span>';
                else $crumbs[] = '<a href="' . htmlspecialchars($url) . '">' . htmlspecialchars($text) . '</a>';
            }
            echo implode(' / ', $crumbs);
            ?>
        </div>
    <?php endif; ?>
</header>
<main class="site-main"><?= $content ?? '' ?></main>
<footer class="site-footer">
    <div class="footer-inner">
        <div class="footer-col">
            <strong>Портал «Пересдачи»</strong><br>
            <span>Информационная система для записи на пересдачи экзаменов.</span>
        </div>
        <div class="footer-col">
            <strong>Контакты</strong><br>
            Email: support@peresdachi.ru<br>
            Тел: 8 (800) 555-35-35
        </div>
        <div class="footer-col">
            &copy; <?= date('Y') ?> Пересдачи.<br>
            Все права защищены.
        </div>
    </div>
</footer>
<script src="<?= $baseUrl ?>/scripts/script.js"></script>
</body>
</html>