<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/security.php';

function current_admin(): ?array {
    secure_session_start();
    return $_SESSION['admin_user'] ?? null;
}

function require_admin(): array {
    $admin = current_admin();
    if (!$admin) {
        header('Location: /admin/login.php');
        exit;
    }
    return $admin;
}

function attempt_login(string $email, string $password): bool {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM admin_users WHERE email = ? LIMIT 1');
    $stmt->execute([strtolower(trim($email))]);
    $user = $stmt->fetch();
    if (!$user || !password_verify($password, $user['password_hash'])) {
        return false;
    }
    secure_session_start();
    session_regenerate_id(true);
    $_SESSION['admin_user'] = [
        'id' => (int)$user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role'],
    ];
    $stmt = $pdo->prepare('UPDATE admin_users SET last_login_at = NOW() WHERE id = ?');
    $stmt->execute([$user['id']]);
    return true;
}

function admin_logout(): void {
    secure_session_start();
    $_SESSION = [];
    session_destroy();
}

function log_activity(?int $adminUserId, string $action, string $entityType = '', string $entityId = '', string $description = ''): void {
    try {
        $pdo = db();
        $stmt = $pdo->prepare('INSERT INTO activity_logs (admin_user_id, action, entity_type, entity_id, description) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$adminUserId, $action, $entityType, $entityId, $description]);
    } catch (Throwable $e) {
        // Logs não devem quebrar ações administrativas.
    }
}
