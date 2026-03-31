<?php
$pageTitle = 'Смена пароля';
$css = array('change_password.css');
$js  = array('change_password.js');
require ROOT . '/views/layout/header.php';
?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">

<div class="card">
    <div class="card-header">
        <h1>Смена пароля</h1>
        <p>Подтвердите текущий пароль и задайте новый</p>
    </div>

    <div class="card-body">

        <?php if (!empty($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo e($_SESSION['error']); unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <?php if (!empty($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo e($_SESSION['success']); unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <form method="POST" action="/change-password">
            <input type="hidden" name="csrf_token" value="<?php echo e(csrfToken()); ?>">

            <div class="mb-3">
                <label class="form-label">Старый пароль</label>
                <div class="password-wrapper">
                    <input type="password" name="old_password" class="form-control" required>
                    <button type="button" class="toggle-password"><i class="bi bi-eye"></i></button>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Новый пароль</label>
                <div class="password-wrapper">
                    <input type="password" name="new_password" class="form-control" minlength="8" required>
                    <button type="button" class="toggle-password"><i class="bi bi-eye"></i></button>
                </div>
                <span class="password-hint">Минимум 8 символов</span>
            </div>

            <div class="mb-4">
                <label class="form-label">Повтор нового пароля</label>
                <div class="password-wrapper">
                    <input type="password" name="confirm_password" class="form-control" minlength="8" required>
                    <button type="button" class="toggle-password"><i class="bi bi-eye"></i></button>
                </div>
            </div>

            <button type="submit" class="btn-primary w-100">Сменить пароль</button>
        </form>
    </div>

    <div class="card-footer">
        <a href="/profile">← Вернуться в профиль</a>
    </div>
</div>

<?php require ROOT . '/views/layout/footer.php'; ?>
