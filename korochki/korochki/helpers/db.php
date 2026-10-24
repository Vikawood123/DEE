<?php
/**
 * db.php — подключение к базе данных «korochki».
 * Путь: /opt/lampp/htdocs/DEE/korochki/korochki/helpers/db.php
 *
 * Возвращает объект PDO (singleton). Использование:
 *   require_once __DIR__ . '/../helpers/db.php';
 *   $pdo = db();
 */

// --- Параметры подключения ---
const DB_HOST    = '127.0.0.1';
const DB_PORT    = 3306;
const DB_NAME    = 'korochki';
const DB_USER    = 'root';
const DB_PASS    = '';          // в XAMPP по умолчанию пусто
const DB_CHARSET = 'utf8mb4';

/**
 * Возвращает единый экземпляр PDO.
 *
 * @return PDO
 */
function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        DB_HOST,
        DB_PORT,
        DB_NAME,
        DB_CHARSET
    );

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        http_response_code(500);
        exit('Ошибка подключения к базе данных: ' . $e->getMessage());
    }

    return $pdo;
}