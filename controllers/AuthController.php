<?php
class AuthController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function loginForm() {
        csrfToken();
        render('auth/login');
    }

    public function login() {
        checkCsrf();

        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $pass  = isset($_POST['password']) ? $_POST['password'] : '';

        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute(array($email));
        $user = $stmt->fetch();

        if ($user && password_verify($pass, $user['password_hash'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_name'] = $user['email'];

            if ($user['role'] === 'admin') {
                redirect('/admin');
            } else {
                redirect('/');
            }
        }

        $_SESSION['login_error'] = 'Неверный логин или пароль';
        redirect('/login');
    }

    public function registerForm() {
        render('auth/register');
    }

    public function register() {
        $email        = isset($_POST['email'])            ? trim($_POST['email'])        : '';
        $pass         = isset($_POST['password'])         ? $_POST['password']           : '';
        $passConfirm  = isset($_POST['password_confirm']) ? $_POST['password_confirm']   : '';

        if (empty($email) || empty($pass)) {
            $_SESSION['reg_error'] = 'Заполните все поля!';
            redirect('/register');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['reg_error'] = 'Некорректный формат Email!';
            redirect('/register');
        }
        if ($pass !== $passConfirm) {
            $_SESSION['reg_error'] = 'Пароли не совпадают!';
            redirect('/register');
        }

        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $sql  = "INSERT INTO users (email, password_hash, role) VALUES (?, ?, 'client')";

        try {
            $this->pdo->prepare($sql)->execute(array($email, $hash));
            $_SESSION['reg_success'] = 'Регистрация успешна! <a href="/login">Войти</a>';
            redirect('/register');
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $_SESSION['reg_error'] = 'Такой email уже зарегистрирован.';
            } else {
                $_SESSION['reg_error'] = 'Ошибка БД: ' . $e->getMessage();
            }
            redirect('/register');
        }
    }

    public function logout() {
        session_unset();
        session_destroy();
        redirect('/');
    }
}
