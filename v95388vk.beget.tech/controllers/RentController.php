<?php
class RentController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Оформление аренды

    public function rentForm() {
        requireLogin();
        runStatusUpdate($this->pdo);
        csrfToken();

        $inventory_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($inventory_id <= 0) die('Неверный ID инвентаря');

        $item   = $this->getItem($inventory_id);
        $tariff = $this->getTariff($inventory_id);

        $status = strtolower(trim($item['status']));
        if ($status === 'archived') die('Инвентарь в архиве');
        if (!in_array($status, array('free', ''))) die('Инвентарь недоступен для аренды');

        $busy_slots = $this->getBusySlots($inventory_id);

        render('rent/rent', array(
            'item'        => $item,
            'tariff'      => $tariff,
            'busy_slots'  => $busy_slots,
            'error'       => '',
            'success'     => '',
            'calculated_price' => 0,
            'duration_hours'   => 0,
            'start_date'  => '',
            'end_date'    => '',
        ));
    }

    public function rent() {
        requireLogin();
        csrfToken();

        $inventory_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($inventory_id <= 0) die('Неверный ID инвентаря');

        $item   = $this->getItem($inventory_id);
        $tariff = $this->getTariff($inventory_id);
        $busy_slots = $this->getBusySlots($inventory_id);

        $start_date = isset($_POST['start_date']) ? $_POST['start_date'] : '';
        $end_date   = isset($_POST['end_date'])   ? $_POST['end_date']   : '';
        $start_ts   = strtotime($start_date);
        $end_ts     = strtotime($end_date);

        $error   = '';
        $success = '';
        $calculated_price = 0;
        $duration_hours   = 0;

        // Расчёт стоимости
        if (isset($_POST['calculate'])) {
            if (empty($start_date) || empty($end_date)) {
                $error = 'Заполните обе даты';
            } elseif ($start_ts < time()) {
                $error = 'Нельзя выбрать прошедшее время';
            } elseif ($end_ts <= $start_ts) {
                $error = 'Дата окончания должна быть позже начала';
            } else {
                $hours = ($end_ts - $start_ts) / 3600;
                if ($hours < 1) {
                    $error = 'Минимальное время аренды — 1 час';
                } else {
                    $hours_rounded = ceil($hours);
                    $days_rounded  = ceil($hours_rounded / 24);
                    $price_hour    = (float)$tariff['price_per_hour'];
                    $price_day     = (float)$tariff['price_per_day'];

                    if ($price_day > 0 && $hours_rounded >= 6) {
                        $calculated_price = $days_rounded * $price_day;
                    } else {
                        $calculated_price = $hours_rounded * $price_hour;
                    }
                    $duration_hours = $hours_rounded;
                    $success = 'Итоговая сумма: ' . number_format($calculated_price, 2) . ' ₽';
                }
            }
        }

        // Подтверждение аренды
        if (isset($_POST['confirm'])) {
            if (empty($start_date) || empty($end_date)) {
                $error = 'Заполните обе даты';
            } elseif ($start_ts < time()) {
                $error = 'Нельзя выбрать прошедшее время';
            } elseif ($end_ts <= $start_ts) {
                $error = 'Дата окончания должна быть позже начала';
            } else {
                $hours_rounded = ceil(($end_ts - $start_ts) / 3600);
                if ($hours_rounded < 1) {
                    $error = 'Минимальное время аренды — 1 час';
                } else {
                    $days_rounded = ceil($hours_rounded / 24);
                    $price_hour   = (float)$tariff['price_per_hour'];
                    $price_day    = (float)$tariff['price_per_day'];

                    if ($price_day > 0 && $hours_rounded >= 6) {
                        $total_price = $days_rounded * $price_day;
                    } else {
                        $total_price = $hours_rounded * $price_hour;
                    }

                    $stmt = $this->pdo->prepare("
                        INSERT INTO rent_history (inventory_id, user_id, tariff_id, start_time, end_time, total_price)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute(array(
                        $inventory_id,
                        (int)$_SESSION['user_id'],
                        $tariff['id'],
                        date('Y-m-d H:i:s', $start_ts),
                        date('Y-m-d H:i:s', $end_ts),
                        $total_price,
                    ));

                    redirect('/');
                }
            }
        }

        render('rent/rent', array(
            'item'             => $item,
            'tariff'           => $tariff,
            'busy_slots'       => $busy_slots,
            'error'            => $error,
            'success'          => $success,
            'calculated_price' => $calculated_price,
            'duration_hours'   => $duration_hours,
            'start_date'       => $start_date,
            'end_date'         => $end_date,
        ));
    }

    //  Досрочный возврат 

    public function returnForm() {
        requireLogin();
        csrfToken();

        $rent = $this->getRent();
        $refund = $this->calcRefund($rent);

        render('rent/return', array(
            'rent'   => $rent,
            'refund' => $refund,
            'error'  => '',
            'success'=> '',
            'paid_hours' => ceil((strtotime($rent['end_time']) - strtotime($rent['start_time'])) / 3600),
            'used_hours' => max(1, ceil((time() - strtotime($rent['start_time'])) / 3600)),
        ));
    }

    public function doReturn() {
        requireLogin();
        checkCsrf();

        $rent    = $this->getRent();
        $refund  = $this->calcRefund($rent);
        $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';

        $paid_hours = ceil((strtotime($rent['end_time']) - strtotime($rent['start_time'])) / 3600);
        $used_hours = max(1, ceil((time() - strtotime($rent['start_time'])) / 3600));

        if ($rent['status'] !== 'active') {
            $error = 'Аренда уже завершена';
        } elseif (empty($comment)) {
            $error = 'Комментарий обязателен';
        } else {
            $this->pdo->prepare("
                UPDATE rent_history
                SET status='early_return', actual_end_time=NOW(), refund_amount=?, return_comment=?
                WHERE id=?
            ")->execute(array($refund, $comment, $rent['id']));

            $this->pdo->prepare("UPDATE inventory SET status='free' WHERE id=?")->execute(array($rent['inventory_id']));

            $success = 'Инвентарь возвращён. Сумма к возврату: ' . number_format($refund, 2) . ' ₽';
            $rent['status'] = 'early_return';

            render('rent/return', array(
                'rent'       => $rent,
                'refund'     => $refund,
                'error'      => '',
                'success'    => $success,
                'paid_hours' => $paid_hours,
                'used_hours' => $used_hours,
            ));
            return;
        }

        render('rent/return', array(
            'rent'       => $rent,
            'refund'     => $refund,
            'error'      => $error,
            'success'    => '',
            'paid_hours' => $paid_hours,
            'used_hours' => $used_hours,
        ));
    }

    //  Перенос даты аренды

    public function editForm() {
        requireLogin();
        runStatusUpdate($this->pdo);
        csrfToken();

        $rent = $this->getRent();
        if ($rent['status'] !== 'active') die('Можно переносить только активные аренды');

        render('rent/edit', array('rent' => $rent, 'error' => '', 'success' => ''));
    }

    public function editRent() {
        requireLogin();
        checkCsrf();
        runStatusUpdate($this->pdo);

        $rent      = $this->getRent();
        $new_start = isset($_POST['start_time']) ? $_POST['start_time'] : '';
        $new_end   = isset($_POST['end_time'])   ? $_POST['end_time']   : '';
        $start_ts  = strtotime($new_start);
        $end_ts    = strtotime($new_end);

        if ($rent['status'] !== 'active') die('Можно переносить только активные аренды');

        $error = '';
        if (!$start_ts || !$end_ts) {
            $error = 'Некорректные даты.';
        } elseif ($start_ts < time()) {
            $error = 'Нельзя перенести аренду на прошлое время.';
        } elseif ($end_ts <= $start_ts) {
            $error = 'Окончание аренды должно быть позже начала.';
        } else {
            // Проверка пересечений
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*)
                FROM rent_history
                WHERE inventory_id = ? AND id != ? AND status = 'active'
                  AND ((start_time <= ? AND end_time > ?) OR (start_time < ? AND end_time >= ?))
            ");
            $stmt->execute(array(
                $rent['inventory_id'], $rent['id'],
                date('Y-m-d H:i:s', $start_ts), date('Y-m-d H:i:s', $start_ts),
                date('Y-m-d H:i:s', $end_ts),   date('Y-m-d H:i:s', $end_ts),
            ));
            if ($stmt->fetchColumn() > 0) {
                $error = 'Выбранный слот уже занят.';
            } else {
                $this->pdo->prepare("UPDATE rent_history SET start_time=?, end_time=? WHERE id=?")->execute(array(
                    date('Y-m-d H:i:s', $start_ts),
                    date('Y-m-d H:i:s', $end_ts),
                    $rent['id'],
                ));
                $rent['start_time'] = date('Y-m-d H:i:s', $start_ts);
                $rent['end_time']   = date('Y-m-d H:i:s', $end_ts);

                render('rent/edit', array('rent' => $rent, 'error' => '', 'success' => 'Аренда успешно перенесена.'));
                return;
            }
        }

        render('rent/edit', array('rent' => $rent, 'error' => $error, 'success' => ''));
    }

    // Приватные хелперы 

    private function getItem($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM inventory WHERE id = ?");
        $stmt->execute(array($id));
        $item = $stmt->fetch();
        if (!$item) die('Инвентарь не найден');
        return $item;
    }

    private function getTariff($inventory_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM tariffs WHERE inventory_id = ?");
        $stmt->execute(array($inventory_id));
        $tariff = $stmt->fetch();
        if (!$tariff) die('Тариф не задан');
        return $tariff;
    }

    private function getBusySlots($inventory_id) {
        $stmt = $this->pdo->prepare("SELECT start_time, end_time FROM rent_history WHERE inventory_id = ? AND status = 'active'");
        $stmt->execute(array($inventory_id));
        return $stmt->fetchAll();
    }

    private function getRent() {
        $rent_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($rent_id <= 0) die('Некорректный ID аренды');
        $user_id = (int)$_SESSION['user_id'];

        $stmt = $this->pdo->prepare("
            SELECT rh.*, i.name AS inventory_name, i.category, i.image_url,
                   i.description, i.status AS inventory_status, t.price_per_hour
            FROM rent_history rh
            JOIN inventory i ON rh.inventory_id = i.id
            LEFT JOIN tariffs t ON rh.tariff_id = t.id
            WHERE rh.id = ? AND rh.user_id = ?
            LIMIT 1
        ");
        $stmt->execute(array($rent_id, $user_id));
        $rent = $stmt->fetch();
        if (!$rent) die('Аренда не найдена');
        return $rent;
    }

    private function calcRefund($rent) {
        $start_ts   = strtotime($rent['start_time']);
        $end_ts     = strtotime($rent['end_time']);
        $now_ts     = time();
        $paid_hours = ceil(($end_ts - $start_ts) / 3600);
        $used_hours = max(1, min(ceil(($now_ts - $start_ts) / 3600), $paid_hours));
        $price_hour = isset($rent['price_per_hour']) && $rent['price_per_hour']
            ? (float)$rent['price_per_hour']
            : ($rent['total_price'] / $paid_hours);
        $refund = $rent['total_price'] - ($used_hours * $price_hour);
        return max(0, $refund);
    }
}
