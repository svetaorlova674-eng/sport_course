<?php
header('Content-Type: text/html; charset=UTF-8');
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/check_admin.php';

// CSRF защита
if (!isset($_POST['csrf_token'], $_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    die('CSRF Attack blocked');
}

// Получаем ID
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) die('Неверный ID инвентаря');

// Получаем инвентарь
$stmt = $pdo->prepare("SELECT id, name, status FROM inventory WHERE id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$item) die('Инвентарь не найден');

// Проверяем активные аренды
$stmt2 = $pdo->prepare("SELECT COUNT(*) FROM rent_history WHERE inventory_id = ? AND status IN ('active','early_return')");
$stmt2->execute([$id]);
$active_rentals = (int)$stmt2->fetchColumn();

// Логика архивирования
if (isset($_POST['confirm_delete']) && $_POST['confirm_delete'] === 'yes') {

    if ($item['status'] === 'archived') {
        echo "<script>alert('Инвентарь уже в архиве'); window.location.href='index.php';</script>";
        exit;
    }

    // Ставим статус archived для всех случаев
    $stmt3 = $pdo->prepare("UPDATE inventory SET status = 'archived' WHERE id = ?");
    $stmt3->execute([$id]);

    if ($active_rentals > 0) {
        echo "<script>alert('Инвентарь имеет активные аренды и отправлен в архив.'); window.location.href='index.php';</script>";
    } else {
        echo "<script>alert('Инвентарь больше не доступен и отправлен в архив.'); window.location.href='index.php';</script>";
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Подтверждение удаления</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="d-flex justify-content-center align-items-center vh-100">
<div class="card p-4 shadow" style="width: 400px;">
    <h5 class="card-title mb-3">Подтверждение удаления</h5>
    <p>
        Вы уверены, что хотите удалить инвентарь:
        <strong><?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?></strong>?
    </p>
    <form method="POST">
        <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        <input type="hidden" name="confirm_delete" value="yes">
        <button type="submit" class="btn btn-danger w-100 mb-2">Да, удалить</button>
        <a href="index.php" class="btn btn-secondary w-100">Отмена</a>
    </form>
</div>
</body>
</html>
