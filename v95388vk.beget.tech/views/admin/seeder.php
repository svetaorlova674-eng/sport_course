<?php
$pageTitle = 'Генератор данных — SibGo';
$css = array('admin.css');
require ROOT . '/views/layout/header.php';
?>

<nav class="navbar navbar-light bg-light px-3 px-md-4 mb-4 shadow-sm">
    <div class="container-fluid d-flex justify-content-between align-items-center">
        <?php require ROOT . '/views/layout/logo.php'; ?>
        <a href="/admin" class="btn btn-secondary btn-sm">← Назад</a>
    </div>
</nav>

<div class="container pb-5" style="max-width:700px;">
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h3 class="mb-0">Генератор контента (Seeder)</h3>
        </div>
        <div class="card-body">

            <?php if ($message): ?>
                <?php echo $message; ?>
            <?php endif; ?>

            <!-- Форма генерации -->
            <form method="POST" action="/admin/seeder" class="mb-4">
                <input type="hidden" name="action" value="generate">

                <div class="mb-3">
                    <label class="form-label fw-semibold">Выберите таблицу:</label>
                    <select name="table_name" class="form-select">
                        <?php foreach ($tables as $t): ?>
                            <option value="<?php echo e($t); ?>"><?php echo e($t); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Рекомендуется: inventory, rent_history.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Сколько тестовых записей добавить?</label>
                    <input type="number" name="count" class="form-control" value="10" min="1" max="100">
                </div>

                <div class="alert alert-warning py-2">
                    <small>⚠️ Скрипт создаст CSV-бэкап в папке <code>/exports</code>, затем клонирует
                    случайную запись указанное количество раз, изменяя числа на ±15%.</small>
                </div>

                <button type="submit" class="btn btn-success w-100">
                    Создать тестовые записи + CSV бэкап
                </button>
            </form>

            <hr>

            <!-- Форма удаления тестовых записей -->
            <form method="POST" action="/admin/seeder">
                <input type="hidden" name="action" value="delete_tests">

                <div class="mb-3">
                    <label class="form-label fw-semibold">Удалить все тестовые записи из таблицы:</label>
                    <select name="table_name" class="form-select">
                        <?php foreach ($tables as $t): ?>
                            <option value="<?php echo e($t); ?>"><?php echo e($t); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Удаляются только записи с полем <code>is_test = 1</code>.</div>
                </div>

                <button type="submit" class="btn btn-danger w-100"
                        onclick="return confirm('Удалить все тестовые записи?');">
                     Удалить все тестовые записи
                </button>
            </form>

        </div>
    </div>
</div>

<?php require ROOT . '/views/layout/footer.php'; ?>
