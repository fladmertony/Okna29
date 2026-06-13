<?php 
$title = 'Главная'; include 'includes/header.php'; 
$latestServices = $pdo->query('SELECT id, name, description, image FROM service_types ORDER BY id DESC LIMIT 3')->fetchAll();
?>
<div class="text-center py-5 bg-body-secondary rounded-4 mb-4 border">
    <h1 class="display-4 fw-bold mb-3">Качество в каждой раме, комфорт в каждом доме</h1>
    <p class="lead mb-4">Профессиональная установка, ремонт и обслуживание окон любой сложности.</p>
    <a href="services.php" class="btn btn-primary btn-lg px-4">Посмотреть все услуги</a>
    <?php if (!isset($_SESSION['user_id'])): ?>
        <a href="register.php" class="btn btn-outline-primary btn-lg px-4 ms-2">Оставить заявку</a>
    <?php endif; ?>
</div>

<?php if(!empty($latestServices)): ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0">🔥 Новые услуги</h4>
    <span class="text-body-secondary small">Обновлено недавно</span>
</div>
<div class="row g-4 mb-5">
    <?php foreach($latestServices as $s): 
        $imgPath = $s['image'] && file_exists('uploads/' . $s['image']) ? 'uploads/' . $s['image'] : 'https://via.placeholder.com/400x200/0d6efd/ffffff?text=' . urlencode($s['name']);
    ?>
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow rounded-4 overflow-hidden position-relative">
                <img src="<?= htmlspecialchars($imgPath) ?>" class="card-img-top" alt="<?= htmlspecialchars($s['name']) ?>" style="height: 200px; object-fit: cover;">
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title fw-bold text-primary-emphasis"><?= htmlspecialchars($s['name']) ?></h5>
                    <p class="card-text text-body-secondary flex-grow-1"><?= htmlspecialchars($s['description']) ?></p>
                    <a href="submit_request.php?service_type_id=<?= $s['id'] ?>" class="btn btn-primary stretched-link mt-3">
                        Оставить заявку
                    </a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-md-4"><div class="card h-100 border-0 shadow-sm"><div class="card-body text-center"><i class="bi bi-tools display-4 text-primary"></i><h5 class="mt-3">Монтаж окон</h5><p class="text-body-secondary">Установка ПВХ, алюминия и дерева под ключ</p></div></div></div>
    <div class="col-md-4"><div class="card h-100 border-0 shadow-sm"><div class="card-body text-center"><i class="bi bi-gear-wide display-4 text-success"></i><h5 class="mt-3">Регулировка и ремонт</h5><p class="text-body-secondary">Устранение продуваний, замена фурнитуры</p></div></div></div>
    <div class="col-md-4"><div class="card h-100 border-0 shadow-sm"><div class="card-body text-center"><i class="bi bi-shield-check display-4 text-warning"></i><h5 class="mt-3">Гарантия 5 лет</h5><p class="text-body-secondary">Бесплатный сервис на все выполненные работы</p></div></div></div>
</div>
<?php include 'includes/footer.php'; ?>