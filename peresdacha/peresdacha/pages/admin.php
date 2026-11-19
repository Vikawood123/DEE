<?php
session_start();
require_once __DIR__ . '/../helpers/db.php';
if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'администратор') { header('Location: auth.php'); exit; }

$pdo = db();

// Обработка смены статуса
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status_id'])) {
    $stmt = $pdo->prepare('UPDATE zayavki_peresdachi SET statusi_id = ? WHERE id_zayavki = ?');
    $stmt->execute([(int)$_POST['status_id'], (int)$_POST['zayavka_id']]);
    
    // После сохранения оставляем админа на той же странице и с теми же фильтрами
    $redirectUrl = 'admin.php?page=' . ($_GET['page'] ?? 1);
    if (isset($_GET['status'])) $redirectUrl .= '&status=' . (int)$_GET['status'];
    if (isset($_GET['disc'])) $redirectUrl .= '&disc=' . (int)$_GET['disc'];
    header("Location: $redirectUrl");
    exit;
}

// Настройки фильтрации
$filterStatus = isset($_GET['status']) ? (int)$_GET['status'] : 0;
$filterDisc = isset($_GET['disc']) ? (int)$_GET['disc'] : 0;
$where = "WHERE 1=1";
$params = [];
if ($filterStatus > 0) { $where .= " AND z.statusi_id = ?"; $params[] = $filterStatus; }
if ($filterDisc > 0) { $where .= " AND z.disciplini_id = ?"; $params[] = $filterDisc; }

// 1. Узнаем общее количество заявок (для пагинации)
$countSql = "SELECT COUNT(*) FROM zayavki_peresdachi z $where";
$stmtCount = $pdo->prepare($countSql);
$stmtCount->execute($params);
$totalZayavki = $stmtCount->fetchColumn();

// 2. Настройки пагинации
$limit = 3; // Выводить ровно по 3 заявки
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$totalPages = ceil($totalZayavki / $limit);
if ($totalPages == 0) $totalPages = 1;
if ($page > $totalPages) $page = $totalPages;
$offset = ($page - 1) * $limit;

// 3. Получаем заявки для текущей страницы
$sql = "SELECT z.*, p.fio, d.nazvanie, f.forma, s.status_name 
        FROM zayavki_peresdachi z 
        JOIN ppolzovateli p ON p.id_polzovateli = z.polzovateli_id
        LEFT JOIN disciplini d ON d.id_disciplini = z.disciplini_id
        LEFT JOIN formi_peresdachi f ON f.id_formi = z.formi_peresdachi_id
        LEFT JOIN statusi_peresdachi s ON s.id_statusa = z.statusi_id
        $where ORDER BY z.id_zayavki DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$zayavki = $stmt->fetchAll();

// Данные для выпадающих списков
$statuses = $pdo->query('SELECT * FROM statusi_peresdachi')->fetchAll();
$disciplini = $pdo->query('SELECT * FROM disciplini')->fetchAll();

// Функция для формирования ссылок с сохранением фильтров
function buildPageUrl($pageNum) {
    $url = 'admin.php?page=' . $pageNum;
    if (isset($_GET['status']) && $_GET['status'] > 0) $url .= '&status=' . (int)$_GET['status'];
    if (isset($_GET['disc']) && $_GET['disc'] > 0) $url .= '&disc=' . (int)$_GET['disc'];
    return $url;
}

$pageTitle = 'Админка — Пересдачи';
$breadcrumbs = ['Главная' => 'index.php', 'Админка' => null];
ob_start();
?>
<section style="max-width: 1000px; margin: 0 auto; padding: 20px;">
    <h1 class="auth-title">Панель администратора</h1>
    
    <!-- Форма фильтрации -->
    <form method="get" style="text-align:center; margin-bottom:20px; display:flex; gap:10px; justify-content:center;">
        <!-- При смене фильтра сбрасываем страницу на первую -->
        <input type="hidden" name="page" value="1">
        <select name="status" onchange="this.form.submit()" class="form-input" style="width:auto;">
            <option value="0">Все статусы</option>
            <?php foreach ($statuses as $s): ?>
                <option value="<?= $s['id_statusa'] ?>" <?= $filterStatus == $s['id_statusa'] ? 'selected' : '' ?>><?= htmlspecialchars($s['status_name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="disc" onchange="this.form.submit()" class="form-input" style="width:auto;">
            <option value="0">Все дисциплины</option>
            <?php foreach ($disciplini as $d): ?>
                <option value="<?= $d['id_disciplini'] ?>" <?= $filterDisc == $d['id_disciplini'] ? 'selected' : '' ?>><?= htmlspecialchars($d['nazvanie']) ?></option>
            <?php endforeach; ?>
        </select>
    </form>

    <!-- Сетка заявок -->
    <div style="display: flex; flex-wrap: wrap; gap: 20px; justify-content: center; margin-bottom: 30px;">
        <?php if (empty($zayavki)): ?>
            <p style="text-align:center; width:100%;">Заявок не найдено.</p>
        <?php else: ?>
            <?php foreach ($zayavki as $z): ?>
                <div class="zayavka-card" style="padding: 15px; width: 280px;">
                    <div style="font-weight:bold; margin-bottom:10px;">Заявка №<?= $z['id_zayavki'] ?> (<?= htmlspecialchars($z['fio']) ?>)</div>
                    <div style="margin-bottom: 5px; font-size:13px;"><strong>Дисциплина:</strong> <?= htmlspecialchars($z['nazvanie']) ?></div>
                    <div style="margin-bottom: 5px; font-size:13px;"><strong>Дата:</strong> <?= htmlspecialchars($z['data_peresdachi']) ?></div>
                    <div style="margin-bottom: 5px; font-size:13px;"><strong>Причина:</strong> <?= htmlspecialchars($z['prichina']) ?></div>
                    <form method="post" style="margin-top:15px; border-top:1px solid #eee; padding-top:10px;">
                        <input type="hidden" name="zayavka_id" value="<?= $z['id_zayavki'] ?>">
                        <select name="status_id" class="form-input" style="font-size:12px; padding:4px;">
                            <?php foreach ($statuses as $s): ?>
                                <option value="<?= $s['id_statusa'] ?>" <?= $z['statusi_id'] == $s['id_statusa'] ? 'selected' : '' ?>><?= htmlspecialchars($s['status_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn" style="font-size:12px; padding:4px; margin-top:5px; width:100%;">Сохранить</button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Блок пагинации (стрелочки) -->
    <?php if ($totalPages > 1): ?>
        <div style="display: flex; justify-content: center; align-items: center; gap: 15px;">
            <?php if ($page > 1): ?>
                <a href="<?= buildPageUrl($page - 1) ?>" class="btn" style="text-decoration: none;">&larr; Назад</a>
            <?php else: ?>
                <span class="btn" style="opacity: 0.5; cursor: not-allowed;">&larr; Назад</span>
            <?php endif; ?>

            <span style="font-size: 14px;">Страница <strong><?= $page ?></strong> из <strong><?= $totalPages ?></strong></span>

            <?php if ($page < $totalPages): ?>
                <a href="<?= buildPageUrl($page + 1) ?>" class="btn" style="text-decoration: none;">Вперед &rarr;</a>
            <?php else: ?>
                <span class="btn" style="opacity: 0.5; cursor: not-allowed;">Вперед &rarr;</span>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';