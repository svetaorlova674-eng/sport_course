<?php
$pageTitle = 'Панель администратора — SibGo';
$css = array('admin.css');
require ROOT . '/views/layout/header.php';
?>

<nav class="navbar navbar-light bg-light px-3 px-md-4 mb-4 shadow-sm">
    <div class="container-fluid d-flex justify-content-between align-items-center">
        <?php require ROOT . '/views/layout/logo.php'; ?>
        <span class="badge bg-danger fs-6">Администратор</span>
    </div>
</nav>

<div class="container pb-5">
    <div class="text-center mb-5">
        <h1 class="display-4">Панель Администратора</h1>
        <p class="lead">Добро пожаловать! Здесь вы управляете прокатом спортивного инвентаря.</p>
    </div>

    <div class="row g-4 justify-content-center">
        <div class="col-md-4">
            <div class="card admin-card shadow-sm text-center p-4">
                <h5 class="card-title mb-3">Добавить инвентарь</h5>
                <p class="card-text text-muted">Создайте новый предмет для проката</p>
                <a href="/admin/add-item" class="btn btn-primary btn-custom">+ Добавить</a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card admin-card shadow-sm text-center p-4">
                <h5 class="card-title mb-3">Управление арендами</h5>
                <p class="card-text text-muted">Проверьте текущее состояние аренды</p>
                <a href="/admin/rentals" class="btn btn-primary btn-custom">Перейти к арендам</a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card admin-card shadow-sm text-center p-4">
                <h5 class="card-title mb-3">Просмотр каталога</h5>
                <p class="card-text text-muted">Посмотрите текущие предметы проката</p>
                <a href="/" class="btn btn-primary btn-custom">Перейти в каталог</a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card admin-card shadow-sm text-center p-4">
                <h5 class="card-title mb-3">Генератор данных</h5>
                <p class="card-text text-muted">Создание тестовых записей и CSV бэкап</p>
                <a href="/admin/seeder" class="btn btn-warning btn-custom">Сгенерировать</a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card admin-card shadow-sm text-center p-4">
                <h5 class="card-title mb-3">Выйти</h5>
                <p class="card-text text-muted">Завершите текущую сессию администратора</p>
                <a href="/logout" class="btn btn-danger btn-custom">Выйти</a>
            </div>
        </div>
    </div>
</div>

<?php require ROOT . '/views/layout/footer.php'; ?>