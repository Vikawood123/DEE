<?php
/**
 * layout.php — общий каркас страниц (шапка + подвал).
 * Путь: /opt/lampp/htdocs/DEE/korochki/korochki/pages/layout.php
 */

// Сессия должна быть запущена, но не дублируем вызов
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($pageTitle))   { $pageTitle = 'Корочки.есть'; }
if (!isset($breadcrumbs)) { $breadcrumbs = []; }
if (!isset($navButtons))  { $navButtons = []; }

// Базовый URL проекта
$baseUrl = '/DEE/korochki/korochki';

// Определяем состояние авторизации
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

<!-- ===================== ШАПКА ===================== -->
<header class="site-header">
    <!-- Логотип -->
    <div class="header-logo">
        <a href="<?= $baseUrl ?>/pages/index.php">
            <img src="<?= $baseUrl ?>/media/image02.jpg"
                 alt="Корочки.есть"
                 class="logo-img">
        </a>
    </div>

    <!-- Название -->
    <div class="header-title">
        <a href="<?= $baseUrl ?>/pages/index.php">корочки.есть</a>
    </div>

    <!-- Кнопки навигации -->
    <nav class="header-nav">
        <?php if ($isLoggedIn): ?>
            <span class="user-info"><?= htmlspecialchars($userName) ?></span>
            
            <?php if ($userRole === 'администратор'): ?>
                <!-- Для админа показываем только админку -->
                <a href="<?= $baseUrl ?>/pages/admin.php" class="nav-btn">админка</a>
            <?php else: ?>
                <!-- Для обычного пользователя показываем заявки -->
                <a href="<?= $baseUrl ?>/pages/new_zayvka.php" class="nav-btn">создать заявку</a>
                <a href="<?= $baseUrl ?>/pages/zayvki.php" class="nav-btn">мои заявки</a>
            <?php endif; ?>
            
            <a href="<?= $baseUrl ?>/pages/logout.php" class="nav-btn">выход</a>
        <?php elseif (!empty($navButtons)): ?>
            <?php foreach ($navButtons as $text => $url): ?>
                <a href="<?= htmlspecialchars($url) ?>" class="nav-btn">
                    <?= htmlspecialchars($text) ?>
                </a>
            <?php endforeach; ?>
        <?php else: ?>
            <a href="<?= $baseUrl ?>/pages/register.php" class="nav-btn">регистрация</a>
            <a href="<?= $baseUrl ?>/pages/auth.php"     class="nav-btn">вход</a>
        <?php endif; ?>
    </nav>

    <!-- Хлебные крошки -->
    <?php if (!empty($breadcrumbs)): ?>
        <div class="breadcrumbs">
            <?php
            $crumbs = [];
            foreach ($breadcrumbs as $text => $url) {
                if ($url === null) {
                    $crumbs[] = '<span class="crumb-current">' . htmlspecialchars($text) . '</span>';
                } else {
                    $crumbs[] = '<a href="' . htmlspecialchars($url) . '">' . htmlspecialchars($text) . '</a>';
                }
            }
            echo implode(' / ', $crumbs);
            ?>
        </div>
    <?php endif; ?>

</header>

<!-- ===================== КОНТЕНТ ===================== -->
<main class="site-main">
    <?= $content ?? '' ?>
</main>

<!-- ===================== ПОДВАЛ ===================== -->
<footer class="site-footer">
    <div class="footer-inner">
        <div class="footer-col">
            <strong>Корочки.есть</strong><br>
            <span style="font-size: 12px; color: #aaa;">Информационная система для записи на онлайн курсы дополнительного профессионального образования.</span>
        </div>
        <div class="footer-col">
            <strong>Контакты</strong><br>
            <span style="font-size: 12px; color: #aaa;">Email: support@korochki.est</span><br>
            <span style="font-size: 12px; color: #aaa;">Тел: 8 (800) 555-35-35</span>
        </div>
        <div class="footer-col">
            &copy; <?= date('Y') ?> Корочки.есть.<br>
            <span style="font-size: 12px; color: #aaa;">Все права защищены.</span>
        </div>
    </div>
</footer>

<script src="<?= $baseUrl ?>/scripts/script.js"></script>
</body>
</html>