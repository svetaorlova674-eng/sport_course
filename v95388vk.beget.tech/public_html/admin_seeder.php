<?php
// admin_seeder.php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/check_admin.php'; // доступ только для админов

$message = "";

// Получаем список всех таблиц в базе
$tables = array();
$stmt = $pdo->query("SHOW TABLES");
while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
    $tables[] = $row[0];
}

// Обработка POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    // --- Генерация тестовых данных ---
    if ($action === 'generate') {
        $tableName = $_POST['table_name'];
        $count = (int)$_POST['count'];

        if (!in_array($tableName, $tables)) die("Ошибка: таблица не найдена.");

        // --- ЭТАП 1: БЭКАП В CSV ---
        $exportDir = __DIR__ . '/../exports/';
        if (!is_dir($exportDir)) mkdir($exportDir, 0755, true);

        $filename = $exportDir . $tableName . '_' . date('Y-m-d_H-i-s') . '.csv';
        $fp = fopen($filename, 'w');

        $stmt = $pdo->query("SELECT * FROM `$tableName`");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($rows)) {
            $message = "Таблица пуста! Создайте хотя бы одну запись вручную.";
        } else {
            // Заголовки CSV
            fputcsv($fp, array_keys($rows[0]));

            // Данные для CSV
            foreach ($rows as $row) {
                fputcsv($fp, $row);
            }
            fclose($fp);
            $message .= "Бэкап сохранен: " . htmlspecialchars($filename) . "<br>";

            // --- ЭТАП 2: Генерация ---
            $template = $rows[array_rand($rows)];
            $inserted = 0;

            for ($i = 0; $i < $count; $i++) {
                $cols = array();
                $vals = array();

                foreach ($template as $key => $value) {
                    if ($key === 'id') continue;

                    // Сначала проверяем is_test
                    if ($key === 'is_test') {
                        $newValue = 1; // помечаем тестовые записи
                    }
                    // Не трогаем внешние ключи
                    elseif (preg_match('/_id$/', $key)) {
                        $newValue = $value;
                    }
                    // Числа
                    elseif (is_numeric($value)) {
                        $percent = mt_rand(-15, 15) / 100;
                        $newValue = round($value * (1 + $percent), 2);
                    }
                    // Строки
                    else {
                        $newValue = $value . '_' . mt_rand(1000, 9999);
                    }

                    $cols[] = "`$key`";
                    $vals[] = $pdo->quote($newValue);
                }

                $sql = "INSERT INTO `$tableName` (" . implode(',', $cols) . ") VALUES (" . implode(',', $vals) . ")";
                try {
                    $pdo->exec($sql);
                    $inserted++;
                } catch (Exception $e) {
                    continue;
                }
            }

            $message .= "Сгенерировано тестовых записей: $inserted из $count.";
        }
    }

    // --- Удаление тестовых записей ---
    if ($action === 'delete_tests') {
        $tableName = $_POST['table_name'];
        if (!in_array($tableName, $tables)) die("Ошибка: таблица не найдена.");
        $stmt = $pdo->prepare("DELETE FROM `$tableName` WHERE is_test = 1");
        $stmt->execute();
        $message = "Все тестовые записи из таблицы " . htmlspecialchars($tableName) . " удалены.";
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title>Генератор тестовых данных</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-5 bg-light">
<div class="container">
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h3>⚙️ Генератор контента (Seeder)</h3>
        </div>
        <div class="card-body">

            <?php if ($message): ?>
                <div class="alert alert-info"><?= $message ?></div>
            <?php endif; ?>

            <!-- Форма генерации -->
            <form method="post" class="mb-4">
                <input type="hidden" name="action" value="generate">
                <div class="mb-3">
                    <label>Выберите таблицу:</label>
                    <select name="table_name" class="form-select">
                        <?php foreach ($tables as $t): ?>
                            <option value="<?= htmlspecialchars($t) ?>"><?= htmlspecialchars($t) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label>Сколько тестовых записей добавить?</label>
                    <input type="number" name="count" class="form-control" value="10" min="1" max="100">
                </div>
                <button type="submit" class="btn btn-success w-100">🚀 Создать тестовые записи + CSV</button>
            </form>

            <!-- Форма удаления тестовых записей -->
            <form method="post">
                <input type="hidden" name="action" value="delete_tests">
                <div class="mb-3">
                    <label>Удалить все тестовые записи из таблицы:</label>
                    <select name="table_name" class="form-select">
                        <?php foreach ($tables as $t): ?>
                            <option value="<?= htmlspecialchars($t) ?>"><?= htmlspecialchars($t) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-danger w-100">🗑 Удалить все тестовые записи</button>
            </form>

            <a href="index.php" class="btn btn-secondary mt-3">← Вернуться на сайт</a>
        </div>
    </div>
</div>
</body>
</html>
