<?php
$pageTitle = 'Управление арендами — SibGo';
require ROOT . '/views/layout/header.php';

function calculate_total($start, $end, $price_hour, $price_day) {
    if (!$end) return '-';
    $seconds = strtotime($end) - strtotime($start);
    if ($seconds <= 0) return 0;
    $hours = ceil($seconds / 3600);
    $days  = ceil($hours / 24);
    $price_hour = is_numeric($price_hour) ? $price_hour : 0;
    $price_day  = is_numeric($price_day)  ? $price_day  : 0;
    if ($hours >= 6 && $price_day > 0) return $days * $price_day;
    elseif ($price_hour > 0) return $hours * $price_hour;
    return 0;
}
?>

<style>
/* Мобильный адаптив для таблицы аренд */
@media (max-width: 767px) {
    .rentals-table thead { display: none; }

    .rentals-table tr {
        display: block;
        background: #fff;
        border: 1px solid #dee2e6;
        border-radius: 10px;
        margin-bottom: 16px;
        padding: 12px;
        box-shadow: 0 2px 6px rgba(0,0,0,.06);
    }

    .rentals-table td {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        padding: 5px 4px;
        border: none;
        font-size: 14px;
        gap: 8px;
    }

    .rentals-table td::before {
        content: attr(data-label);
        font-weight: 600;
        color: #6c757d;
        min-width: 110px;
        flex-shrink: 0;
    }

    .rentals-table td:empty { display: none; }
}
</style>

<nav class="navbar navbar-light bg-light px-3 px-md-4 mb-4 shadow-sm">
    <div class="container-fluid d-flex justify-content-between align-items-center">
        <?php require ROOT . '/views/layout/logo.php'; ?>
        <a href="/admin" class="btn btn-secondary btn-sm">← Назад</a>
    </div>
</nav>

<div class="container-fluid px-3 px-md-4">
    <h1 class="mb-3">Все аренды</h1>

    <!-- Десктоп: таблица -->
    <div class="table-responsive d-none d-md-block">
        <table class="table table-bordered table-striped table-hover align-middle rentals-table">
            <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Инвентарь</th>
                <th>Арендатор</th>
                <th>Статус</th>
                <th>Цена/ч</th>
                <th>Цена/день</th>
                <th>Начало</th>
                <th>Окончание</th>
                <th>Итого</th>
                <th>Возврат</th>
                <th>Комментарий</th>
                <th>Действие</th>
            </tr>
            </thead>
            <tbody>
            <?php
            $now = time();
            foreach ($rentals as $r):
                $total    = calculate_total($r['start_time'], $r['end_time'], $r['price_per_hour'], $r['price_per_day']);
                $start_ts = strtotime($r['start_time']);
                $end_ts   = strtotime($r['end_time'] ?: ($r['actual_end_time'] ?: $r['start_time']));

                if ($r['inventory_status'] === 'archived') {
                    $badge = 'bg-danger'; $label = 'Архив';
                } elseif ($r['status'] === 'active' && $now >= $start_ts - 3600 && $now <= $end_ts) {
                    $badge = 'bg-warning text-dark'; $label = 'Занят';
                } elseif (in_array($r['status'], array('early_return','completed','cancelled'))) {
                    $badge = 'bg-success'; $label = 'Свободен';
                } else {
                    $badge = $r['inventory_status'] === 'free' ? 'bg-success' : 'bg-warning text-dark';
                    $label = $r['inventory_status'] === 'free' ? 'Свободен' : 'Занят';
                }
            ?>
            <tr>
                <td><?php echo $r['id']; ?></td>
                <td><?php echo e($r['inventory_name']); ?></td>
                <td><?php echo e($r['email']); ?></td>
                <td><span class="badge <?php echo $badge; ?>"><?php echo $label; ?></span></td>
                <td><?php echo is_numeric($r['price_per_hour']) ? $r['price_per_hour'] . ' ₽' : '-'; ?></td>
                <td><?php echo is_numeric($r['price_per_day'])  ? $r['price_per_day']  . ' ₽' : '-'; ?></td>
                <td><?php echo date('d.m.Y H:i', strtotime($r['start_time'])); ?></td>
                <td><?php echo $r['end_time'] ? date('d.m.Y H:i', strtotime($r['end_time'])) : '-'; ?></td>
                <td><?php echo is_numeric($total) ? $total . ' ₽' : '-'; ?></td>
                <td><?php echo $r['refund_amount'] > 0 ? number_format($r['refund_amount'], 2) . ' ₽' : '-'; ?></td>
                <td style="max-width:150px;"><?php echo $r['return_comment'] ? e($r['return_comment']) : '-'; ?></td>
                <td><?php echo renderAction($r, csrfToken()); ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Мобиль: карточки -->
    <div class="d-md-none">
        <?php foreach ($rentals as $r):
            $total    = calculate_total($r['start_time'], $r['end_time'], $r['price_per_hour'], $r['price_per_day']);
            $start_ts = strtotime($r['start_time']);
            $end_ts   = strtotime($r['end_time'] ?: ($r['actual_end_time'] ?: $r['start_time']));

            if ($r['inventory_status'] === 'archived') {
                $badge = 'bg-danger'; $label = 'Архив';
            } elseif ($r['status'] === 'active' && time() >= $start_ts - 3600 && time() <= $end_ts) {
                $badge = 'bg-warning text-dark'; $label = 'Занят';
            } elseif (in_array($r['status'], array('early_return','completed','cancelled'))) {
                $badge = 'bg-success'; $label = 'Свободен';
            } else {
                $badge = $r['inventory_status'] === 'free' ? 'bg-success' : 'bg-warning text-dark';
                $label = $r['inventory_status'] === 'free' ? 'Свободен' : 'Занят';
            }
        ?>
        <div class="card mb-3 shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>#<?php echo $r['id']; ?> <?php echo e($r['inventory_name']); ?></strong>
                <span class="badge <?php echo $badge; ?>"><?php echo $label; ?></span>
            </div>
            <div class="card-body py-2" style="font-size:14px;">
                <div class="row g-1">
                    <div class="col-6"><span class="text-muted">Арендатор:</span><br><?php echo e($r['email']); ?></div>
                    <div class="col-6"><span class="text-muted">Итого:</span><br><strong><?php echo is_numeric($total) ? $total . ' ₽' : '-'; ?></strong></div>
                    <div class="col-6"><span class="text-muted">Начало:</span><br><?php echo date('d.m.Y H:i', strtotime($r['start_time'])); ?></div>
                    <div class="col-6"><span class="text-muted">Окончание:</span><br><?php echo $r['end_time'] ? date('d.m.Y H:i', strtotime($r['end_time'])) : '-'; ?></div>
                    <?php if ($r['price_per_hour']): ?>
                    <div class="col-6"><span class="text-muted">Цена/ч:</span><br><?php echo $r['price_per_hour']; ?> ₽</div>
                    <?php endif; ?>
                    <?php if ($r['price_per_day']): ?>
                    <div class="col-6"><span class="text-muted">Цена/день:</span><br><?php echo $r['price_per_day']; ?> ₽</div>
                    <?php endif; ?>
                    <?php if ($r['refund_amount'] > 0): ?>
                    <div class="col-6"><span class="text-muted">Возврат:</span><br><?php echo number_format($r['refund_amount'], 2); ?> ₽</div>
                    <?php endif; ?>
                    <?php if ($r['return_comment']): ?>
                    <div class="col-12"><span class="text-muted">Комментарий:</span><br><?php echo e($r['return_comment']); ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-footer"><?php echo renderAction($r, csrfToken()); ?></div>
        </div>
        <?php endforeach; ?>
    </div>

</div>

<?php
function renderAction($r, $csrf) {
    switch ($r['status']) {
        case 'active':
            return '<span class="text-primary fw-semibold">Активна</span>';
        case 'completed':
            return '<span class="text-success fw-semibold">Завершена</span>';
        case 'cancelled':
            return '<span class="text-muted">Отменена</span>';
        case 'early_return':
            return '
                <form method="POST" action="/admin/rollback">
                    <input type="hidden" name="csrf_token" value="' . e($csrf) . '">
                    <input type="hidden" name="rent_id" value="' . (int)$r['id'] . '">
                    <button class="btn btn-sm btn-danger">Откатить возврат</button>
                </form>';
        default:
            return '-';
    }
}
?>

<?php require ROOT . '/views/layout/footer.php'; ?>