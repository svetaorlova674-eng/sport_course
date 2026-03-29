<?php
class ProfileController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function index() {
        requireLogin();
        runStatusUpdate($this->pdo);

        $user_id = (int)$_SESSION['user_id'];
        csrfToken();

        $stmt = $this->pdo->prepare("
            SELECT
                rh.id, rh.user_id, rh.inventory_id,
                rh.start_time, rh.end_time, rh.actual_end_time,
                rh.status, rh.total_price, rh.refund_amount,
                rh.return_comment,
                i.name        AS inventory_name,
                i.category    AS inventory_category,
                i.image_url,
                i.description AS inventory_description,
                i.status      AS inventory_status
            FROM rent_history rh
            JOIN inventory i ON rh.inventory_id = i.id
            WHERE rh.user_id = ?
            ORDER BY rh.start_time DESC
        ");
        $stmt->execute(array($user_id));
        $rentals = $stmt->fetchAll();

        // Автоматически завершаем просроченные аренды
        $now = time();
        foreach ($rentals as &$r) {
            $end_ts = strtotime($r['end_time']);
            if ($r['status'] === 'active' && $now > $end_ts) {
                $r['status']          = 'completed';
                $r['actual_end_time'] = $r['end_time'];
                $this->pdo->prepare("UPDATE rent_history SET status='completed', actual_end_time=end_time WHERE id=?")->execute(array($r['id']));
                $this->pdo->prepare("UPDATE inventory SET status='free' WHERE id=?")->execute(array($r['inventory_id']));
            }
        }
        unset($r);

        render('profile/index', array('rentals' => $rentals));
    }

    public function changePasswordForm() {
        requireLogin();
        csrfToken();
        render('profile/change_password');
    }

    public function changePassword() {
        requireLogin();
        checkCsrf();

        $old     = isset($_POST['old_password'])     ? $_POST['old_password']     : '';
        $new     = isset($_POST['new_password'])     ? $_POST['new_password']     : '';
        $confirm = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

        if (!$old || !$new || !$confirm) {
            $_SESSION['error'] = 'Заполните все поля';
            redirect('/change-password');
        }
        if ($new !== $confirm) {
            $_SESSION['error'] = 'Новые пароли не совпадают';
            redirect('/change-password');
        }
        if (strlen($new) < 8) {
            $_SESSION['error'] = 'Новый пароль должен быть минимум 8 символов';
            redirect('/change-password');
        }

        $stmt = $this->pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->execute(array($_SESSION['user_id']));
        $user = $stmt->fetch();

        if (!$user || !password_verify($old, $user['password_hash'])) {
            $_SESSION['error'] = 'Старый пароль введён неверно';
            redirect('/change-password');
        }

        $hash = password_hash($new, PASSWORD_DEFAULT);
        $this->pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute(array($hash, $_SESSION['user_id']));

        $_SESSION['success'] = 'Пароль успешно изменён';
        redirect('/change-password');
    }
}
