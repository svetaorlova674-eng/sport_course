<?php
// Включаем полный режим отладки
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/status_update.php';

// Проверка авторизации
if(!isset($_SESSION['user_id'])){
    header('Location: login.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$rent_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if($rent_id <= 0) die('Некорректный ID аренды');

// Инициализация CSRF для PHP 5.6
if(empty($_SESSION['csrf_token'])){
    $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Получаем аренду
$stmt = $pdo->prepare("
    SELECT rh.*, i.name AS inventory_name, i.category, i.image_url, i.description, i.status AS inventory_status
    FROM rent_history rh
    JOIN inventory i ON rh.inventory_id = i.id
    WHERE rh.id = ? AND rh.user_id = ?
    LIMIT 1
");
$stmt->execute(array($rent_id, $user_id));
$rent = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$rent) die('Аренда не найдена');
if($rent['status'] !== 'active') die('Можно переносить только активные аренды');

$error = '';
$success = '';

// Обработка формы
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $csrf_post = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    $new_start = isset($_POST['start_time']) ? $_POST['start_time'] : '';
    $new_end   = isset($_POST['end_time']) ? $_POST['end_time'] : '';

    if($csrf_post !== $csrf_token){
        $error = 'Ошибка безопасности. Попробуйте снова.';
    } else {
        $start_ts = strtotime($new_start);
        $end_ts   = strtotime($new_end);
        $now = time();

        if(!$start_ts || !$end_ts){
            $error = 'Некорректные даты.';
        } elseif($start_ts < $now){
            $error = 'Нельзя перенести аренду на прошлое время.';
        } elseif($end_ts <= $start_ts){
            $error = 'Окончание аренды должно быть позже начала.';
        } else {
            // Проверка пересечения с другими арендами
            $stmt2 = $pdo->prepare("
                SELECT COUNT(*) 
                FROM rent_history 
                WHERE inventory_id = ?
                  AND id != ?
                  AND status = 'active'
                  AND ((start_time <= ? AND end_time > ?)
                    OR (start_time < ? AND end_time >= ?))
            ");
            $stmt2->execute(array(
                $rent['inventory_id'],
                $rent['id'],
                date('Y-m-d H:i:s', $start_ts),
                date('Y-m-d H:i:s', $start_ts),
                date('Y-m-d H:i:s', $end_ts),
                date('Y-m-d H:i:s', $end_ts)
            ));
            $count = $stmt2->fetchColumn();

            if($count > 0){
                $error = 'Выбранный слот уже занят.';
            } else {
                // Обновляем аренду
                $stmt3 = $pdo->prepare("
                    UPDATE rent_history
                    SET start_time = ?, end_time = ?
                    WHERE id = ?
                ");
                $stmt3->execute(array(
                    date('Y-m-d H:i:s', $start_ts),
                    date('Y-m-d H:i:s', $end_ts),
                    $rent['id']
                ));

                $success = 'Аренда успешно перенесена.';
                $rent['start_time'] = date('Y-m-d H:i:s', $start_ts);
                $rent['end_time']   = date('Y-m-d H:i:s', $end_ts);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title>Перенос даты аренды</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4 bg-light">
<div class="container" style="max-width:650px">

<h3 class="mb-3">Перенос даты: <?= htmlspecialchars($rent['inventory_name'], ENT_QUOTES) ?></h3>

<?php if($error): ?>
<div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES) ?></div>
<?php endif; ?>

<?php if($success): ?>
<div class="alert alert-success"><?= htmlspecialchars($success, ENT_QUOTES) ?></div>
<a href="profile.php" class="btn btn-secondary">В профиль</a>
<?php else: ?>
<form method="post">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES) ?>">

    <div class="mb-3">
        <label class="form-label">Начало аренды</label>
        <input type="datetime-local" name="start_time" 
            value="<?= date('Y-m-d\TH:i', strtotime($rent['start_time'])) ?>" 
            class="form-control" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Окончание аренды</label>
        <input type="datetime-local" name="end_time" 
            value="<?= date('Y-m-d\TH:i', strtotime($rent['end_time'])) ?>" 
            class="form-control" required>
    </div>

    <button class="btn btn-primary">Сохранить</button>
    <a href="profile.php" class="btn btn-secondary">Отмена</a>
</form>
<?php endif; ?>

</div>
</body>
</html>
