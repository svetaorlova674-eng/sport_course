<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Принимаем только POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: change_password.php');
    exit;
}

// CSRF-проверка
if (
    empty($_POST['csrf_token']) ||
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    die('Ошибка безопасности: CSRF-токен неверен');
}

$old_password     = $_POST['old_password'];
$new_password     = $_POST['new_password'];
$confirm_password = $_POST['confirm_password'];

// Проверка заполнения
if (!$old_password || !$new_password || !$confirm_password) {
    $_SESSION['error'] = 'Заполните все поля';
    header('Location: change_password.php');
    exit;
}

// Проверка совпадения паролей
if ($new_password !== $confirm_password) {
    $_SESSION['error'] = 'Новые пароли не совпадают';
    header('Location: change_password.php');
    exit;
}

// Проверка длины нового пароля
if (strlen($new_password) < 8) {
    $_SESSION['error'] = 'Новый пароль должен быть минимум 8 символов';
    header('Location: change_password.php');
    exit;
}

// Получаем текущий пароль
$stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !password_verify($old_password, $user['password_hash'])) {
    $_SESSION['error'] = 'Старый пароль введён неверно';
    header('Location: change_password.php');
    exit;
}

// Хешируем новый пароль
$new_hash = password_hash($new_password, PASSWORD_DEFAULT);

// Обновляем в БД
$stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
$stmt->execute([$new_hash, $_SESSION['user_id']]);

$_SESSION['success'] = 'Пароль успешно изменён';
header('Location: change_password.php');
exit;
