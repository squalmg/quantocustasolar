# Deploy na KingHost via Git

## 1. Criar banco MySQL

No painel da KingHost:

1. Crie o banco MySQL.
2. Anote host, nome do banco, usuário e senha.
3. Acesse o phpMyAdmin.
4. Importe, nesta ordem:

```txt
database/schema-mysql.sql
database/seed-settings.sql
```

## 2. Configurar PHP

No painel da hospedagem, selecione PHP 8.2 ou superior.

## 3. Configurar arquivos

Edite:

```txt
config/config.php
```

Preencha:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'nome_real_do_banco');
define('DB_USER', 'usuario_real');
define('DB_PASS', 'senha_real');
define('APP_KEY', 'gere-uma-chave-aleatoria-forte');
define('SITE_URL', 'https://quantocustasolar.com.br');
define('DEFAULT_WHATSAPP_E164', '5564999999999');
```

## 4. Enviar via Git

Se a pasta pública estiver vazia:

```bash
cd /home/SEU_USUARIO/www
git clone https://github.com/squalmg/quantocustasolar.git .
```

Se a pasta já estiver conectada ao repositório:

```bash
cd /home/SEU_USUARIO/www
git pull origin main
```

Se a KingHost usar outra pasta pública para o domínio, entre nela antes de rodar o clone/pull.

## 5. Criar usuário admin

No terminal local ou em qualquer ambiente com PHP, gere o hash:

```bash
php -r "echo password_hash('SUA_SENHA_FORTE', PASSWORD_DEFAULT), PHP_EOL;"
```

No phpMyAdmin, rode:

```sql
INSERT INTO admin_users (name, email, password_hash, role)
VALUES ('Administrador', 'admin@quantocustasolar.com.br', 'COLE_O_HASH_AQUI', 'admin');
```

Depois acesse:

```txt
https://quantocustasolar.com.br/admin/login.php
```

## 6. Testes obrigatórios antes de tráfego

1. Abrir a home.
2. Enviar uma simulação real.
3. Verificar se o lead apareceu no painel.
4. Testar botão WhatsApp.
5. Cadastrar um parceiro em `/parceiros.html`.
6. Confirmar se o parceiro apareceu em `/admin/partners.php`.
7. Editar um parâmetro em `/admin/settings.php`.
8. Confirmar HTTPS ativo.
9. Testar no celular.

## 7. Comandos Git sugeridos

```bash
git status
git add .
git commit -m "Cria versão 1.0 do Quanto Custa Solar"
git push origin main
```
