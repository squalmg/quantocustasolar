<?php
require_once __DIR__ . '/../includes/db.php';
try {
    $pdo = db();
    $state = normalize_state($_GET['state'] ?? 'GO');
    $settings = get_simulator_settings($pdo, $state);
    json_response(['ok' => true, 'settings' => $settings]);
} catch (Throwable $e) {
    $state = normalize_state($_GET['state'] ?? 'GO');
    json_response(['ok' => true, 'settings' => default_settings_for_state($state), 'fallback' => true]);
}
