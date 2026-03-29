<?php
$pageTitle = 'Добавить инвентарь';
$css = array('admin.css');
$js  = array('add_item.js');
require ROOT . '/views/layout/header.php';
?>

<nav class="navbar navbar-expand-lg navbar-light bg-white px-4 mb-4 border-bottom">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold text-primary" href="/">🏸 Прокат инвентаря</a>
        <div class="d-flex align-items-center">
            <span class="me-3 text-muted">
                <?php echo isset($_SESSION['user_name']) ? e($_SESSION['user_name']) : 'Администратор'; ?>
                <span class="badge bg-danger ms-1">Admin</span>
            </span>
            <a href="/admin" class="btn btn-outline-danger btn-sm me-2">Панель админа</a>
            <a href="/" class="btn btn-outline-primary btn-sm me-2">На главную</a>
            <a href="/logout" class="btn btn-dark btn-sm">Выйти</a>
        </div>
    </div>
</nav>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card p-4">
                <div class="text-center mb-4">
                    <h2 class="mb-2">Добавление нового инвентаря</h2>
                    <p class="text-muted">Заполните форму для добавления спортивного инвентаря</p>
                </div>

                <?php echo $message; ?>

                <form method="POST" action="/admin/add-item" id="addItemForm" enctype="multipart/form-data">

                    <div class="mb-3">
                        <label class="form-label">Название инвентаря *</label>
                        <input type="text" name="name" class="form-control" required placeholder="Например: Горный велосипед">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Вид спорта *</label>
                        <select name="sport" class="form-select" required>
                            <option value="">Выберите вид спорта</option>
                            <option value="Велоспорт">Велоспорт</option>
                            <option value="Горные лыжи">Лыжный спорт</option>
                            <option value="Сноубординг">Сноубординг</option>
                            <option value="Самокаты">Кикскутеринг</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Категория *</label>
                        <select name="category" class="form-select" required>
                            <option value="">Выберите категорию</option>
                            <option value="Инвентарь">Инвентарь</option>
                            <option value="Экипировка">Экипировка</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Тип *</label>
                        <select name="type" class="form-select" required>
                            <option value="">Выберите тип</option>
                            <option value="Велосипед">Велосипед</option>
                            <option value="Лыжи">Лыжи</option>
                            <option value="Сноуборд">Сноуборд</option>
                            <option value="Самокат">Самокат</option>
                            <option value="Шлем">Шлем</option>
                            <option value="Ботинки горнолыжные">Ботинки горнолыжные</option>
                            <option value="Крепления для сноуборда">Крепления для сноуборда</option>
                            <option value="Палки горнолыжные">Палки горнолыжные</option>
                            <option value="Велозащита">Велозащита</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Цена за час (руб.)</label>
                        <input type="number" name="price_per_hour" class="form-control" min="0" step="10" value="0">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Цена за день (руб.)</label>
                        <input type="number" name="price_per_day" class="form-control" min="0" step="10" value="0">
                        <small class="text-muted">Если 0 — цена не отображается</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Изображение инвентаря</label>
                        <input type="file" name="file" id="fileInput" class="form-control"
                               accept="image/jpeg,image/png,image/gif" required>
                        <img id="imagePreview" src="" alt="Превью" class="img-fluid mt-2 preview-img">
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Описание инвентаря</label>
                        <textarea name="description" class="form-control" rows="4"
                                  placeholder="Опишите состояние, особенности, комплектацию..."></textarea>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="/" class="btn btn-secondary me-md-2">Отмена</a>
                        <button type="submit" class="btn btn-primary btn-submit">Сохранить инвентарь</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require ROOT . '/views/layout/footer.php'; ?>
