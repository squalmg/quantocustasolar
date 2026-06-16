<?php
function app_config_path(): string {
    $config = __DIR__ . '/../config/config.php';
    if (!file_exists($config)) {
        $config = __DIR__ . '/../config/config.example.php';
    }
    return $config;
}

function app_bootstrap(): void {
    require_once app_config_path();
    if (defined('DEFAULT_TIMEZONE')) {
        date_default_timezone_set(DEFAULT_TIMEZONE);
    }
}

function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function clean_string($value, int $max = 255): string {
    $value = trim((string)$value);
    $value = preg_replace('/\s+/', ' ', $value);
    $value = strip_tags($value);
    if (function_exists('mb_substr')) {
        return mb_substr($value, 0, $max, 'UTF-8');
    }
    return substr($value, 0, $max);
}

function only_digits($value): string {
    return preg_replace('/\D+/', '', (string)$value);
}

function normalize_money($value): float {
    $value = trim((string)$value);
    $value = str_replace(['R$', ' ', '.'], '', $value);
    $value = str_replace(',', '.', $value);
    return max(0, (float)$value);
}

function br_money(float $value): string {
    return 'R$ ' . number_format($value, 2, ',', '.');
}

function br_number(float $value, int $decimals = 1): string {
    return number_format($value, $decimals, ',', '.');
}

function normalize_state($state): string {
    $state = strtoupper(clean_string($state, 2));
    return preg_match('/^[A-Z]{2}$/', $state) ? $state : 'GO';
}

function normalize_whatsapp_br($phone): array {
    $digits = only_digits($phone);
    if (strlen($digits) === 10 || strlen($digits) === 11) {
        $digits = '55' . $digits;
    }
    if (strlen($digits) === 12 || strlen($digits) === 13) {
        if (substr($digits, 0, 2) !== '55') {
            $digits = '55' . $digits;
        }
    }
    $valid = preg_match('/^55\d{10,11}$/', $digits) === 1;
    return [$digits, $valid];
}

function get_client_ip(): string {
    $candidates = [
        $_SERVER['HTTP_CF_CONNECTING_IP'] ?? '',
        $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '',
        $_SERVER['REMOTE_ADDR'] ?? '',
    ];
    foreach ($candidates as $candidate) {
        $candidate = trim(explode(',', $candidate)[0]);
        if (filter_var($candidate, FILTER_VALIDATE_IP)) {
            return $candidate;
        }
    }
    return '0.0.0.0';
}

function ip_hash(): string {
    $key = defined('APP_KEY') ? APP_KEY : 'quantocustasolar-local-key';
    return hash_hmac('sha256', get_client_ip(), $key);
}

function json_response(array $payload, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function read_json_body(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : $_POST;
}

function generate_lead_id(): string {
    return 'QCS-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
}

function default_settings_for_state(string $state = 'GO'): array {
    return [
        'state' => $state,
        'avg_tariff' => 1.00,
        'avg_generation_per_kwp' => 130.00,
        'residential_kwp_min_price' => 3800.00,
        'residential_kwp_max_price' => 5500.00,
        'commercial_kwp_min_price' => 3300.00,
        'commercial_kwp_max_price' => 5000.00,
        'rural_kwp_min_price' => 3600.00,
        'rural_kwp_max_price' => 5600.00,
        'industrial_kwp_min_price' => 3000.00,
        'industrial_kwp_max_price' => 4800.00,
        'default_kwp_min_price' => 3800.00,
        'default_kwp_max_price' => 5500.00,
        'savings_factor_min' => 0.75,
        'savings_factor_max' => 0.90,
    ];
}

function normalize_property_type(string $type): string {
    $type = strtolower(clean_string($type, 40));
    $map = [
        'casa' => 'residential',
        'residencial' => 'residential',
        'comercio' => 'commercial',
        'comércio' => 'commercial',
        'empresa' => 'commercial',
        'fazenda' => 'rural',
        'rural' => 'rural',
        'industria' => 'industrial',
        'indústria' => 'industrial',
        'condominio' => 'commercial',
        'condomínio' => 'commercial',
        'outro' => 'default',
    ];
    return $map[$type] ?? $type;
}

function display_property_type(string $type): string {
    $map = [
        'residential' => 'Casa / residencial',
        'commercial' => 'Comércio / empresa',
        'rural' => 'Fazenda / rural',
        'industrial' => 'Indústria',
        'default' => 'Outro',
    ];
    return $map[$type] ?? ucfirst($type);
}

function calculate_solar_estimate(array $input, array $settings): array {
    $bill = max(0, (float)($input['monthly_bill'] ?? 0));
    $type = normalize_property_type((string)($input['property_type'] ?? 'default'));
    $tariff = max(0.10, (float)($settings['avg_tariff'] ?? 1.00));
    $generation = max(50.0, (float)($settings['avg_generation_per_kwp'] ?? 130.0));
    $estimatedKwh = $bill / $tariff;
    $estimatedKwp = $estimatedKwh / $generation;
    if ($estimatedKwp < 1.5 && $bill > 0) {
        $estimatedKwp = 1.5;
    }

    $prefix = match ($type) {
        'residential' => 'residential',
        'commercial' => 'commercial',
        'rural' => 'rural',
        'industrial' => 'industrial',
        default => 'default'
    };

    $minPrice = (float)($settings[$prefix . '_kwp_min_price'] ?? $settings['default_kwp_min_price'] ?? 3800);
    $maxPrice = (float)($settings[$prefix . '_kwp_max_price'] ?? $settings['default_kwp_max_price'] ?? 5500);
    $savingsMinFactor = min(0.98, max(0.20, (float)($settings['savings_factor_min'] ?? 0.75)));
    $savingsMaxFactor = min(0.98, max($savingsMinFactor, (float)($settings['savings_factor_max'] ?? 0.90)));

    $investmentMin = $estimatedKwp * $minPrice;
    $investmentMax = $estimatedKwp * $maxPrice;
    $savingsMin = $bill * $savingsMinFactor;
    $savingsMax = $bill * $savingsMaxFactor;
    $paybackMin = ($savingsMax > 0) ? $investmentMin / ($savingsMax * 12) : 0;
    $paybackMax = ($savingsMin > 0) ? $investmentMax / ($savingsMin * 12) : 0;

    return [
        'estimated_kwh' => round($estimatedKwh, 2),
        'estimated_kwp' => round($estimatedKwp, 2),
        'investment_min' => round($investmentMin, 2),
        'investment_max' => round($investmentMax, 2),
        'savings_min' => round($savingsMin, 2),
        'savings_max' => round($savingsMax, 2),
        'payback_min' => round($paybackMin, 2),
        'payback_max' => round($paybackMax, 2),
    ];
}

function calculate_lead_score(array $data, bool $validWhatsapp): int {
    $score = 0;
    $bill = (float)($data['monthly_bill'] ?? 0);
    $ownership = strtolower((string)($data['property_ownership'] ?? ''));
    $timeline = strtolower((string)($data['installation_timeline'] ?? ''));
    $property = normalize_property_type((string)($data['property_type'] ?? ''));
    $roof = strtolower((string)($data['roof_type'] ?? ''));

    if ($bill > 1000) $score += 25;
    elseif ($bill >= 600) $score += 15;

    if (str_contains($ownership, 'proprio') || str_contains($ownership, 'próprio') || $ownership === 'sim') $score += 20;

    if (str_contains($timeline, 'imediatamente') || str_contains($timeline, '30')) $score += 20;
    elseif (str_contains($timeline, '90')) $score += 10;

    if (in_array($property, ['commercial', 'rural', 'industrial'], true)) $score += 15;
    if ($validWhatsapp) $score += 10;
    if ($roof && !str_contains($roof, 'nao') && !str_contains($roof, 'não')) $score += 5;

    return min(100, max(0, $score));
}

function classify_score(int $score): string {
    if ($score >= 80) return 'Lead quente';
    if ($score >= 60) return 'Lead bom';
    if ($score >= 40) return 'Lead médio';
    return 'Lead frio';
}

function whatsapp_link(string $phoneE164, string $message): string {
    $phone = only_digits($phoneE164);
    return 'https://wa.me/' . $phone . '?text=' . rawurlencode($message);
}

function status_label(string $status): string {
    $labels = [
        'novo' => 'Novo',
        'qualificado' => 'Qualificado',
        'enviado' => 'Enviado para parceiro',
        'negociacao' => 'Em negociação',
        'fechado' => 'Venda fechada',
        'perdido' => 'Perdido',
        'invalido' => 'Inválido',
        'ativo' => 'Ativo',
        'inativo' => 'Inativo',
    ];
    return $labels[$status] ?? ucfirst($status);
}
