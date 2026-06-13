<?php 
$title = 'Услуги'; include 'includes/header.php'; 
$services = $pdo->query('SELECT * FROM service_types ORDER BY name')->fetchAll();
?>
<h2 class="mb-4 border-bottom pb-2">Наши услуги</h2>
<?php if(!isset($_SESSION['user_id'])): ?>
    <div class="alert alert-info mb-4">
        Для оформления заявки необходимо <a href="login.php" class="alert-link">авторизоваться</a> или <a href="register.php" class="alert-link">зарегистрироваться</a>.
    </div>
<?php endif; ?>

<div class="list-group shadow-sm">
    <?php foreach($services as $s): 
        $href = "submit_request.php?service_type_id=" . $s['id'];
        $badgeClass = isset($_SESSION['user_id']) ? 'bg-primary' : 'bg-secondary';
        $badgeText = isset($_SESSION['user_id']) ? 'Оставить заявку →' : '🔒 Войти';
    ?>
        <a href="<?= $href ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-start py-3">
            <div class="ms-2 me-auto">
                <div class="fw-bold fs-5"><?= htmlspecialchars($s['name']) ?></div>
                <p class="mb-0 text-body-secondary"><?= htmlspecialchars($s['description']) ?></p>
            </div>
            <span class="badge <?= $badgeClass ?> rounded-pill align-self-center"><?= $badgeText ?></span>
        </a>
    <?php endforeach; ?>
</div>
<?php include 'includes/footer.php'; ?>