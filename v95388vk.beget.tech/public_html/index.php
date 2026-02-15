<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/status_update.php';


if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
}

$pdo->exec("SET time_zone = '+07:00'");

// Проверка роли
$isAdmin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';

// --- Формируем основной SELECT ---
$sql = "
    SELECT i.*, t.price_per_day, t.price_per_hour
    FROM inventory i
    LEFT JOIN tariffs t ON i.id = t.inventory_id
";

$params = [];
$where_clauses = [];

// Для обычного пользователя скрываем архив
if (!$isAdmin) {
    $where_clauses[] = "i.status != 'archived'";
}

// Поиск по названию
if (!empty($_GET['q'])) {
    $where_clauses[] = "i.name LIKE ?";
    $params[] = "%" . $_GET['q'] . "%";
}

// Фильтр по виду спорта
if (!empty($_GET['sport'])) {
    $where_clauses[] = "i.sport = ?";
    $params[] = $_GET['sport'];
}

// Фильтр по категории
if (!empty($_GET['category'])) {
    $where_clauses[] = "i.category = ?";
    $params[] = $_GET['category'];
}

// Фильтр по типу
if (!empty($_GET['type'])) {
    $where_clauses[] = "i.type = ?";
    $params[] = $_GET['type'];
}

// Только свободный инвентарь
if (!empty($_GET['only_free'])) {
    $where_clauses[] = "i.status = 'free'";
}

// Склеиваем условия только один раз
if (count($where_clauses) > 0) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}


// ПАГИНАЦИЯ 
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

if ($page < 1) {
    $page = 1;
}

$limit = 9;
$offset = ($page - 1) * $limit;

// Считаем общее количество записей
$count_sql = "
    SELECT COUNT(*)
    FROM inventory i
    LEFT JOIN tariffs t ON i.id = t.inventory_id
";

if (count($where_clauses) > 0) {
    $count_sql .= " WHERE " . implode(" AND ", $where_clauses);
}

$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_rows = $count_stmt->fetchColumn();

$total_pages = ceil($total_rows / $limit);

$sql .= " ORDER BY i.id DESC LIMIT $limit OFFSET $offset";


// Выполнение запроса
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>


<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Прокат спортивного инвентаря</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<!-- Навигация -->
<!-- Навигация -->
<nav class="navbar navbar-light bg-light px-3 px-md-4 mb-4 shadow-sm">
    <div class="container-fluid d-flex flex-column flex-md-row gap-2 gap-md-0">
        <span class="navbar-brand mb-0 h1">Прокат Спорта</span>
    <div class="d-flex flex-wrap gap-2 justify-content-end">
        <?php if (isset($_SESSION['user_id'])): ?>
          
            <a href="profile.php" class="btn btn-outline-primary btn-sm me-3">Мои аренды</a>
            <a href="change_password.php" class="btn btn-outline-primary btn-sm me-3">Сменить пароль</a>
            <?php if ($_SESSION['user_role'] === 'admin'): ?>
            <a href="admin_panel.php" class="btn btn-outline-danger btn-sm me-2">Панель админа</a>
                <a href="add_item.php" class="btn btn-success btn-sm">+ Добавить инвентарь</a>
            <?php endif; ?>
            
            <a href="logout.php" class="btn btn-dark btn-sm">Выйти</a>
        <?php else: ?>
            <a href="login.php" class="btn btn-primary btn-sm">Войти</a>
            <a href="register.php" class="btn btn-outline-primary btn-sm">Регистрация</a>
        <?php endif; ?>
    </div>
    </div>
</nav>


<!-- фильтр -->
 <div class="container py-3">
<div class="card mb-4 p-3 bg-light">
    <form action="index.php" method="GET" class="row g-2 align-items-end">

        <!-- Поиск -->
        <div class="col-md-4">
            <input type="text" name="q" class="form-control"
                   placeholder="Поиск по названию..."
                   value="<?= isset($_GET['q']) ? htmlspecialchars($_GET['q']) : '' ?>">
                
        </div>

        <!-- Вид спорта -->
        <div class="col-md-2">
            <select name="sport" class="form-select">
                <option value="">Вид спорта</option>
                <option value="Велоспорт">Велоспорт</option>
                <option value="Горные лыжи">Лыжный спорт</option>
                <option value="Сноубординг">Сноубординг</option>
                <option value="Самокаты">Кикскутеринг</option>
            </select>
        </div>

        <!-- Категория -->
        <div class="col-md-2">
            <select name="category" class="form-select">
                <option value="">Категория</option>
                <option value="Инвентарь">Инвентарь</option>
                <option value="Экипировка">Экипировка</option>
            </select>
        </div>

        <!-- Тип -->
        <div class="col-md-2">
            <select name="type" class="form-select">
                <option value="">Тип</option>
                <option value="Велосипед">Велосипед</option>
                <option value="Лыжи">Лыжи</option>
                <option value="Сноуборд">Сноуборд</option>
                <option value="Самокат">Самокат</option>
                <option value="Шлем">Шлем</option>
                <option value="Ботинки горнолыжные">Ботинки горнолыжные</option>
                <option value="Крепления для сноуборда">Крепления для сноуборда</option>
                <option value="Палки горнолыжные">Палки горнолыжные</option>
                <option value="Велозащита">Велозащита</option>
            </select>
        </div>

        <!-- Только свободные -->
        <div class="col-12 col-sm-6 col-lg-3">
            <select name="only_free" class="form-select">
                <option value="">Весь инвентарь</option>
                <option value="1" <?= !empty($_GET['only_free']) ? 'selected' : '' ?>>
                    Только свободный
                </option>
            </select>
        </div>

        <!-- Кнопки -->
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary w-100">Найти</button>
            <a href="index.php" class="btn btn-outline-secondary w-100">Сброс</a>
        </div>

    </form>
</div>



<div class="container">
    <h2 class="mb-4">Каталог инвентаря</h2>
    
    <div class="row">
        <?php foreach ($items as $item): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <?php 
                    $img = !empty($item['image_url']) ? $item['image_url'] : 'https://via.placeholder.com/300x200/3D9970/FFFFFF?text=Инвентарь';
                    ?>
<img src="<?= htmlspecialchars($img) ?>"
     class="card-img-top img-fluid"
     alt="Фото инвентаря"
     style="height:200px; object-fit:contain;">


                    
                    <div class="card-body">
                        <h5 class="card-title">
                            <?= htmlspecialchars($item['name']) ?>
                            <?php if ($isAdmin && $item['status'] === 'archived'): ?>
                                <span class="badge bg-danger">Архив</span>
                            <?php endif; ?>
                        </h5>

                    <div class="mb-2 small text-muted">
                        <div>Вид спорта: <strong><?= htmlspecialchars($item['sport']) ?></strong></div>
                        <div>Категория: <strong><?= htmlspecialchars($item['category']) ?></strong></div>
                        <div>Тип: <strong><?= htmlspecialchars($item['type']) ?></strong></div>
                    </div>

                        
                <!-- ЦЕНЫ -->
                <div class="d-flex flex-wrap gap-2 mb-2">
                    <?php if (!empty($item['price_per_hour'])): ?>
                        <span class="badge bg-success-subtle text-success border fs-6 px-3 py-2">
                            Час: <?= (int)$item['price_per_hour'] ?> ₽
                        </span>
                    <?php endif; ?> 
                    <?php if (!empty($item['price_per_day'])): ?>
                        <span class="badge text-nowrap bg-primary-subtle text-primary border fs-6 px-3 py-2">
                            День: <?= (int)$item['price_per_day'] ?> ₽
                        </span>
                    <?php endif; ?>
                </div>

                        
                    <p class="card-text">
                        <?php
                        $description = $item['description'];
                        $short = mb_substr($description, 0, 100);
                        echo htmlspecialchars($short);
                        
                        if (mb_strlen($description) > 100) {
                            echo '... ';
                            echo '<a href="rent.php?id=' . (int)$item['id'] . '" class="read-more">Подробнее</a>';
                        }
                        ?>
                    </p>

                        
                        <p class="card-text fw-bold">
                            Статус: 
                            <?php if ($item['status'] === 'free'): ?>
                                <span class="badge bg-success">✅ Свободен</span>
                            <?php elseif ($item['status'] === 'busy'): ?>
                                <span class="badge bg-secondary">⏳ Занят</span>
                            <?php elseif ($item['status'] === 'archived'): ?>
                                <span class="badge bg-danger">Архив</span>
                            <?php endif; ?>
                        </p>

                    </div>
                    
                    <div class="card-footer bg-white border-top-0">
                        <?php if ($item['status'] === 'free'): ?>
                        <a href="rent.php?id=<?= $item['id'] ?>" class="btn btn-primary w-100">Арендовать</a>
                    <?php elseif ($item['status'] === 'busy'): ?>
                        <button class="btn btn-secondary w-100" disabled>Занят</button>
                    <?php elseif ($item['status'] === 'archived'): ?>
                        <button class="btn btn-secondary w-100" disabled>Недоступно</button>
                    <?php endif; ?>

                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                <a href="edit_item.php?id=<?= $item['id'] ?>" class="btn btn-warning w-100 mt-2">Редактировать</a>
                    <form action="delete_item.php" method="POST" 
                          onsubmit="return confirm('Вы уверены, что хотите удалить этот инвентарь?');">
                        <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <button type="submit" class="btn btn-danger w-100 mt-2">Удалить</button>
                    </form>
                <?php endif; ?>

                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (count($items) === 0): ?>
            <div class="col-12">
                <p class="text-muted">Инвентарь пока отсутствует.</p>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php if ($total_pages > 1): ?>
<nav class="mt-4">
    <ul class="pagination justify-content-center">

        <!-- Кнопка Назад -->
        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => max(1, $page - 1)])) ?>">«</a>
        </li>

        <?php
        $start = max(1, $page - 2);
        $end = min($total_pages, $page + 2);

        if ($start > 1) {
            echo '<li class="page-item"><a class="page-link" href="?'.http_build_query(array_merge($_GET, ['page'=>1])).'">1</a></li>';
            if ($start > 2) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }

        for ($i = $start; $i <= $end; $i++): ?>
            <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                    <?= $i ?>
                </a>
            </li>
        <?php endfor;

        if ($end < $total_pages) {
            if ($end < $total_pages - 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
            echo '<li class="page-item"><a class="page-link" href="?'.http_build_query(array_merge($_GET, ['page'=>$total_pages])).'">'.$total_pages.'</a></li>';
        }
        ?>

        <!-- Кнопка Вперед -->
        <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => min($total_pages, $page + 1)])) ?>">»</a>
        </li>

    </ul>
</nav>
<?php endif; ?>

</body>
</html>
