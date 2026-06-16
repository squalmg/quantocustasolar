<?php
$pageTitle = 'Detalhes do lead';
require_once __DIR__ . '/../includes/auth.php';
$admin = require_admin();
$pdo = db();
$id = (int)($_GET['id'] ?? 0);
$allowedStatuses = ['novo', 'qualificado', 'enviado', 'negociacao', 'fechado', 'perdido', 'invalido'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = in_array($_POST['status'] ?? '', $allowedStatuses, true) ? $_POST['status'] : 'novo';
    $notes = clean_string($_POST['notes'] ?? '', 2000);
    $stmt = $pdo->prepare('UPDATE leads SET status = ?, notes = ? WHERE id = ?');
    $stmt->execute([$status, $notes, $id]);
    log_activity((int)$admin['id'], 'update_lead', 'lead', (string)$id, 'Detalhes do lead atualizados');
    header('Location: /admin/lead-view.php?id=' . $id . '&updated=1');
    exit;
}
$stmt = $pdo->prepare('SELECT * FROM leads WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$lead = $stmt->fetch();
if (!$lead) { http_response_code(404); exit('Lead não encontrado.'); }
$msg = "Novo lead de energia solar:\nNome: {$lead['name']}\nWhatsApp: {$lead['whatsapp']}\nCidade: {$lead['city']}/{$lead['state']}\nTipo: " . display_property_type($lead['property_type']) . "\nConta média: " . br_money((float)$lead['monthly_bill']) . "\nPotência estimada: " . br_number((float)$lead['estimated_kwp'], 1) . " kWp\nInvestimento: " . br_money((float)$lead['investment_min']) . " a " . br_money((float)$lead['investment_max']) . "\nEconomia: " . br_money((float)$lead['savings_min']) . " a " . br_money((float)$lead['savings_max']) . "\nPayback: " . br_number((float)$lead['payback_min'], 1) . " a " . br_number((float)$lead['payback_max'], 1) . " anos\nScore: {$lead['score']}/100\nPrazo: {$lead['installation_timeline']}";
$wa = whatsapp_link($lead['whatsapp_e164'], $msg);
require __DIR__ . '/_header.php';
?>
<?php if (isset($_GET['updated'])): ?><div class="notice ok">Lead atualizado.</div><?php endif; ?>
<div class="panel">
  <div class="actions" style="justify-content:space-between;margin-bottom:16px">
    <a class="btn btn-light" href="/admin/leads.php">← Voltar</a>
    <a class="btn btn-primary" target="_blank" rel="noopener" href="<?= e($wa) ?>">Abrir WhatsApp</a>
  </div>
  <div class="detail-grid">
    <div class="detail"><small>Lead ID</small><strong><?= e($lead['lead_id']) ?></strong></div>
    <div class="detail"><small>Nome</small><strong><?= e($lead['name']) ?></strong></div>
    <div class="detail"><small>WhatsApp</small><strong><?= e($lead['whatsapp']) ?></strong></div>
    <div class="detail"><small>Cidade/UF</small><strong><?= e($lead['city']) ?>/<?= e($lead['state']) ?></strong></div>
    <div class="detail"><small>Tipo</small><strong><?= e(display_property_type($lead['property_type'])) ?></strong></div>
    <div class="detail"><small>Conta média</small><strong><?= e(br_money((float)$lead['monthly_bill'])) ?></strong></div>
    <div class="detail"><small>Consumo estimado</small><strong><?= e(br_number((float)$lead['estimated_kwh'], 1)) ?> kWh/mês</strong></div>
    <div class="detail"><small>Sistema estimado</small><strong><?= e(br_number((float)$lead['estimated_kwp'], 1)) ?> kWp</strong></div>
    <div class="detail"><small>Investimento</small><strong><?= e(br_money((float)$lead['investment_min'])) ?> a <?= e(br_money((float)$lead['investment_max'])) ?></strong></div>
    <div class="detail"><small>Economia</small><strong><?= e(br_money((float)$lead['savings_min'])) ?> a <?= e(br_money((float)$lead['savings_max'])) ?>/mês</strong></div>
    <div class="detail"><small>Payback</small><strong><?= e(br_number((float)$lead['payback_min'], 1)) ?> a <?= e(br_number((float)$lead['payback_max'], 1)) ?> anos</strong></div>
    <div class="detail"><small>Score</small><strong><?= (int)$lead['score'] ?> · <?= e($lead['classification']) ?></strong></div>
    <div class="detail"><small>Imóvel</small><strong><?= e($lead['property_ownership']) ?></strong></div>
    <div class="detail"><small>Telhado</small><strong><?= e($lead['roof_type']) ?></strong></div>
    <div class="detail"><small>Prazo</small><strong><?= e($lead['installation_timeline']) ?></strong></div>
    <div class="detail"><small>Origem</small><strong><?= e($lead['source']) ?></strong></div>
    <div class="detail"><small>UTM</small><strong><?= e(trim(($lead['utm_source'] ?? '') . ' / ' . ($lead['utm_campaign'] ?? ''), ' /')) ?: '-' ?></strong></div>
    <div class="detail"><small>Data</small><strong><?= e(date('d/m/Y H:i', strtotime($lead['created_at']))) ?></strong></div>
  </div>
</div>
<div class="panel">
  <h2>Atualizar atendimento</h2>
  <form method="post" class="form-grid">
    <div><label>Status</label><select name="status"><?php foreach ($allowedStatuses as $st): ?><option value="<?= e($st) ?>" <?= $lead['status'] === $st ? 'selected' : '' ?>><?= e(status_label($st)) ?></option><?php endforeach; ?></select></div>
    <div class="full"><label>Observações</label><textarea name="notes" rows="6"><?= e($lead['notes']) ?></textarea></div>
    <div class="full"><button class="btn btn-primary" type="submit">Salvar alterações</button></div>
  </form>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
