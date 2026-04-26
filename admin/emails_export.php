<?php require_once __DIR__ . '/../includes/auth.php'; require_once __DIR__ . '/../includes/db.php'; require_login();
header('Content-Type: text/csv'); header('Content-Disposition: attachment; filename="taosarts-newsletter-signups.csv"');
$out=fopen('php://output','w'); fputcsv($out,['email','source','created_at']);
foreach(db()->query('SELECT email,source,created_at FROM newsletter_signups ORDER BY created_at DESC') as $row) fputcsv($out,$row);
