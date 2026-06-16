<?php
require_once __DIR__ . '/helpers.php';
app_bootstrap();

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    return $pdo;
}

function get_simulator_settings(PDO $pdo, string $state): array {
    $state = normalize_state($state);
    $stmt = $pdo->prepare('SELECT * FROM simulator_settings WHERE state = ? LIMIT 1');
    $stmt->execute([$state]);
    $settings = $stmt->fetch();
    if (!$settings) {
        $stmt = $pdo->prepare('SELECT * FROM simulator_settings WHERE state = ? LIMIT 1');
        $stmt->execute(['BR']);
        $settings = $stmt->fetch();
    }
    return $settings ?: default_settings_for_state($state);
}
