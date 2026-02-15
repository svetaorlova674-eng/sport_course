<?php
if (!isset($pdo)) {
    require_once __DIR__ . '/../config/db.php';
}

$pdo->exec("SET time_zone = '+07:00'");


/*
|--------------------------------------------------------------------------
| 1. Перевод в BUSY
| Если:
| - до начала аренды осталось <= 1 часа
| - аренда уже идет
|--------------------------------------------------------------------------
*/

$stmtBusy = $pdo->prepare("
    UPDATE inventory i
    JOIN (
        SELECT inventory_id
        FROM rent_history
        WHERE start_time <= DATE_ADD(NOW(), INTERVAL 1 HOUR)
          AND end_time > NOW()
    ) active_rents ON i.id = active_rents.inventory_id
    SET i.status = 'busy'
    WHERE i.status != 'archived'
");
$stmtBusy->execute();



/*
|--------------------------------------------------------------------------
| 2. Возврат в FREE
| Если аренда закончилась
|--------------------------------------------------------------------------
*/

$stmtFree = $pdo->prepare("
    UPDATE inventory i
    JOIN (
        SELECT inventory_id
        FROM rent_history
        WHERE end_time <= NOW()
    ) finished_rents ON i.id = finished_rents.inventory_id
    SET i.status = 'free'
    WHERE i.status = 'busy'
");
$stmtFree->execute();
