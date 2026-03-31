<?php
require_once __DIR__ . '/../config/db.php';

// 1. Завершаем просроченные активные аренды
$pdo->prepare("
    UPDATE rent_history 
    SET status='completed', 
        actual_end_time = end_time,
        refund_amount = total_price
    WHERE status='active' 
    AND end_time < NOW()
")->execute();

// 2. Освобождаем инвентарь для завершенных аренд
$pdo->prepare("
    UPDATE inventory i
    JOIN rent_history rh ON i.id = rh.inventory_id
    SET i.status='free'
    WHERE rh.status IN ('completed', 'cancelled', 'early_return')
    AND i.status='busy'
    AND NOT EXISTS (
        SELECT 1 FROM rent_history rh2 
        WHERE rh2.inventory_id = i.id 
        AND rh2.status = 'active'
        AND rh2.id != rh.id
    )
")->execute();
?>