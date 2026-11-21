<?php
// ===== PHP-часть: подготовка данных =====
$uploadDir = __DIR__ . '/uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Задание 6: ограничения из php.ini
$maxFile = ini_get('upload_max_filesize');
$maxPost = ini_get('post_max_size');

// Сообщения, которые передают upload.php и delete.php
$msg = $_GET['msg'] ?? '';
$error = $_GET['error'] ?? '';

// Задание 4: функция formatSize()
function formatSize(int $bytes): string {
    $units = ['Б', 'КБ', 'МБ', 'ГБ'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 1) . ' ' . $units[$i];
}

// Задание 4: список файлов (скрытые файлы вроде .gitkeep не показываем)
$files = array_values(array_filter(
    array_diff(scandir($uploadDir), ['.', '..']),
    fn($f) => is_file($uploadDir . $f) && $f[0] !== '.'
));

// Доп. задание 2: сортировка ?sort=name|size|date
$sort = $_GET['sort'] ?? 'name';
usort($files, function ($a, $b) use ($uploadDir, $sort) {
    return match ($sort) {
        'size' => filesize($uploadDir . $a) <=> filesize($uploadDir . $b),
        'date' => filemtime($uploadDir . $b) <=> filemtime($uploadDir . $a),
        default => strcasecmp($a, $b),
    };
});

// Итоги
$totalSize = 0;
foreach ($files as $f) {
    $totalSize += filesize($uploadDir . $f);
}

// Доп. задание 4: последние 10 записей журнала
$logFile = __DIR__ . '/log.txt';
$logLines = is_file($logFile) ? file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
$logLines = array_reverse(array_slice($logLines, -10));
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Файловый менеджер</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <header>
        <h1>Файловый менеджер</h1>
        <p class="subtitle">Практическая работа: работа с файлами в PHP</p>
    </header>

    <!-- Сообщения об успехе и ошибках -->
    <?php if ($msg): ?>
        <div class="alert success"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Форма загрузки -->
    <section class="card">
        <h2>Загрузка файла</h2>
        <div class="limits">
            Ограничения сервера: один файл — до <b><?= $maxFile ?></b>,
            весь запрос — до <b><?= $maxPost ?></b>
        </div>
        <form action="upload.php" method="post" enctype="multipart/form-data" class="upload-form">
            <input type="file" name="userfile" required>
            <button type="submit">Загрузить</button>
        </form>
        <p class="hint">Разрешены: jpg, jpeg, png, gif, pdf, txt, docx, xlsx, zip. Не более 5 МБ.</p>
    </section>

    <!-- Список файлов -->
    <section class="card">
        <h2>Загруженные файлы</h2>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>№</th>
                    <th>Превью</th>
                    <th><a href="?sort=name">Имя файла</a></th>
                    <th><a href="?sort=size">Размер</a></th>
                    <th><a href="?sort=date">Дата загрузки</a></th>
                    <th>Действие</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($files)): ?>
                    <tr><td colspan="6" class="empty">Файлов пока нет</td></tr>
                <?php else: ?>
                    <?php foreach ($files as $i => $name): ?>
                        <?php
                        $path = $uploadDir . $name;
                        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                        ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td>
                                <?php if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])): ?>
                                    <img class="thumb" src="uploads/<?= rawurlencode($name) ?>" alt="">
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="uploads/<?= rawurlencode($name) ?>" download>
                                    <?= htmlspecialchars($name) ?>
                                </a>
                            </td>
                            <td><?= formatSize(filesize($path)) ?></td>
                            <td><?= date('d.m.Y H:i', filemtime($path)) ?></td>
                            <td>
                                <a class="btn-delete"
                                   href="delete.php?file=<?= urlencode($name) ?>"
                                   onclick="return confirm('Удалить этот файл?')">Удалить</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <p class="total">
            Всего файлов: <?= count($files) ?>, общий размер: <?= formatSize($totalSize) ?>
        </p>
    </section>

    <!-- Журнал -->
    <section class="card">
        <h2>Журнал (последние 10 записей)</h2>
        <?php if (empty($logLines)): ?>
            <p class="hint">Записей пока нет</p>
        <?php else: ?>
            <ul class="log">
                <?php foreach ($logLines as $line): ?>
                    <li><?= htmlspecialchars($line) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <footer>Выполнил(а): ____________________ · Группа: ИС-281</footer>
</div>
</body>
</html>
