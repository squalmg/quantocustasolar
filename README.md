# Quanto Custa Solar — V1.0

Site/simulador para gerar leads de energia solar com painel administrativo em PHP 8.2 + MySQL.

## O que está incluso

- Home com identidade visual e logo transparente.
- Simulador de custo de energia solar.
- Cálculo de kWp, investimento, economia e payback em faixa.
- Salvamento de leads em MySQL.
- Score comercial do lead.
- Painel admin com login protegido.
- Lista de leads com filtros, alteração de status, CSV e botão WhatsApp.
- Página de detalhe do lead.
- Cadastro público de empresas parceiras.
- CRUD simples de parceiros no admin.
- Configurações do simulador por estado.
- Política de privacidade e termos de uso.
- Proteções básicas via `.htaccess`, sessão, prepared statements, honeypot e rate limit.

## Requisitos

- PHP 8.2 ou superior.
- MySQL/MariaDB.
- Extensão PDO MySQL habilitada.
- Hospedagem apontando para a raiz do projeto.

## Instalação rápida

1. Envie os arquivos para a hospedagem.
2. Crie um banco MySQL.
3. Importe:

```sql
/database/schema-mysql.sql
/database/seed-settings.sql
```

4. Edite:

```txt
/config/config.php
```

com os dados reais do banco.

5. Crie o primeiro usuário admin.

Gere o hash da senha:

```bash
php -r "echo password_hash('SUA_SENHA_FORTE', PASSWORD_DEFAULT), PHP_EOL;"
```

Depois rode no banco:

```sql
INSERT INTO admin_users (name, email, password_hash, role)
VALUES ('Administrador', 'admin@quantocustasolar.com.br', 'COLE_O_HASH_GERADO_AQUI', 'admin');
```

6. Acesse:

```txt
/admin/login.php
```

## Arquivo de configuração

Use `config/config.example.php` como referência. O arquivo `config/config.php` vem com placeholders e precisa ser preenchido.

Campos principais:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'nome_do_banco');
define('DB_USER', 'usuario_do_banco');
define('DB_PASS', 'senha_do_banco');
define('APP_KEY', 'uma-chave-grande-e-aleatoria');
define('SITE_URL', 'https://quantocustasolar.com.br');
define('DEFAULT_WHATSAPP_E164', '5564999999999');
```

Troque `DEFAULT_WHATSAPP_E164` para o WhatsApp que deve receber o CTA inicial, em formato internacional sem `+`.

## Estrutura

```txt
/
  index.html
  parceiros.html
  politica-de-privacidade.html
  termos-de-uso.html
  .htaccess
  /assets
    /css
    /js
    /img
  /api
    leads.php
    partners.php
    settings.php
  /admin
    login.php
    logout.php
    index.php
    leads.php
    lead-view.php
    partners.php
    settings.php
  /config
    config.php
    config.example.php
  /database
    schema-mysql.sql
    seed-settings.sql
    create-admin-example.sql
  /includes
    db.php
    auth.php
    helpers.php
    security.php
```

## Fórmula da simulação

```txt
consumo_kwh_estimado = valor_conta / tarifa_media_estado
potencia_kwp = consumo_kwh_estimado / geracao_media_mensal_por_kwp_estado
investimento_min = potencia_kwp * custo_min_por_kwp
investimento_max = potencia_kwp * custo_max_por_kwp
economia_min = valor_conta * fator_economia_min
economia_max = valor_conta * fator_economia_max
payback_min = investimento_min / (economia_max * 12)
payback_max = investimento_max / (economia_min * 12)
```

Todos os parâmetros podem ser editados em `/admin/settings.php`.

## Status dos leads

- Novo
- Qualificado
- Enviado para parceiro
- Em negociação
- Venda fechada
- Perdido
- Inválido

## Observações importantes

- O simulador não promete valor exato nem economia garantida.
- O orçamento final depende de avaliação técnica.
- Não suba senhas reais para o GitHub.
- Depois de testar, altere `APP_KEY` para uma chave forte.
- Ative HTTPS no domínio antes de rodar tráfego pago.
