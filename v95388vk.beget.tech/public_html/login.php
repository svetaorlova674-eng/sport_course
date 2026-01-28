<?php
session_start();
// В самом начале файла подключаем конфигурацию
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $pass = $_POST['password'];

    // Ищем пользователя по email
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Проверяем пароль
    if ($user && password_verify($pass, $user['password_hash'])) {
        
        // --- НАЧАЛО ИЗМЕНЕНИЙ ---
        // Запоминаем данные пользователя в сессию
        $_SESSION['user_id'] = $user['id'];
        
        // ВАЖНО: Сохраняем роль! Это наш "браслет"
        $_SESSION['user_role'] = $user['role']; 
        // --- КОНЕЦ ИЗМЕНЕНИЙ ---

        // Перенаправляем: Админа в админку, Клиента в профиль
        if ($user['role'] === 'admin') {
            header("Location: admin_panel.php");
        } else {
            header("Location: index.php"); // Или profile.php
        }
        exit;
    } else {
        echo "Неверный логин или пароль";
    }
}
?>
<!-- Далее идет HTML форма... -->

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в систему</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .login-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 400px;
            overflow: hidden;
        }
        
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        
        .login-header h1 {
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .login-header p {
            opacity: 0.9;
            font-size: 14px;
        }
        
        .login-form {
            padding: 40px 30px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e1e1;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .form-group input.error {
            border-color: #ff4757;
        }
        
        .error-message {
            color: #ff4757;
            background: #ffeaea;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
            display: none;
        }
        
        .error-message.show {
            display: block;
            animation: fadeIn 0.3s ease;
        }
        
        .login-button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .login-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
        }
        
        .login-button:active {
            transform: translateY(0);
        }
        
        .login-footer {
            text-align: center;
            padding: 20px;
            border-top: 1px solid #eee;
            color: #666;
            font-size: 14px;
        }
        
        .login-footer a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }
        
        .login-footer a:hover {
            text-decoration: underline;
        }
        
        .password-container {
            position: relative;
        }
        
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #667eea;
            cursor: pointer;
            font-size: 14px;
            padding: 5px;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @media (max-width: 480px) {
            .login-container {
                max-width: 100%;
            }
            
            .login-form {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Добро пожаловать</h1>
            <p>Войдите в свой аккаунт</p>
        </div>
        
        <!-- Блок для отображения ошибок -->
        <?php if (isset($error_message)): ?>
        <div class="error-message show" id="errorMessage">
            <?php echo htmlspecialchars($error_message); ?>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="login-form">
            <div class="form-group">
                <label for="email">Email</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    required 
                    placeholder="Введите ваш email"
                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                >
            </div>
            
            <div class="form-group">
                <label for="password">Пароль</label>
                <div class="password-container">
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required 
                        placeholder="Введите ваш пароль"
                    >
                    <button type="button" class="toggle-password" onclick="togglePassword()">
                        Показать
                    </button>
                </div>
            </div>
            
            <button type="submit" class="login-button">Войти</button>
        </form>
        
        <div class="login-footer">
            Нет аккаунта? <a href="register.php">Зарегистрируйтесь</a>
        </div>
    </div>

    <script>
        // Функция для показа/скрытия пароля
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleButton = document.querySelector('.toggle-password');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleButton.textContent = 'Скрыть';
            } else {
                passwordInput.type = 'password';
                toggleButton.textContent = 'Показать';
            }
        }
        
        // Валидация формы на клиенте
        document.querySelector('.login-form').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const errorElement = document.getElementById('errorMessage');
            
            // Простая валидация
            if (!email || !password) {
                e.preventDefault();
                showError('Пожалуйста, заполните все поля');
                return;
            }
            
            if (!isValidEmail(email)) {
                e.preventDefault();
                showError('Введите корректный email адрес');
                return;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                showError('Пароль должен быть не менее 6 символов');
                return;
            }
        });
        
        function isValidEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        }
        
        function showError(message) {
            let errorElement = document.getElementById('errorMessage');
            
            if (!errorElement) {
                errorElement = document.createElement('div');
                errorElement.className = 'error-message';
                errorElement.id = 'errorMessage';
                document.querySelector('.login-form').insertBefore(errorElement, document.querySelector('.form-group'));
            }
            
            errorElement.textContent = message;
            errorElement.classList.add('show');
            
            // Автоматически скрыть через 5 секунд
            setTimeout(() => {
                errorElement.classList.remove('show');
            }, 5000);
        }
        
        // Убираем ошибку при начале ввода
        document.getElementById('email').addEventListener('input', clearError);
        document.getElementById('password').addEventListener('input', clearError);
        
        function clearError() {
            const errorElement = document.getElementById('errorMessage');
            if (errorElement) {
                errorElement.classList.remove('show');
            }
        }
    </script>
</body>
</html>

