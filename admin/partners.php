<?php
$pageTitle = 'Parceiros';
require_once __DIR__ . '/../includes/auth.php';
$admin = require_admin();
$pdo = db();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $company = clean_string($_POST['company_name'] ?? '', 180);
    $responsible = clean_string($_POST['responsible_name'] ?? '', 150);
    $whatsapp = clean_string($_POST['whatsapp'] ?? '', 30);
    [$whatsappE164] = normalize_whatsapp_br($whatsapp);
    $email = clean_string($_POST['email'] ?? '', 180);
    $status = in_array($_POST['status'] ?? 'novo', ['novo', 'ativo', 'inativo'], true) ? $_POST['status'] : 'novo';
    if ($id > 0) {
        $stmt = $pdo->prepare('UPDATE partners SET company_name=?, responsible_name=?, whatsapp=?, whatsapp_e164=?, email=?, cnpj=?, base_city=?, base_state=?, service_states=?, service_radius_km=?, accepts_residential=?, accepts_commercial=?, accepts_rural=?, accepts_industrial=?, monthly_lead_limit=?, plan_name=?, status=? WHERE id=?');
        $stmt->execute([$company, $responsible, $whatsapp, $whatsappE164, $email, clean_string($_POST['cnpj'] ?? '', 30), clean_string($_POST['base_city'] ?? '', 120), normalize_state($_POST['base_state'] ?? 'GO'), clean_string($_POST['service_states'] ?? '', 500), (int)$_POST['service_radius_km'], isset($_POST['accepts_residential']) ? 1 : 0, isset($_POST['accepts_commercial']) ? 1 : 0, isset($_POST['accepts_rural']) ? 1 : 0, isset($_POST['accepts_industrial']) ? 1 : 0, (int)$_POST['monthly_lead_limit'], clean_string($_POST['plan_name'] ?? '', 80), $status, $id]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO partners (company_name, responsible_name, whatsapp, whatsapp_e164, email, cnpj, base_city, base_state, service_states, service_radius_km, accepts_residential, accepts_commercial, accepts_rural, accepts_industrial, monthly_lead_limit, plan_name, status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $stmt->execute([$company, $responsible, $whatsapp, $whatsappE164, $email, clean_string($_POST['cnpj'] ?? '', 30), clean_string($_POST['base_city'] ?? '', 120), normalize_state($_POST['base_state'] ?? 'GO'), clean_string($_POST['service_states'] ?? '', 500), (int)$_POST['service_radius_km'], isset($_POST['accepts_residential']) ? 1 : 0, isset($_POST['accepts_commercial']) ? 1 : 0, isset($_POST['accepts_rural']) ? 1 : 0, isset($_POST['accepts_industrial']) ? 1 : 0, (int)$_POST['monthly_lead_limit'], clean_string($_POST['plan_name'] ?? '', 80), $status]);
    }
    header('Location: /admin/partners.php?saved=1');
    exit;
}
$edit = null;
if (!empty($_GET['edit'])) { $stmt = $pdo->prepare('SELECT * FROM partners WHERE id=?'); $stmt->execute([(int)$_GET['edit']]); $edit = $stmt->fetch(); }
$partners = $pdo->query('SELECT * FROM partners ORDER BY created_at DESC LIMIT 300')->fetchAll();
require __DIR__ . '/_header.php';
?>
<?php if (isset($_GET['saved'])): ?><div class="notice ok">Parceiro salvo.</div><?php endif; ?>
<div class="panel">
  <h2><?= $edit ? 'Editar parceiro' : 'Novo parceiro' ?></h2>
  <form method="post" class="form-grid">
    <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
    <div><label>Empresa</label><input name="company_name" value="<?= e($edit['company_name'] ?? '') ?>" required></div>
    <div><label>Responsável</label><input name="responsible_name" value="<?= e($edit['responsible_name'] ?? '') ?>" required></div>
    <div><label>WhatsApp</label><input name="whatsapp" value="<?= e($edit['whatsapp'] ?? '') ?>" required></div>
    <div><label>E-mail</label><input name="email" value="<?= e($edit['email'] ?? '') ?>"></div>
    <div><label>CNPJ</label><input name="cnpj" value="<?= e($edit['cnpj'] ?? '') ?>"></div>
    <div><label>Cidade base</label><input name="base_city" value="<?= e($edit['base_city'] ?? '') ?>"></div>
    <div><label>UF base</label><input name="base_state" maxlength="2" value="<?= e($edit['base_state'] ?? 'GO') ?>"></div>
    <div><label>Estados atendidos</label><input name="service_states" value="<?= e($edit['service_states'] ?? 'GO') ?>"></div>
    <div><label>Raio km</label><input name="service_radius_km" type="number" value="<?= e($edit['service_radius_km'] ?? 150) ?>"></div>
    <div><label>Limite mensal</label><input name="monthly_lead_limit" type="number" value="<?= e($edit['monthly_lead_limit'] ?? 20) ?>"></div>
    <div><label>Plano</label><input name="plan_name" value="<?= e($edit['plan_name'] ?? 'validação') ?>"></div>
    <div><label>Status</label><select name="status"><option value="novo">Novo</option><option value="ativo" <?= ($edit['status'] ?? '') === 'ativo' ? 'selected' : '' ?>>Ativo</option><option value="inativo" <?= ($edit['status'] ?? '') === 'inativo' ? 'selected' : '' ?>>Inativo</option></select></div>
    <div class="full actions">
      <label class="check-pill"><input type="checkbox" name="accepts_residential" <?= !empty($edit['accepts_residential']) ? 'checked' : '' ?>> Residencial</label>
      <label class="check-pill"><input type="checkbox" name="accepts_commercial" <?= !empty($edit['accepts_commercial']) ? 'checked' : '' ?>> Comercial</label>
      <label class="check-pill"><input type="checkbox" name="accepts_rural" <?= !empty($edit['accepts_rural']) ? 'checked' : '' ?>> Rural</label>
      <label class="check-pill"><input type="checkbox" name="accepts_industrial" <?= !empty($edit['accepts_industrial']) ? 'checked' : '' ?>> Industrial</label>
    </div>
    <div class="full"><button class="btn btn-primary" type="submit">Salvar parceiro</button></div>
  </form>
</div>
<div class="panel">
  <h2>Parceiros cadastrados</h2>
  <div class="table-wrap"><table><thead><tr><th>Empresa</th><th>Contato</th><th>Base</th><th>Atende</th><th>Plano</th><th>Status</th><th>Ações</th></tr></thead><tbody>
  <?php foreach ($partners as $p): ?><tr><td><strong><?= e($p['company_name']) ?></strong><br><small><?= e($p['cnpj']) ?></small></td><td><?= e($p['responsible_name']) ?><br><small><?= e($p['whatsapp']) ?> · <?= e($p['email']) ?></small></td><td><?= e($p['base_city']) ?>/<?= e($p['base_state']) ?><br><small><?= e($p['service_states']) ?> · <?= (int)$p['service_radius_km'] ?> km</small></td><td><?= $p['accepts_residential']?'Residencial ':'' ?><?= $p['accepts_commercial']?'Comercial ':'' ?><?= $p['accepts_rural']?'Rural ':'' ?><?= $p['accepts_industrial']?'Industrial':'' ?></td><td><?= e($p['plan_name']) ?><br><small><?= (int)$p['monthly_lead_limit'] ?> leads/mês</small></td><td><span class="badge <?= e($p['status']) ?>"><?= e(status_label($p['status'])) ?></span></td><td><a class="btn btn-light" href="/admin/partners.php?edit=<?= (int)$p['id'] ?>">Editar</a></td></tr><?php endforeach; ?>
  <?php if (!$partners): ?><tr><td colspan="7">Nenhum parceiro cadastrado.</td></tr><?php endif; ?></tbody></table></div>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
