<?php
$pageTitle = 'Вход в систему';
$css = array('login.css');
$js  = array('login.js');
require ROOT . '/views/layout/header.php';
?>

<div class="login-container">
    <div class="login-header">
        <h1>Добро пожаловать</h1>
        <p>Войдите в свой аккаунт</p>
    </div>

    <?php if (!empty($_SESSION['login_error'])): ?>
        <div class="error-message show" id="errorMessage">
            <?php echo e($_SESSION['login_error']); unset($_SESSION['login_error']); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="/login" class="login-form">
        <input type="hidden" name="csrf_token" value="<?php echo e(csrfToken()); ?>">

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required placeholder="Введите ваш email">
        </div>

        <div class="form-group">
            <label for="password">Пароль</label>
            <div class="password-container">
                <input type="password" id="password" name="password" required placeholder="Введите ваш пароль">
                <button type="button" class="toggle-password" onclick="togglePassword()">Показать</button>
            </div>
        </div>

        <button type="submit" class="login-button">Войти</button>
    </form>

    <div class="login-footer">
        Нет аккаунта? <a href="/register">Зарегистрируйтесь</a>
    </div>
</div>

<?php require ROOT . '/views/layout/footer.php'; ?>