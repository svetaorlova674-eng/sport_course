<?php
session_start();

// Включаем ошибки для отладки
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Проверка админа
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Подключение БД
require_once __DIR__ . '/../config/db.php';

// Обработка изменения статуса инвентаря
if (!empty($_GET['toggle_status'])) {
    $inventory_id = (int)$_GET['toggle_status'];
    if ($inventory_id > 0) {
        $stmt = $pdo->prepare("SELECT status FROM inventory WHERE id=?");
        $stmt->execute([$inventory_id]);
        $status = $stmt->fetchColumn();
        $new_status = ($status === 'free') ? 'busy' : 'free';
        $stmt = $pdo->prepare("UPDATE inventory SET status=? WHERE id=?");
        $stmt->execute([$new_status, $inventory_id]);
    }
    header('Location: admin_rentals.php');
    exit();
}

// Получаем все аренды
$sql = "
    SELECT 
        rh.id AS rent_id,
        rh.start_time,
        rh.end_time,
        rh.total_price,
        u.email,
        i.id AS inventory_id,
        i.name AS inventory_name,
        i.status AS inventory_status,
        t.price_per_hour,
        t.price_per_day
    FROM rent_history rh
    JOIN users u ON rh.user_id = u.id
    JOIN inventory i ON rh.inventory_id = i.id
    LEFT JOIN tariffs t ON rh.tariff_id = t.id
    ORDER BY rh.id DESC
";

$stmt = $pdo->query($sql);
$rentals = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Функция для расчета итоговой суммы
function calculate_total($start, $end, $price_hour, $price_day) {
    if (!$end) return '-';
    $seconds = strtotime($end) - strtotime($start);
    if ($seconds <= 0) return 0;

    $hours = ceil($seconds / 3600);
    $days = ceil($seconds / 86400);

    $price_hour = is_numeric($price_hour) ? $price_hour : 0;
    $price_day = is_numeric($price_day) ? $price_day : 0;

    if ($hours >= 6 && $price_day > 0) {
        return $days * $price_day;
    } elseif ($price_hour > 0) {
        return $hours * $price_hour;
    } else {
        return 0;
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление арендами</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">

<h1>Все аренды</h1>
<a href="admin_panel.php" class="btn btn-secondary mb-3">Назад в панель админа</a>

<table class="table table-bordered table-striped">
    <thead>
        <tr>
            <th>ID Аренды</th>
            <th>Инвентарь</th>
            <th>Арендатор (Email)</th>
            <th>Статус инвентаря</th>
            <th>Цена/ч</th>
            <th>Цена/день</th>
            <th>Дата начала</th>
            <th>Дата окончания</th>
            <th>Итоговая сумма</th>
            <th>Действие</th>
        </tr>
    </thead>
    <tbody>
        <?php if (count($rentals) === 0): ?>
            <tr><td colspan="10" class="text-center">Аренд пока нет</td></tr>
        <?php else: ?>
            <?php foreach ($rentals as $r): ?>
                <?php 
                    $total = calculate_total($r['start_time'], $r['end_time'], $r['price_per_hour'], $r['price_per_day']);
                    $status = strtolower($r['inventory_status']);
                ?>
                <tr>
                    <td><?= $r['rent_id'] ?></td>
                    <td><?= htmlspecialchars($r['inventory_name']) ?></td>
                    <td><?= htmlspecialchars($r['email']) ?></td>
                    <td>
                        <?php if ($status === 'free'): ?>
                            <span class="badge bg-success">Свободен</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Занят</span>
                        <?php endif; ?>
                    </td>
                    <td><?= is_numeric($r['price_per_hour']) ? $r['price_per_hour'].' ₽' : '-' ?></td>
                    <td><?= is_numeric($r['price_per_day']) ? $r['price_per_day'].' ₽' : '-' ?></td>
                    <td><?= date('d.m.Y H:i', strtotime($r['start_time'])) ?></td>
                    <td><?= $r['end_time'] ? date('d.m.Y H:i', strtotime($r['end_time'])) : '-' ?></td>
                    <td><?= is_numeric($total) ? $total.' ₽' : '-' ?></td>
                    <td>
                        <a href="?toggle_status=<?= $r['inventory_id'] ?>" class="btn btn-sm btn-warning">
                            <?= ($status === 'free') ? 'Сделать занятым' : 'Сделать свободным' ?>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

</body>
</html>
