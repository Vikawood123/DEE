<?php
/**
 * new_zayvka.php — создание новой заявки.
 */

session_start();

require_once __DIR__ . '/../helpers/db.php';

// Если не вошёл — отправляем на авторизацию
if (empty($_SESSION['user_id'])) {
    header('Location: /DEE/korochki/korochki/pages/auth.php');
    exit;
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_id  = (int)($_POST['course_id'] ?? 0);
    $status_id  = 1; // Жестко ставим статус "Новая"
    $payment_id = (int)($_POST['payment_id'] ?? 0);
    $date_start = trim($_POST['date_start'] ?? '');

    // Валидация
    if ($course_id <= 0) {
        $errors['course_id'] = 'Выберите курс.';
    }
    if ($payment_id <= 0) {
        $errors['payment_id'] = 'Выберите способ оплаты.';
    }
    if ($date_start === '') {
        $errors['date_start'] = 'Укажите дату начала обучения.';
    }

    // Сохранение
    if (!$errors) {
        try {
            $pdo = db();

            // Переводим дату из формата HTML (ГГГГ-ММ-ДД) в формат БД (ДД.ММ.ГГГГ)
            $date_parts = explode('-', $date_start);
            $date_formatted = $date_parts[2] . '.' . $date_parts[1] . '.' . $date_parts[0];

            // Сохраняем только курс, оплату и дату (без комментария)
            $sql = 'INSERT INTO zayavki (polzovateli_id, kursi_id, data_nachalo, oplati_id, status_id) 
                    VALUES (?, ?, ?, ?, ?)';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $_SESSION['user_id'],
                $course_id,
                $date_formatted,
                $payment_id,
                $status_id
            ]);

            $success = true;

        } catch (Throwable $e) {
            $errors['common'] = 'Ошибка сохранения: ' . $e->getMessage();
        }
    }
}

// Получаем списки для выпадающих меню
try {
    $pdo = db();
    $courses  = $pdo->query('SELECT id_course, name_course FROM courses')->fetchAll();
    $payments = $pdo->query('SELECT id_payment, method FROM playments')->fetchAll();
} catch (Throwable $e) {
    $errors['common'] = 'Ошибка загрузки данных: ' . $e->getMessage();
}

// Метаданные для layout
$pageTitle = 'Подача заявки — Корочки.есть';
$breadcrumbs = [
    'Главная'         => '/DEE/korochki/korochki/pages/index.php',
    'Просмотр заявок' => '/DEE/korochki/korochki/pages/zayvki.php',
    'Подача заявки'   => null,
];

ob_start();
?>
<section class="auth" style="max-width: 600px;">
    <h1 class="auth-title">Подача заявки</h1>

    <?php if (!empty($errors['common'])): ?>
        <div class="alert alert-error"><?= htmlspecialchars($errors['common']) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success" style="background: #d4edda; color: #155724; border: 1px solid #c3e6cb; padding: 10px; border-radius: 4px; margin-bottom: 15px;">
            ✅ Заявка успешно создана! <a href="/DEE/korochki/korochki/pages/zayvki.php">Перейти к заявкам</a>
        </div>
    <?php endif; ?>

    <form class="auth-form" method="post" novalidate>

        <!-- Курс -->
        <div class="form-group">
            <label for="course_id">Наименование курса</label>
            <select id="course_id" name="course_id" class="form-input <?= isset($errors['course_id']) ? 'is-invalid' : '' ?>" style="padding: 8px;">
                <option value="">-- выберите курс --</option>
                <?php foreach ($courses as $c): ?>
                    <option value="<?= $c['id_course'] ?>" <?= (($_POST['course_id'] ?? '') == $c['id_course']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['name_course']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['course_id'])): ?>
                <span class="form-error"><?= htmlspecialchars($errors['course_id']) ?></span>
            <?php endif; ?>
        </div>

        <!-- Способ оплаты -->
        <div class="form-group">
            <label for="payment_id">Способ оплаты</label>
            <select id="payment_id" name="payment_id" class="form-input <?= isset($errors['payment_id']) ? 'is-invalid' : '' ?>" style="padding: 8px;">
                <option value="">-- выберите способ --</option>
                <?php foreach ($payments as $p): ?>
                    <option value="<?= $p['id_payment'] ?>" <?= (($_POST['payment_id'] ?? '') == $p['id_payment']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p['method']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['payment_id'])): ?>
                <span class="form-error"><?= htmlspecialchars($errors['payment_id']) ?></span>
            <?php endif; ?>
        </div>

        <!-- Дата -->
        <div class="form-group">
            <label for="date_start">Желаемая дата начала обучения</label>
            <input type="date" id="date_start" name="date_start"
                   class="form-input <?= isset($errors['date_start']) ? 'is-invalid' : '' ?>"
                   value="<?= htmlspecialchars($_POST['date_start'] ?? '') ?>"
                   style="padding: 7px;">
            <?php if (isset($errors['date_start'])): ?>
                <span class="form-error"><?= htmlspecialchars($errors['date_start']) ?></span>
            <?php endif; ?>
        </div>

        <button type="submit" class="btn btn-primary auth-submit" style="background: #e1d5e7; color: #4a3a6b; border-color: #9673a6;">
            Подать заявку
        </button>

        <p class="auth-alt" style="text-align: left; margin-top: 20px;">
            «Заявка уже создана?»
            <a href="/DEE/korochki/korochki/pages/zayvki.php">посмотреть</a>
        </p>

    </form>
</section>
<?php
$content = ob_get_clean();

require __DIR__ . '/layout.php';