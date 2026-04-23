-- Tabla para blacklist de tokens revocados
CREATE TABLE IF NOT EXISTS `token_blacklist` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `token_jti` VARCHAR(255) NOT NULL UNIQUE COMMENT 'JWT Token ID (jti claim)',
    `token_hash` VARCHAR(255) NOT NULL UNIQUE COMMENT 'Hash del token para búsqueda rápida',
    `user_id` BIGINT UNSIGNED NOT NULL,
    `revoked_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `reason` VARCHAR(255) NULL COMMENT 'Razón de revocación',
    `expires_at` TIMESTAMP NOT NULL COMMENT 'Fecha de expiración del token original',
    INDEX `idx_jti` (`token_jti`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla para almacenar refresh tokens
CREATE TABLE IF NOT EXISTS `refresh_tokens` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `token_hash` VARCHAR(255) NOT NULL UNIQUE COMMENT 'Hash del refresh token',
    `issued_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `expires_at` TIMESTAMP NOT NULL,
    `revoked_at` TIMESTAMP NULL COMMENT 'Null si está activo',
    `ip_address` VARCHAR(45) NULL COMMENT 'IP que emitió el token',
    `user_agent` TEXT NULL COMMENT 'User agent del cliente',
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_token_hash` (`token_hash`),
    INDEX `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla mejorada para api_tokens (apps de terceros)
CREATE TABLE IF NOT EXISTS `api_tokens` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `app_name` VARCHAR(255) NOT NULL UNIQUE,
    `token` VARCHAR(255) NOT NULL UNIQUE,
    `token_hash` VARCHAR(255) NOT NULL UNIQUE COMMENT 'Hash para búsqueda rápida',
    `description` TEXT NULL,
    `is_active` BOOLEAN DEFAULT TRUE,
    `rate_limit` INT DEFAULT 1000 COMMENT 'Requests por hora',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `expires_at` TIMESTAMP NULL,
    `last_used_at` TIMESTAMP NULL,
    `created_by` BIGINT UNSIGNED NULL COMMENT 'Usuario que creó el token',
    INDEX `idx_token` (`token`),
    INDEX `idx_token_hash` (`token_hash`),
    INDEX `idx_app_name` (`app_name`),
    INDEX `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla para tracking de rate limiting
CREATE TABLE IF NOT EXISTS `rate_limit_logs` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `identifier` VARCHAR(255) NOT NULL COMMENT 'IP o user_id',
    `endpoint` VARCHAR(255) NOT NULL,
    `request_count` INT DEFAULT 1,
    `reset_at` TIMESTAMP NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_limit` (`identifier`, `endpoint`, `reset_at`),
    INDEX `idx_reset_at` (`reset_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
