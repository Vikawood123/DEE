<?php
/**
 * logout.php — выход из учётной записи.
 * Путь: /opt/lampp/htdocs/DEE/korochki/korochki/pages/logout.php
 */

// Стартуем сессию, только если она ещё не активна
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Очищаем все данные сессии
$_SESSION = [];

// Удаляем cookie сессии
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Уничтожаем сессию на сервере
session_destroy();

// Возвращаемся на главную
header('Location: /DEE/korochki/korochki/pages/index.php');
exit;