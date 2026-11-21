<?php
// delete.php?file=report.pdf
$name = basename($_GET['file'] ?? ''); // basename не даст выйти из папки через ../
$path = __DIR__ . '/uploads/' . $name;

if ($name !== '' && is_file($path)) {
    unlink($path);
    file_put_contents(__DIR__ . '/log.txt', date('d.m.Y H:i:s') . " — удалён: $name\n", FILE_APPEND);
    header('Location: index.php?msg=' . urlencode('Файл удалён'));
} else {
    header('Location: index.php?error=' . urlencode('Файл не найден'));
}
exit;
