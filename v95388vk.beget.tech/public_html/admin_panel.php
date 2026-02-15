<?php
// Защита админки
require 'check_admin.php';
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Панель администратора</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .admin-card {
            transition: transform 0.2s;
        }
        .admin-card:hover {
            transform: scale(1.05);
        }
        .btn-custom {
            min-width: 200px;
        }
    </style>
</head>
<body class="p-5">

<div class="container">
    <div class="text-center mb-5">
        <h1 class="display-4">Панель Администратора</h1>
        <p class="lead">Добро пожаловать! Здесь вы управляете прокатом спортивного инвентаря.</p>
    </div>

    <div class="row g-4 justify-content-center">
        <!-- Кнопка добавления инвентаря -->
        <div class="col-md-4">
            <div class="card admin-card shadow-sm text-center p-4">
                <h5 class="card-title mb-3">Добавить инвентарь</h5>
                <p class="card-text text-muted">Создайте новый предмет для проката</p>
                <a href="add_item.php" class="btn btn-primary btn-custom">+ Добавить</a>
            </div>
        </div>


        <div class="col-md-4">
            <div class="card admin-card shadow-sm text-center p-4">
                <h5 class="card-title mb-3">Управление арендами</h5>
                <p class="card-text text-muted">Проверьте текущее состояние аренды</p>
                <a href="admin_rentals.php" class="btn btn-primary btn-custom">Перейти к арендам</a>
            </div>
        </div>


        <!-- Кнопка просмотра каталога -->
        <div class="col-md-4">
            <div class="card admin-card shadow-sm text-center p-4">
                <h5 class="card-title mb-3">Просмотр каталога</h5>
                <p class="card-text text-muted">Посмотрите текущие предметы проката</p>
                <a href="index.php" class="btn btn-primary btn-custom">Перейти в каталог</a>
            </div>
        </div>

        <!-- Кнопка выхода -->
        <div class="col-md-4">
            <div class="card admin-card shadow-sm text-center p-4">
                <h5 class="card-title mb-3">Выйти</h5>
                <p class="card-text text-muted">Завершите текущую сессию администратора</p>
                <a href="logout.php" class="btn btn-danger btn-custom">Выйти</a>
            </div>
        </div>
    </div>
</div>

</body>
</html>
