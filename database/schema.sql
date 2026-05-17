CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    callsign VARCHAR(32) NOT NULL UNIQUE,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('user', 'moderator', 'admin') NOT NULL DEFAULT 'user',
    status ENUM('new', 'callsign_pending', 'callsign_verified', 'payment_pending', 'active', 'expired', 'rejected', 'blocked') NOT NULL DEFAULT 'new',
    access_expires_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS callsign_checks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    callsign VARCHAR(32) NOT NULL,
    source VARCHAR(64) NOT NULL DEFAULT 'manual',
    status ENUM('pending', 'verified', 'rejected', 'error') NOT NULL DEFAULT 'pending',
    response_json JSON NULL,
    checked_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_callsign_checks_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dmr_ids (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL UNIQUE,
    dmr_id INT UNSIGNED NOT NULL UNIQUE,
    status ENUM('reserved', 'active', 'blocked') NOT NULL DEFAULT 'reserved',
    assigned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_dmr_ids_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tariffs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL UNIQUE,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    currency CHAR(3) NOT NULL DEFAULT 'RUB',
    duration_days INT UNSIGNED NOT NULL DEFAULT 30,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    tariff_id BIGINT UNSIGNED NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency CHAR(3) NOT NULL DEFAULT 'RUB',
    provider VARCHAR(64) NOT NULL DEFAULT 'manual',
    provider_payment_id VARCHAR(190) NULL,
    confirmation_url TEXT NULL,
    status ENUM('pending', 'paid', 'failed', 'cancelled', 'refunded') NOT NULL DEFAULT 'pending',
    paid_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_payments_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_payments_tariff FOREIGN KEY (tariff_id) REFERENCES tariffs(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payment_receipts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    payment_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    receipt_reference VARCHAR(255) NOT NULL,
    comment TEXT NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    reviewed_by_user_id BIGINT UNSIGNED NULL,
    reviewed_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_payment_receipts_payment FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE,
    CONSTRAINT fk_payment_receipts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_payment_receipts_reviewer FOREIGN KEY (reviewed_by_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS access_credentials (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL UNIQUE,
    username VARCHAR(64) NOT NULL UNIQUE,
    dmr_password_hash VARCHAR(255) NOT NULL,
    dmr_password_hint VARCHAR(12) NULL,
    is_enabled TINYINT(1) NOT NULL DEFAULT 0,
    last_rotated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_access_credentials_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS server_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(120) NOT NULL UNIQUE,
    setting_value TEXT NULL,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS connection_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    callsign VARCHAR(32) NULL,
    dmr_id INT UNSIGNED NULL,
    ip_address VARCHAR(64) NULL,
    event_type VARCHAR(64) NOT NULL,
    message TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_connection_logs_created_at (created_at),
    CONSTRAINT fk_connection_logs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    actor_user_id BIGINT UNSIGNED NULL,
    action VARCHAR(120) NOT NULL,
    target_type VARCHAR(120) NULL,
    target_id BIGINT UNSIGNED NULL,
    payload_json JSON NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_logs_created_at (created_at),
    CONSTRAINT fk_audit_logs_actor FOREIGN KEY (actor_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS xlx_service_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(64) NOT NULL,
    status VARCHAR(64) NOT NULL,
    message TEXT NULL,
    payload_json JSON NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_xlx_service_events_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO tariffs (name, price, currency, duration_days)
VALUES ('Стартовый доступ 30 дней', 300.00, 'RUB', 30)
ON DUPLICATE KEY UPDATE price = VALUES(price), currency = VALUES(currency), duration_days = VALUES(duration_days);
