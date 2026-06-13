<?php 
require 'includes/auth.php'; 
$title = 'Мои заявки'; include 'includes/header.php';

$perPage = 5;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

$stmtCount = $pdo->prepare('SELECT COUNT(*) FROM requests WHERE user_id = ?');
$stmtCount->execute([$_SESSION['user_id']]);
$total = $stmtCount->fetchColumn();
$totalPages = ceil($total / $perPage);

$stmt = $pdo->prepare('
    SELECT r.*, st.name as service_name, rs.name as status_name, rs.bootstrap_color as status_color
    FROM requests r
    JOIN service_types st ON r.service_type_id = st.id
    JOIN request_statuses rs ON r.status_id = rs.id
    WHERE r.user_id = ? ORDER BY r.created_at DESC LIMIT ? OFFSET ?
');
$stmt->execute([$_SESSION['user_id'], $perPage, $offset]);
$requests = $stmt->fetchAll();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">Мои заявки</h2>
    <a href="submit_request.php" class="btn btn-primary">+ Новая заявка</a>
</div>
<?php if(empty($requests)): ?>
    <div class="alert alert-light border text-center">Заявок пока нет. <a href="services.php">Выберите услугу</a></div>
<?php else: ?>
<div class="table-responsive bg-body rounded shadow-sm p-2 mb-3">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light"><tr><th>Услуга</th><th>Дата работ</th><th>Описание</th><th>Статус</th><th>Создана</th></tr></thead>
        <tbody>
        <?php foreach($requests as $r): ?>
            <tr>
                <td><?= htmlspecialchars($r['service_name']) ?></td>
                <td><?= $r['work_date'] ? date('d.m.Y', strtotime($r['work_date'])) : '<span class="text-muted">Не указана</span>' ?></td>
                <td><?= htmlspecialchars($r['description']) ?></td>
                <td><span class="badge <?= htmlspecialchars($r['status_color']) ?>"><?= htmlspecialchars($r['status_name']) ?></span></td>
                <td><small class="text-body-secondary"><?= date('d.m.Y H:i', strtotime($r['created_at'])) ?></small></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if ($totalPages > 1): ?>
<nav aria-label="Пагинация">
  <ul class="pagination justify-content-center">
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
      <li class="page-item <?= $i === $page ? 'active' : '' ?>">
        <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
      </li>
    <?php endfor; ?>
  </ul>
</nav>
<?php endif; ?>
<?php endif; ?>
<?php include 'includes/footer.php'; ?>