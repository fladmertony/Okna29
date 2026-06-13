<?php require_once __DIR__ . '/auth.php'; ?>
<!DOCTYPE html>
<html lang="ru" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Оконные Решения' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100">
<nav class="navbar navbar-expand-lg bg-body-tertiary border-bottom">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">🪟 Оконные Решения</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="index.php">Главная</a></li>
                <li class="nav-item"><a class="nav-link" href="services.php">Услуги</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">Мои заявки</a></li>
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                        <li class="nav-item"><a class="nav-link" href="admin.php">Админ-панель</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Выход</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="login.php">Вход</a></li>
                    <li class="nav-item"><a class="nav-link" href="register.php">Регистрация</a></li>
                <?php endif; ?>
            </ul>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary btn-sm" id="themeToggle">🌙 Тема</button>
            </div>
        </div>
    </div>
</nav>
<main class="flex-grow-1">
<div class="container py-4">