<?php
$title = 'Регистрация'; include 'includes/header.php';
$errors = []; $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';
    $pass2 = $_POST['password_confirm'] ?? '';

    if ($name === '') $errors['name'] = 'Укажите имя';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Некорректный email';
    else {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) $errors['email'] = 'Email уже зарегистрирован';
    }
    if (!preg_match('/^(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#])[^\s]{8,}$/', $pass)) {
        $errors['password'] = 'Мин. 8 символов, 1 заглавная, 1 цифра, 1 спецсимвол';
    } elseif ($pass !== $pass2) {
        $errors['password_confirm'] = 'Пароли не совпадают';
    }

    if (empty($errors)) {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        try {
            // Подтверждение почты полностью удалено, is_verified = 1
            $pdo->prepare('INSERT INTO users (role_id, name, email, password_hash, is_verified) VALUES (1, ?, ?, ?, 1)')
                ->execute([$name, $email, $hash]);
            $success = 'Регистрация успешна! Теперь вы можете войти.';
            $_POST = [];
        } catch (PDOException $e) {
            $errors['general'] = 'Ошибка регистрации';
        }
    }
}
?>
<h2 class="mb-3">Регистрация</h2>
<?php if(!empty($errors)): ?><div class="alert alert-danger"><?= implode('<br>', $errors) ?></div><?php endif; ?>
<?php if($success): ?><div class="alert alert-success"><?= $success ?> <a href="login.php" class="alert-link">Войти</a></div><?php endif; ?>
<form method="POST" class="card p-4 shadow-sm needs-validation" novalidate>
    <div class="mb-3">
        <label class="form-label">Имя</label>
        <input type="text" name="name" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
        <?php if(isset($errors['name'])): ?><div class="invalid-feedback"><?= $errors['name'] ?></div><?php endif; ?>
    </div>
    <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
        <?php if(isset($errors['email'])): ?><div class="invalid-feedback"><?= $errors['email'] ?></div><?php endif; ?>
    </div>
    <div class="mb-3">
        <label class="form-label">Пароль</label>
        <div class="input-group">
            <input type="password" name="password" id="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" required>
            <button type="button" class="btn btn-outline-secondary" id="generatePassBtn">🎲 Сгенерировать</button>
        </div>
        <small class="form-text text-muted">Нажмите кнопку для генерации надежного пароля или введите свой.</small>
        <?php if(isset($errors['password'])): ?><div class="invalid-feedback"><?= $errors['password'] ?></div><?php endif; ?>
    </div>
    <div class="mb-3">
        <label class="form-label">Повторите пароль</label>
        <input type="password" name="password_confirm" class="form-control <?= isset($errors['password_confirm']) ? 'is-invalid' : '' ?>" required>
        <?php if(isset($errors['password_confirm'])): ?><div class="invalid-feedback"><?= $errors['password_confirm'] ?></div><?php endif; ?>
    </div>
    <button type="submit" class="btn btn-primary w-100">Зарегистрироваться</button>
</form>
<?php include 'includes/footer.php'; ?>