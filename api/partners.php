<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/security.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_response(['ok' => false, 'message' => 'Método não permitido.'], 405);
    }
    $pdo = db();
    if (!rate_limit($pdo, 'partner_submit', 5, 10)) {
        json_response(['ok' => false, 'message' => 'Muitas tentativas. Aguarde alguns minutos e tente novamente.'], 429);
    }
    $data = read_json_body();
    if (!empty($data['website'] ?? '')) {
        json_response(['ok' => false, 'message' => 'Não foi possível processar o envio.'], 400);
    }

    $company = clean_string($data['company_name'] ?? '', 180);
    $responsible = clean_string($data['responsible_name'] ?? '', 150);
    $whatsapp = clean_string($data['whatsapp'] ?? '', 30);
    [$whatsappE164, $validWhatsapp] = normalize_whatsapp_br($whatsapp);
    $email = filter_var(trim((string)($data['email'] ?? '')), FILTER_VALIDATE_EMAIL) ?: '';
    $city = clean_string($data['base_city'] ?? '', 120);
    $state = normalize_state($data['base_state'] ?? 'GO');
    $consent = filter_var($data['consent'] ?? false, FILTER_VALIDATE_BOOLEAN);

    if (!$company || !$responsible || !$validWhatsapp || !$email || !$city || !$consent) {
        json_response(['ok' => false, 'message' => 'Preencha os campos obrigatórios com dados válidos.'], 422);
    }

    $types = $data['project_types'] ?? [];
    if (!is_array($types)) $types = [];
    $serviceStates = $data['service_states'] ?? [];
    if (!is_array($serviceStates)) $serviceStates = array_filter(array_map('trim', explode(',', (string)$serviceStates)));
    $serviceStates = array_map('normalize_state', $serviceStates);

    $stmt = $pdo->prepare('INSERT INTO partners (
        company_name, responsible_name, whatsapp, whatsapp_e164, email, cnpj, base_city, base_state,
        service_states, service_radius_km, accepts_residential, accepts_commercial, accepts_rural, accepts_industrial,
        monthly_lead_limit, plan_name, status
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        $company,
        $responsible,
        $whatsapp,
        $whatsappE164,
        $email,
        clean_string($data['cnpj'] ?? '', 30),
        $city,
        $state,
        implode(',', array_unique($serviceStates)),
        (int)($data['service_radius_km'] ?? 150),
        in_array('residential', $types, true) ? 1 : 0,
        in_array('commercial', $types, true) ? 1 : 0,
        in_array('rural', $types, true) ? 1 : 0,
        in_array('industrial', $types, true) ? 1 : 0,
        (int)($data['monthly_lead_limit'] ?? 20),
        clean_string($data['plan_name'] ?? 'interesse', 80),
        'novo',
    ]);

    json_response(['ok' => true, 'message' => 'Cadastro recebido. Nossa equipe entrará em contato para validar a parceria.']);
} catch (Throwable $e) {
    json_response(['ok' => false, 'message' => 'Erro ao salvar cadastro de parceiro. Confira a configuração do banco de dados.'], 500);
}
