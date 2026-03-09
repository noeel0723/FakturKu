-- =====================================================
-- FakturKu - Migration 001: Initial Schema
-- Sistem Faktur & Tagihan UMKM dengan Multi-Currency
-- =====================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------
-- Table: currencies
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `currencies` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(3) NOT NULL UNIQUE,
    `symbol` VARCHAR(10) NOT NULL DEFAULT '',
    `name` VARCHAR(100) NOT NULL DEFAULT '',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- Table: exchange_rates
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `exchange_rates` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `base_currency` VARCHAR(3) NOT NULL,
    `target_currency` VARCHAR(3) NOT NULL,
    `rate` DECIMAL(18,8) NOT NULL,
    `fetched_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_exchange_pair` (`base_currency`, `target_currency`),
    INDEX `idx_fetched_at` (`fetched_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- Table: clients
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `clients` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(50) DEFAULT NULL,
    `address` TEXT DEFAULT NULL,
    `company` VARCHAR(255) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_clients_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- Table: products
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `products` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `unit_price` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `currency` VARCHAR(3) NOT NULL DEFAULT 'IDR',
    `unit` VARCHAR(50) NOT NULL DEFAULT 'pcs',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- Table: invoice_sequences (for atomic numbering)
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `invoice_sequences` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `prefix` VARCHAR(20) NOT NULL,
    `year` SMALLINT UNSIGNED NOT NULL,
    `month` TINYINT UNSIGNED NOT NULL,
    `last_number` INT UNSIGNED NOT NULL DEFAULT 0,
    UNIQUE KEY `uk_prefix_year_month` (`prefix`, `year`, `month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- Table: invoices
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `invoices` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `invoice_number` VARCHAR(50) NOT NULL UNIQUE,
    `client_id` INT UNSIGNED NOT NULL,
    `currency` VARCHAR(3) NOT NULL DEFAULT 'IDR',
    `exchange_rate` DECIMAL(18,8) DEFAULT NULL COMMENT 'Rate to base currency at issue time',
    `subtotal` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `tax_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `tax_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `discount_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `total` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `total_in_base` DECIMAL(15,2) DEFAULT NULL COMMENT 'Total converted to base currency',
    `amount_paid` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `status` ENUM('draft','sent','paid','partially_paid','overdue','cancelled') NOT NULL DEFAULT 'draft',
    `issue_date` DATE NOT NULL,
    `due_date` DATE NOT NULL,
    `notes` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_invoices_status` (`status`),
    INDEX `idx_invoices_client` (`client_id`),
    INDEX `idx_invoices_due_date` (`due_date`),
    CONSTRAINT `fk_invoices_client` FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- Table: invoice_items
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `invoice_items` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `invoice_id` INT UNSIGNED NOT NULL,
    `product_id` INT UNSIGNED DEFAULT NULL,
    `description` VARCHAR(500) NOT NULL DEFAULT '',
    `quantity` DECIMAL(10,2) NOT NULL DEFAULT 1.00,
    `unit` VARCHAR(50) NOT NULL DEFAULT 'pcs',
    `unit_price` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `sort_order` INT NOT NULL DEFAULT 0,
    CONSTRAINT `fk_items_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_items_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- Table: payments
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `payments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `invoice_id` INT UNSIGNED NOT NULL,
    `currency` VARCHAR(3) NOT NULL DEFAULT 'IDR',
    `amount` DECIMAL(15,2) NOT NULL COMMENT 'Amount in payment currency',
    `amount_in_base` DECIMAL(15,2) DEFAULT NULL COMMENT 'Amount converted to base currency',
    `exchange_rate` DECIMAL(18,8) DEFAULT NULL COMMENT 'Rate used for conversion',
    `provider` VARCHAR(50) DEFAULT 'manual' COMMENT 'stripe, midtrans, manual',
    `provider_payment_id` VARCHAR(255) DEFAULT NULL,
    `idempotency_key` VARCHAR(255) DEFAULT NULL,
    `status` ENUM('pending','success','failed','refunded') NOT NULL DEFAULT 'pending',
    `payment_method` VARCHAR(100) DEFAULT NULL,
    `payment_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `notes` TEXT DEFAULT NULL,
    `raw_response` JSON DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_idempotency` (`idempotency_key`),
    INDEX `idx_payments_invoice` (`invoice_id`),
    INDEX `idx_payments_provider_id` (`provider_payment_id`),
    INDEX `idx_payments_status` (`status`),
    CONSTRAINT `fk_payments_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- Table: audit_logs
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `entity_type` VARCHAR(50) NOT NULL COMMENT 'invoice, payment, client, etc.',
    `entity_id` INT UNSIGNED NOT NULL,
    `action` VARCHAR(50) NOT NULL COMMENT 'created, updated, status_changed, payment_received, etc.',
    `old_value` JSON DEFAULT NULL,
    `new_value` JSON DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `user_agent` VARCHAR(500) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_audit_entity` (`entity_type`, `entity_id`),
    INDEX `idx_audit_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- Seed: default currencies
-- ---------------------------------------------------
INSERT INTO `currencies` (`code`, `symbol`, `name`) VALUES
    ('IDR', 'Rp', 'Indonesian Rupiah'),
    ('USD', '$', 'US Dollar'),
    ('EUR', '€', 'Euro'),
    ('SGD', 'S$', 'Singapore Dollar'),
    ('MYR', 'RM', 'Malaysian Ringgit'),
    ('JPY', '¥', 'Japanese Yen'),
    ('GBP', '£', 'British Pound'),
    ('AUD', 'A$', 'Australian Dollar')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

SET FOREIGN_KEY_CHECKS = 1;
