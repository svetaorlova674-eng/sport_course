<?php
session_start();

// Обработка ошибок для отладки
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Подключаем БД
require_once __DIR__ . '/../config/db.php';

// Массив видов спорта → категории → типы
$sports = [
    'Велосипеды' => [
        'Велосипед горный' => 'Инвентарь',
        'Велосипед городской' => 'Инвентарь',
        'Шлем' => 'Экипировка',
        'Велозащита' => 'Экипировка'
    ],
    'Лыжи' => [
        'Лыжи горные' => 'Инвентарь',
        'Ботинки горнолыжные' => 'Экипировка',
        'Палки горнолыжные' => 'Инвентарь'
    ],
    'Сноуборды' => [
        'Сноуборд' => 'Инвентарь',
        'Ботинки' => 'Экипировка',
        'Крепления' => 'Инвентарь'
    ],
    'Самокаты' => [
        'Самокат городской' => 'Инвентарь',
        'Самокат трюковой' => 'Инвентарь'
    ],
    'Другое' => [
        'Прочее' => 'Инвентарь'
    ]
];


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
        $sport = trim($_POST['sport']);
        $category = trim($_POST['category']);
        $type = trim($_POST['type']);
        $description = trim($_POST['description']);
        $image_url = null;
        $price_per_day = isset($_POST['price_per_day']) ? floatval($_POST['price_per_day']) : 0;
        $price_per_hour = isset($_POST['price_per_hour']) ? floatval($_POST['price_per_hour']) : 0;

        if (empty($name) || empty($category) || empty($type) || empty($sport)) {
            $message = '<div class="alert alert-danger">Заполните название, вид спорта, категорию и тип!</div>';
        } else {
            $user_id = intval($_SESSION['user_id']);
        }

          /* ========= НАЧАЛО: ЗАГРУЗКА ФАЙЛА ========= */
if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {

    $uploadDir = __DIR__ . '/uploads/';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/pjpeg'=> 'jpg', // IE старые версии
        'image/png'  => 'png',
        'image/x-png'=> 'png', // старые варианты
        'image/gif'  => 'gif'
    ];

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $_FILES['file']['tmp_name']);
    finfo_close($finfo);

    if (!isset($allowed[$mime])) {
        // Альтернатива: проверить начало строки
        if (substr($mime, 0, 5) !== 'image') {
            throw new Exception('Разрешены только JPG, PNG, GIF');
        }
    }

    if (!getimagesize($_FILES['file']['tmp_name'])) {
        throw new Exception('Файл не является изображением');
    }

    // Определяем расширение
    $ext = isset($allowed[$mime]) ? $allowed[$mime] : pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
    $filename = uniqid('item_') . '.' . $ext;

    if (!move_uploaded_file($_FILES['file']['tmp_name'], $uploadDir . $filename)) {
        throw new Exception('Ошибка сохранения файла');
    }

    $image_url = 'uploads/' . $filename;
}
/* ========= КОНЕЦ: ЗАГРУЗКА ФАЙЛА ========= */

            // 3. Сохраняем в inventory без цены
            $sql = "INSERT INTO inventory (name, sport, category, type, description, image_url, status, user_id) 
                    VALUES (:name, :sport, :category, :type, :description, :image_url, 'free', :user_id)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':name' => $name,
                ':sport' => $sport,
                ':category' => $category,
                ':type' => $type,
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

<form method="POST" action="" id="addItemForm" enctype="multipart/form-data">


                    <div class="mb-3">
                        <label for="name" class="form-label">Название инвентаря *</label>
                        <input type="text" id="name" name="name" class="form-control" value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>" required placeholder="Например: Горный велосипед">
                    </div>
                    
                    <!-- Вид спорта -->
                    <div class="mb-3">
                        <label class="form-label">Вид спорта *</label>
                        <select name="sport" class="form-select" required>
                            <option value="">Выберите вид спорта</option>
                            <option value="Велоспорт">Велоспорт</option>
                            <option value="Горные лыжи">Лыжный спорт</option>
                            <option value="Сноубординг">Сноубординг</option>
                            <option value="Самокаты">Кикскутеринг</option>
                        </select>
                    </div>
                    
                    
                    
                    <div class="mb-3">
                        <label class="form-label">Категория *</label>
                        <select name="category" class="form-select" required>
                            <option value="">Выберите категорию</option>
                            <option value="Инвентарь">Инвентарь</option>
                            <option value="Экипировка">Экипировка</option>
                        </select>
                    </div>
                    
                    
                    <div class="mb-3">
                        <label class="form-label">Тип *</label>
                        <select name="type" class="form-select" required>
                            <option value="">Выберите тип</option>
                    
                            <!-- Инвентарь -->
                            <option value="Велосипед">Велосипед</option>
                            <option value="Лыжи">Лыжи</option>
                            <option value="Сноуборд">Сноуборд</option>
                            <option value="Самокат">Самокат</option>
                    
                            <!-- Экипировка -->
                            <option value="Шлем">Шлем</option>
                            <option value="Ботинки горнолыжные">Ботинки горнолыжные</option>
                            <option value="Крепления для сноуборда">Крепления для сноуборда</option>
                            <option value="Палки горнолыжные">Палки горнолыжные</option>
                            <option value="Велозащита">Велозащита</option>
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
                  
                        <div class="mb-3">
    <label class="form-label">Изображение инвентаря</label>
<input type="file"
       name="file"
       id="fileInput"
       class="form-control"
       accept="image/jpeg,image/png,image/gif"
       required>
</div>

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
document.getElementById('fileInput').addEventListener('change', function () {
    const file = this.files[0];
    const preview = document.getElementById('imagePreview');

    if (!file) {
        preview.style.display = 'none';
        preview.src = '';
        return;
    }

    if (!file.type.startsWith('image/')) {
        alert('Можно выбрать только изображение');
        this.value = '';
        preview.style.display = 'none';
        preview.src = '';
        return;
    }

    const reader = new FileReader();
    reader.onload = function (e) {
        preview.src = e.target.result;
        preview.style.display = 'block';
    };
    reader.readAsDataURL(file);
});
</script>
</body>
</html>
