<?php
$pageTitle = 'Оформление аренды';
$css = array('rent.css');
require ROOT . '/views/layout/header.php';
?>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h2 class="mb-0">Прокат инвентаря</h2>
            <p class="mb-0">Оформление аренды</p>
        </div>
        <div class="card-body p-4">

            <div class="price-info">
                <h4 class="mb-3"><?php echo e($item['name']); ?></h4>
                <p class="mb-2"><strong>Категория:</strong> <?php echo e($item['category']); ?></p>
                <div class="d-flex flex-wrap gap-2">
                    <div class="price-badge"><?php echo number_format($tariff['price_per_hour'], 2); ?> ₽/час</div>
                    <?php if (!empty($tariff['price_per_day'])): ?>
                        <div class="price-badge" style="background-color:#0099cc;">
                            <?php echo number_format($tariff['price_per_day'], 2); ?> ₽/день
                        </div>
                    <?php endif; ?>
                </div>
                <?php if (!empty($item['description'])): ?>
                    <p class="mt-3 mb-0"><strong>Описание:</strong> <?php echo e($item['description']); ?></p>
                <?php endif; ?>
            </div>

            <h4 class="mt-4">Календарь занятости</h4>
            <?php if (!empty($busy_slots)): ?>
                <?php foreach ($busy_slots as $slot): ?>
                    <div style="background:#f8d7da;padding:10px;margin-bottom:8px;border-radius:6px;">
                        🔴 <?php echo date('d.m.Y H:i', strtotime($slot['start_time'])); ?>
                        — <?php echo date('d.m.Y H:i', strtotime($slot['end_time'])); ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="background:#d4edda;padding:10px;border-radius:6px;">
                    🟢 Сейчас нет активных броней
                </div>
            <?php endif; ?>

            <h4 class="mt-4">Выбор времени аренды</h4>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo e($error); ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo e($success); ?>
                    <br>Продолжительность: <?php echo htmlspecialchars($duration_hours, ENT_QUOTES, \'UTF-8\'); ?> часов
                    (<?php echo round($duration_hours / 24, 1); ?> дней)
                    <div class="total-price mt-2">
                        К оплате: <?php echo number_format($calculated_price, 2); ?> ₽
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" action="/rent?id=<?php echo (int)$item['id']; ?>">
                <div class="row mb-4">
                    <div class="col-12 col-md-6 mb-3">
                        <label class="form-label">Начало аренды *</label>
                        <input type="datetime-local" class="form-control" name="start_date"
                               value="<?php echo e($start_date); ?>" required>
                    </div>
                    <div class="col-12 col-md-6 mb-3">
                        <label class="form-label">Окончание аренды *</label>
                        <input type="datetime-local" class="form-control" name="end_date"
                               value="<?php echo e($end_date); ?>" required>
                    </div>
                </div>

                <div class="btn-group-rent mt-2">
                    <button type="submit" name="calculate" value="1"
                            class="btn btn-warning">Рассчитать стоимость</button>

                    <?php if ($success && empty($error)): ?>
                        <button type="submit" name="confirm" value="1"
                                class="btn btn-primary">Подтвердить аренду</button>
                    <?php else: ?>
                        <button type="submit" name="confirm" value="1"
                                class="btn btn-primary" disabled>Подтвердить аренду</button>
                    <?php endif; ?>

                    <a href="/" class="btn btn-secondary">Отмена</a>
                </div>
            </form>

            <div class="mt-4 pt-3 border-top">
                <h5>Как это работает:</h5>
                <ul class="text-muted">
                    <li>1. Выберите дату начала и окончания аренды</li>
                    <li>2. Нажмите «Рассчитать стоимость»</li>
                    <li>3. Проверьте сумму и продолжительность</li>
                    <li>4. Нажмите «Подтвердить аренду»</li>
                </ul>
            </div>

        </div>
    </div>
</div>

<?php require ROOT . '/views/layout/footer.php'; ?>