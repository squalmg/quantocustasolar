<?php
header('Content-Type: application/json; charset=utf-8');

$result = array(
    'ok' => true,
    'service' => 'Quanto Custa Solar API health',
    'php_version' => PHP_VERSION,
    'php_supported' => version_compare(PHP_VERSION, '7.4.0', '>='),
    'pdo_loaded' => extension_loaded('pdo'),
    'pdo_mysql_loaded' => extension_loaded('pdo_mysql'),
    'config_file_exists' => file_exists(__DIR__ . '/../config/config.php'),
    'errors' => array(),
);

if (!$result['php_supported']) {
    $result['ok'] = false;
    $result['errors'][] = 'PHP abaixo de 7.4. Use PHP 8.2 na hospedagem.';
}

if (!$result['pdo_loaded'] || !$result['pdo_mysql_loaded']) {
    $result['ok'] = false;
    $result['errors'][] = 'PDO MySQL não habilitado.';
}

if (!$result['config_file_exists']) {
    $result['ok'] = false;
    $result['errors'][] = 'Arquivo config/config.php não encontrado.';
}

http_response_code($result['ok'] ? 200 : 500);
echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
