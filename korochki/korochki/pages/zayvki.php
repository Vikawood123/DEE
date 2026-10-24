<?php
/**
 * zayvki.php — просмотр заявок пользователя.
 */

session_start();

require_once __DIR__ . '/../helpers/db.php';

// Если не вошёл — на авторизацию
if (empty($_SESSION['user_id'])) {
    header('Location: /DEE/korochki/korochki/pages/auth.php');
    exit;
}

$pdo = db();
$userId = (int)$_SESSION['user_id'];
$errors = [];
$success = false;

// --- Обработка сохранения (изменения статуса) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $zayavkaId = (int)($_POST['zayavka_id'] ?? 0);
    $newStatus = (int)($_POST['status_id'] ?? 1);

    if ($zayavkaId > 0) {
        try {
            $stmt = $pdo->prepare('UPDATE zayavki SET status_id = ? WHERE id_zayavki = ? AND polzovateli_id = ?');
            $stmt->execute([$newStatus, $zayavkaId, $userId]);
            $success = true;
        } catch (Throwable $e) {
            $errors['common'] = 'Ошибка сохранения: ' . $e->getMessage();
        }
    }
}

// --- Обработка добавления отзыва ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_review') {
    $zayavkaId = (int)($_POST['zayavka_id'] ?? 0);
    $komment   = trim($_POST['komment'] ?? '');
    $zvezda    = (int)($_POST['zvezda'] ?? 5);

    if ($zayavkaId > 0 && $komment !== '') {
        try {
            $pdo->beginTransaction();

            // 1. Создаем отзыв в таблице otziv
            $stmt = $pdo->prepare('INSERT INTO otziv (koment, zvezda) VALUES (?, ?)');
            $stmt->execute([$komment, $zvezda]);
            $otzivId = (int)$pdo->lastInsertId();

            // 2. Привязываем отзыв к заявке (только если это заявка этого пользователя)
            $stmt = $pdo->prepare('UPDATE zayavki SET otziv_id = ? WHERE id_zayavki = ? AND polzovateli_id = ?');
            $stmt->execute([$otzivId, $zayavkaId, $userId]);

            $pdo->commit();
            $success = true;
        } catch (Throwable $e) {
            $pdo->rollBack();
            $errors['common'] = 'Ошибка сохранения отзыва: ' . $e->getMessage();
        }
    } else {
        $errors['common'] = 'Пожалуйста, напишите текст отзыва.';
    }
}

// --- Пагинация ---
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 3; // Количество заявок на одной странице
$offset = ($page - 1) * $limit;

try {
    // Получаем общее количество заявок пользователя
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM zayavki WHERE polzovateli_id = ?');
    $stmt->execute([$userId]);
    $totalRows = (int)$stmt->fetchColumn();
    $totalPages = ceil($totalRows / $limit);

    // Получаем заявки с учетом лимита и ПОДТЯГИВАЕМ ОТЗЫВЫ
    $sql = 'SELECT z.id_zayavki, z.data_nachalo, z.status_id, z.otziv_id,
                   c.name_course, 
                   p.method AS payment_method,
                   s.status_name,
                   o.koment AS otziv_text, o.zvezda AS otziv_stars
            FROM zayavki z
            LEFT JOIN courses c ON c.id_course = z.kursi_id
            LEFT JOIN playments p ON p.id_payment = z.oplati_id
            LEFT JOIN statuses s ON s.id_status = z.status_id
            LEFT JOIN otziv o ON o.id_otziv = z.otziv_id
            WHERE z.polzovateli_id = ?
            ORDER BY z.id_zayavki DESC
            LIMIT ' . $limit . ' OFFSET ' . $offset;
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    $zayavki = $stmt->fetchAll();

    // Список статусов для выпадающего меню
    $statuses = $pdo->query('SELECT id_status, status_name FROM statuses')->fetchAll();
} catch (Throwable $e) {
    $errors['common'] = 'Ошибка загрузки заявок: ' . $e->getMessage();
    $zayavki = [];
    $statuses = [];
}

// Метаданные для layout
$pageTitle = 'Мои заявки — Корочки.есть';
$breadcrumbs = [
    'Главная'        => '/DEE/korochki/korochki/pages/index.php',
    'Мои заявки'     => null,
];

ob_start();
?>
<section class="zayavki-page" style="max-width: 1000px; margin: 0 auto; padding: 20px;">

    <h1 class="auth-title" style="text-align: center;">Мои заявки</h1>

    <?php if (!empty($errors['common'])): ?>
        <div class="alert alert-error" style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 15px; border: 1px solid #f5c6cb;">
            <?= htmlspecialchars($errors['common']) ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success" style="background: #d4edda; color: #155724; border: 1px solid #c3e6cb; padding: 10px; border-radius: 4px; margin-bottom: 15px;">
            ✅ Данные успешно сохранены!
        </div>
    <?php endif; ?>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <p style="font-size: 14px;">
            «Нет вашей заявки?»<br>
            <a href="/DEE/korochki/korochki/pages/new_zayvka.php">создать</a>
        </p>
        <a href="/DEE/korochki/korochki/pages/new_zayvka.php" class="btn" style="background: #e1d5e7; color: #4a3a6b; border: 1px solid #9673a6; padding: 8px 20px; border-radius: 4px; text-decoration: none;">
            Создать заявку
        </a>
    </div>

    <?php if (empty($zayavki)): ?>
        <div style="text-align: center; padding: 40px; background: #f9f9f9; border: 1px dashed #ccc; border-radius: 8px;">
            <p>У вас пока нет созданных заявок.</p>
        </div>
    <?php else: ?>
        <!-- Сетка заявок -->
        <div style="display: flex; flex-wrap: wrap; gap: 20px; justify-content: center;">
            <?php foreach ($zayavki as $z): ?>
                <div class="zayavka-card" style="background: #fff; border: 1px solid #ccc; border-radius: 8px; width: 260px; display: flex; flex-direction: column;">
                    
                    <div style="background: #f0f0f0; padding: 8px 12px; border-bottom: 1px solid #ccc; border-radius: 8px 8px 0 0; font-size: 13px; font-weight: bold;">
                        заявка <?= $z['id_zayavki'] ?>
                    </div>

                    <div style="padding: 12px; font-size: 13px; flex-grow: 1;">
                        <div style="margin-bottom: 8px;">
                            <span style="color: #777; font-size: 11px; display: block;">наименование курса</span>
                            <?= htmlspecialchars($z['name_course'] ?? 'Не указан') ?>
                        </div>
                        
                        <div style="margin-bottom: 8px;">
                            <span style="color: #777; font-size: 11px; display: block;">статус</span>
                            <?php 
                                $statusId = $z['status_id'];
                                $statusName = $z['status_name'] ?? 'Неизвестно';
                                if ($statusId == 1) { $badgeColor = '#ffc107'; $textColor = '#000'; }
                                elseif ($statusId == 2) { $badgeColor = '#0085fc'; $textColor = '#fff'; }
                                elseif ($statusId == 3) { $badgeColor = '#28a745'; $textColor = '#fff'; }
                                else { $badgeColor = '#e9ecef'; $textColor = '#333'; }
                            ?>
                            <span style="background: <?= $badgeColor ?>; color: <?= $textColor ?>; padding: 2px 8px; border-radius: 12px; font-size: 12px; font-weight: bold; display: inline-block;">
                                <?= htmlspecialchars($statusName) ?>
                            </span>
                        </div>
                        
                        <div style="margin-bottom: 8px;">
                            <span style="color: #777; font-size: 11px; display: block;">способ оплаты</span>
                            <?= htmlspecialchars($z['payment_method'] ?? 'Не указан') ?>
                        </div>
                        
                        <div style="margin-bottom: 8px;">
                            <span style="color: #777; font-size: 11px; display: block;">дата начала</span>
                            <?= htmlspecialchars($z['data_nachalo']) ?>
                        </div>

                        <!-- Блок отзыва (если он уже есть) -->
                        <?php if (!empty($z['otziv_id'])): ?>
                            <div style="margin-top: 15px; padding-top: 10px; border-top: 1px dashed #ccc;">
                                <span style="color: #777; font-size: 11px; display: block;">Ваш отзыв</span>
                                <div style="font-weight: bold; color: #f39c12; margin-bottom: 4px;">
                                    <?= str_repeat('★', (int)$z['otziv_stars']) . str_repeat('☆', 5 - (int)$z['otziv_stars']) ?>
                                </div>
                                <div style="font-size: 12px; font-style: italic;">
                                    "<?= htmlspecialchars($z['otziv_text']) ?>"
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Подвал карточки -->
                    <div style="padding: 12px; border-top: 1px dashed #eee; background: #fafafa; border-radius: 0 0 8px 8px;">
                        
                        <!-- Если отзыва нет и обучение завершено (status 3), показываем форму -->
                        <?php if (empty($z['otziv_id']) && $z['status_id'] == 3): ?>
                            <form method="post" style="margin: 0;">
                                <input type="hidden" name="action" value="add_review">
                                <input type="hidden" name="zayavka_id" value="<?= $z['id_zayavki'] ?>">
                                
                                <label style="font-size: 12px; color: #555;">Ваша оценка:</label>
                                <select name="zvezda" class="form-input" style="width: 100%; padding: 4px; margin-bottom: 5px; font-size: 12px;">
                                    <option value="5">★★★★★ (5)</option>
                                    <option value="4">★★★★☆ (4)</option>
                                    <option value="3">★★★☆☆ (3)</option>
                                    <option value="2">★★☆☆☆ (2)</option>
                                    <option value="1">★☆☆☆☆ (1)</option>
                                </select>

                                <label style="font-size: 12px; color: #555;">Комментарий:</label>
                                <textarea name="komment" class="form-input" style="width: 100%; height: 50px; padding: 4px; margin-bottom: 5px; font-size: 12px;" required></textarea>
                                
                                <button type="submit" class="btn" style="background: #28a745; color: #fff; border: none; width: 100%; font-size: 12px; padding: 5px; cursor: pointer;">
                                    Оставить отзыв
                                </button>
                            </form>
                        <?php elseif (empty($z['otziv_id'])): ?>
                            <p style="font-size: 11px; color: #999; text-align: center; margin: 0;">Отзыв можно оставить после завершения обучения.</p>
                        <?php endif; ?>

                        <!-- Форма смены статуса (для теста) -->
                        <form method="post" style="margin: 0; margin-top: 10px; border-top: 1px dashed #ddd; padding-top: 10px;">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="zayavka_id" value="<?= $z['id_zayavki'] ?>">
                            
                            <select name="status_id" class="form-input" style="width: 100%; font-size: 11px; padding: 3px; margin-bottom: 4px; color: #666;">
                                <?php foreach ($statuses as $s): ?>
                                    <option value="<?= $s['id_status'] ?>" <?= ($z['status_id'] == $s['id_status']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($s['status_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            
                            <button type="submit" class="btn" style="background: #f0f0f0; color: #666; border: 1px solid #ccc; width: 100%; font-size: 11px; padding: 3px; cursor: pointer;">
                                сменить статус
                            </button>
                        </form>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>

        <!-- Пагинация -->
        <?php if ($totalPages > 1): ?>
            <div style="text-align: center; margin-top: 30px;">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?page=<?= $i ?>" 
                       style="display: inline-block; padding: 5px 12px; margin: 0 3px; border: 1px solid #ccc; text-decoration: none; border-radius: 4px; <?= $i == $page ? 'background: #0085fc; color: white;' : 'color: #333;' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>

    <?php endif; ?>

</section>
<?php
$content = ob_get_clean();

require __DIR__ . '/layout.php';