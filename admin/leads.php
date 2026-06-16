<?php
$pageTitle = 'Leads';
require_once __DIR__ . '/../includes/auth.php';
$admin = require_admin();
$pdo = db();

$allowedStatuses = ['novo', 'qualificado', 'enviado', 'negociacao', 'fechado', 'perdido', 'invalido'];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lead_id'], $_POST['status'])) {
    $status = in_array($_POST['status'], $allowedStatuses, true) ? $_POST['status'] : 'novo';
    $stmt = $pdo->prepare('UPDATE leads SET status = ? WHERE id = ?');
    $stmt->execute([$status, (int)$_POST['lead_id']]);
    log_activity((int)$admin['id'], 'update_lead_status', 'lead', (string)$_POST['lead_id'], 'Status alterado para ' . $status);
    header('Location: /admin/leads.php?updated=1');
    exit;
}

$where = [];
$params = [];
$q = trim($_GET['q'] ?? '');
if ($q !== '') {
    $where[] = '(name LIKE ? OR whatsapp LIKE ? OR whatsapp_e164 LIKE ? OR city LIKE ?)';
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like, $like);
}
if (!empty($_GET['state'])) { $where[] = 'state = ?'; $params[] = normalize_state($_GET['state']); }
if (!empty($_GET['status'])) { $where[] = 'status = ?'; $params[] = clean_string($_GET['status'], 40); }
if (!empty($_GET['property_type'])) { $where[] = 'property_type = ?'; $params[] = clean_string($_GET['property_type'], 40); }
if (!empty($_GET['score'])) {
    if ($_GET['score'] === 'hot') $where[] = 'score >= 80';
    elseif ($_GET['score'] === 'good') $where[] = 'score BETWEEN 60 AND 79';
    elseif ($_GET['score'] === 'medium') $where[] = 'score BETWEEN 40 AND 59';
    elseif ($_GET['score'] === 'cold') $where[] = 'score < 40';
}
$sqlWhere = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $stmt = $pdo->prepare("SELECT * FROM leads $sqlWhere ORDER BY created_at DESC");
    $stmt->execute($params);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=leads-quantocustasolar.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['data', 'nome', 'whatsapp', 'cidade', 'uf', 'tipo', 'conta', 'kwp', 'invest_min', 'invest_max', 'score', 'status']);
    while ($row = $stmt->fetch()) {
        fputcsv($out, [$row['created_at'], $row['name'], $row['whatsapp_e164'], $row['city'], $row['state'], $row['property_type'], $row['monthly_bill'], $row['estimated_kwp'], $row['investment_min'], $row['investment_max'], $row['score'], $row['status']]);
    }
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM leads $sqlWhere ORDER BY created_at DESC LIMIT 300");
$stmt->execute($params);
$leads = $stmt->fetchAll();
require __DIR__ . '/_header.php';
?>
<?php if (isset($_GET['updated'])): ?><div class="notice ok">Status atualizado.</div><?php endif; ?>
<div class="panel">
  <form class="filters" method="get">
    <input name="q" value="<?= e($q) ?>" placeholder="Buscar nome, WhatsApp ou cidade">
    <input name="state" value="<?= e($_GET['state'] ?? '') ?>" maxlength="2" placeholder="UF">
    <select name="property_type"><option value="">Tipo</option><option value="residential">Residencial</option><option value="commercial">Comercial</option><option value="rural">Rural</option><option value="industrial">Industrial</option></select>
    <select name="status"><option value="">Status</option><?php foreach ($allowedStatuses as $st): ?><option value="<?= e($st) ?>" <?= ($_GET['status'] ?? '') === $st ? 'selected' : '' ?>><?= e(status_label($st)) ?></option><?php endforeach; ?></select>
    <select name="score"><option value="">Score</option><option value="hot">Quente</option><option value="good">Bom</option><option value="medium">Médio</option><option value="cold">Frio</option></select>
    <button class="btn btn-dark" type="submit">Filtrar</button>
    <a class="btn btn-light" href="/admin/leads.php">Limpar</a>
    <a class="btn btn-primary" href="?<?= e(http_build_query(array_merge($_GET, ['export' => 'csv']))) ?>">Exportar CSV</a>
  </form>
</div>
<div class="panel">
  <h2>Lista de leads</h2>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Data</th><th>Lead</th><th>Local</th><th>Perfil</th><th>Simulação</th><th>Score</th><th>Status</th><th>Ações</th></tr></thead>
      <tbody>
        <?php foreach ($leads as $lead):
          $msg = "Novo lead de energia solar:\nNome: {$lead['name']}\nWhatsApp: {$lead['whatsapp']}\nCidade: {$lead['city']}/{$lead['state']}\nTipo: " . display_property_type($lead['property_type']) . "\nConta média: " . br_money((float)$lead['monthly_bill']) . "\nPotência estimada: " . br_number((float)$lead['estimated_kwp'], 1) . " kWp\nInvestimento: " . br_money((float)$lead['investment_min']) . " a " . br_money((float)$lead['investment_max']) . "\nScore: {$lead['score']}/100\nPrazo: {$lead['installation_timeline']}";
          $wa = whatsapp_link($lead['whatsapp_e164'], $msg);
        ?>
          <tr>
            <td><?= e(date('d/m/Y H:i', strtotime($lead['created_at']))) ?><br><small><?= e($lead['lead_id']) ?></small></td>
            <td><strong><?= e($lead['name']) ?></strong><br><small><?= e($lead['whatsapp']) ?></small></td>
            <td><?= e($lead['city']) ?>/<?= e($lead['state']) ?></td>
            <td><?= e(display_property_type($lead['property_type'])) ?><br><small><?= e($lead['property_ownership']) ?> · <?= e($lead['roof_type']) ?></small></td>
            <td><?= e(br_money((float)$lead['monthly_bill'])) ?><br><small><?= e(br_number((float)$lead['estimated_kwp'], 1)) ?> kWp · <?= e(br_money((float)$lead['investment_min'])) ?> a <?= e(br_money((float)$lead['investment_max'])) ?></small></td>
            <td><span class="score <?= $lead['score'] >= 80 ? 'hot' : ($lead['score'] >= 60 ? 'good' : ($lead['score'] >= 40 ? 'medium' : 'cold')) ?>"><?= (int)$lead['score'] ?></span><br><small><?= e($lead['classification']) ?></small></td>
            <td><span class="badge <?= e($lead['status']) ?>"><?= e(status_label($lead['status'])) ?></span></td>
            <td>
              <div class="actions">
                <a class="btn btn-light" href="/admin/lead-view.php?id=<?= (int)$lead['id'] ?>">Ver</a>
                <a class="btn btn-primary" target="_blank" rel="noopener" href="<?= e($wa) ?>">WhatsApp</a>
                <form method="post"><input type="hidden" name="lead_id" value="<?= (int)$lead['id'] ?>"><select name="status" onchange="this.form.submit()"><?php foreach ($allowedStatuses as $st): ?><option value="<?= e($st) ?>" <?= $lead['status'] === $st ? 'selected' : '' ?>><?= e(status_label($st)) ?></option><?php endforeach; ?></select></form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$leads): ?><tr><td colspan="8">Nenhum lead encontrado.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
