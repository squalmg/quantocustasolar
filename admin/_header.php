<?php
require_once __DIR__ . '/../includes/auth.php';
$admin = require_admin();
$current = basename($_SERVER['SCRIPT_NAME']);
function active($file, $current) { return $file === $current ? 'active' : ''; }
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Painel | Quanto Custa Solar</title>
  <link rel="icon" href="/assets/img/icone.png">
  <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body>
<div class="admin-layout">
  <aside class="sidebar">
    <a class="sidebar-logo" href="/admin/index.php"><img src="/assets/img/icone.png" alt="QCS"><strong>QCS</strong></a>
    <nav class="side-nav">
      <a class="<?= active('index.php', $current) ?>" href="/admin/index.php">📊 Painel</a>
      <a class="<?= active('leads.php', $current) ?>" href="/admin/leads.php">🔥 Leads</a>
      <a class="<?= active('partners.php', $current) ?>" href="/admin/partners.php">🤝 Parceiros</a>
      <a class="<?= active('settings.php', $current) ?>" href="/admin/settings.php">⚙ Configurações</a>
      <a href="/" target="_blank">🌐 Ver site</a>
      <a href="/admin/logout.php">↩ Sair</a>
    </nav>
  </aside>
  <main class="admin-main">
    <div class="topbar">
      <div><h1><?= e($pageTitle ?? 'Painel') ?></h1></div>
      <div class="user-badge"><?= e($admin['name']) ?> · Online</div>
    </div>
