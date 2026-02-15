<?php
header('Content-Type: text/html; charset=UTF-8');
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../config/db.php';

if(!isset($_SESSION['user_id'])){
    header('Location: login.php'); exit;
}

$user_id = (int)$_SESSION['user_id'];
$rent_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if($rent_id<=0) die('Некорректный ID аренды');

// Инициализация CSRF для старого PHP
if(!isset($_SESSION['csrf_token'])){
    $_SESSION['csrf_token'] = md5(uniqid(rand(), true));
}
$csrf_token = $_SESSION['csrf_token'];

// Получаем аренду с инвентарем и тарифом
$stmt = $pdo->prepare("
    SELECT rh.*, i.name AS inventory_name, i.category, i.image_url, i.description, t.price_per_hour
    FROM rent_history rh
    JOIN inventory i ON rh.inventory_id=i.id
    LEFT JOIN tariffs t ON rh.tariff_id=t.id
    WHERE rh.id=? AND rh.user_id=?
    LIMIT 1
");
$stmt->execute(array($rent_id, $user_id));
$rent = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$rent) die('Аренда не найдена');

$error='';
$success='';
$refund=0;

// Расчет возврата
$start_ts = strtotime($rent['start_time']);
$end_ts   = strtotime($rent['end_time']);
$now_ts   = time();

$paid_hours = ceil(($end_ts-$start_ts)/3600);
$used_hours = ceil(($now_ts-$start_ts)/3600);
if($used_hours<1) $used_hours=1;
if($used_hours>$paid_hours) $used_hours=$paid_hours;

$price_hour = isset($rent['price_per_hour']) ? floatval($rent['price_per_hour']) : ($rent['total_price']/$paid_hours);
$refund = $rent['total_price'] - ($used_hours*$price_hour);
if($refund<0) $refund=0;

// Обработка формы
if($_SERVER['REQUEST_METHOD']=='POST'){
    $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
    $csrf_post = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';

    if($csrf_post != $csrf_token){
        $error = 'Ошибка безопасности. Попробуйте снова.';
    } elseif($rent['status']!='active'){
        $error='Аренда уже завершена';
    } elseif($comment==''){
        $error='Комментарий обязателен';
    } else {
        // Обновляем аренду
        $stmt = $pdo->prepare("
            UPDATE rent_history
            SET status='early_return',
                actual_end_time=NOW(),
                refund_amount=?,
                return_comment=?
            WHERE id=?
        ");
        $stmt->execute(array($refund, $comment, $rent_id));

        // Освобождаем инвентарь
        $pdo->prepare("UPDATE inventory SET status='free' WHERE id=?")->execute(array($rent['inventory_id']));

        $success='Инвентарь возвращён. Сумма к возврату: '.number_format($refund,2).' ₽';
        $rent['status']='early_return';
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title>Возврат аренды</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4 bg-light">
<div class="container" style="max-width:650px">
<h3 class="mb-3">Возврат: <?= htmlspecialchars($rent['inventory_name']) ?></h3>

<?php if($error): ?>
<div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if($success): ?>
<div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<a href="profile.php" class="btn btn-secondary">В профиль</a>
<?php else: ?>
<p><strong>Начало:</strong> <?= $rent['start_time'] ?></p>
<p><strong>Плановое окончание:</strong> <?= $rent['end_time'] ?></p>
<p><strong>Оплачено часов:</strong> <?= $paid_hours ?></p>
<p><strong>Использовано часов:</strong> <?= $used_hours ?></p>
<div class="alert alert-info"><strong>Сумма к возврату:</strong> <?= number_format($refund,2) ?> ₽</div>

<form method="post">
    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
    <div class="mb-3">
        <label class="form-label">Комментарий (обязательно)</label>
        <textarea name="comment" class="form-control" rows="3" required></textarea>
    </div>
    <button class="btn btn-warning">Подтвердить возврат</button>
    <a href="profile.php" class="btn btn-secondary">Отмена</a>
</form>
<?php endif; ?>
</div>
</body>
</html>
