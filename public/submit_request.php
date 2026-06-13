<?php
require 'includes/auth.php';
$title = 'Новая заявка';
include 'includes/header.php';

// Загружаем услуги с флагом needs_calc
$services = $pdo->query('SELECT id, name, needs_calc FROM service_types ORDER BY name')->fetchAll();
$errors = [];
$success = '';
$old = [];

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['service_type_id'])) {
    $get_id = filter_input(INPUT_GET, 'service_type_id', FILTER_VALIDATE_INT);
    if ($get_id && in_array($get_id, array_column($services, 'id'))) {
        $old['service_type_id'] = $get_id;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = $_POST;
    $serviceId = filter_var($old['service_type_id'] ?? '', FILTER_VALIDATE_INT);
    $address = trim($old['work_address'] ?? '');
    $desc = trim($old['description'] ?? '');
    $workDate = $old['work_date'] ?? '';
    $calcJson = $old['calc_json'] ?? '';
    
    if (!$serviceId || !in_array($serviceId, array_column($services, 'id'))) $errors['service_type_id'] = 'Выберите тип работ';
    if (empty($address)) $errors['work_address'] = 'Укажите адрес выполнения работ';
    if (mb_strlen($desc) < 10) $errors['description'] = 'Опишите задачу подробнее (мин. 10 символов)';
    if ($workDate) {
        if (!strtotime($workDate)) $errors['work_date'] = 'Укажите корректную дату';
        elseif (date('w', strtotime($workDate)) == 0) $errors['work_date'] = 'Воскресенье — выходной';
    }

    if (empty($errors)) {
        $finalDesc = $desc;
        $totalPrice = 0;
        
        if ($calcJson && json_decode($calcJson)) {
            $calcData = json_decode($calcJson, true);
            if (!empty($calcData['items'])) {
                $finalDesc = "📋 Состав заявки:\n";
                foreach ($calcData['items'] as $item) {
                    $finalDesc .= "• {$item['type']} ({$item['opening']}, {$item['size']}): {$item['qty']} шт. × {$item['basePrice']} ₽ = {$item['total']} ₽\n";
                    $totalPrice += $item['total'];
                }
                $finalDesc .= "\n💰 Итого: {$totalPrice} ₽ (ориентировочно)\n\n📝 {$desc}";
            }
        }
        
        try {
            $stmt = $pdo->prepare('INSERT INTO requests (user_id, service_type_id, status_id, work_date, work_address, description) VALUES (?, ?, 1, ?, ?, ?)');
            $stmt->execute([$_SESSION['user_id'], $serviceId, $workDate !== '' ? $workDate : null, $address, $finalDesc]);
            $success = 'Заявка успешно создана!';
            $old = [];
        } catch (PDOException $e) {
            $errors['general'] = 'Ошибка сохранения данных';
        }
    }
}
?>
<h2 class="mb-3">Оставить заявку</h2>
<?php if($success): ?><div class="alert alert-success"><?= $success ?> <a href="dashboard.php" class="alert-link">Перейти к заявкам</a></div><?php endif; ?>
<?php if(!empty($errors)): ?><div class="alert alert-danger"><?= implode('<br>', $errors) ?></div><?php endif; ?>

<form method="POST" class="card p-4 shadow-sm needs-validation" novalidate>
    <div class="mb-3">
        <label class="form-label">Тип работ</label>
        <select name="service_type_id" id="service_type" class="form-select <?= isset($errors['service_type_id']) ? 'is-invalid' : '' ?>" required>
            <option value="" disabled <?= empty($old['service_type_id']) ? 'selected' : '' ?>>Выберите услугу...</option>
            <?php foreach($services as $s): ?>
                <option value="<?= $s['id'] ?>" data-needs-calc="<?= $s['needs_calc'] ?>" <?= ($old['service_type_id']??'')==$s['id']?'selected':'' ?>><?= htmlspecialchars($s['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <?php if(isset($errors['service_type_id'])): ?><div class="invalid-feedback"><?= $errors['service_type_id'] ?></div><?php endif; ?>
    </div>

    <div class="mb-3">
        <label class="form-label">Адрес выполнения работ</label>
        <input type="text" name="work_address" class="form-control <?= isset($errors['work_address']) ? 'is-invalid' : '' ?>" placeholder="г. Архангельск, ул. Примерная, д. 10, кв. 5" value="<?= htmlspecialchars($old['work_address'] ?? '') ?>" required>
        <?php if(isset($errors['work_address'])): ?><div class="invalid-feedback"><?= $errors['work_address'] ?></div><?php endif; ?>
    </div>

    <!-- Контекстный калькулятор -->
    <div id="calc_wrapper" class="mb-3 p-3 bg-body-secondary rounded" style="display: none;">
        <label class="form-label fw-bold">🧮 Конфигуратор окон</label>
        <div id="calc_container" class="mb-3">
            <!-- Динамические строки -->
        </div>
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <button type="button" id="calc_add_btn" class="btn btn-sm btn-outline-primary">+ Добавить окно</button>
            <div class="text-end">
                <div class="small text-body-secondary">Итого за окна:</div>
                <div class="h5 mb-0 text-success fw-bold" id="calc_total">0 ₽</div>
            </div>
        </div>
        <input type="hidden" name="calc_json" id="calc_json">
        <small class="text-body-secondary d-block mt-2">Выберите конфигурацию, фурнитуру и размер. Цена ориентировочная, точная смета после замера.</small>
    </div>

    <div class="mb-3">
        <label class="form-label">Желаемая дата выполнения</label>
        <input type="date" name="work_date" class="form-control <?= isset($errors['work_date']) ? 'is-invalid' : '' ?>" min="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($old['work_date'] ?? '') ?>">
        <div class="form-text">Пн-Сб. Воскресенье — выходной.</div>
        <?php if(isset($errors['work_date'])): ?><div class="invalid-feedback"><?= $errors['work_date'] ?></div><?php endif; ?>
    </div>

    <div class="mb-3">
        <label class="form-label">Описание задачи / Комментарий</label>
        <textarea name="description" class="form-control <?= isset($errors['description']) ? 'is-invalid' : '' ?>" rows="4" required minlength="10"><?= htmlspecialchars($old['description'] ?? '') ?></textarea>
        <?php if(isset($errors['description'])): ?><div class="invalid-feedback"><?= $errors['description'] ?></div><?php endif; ?>
    </div>

    <div class="alert alert-info py-2 mb-3 small">
        <i class="bi bi-info-circle me-1"></i> Цена на работу обговаривается на месте, исходя из разных условий выполнения заказа.
    </div>

    <button type="submit" class="btn btn-success w-100">Отправить заявку</button>
</form>
<?php include 'includes/footer.php'; ?>