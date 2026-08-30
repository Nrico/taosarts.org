<?php
// CLI-only: php scripts/create_api_token.php "label for this token"
// Prints the plaintext token once. Only the hash is stored — if you lose it,
// make a new one (and revoke the old row from admin/tokens.php).

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("This script only runs from the command line.\n");
}

require_once __DIR__ . '/../includes/db.php';

$label = $argv[1] ?? 'unlabeled';
$token = bin2hex(random_bytes(32));
$hash = password_hash($token, PASSWORD_DEFAULT);

db()->prepare('INSERT INTO api_tokens (token_hash, label) VALUES (?, ?)')->execute([$hash, $label]);

echo "Token created for \"$label\".\n";
echo "Copy it now — it will not be shown again:\n\n";
echo "  $token\n\n";
echo "Use it as: Authorization: Bearer $token\n";
