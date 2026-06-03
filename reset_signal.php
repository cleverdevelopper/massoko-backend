<?php
// Load env
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos($line, '=') !== false && $line[0] !== '#') {
            [$key, $val] = explode('=', $line, 2);
            putenv(trim($key) . '=' . trim($val));
        }
    }
}

$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbName = getenv('DB_NAME') ?: 'massoko';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: '';

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Delete all prekeys (used and unused)
    $pdo->exec('DELETE FROM user_prekeys');
    echo "user_prekeys: cleared\n";

    // Delete all identity/signal keys
    $pdo->exec('DELETE FROM user_keys');
    echo "user_keys: cleared\n";

    // Delete all messages (they're encrypted with old sessions)
    $pdo->exec('DELETE FROM messages');
    echo "messages: cleared\n";

    // Reset conversation last_message info
    $pdo->exec("UPDATE conversations SET last_message_id = NULL, last_message_at = NULL");
    echo "conversations: reset\n";

    echo "\nDone! Signal keys and messages cleared. Both devices will regenerate keys on next app start.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
