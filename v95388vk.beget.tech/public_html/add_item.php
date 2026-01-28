<?php
session_start();

// Обработка ошибок для отладки
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Подключаем БД
require_once __DIR__ . '/../config/db.php';

// Проверяем админские права
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$message = '';

// 2. Если нажата кнопка "Сохранить"
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $name = trim($_POST['name']);
        $category = trim($_POST['category']);
        $description = trim($_POST['description']);
        $image_url = trim($_POST['image_url']);
        $price_per_day = isset($_POST['price_per_day']) ? floatval($_POST['price_per_day']) : 0;
        $price_per_hour = isset($_POST['price_per_hour']) ? floatval($_POST['price_per_hour']) : 0;

        if (empty($name) || empty($category)) {
            $message = '<div class="alert alert-danger">Заполните название и категорию!</div>';
        } else {
            $user_id = intval($_SESSION['user_id']);

            // 3. Сохраняем в inventory без цены
            $sql = "INSERT INTO inventory (name, category, description, image_url, status, user_id) 
                    VALUES (:name, :category, :description, :image_url, 'free', :user_id)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':name' => $name,
                ':category' => $category,
                ':description' => $description,
                ':image_url' => $image_url,
                ':user_id' => $user_id
            ]);

            // Получаем ID нового инвентаря
            $inventory_id = $pdo->lastInsertId();

            // ✅ Создаём тариф в tariffs
            if ($price_per_day > 0 || $price_per_hour > 0) {
                $stmt = $pdo->prepare("
                    INSERT INTO tariffs (inventory_id, price_per_hour, price_per_day)
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$inventory_id, $price_per_hour, $price_per_day]);
            }

            $message = '<div class="alert alert-success">Инвентарь успешно добавлен!</div>';
            $_POST = [];
        }
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">Ошибка БД: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Добавить инвентарь - Прокат спортивного инвентаря</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .card { border: none; box-shadow: 0 0 15px rgba(0,0,0,.08); }
        .form-label { font-weight: 500; color: #495057; }
        .btn-submit { padding: 10px 30px; font-weight: 500; }
        .preview-img { max-height: 200px; object-fit: cover; border-radius: 8px; display: none; margin-top: 10px; }
    </style>
</head>
<body>

<!-- Навигация -->
<nav class="navbar navbar-expand-lg navbar-light bg-white px-4 mb-4 border-bottom">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold text-primary" href="index.php">🏸 Прокат инвентаря</a>
        <div class="d-flex align-items-center">
            <span class="me-3 text-muted">
                <?= isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Администратор' ?>
                <span class="badge bg-danger ms-1">Admin</span>
            </span>
            <a href="admin_panel.php" class="btn btn-outline-danger btn-sm me-2">Панель админа</a>
            <a href="index.php" class="btn btn-outline-primary btn-sm me-2">На главную</a>
            <a href="logout.php" class="btn btn-dark btn-sm">Выйти</a>
        </div>
    </div>
</nav>

<!-- Основной контент -->
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card p-4">
                <div class="text-center mb-4">
                    <h2 class="mb-2">Добавление нового инвентаря</h2>
                    <p class="text-muted">Заполните форму для добавления спортивного инвентаря</p>
                </div>

                <?= $message ?>

                <form method="POST" id="addItemForm">
                    <div class="mb-3">
                        <label for="name" class="form-label">Название инвентаря *</label>
                        <input type="text" id="name" name="name" class="form-control" value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>" required placeholder="Например: Горный велосипед">
                    </div>

                    <div class="mb-3">
                        <label for="category" class="form-label">Категория *</label>
                        <select id="category" name="category" class="form-select" required>
                            <option value="">Выберите категорию</option>
                            <option value="Велосипеды" <?= isset($_POST['category']) && $_POST['category'] == 'Велосипеды' ? 'selected' : '' ?>>Велосипеды</option>
                            <option value="Лыжи" <?= isset($_POST['category']) && $_POST['category'] == 'Лыжи' ? 'selected' : '' ?>>Лыжи</option>
                            <option value="Сноуборды" <?= isset($_POST['category']) && $_POST['category'] == 'Сноуборды' ? 'selected' : '' ?>>Сноуборды</option>
                            <option value="Ролики" <?= isset($_POST['category']) && $_POST['category'] == 'Ролики' ? 'selected' : '' ?>>Ролики</option>
                            <option value="Коньки" <?= isset($_POST['category']) && $_POST['category'] == 'Коньки' ? 'selected' : '' ?>>Коньки</option>
                            <option value="Другое" <?= isset($_POST['category']) && $_POST['category'] == 'Другое' ? 'selected' : '' ?>>Другое</option>
                        </select>
                    </div>

                    <!-- Добавляем цену за час -->
                    <div class="mb-3">
                        <label for="price_per_hour" class="form-label">Цена за час (руб.)</label>
                        <input type="number" id="price_per_hour" name="price_per_hour" class="form-control" min="0" step="10" value="<?= isset($_POST['price_per_hour']) ? htmlspecialchars($_POST['price_per_hour']) : '0' ?>" placeholder="0">
                    </div>

                    <div class="mb-3">
                        <label for="price_per_day" class="form-label">Цена за день (руб.)</label>
                        <input type="number" id="price_per_day" name="price_per_day" class="form-control" min="0" step="10" value="<?= isset($_POST['price_per_day']) ? htmlspecialchars($_POST['price_per_day']) : '0' ?>" placeholder="0">
                        <small class="text-muted">Если 0 — цена не отображается</small>
                    </div>

                    <div class="mb-3">
                        <label for="image_url" class="form-label">Ссылка на изображение</label>
                        <input type="url" id="image_url" name="image_url" class="form-control" value="<?= isset($_POST['image_url']) ? htmlspecialchars($_POST['image_url']) : '' ?>" placeholder="https://example.com/image.jpg">
                        <img id="imagePreview" src="" alt="Превью" class="img-fluid mt-2 preview-img">
                    </div>

                    <div class="mb-4">
                        <label for="description" class="form-label">Описание инвентаря</label>
                        <textarea id="description" name="description" class="form-control" rows="4" placeholder="Опишите состояние, особенности, комплектацию..."><?= isset($_POST['description']) ? htmlspecialchars($_POST['description']) : '' ?></textarea>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="index.php" class="btn btn-secondary me-md-2">Отмена</a>
                        <button type="submit" class="btn btn-primary btn-submit">Сохранить инвентарь</button>
                    </div>
                </form>

                <div class="mt-4 pt-3 border-top">
                    <div class="alert alert-info mb-0">
                        <small>
                            <strong>Информация:</strong> Этот инвентарь будет привязан к вашему аккаунту 
                            (ID: <?= isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'не определен' ?>, 
                            Имя: <?= isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Администратор' ?>)
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Предпросмотр изображения
    document.getElementById('image_url').addEventListener('input', function() {
        const preview = document.getElementById('imagePreview');
        const url = this.value.trim();
        if (url) {
            preview.src = url;
            preview.style.display = 'block';
            preview.onerror = function() { preview.style.display = 'none'; preview.src = ''; };
        } else {
            preview.style.display = 'none';
            preview.src = '';
        }
    });

    // Валидация формы
    document.getElementById('addItemForm').addEventListener('submit', function(e) {
        const name = document.getElementById('name').value.trim();
        const category = document.getElementById('category').value;
        if (!name || !category) { e.preventDefault(); alert('Пожалуйста, заполните обязательные поля (Название и Категория)'); return false; }
    });
</script>
</body>
</html>
