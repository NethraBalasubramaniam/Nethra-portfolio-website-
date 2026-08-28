<?php
// Public read-only endpoint — the homepage/work-page fetch this directly,
// no login required. Only GET is served.
require __DIR__ . '/../admin/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
echo json_encode(db_list_cases(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
