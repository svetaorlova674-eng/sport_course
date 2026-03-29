<?php
$pageTitle = 'Перенос даты аренды';
require ROOT . '/views/layout/header.php';
?>

<div class="container p-4" style="max-width:650px">
    <h3 class="mb-3">Перенос даты: <?php echo e($rent['inventory_name']); ?></h3>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo e($error); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo e($success); ?></div>
        <a href="/profile" class="btn btn-secondary">В профиль</a>
    <?php else: ?>
        <form method="POST" action="/edit-rent?id=<?php echo (int)$rent['id']; ?>">
            <input type="hidden" name="csrf_token" value="<?php echo e(csrfToken()); ?>">

            <div class="mb-3">
                <label class="form-label">Начало аренды</label>
                <input type="datetime-local" name="start_time"
                       value="<?php echo date('Y-m-d\TH:i', strtotime($rent['start_time'])); ?>"
                       class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Окончание аренды</label>
                <input type="datetime-local" name="end_time"
                       value="<?php echo date('Y-m-d\TH:i', strtotime($rent['end_time'])); ?>"
                       class="form-control" required>
            </div>

            <button class="btn btn-primary">Сохранить</button>
            <a href="/profile" class="btn btn-secondary">Отмена</a>
        </form>
    <?php endif; ?>
</div>

<?php require ROOT . '/views/layout/footer.php'; ?>
