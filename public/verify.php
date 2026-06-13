<?php require 'includes/auth.php'; $title = 'Подтверждение'; include 'includes/header.php';
$token = $_GET['token'] ?? '';
$stmt = $pdo->prepare('UPDATE users SET is_verified = 1, verification_token = NULL WHERE verification_token = ? AND is_verified = 0');
$stmt->execute([$token]);
if ($stmt->rowCount()) echo '<div class="alert alert-success text-center mt-4">✅ Email подтвержден! <a href="login.php" class="alert-link">Войти</a></div>';
else echo '<div class="alert alert-danger text-center mt-4">❌ Ссылка недействительна или уже использована.</div>';
include 'includes/footer.php';