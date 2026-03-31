<?php
$pageTitle = 'Редактирование инвентаря';
$css = array('admin.css');
require ROOT . '/views/layout/header.php';
?>

<nav class="navbar navbar-light bg-white border-bottom mb-4 px-4">
    <a class="navbar-brand fw-bold text-primary" href="/">🏸 Прокат инвентаря</a>
    <a href="/admin" class="btn btn-outline-danger btn-sm">Панель админа</a>
</nav>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-sm p-4">
                <h4 class="text-center mb-3">Редактирование инвентаря</h4>

                <?php echo htmlspecialchars($message, ENT_QUOTES, \'UTF-8\'); ?>

                <form method="POST" action="/admin/edit-item?id=<?php echo (int)$item['id']; ?>" enctype="multipart/form-data">

                    <div class="mb-3">
                        <label class="form-label">Название *</label>
                        <input type="text" name="name" class="form-control"
                               value="<?php echo e($item['name']); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Вид спорта *</label>
                        <select name="sport" class="form-select" required>
                            <option value="">Выберите вид спорта</option>
                            <?php foreach (array('Велоспорт','Горные лыжи','Сноубординг','Кикскутеринг','Другое') as $s): ?>
                                <option value="<?php echo e($s); ?>" <?php echo $item['sport'] === $s ? 'selected' : ''; ?>><?php echo e($s); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Категория *</label>
                        <select name="category" class="form-select" required>
                            <option value="">Выберите категорию</option>
                            <?php foreach (array('Инвентарь','Экипировка') as $c): ?>
                                <option value="<?php echo e($c); ?>" <?php echo $item['category'] === $c ? 'selected' : ''; ?>><?php echo e($c); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Тип *</label>
                        <select name="type" class="form-select" required>
                            <option value="">Выберите тип</option>
                            <?php foreach (array('Велосипед','Лыжи','Сноуборд','Самокат','Шлем','Ботинки горнолыжные','Крепления для сноуборда','Палки горнолыжные','Велозащита','Прочее') as $t): ?>
                                <option value="<?php echo e($t); ?>" <?php echo $item['type'] === $t ? 'selected' : ''; ?>><?php echo e($t); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Цена за час</label>
                        <input type="number" name="price_per_hour" class="form-control" min="0" step="10"
                               value="<?php echo isset($item['price_per_hour']) ? $item['price_per_hour'] : 0; ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Цена за день</label>
                        <input type="number" name="price_per_day" class="form-control" min="0" step="10"
                               value="<?php echo isset($item['price_per_day']) ? $item['price_per_day'] : 0; ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Новое изображение</label>
                        <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/gif">
                        <?php if (!empty($item['image_url'])): ?>
                            <img src="/public_html/<?php echo e($item['image_url']); ?>" style="max-height:120px;margin-top:10px;">
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Описание</label>
                        <textarea name="description" class="form-control" rows="4"><?php echo e($item['description']); ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Статус инвентаря</label>
                        <select name="status" class="form-select">
                            <?php foreach (array('free'=>'Доступен','busy'=>'Занят','archived'=>'В архиве') as $val => $label): ?>
                                <option value="<?php echo htmlspecialchars($val, ENT_QUOTES, \'UTF-8\'); ?>" <?php echo $item['status'] === $val ? 'selected' : ''; ?>><?php echo htmlspecialchars($label, ENT_QUOTES, \'UTF-8\'); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($item['status'] === 'busy'): ?>
                            <div class="form-text text-muted">Занятый инвентарь нельзя изменить</div>
                        <?php endif; ?>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="/admin" class="btn btn-secondary">Отмена</a>
                        <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require ROOT . '/views/layout/footer.php'; ?>
