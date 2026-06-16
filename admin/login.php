<?php
require_once __DIR__ . '/../includes/auth.php';
$error = '';
if (current_admin()) {
    header('Location: /admin/index.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        if (attempt_login($email, $password)) {
            header('Location: /admin/index.php');
            exit;
        }
        $error = 'E-mail ou senha inválidos.';
    } catch (Throwable $e) {
        $error = 'Erro ao conectar no banco. Confira config/config.php e o schema.';
    }
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login admin | Quanto Custa Solar</title>
  <link rel="icon" href="/assets/img/icone.png"><link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body class="login-page">
  <form class="login-card" method="post">
    <img src="/assets/img/logo.png" alt="Quanto Custa Solar">
    <h1>Acesso administrativo</h1>
    <?php if ($error): ?><div class="notice error"><?= e($error) ?></div><?php endif; ?>
    <label for="email">E-mail</label>
    <input id="email" name="email" type="email" required autocomplete="email">
    <br><br>
    <label for="password">Senha</label>
    <input id="password" name="password" type="password" required autocomplete="current-password">
    <br><br>
    <button class="btn btn-primary" style="width:100%" type="submit">Entrar</button>
    <p class="small">Crie o primeiro usuário manualmente no banco usando as instruções do README.</p>
  </form>
</body></html>
