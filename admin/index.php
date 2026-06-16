<?php
$pageTitle = 'Painel de Controle';
require_once __DIR__ . '/../includes/auth.php';
$admin = require_admin();
$pdo = db();
function scalar_query(PDO $pdo, string $sql, array $params = []) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn() ?: 0;
}
$today = scalar_query($pdo, "SELECT COUNT(*) FROM leads WHERE DATE(created_at) = CURDATE()");
$week = scalar_query($pdo, "SELECT COUNT(*) FROM leads WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$month = scalar_query($pdo, "SELECT COUNT(*) FROM leads WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
$hot = scalar_query($pdo, "SELECT COUNT(*) FROM leads WHERE score >= 80");
$sent = scalar_query($pdo, "SELECT COUNT(*) FROM leads WHERE status = 'enviado'");
$partners = scalar_query($pdo, "SELECT COUNT(*) FROM partners WHERE status = 'ativo'");
$recent = $pdo->query("SELECT * FROM leads ORDER BY created_at DESC LIMIT 8")->fetchAll();
require __DIR__ . '/_header.php';
?>
<div class="cards">
  <div class="metric"><span>Leads hoje</span><strong><?= (int)$today ?></strong></div>
  <div class="metric"><span>Últimos 7 dias</span><strong><?= (int)$week ?></strong></div>
  <div class="metric"><span>Últimos 30 dias</span><strong><?= (int)$month ?></strong></div>
  <div class="metric"><span>Leads quentes</span><strong><?= (int)$hot ?></strong></div>
  <div class="metric"><span>Enviados</span><strong><?= (int)$sent ?></strong></div>
  <div class="metric"><span>Parceiros ativos</span><strong><?= (int)$partners ?></strong></div>
</div>
<div class="panel">
  <h2>Leads recentes</h2>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Data</th><th>Nome</th><th>Cidade/UF</th><th>Tipo</th><th>Conta</th><th>Score</th><th>Status</th><th>Ações</th></tr></thead>
      <tbody>
        <?php foreach ($recent as $lead): ?>
          <tr>
            <td><?= e(date('d/m/Y H:i', strtotime($lead['created_at']))) ?></td>
            <td><?= e($lead['name']) ?><br><small><?= e($lead['whatsapp']) ?></small></td>
            <td><?= e($lead['city']) ?>/<?= e($lead['state']) ?></td>
            <td><?= e(display_property_type($lead['property_type'])) ?></td>
            <td><?= e(br_money((float)$lead['monthly_bill'])) ?></td>
            <td><span class="score <?= $lead['score'] >= 80 ? 'hot' : ($lead['score'] >= 60 ? 'good' : ($lead['score'] >= 40 ? 'medium' : 'cold')) ?>"><?= (int)$lead['score'] ?></span><br><small><?= e($lead['classification']) ?></small></td>
            <td><span class="badge <?= e($lead['status']) ?>"><?= e(status_label($lead['status'])) ?></span></td>
            <td><a class="btn btn-light" href="/admin/lead-view.php?id=<?= (int)$lead['id'] ?>">Ver</a></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$recent): ?><tr><td colspan="8">Nenhum lead recebido ainda.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
