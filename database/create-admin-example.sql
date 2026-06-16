-- NÃO execute sem trocar o hash.
-- Gere o hash com:
-- php -r "echo password_hash('SUA_SENHA_FORTE', PASSWORD_DEFAULT), PHP_EOL;"
-- Depois cole o hash abaixo:

-- INSERT INTO admin_users (name, email, password_hash, role)
-- VALUES ('Administrador', 'admin@quantocustasolar.com.br', 'COLE_O_HASH_AQUI', 'admin');
