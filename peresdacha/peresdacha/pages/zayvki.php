<?php
session_start();
require_once __DIR__ . '/../helpers/db.php';
if (empty($_SESSION['user_id'])) { header('Location: auth.php'); exit; }

$pdo = db();
$userId = $_SESSION['user_id'];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_review') {
    $zayavkaId = (int)$_POST['zayavka_id'];
    $koment = trim($_POST['koment']);
    $zvezda = (int)$_POST['zvezda'];
    if ($koment && $zvezda >= 1 && $zvezda <= 5) {
        $stmt = $pdo->prepare('INSERT INTO otzivi_peresdachi (zayavki_id, koment, zvezda) VALUES (?, ?, ?)');
        $stmt->execute([$zayavkaId, $koment, $zvezda]);
        $success = true;
    }
}

$sql = 'SELECT z.*, d.nazvanie, f.forma, s.status_name, 
        (SELECT id_otziva FROM otzivi_peresdachi o WHERE o.zayavki_id = z.id_zayavki LIMIT 1) as has_review
        FROM zayavki_peresdachi z
        LEFT JOIN disciplini d ON d.id_disciplini = z.disciplini_id
        LEFT JOIN formi_peresdachi f ON f.id_formi = z.formi_peresdachi_id
        LEFT JOIN statusi_peresdachi s ON s.id_statusa = z.statusi_id
        WHERE z.polzovateli_id = ? ORDER BY z.id_zayavki DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute([$userId]);
$zayavki = $stmt->fetchAll();

$pageTitle = 'Мои заявки — Пересдачи';
$breadcrumbs = ['Главная' => 'index.php', 'Мои заявки' => null];
ob_start();
?>
<section class="zayavki-page" style="max-width: 1000px; margin: 0 auto; padding: 20px;">
    <h1 class="auth-title">Мои заявки</h1>
    <?php if ($success): ?><div class="alert alert-success">✅ Отзыв успешно добавлен!</div><?php endif; ?>
    
    <?php if (empty($zayavki)): ?>
        <p style="text-align:center;">У вас пока нет заявок. <a href="new_zayvka.php">Создать</a></p>
    <?php else: ?>
        <div style="display: flex; flex-wrap: wrap; gap: 20px; justify-content: center;">
            <?php foreach ($zayavki as $z): ?>
                <div class="zayavka-card" style="padding: 15px;">
                    <div style="font-weight:bold; margin-bottom:10px; border-bottom:1px solid #eee; padding-bottom:5px;">Заявка №<?= $z['id_zayavki'] ?></div>
                    <div style="margin-bottom: 5px;"><strong>Дисциплина:</strong> <?= htmlspecialchars($z['nazvanie']) ?></div>
                    <div style="margin-bottom: 5px;"><strong>Дата:</strong> <?= htmlspecialchars($z['data_peresdachi']) ?></div>
                    <div style="margin-bottom: 5px;"><strong>Форма:</strong> <?= htmlspecialchars($z['forma']) ?></div>
                    <div style="margin-bottom: 10px;"><strong>Статус:</strong> 
                        <span style="background: <?= $z['statusi_id'] == 1 ? '#ffc107' : ($z['statusi_id'] == 2 ? '#3b82f6' : '#10b981') ?>; color: <?= $z['statusi_id'] == 1 ? '#000' : '#fff' ?>; padding: 2px 8px; border-radius: 12px; font-size:12px;">
                            <?= htmlspecialchars($z['status_name']) ?>
                        </span>
                    </div>
                    
                    <?php if ($z['statusi_id'] == 3): ?>
                        <?php if ($z['has_review']): ?>
                            <div style="color: #10b981; font-size: 13px;">Отзыв оставлен</div>
                        <?php else: ?>
                            <form method="post" style="margin-top:10px; border-top:1px dashed #ccc; padding-top:10px;">
                                <input type="hidden" name="action" value="add_review">
                                <input type="hidden" name="zayavka_id" value="<?= $z['id_zayavki'] ?>">
                                <label style="font-size:12px;">Оценка:</label>
                                <select name="zvezda" class="form-input" style="padding:4px; font-size:12px;">
                                    <option value="5">5 ★</option><option value="4">4 ★</option><option value="3">3 ★</option><option value="2">2 ★</option><option value="1">1 ★</option>
                                </select>
                                <textarea name="koment" placeholder="Текст отзыва" class="form-input" style="height:40px; font-size:12px; margin-top:5px;" required></textarea>
                                <button type="submit" class="btn" style="font-size:12px; padding:4px; margin-top:5px;">Оставить отзыв</button>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';