<?php
/**
 * public_html/index.php — FRONT CONTROLLER
 * Все HTTP-запросы проходят через этот файл (через .htaccess)
 */

// ROOT — корень проекта (папка выше public_html)
define('ROOT', dirname(__DIR__));

session_start();

// Подключаем конфиг БД, константы и хелперы
require_once ROOT . '/config/db.php';
require_once ROOT . '/config/config.php';
require_once ROOT . '/includes/helpers.php';

// Автозагрузка контроллеров и моделей
spl_autoload_register(function($class) {
    $paths = array(
        ROOT . '/controllers/' . $class . '.php',
        ROOT . '/models/'      . $class . '.php',
    );
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

// ===== ROUTER =====
$uri    = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
$uri    = strtok($uri, '?');           // убираем строку запроса
$uri    = '/' . trim($uri, '/');       // нормализуем слэши
$method = $_SERVER['REQUEST_METHOD'];
$key    = $method . ' ' . $uri;

// Таблица маршрутов: 'МЕТОД /путь' => ['Контроллер', 'метод']
$routes = array(
    // Главная
    'GET /'                   => array('InventoryController', 'index'),

    // Авторизация
    'GET /login'              => array('AuthController', 'loginForm'),
    'POST /login'             => array('AuthController', 'login'),
    'GET /register'           => array('AuthController', 'registerForm'),
    'POST /register'          => array('AuthController', 'register'),
    'GET /logout'             => array('AuthController', 'logout'),

    // Профиль / пароль
    'GET /profile'            => array('ProfileController', 'index'),
    'GET /change-password'    => array('ProfileController', 'changePasswordForm'),
    'POST /change-password'   => array('ProfileController', 'changePassword'),

    // Аренда
    'GET /rent'               => array('RentController', 'rentForm'),
    'POST /rent'              => array('RentController', 'rent'),
    'GET /return'             => array('RentController', 'returnForm'),
    'POST /return'            => array('RentController', 'doReturn'),
    'GET /edit-rent'          => array('RentController', 'editForm'),
    'POST /edit-rent'         => array('RentController', 'editRent'),

    // Админ
    'GET /admin'              => array('AdminController', 'panel'),
    'GET /admin/rentals'      => array('AdminController', 'rentals'),
    'POST /admin/rollback'    => array('AdminController', 'rollback'),
    'GET /admin/add-item'     => array('AdminController', 'addItemForm'),
    'POST /admin/add-item'    => array('AdminController', 'addItem'),
    'GET /admin/edit-item'    => array('AdminController', 'editItemForm'),
    'POST /admin/edit-item'   => array('AdminController', 'editItem'),
    'POST /admin/delete-item' => array('AdminController', 'deleteItem'),

    // Сидер
    'GET /admin/seeder'   => array('AdminSeederController', 'index'),
    'POST /admin/seeder'  => array('AdminSeederController', 'handle'),
);

if (isset($routes[$key])) {
    list($controllerName, $action) = $routes[$key];
    $controller = new $controllerName($pdo);
    $controller->$action();
} else {
    http_response_code(404);
    echo '<h1>404 — Страница не найдена</h1><a href="/">На главную</a>';
}