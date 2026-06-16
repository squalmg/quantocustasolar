<?php
header('Content-Type: application/json; charset=utf-8');

$configPath = __DIR__ . '/../config/config.php';
$exampleConfigPath = __DIR__ . '/../config/config.example.php';

$result = array(
    'ok' => true,
    'service' => 'Quanto Custa Solar API health',
    'php_version' => PHP_VERSION,
    'php_supported' => version_compare(PHP_VERSION, '7.4.0', '>='),
    'pdo_loaded' => extension_loaded('pdo'),
    'pdo_mysql_loaded' => extension_loaded('pdo_mysql'),
    'config_file_exists' => file_exists($configPath),
    'config_example_exists' => file_exists($exampleConfigPath),
    'paths' => array(
        'root' => dirname(__DIR__),
        'api' => __DIR__,
        'config' => $configPath,
    ),
    'warnings' => array(),
    'errors' => array(),
);

if (!$result['php_supported']) {
    $result['ok'] = false;
    $result['errors'][] = 'PHP abaixo de 7.4. Use PHP 8.2 na hospedagem.';
}

if (!$result['pdo_loaded']) {
    $result['ok'] = false;
    $result['errors'][] = 'Extensão PDO não habilitada.';
}

if (!$result['pdo_mysql_loaded']) {
    $result['ok'] = false;
    $result['errors'][] = 'Extensão pdo_mysql não habilitada.';
}

if (!$result['config_file_exists']) {
    $result['ok'] = false;
    $result['errors'][] = 'Arquivo config/config.php não encontrado.';
}

// Não retorna HTTP 500 de propósito: diagnóstico precisa abrir mesmo quando algo estiver errado.
http_response_code(200);
echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
