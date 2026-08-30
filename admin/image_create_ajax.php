<?php
// Backs the "+ Add image" widget in the story editor's sidebar. Exists only
// so adding an image mid-draft doesn't require a page reload — which would
// throw away whatever the writer hasn't saved yet.
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
if (!current_user()) json_response(['error' => 'Not logged in'], 401);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['error' => 'POST only'], 405);

$pdo = db();
$id = create_image($pdo, $_POST, $_FILES['image_file'] ?? null);
if (!$id) {
    json_response(['error' => 'Need either an uploaded file or a URL, plus credit text.'], 400);
}

$stmt = $pdo->prepare('SELECT * FROM images WHERE id = ?');
$stmt->execute([$id]);
$img = $stmt->fetch();
json_response(['id' => $img['id'], 'url' => $img['url'], 'thumbnail_url' => $img['thumbnail_url'], 'credit_text' => $img['credit_text']]);
