<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Смена пароля</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #f0f4f8;
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 20px;
}

.card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.1);
    width: 100%;
    max-width: 440px;
}

.card-header {
    background: #0d6efd;
    color: white;
    padding: 30px 20px;
    text-align: center;
    border-radius: 15px 15px 0 0;
}

.card-header h1 {
    font-size: 24px;
    margin-bottom: 10px;
    font-weight: 700;
}

.card-header p {
    opacity: 0.9;
    font-size: 14px;
    margin: 0;
}

.card-body {
    padding: 40px 30px;
}

.form-label {
    display: block;
    margin-bottom: 8px;
    color: #333;
    font-weight: 500;
    font-size: 14px;
}

.mb-3 {
    margin-bottom: 25px;
}

.mb-4 {
    margin-bottom: 30px;
}

.password-wrapper {
    position: relative;
}

.form-control {
    width: 100%;
    padding: 12px 42px 12px 15px;
    border: 2px solid #e1e1e1;
    border-radius: 8px;
    font-size: 16px;
    height: 46px;
    transition: border-color 0.3s;
}

.form-control:focus {
    outline: none;
    border-color: #0d6efd;
    box-shadow: none;
}

.toggle-password {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #0d6efd;
    cursor: pointer;
    font-size: 18px;
    padding: 5px;
    z-index: 2;
}

/* Убираем обводку при клике */
.toggle-password:focus {
    outline: none;
    box-shadow: none;
}

.password-hint {
    font-size: 13px;
    color: #6c757d;
    margin-top: 4px;
    display: block;
}

.alert {
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 14px;
    text-align: center;
    animation: fadeIn 0.3s ease;
}

.alert-danger {
    color: #721c24;
    background-color: #f8d7da;
    border: 1px solid #f5c6cb;
}

.alert-success {
    color: #155724;
    background-color: #d4edda;
    border: 1px solid #c3e6cb;
}

.btn-primary {
    width: 100%;
    padding: 14px;
    background: #0d6efd;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
    height: 48px;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(0,123,255,0.3);
}

.card-footer {
    text-align: center;
    padding: 20px;
    border-top: 1px solid #eee;
    color: #666;
    font-size: 14px;
    background: transparent;
}

.card-footer a {
    color: #0d6efd;
    text-decoration: none;
    font-weight: 500;
}

.card-footer a:hover {
    text-decoration: underline;
}

.w-100 {
    width: 100%;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@media (max-width: 480px) {
    body {
        display: flex;
        padding: 20px;
    }
    
    .card {
        max-width: 100%;
        border-radius: 15px;
    }
    
    .card-header {
        padding: 24px 16px;
        border-radius: 15px 15px 0 0;
    }
    
    .card-body {
        padding: 30px 20px;
    }
    
    .card-footer {
        padding: 16px;
        border-radius: 0 0 15px 15px;
    }
}
</style>
</head>
<body>

<div class="card">
    <div class="card-header">
        <h1>Смена пароля</h1>
        <p>Подтвердите текущий пароль и задайте новый</p>
    </div>

    <div class="card-body">

        <?php if (!empty($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($_SESSION['error']) ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($_SESSION['success']) ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <form method="POST" action="update_password.php">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

            <div class="mb-3">
                <label class="form-label">Старый пароль</label>
                <div class="password-wrapper">
                    <input type="password" name="old_password" class="form-control" required>
                    <button type="button" class="toggle-password">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Новый пароль</label>
                <div class="password-wrapper">
                    <input type="password" name="new_password" class="form-control" minlength="8" required>
                    <button type="button" class="toggle-password">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
                <div class="password-hint">
                    Минимум 8 символов
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label">Повтор нового пароля</label>
                <div class="password-wrapper">
                    <input type="password" name="confirm_password" class="form-control" minlength="8" required>
                    <button type="button" class="toggle-password">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100">
                Сменить пароль
            </button>
        </form>

    </div>

    <div class="card-footer">
        <a href="profile.php">← Вернуться в профиль</a>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.toggle-password').forEach(button => {
        button.addEventListener('click', function () {
            const input = this.parentElement.querySelector('input');
            const icon = this.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        });
    });
});
</script>

</body>
</html>