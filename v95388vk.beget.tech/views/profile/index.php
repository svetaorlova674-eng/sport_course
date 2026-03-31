<?php
$pageTitle = 'Мои аренды — SibGo';
$css = array('profile.css');
require ROOT . '/views/layout/header.php';
?>

<nav class="navbar navbar-light bg-light px-3 px-md-4 mb-4 shadow-sm">
    <div class="container-fluid d-flex justify-content-between align-items-center">
        <?php require ROOT . '/views/layout/logo.php'; ?>
        <div class="d-flex gap-2">
            <a href="/" class="btn btn-outline-secondary btn-sm">На главную</a>
            <a href="/logout" class="btn btn-dark btn-sm">Выйти</a>
        </div>
    </div>
</nav>

<div class="container p-4">
    <h1 class="mb-4">Мои аренды</h1>

    <?php if (empty($rentals)): ?>
        <div class="alert alert-info">Вы пока не арендовали ничего.</div>
    <?php else: ?>
    <div class="row">
        <?php foreach ($rentals as $r): ?>
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 mb-4">

                <!-- Контейнер картинки отдельно от card-header -->
                <?php if ($r['image_url']): ?>
                <div style="height:200px;background:#fff;display:flex;align-items:center;justify-content:center;overflow:hidden;border-bottom:1px solid #e9ecef;">
                    <img src="/<?php echo e($r['image_url']); ?>"
                         alt="<?php echo e($r['inventory_name']); ?>"
                         style="max-height:190px;max-width:100%;object-fit:contain;">
                </div>
                <?php endif; ?>

                <div class="card-header fw-bold">
                    <?php echo e($r['inventory_name']); ?> (<?php echo e($r['inventory_category']); ?>)
                </div>

                <?php if ($r['inventory_status'] === 'archived'): ?>
                    <div style="color:#b00000;font-size:13px;padding:6px 10px;background:#fff5f5;">
                        ⚠️ Инвентарь выведен из обихода и больше недоступен
                    </div>
                <?php endif; ?>

                <div class="card-body">
                    <?php if (!empty($r['inventory_description'])): ?>
                        <?php $descId = 'desc_' . (int)$r['id']; ?>
                        <div id="<?php echo htmlspecialchars($descId, ENT_QUOTES, \'UTF-8\'); ?>"
                             style="max-height:60px;overflow:hidden;font-size:14px;color:#555;margin-bottom:4px;">
                            <?php echo e($r['inventory_description']); ?>
                        </div>
                        <button onclick="toggleDesc('<?php echo htmlspecialchars($descId, ENT_QUOTES, \'UTF-8\'); ?>',this)"
                                class="btn btn-link btn-sm p-0 mb-2" style="font-size:13px;">
                            Показать полностью ▼
                        </button>
                    <?php endif; ?>

                    <p class="mb-1"><strong>Начало:</strong> <?php echo date('d.m.Y H:i', strtotime($r['start_time'])); ?></p>
                    <p class="mb-1"><strong>Окончание:</strong> <?php echo $r['end_time'] ? date('d.m.Y H:i', strtotime($r['end_time'])) : '-'; ?></p>
                    <p class="mb-1"><strong>Сумма:</strong> <?php echo number_format($r['total_price'], 2); ?> ₽</p>
                    <p class="mb-2"><strong>Статус:</strong>
                        <?php
                        $sm = array(
                            'active'       => array('Активна',             'bg-primary'),
                            'completed'    => array('Завершена',           'bg-success'),
                            'early_return' => array('Возвращено досрочно', 'bg-warning text-dark'),
                            'cancelled'    => array('Отменена',            'bg-secondary'),
                        );
                        $s = isset($sm[$r['status']]) ? $sm[$r['status']] : array($r['status'], 'bg-secondary');
                        ?>
                        <span class="badge <?php echo htmlspecialchars($s[1], ENT_QUOTES, \'UTF-8\'); ?>"><?php echo htmlspecialchars($s[0], ENT_QUOTES, \'UTF-8\'); ?></span>
                    </p>

                    <?php if ($r['status'] === 'active' && $r['inventory_status'] !== 'archived'): ?>
                        <div class="d-flex gap-2 mt-2">
                            <a href="/return?id=<?php echo (int)$r['id']; ?>" class="btn btn-warning btn-sm">Вернуть досрочно</a>
                            <a href="/edit-rent?id=<?php echo (int)$r['id']; ?>" class="btn btn-info btn-sm">Перенести дату</a>
                        </div>
                    <?php endif; ?>

                    <?php if ($r['status'] === 'early_return' && $r['refund_amount'] > 0): ?>
                        <p class="mt-2 mb-0"><strong>Возврат:</strong> <?php echo number_format($r['refund_amount'], 2); ?> ₽</p>
                        <p><strong>Комментарий:</strong> <?php echo e($r['return_comment']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<script>
function toggleDesc(id, btn) {
    var el = document.getElementById(id);
    if (el.style.maxHeight === 'none' || el.style.overflow === 'visible') {
        el.style.maxHeight = '60px';
        el.style.overflow  = 'hidden';
        btn.textContent    = 'Показать полностью ▼';
    } else {
        el.style.maxHeight = 'none';
        el.style.overflow  = 'visible';
        btn.textContent    = 'Свернуть ▲';
    }
}
</script>

<?php require ROOT . '/views/layout/footer.php'; ?>