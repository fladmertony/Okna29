<?php $title = 'Новый пароль'; include 'includes/header.php';
$token = $_GET['token'] ?? '';
$errors = []; $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $token) {
    $pass = $_POST['password'] ?? '';
    if (!preg_match('/^(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $pass)) $errors['password'] = 'Неверный формат пароля';
    else {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE reset_token = ? AND reset_token_expires > NOW()');
        $stmt->execute([$token]);
        if ($stmt->fetch()) {
            $pdo->prepare('UPDATE users SET password_hash = ?, reset_token = NULL, reset_token_expires = NULL WHERE reset_token = ?')
               ->execute([password_hash($pass, PASSWORD_DEFAULT), $token]);
            $success = 'Пароль изменен. <a href="login.php">Войти</a>';
        } else $errors['general'] = 'Ссылка истекла или неверна';
    }
}
if (isset($_GET['token']) && !$success): ?>
<h2 class="mb-3">Установите новый пароль</h2>
<?php if(!empty($errors)): ?><div class="alert alert-danger"><?= reset($errors) ?></div><?php endif; ?>
<form method="POST" class="card p-4 shadow-sm needs-validation" novalidate>
    <div class="mb-3">
        <label class="form-label">Новый пароль</label>
        <input type="password" name="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" required minlength="8">
        <?php if(isset($errors['password'])): ?><div class="invalid-feedback"><?= $errors['password'] ?></div><?php endif; ?>
    </div>
    <button type="submit" class="btn btn-primary w-100">Сохранить</button>
</form>
<?php endif; include 'includes/footer.php'; ?>