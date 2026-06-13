<?php $title = 'Восстановление'; include 'includes/header.php';
$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $error = 'Некорректный email';
    else {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND is_verified = 1');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            $pdo->prepare('UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE email = ?')->execute([$token, $expires, $email]);
            @mail($email, 'Сброс пароля', "http://localhost/window-app/reset_password.php?token=$token", 'From: admin@windows.ru');
            $success = 'Ссылка отправлена на email.';
        } else $error = 'Пользователь не найден или email не подтвержден';
    }
}
?>
<h2 class="mb-3">Восстановление пароля</h2>
<?php if($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
<?php if($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
<form method="POST" class="card p-4 shadow-sm needs-validation" novalidate>
    <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required></div>
    <button type="submit" class="btn btn-warning w-100">Отправить ссылку</button>
</form>
<?php include 'includes/footer.php'; ?>