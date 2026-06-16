<?php
require_once __DIR__ . '/helpers.php';

function secure_session_start(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function csrf_token(): string {
    secure_session_start();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function verify_csrf(): void {
    secure_session_start();
    $token = $_POST['csrf'] ?? '';
    if (!$token || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $token)) {
        http_response_code(419);
        exit('Sessão expirada. Volte e tente novamente.');
    }
}

function rate_limit(PDO $pdo, string $action, int $maxAttempts = 8, int $windowMinutes = 10): bool {
    $hash = ip_hash();
    $now = new DateTimeImmutable('now');
    $windowStart = $now->modify('-' . $windowMinutes . ' minutes')->format('Y-m-d H:i:s');

    $stmt = $pdo->prepare('DELETE FROM rate_limits WHERE window_start < ?');
    $stmt->execute([$windowStart]);

    $stmt = $pdo->prepare('SELECT * FROM rate_limits WHERE ip_hash = ? AND action = ? LIMIT 1');
    $stmt->execute([$hash, $action]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        $stmt = $pdo->prepare('INSERT INTO rate_limits (ip_hash, action, attempts, window_start) VALUES (?, ?, 1, NOW())');
        $stmt->execute([$hash, $action]);
        return true;
    }

    if ((int)$row['attempts'] >= $maxAttempts) {
        return false;
    }

    $stmt = $pdo->prepare('UPDATE rate_limits SET attempts = attempts + 1, updated_at = NOW() WHERE id = ?');
    $stmt->execute([$row['id']]);
    return true;
}
