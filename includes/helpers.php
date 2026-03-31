<?php
/**
 * helpers.php — вспомогательные функции, доступные везде
 */

// Безопасный вывод строки
function e($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// Редирект
function redirect($url) {
    header('Location: ' . $url);
    exit;
}

// Проверка: авторизован ли пользователь
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Проверка: является ли пользователь админом
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

// Требовать авторизацию — иначе редирект на /login
function requireLogin() {
    if (!isLoggedIn()) {
        redirect('/login');
    }
}

// Требовать права администратора
function requireAdmin() {
    if (!isLoggedIn() || !isAdmin()) {
        die('ДОСТУП ЗАПРЕЩЕН. <a href="/login">Войти</a>');
    }
}

// Генерация CSRF-токена (если нет — создаём)
function csrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Проверка CSRF-токена из POST
function checkCsrf() {
    if (
        empty($_POST['csrf_token']) ||
        empty($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        die('Ошибка безопасности: неверный CSRF-токен');
    }
}

// Подключить view-файл
// $view  — путь относительно /views/, например 'auth/login'
// $data  — массив переменных, которые будут доступны в шаблоне
function render($view, $data = array()) {
    extract($data);
    $file = ROOT . '/views/' . $view . '.php';
    if (!file_exists($file)) {
        die('View не найден: ' . e($view));
    }
    require $file;
}

// Базовый URL для ссылок на публичные файлы (css/js/uploads)
function asset($path) {
    return '/public_html/' . ltrim($path, '/');
}

// Автообновление статусов инвентаря (вызывается в нужных контроллерах)
function runStatusUpdate($pdo) {
    // Переводим в BUSY если аренда началась или начнётся через ≤1 час
    $pdo->prepare("
        UPDATE inventory i
        JOIN (
            SELECT inventory_id
            FROM rent_history
            WHERE start_time <= DATE_ADD(NOW(), INTERVAL 1 HOUR)
              AND end_time > NOW()
        ) active_rents ON i.id = active_rents.inventory_id
        SET i.status = 'busy'
        WHERE i.status != 'archived'
    ")->execute();

    // Возвращаем в FREE если аренда закончилась
    $pdo->prepare("
        UPDATE inventory i
        JOIN (
            SELECT inventory_id
            FROM rent_history
            WHERE end_time <= NOW()
        ) finished_rents ON i.id = finished_rents.inventory_id
        SET i.status = 'free'
        WHERE i.status = 'busy'
    ")->execute();
}
