<?php
class AdminController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function panel() {
        requireAdmin();
        render('admin/panel');
    }

    public function rentals() {
        requireAdmin();
        runStatusUpdate($this->pdo);
        csrfToken();

        // Завершаем просроченные аренды
        $this->pdo->query("
            UPDATE rent_history
            SET status='completed', actual_end_time=end_time
            WHERE status='active' AND end_time <= NOW()
        ");
        $this->pdo->query("
            UPDATE inventory i
            JOIN rent_history rh ON i.id = rh.inventory_id
            SET i.status='free'
            WHERE rh.status='completed' AND i.status='busy'
        ");

        $rentals = $this->pdo->query("
            SELECT rh.*, u.email,
                   i.name AS inventory_name, i.status AS inventory_status,
                   t.price_per_hour, t.price_per_day
            FROM rent_history rh
            JOIN users u ON rh.user_id = u.id
            JOIN inventory i ON rh.inventory_id = i.id
            LEFT JOIN tariffs t ON rh.tariff_id = t.id
            ORDER BY rh.id DESC
        ")->fetchAll();

        render('admin/rentals', array('rentals' => $rentals));
    }

    public function rollback() {
        requireAdmin();
        checkCsrf();

        $rent_id = isset($_POST['rent_id']) ? (int)$_POST['rent_id'] : 0;
        $stmt = $this->pdo->prepare("SELECT * FROM rent_history WHERE id = ? LIMIT 1");
        $stmt->execute(array($rent_id));
        $r = $stmt->fetch();

        if ($r && $r['status'] === 'early_return') {
            $this->pdo->prepare("
                UPDATE rent_history
                SET status='active', actual_end_time=NULL, refund_amount=NULL, return_comment=NULL
                WHERE id=?
            ")->execute(array($rent_id));
            $this->pdo->prepare("UPDATE inventory SET status='busy' WHERE id=?")->execute(array($r['inventory_id']));
        }
        redirect('/admin/rentals');
    }

    public function addItemForm() {
        requireAdmin();
        render('admin/add_item', array('message' => ''));
    }

    public function addItem() {
        requireAdmin();

        $name        = trim(isset($_POST['name'])           ? $_POST['name']           : '');
        $sport       = trim(isset($_POST['sport'])          ? $_POST['sport']          : '');
        $category    = trim(isset($_POST['category'])       ? $_POST['category']       : '');
        $type        = trim(isset($_POST['type'])           ? $_POST['type']           : '');
        $description = trim(isset($_POST['description'])    ? $_POST['description']    : '');
        $price_per_hour = isset($_POST['price_per_hour'])   ? (float)$_POST['price_per_hour'] : 0;
        $price_per_day  = isset($_POST['price_per_day'])    ? (float)$_POST['price_per_day']  : 0;

        if (empty($name) || empty($sport) || empty($category) || empty($type)) {
            render('admin/add_item', array('message' => '<div class="alert alert-danger">Заполните все обязательные поля!</div>'));
            return;
        }

        $image_url = null;
        if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $image_url = $this->uploadImage($_FILES['file'], 'item_');
        }

        try {
            $this->pdo->prepare("
                INSERT INTO inventory (name, sport, category, type, description, image_url, status, user_id)
                VALUES (?, ?, ?, ?, ?, ?, 'free', ?)
            ")->execute(array($name, $sport, $category, $type, $description, $image_url, (int)$_SESSION['user_id']));

            $inventory_id = $this->pdo->lastInsertId();

            if ($price_per_day > 0 || $price_per_hour > 0) {
                $this->pdo->prepare("INSERT INTO tariffs (inventory_id, price_per_hour, price_per_day) VALUES (?, ?, ?)")
                    ->execute(array($inventory_id, $price_per_hour, $price_per_day));
            }

            render('admin/add_item', array('message' => '<div class="alert alert-success">Инвентарь успешно добавлен!</div>'));
        } catch (PDOException $e) {
            render('admin/add_item', array('message' => '<div class="alert alert-danger">Ошибка БД: ' . e($e->getMessage()) . '</div>'));
        }
    }

    public function editItemForm() {
        requireAdmin();

        $id   = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $item = $this->getItemWithTariff($id);

        render('admin/edit_item', array('item' => $item, 'message' => ''));
    }

    public function editItem() {
        requireAdmin();

        $id          = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $item        = $this->getItemWithTariff($id);
        $name        = trim(isset($_POST['name'])        ? $_POST['name']        : '');
        $sport       = trim(isset($_POST['sport'])       ? $_POST['sport']       : '');
        $category    = trim(isset($_POST['category'])    ? $_POST['category']    : '');
        $type        = trim(isset($_POST['type'])        ? $_POST['type']        : '');
        $description = trim(isset($_POST['description']) ? $_POST['description'] : '');
        $price_per_hour = (float)(isset($_POST['price_per_hour']) ? $_POST['price_per_hour'] : 0);
        $price_per_day  = (float)(isset($_POST['price_per_day'])  ? $_POST['price_per_day']  : 0);

        if (empty($name) || empty($category) || empty($sport) || empty($type)) {
            render('admin/edit_item', array('item' => $item, 'message' => '<div class="alert alert-danger">Заполните все обязательные поля</div>'));
            return;
        }

        $image_url = $item['image_url'];
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $image_url = $this->uploadImage($_FILES['image'], 'inventory_' . $id . '_');
        }

        $this->pdo->prepare("
            UPDATE inventory SET name=?, sport=?, category=?, type=?, description=?, image_url=? WHERE id=?
        ")->execute(array($name, $sport, $category, $type, $description, $image_url, $id));

        // Статус (только если не занят)
        if (isset($_POST['status']) && in_array($_POST['status'], array('free','busy','archived'), true)) {
            if ($item['status'] !== 'busy') {
                $this->pdo->prepare("UPDATE inventory SET status=? WHERE id=?")->execute(array($_POST['status'], $id));
            }
        }

        // Тариф
        $stmt = $this->pdo->prepare("SELECT id FROM tariffs WHERE inventory_id=?");
        $stmt->execute(array($id));
        if ($stmt->fetch()) {
            $this->pdo->prepare("UPDATE tariffs SET price_per_hour=?, price_per_day=? WHERE inventory_id=?")
                ->execute(array($price_per_hour, $price_per_day, $id));
        } else {
            $this->pdo->prepare("INSERT INTO tariffs (inventory_id, price_per_hour, price_per_day) VALUES (?,?,?)")
                ->execute(array($id, $price_per_hour, $price_per_day));
        }

        $item = $this->getItemWithTariff($id);
        render('admin/edit_item', array('item' => $item, 'message' => '<div class="alert alert-success">Инвентарь успешно обновлён</div>'));
    }

    public function deleteItem() {
        requireAdmin();
        checkCsrf();

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id <= 0) die('Неверный ID');

        $this->pdo->prepare("UPDATE inventory SET status='archived' WHERE id=?")->execute(array($id));
        redirect('/');
    }

    // ─── Приватные хелперы ───────────────────────────────────────────────────

    private function getItemWithTariff($id) {
        if ($id <= 0) die('Некорректный ID');
        $stmt = $this->pdo->prepare("
            SELECT i.*, t.price_per_hour, t.price_per_day
            FROM inventory i
            LEFT JOIN tariffs t ON t.inventory_id = i.id
            WHERE i.id = ?
        ");
        $stmt->execute(array($id));
        $item = $stmt->fetch();
        if (!$item) die('Инвентарь не найден');
        return $item;
    }

    private function uploadImage($file, $prefix = 'item_') {
        $uploadDir = ROOT . '/public_html/uploads/';
        $allowed   = array('image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif');

        if ($file['size'] > 5 * 1024 * 1024) die('Файл слишком большой (макс. 5 МБ)');

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!isset($allowed[$mime])) die('Разрешены только JPG, PNG, GIF');
        if (!getimagesize($file['tmp_name'])) die('Файл не является изображением');
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $ext      = $allowed[$mime];
        $filename = $prefix . uniqid() . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) die('Ошибка сохранения файла');

        return 'uploads/' . $filename;
    }
}
