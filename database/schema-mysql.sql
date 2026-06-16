-- Quanto Custa Solar v1.0
-- MySQL/MariaDB, compatível com hospedagem compartilhada KingHost.

CREATE TABLE IF NOT EXISTS leads (
  id INT AUTO_INCREMENT PRIMARY KEY,
  lead_id VARCHAR(40) NOT NULL UNIQUE,
  name VARCHAR(150) NOT NULL,
  whatsapp VARCHAR(30) NOT NULL,
  whatsapp_e164 VARCHAR(30) NOT NULL,
  city VARCHAR(120) NOT NULL,
  state CHAR(2) NOT NULL,
  property_type VARCHAR(40) NOT NULL,
  monthly_bill DECIMAL(10,2) NOT NULL,
  estimated_kwh DECIMAL(10,2) NOT NULL,
  estimated_kwp DECIMAL(10,2) NOT NULL,
  investment_min DECIMAL(12,2) NOT NULL,
  investment_max DECIMAL(12,2) NOT NULL,
  savings_min DECIMAL(10,2) NOT NULL,
  savings_max DECIMAL(10,2) NOT NULL,
  payback_min DECIMAL(10,2) NOT NULL,
  payback_max DECIMAL(10,2) NOT NULL,
  roof_type VARCHAR(80) NOT NULL,
  property_ownership VARCHAR(60) NOT NULL,
  installation_timeline VARCHAR(80) NOT NULL,
  score INT NOT NULL DEFAULT 0,
  classification VARCHAR(40) NOT NULL,
  status VARCHAR(40) NOT NULL DEFAULT 'novo',
  source VARCHAR(120) DEFAULT 'site',
  utm_source VARCHAR(120) NULL,
  utm_campaign VARCHAR(120) NULL,
  utm_medium VARCHAR(120) NULL,
  utm_content VARCHAR(120) NULL,
  utm_term VARCHAR(120) NULL,
  ip_hash VARCHAR(100) NULL,
  user_agent TEXT NULL,
  notes TEXT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_leads_created_at (created_at),
  INDEX idx_leads_state_city (state, city),
  INDEX idx_leads_status (status),
  INDEX idx_leads_score (score),
  INDEX idx_leads_whatsapp (whatsapp_e164)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS partners (
  id INT AUTO_INCREMENT PRIMARY KEY,
  company_name VARCHAR(180) NOT NULL,
  responsible_name VARCHAR(150) NOT NULL,
  whatsapp VARCHAR(30) NOT NULL,
  whatsapp_e164 VARCHAR(30) NOT NULL,
  email VARCHAR(180) NOT NULL,
  cnpj VARCHAR(30) NULL,
  base_city VARCHAR(120) NOT NULL,
  base_state CHAR(2) NOT NULL,
  service_states TEXT NULL,
  service_radius_km INT DEFAULT 150,
  accepts_residential TINYINT(1) DEFAULT 1,
  accepts_commercial TINYINT(1) DEFAULT 1,
  accepts_rural TINYINT(1) DEFAULT 0,
  accepts_industrial TINYINT(1) DEFAULT 0,
  monthly_lead_limit INT DEFAULT 20,
  plan_name VARCHAR(80) DEFAULT 'validação',
  status VARCHAR(40) DEFAULT 'novo',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_partners_state (base_state),
  INDEX idx_partners_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS lead_assignments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  lead_id INT NOT NULL,
  partner_id INT NOT NULL,
  assignment_type VARCHAR(40) DEFAULT 'manual',
  exclusive TINYINT(1) DEFAULT 0,
  sent_at DATETIME NULL,
  partner_status VARCHAR(40) DEFAULT 'enviado',
  partner_notes TEXT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_assignment_lead (lead_id),
  INDEX idx_assignment_partner (partner_id),
  CONSTRAINT fk_assignment_lead FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE,
  CONSTRAINT fk_assignment_partner FOREIGN KEY (partner_id) REFERENCES partners(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS simulator_settings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  state CHAR(2) NOT NULL UNIQUE,
  avg_tariff DECIMAL(8,4) NOT NULL DEFAULT 1.0000,
  avg_generation_per_kwp DECIMAL(8,2) NOT NULL DEFAULT 130.00,
  residential_kwp_min_price DECIMAL(10,2) NOT NULL DEFAULT 3800.00,
  residential_kwp_max_price DECIMAL(10,2) NOT NULL DEFAULT 5500.00,
  commercial_kwp_min_price DECIMAL(10,2) NOT NULL DEFAULT 3300.00,
  commercial_kwp_max_price DECIMAL(10,2) NOT NULL DEFAULT 5000.00,
  rural_kwp_min_price DECIMAL(10,2) NOT NULL DEFAULT 3600.00,
  rural_kwp_max_price DECIMAL(10,2) NOT NULL DEFAULT 5600.00,
  industrial_kwp_min_price DECIMAL(10,2) NOT NULL DEFAULT 3000.00,
  industrial_kwp_max_price DECIMAL(10,2) NOT NULL DEFAULT 4800.00,
  default_kwp_min_price DECIMAL(10,2) NOT NULL DEFAULT 3800.00,
  default_kwp_max_price DECIMAL(10,2) NOT NULL DEFAULT 5500.00,
  savings_factor_min DECIMAL(5,4) NOT NULL DEFAULT 0.7500,
  savings_factor_max DECIMAL(5,4) NOT NULL DEFAULT 0.9000,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(180) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role VARCHAR(40) DEFAULT 'admin',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  last_login_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS activity_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  admin_user_id INT NULL,
  action VARCHAR(120) NOT NULL,
  entity_type VARCHAR(80) NULL,
  entity_id VARCHAR(80) NULL,
  description TEXT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_activity_created (created_at),
  INDEX idx_activity_admin (admin_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rate_limits (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ip_hash VARCHAR(100) NOT NULL,
  action VARCHAR(80) NOT NULL,
  attempts INT DEFAULT 1,
  window_start DATETIME NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_rate_limit (ip_hash, action),
  INDEX idx_rate_window (window_start)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
