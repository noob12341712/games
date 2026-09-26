<?php
// suggest.php — receives game suggestions and saves to suggestions.json

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

$name     = trim($_POST['name']     ?? '');
$reason   = trim($_POST['reason']   ?? '');
$username = trim($_POST['username'] ?? '');

if ($name === '' || $username === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Name and game name are required']);
    exit;
}

// Sanitise
$name     = htmlspecialchars($name,     ENT_QUOTES, 'UTF-8');
$reason   = htmlspecialchars($reason,   ENT_QUOTES, 'UTF-8');
$username = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');

// Enforce length limits
$name     = mb_substr($name,     0, 80);
$reason   = mb_substr($reason,   0, 300);
$username = mb_substr($username, 0, 40);

$file = __DIR__ . '/suggestions.json';

// Load existing
$suggestions = [];
if (file_exists($file)) {
    $raw = file_get_contents($file);
    $suggestions = json_decode($raw, true) ?: [];
}

// Prepend new entry
array_unshift($suggestions, [
    'id'       => uniqid('s_', true),
    'username' => $username,
    'name'     => $name,
    'reason'   => $reason,
    'date'     => date('Y-m-d H:i:s'),
    'ip'       => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
]);

// Save (keep last 200)
$suggestions = array_slice($suggestions, 0, 200);
file_put_contents($file, json_encode($suggestions, JSON_PRETTY_PRINT));

echo json_encode(['ok' => true]);
