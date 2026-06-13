<?php $title = 'Вход'; include 'includes/header.php';
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $pass = $_POST['password'] ?? '';
    $stmt = $pdo->prepare('SELECT u.*, r.name as role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user && password_verify($pass, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role_name'];
        header('Location: dashboard.php'); exit;
    } else {
        $errors['auth'] = 'Неверный email или пароль';
    }
}
?>
<h2 class="mb-3">Вход</h2>
<?php if(!empty($errors)): ?><div class="alert alert-danger"><?= $errors['auth'] ?></div><?php endif; ?>
<form method="POST" class="card p-4 shadow-sm needs-validation" novalidate>
    <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required></div>
    <div class="mb-3"><label class="form-label">Пароль</label><input type="password" name="password" class="form-control" required></div>
    <button type="submit" class="btn btn-primary w-100">Войти</button>
    <a href="forgot_password.php" class="d-block mt-3 text-center small">Забыли пароль?</a>
</form>
<?php include 'includes/footer.php'; ?>