<?php
class InventoryController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function index() {
        runStatusUpdate($this->pdo);

        $page  = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = 9;
        $offset = ($page - 1) * $limit;

        // Фильтры
        $search    = isset($_GET['q'])         ? trim($_GET['q'])         : '';
        $sport     = isset($_GET['sport'])      ? trim($_GET['sport'])     : '';
        $category  = isset($_GET['category'])  ? trim($_GET['category'])  : '';
        $type      = isset($_GET['type'])       ? trim($_GET['type'])      : '';
        $only_free = !empty($_GET['only_free']) ? 1 : 0;

        $conditions = array();
        $params     = array();

        if (!isAdmin()) {
            $conditions[] = "i.status != 'archived'";
        }
        if ($search !== '') {
            $conditions[] = "i.name LIKE ?";
            $params[]     = '%' . $search . '%';
        }
        if ($sport !== '') {
            $conditions[] = "i.sport = ?";
            $params[]     = $sport;
        }
        if ($category !== '') {
            $conditions[] = "i.category = ?";
            $params[]     = $category;
        }
        if ($type !== '') {
            $conditions[] = "i.type = ?";
            $params[]     = $type;
        }
        if ($only_free) {
            $conditions[] = "i.status = 'free'";
        }

        $where = count($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

        // Считаем total
        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM inventory i LEFT JOIN tariffs t ON i.id = t.inventory_id $where");
        $countStmt->execute($params);
        $totalRows  = (int)$countStmt->fetchColumn();
        $totalPages = max(1, ceil($totalRows / $limit));

        $stmt = $this->pdo->prepare("
            SELECT i.*, t.price_per_hour, t.price_per_day
            FROM inventory i
            LEFT JOIN tariffs t ON i.id = t.inventory_id
            $where
            ORDER BY i.id DESC
            LIMIT $limit OFFSET $offset
        ");
        $stmt->execute($params);
        $items = $stmt->fetchAll();

        render('inventory/index', array(
            'items'       => $items,
            'currentPage' => $page,
            'totalPages'  => $totalPages,
            'f_search'    => $search,
            'f_sport'     => $sport,
            'f_category'  => $category,
            'f_type'      => $type,
            'f_only_free' => $only_free,
        ));
    }
}