<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';

function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

function requireAdmin() {
    requireAuth();
    if ($_SESSION['role'] !== 'admin') {
        header('Location: index.php');
        exit;
    }
}