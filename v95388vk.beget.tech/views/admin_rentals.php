<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role']!=='admin') {
    header('Location: login.php'); exit;
}
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/status_update.php';


// 1️⃣ Автоматически обновляем все завершённые по времени активные аренды
$pdo->query("
    UPDATE rent_history
    SET status='completed',
        actual_end_time=end_time
    WHERE status='active' AND end_time <= NOW()
");

// 2️⃣ Освобождаем инвентарь для завершённых аренды
$pdo->query("
    UPDATE inventory i
    JOIN rent_history rh ON i.id = rh.inventory_id
    SET i.status='free'
    WHERE rh.status='completed' AND i.status='busy'
");

// Инициализация CSRF
if(!isset($_SESSION['csrf_token'])){
    $_SESSION['csrf_token'] = md5(uniqid(rand(), true));
}
$csrf_token = $_SESSION['csrf_token'];

// Откат возврата (early_return -> active)
if (!empty($_GET['rollback'])) {
    $rent_id = (int)$_GET['rollback'];
    $csrf_get = isset($_GET['csrf']) ? $_GET['csrf'] : '';
    if($csrf_get != $csrf_token){
        die('Ошибка безопасности. Попробуйте снова.');
    }

    $stmt = $pdo->prepare("SELECT * FROM rent_history WHERE id=? LIMIT 1");
    $stmt->execute(array($rent_id));
    $r = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($r && $r['status']=='early_return') {
        $pdo->prepare("
            UPDATE rent_history
            SET status='active', actual_end_time=NULL, refund_amount=NULL, return_comment=NULL
            WHERE id=?
        ")->execute(array($rent_id));

        $pdo->prepare("UPDATE inventory SET status='busy' WHERE id=?")->execute(array($r['inventory_id']));
    }
    header('Location: admin_rentals.php'); exit;
}

// Получаем все аренды
$sql = "
SELECT rh.*, u.email, i.name AS inventory_name, i.status AS inventory_status,
       t.price_per_hour, t.price_per_day
FROM rent_history rh
JOIN users u ON rh.user_id=u.id
JOIN inventory i ON rh.inventory_id=i.id
LEFT JOIN tariffs t ON rh.tariff_id=t.id
ORDER BY rh.id DESC
";
$rentals = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// Функция расчета итоговой суммы
function calculate_total($start,$end,$price_hour,$price_day){
    if (!$end) return '-';
    $seconds = strtotime($end)-strtotime($start);
    if ($seconds<=0) return 0;
    $hours = ceil($seconds/3600);
    $days = ceil($hours/24);
    $price_hour = is_numeric($price_hour)?$price_hour:0;
    $price_day = is_numeric($price_day)?$price_day:0;
    if ($hours>=6 && $price_day>0) return $days*$price_day;
    elseif ($price_hour>0) return $hours*$price_hour;
    return 0;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title>Управление арендами</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4 bg-light">
<h1>Все аренды</h1>
<a href="admin_panel.php" class="btn btn-secondary mb-3">Назад</a>

<table class="table table-bordered table-striped">
<thead>
<tr>
<th>ID</th><th>Инвентарь</th><th>Арендатор</th><th>Статус инвентаря</th>
<th>Цена/ч</th><th>Цена/день</th><th>Начало</th><th>Окончание</th>
<th>Итоговая сумма</th><th>Возврат</th><th>Комментарий</th><th>Действие</th>
</tr>
</thead>
<tbody>
<?php
$now = time();
foreach($rentals as $r):
    $total = calculate_total($r['start_time'],$r['end_time'],$r['price_per_hour'],$r['price_per_day']);

    $start_ts = strtotime($r['start_time']);
    $end_ts   = strtotime($r['end_time'] ? $r['end_time'] : ($r['actual_end_time'] ? $r['actual_end_time'] : $r['start_time']));

    // Проверяем: если активная аренда уже закончилась по времени — обновляем статус в базе
    if ($r['status'] === 'active' && $now > $end_ts) {
        $r['status'] = 'completed';
        $inventory_status_text = 'Свободен';
        $badge_class = 'bg-success';

        // Обновляем базу, чтобы в следующий раз SELECT вернул уже completed
        $pdo->prepare("UPDATE rent_history SET status='completed' WHERE id=?")->execute(array($r['id']));
        $pdo->prepare("UPDATE inventory SET status='free' WHERE id=?")->execute(array($r['inventory_id']));
    } else {
        // Если не завершена, берём динамический статус инвентаря
// Статус инвентаря для отображения
        if($r['inventory_status'] === 'archived'){
            $inventory_status_text = 'Архив';
            $badge_class = 'bg-danger';
        } elseif($r['status']=='active' && $now >= $start_ts - 3600 && $now <= $end_ts){
    $inventory_status_text = 'Занят';
    $badge_class = 'bg-secondary';
        } elseif(in_array($r['status'], ['early_return','completed','cancelled'])){
            $inventory_status_text = 'Свободен';
            $badge_class = 'bg-success';
        } else {
            $inventory_status_text = $r['inventory_status']=='free'?'Свободен':'Занят';
            $badge_class = $r['inventory_status']=='free'?'bg-success':'bg-secondary';
        }
    }

?>
<tr>
<td><?= $r['id'] ?></td>
<td><?= htmlspecialchars($r['inventory_name']) ?></td>
<td><?= htmlspecialchars($r['email']) ?></td>
<td><span class="badge <?= $badge_class ?>"><?= $inventory_status_text ?></span></td>
<td><?= is_numeric($r['price_per_hour'])?$r['price_per_hour'].' ₽':'-' ?></td>
<td><?= is_numeric($r['price_per_day'])?$r['price_per_day'].' ₽':'-' ?></td>
<td><?= date('d.m.Y H:i',strtotime($r['start_time'])) ?></td>
<td><?= $r['end_time']?date('d.m.Y H:i',strtotime($r['end_time'])):'-' ?></td>
<td><?= is_numeric($total)?$total.' ₽':'-' ?></td>
<td><?= $r['refund_amount']>0?number_format($r['refund_amount'],2).' ₽':'-' ?></td>
<td><?= $r['return_comment']?htmlspecialchars($r['return_comment']):'-' ?></td>
<td>
<?php
switch($r['status']){
    case 'active':
        echo '<span class="text-primary">Активна</span>';
        break;
    case 'completed':
        echo '<span class="text-success">Завершена</span>';
        break;
    case 'cancelled':
        echo '<span class="text-muted">Отменена</span>';
        break;
    case 'early_return':
        echo '<a href="?rollback=' . $r['id'] . '&csrf=' . $csrf_token . '" class="btn btn-sm btn-danger">Откатить возврат</a>';
        break;
    default:
        echo '-';
}
?>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</body>
</html>
