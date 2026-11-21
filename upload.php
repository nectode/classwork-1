<?php
$uploadDir = __DIR__ . '/uploads/';
$logFile = __DIR__ . '/log.txt';

// Вернуться на главную с сообщением или ошибкой
function back(string $param, string $text): void {
    header('Location: index.php?' . $param . '=' . urlencode($text));
    exit;
}

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    back('error', 'Используйте форму загрузки');
}

// Задание 3: превышение post_max_size (массивы $_POST и $_FILES пусты)
if (empty($_FILES) && ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
    back('error', 'Запрос больше post_max_size (' . ini_get('post_max_size') . ')');
}

$file = $_FILES['userfile'] ?? null;
if ($file === null) {
    back('error', 'Файл не получен');
}

// Задание 3: проверка кода ошибки
switch ($file['error']) {
    case UPLOAD_ERR_OK:
        break;
    case UPLOAD_ERR_NO_FILE:
        back('error', 'Файл не выбран');
    case UPLOAD_ERR_INI_SIZE:
        back('error', 'Файл больше upload_max_filesize (' . ini_get('upload_max_filesize') . ')');
    case UPLOAD_ERR_FORM_SIZE:
        back('error', 'Файл больше MAX_FILE_SIZE из формы');
    case UPLOAD_ERR_PARTIAL:
        back('error', 'Файл загружен частично');
    case UPLOAD_ERR_NO_TMP_DIR:
        back('error', 'На сервере нет временной папки');
    case UPLOAD_ERR_CANT_WRITE:
        back('error', 'Не удалось записать файл на диск');
    case UPLOAD_ERR_EXTENSION:
        back('error', 'Загрузку остановило расширение PHP');
    default:
        back('error', 'Ошибка загрузки, код: ' . $file['error']);
}

// Задание 3: размер и расширение
$maxSize = 5 * 1024 * 1024; // 5 МБ
$allowed = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'txt', 'docx', 'xlsx', 'zip'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if ($file['size'] > $maxSize) {
    back('error', 'Файл больше 5 МБ');
}
if (!in_array($ext, $allowed, true)) {
    back('error', 'Недопустимый тип файла: .' . $ext);
}

// Задание 2: безопасное имя
$name = basename($file['name']);
$name = preg_replace('/[^\w.\-]/u', '_', $name) ?? 'file.' . $ext;
$name = ltrim($name, '.');
if ($name === '') {
    $name = 'file.' . $ext;
}

// Если файл с таким именем уже есть — добавляем метку времени
if (file_exists($uploadDir . $name)) {
    $name = time() . '_' . $name;
}

if (move_uploaded_file($file['tmp_name'], $uploadDir . $name)) {
    // Доп. задание 4: запись в журнал
    file_put_contents($logFile, date('d.m.Y H:i:s') . " — загружен: $name\n", FILE_APPEND);
    back('msg', 'Файл загружен');
} else {
    back('error', 'Не удалось сохранить файл');
}
