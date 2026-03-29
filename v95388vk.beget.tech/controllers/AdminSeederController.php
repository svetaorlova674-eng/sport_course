<?php
class AdminSeederController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function index() {
        requireAdmin();
        $tables = $this->getTables();
        render('admin/seeder', array('tables' => $tables, 'message' => ''));
    }

    public function handle() {
        requireAdmin();
        $tables  = $this->getTables();
        $message = '';
        $action  = isset($_POST['action']) ? $_POST['action'] : '';

        if ($action === 'generate') {
            $message = $this->generate($tables);
        } elseif ($action === 'delete_tests') {
            $message = $this->deleteTests($tables);
        }

        render('admin/seeder', array('tables' => $tables, 'message' => $message));
    }

    // ─── Генерация тестовых записей ─────────────────────────────────────────

    private function generate($tables) {
        $tableName = isset($_POST['table_name']) ? $_POST['table_name'] : '';
        $count     = isset($_POST['count'])      ? (int)$_POST['count'] : 10;

        if (!in_array($tableName, $tables)) {
            return '<div class="alert alert-danger">Ошибка: таблица не найдена.</div>';
        }

        // Бэкап в CSV
        $exportDir = ROOT . '/exports/';
        if (!is_dir($exportDir)) {
            mkdir($exportDir, 0755, true);
        }

        $filename = $exportDir . $tableName . '_' . date('Y-m-d_H-i-s') . '.csv';
        $fp = fopen($filename, 'w');

        $stmt = $this->pdo->query("SELECT * FROM `$tableName`");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($rows)) {
            fclose($fp);
            return '<div class="alert alert-warning">Таблица пуста! Создайте хотя бы одну запись вручную.</div>';
        }

        // Записываем заголовки и данные в CSV
        fputcsv($fp, array_keys($rows[0]));
        foreach ($rows as $row) {
            fputcsv($fp, $row);
        }
        fclose($fp);

        $message = '<div class="alert alert-info">Бэкап сохранён: <code>' . e(basename($filename)) . '</code></div>';

        // Генерация записей
        $template = $rows[array_rand($rows)];
        $inserted = 0;

        for ($i = 0; $i < $count; $i++) {
            $cols = array();
            $vals = array();

            foreach ($template as $key => $value) {
                if ($key === 'id') continue;

                if ($key === 'is_test') {
                    $newValue = 1;
                } elseif (preg_match('/_id$/', $key)) {
                    // Внешние ключи не трогаем
                    $newValue = $value;
                } elseif (is_numeric($value)) {
                    $percent  = mt_rand(-15, 15) / 100;
                    $newValue = round($value * (1 + $percent), 2);
                } else {
                    $newValue = $value . '_' . mt_rand(1000, 9999);
                }

                $cols[] = "`$key`";
                $vals[] = $this->pdo->quote($newValue);
            }

            $sql = "INSERT INTO `$tableName` (" . implode(',', $cols) . ") VALUES (" . implode(',', $vals) . ")";
            try {
                $this->pdo->exec($sql);
                $inserted++;
            } catch (Exception $e) {
                continue;
            }
        }

        $message .= '<div class="alert alert-success">Сгенерировано записей: <strong>' . $inserted . '</strong> из ' . $count . '</div>';
        return $message;
    }

    // ─── Удаление тестовых записей ───────────────────────────────────────────

    private function deleteTests($tables) {
        $tableName = isset($_POST['table_name']) ? $_POST['table_name'] : '';

        if (!in_array($tableName, $tables)) {
            return '<div class="alert alert-danger">Ошибка: таблица не найдена.</div>';
        }

        $stmt = $this->pdo->prepare("DELETE FROM `$tableName` WHERE is_test = 1");
        $stmt->execute();
        $deleted = $stmt->rowCount();

        return '<div class="alert alert-success">Удалено тестовых записей: <strong>' . $deleted . '</strong> из таблицы <code>' . e($tableName) . '</code></div>';
    }

    // ─── Хелпер: список таблиц ───────────────────────────────────────────────

    private function getTables() {
        $tables = array();
        $stmt   = $this->pdo->query("SHOW TABLES");
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }
        return $tables;
    }
}
