<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Получаем инвентарь с тарифами
$items = $pdo->query("
    SELECT i.*, t.price_per_day, t.price_per_hour
    FROM inventory i
    LEFT JOIN tariffs t ON i.id = t.inventory_id
    ORDER BY i.id DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Прокат спортивного инвентаря</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<!-- Навигация -->
<nav class="navbar navbar-light bg-light px-4 mb-4 shadow-sm">
    <span class="navbar-brand mb-0 h1">Прокат Спорта</span>
    <div>
        <?php if (isset($_SESSION['user_id'])): ?>
            <span class="me-3">Привет!</span>
            <?php if ($_SESSION['user_role'] === 'admin'): ?>
                <a href="add_item.php" class="btn btn-success btn-sm">+ Добавить инвентарь</a>
            <?php endif; ?>
            <a href="logout.php" class="btn btn-dark btn-sm">Выйти</a>
        <?php else: ?>
            <a href="login.php" class="btn btn-primary btn-sm">Войти</a>
            <a href="register.php" class="btn btn-outline-primary btn-sm">Регистрация</a>
        <?php endif; ?>
    </div>
</nav>

<div class="container">
    <h2 class="mb-4">Каталог инвентаря</h2>
    
    <div class="row">
        <?php foreach ($items as $item): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <?php 
                    $img = !empty($item['image_url']) ? $item['image_url'] : 'https://via.placeholder.com/300x200/3D9970/FFFFFF?text=Инвентарь';
                    ?>
                    <img src="<?= htmlspecialchars($img) ?>" class="card-img-top" alt="Фото инвентаря" style="height: 200px; object-fit: cover;">
                    
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($item['name']) ?></h5>
                        <p class="card-text text-muted"><?= htmlspecialchars($item['category']) ?></p>
                        
                        <!-- ЦЕНЫ -->
                        <?php if (isset($item['price_per_day']) && $item['price_per_day'] > 0): ?>
                            <p class="card-text fw-bold text-success">
                                💰 Цена за день: <?= $item['price_per_day'] ?> ₽
                            </p>
                        <?php endif; ?>
                        <?php if (isset($item['price_per_hour']) && $item['price_per_hour'] > 0): ?>
                            <p class="card-text fw-bold text-primary">
                                ⏱ Цена за час: <?= $item['price_per_hour'] ?> ₽
                            </p>
                        <?php endif; ?>
                        
                        <p class="card-text">
                            <?php 
                            $description = $item['description'];
                            if (strlen($description) > 100) {
                                echo htmlspecialchars(substr($description, 0, 100)) . '...';
                            } else {
                                echo htmlspecialchars($description);
                            }
                            ?>
                        </p>
                        
                        <p class="card-text fw-bold">
                            Статус: 
                            <?php if ($item['status'] === 'free'): ?>
                                <span class="badge bg-success">✅ Свободен</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">⏳ Занят</span>
                            <?php endif; ?>
                        </p>
                    </div>
                    
                    <div class="card-footer bg-white border-top-0">
                        <?php if ($item['status'] === 'free'): ?>
                            <a href="rent.php?id=<?= $item['id'] ?>" class="btn btn-primary w-100">Арендовать</a>
                        <?php else: ?>
                            <button class="btn btn-secondary w-100" disabled>Занят</button>
                        <?php endif; ?>

                        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                            <a href="delete_item.php?id=<?= $item['id'] ?>" class="btn btn-danger w-100 mt-2" onclick="return confirm('Удалить этот инвентарь?')">Удалить</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (count($items) === 0): ?>
            <div class="col-12">
                <p class="text-muted">Инвентарь пока отсутствует. Зайдите под админом и добавьте его.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
