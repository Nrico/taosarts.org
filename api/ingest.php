<?php
// POST /api/ingest.php — receives story-idea packages from the research agent.
// Auth: Authorization: Bearer <token>, checked against hashed tokens in
// api_tokens (see scripts/create_api_token.php to mint one). Never writes
// story copy and never auto-creates entities — it only ever inserts rows
// into story_ideas for a human to review in /admin/ideas.php.

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'POST only'], 405);
}

$pdo = db();
require_api_token($pdo);

// ---- body -----------------------------------------------------------

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!is_array($data) || !isset($data['ideas']) || !is_array($data['ideas'])) {
    json_response(['error' => 'body must be a JSON object with an "ideas" array'], 400);
}

$batchId = isset($data['batch_id']) && is_string($data['batch_id']) ? $data['batch_id'] : null;
$ideas = $data['ideas'];

function validation_error(int $index, string $reason, array &$errors): void {
    $errors[] = ['index' => $index, 'reason' => $reason];
}

$errors = [];
$inserted = 0;

$insertStmt = $pdo->prepare(
    'INSERT INTO story_ideas
        (batch_id, spark_url, spark_source, spark_snippet, detected_via,
         matched_entity_id, proposed_new_entity, connection_note, draft_markdown,
         suggested_images, suggested_links, status)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "new")'
);
$entityLookup = $pdo->prepare('SELECT id FROM entities WHERE slug = ? LIMIT 1');

foreach ($ideas as $index => $idea) {
    if (!is_array($idea)) {
        validation_error($index, 'idea must be an object', $errors);
        continue;
    }

    // spark
    $spark = $idea['spark'] ?? null;
    if (!is_array($spark) || empty($spark['url']) || !is_string($spark['url']) || empty($spark['source']) || !is_string($spark['source'])) {
        validation_error($index, 'spark.url and spark.source are required', $errors);
        continue;
    }
    $sparkUrl = $spark['url'];
    $sparkSource = $spark['source'];
    $sparkSnippet = isset($spark['snippet']) && is_string($spark['snippet']) ? $spark['snippet'] : null;
    $detectedVia = isset($spark['detected_via']) && is_string($spark['detected_via']) ? $spark['detected_via'] : null;

    // matched_entity / proposed_new_entity
    $matched = $idea['matched_entity'] ?? null;
    if (!is_array($matched) || !array_key_exists('is_new', $matched)) {
        validation_error($index, 'matched_entity.is_new is required', $errors);
        continue;
    }

    $matchedEntityId = null;
    $proposedNewEntityJson = null;

    if ($matched['is_new'] === true) {
        $proposed = $idea['proposed_new_entity'] ?? null;
        if (!is_array($proposed) || empty($proposed['type']) || !in_array($proposed['type'], ['person', 'place', 'tradition'], true) || empty($proposed['name']) || !is_string($proposed['name'])) {
            validation_error($index, 'proposed_new_entity.type (person|place|tradition) and .name are required when matched_entity.is_new is true', $errors);
            continue;
        }
        // Stored as-is, verbatim — never auto-created. An admin approves it
        // into a real entities row from /admin/idea.php.
        $proposedNewEntityJson = json_encode($proposed, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    } else {
        if (empty($matched['slug']) || !is_string($matched['slug'])) {
            validation_error($index, 'matched_entity.slug is required when matched_entity.is_new is false', $errors);
            continue;
        }
        $entityLookup->execute([$matched['slug']]);
        $row = $entityLookup->fetch();
        if (!$row) {
            validation_error($index, 'matched_entity.slug not found: ' . $matched['slug'], $errors);
            continue;
        }
        $matchedEntityId = $row['id'];
    }

    $connectionNote = isset($idea['connection_note']) && is_string($idea['connection_note']) ? $idea['connection_note'] : null;

    // draft_markdown — optional; a research agent may submit a full draft
    // alongside the idea, but plenty of legitimate callers never will.
    $draftMarkdown = isset($idea['draft_markdown']) && is_string($idea['draft_markdown']) ? $idea['draft_markdown'] : null;

    // suggested_images — every entry must already carry full credit
    // metadata; this is where that requirement starts, not just at
    // publish time.
    $suggestedImages = $idea['suggested_images'] ?? [];
    if (!is_array($suggestedImages)) {
        validation_error($index, 'suggested_images must be an array', $errors);
        continue;
    }
    $imageError = null;
    foreach ($suggestedImages as $imgIndex => $img) {
        if (!is_array($img) || empty($img['url']) || empty($img['source']) || empty($img['license']) || empty($img['credit_text'])) {
            $imageError = "suggested_images[$imgIndex] is missing one of url/source/license/credit_text";
            break;
        }
    }
    if ($imageError) {
        validation_error($index, $imageError, $errors);
        continue;
    }

    // suggested_links — url required, rest optional
    $suggestedLinks = $idea['suggested_links'] ?? [];
    if (!is_array($suggestedLinks)) {
        validation_error($index, 'suggested_links must be an array', $errors);
        continue;
    }
    $linkError = null;
    foreach ($suggestedLinks as $linkIndex => $link) {
        if (!is_array($link) || empty($link['url'])) {
            $linkError = "suggested_links[$linkIndex] is missing url";
            break;
        }
    }
    if ($linkError) {
        validation_error($index, $linkError, $errors);
        continue;
    }

    try {
        $insertStmt->execute([
            $batchId,
            $sparkUrl,
            $sparkSource,
            $sparkSnippet,
            $detectedVia,
            $matchedEntityId,
            $proposedNewEntityJson,
            $connectionNote,
            $draftMarkdown,
            json_encode($suggestedImages, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            json_encode($suggestedLinks, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ]);
        $inserted++;
    } catch (Throwable $e) {
        error_log('ingest.php insert failed: ' . $e->getMessage());
        validation_error($index, 'internal error while inserting', $errors);
    }
}

json_response([
    'received' => count($ideas),
    'inserted' => $inserted,
    'errors' => $errors,
]);
