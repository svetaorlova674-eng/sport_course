<?php
header('Content-Type: text/plain; charset=UTF-8');
session_start();

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    die('Доступ запрещен');
}

$uploadDir = __DIR__ . '/../uploads/';
$maxFileSize = 5 * 1024 * 1024; // 5 MB
$allowedMimeTypes = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/gif'  => 'gif',
];

// Создаём папку, если нет
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        exit('Не удалось создать папку для загрузки');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {

    $file = $_FILES['file'];

    // Проверка ошибок
    if ($file['error'] !== UPLOAD_ERR_OK) {
        die('Ошибка загрузки файла: ' . $file['error']);
    }

    if ($file['size'] > $maxFileSize) {
        die('Файл слишком большой (макс. 5 МБ)');
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!isset($allowedMimeTypes[$mime])) {
        die('Разрешены только JPG, PNG или GIF');
    }

    if (!getimagesize($file['tmp_name'])) {
        die('Файл не является изображением');
    }

    $ext = $allowedMimeTypes[$mime];
    $filename = uniqid('item_') . '.' . $ext;

    if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
        die('Не удалось сохранить файл');
    }

    // Возвращаем путь к файлу, чтобы add_item.php мог его использовать
    echo 'uploads/' . $filename;

} else {
    exit('Неверный запрос или отсутствует файл');
}
