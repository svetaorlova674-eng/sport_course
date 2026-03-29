<?php
header('Content-Type: text/html; charset=UTF-8');
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/status_update.php';


if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$inventory_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($inventory_id <= 0) {
    die('Неверный ID инвентаря');
}

// Получаем данные об инвентаре
$stmt = $pdo->prepare("SELECT * FROM inventory WHERE id = ?");
$stmt->execute([$inventory_id]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) {
    die('Инвентарь не найден');
}

// Приводим статус к нижнему регистру для надежности
$normalized_status = strtolower(trim($item['status']));

// Проверяем что товар вообще доступен для аренды
if ($normalized_status === 'archived') {
    die('Этот инвентарь находится в архиве и недоступен для аренды');
}

// Проверяем свободен ли товар (допускаем пустой статус как свободный)
if (!in_array($normalized_status, ['free', ''])) {
    die('Инвентарь недоступен для аренды. Статус: "' . $item['status'] . '"');
}
// Получаем тариф
$stmt = $pdo->prepare("SELECT * FROM tariffs WHERE inventory_id = ?");
$stmt->execute([$inventory_id]);
$tariff = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tariff) {
    die('Для данного инвентаря тариф не задан');
}

// ===== Получаем занятые интервалы =====
$stmt = $pdo->prepare("
    SELECT start_time, end_time 
    FROM rent_history 
    WHERE inventory_id = ? 
      AND status = 'active'
");
$stmt->execute([$inventory_id]);
$busy_slots = $stmt->fetchAll(PDO::FETCH_ASSOC);


$error = '';
$success = '';
$calculated_price = 0;
$duration_hours = 0;
$start_date = '';
$end_date = '';

/* Обработка расчета суммы */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['calculate'])) {
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    
$start_timestamp = strtotime($_POST['start_date']);  // время начала
$end_timestamp = strtotime($_POST['end_date']);      // время окончания
    
    if (empty($start_date) || empty($end_date)) {
        $error = 'Заполните обе даты';
    } elseif ($start_timestamp < time()) {
        $error = 'Нельзя выбрать прошедшее время для начала аренды';
    } elseif ($end_timestamp <= $start_timestamp) {
        $error = 'Дата окончания должна быть позже даты начала';
    } else {
        $hours = ($end_timestamp - $start_timestamp) / 3600;
        
        if ($hours < 1) {
            $error = 'Минимальное время аренды - 1 час';
        } else {
            $hours_rounded = ceil($hours);
            $days_rounded = ceil($hours_rounded / 24);
            
            $price_hour = (float)$tariff['price_per_hour'];
            $price_day = (float)$tariff['price_per_day'];
            
            if ($price_day > 0 && $hours_rounded >= 6) {
                $calculated_price = $days_rounded * $price_day;
            } else {
                $calculated_price = $hours_rounded * $price_hour;
            }
            
            $duration_hours = $hours_rounded;
            $success = "Итоговая сумма: " . number_format($calculated_price, 2) . " ₽";
        }
    }
}

/* Обработка подтверждения аренды */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {
    $start_date = $_POST['start_date'];
    $end_date   = $_POST['end_date'];

$start_timestamp = strtotime($_POST['start_date']);  // время начала
$end_timestamp = strtotime($_POST['end_date']);      // время окончания

    if (empty($start_date) || empty($end_date)) {
        $error = 'Заполните обе даты';
    } elseif ($start_timestamp < time()) {
        $error = 'Нельзя выбрать прошедшее время для начала аренды';
    } elseif ($end_timestamp <= $start_timestamp) {
        $error = 'Дата окончания должна быть позже даты начала';
    } else {
        $hours_rounded = ceil(($end_timestamp - $start_timestamp) / 3600);
        if ($hours_rounded < 1) {
            $error = 'Минимальное время аренды - 1 час';
        } else {
            $days_rounded = ceil($hours_rounded / 24);

            $price_hour = (float)$tariff['price_per_hour'];
            $price_day  = (float)$tariff['price_per_day'];

            if ($price_day > 0 && $hours_rounded >= 6) {
                $total_price = $days_rounded * $price_day;
            } else {
                $total_price = $hours_rounded * $price_hour;
            }


            // ДОБАВЬ ЭТО ПЕРЕД INSERT
error_log("=== DEBUG rent.php ===");
error_log("Сейчас: " . date('Y-m-d H:i:s'));
error_log("start_time для БД: " . date('Y-m-d H:i:s', $start_timestamp));
error_log("end_time для БД: " . date('Y-m-d H:i:s', $end_timestamp));
error_log("Разница часов: " . ($end_timestamp - $start_timestamp) / 3600);
error_log("end_time уже прошел? " . ($end_timestamp < time() ? 'ДА!' : 'нет'));
error_log("======================");



            // Добавляем запись в историю аренды
            $stmt = $pdo->prepare("
                INSERT INTO rent_history (inventory_id, user_id, tariff_id, start_time, end_time, total_price)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $inventory_id,
                $user_id,
                $tariff['id'],
                date('Y-m-d H:i:s', $start_timestamp),
                date('Y-m-d H:i:s', $end_timestamp),
                $total_price
            ]);


// Добавьте отладку чтобы убедиться что UPDATE работает
error_log("UPDATE inventory: id=$inventory_id, rowCount=" . $stmt->rowCount());

            if ($stmt->rowCount() !== 1) {
                // откат последней аренды ЭТОГО пользователя
                $pdo->prepare("
                    DELETE FROM rent_history
                    WHERE inventory_id = ? AND user_id = ?
                    ORDER BY id DESC
                    LIMIT 1
                ")->execute([$inventory_id, $user_id]);

                die('Товар уже занят или недоступен для аренды');
            }

            header('Location: index.php');
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Прокат инвентаря - Оформление аренды</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            max-width: 800px;
            margin-top: 50px;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .card-header {
            background-color: #0d6efd;
            color: white;
            border-radius: 10px 10px 0 0 !important;
            padding: 20px;
        }
        .form-label {
            font-weight: 600;
            margin-bottom: 8px;
        }
        .btn-primary {
            background-color: #0d6efd;
            border: none;
            padding: 10px 30px;
            font-weight: 600;
        }
        .btn-secondary {
            padding: 10px 30px;
        }
        .btn-warning {
            background-color: #ffc107;
            border: none;
            padding: 10px 30px;
            font-weight: 600;
        }
        .price-info {
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .price-badge {
            background-color: #198754;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-weight: 600;
        }
        .alert {
            border-radius: 8px;
        }
        .total-price {
            font-size: 1.5rem;
            font-weight: bold;
            color: #198754;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2 class="mb-0">Прокат инвентаря</h2>
                <p class="mb-0">Оформление аренды</p>
            </div>
            
            <div class="card-body p-4">
                <!-- Информация об инвентаре -->
                <div class="price-info">
                    <h4 class="mb-3"><?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?></h4>
                    <p class="mb-2"><strong>Категория:</strong> <?= htmlspecialchars($item['category'], ENT_QUOTES, 'UTF-8') ?></p>
                    <div class="d-flex gap-3">
                        <div class="price-badge"><?= number_format($tariff['price_per_hour'], 2) ?> ₽/час</div>
                        <?php if (!empty($tariff['price_per_day'])): ?>
                            <div class="price-badge" style="background-color: #0dcaf0;"><?= number_format($tariff['price_per_day'], 2) ?> ₽/день</div>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($item['description'])): ?>
                        <p class="mt-3 mb-0"><strong>Описание:</strong> <?= htmlspecialchars($item['description'], ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>
                </div>

                
                <h4 class="mt-4">Календарь занятости</h4>
            
            <?php if (!empty($busy_slots)): ?>
                <?php foreach ($busy_slots as $slot): ?>
                    <div style="
                        background:#f8d7da;
                        padding:10px;
                        margin-bottom:8px;
                        border-radius:6px;
                    ">
                        🔴 
                        <?= date('d.m.Y H:i', strtotime($slot['start_time'])) ?>
                        —
                        <?= date('d.m.Y H:i', strtotime($slot['end_time'])) ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="
                    background:#d4edda;
                    padding:10px;
                    border-radius:6px;
                ">
                    🟢 Сейчас нет активных броней
                </div>
            <?php endif; ?>

                <h4 class="mt-4">Выбор времени аренды</h4>

                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <?= htmlspecialchars($success) ?>
                        <br>Продолжительность: <?= $duration_hours ?> часов (<?= round($duration_hours/24, 1) ?> дней)
                        <div class="total-price mt-2">
                            К оплате: <?= number_format($calculated_price, 2) ?> ₽
                        </div>
                    </div>
                <?php endif; ?>

                <form method="post">
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Начало аренды *</label>
                            <input type="datetime-local" 
                                   class="form-control" 
                                   name="start_date" 
                                   value="<?= htmlspecialchars($start_date) ?>"
                                   required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Окончание аренды *</label>
                            <input type="datetime-local" 
                                   class="form-control" 
                                   name="end_date" 
                                   value="<?= htmlspecialchars($end_date) ?>"
                                   required>
                        </div>
                    </div>

                    <div class="d-flex gap-3 mt-4">
                        <button type="submit" name="calculate" value="1" class="btn btn-warning">Рассчитать стоимость</button>
                        
                        <?php if ($success && empty($error)): ?>
                            <button type="submit" name="confirm" value="1" class="btn btn-primary">Подтвердить аренду</button>
                        <?php else: ?>
                            <button type="submit" name="confirm" value="1" class="btn btn-primary" disabled>Подтвердить аренду</button>
                        <?php endif; ?>
                        
                        <a href="index.php" class="btn btn-secondary">Отмена</a>
                    </div>
                </form>

                <div class="mt-4 pt-3 border-top">
                    <h5>Как это работает:</h5>
                    <ul class="text-muted">
                        <li>1. Выберите дату начала и окончания аренды</li>
                        <li>2. Нажмите "Рассчитать стоимость"</li>
                        <li>3. Проверьте сумму и продолжительность</li>
                        <li>4. Если всё верно, нажмите "Подтвердить аренду"</li>
                        <li>Кнопка подтверждения активна только после расчета</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</body>
</html>