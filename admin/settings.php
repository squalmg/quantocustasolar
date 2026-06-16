<?php
$pageTitle = 'Configurações do simulador';
require_once __DIR__ . '/../includes/auth.php';
$admin = require_admin();
$pdo = db();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['settings'])) {
    foreach ($_POST['settings'] as $id => $row) {
        $stmt = $pdo->prepare('UPDATE simulator_settings SET avg_tariff=?, avg_generation_per_kwp=?, residential_kwp_min_price=?, residential_kwp_max_price=?, commercial_kwp_min_price=?, commercial_kwp_max_price=?, rural_kwp_min_price=?, rural_kwp_max_price=?, industrial_kwp_min_price=?, industrial_kwp_max_price=?, default_kwp_min_price=?, default_kwp_max_price=?, savings_factor_min=?, savings_factor_max=? WHERE id=?');
        $stmt->execute([
            normalize_money($row['avg_tariff'] ?? 1), normalize_money($row['avg_generation_per_kwp'] ?? 130),
            normalize_money($row['residential_kwp_min_price'] ?? 3800), normalize_money($row['residential_kwp_max_price'] ?? 5500),
            normalize_money($row['commercial_kwp_min_price'] ?? 3300), normalize_money($row['commercial_kwp_max_price'] ?? 5000),
            normalize_money($row['rural_kwp_min_price'] ?? 3600), normalize_money($row['rural_kwp_max_price'] ?? 5600),
            normalize_money($row['industrial_kwp_min_price'] ?? 3000), normalize_money($row['industrial_kwp_max_price'] ?? 4800),
            normalize_money($row['default_kwp_min_price'] ?? 3800), normalize_money($row['default_kwp_max_price'] ?? 5500),
            normalize_money($row['savings_factor_min'] ?? .75), normalize_money($row['savings_factor_max'] ?? .90), (int)$id
        ]);
    }
    log_activity((int)$admin['id'], 'update_settings', 'simulator_settings', '', 'Parâmetros de simulação atualizados');
    header('Location: /admin/settings.php?saved=1');
    exit;
}
$settings = $pdo->query('SELECT * FROM simulator_settings ORDER BY state = "BR" DESC, state ASC')->fetchAll();
require __DIR__ . '/_header.php';
?>
<?php if (isset($_GET['saved'])): ?><div class="notice ok">Configurações salvas.</div><?php endif; ?>
<div class="panel">
  <h2>Parâmetros por estado</h2>
  <p class="small">Use ponto ou vírgula para decimais. Estes valores afetam as próximas simulações. Recomendo começar conservador e ajustar conforme propostas reais dos instaladores.</p>
  <form method="post">
    <div class="table-wrap">
      <table>
        <thead><tr><th>UF</th><th>Tarifa</th><th>Geração kWh/kWp</th><th>Residencial</th><th>Comercial</th><th>Rural</th><th>Industrial</th><th>Economia</th></tr></thead>
        <tbody>
          <?php foreach ($settings as $s): ?>
            <tr>
              <td><strong><?= e($s['state']) ?></strong></td>
              <td><input name="settings[<?= (int)$s['id'] ?>][avg_tariff]" value="<?= e($s['avg_tariff']) ?>"></td>
              <td><input name="settings[<?= (int)$s['id'] ?>][avg_generation_per_kwp]" value="<?= e($s['avg_generation_per_kwp']) ?>"></td>
              <td><input name="settings[<?= (int)$s['id'] ?>][residential_kwp_min_price]" value="<?= e($s['residential_kwp_min_price']) ?>"><input name="settings[<?= (int)$s['id'] ?>][residential_kwp_max_price]" value="<?= e($s['residential_kwp_max_price']) ?>"></td>
              <td><input name="settings[<?= (int)$s['id'] ?>][commercial_kwp_min_price]" value="<?= e($s['commercial_kwp_min_price']) ?>"><input name="settings[<?= (int)$s['id'] ?>][commercial_kwp_max_price]" value="<?= e($s['commercial_kwp_max_price']) ?>"></td>
              <td><input name="settings[<?= (int)$s['id'] ?>][rural_kwp_min_price]" value="<?= e($s['rural_kwp_min_price']) ?>"><input name="settings[<?= (int)$s['id'] ?>][rural_kwp_max_price]" value="<?= e($s['rural_kwp_max_price']) ?>"></td>
              <td><input name="settings[<?= (int)$s['id'] ?>][industrial_kwp_min_price]" value="<?= e($s['industrial_kwp_min_price']) ?>"><input name="settings[<?= (int)$s['id'] ?>][industrial_kwp_max_price]" value="<?= e($s['industrial_kwp_max_price']) ?>"><input type="hidden" name="settings[<?= (int)$s['id'] ?>][default_kwp_min_price]" value="<?= e($s['default_kwp_min_price']) ?>"><input type="hidden" name="settings[<?= (int)$s['id'] ?>][default_kwp_max_price]" value="<?= e($s['default_kwp_max_price']) ?>"></td>
              <td><input name="settings[<?= (int)$s['id'] ?>][savings_factor_min]" value="<?= e($s['savings_factor_min']) ?>"><input name="settings[<?= (int)$s['id'] ?>][savings_factor_max]" value="<?= e($s['savings_factor_max']) ?>"></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <br><button class="btn btn-primary" type="submit">Salvar configurações</button>
  </form>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
