<?php
session_start();
require_once __DIR__ . '/../helpers/db.php';
if (empty($_SESSION['user_id'])) { header('Location: auth.php'); exit; }

$pdo = db();
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $disciplina = (int)($_POST['disciplina'] ?? 0);
    $date_raw = trim($_POST['date'] ?? '');
    $forma = (int)($_POST['forma'] ?? 0);
    $prichina = trim($_POST['prichina'] ?? '');

    // Переводим дату из формата календаря (ГГГГ-ММ-ДД) в формат базы (ДД.ММ.ГГГГ)
    $date = '';
    if ($date_raw !== '') {
        $d = DateTime::createFromFormat('Y-m-d', $date_raw);
        if ($d) {
            $date = $d->format('d.m.Y');
        }
    }

    if ($disciplina <= 0) $errors['disciplina'] = 'Выберите дисциплину.';
    if ($forma <= 0) $errors['forma'] = 'Выберите форму пересдачи.';
    if ($date === '') $errors['date'] = 'Выберите корректную дату.';
    if ($prichina === '') $errors['prichina'] = 'Укажите причину пересдачи.';

    if (!$errors) {
        try {
            $stmt = $pdo->prepare('INSERT INTO zayavki_peresdachi (polzovateli_id, disciplini_id, data_peresdachi, formi_peresdachi_id, statusi_id, prichina) VALUES (?, ?, ?, ?, 1, ?)');
            $stmt->execute([$_SESSION['user_id'], $disciplina, $date, $forma, $prichina]);
            $success = true;
        } catch (Throwable $e) { $errors['common'] = 'Ошибка БД: ' . $e->getMessage(); }
    }
}

$disciplini = $pdo->query('SELECT * FROM disciplini')->fetchAll();
$formi = $pdo->query('SELECT * FROM formi_peresdachi')->fetchAll();

$pageTitle = 'Новая заявка — Пересдачи';
$breadcrumbs = ['Главная' => 'index.php', 'Новая заявка' => null];
ob_start();
?>
<section class="auth" style="max-width: 600px;">
    <h1 class="auth-title">Подача заявки</h1>
    <?php if ($success): ?><div class="alert alert-success">✅ Заявка успешно отправлена!</div><?php endif; ?>
    <?php if (!empty($errors['common'])): ?><div class="alert alert-error"><?= htmlspecialchars($errors['common']) ?></div><?php endif; ?>
    
    <form class="auth-form" method="post">
        <div class="form-group">
            <label>Дисциплина</label>
            <select name="disciplina" class="form-input <?= isset($errors['disciplina']) ? 'is-invalid' : '' ?>">
                <option value="">-- выберите дисциплину --</option>
                <?php foreach ($disciplini as $d): ?>
                    <option value="<?= $d['id_disciplini'] ?>" <?= (isset($_POST['disciplina']) && $_POST['disciplina'] == $d['id_disciplini']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($d['nazvanie']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['disciplina'])): ?><span class="form-error"><?= $errors['disciplina'] ?></span><?php endif; ?>
        </div>
        
        <div class="form-group">
            <label>Желаемая дата пересдачи</label>
            <!-- Изменено на type="date" -->
            <input type="date" name="date" class="form-input <?= isset($errors['date']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars($_POST['date'] ?? '') ?>">
            <?php if (isset($errors['date'])): ?><span class="form-error"><?= $errors['date'] ?></span><?php endif; ?>
        </div>
        
        <div class="form-group">
            <label>Форма пересдачи</label>
            <select name="forma" class="form-input <?= isset($errors['forma']) ? 'is-invalid' : '' ?>">
                <option value="">-- выберите форму --</option>
                <?php foreach ($formi as $f): ?>
                    <option value="<?= $f['id_formi'] ?>" <?= (isset($_POST['forma']) && $_POST['forma'] == $f['id_formi']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($f['forma']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['forma'])): ?><span class="form-error"><?= $errors['forma'] ?></span><?php endif; ?>
        </div>
        
        <div class="form-group">
            <label>Причина пересдачи</label>
            <textarea name="prichina" class="form-input <?= isset($errors['prichina']) ? 'is-invalid' : '' ?>" style="height: 80px;"><?= htmlspecialchars($_POST['prichina'] ?? '') ?></textarea>
            <?php if (isset($errors['prichina'])): ?><span class="form-error"><?= $errors['prichina'] ?></span><?php endif; ?>
        </div>
        <button type="submit" class="btn auth-submit">Отправить</button>
    </form>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';