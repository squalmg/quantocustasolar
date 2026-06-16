<?php
// Uso via terminal: php tools/generate-admin-hash.php 'SUA_SENHA_FORTE'
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Execute este arquivo somente via terminal.');
}
$password = $argv[1] ?? '';
if (strlen($password) < 10) {
    fwrite(STDERR, "Informe uma senha com pelo menos 10 caracteres.\n");
    exit(1);
}
echo password_hash($password, PASSWORD_DEFAULT) . PHP_EOL;
