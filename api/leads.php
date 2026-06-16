<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/security.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_response(['ok' => false, 'message' => 'Método não permitido.'], 405);
    }

    $pdo = db();
    if (!rate_limit($pdo, 'lead_submit', 10, 10)) {
        json_response(['ok' => false, 'message' => 'Muitas tentativas. Aguarde alguns minutos e tente novamente.'], 429);
    }

    $data = read_json_body();

    if (!empty($data['website'] ?? '')) {
        json_response(['ok' => false, 'message' => 'Não foi possível processar o envio.'], 400);
    }

    $name = clean_string($data['name'] ?? '', 150);
    $whatsapp = clean_string($data['whatsapp'] ?? '', 30);
    [$whatsappE164, $validWhatsapp] = normalize_whatsapp_br($whatsapp);
    $city = clean_string($data['city'] ?? '', 120);
    $state = normalize_state($data['state'] ?? 'GO');
    $propertyType = normalize_property_type((string)($data['property_type'] ?? 'default'));
    $monthlyBill = normalize_money($data['monthly_bill'] ?? 0);
    $ownership = clean_string($data['property_ownership'] ?? '', 60);
    $roofType = clean_string($data['roof_type'] ?? '', 80);
    $timeline = clean_string($data['installation_timeline'] ?? '', 80);
    $source = clean_string($data['source'] ?? 'site', 120);
    $consent = filter_var($data['consent'] ?? false, FILTER_VALIDATE_BOOLEAN);

    if (!$name || !$whatsapp || !$city || !$state || !$propertyType || $monthlyBill <= 0 || !$ownership || !$roofType || !$timeline) {
        json_response(['ok' => false, 'message' => 'Preencha todos os campos obrigatórios.'], 422);
    }
    if (!$validWhatsapp) {
        json_response(['ok' => false, 'message' => 'Informe um WhatsApp brasileiro válido com DDD.'], 422);
    }
    if (!$consent) {
        json_response(['ok' => false, 'message' => 'É necessário aceitar o contato de empresas parceiras para receber orçamentos.'], 422);
    }

    $settings = get_simulator_settings($pdo, $state);
    $estimate = calculate_solar_estimate([
        'monthly_bill' => $monthlyBill,
        'property_type' => $propertyType,
    ], $settings);

    $score = calculate_lead_score([
        'monthly_bill' => $monthlyBill,
        'property_ownership' => $ownership,
        'installation_timeline' => $timeline,
        'property_type' => $propertyType,
        'roof_type' => $roofType,
    ], $validWhatsapp);
    $classification = classify_score($score);
    $leadId = generate_lead_id();

    $stmt = $pdo->prepare('INSERT INTO leads (
        lead_id, name, whatsapp, whatsapp_e164, city, state, property_type, monthly_bill,
        estimated_kwh, estimated_kwp, investment_min, investment_max, savings_min, savings_max,
        payback_min, payback_max, roof_type, property_ownership, installation_timeline,
        score, classification, status, source, utm_source, utm_campaign, utm_medium, utm_content, utm_term,
        ip_hash, user_agent
    ) VALUES (
        :lead_id, :name, :whatsapp, :whatsapp_e164, :city, :state, :property_type, :monthly_bill,
        :estimated_kwh, :estimated_kwp, :investment_min, :investment_max, :savings_min, :savings_max,
        :payback_min, :payback_max, :roof_type, :property_ownership, :installation_timeline,
        :score, :classification, :status, :source, :utm_source, :utm_campaign, :utm_medium, :utm_content, :utm_term,
        :ip_hash, :user_agent
    )');
    $stmt->execute([
        ':lead_id' => $leadId,
        ':name' => $name,
        ':whatsapp' => $whatsapp,
        ':whatsapp_e164' => $whatsappE164,
        ':city' => $city,
        ':state' => $state,
        ':property_type' => $propertyType,
        ':monthly_bill' => $monthlyBill,
        ':estimated_kwh' => $estimate['estimated_kwh'],
        ':estimated_kwp' => $estimate['estimated_kwp'],
        ':investment_min' => $estimate['investment_min'],
        ':investment_max' => $estimate['investment_max'],
        ':savings_min' => $estimate['savings_min'],
        ':savings_max' => $estimate['savings_max'],
        ':payback_min' => $estimate['payback_min'],
        ':payback_max' => $estimate['payback_max'],
        ':roof_type' => $roofType,
        ':property_ownership' => $ownership,
        ':installation_timeline' => $timeline,
        ':score' => $score,
        ':classification' => $classification,
        ':status' => 'novo',
        ':source' => $source,
        ':utm_source' => clean_string($data['utm_source'] ?? '', 120),
        ':utm_campaign' => clean_string($data['utm_campaign'] ?? '', 120),
        ':utm_medium' => clean_string($data['utm_medium'] ?? '', 120),
        ':utm_content' => clean_string($data['utm_content'] ?? '', 120),
        ':utm_term' => clean_string($data['utm_term'] ?? '', 120),
        ':ip_hash' => ip_hash(),
        ':user_agent' => clean_string($_SERVER['HTTP_USER_AGENT'] ?? '', 500),
    ]);

    $message = "Olá, fiz uma simulação no Quanto Custa Solar.\n\n" .
        "Meu nome: {$name}\n" .
        "Cidade: {$city}/{$state}\n" .
        "Tipo de imóvel: " . display_property_type($propertyType) . "\n" .
        "Conta média: " . br_money($monthlyBill) . "\n" .
        "Sistema estimado: " . br_number($estimate['estimated_kwp'], 1) . " kWp\n" .
        "Investimento aproximado: " . br_money($estimate['investment_min']) . " a " . br_money($estimate['investment_max']) . "\n\n" .
        "Gostaria de receber um orçamento real.";

    json_response([
        'ok' => true,
        'message' => 'Simulação enviada com sucesso.',
        'lead_id' => $leadId,
        'classification' => $classification,
        'score' => $score,
        'result' => $estimate,
        'whatsapp_url' => whatsapp_link(defined('DEFAULT_WHATSAPP_E164') ? DEFAULT_WHATSAPP_E164 : $whatsappE164, $message),
    ]);
} catch (Throwable $e) {
    json_response(['ok' => false, 'message' => 'Erro ao processar sua simulação. Confira a configuração do banco de dados.'], 500);
}
