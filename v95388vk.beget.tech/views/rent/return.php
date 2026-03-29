<?php
$pageTitle = 'Возврат аренды';
require ROOT . '/views/layout/header.php';
?>

<div class="container p-4" style="max-width:650px">
    <h3 class="mb-3">Возврат: <?php echo e($rent['inventory_name']); ?></h3>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo e($error); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo e($success); ?></div>
        <a href="/profile" class="btn btn-secondary">В профиль</a>
    <?php else: ?>
        <p><strong>Начало:</strong> <?php echo $rent['start_time']; ?></p>
        <p><strong>Плановое окончание:</strong> <?php echo $rent['end_time']; ?></p>
        <p><strong>Оплачено часов:</strong> <?php echo $paid_hours; ?></p>
        <p><strong>Использовано часов:</strong> <?php echo $used_hours; ?></p>
        <div class="alert alert-info"><strong>Сумма к возврату:</strong> <?php echo number_format($refund, 2); ?> ₽</div>

        <form method="POST" action="/return?id=<?php echo (int)$rent['id']; ?>">
            <input type="hidden" name="csrf_token" value="<?php echo e(csrfToken()); ?>">
            <div class="mb-3">
                <label class="form-label">Комментарий (обязательно)</label>
                <textarea name="comment" class="form-control" rows="3" required></textarea>
            </div>
            <button class="btn btn-warning">Подтвердить возврат</button>
            <a href="/profile" class="btn btn-secondary">Отмена</a>
        </form>
    <?php endif; ?>
</div>

<?php require ROOT . '/views/layout/footer.php'; ?>
