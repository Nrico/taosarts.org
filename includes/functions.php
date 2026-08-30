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
function json_response(array $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function client_ip(): string {
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

// ---- Public front-end helpers -------------------------------------------

function story_primary_image(PDO $pdo, int $storyId): ?array {
    $stmt = $pdo->prepare(
        'SELECT i.* FROM story_images si JOIN images i ON i.id = si.image_id
         WHERE si.story_id = ? ORDER BY si.sort_order ASC, si.image_id ASC LIMIT 1'
    );
    $stmt->execute([$storyId]);
    return $stmt->fetch() ?: null;
}

function story_related_entities(PDO $pdo, int $storyId): array {
    $stmt = $pdo->prepare(
        'SELECT e.name, e.slug, e.type FROM story_entities se JOIN entities e ON e.id = se.entity_id
         WHERE se.story_id = ? ORDER BY e.type, e.name'
    );
    $stmt->execute([$storyId]);
    return $stmt->fetchAll();
}

// The card grid (and story kicker) color-codes by entity type. That needs an
// actual primary topic, not a guess — stories.primary_entity_id is set
// explicitly by whoever writes the story (admin/editor.php). Falls back to
// the first tagged entity for older rows that predate the field.
function story_primary_entity(PDO $pdo, array $story, ?array $fallbackEntities = null): ?array {
    if (!empty($story['primary_entity_id'])) {
        $stmt = $pdo->prepare('SELECT name, slug, type FROM entities WHERE id = ?');
        $stmt->execute([$story['primary_entity_id']]);
        if ($row = $stmt->fetch()) return $row;
    }
    $entities = $fallbackEntities ?? story_related_entities($pdo, $story['id']);
    return $entities[0] ?? null;
}

function format_published_date(?string $value): string {
    if (!$value) return '';
    return strtoupper(date('M Y', strtotime($value)));
}

// CSS object-position value for an images row's focal point, for use
// anywhere an <img> is cropped with object-fit:cover.
function image_object_position(array $image): string {
    $x = $image['focal_x'] ?? 50;
    $y = $image['focal_y'] ?? 50;
    return (int)$x . '% ' . (int)$y . '%';
}

// Renders one card for the homepage/archive grid. $story needs at least
// id, title, slug, dek, published_at, primary_entity_id.
function render_story_card(PDO $pdo, array $story): string {
    $image = story_primary_image($pdo, $story['id']);
    $entities = story_related_entities($pdo, $story['id']);
    $primary = story_primary_entity($pdo, $story, $entities);
    $tagsByType = [];
    foreach ($entities as $e) {
        $tagsByType[$e['type']][] = '<a href="entity.php?slug=' . h($e['slug']) . '">' . h($e['name']) . '</a>';
    }

    ob_start();
    ?>
    <div class="card">
      <div class="card-image">
        <?php if ($image): ?><img src="<?= h($image['url']) ?>" alt="" loading="lazy" style="object-position:<?= h(image_object_position($image)) ?>"><?php endif; ?>
        <?php if ($primary): ?><span class="type-tag <?= h($primary['type']) ?>"><?= h($primary['type']) ?></span><?php endif; ?>
      </div>
      <div class="card-body">
        <div class="card-title"><a href="story.php?slug=<?= h($story['slug']) ?>"><?= h($story['title']) ?></a></div>
        <div class="card-dek"><?= h($story['dek']) ?></div>
        <?php if ($tagsByType):
          $metaParts = [];
          foreach ($tagsByType as $type => $links) $metaParts[] = h(entity_type_label_plural($type)) . ': ' . implode(', ', $links);
        ?>
        <div class="card-meta"><?= implode(' &middot; ', $metaParts) ?></div>
        <?php endif; ?>
      </div>
    </div>
    <?php
    return ob_get_clean();
}

function entity_type_label(string $type): string {
    return ucfirst($type);
}

// What's worth showing at a glance differs by type — an era for a person, a
// cadence for a tradition. Coordinates are precise but not a great glance-read
// on a card, so places show nothing extra there (they get it on their own page).
function entity_meta_line(array $e): ?string {
    if ($e['type'] === 'person' && ($e['date_start'] || $e['date_end'])) {
        return $e['date_start'] ? $e['date_start'] . ' – ' . ($e['date_end'] ?: 'present') : $e['date_end'];
    }
    if ($e['type'] === 'tradition' && $e['cadence']) {
        return $e['cadence'];
    }
    return null;
}

function entity_type_label_plural(string $type): string {
    return $type === 'person' ? 'PEOPLE' : strtoupper($type) . 'S';
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

// Single entry point for creating an images row, whether the source is a
// pasted URL or an uploaded file — used by admin/images.php, the editor's
// inline "add image" widget, and entity_edit.php's portrait registration, so
// there's exactly one place that decides what a valid image record is.
// Returns the new image id, or null if there was nothing usable to save
// (no URL/file, or no credit text — credit_text is NOT NULL for a reason).
function create_image(PDO $pdo, array $data, ?array $uploadedFile = null): ?int {
    $creditText = trim($data['credit_text'] ?? '');
    if (!$creditText) return null;

    $url = null;
    if ($uploadedFile && !empty($uploadedFile['tmp_name']) && $uploadedFile['error'] === UPLOAD_ERR_OK) {
        $path = upload_image($uploadedFile, 'images');
        if ($path) $url = rtrim(BASE_URL, '/') . '/' . $path;
    }
    if (!$url) $url = trim($data['url'] ?? '');
    if (!$url) return null;

    $stmt = $pdo->prepare('INSERT INTO images (url, thumbnail_url, source, license, credit_text, original_url) VALUES (?,?,?,?,?,?)');
    $stmt->execute([
        $url,
        trim($data['thumbnail_url'] ?? '') ?: $url,
        trim($data['source'] ?? '') ?: 'Taos Arts',
        trim($data['license'] ?? '') ?: 'All rights reserved',
        $creditText,
        trim($data['original_url'] ?? '') ?: null,
    ]);
    return (int)$pdo->lastInsertId();
}
