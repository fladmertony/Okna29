<?php 
require_once 'includes/auth.php'; 
requireAdmin(); 
$title = 'Админ-панель'; 
include 'includes/header.php';

// Создаём папку для фото, если её нет
if (!is_dir('uploads')) mkdir('uploads', 0755, true);

// ==========================================
// 1. УПРАВЛЕНИЕ УСЛУГАМИ (ADD / EDIT / DELETE)
// ==========================================
$svcError = $svcSuccess = '';

// Добавление
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_service'])) {
    $name = trim($_POST['svc_name'] ?? '');
    $desc = trim($_POST['svc_desc'] ?? '');
    $needsCalc = isset($_POST['needs_calc']) ? 1 : 0;
    $imageName = null;

    if ($name === '') $svcError = 'Введите название услуги';
    elseif ($desc === '') $svcError = 'Введите описание услуги';
    else {
        if (isset($_FILES['svc_image']) && $_FILES['svc_image']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/jpeg', 'image/png', 'image/webp'];
            $ext = pathinfo($_FILES['svc_image']['name'], PATHINFO_EXTENSION);
            if (in_array($_FILES['svc_image']['type'], $allowed) && in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'webp'])) {
                $imageName = uniqid('svc_') . '.' . $ext;
                move_uploaded_file($_FILES['svc_image']['tmp_name'], 'uploads/' . $imageName);
            }
        }
        try {
            $pdo->prepare('INSERT INTO service_types (name, description, needs_calc, image) VALUES (?, ?, ?, ?)')
                ->execute([$name, $desc, $needsCalc, $imageName]);
            $svcSuccess = 'Услуга успешно добавлена!';
        } catch (PDOException $e) {
            $svcError = 'Ошибка: услуга с таким названием уже существует';
        }
    }
}

// Редактирование
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_service'])) {
    $id = (int)$_POST['service_id'];
    $name = trim($_POST['edit_name'] ?? '');
    $desc = trim($_POST['edit_desc'] ?? '');
    $needsCalc = isset($_POST['edit_needs_calc']) ? 1 : 0;
    $deleteImage = isset($_POST['delete_image']);

    $stmt = $pdo->prepare('SELECT image FROM service_types WHERE id = ?');
    $stmt->execute([$id]);
    $current = $stmt->fetch();
    $imageName = $current['image'] ?? null;

    if ($deleteImage && $imageName) { @unlink('uploads/' . $imageName); $imageName = null; }
    
    if (isset($_FILES['edit_image']) && $_FILES['edit_image']['error'] === UPLOAD_ERR_OK) {
        if ($imageName) @unlink('uploads/' . $imageName);
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        $ext = pathinfo($_FILES['edit_image']['name'], PATHINFO_EXTENSION);
        if (in_array($_FILES['edit_image']['type'], $allowed) && in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'webp'])) {
            $imageName = uniqid('svc_') . '.' . $ext;
            move_uploaded_file($_FILES['edit_image']['tmp_name'], 'uploads/' . $imageName);
        }
    }

    $pdo->prepare('UPDATE service_types SET name = ?, description = ?, needs_calc = ?, image = ? WHERE id = ?')
        ->execute([$name, $desc, $needsCalc, $imageName, $id]);
    header('Location: admin.php#services-tab'); exit;
}

// Удаление
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_service'])) {
    $id = (int)$_POST['service_id'];
    $stmt = $pdo->prepare('SELECT image FROM service_types WHERE id = ?');
    $stmt->execute([$id]);
    $svc = $stmt->fetch();
    if ($svc && $svc['image']) @unlink('uploads/' . $svc['image']);
    $pdo->prepare('DELETE FROM service_types WHERE id = ?')->execute([$id]);
    header('Location: admin.php#services-tab'); exit;
}

$allServices = $pdo->query('SELECT * FROM service_types ORDER BY id DESC')->fetchAll();

// ==========================================
// 2. УПРАВЛЕНИЕ ЗАЯВКАМИ (FILTER / PAGINATION / STATUS)
// ==========================================
$filter = $_GET['filter'] ?? '';
$status_filter = $_GET['status_filter'] ?? '';
$search_id = isset($_GET['search_id']) && $_GET['search_id'] !== '' ? (int)$_GET['search_id'] : null;

$service_types = $pdo->query('SELECT id, name FROM service_types ORDER BY name')->fetchAll();
$statuses = $pdo->query('SELECT id, name, bootstrap_color FROM request_statuses ORDER BY id')->fetchAll();

// Смена статуса
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['req_id'], $_POST['new_status_id'])) {
    $reqId = filter_var($_POST['req_id'], FILTER_VALIDATE_INT);
    $newStatus = filter_var($_POST['new_status_id'], FILTER_VALIDATE_INT);
    if ($reqId && $newStatus && in_array($newStatus, array_column($statuses, 'id'))) {
        $pdo->prepare('UPDATE requests SET status_id = ? WHERE id = ?')->execute([$newStatus, $reqId]);
    }
    $qs = http_build_query(['filter' => $filter, 'status_filter' => $status_filter, 'search_id' => $search_id]);
    header("Location: admin.php?" . $qs); exit;
}

// Пагинация и выборка
$perPage = 10;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

$conditions = []; $params = [];
if ($filter) { $conditions[] = 'r.service_type_id = ?'; $params[] = $filter; }
if ($status_filter) { $conditions[] = 'r.status_id = ?'; $params[] = $status_filter; }
if ($search_id > 0) { $conditions[] = 'r.id = ?'; $params[] = $search_id; }

$where = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM requests r $where");
$stmtCount->execute($params);
$total = $stmtCount->fetchColumn();
$totalPages = ceil($total / $perPage);

$sql = "SELECT r.*, u.name as user_name, u.email as user_email, st.name as service_name, rs.name as status_name, rs.bootstrap_color as status_color
        FROM requests r
        JOIN users u ON r.user_id = u.id
        JOIN service_types st ON r.service_type_id = st.id
        JOIN request_statuses rs ON r.status_id = rs.id
        $where ORDER BY r.created_at DESC LIMIT ? OFFSET ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([...$params, $perPage, $offset]);
$requests = $stmt->fetchAll();

$qsParams = array_filter(['filter' => $filter, 'status_filter' => $status_filter, 'search_id' => $search_id], fn($v) => $v !== '' && $v !== null);
$baseQS = http_build_query($qsParams);
$baseQS = $baseQS ? $baseQS . '&' : '';
?>

<h2 class="mb-3">Администрирование</h2>

<ul class="nav nav-tabs mb-4" id="adminTabs" role="tablist">
    <li class="nav-item" role="presentation"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#services-tab" type="button">🛠️ Услуги</button></li>
    <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#requests-tab" type="button">📋 Заявки</button></li>
</ul>

<div class="tab-content">
    <!-- ==================== ВКЛАДКА УСЛУГ ==================== -->
    <div class="tab-pane fade show active" id="services-tab">
        <div class="card shadow-sm p-4 mb-4">
            <h5 class="fw-bold mb-3">➕ Добавить новую услугу</h5>
            <?php if($svcSuccess): ?><div class="alert alert-success py-2"><?= $svcSuccess ?></div><?php endif; ?>
            <?php if($svcError): ?><div class="alert alert-danger py-2"><?= $svcError ?></div><?php endif; ?>
            <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                <input type="hidden" name="add_service" value="1">
                <div class="row g-3">
                    <div class="col-md-4"><label class="form-label">Название</label><input type="text" name="svc_name" class="form-control" required></div>
                    <div class="col-md-4"><label class="form-label">Описание</label><input type="text" name="svc_desc" class="form-control" required></div>
                    <div class="col-md-2"><label class="form-label">Фото</label><input type="file" name="svc_image" class="form-control" accept="image/*"></div>
                    <div class="col-md-1 d-flex align-items-center pt-4">
                        <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="needs_calc" id="add_nc" checked><label class="form-check-label small" for="add_nc">Калькулятор</label></div>
                    </div>
                    <div class="col-md-1 d-flex align-items-end"><button type="submit" class="btn btn-success w-100">Добавить</button></div>
                </div>
            </form>
        </div>

        <h5 class="fw-bold mb-3">📋 Список услуг</h5>
        <div class="row g-3">
            <?php foreach($allServices as $s): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border shadow-sm">
                    <?php if($s['image']): ?>
                        <img src="uploads/<?= htmlspecialchars($s['image']) ?>" class="card-img-top" alt="<?= htmlspecialchars($s['name']) ?>" style="height: 180px; object-fit: cover;">
                    <?php else: ?>
                        <div class="bg-secondary bg-opacity-10 text-center py-4"><i class="bi bi-image display-6 text-secondary"></i></div>
                    <?php endif; ?>
                    <div class="card-body">
                        <h6 class="card-title fw-bold"><?= htmlspecialchars($s['name']) ?></h6>
                        <p class="card-text small text-body-secondary mb-2"><?= htmlspecialchars($s['description']) ?></p>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-outline-primary flex-grow-1" data-bs-toggle="modal" data-bs-target="#editModal<?= $s['id'] ?>">✏️ Ред.</button>
                            <form method="POST" onsubmit="return confirm('Удалить услугу? Заявки с этой услугой останутся.');">
                                <input type="hidden" name="delete_service" value="1">
                                <input type="hidden" name="service_id" value="<?= $s['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">🗑️</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Модальное окно редактирования -->
                <div class="modal fade" id="editModal<?= $s['id'] ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <form method="POST" enctype="multipart/form-data" class="modal-content">
                            <div class="modal-header"><h5 class="modal-title">Редактировать услугу</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                            <div class="modal-body">
                                <input type="hidden" name="edit_service" value="1">
                                <input type="hidden" name="service_id" value="<?= $s['id'] ?>">
                                <div class="mb-3"><label class="form-label">Название</label><input type="text" name="edit_name" class="form-control" value="<?= htmlspecialchars($s['name']) ?>" required></div>
                                <div class="mb-3"><label class="form-label">Описание</label><input type="text" name="edit_desc" class="form-control" value="<?= htmlspecialchars($s['description']) ?>" required></div>
                                <div class="mb-3 form-check form-switch"><input class="form-check-input" type="checkbox" name="edit_needs_calc" id="edit_nc<?= $s['id'] ?>" <?= $s['needs_calc'] ? 'checked' : '' ?>><label class="form-check-label" for="edit_nc<?= $s['id'] ?>">Показывать калькулятор</label></div>
                                <div class="mb-3">
                                    <label class="form-label">Фото</label>
                                    <?php if($s['image']): ?>
                                        <div class="mb-2"><img src="uploads/<?= htmlspecialchars($s['image']) ?>" class="img-thumbnail" style="max-height: 100px;"></div>
                                        <div class="form-check"><input class="form-check-input" type="checkbox" name="delete_image" id="del<?= $s['id'] ?>"><label class="form-check-label small" for="del<?= $s['id'] ?>">Удалить текущее</label></div>
                                    <?php else: ?>
                                        <p class="text-muted small">Фото не загружено</p>
                                    <?php endif; ?>
                                    <input type="file" name="edit_image" class="form-control mt-2" accept="image/*">
                                </div>
                            </div>
                            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button><button type="submit" class="btn btn-primary">Сохранить</button></div>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ==================== ВКЛАДКА ЗАЯВОК ==================== -->
    <div class="tab-pane fade" id="requests-tab">
        <h4 class="mb-3">Управление заявками</h4>
        <form class="row g-2 mb-3 align-items-end" method="GET">
            <div class="col-auto">
                <label class="form-label small text-body-secondary">Тип работ</label>
                <select name="filter" class="form-select" onchange="this.form.submit()">
                    <option value="">Все типы</option>
                    <?php foreach($service_types as $t): ?>
                        <option value="<?= $t['id'] ?>" <?= $filter == $t['id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label small text-body-secondary">Статус</label>
                <select name="status_filter" class="form-select" onchange="this.form.submit()">
                    <option value="">Все статусы</option>
                    <?php foreach($statuses as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= $status_filter == $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label small text-body-secondary">№ заказа</label>
                <div class="input-group">
                    <input type="number" name="search_id" class="form-control" placeholder="ID" value="<?= $search_id ?? '' ?>">
                    <button type="submit" class="btn btn-outline-primary"><i class="bi bi-search"></i></button>
                </div>
            </div>
            <div class="col-auto d-flex align-items-end">
                <a href="admin.php" class="btn btn-outline-secondary btn-sm">Сбросить всё</a>
            </div>
        </form>

        <div class="table-responsive bg-body rounded shadow-sm p-2 mb-3">
            <table class="table table-striped align-middle mb-0">
                <thead><tr><th>ID</th><th>Клиент</th><th>Адрес</th><th>Услуга</th><th>Дата</th><th>Оценка</th><th>Статус</th><th>Действие</th></tr></thead>
                <tbody>
                <?php if(empty($requests)): ?>
                    <tr><td colspan="8" class="text-center py-4 text-body-secondary">Заявки не найдены</td></tr>
                <?php else: foreach($requests as $r): 
                    preg_match('/💰 Итого: (.+)/', $r['description'], $pm);
                    $price = $pm ? htmlspecialchars($pm[1]) : '—';
                    // Убираем калькуляторный блок для вывода чистого комментария
                    $cleanDesc = preg_replace('/.*📝 /s', '', $r['description']) ?: $r['description'];
                ?>
                    <tr>
                        <td><span class="badge bg-light text-dark">#<?= $r['id'] ?></span></td>
                        <td><div><?= htmlspecialchars($r['user_name']) ?></div><small class="text-body-secondary"><?= htmlspecialchars($r['user_email']) ?></small></td>
                        <td><small title="<?= htmlspecialchars($r['work_address']) ?>"><?= htmlspecialchars(mb_substr($r['work_address'] ?? '—', 0, 20)) ?><?= mb_strlen($r['work_address']??'') > 20 ? '...' : '' ?></small></td>
                        <td><?= htmlspecialchars($r['service_name']) ?></td>
                        <td><?= $r['work_date'] ? date('d.m.Y', strtotime($r['work_date'])) : '—' ?></td>
                        <td><small class="fw-bold text-success"><?= $price ?></small></td>
                        <td><span class="badge <?= htmlspecialchars($r['status_color']) ?>"><?= htmlspecialchars($r['status_name']) ?></span></td>
                        <td>
                            <form method="POST" class="d-flex gap-1">
                                <input type="hidden" name="req_id" value="<?= $r['id'] ?>">
                                <select name="new_status_id" class="form-select form-select-sm" style="min-width: 130px;">
                                    <?php foreach($statuses as $s): ?>
                                        <option value="<?= $s['id'] ?>" <?= $r['status_id']==$s['id']?'selected':'' ?>><?= htmlspecialchars($s['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn btn-sm btn-outline-primary">✓</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
        <nav aria-label="Пагинация">
          <ul class="pagination justify-content-center">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
              <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                <a class="page-link" href="?<?= $baseQS ?>page=<?= $i ?>"><?= $i ?></a>
              </li>
            <?php endfor; ?>
          </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php include 'includes/footer.php'; ?>