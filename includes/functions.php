<?php
function h($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function slugify(string $text): string {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return $text ?: 'item-' . time();
}
function redirect(string $path): void { header('Location: ' . $path); exit; }
function format_meeting_datetime(?string $value): string {
    if (!$value) return '';
    return date('M j, Y \a\t g:i a', strtotime($value));
}
function format_datetime_local(?string $value): string {
    if (!$value) return '';
    return date('Y-m-d\TH:i', strtotime($value));
}
function upload_image(array $file, string $folder): ?string {
    if (empty($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) return null;
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    $type = mime_content_type($file['tmp_name']);
    if (!isset($allowed[$type])) return null;
    $name = bin2hex(random_bytes(8)) . '.' . $allowed[$type];
    $targetDir = __DIR__ . '/../uploads/' . $folder;
    if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
    $target = $targetDir . '/' . $name;
    move_uploaded_file($file['tmp_name'], $target);
    return 'uploads/' . $folder . '/' . $name;
}
