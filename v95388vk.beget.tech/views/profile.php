<?php
session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/status_update.php';


if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];

$sql = "
SELECT 
    rh.id,
    rh.user_id,
    rh.inventory_id,
    rh.start_time,
    rh.end_time,
    rh.actual_end_time,
    rh.status,
    rh.total_price,
    rh.refund_amount,
    rh.return_comment,
    i.name AS inventory_name,
    i.category AS inventory_category,
    i.image_url,
    i.description AS inventory_description,
    i.status AS inventory_status
FROM rent_history rh
JOIN inventory i ON rh.inventory_id = i.id
WHERE rh.user_id = ?
ORDER BY rh.start_time DESC
";


$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$rentals = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach($rentals as &$r){
$start_ts = strtotime($r['start_time']);
$end_ts = strtotime($r['end_time']);
$now = time();

// Если аренда активная и идёт сейчас или через <= 1 час — считаем занятым
if ($r['status'] === 'active' && ($now >= $start_ts - 3600 && $now <= $end_ts)) {
    $inventory_status_text = 'Занят';
    $badge_class = 'bg-secondary';
} elseif ($r['status'] === 'active' && $now > $end_ts) {
    // Аренда завершилась
    $inventory_status_text = 'Свободен';
    $badge_class = 'bg-success';
    $r['status'] = 'completed';
    $r['actual_end_time'] = $r['end_time'];
    // Обновляем базу данных
    $pdo->prepare("UPDATE rent_history SET status='completed', actual_end_time=end_time WHERE id=?")->execute([$r['id']]);
    $pdo->prepare("UPDATE inventory SET status='free' WHERE id=?")->execute([$r['inventory_id']]);
} else {
    // Остальные статусы
    $inventory_status_text = $r['inventory_status'] === 'archived' ? 'Архив' : 'Свободен';
    $badge_class = $r['inventory_status'] === 'archived' ? 'bg-danger' : 'bg-success';
}
}
unset($r);

?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title>Мои аренды</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background-color:#f8f9fa; }
.card { border-radius:10px; box-shadow:0 0 15px rgba(0,0,0,0.1); margin-bottom:20px; }
.card-header { background-color:#0d6efd; color:white; font-weight:bold; font-size:1.2rem; }
.card-img-top { height:200px; object-fit:cover; border-top-left-radius:10px; border-top-right-radius:10px; }
.description { max-height:60px; overflow:hidden; position:relative; margin-bottom:10px; }
.description::after { content:''; position:absolute; bottom:0; right:0; height:1.5em; width:100%; background:linear-gradient(to top, white, transparent); }
.badge-status { font-size:0.9rem; }
</style>
</head>
<body class="p-4">
<div class="container">
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-0">Мои аренды</h1>

    <div>
        <a href="index.php" class="btn btn-secondary">
            На главную
        </a>
    </div>
</div>


<?php if (!$rentals): ?>
    <div class="alert alert-info">Вы пока не арендовали ничего.</div>
<?php else: ?>
<div class="row">
<?php foreach($rentals as $r): ?>
<div class="col-md-6 col-lg-4">
    <div class="card">
        <?php if($r['image_url']): ?>
           <img src="<?= htmlspecialchars($r['image_url']) ?>" 
     class="card-img-top" 
     alt="<?= htmlspecialchars($r['inventory_name']) ?>" 
     style="height: 200px; width: auto; object-fit: contain; display: block; margin: 0 auto;">

        <?php endif; ?>
        <div class="card-header"><?= htmlspecialchars($r['inventory_name']) ?> (<?= htmlspecialchars($r['inventory_category']) ?>)</div>
        <?php if ($r['inventory_status'] === 'archived'): ?>
    <div style="color:#b00000; font-size:13px; padding:6px 10px;">
        Инвентарь выведен из обихода и больше недоступен
    </div>
<?php endif; ?>

        <div class="card-body">
            <?php if ($r['inventory_description']): ?>
    <div class="description">
        <?= htmlspecialchars($r['inventory_description']) ?>
    </div>
<?php endif; ?>

            <p><strong>Начало:</strong> <?= date('d.m.Y H:i', strtotime($r['start_time'])) ?></p>
            <p><strong>Окончание:</strong> <?= $r['end_time'] ? date('d.m.Y H:i', strtotime($r['end_time'])) : '-' ?></p>
            <p><strong>Сумма:</strong> <?= number_format($r['total_price'],2) ?> ₽</p>
            <p><strong>Статус:</strong>
                <?php
                    $status_text = '';
                    switch($r['status']){
                        case 'active': $status_text='Активна'; break;
                        case 'completed': $status_text='Завершена'; break;
                        case 'early_return': $status_text='Возвращено досрочно'; break;
                        case 'cancelled': $status_text='Отменена'; break;
                        default: $status_text=$r['status'];
                    }
                ?>
                <span class="badge bg-primary badge-status"><?= $status_text ?></span>
            </p>

      <?php if($r['status']=='active' && $r['inventory_status'] !== 'archived'): ?>
    <a href="return.php?id=<?= (int)$r['id'] ?>&csrf=<?= $_SESSION['csrf_token'] ?>" class="btn btn-warning btn-sm">
        Вернуть досрочно
    </a>
        <a href="edit_rent.php?id=<?= (int)$r['id'] ?>&csrf=<?= $_SESSION['csrf_token'] ?>" class="btn btn-info btn-sm">
        Перенести дату
    </a>
<?php endif; ?>

            <?php if($r['status']=='early_return' && $r['refund_amount']>0): ?>
                <p><strong>Возврат:</strong> <?= number_format($r['refund_amount'],2) ?> ₽</p>
                <p><strong>Комментарий:</strong> <?= htmlspecialchars($r['return_comment']) ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>
</body>
</html>
