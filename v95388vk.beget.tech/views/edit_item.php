<?php
session_start();

/* ===== Отладка ===== */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/* ===== БД ===== */
require_once __DIR__ . '/../config/db.php';

/* ===== Проверка админа ===== */
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

/* ===== Получаем ID ===== */
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    die('Некорректный ID');
}

$message = '';

/* ===== Получаем данные ===== */
$sql = "
SELECT i.id, i.name, i.sport, i.category, i.type, i.description, i.image_url, i.status, i.user_id,
       t.price_per_hour, t.price_per_day
    FROM inventory i
    LEFT JOIN tariffs t ON t.inventory_id = i.id
    WHERE i.id = ?
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) {
    die('Инвентарь не найден');
}

/* ===== Получаем текущий статус ===== */
$stmt = $pdo->prepare("SELECT status FROM inventory WHERE id = ?");
$stmt->execute([$id]);
$currentStatus = $stmt->fetchColumn();

/* ===== Обработка обновления ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name']);
    $sport = trim($_POST['sport']);
    $category = trim($_POST['category']);
    $type = trim($_POST['type']);
    $description = trim($_POST['description']);
    $price_per_hour = isset($_POST['price_per_hour']) ? (float)$_POST['price_per_hour'] : 0;
    $price_per_day  = isset($_POST['price_per_day']) ? (float)$_POST['price_per_day'] : 0;
   
    if ($name === '' || $category === '' || $sport === '' || $type === '') {
        $message = '<div class="alert alert-danger">Заполните все обязательные поля</div>';
    } else {

        /* ===== Загрузка изображения ===== */
        $image_url = $item['image_url'];

        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {

            $uploadDir = __DIR__ . '/../uploads/';
            $maxFileSize = 5 * 1024 * 1024;

            $allowedMimeTypes = array(
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/gif'  => 'gif',
            );

            if ($_FILES['image']['size'] > $maxFileSize) {
                die('Файл слишком большой (макс. 5 МБ)');
            }

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime  = finfo_file($finfo, $_FILES['image']['tmp_name']);
            finfo_close($finfo);

            if (!isset($allowedMimeTypes[$mime])) {
                die('Разрешены только JPG, PNG, GIF');
            }

            if (!getimagesize($_FILES['image']['tmp_name'])) {
                die('Файл не является изображением');
            }

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $extension = $allowedMimeTypes[$mime];
            $fileName = 'inventory_' . $id . '_' . time() . '.' . $extension;
            $destination = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
                $image_url = 'uploads/' . $fileName;
            }
        }

        /* ===== UPDATE inventory ===== */
        $sql = "
            UPDATE inventory
            SET name = ?, sport = ?, category = ?, type = ?, description = ?, image_url = ?
            WHERE id = ?
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $name,
            $sport,
            $category,
            $type,
            $description,
            $image_url,
            $id
        ]);

        /* ===== Обновление статуса ===== */
        if (isset($_POST['status']) && in_array($_POST['status'], ['free','busy','archived'], true)) {
            if ($currentStatus !== 'busy') {
                $stmt = $pdo->prepare("UPDATE inventory SET status = ? WHERE id = ?");
                $stmt->execute([$_POST['status'], $id]);
                $item['status'] = $_POST['status'];
            }
        }

        /* ===== Тариф ===== */
        $stmt = $pdo->prepare("SELECT id FROM tariffs WHERE inventory_id = ?");
        $stmt->execute([$id]);

        if ($stmt->fetch()) {
            $sql = "
                UPDATE tariffs
                SET price_per_hour = ?, price_per_day = ?
                WHERE inventory_id = ?
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$price_per_hour, $price_per_day, $id]);
        } else {
            $sql = "
                INSERT INTO tariffs (inventory_id, price_per_hour, price_per_day)
                VALUES (?, ?, ?)
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id, $price_per_hour, $price_per_day]);
        }

        /* ===== Обновляем данные формы ===== */
        $item['name'] = $name;
        $item['sport'] = $sport;
        $item['category'] = $category;
        $item['type'] = $type;
        $item['description'] = $description;
        $item['price_per_hour'] = $price_per_hour;
        $item['price_per_day'] = $price_per_day;
        $item['image_url'] = $image_url;

        $message = '<div class="alert alert-success">Инвентарь успешно обновлён</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактирование инвентаря</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-light bg-white border-bottom mb-4 px-4">
    <a class="navbar-brand fw-bold text-primary" href="index.php">🏸 Прокат инвентаря</a>
    <a href="admin_panel.php" class="btn btn-outline-danger btn-sm">Панель админа</a>
</nav>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-sm p-4">

                <h4 class="text-center mb-3">Редактирование инвентаря</h4>

                <?= $message ?>

                <form method="POST" enctype="multipart/form-data">

                    <div class="mb-3">
                        <label class="form-label">Название *</label>
                        <input type="text" name="name" class="form-control"
                               value="<?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>

                    <!-- Вид спорта -->
                    <div class="mb-3">
                        <label class="form-label">Вид спорта *</label>
                        <select name="sport" class="form-select" required>
                            <option value="">Выберите вид спорта</option>
                            <option value="Велоспорт" <?= $item['sport'] === 'Велоспорт' ? 'selected' : '' ?>>Велоспорт</option>
                            <option value="Горные лыжи" <?= $item['sport'] === 'Горные лыжи' ? 'selected' : '' ?>>Лыжный спорт</option>
                            <option value="Сноубординг" <?= $item['sport'] === 'Сноубординг' ? 'selected' : '' ?>>Сноубординг</option>
                            <option value="Кикскутеринг" <?= $item['sport'] === 'Кикскутеринг' ? 'selected' : '' ?>>Кикскутеринг</option>
                            <option value="Другое" <?= $item['sport'] === 'Другое' ? 'selected' : '' ?>>Другое</option>
                        </select>
                    </div>

                    <!-- Категория -->
                    <div class="mb-3">
                        <label class="form-label">Категория *</label>
                        <select name="category" class="form-select" required>
                            <option value="">Выберите категорию</option>
                            <option value="Инвентарь" <?= $item['category'] === 'Инвентарь' ? 'selected' : '' ?>>Инвентарь</option>
                            <option value="Экипировка" <?= $item['category'] === 'Экипировка' ? 'selected' : '' ?>>Экипировка</option>
                        </select>
                    </div>

                    <!-- Тип -->
                    <div class="mb-3">
                        <label class="form-label">Тип *</label>
                        <select name="type" class="form-select" required>
                            <option value="">Выберите тип</option>
                            <option value="Велосипед" <?= $item['type'] === 'Велосипед' ? 'selected' : '' ?>>Велосипед</option>
                            <option value="Лыжи" <?= $item['type'] === 'Лыжи' ? 'selected' : '' ?>>Лыжи</option>
                            <option value="Сноуборд" <?= $item['type'] === 'Сноуборд' ? 'selected' : '' ?>>Сноуборд</option>
                            <option value="Самокат" <?= $item['type'] === 'Самокат' ? 'selected' : '' ?>>Самокат</option>
                            <option value="Шлем" <?= $item['type'] === 'Шлем' ? 'selected' : '' ?>>Шлем</option>
                            <option value="Ботинки горнолыжные" <?= $item['type'] === 'Ботинки горнолыжные' ? 'selected' : '' ?>>Ботинки горнолыжные</option>
                            <option value="Крепления для сноуборда" <?= $item['type'] === 'Крепления для сноуборда' ? 'selected' : '' ?>>Крепления для сноуборда</option>
                            <option value="Палки горнолыжные" <?= $item['type'] === 'Палки горнолыжные' ? 'selected' : '' ?>>Палки горнолыжные</option>
                            <option value="Велозащита" <?= $item['type'] === 'Велозащита' ? 'selected' : '' ?>>Велозащита</option>
                            <option value="Прочее" <?= $item['type'] === 'Прочее' ? 'selected' : '' ?>>Прочее</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Цена за час</label>
                        <input type="number" name="price_per_hour" class="form-control"
                               min="0" step="10"
                               value="<?= isset($item['price_per_hour']) ? $item['price_per_hour'] : 0 ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Цена за день</label>
                        <input type="number" name="price_per_day" class="form-control"
                               min="0" step="10"
                               value="<?= isset($item['price_per_day']) ? $item['price_per_day'] : 0 ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Новое изображение</label>
                        <input type="file" name="image" class="form-control"
                               accept="image/jpeg,image/png,image/gif">
                        <?php if (!empty($item['image_url'])): ?>
                            <img src="<?= htmlspecialchars($item['image_url']) ?>"
                                 style="max-height:120px;margin-top:10px;">
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Описание</label>
                        <textarea name="description" class="form-control" rows="4"><?= htmlspecialchars($item['description'], ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                
                    <div class="mb-3">
                        <label class="form-label">Статус инвентаря</label>
                        <select name="status" class="form-select">
                            <option value="free" <?= $item['status'] === 'free' ? 'selected' : '' ?>>Доступен</option>
                            <option value="busy" <?= $item['status'] === 'busy' ? 'selected' : '' ?>>Занят</option>
                            <option value="archived" <?= $item['status'] === 'archived' ? 'selected' : '' ?>>В архиве</option>
                        </select>
                        <div class="form-text text-muted">
                            <?php if ($currentStatus === 'busy'): ?>
                                Занятый инвентарь нельзя изменить
                            <?php endif; ?>
                        </div>
                    </div>

                    
                    <div class="d-flex justify-content-between">
                        <a href="admin_panel.php" class="btn btn-secondary">Отмена</a>
                        <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                    </div>

                </form>

            </div>
        </div>
    </div>
</div>

</body>
</html>