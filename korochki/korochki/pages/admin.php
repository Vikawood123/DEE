<?php
/**
 * admin.php — панель администратора (просмотр и управление всеми заявками).
 */

session_start();
require_once __DIR__ . '/../helpers/db.php';

// Защита: пускаем только администраторов
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'администратор') {
    header('Location: /DEE/korochki/korochki/pages/auth.php');
    exit;
}

$pdo = db();
$errors = [];
$success = false;

// --- Обработка сохранения (изменения статуса админом) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $zayavkaId = (int)($_POST['zayavka_id'] ?? 0);
    $newStatus = (int)($_POST['status_id'] ?? 1);

    if ($zayavkaId > 0) {
        try {
            $stmt = $pdo->prepare('UPDATE zayavki SET status_id = ? WHERE id_zayavki = ?');
            $stmt->execute([$newStatus, $zayavkaId]);
            $success = true;
        } catch (Throwable $e) {
            $errors['common'] = 'Ошибка сохранения: ' . $e->getMessage();
        }
    }
}

// --- Фильтр и пагинация ---
$filterStatus = isset($_GET['status']) ? (int)$_GET['status'] : 0;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 3; // Количество заявок на одной странице
$offset = ($page - 1) * $limit;

$where = '';
$params = [];
if ($filterStatus > 0) {
    $where = 'WHERE z.status_id = ?';
    $params[] = $filterStatus;
}

// Получаем общее количество для пагинации
$countSql = "SELECT COUNT(*) FROM zayavki z $where";
$stmt = $pdo->prepare($countSql);
$stmt->execute($params);
$totalRows = (int)$stmt->fetchColumn();
$totalPages = ceil($totalRows / $limit);

// Получаем сами заявки
$sql = "SELECT z.id_zayavki, z.data_nachalo, z.status_id, c.name_course, p.method AS payment_method, s.status_name, u.fio AS user_fio
        FROM zayavki z
        LEFT JOIN courses c ON c.id_course = z.kursi_id
        LEFT JOIN playments p ON p.id_payment = z.oplati_id
        LEFT JOIN statuses s ON s.id_status = z.status_id
        LEFT JOIN polzovateli u ON u.id_polzovateli = z.polzovateli_id
        $where
        ORDER BY z.id_zayavki DESC
        LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$zayavki = $stmt->fetchAll();

$statuses = $pdo->query('SELECT id_status, status_name FROM statuses')->fetchAll();

// Метаданные для layout
$pageTitle = 'Панель администратора — Корочки.есть';
$breadcrumbs = ['Главная' => '/DEE/korochki/korochki/pages/index.php', 'Панель администратора' => null];

ob_start();
?>
<section class="zayavki-page" style="max-width: 1000px; margin: 0 auto; padding: 20px;">

    <h1 class="auth-title" style="text-align: center;">Панель администратора</h1>

    <?php if (!empty($errors['common'])): ?>
        <div class="alert alert-error"><?= htmlspecialchars($errors['common']) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success" style="background: #d4edda; color: #155724; border: 1px solid #c3e6cb; padding: 10px; border-radius: 4px; margin-bottom: 15px;">
            ✅ Статус заявки успешно обновлён!
        </div>
    <?php endif; ?>

    <!-- Кнопка создания -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <p style="font-size: 14px;">
            «Нет вашей заявки?»<br>
            <a href="/DEE/korochki/korochki/pages/new_zayvka.php">создать</a>
        </p>
        <a href="/DEE/korochki/korochki/pages/new_zayvka.php" class="btn" style="background: #e1d5e7; color: #4a3a6b; border: 1px solid #9673a6; padding: 8px 20px; border-radius: 4px; text-decoration: none;">
            Создать заявку
        </a>
    </div>

    <!-- Фильтр по статусу -->
    <form method="get" style="margin-bottom: 20px; text-align: center;">
        <label>Фильтр по статусу: </label>
        <select name="status" onchange="this.form.submit()" class="form-input" style="width: auto; display: inline-block;">
            <option value="0">Все заявки</option>
            <?php foreach ($statuses as $s): ?>
                <option value="<?= $s['id_status'] ?>" <?= ($filterStatus == $s['id_status']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($s['status_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>

    <?php if (empty($zayavki)): ?>
        <div style="text-align: center; padding: 40px; background: #f9f9f9; border: 1px dashed #ccc; border-radius: 8px;">
            <p>Заявок не найдено.</p>
        </div>
    <?php else: ?>
        <!-- Сетка заявок -->
        <div style="display: flex; flex-wrap: wrap; gap: 20px; justify-content: center;">
            <?php foreach ($zayavki as $z): ?>
                <div class="zayavka-card" style="background: #fff; border: 1px solid #ccc; border-radius: 8px; width: 240px; display: flex; flex-direction: column;">
                    
                    <div style="background: #f0f0f0; padding: 8px 12px; border-bottom: 1px solid #ccc; border-radius: 8px 8px 0 0; font-size: 13px; font-weight: bold;">
                        заявка <?= $z['id_zayavki'] ?>
                    </div>

                    <div style="padding: 12px; font-size: 13px; flex-grow: 1;">
                        <div style="margin-bottom: 8px;">
                            <span style="color: #777; font-size: 11px; display: block;">фио того кто подал заявку</span>
                            <?= htmlspecialchars($z['user_fio'] ?? 'Неизвестно') ?>
                        </div>
                        <div style="margin-bottom: 8px;">
                            <span style="color: #777; font-size: 11px; display: block;">наименование курса</span>
                            <?= htmlspecialchars($z['name_course'] ?? 'Не указан') ?>
                        </div>
                        
                        <div style="margin-bottom: 8px;">
                            <span style="color: #777; font-size: 11px; display: block;">статус</span>
                            <?php 
                                // Цветной бейдж для статуса
                                $statusId = $z['status_id'];
                                $statusName = $z['status_name'] ?? 'Неизвестно';
                                if ($statusId == 1) {
                                    $badgeColor = '#ffc107'; // Жёлтый (Новая)
                                    $textColor = '#000';
                                } elseif ($statusId == 2) {
                                    $badgeColor = '#0085fc'; // Синий (Идет обучение)
                                    $textColor = '#fff';
                                } elseif ($statusId == 3) {
                                    $badgeColor = '#28a745'; // Зелёный (Обучение завершено)
                                    $textColor = '#fff';
                                } else {
                                    $badgeColor = '#e9ecef'; // Серый
                                    $textColor = '#333';
                                }
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
                            <span style="color: #777; font-size: 11px; display: block;">желаемая дата начала обучения</span>
                            <?= htmlspecialchars($z['data_nachalo']) ?>
                        </div>
                    </div>

                    <div style="padding: 12px; border-top: 1px dashed #eee; background: #fafafa; border-radius: 0 0 8px 8px;">
                        <form method="post" style="margin: 0;">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="zayavka_id" value="<?= $z['id_zayavki'] ?>">
                            
                            <select name="status_id" class="form-input" style="width: 100%; font-size: 12px; padding: 4px; margin-bottom: 5px;">
                                <?php foreach ($statuses as $s): ?>
                                    <option value="<?= $s['id_status'] ?>" <?= ($z['status_id'] == $s['id_status']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($s['status_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            
                            <button type="submit" class="btn" style="background: #e1d5e7; color: #4a3a6b; border: 1px solid #9673a6; width: 100%; font-size: 12px; padding: 4px; cursor: pointer;">
                                сохранить
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
                    <a href="?status=<?= $filterStatus ?>&page=<?= $i ?>" 
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