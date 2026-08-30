<?php
// GET /api/entities.php — read-only export of the live entities table, for
// external tools (the research agent) to keep their own matching knowledge
// in sync with what actually exists on the site, instead of drifting apart
// from a hand-maintained copy. Auth: same bearer-token scheme as
// api/ingest.php (see includes/functions.php's require_api_token()) — this
// endpoint is read-only, but the entities table is still not something to
// leave open to the whole internet.
//
// Returns every entity's id/type/name/slug/one_line_id plus its aliases and
// keywords (both JSON columns, decoded here) — exactly the fields a
// matcher needs, nothing else (no bio_markdown; that's editorial content,
// not matching data, and this endpoint has no reason to expose it).

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['error' => 'GET only'], 405);
}

$pdo = db();
require_api_token($pdo);

$rows = $pdo->query('SELECT id, type, name, slug, one_line_id, aliases, keywords FROM entities ORDER BY id')->fetchAll();

$entities = array_map(function (array $row): array {
    return [
        'id' => (int)$row['id'],
        'type' => $row['type'],
        'name' => $row['name'],
        'slug' => $row['slug'],
        'one_line_id' => $row['one_line_id'],
        'aliases' => json_decode($row['aliases'] ?: '[]', true) ?: [],
        'keywords' => json_decode($row['keywords'] ?: '[]', true) ?: [],
    ];
}, $rows);

json_response(['entities' => $entities]);
