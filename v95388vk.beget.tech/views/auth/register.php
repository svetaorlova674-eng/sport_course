<?php
$pageTitle = 'Регистрация';
require ROOT . '/views/layout/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Регистрация</h4>
                </div>
                <div class="card-body">

                    <?php if (!empty($_SESSION['reg_error'])): ?>
                        <div class="alert alert-danger"><?php echo $_SESSION['reg_error']; unset($_SESSION['reg_error']); ?></div>
                    <?php endif; ?>

                    <?php if (!empty($_SESSION['reg_success'])): ?>
                        <div class="alert alert-success"><?php echo $_SESSION['reg_success']; unset($_SESSION['reg_success']); ?></div>
                    <?php else: ?>

                    <form method="POST" action="/register">
                        <div class="mb-3">
                            <label class="form-label">Email адрес</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Пароль</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Подтверждение пароля</label>
                            <input type="password" name="password_confirm" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Зарегистрироваться</button>
                    </form>
                    <div class="mt-3 text-center">
                        <a href="/login">Уже есть аккаунт? Войти</a>
                    </div>

                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require ROOT . '/views/layout/footer.php'; ?>
