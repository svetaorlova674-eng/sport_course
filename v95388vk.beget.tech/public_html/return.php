<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$rent_id = (int)($_GET['id'] ?? 0);

/* Получаем аренду */
$stmt = $pdo->prepare("
    SELECT rh.*, t.price_per_hour
    FROM rent_history rh
    JOIN tariffs t ON rh.tariff_id = t.id
    WHERE rh.id = ?
");
$stmt->execute([$rent_id]);
$rent = $stmt->fetch();

if (!$rent || $rent['end_time']) {
    die('Аренда не найдена или уже завершена');
}

/* Расчёт стоимости */
$hours = ceil((time() - strtotime($rent['start_time'])) / 3600);
$total = $hours * $rent['price_per_hour'];

/* Закрываем аренду */
$pdo->prepare("
    UPDATE rent_history
    SET end_time = NOW(), total_price = ?
    WHERE id = ?
")->execute([$total, $rent_id]);

/* Освобождаем инвентарь */
$pdo->prepare("
    UPDATE inventory SET status = 'free' WHERE id = ?
")->execute([$rent['inventory_id']]);

header('Location: index.php');
exit;
