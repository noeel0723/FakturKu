-- =====================================================
-- FakturKu - Migration 002: Advanced Billing Features
-- =====================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(120) NOT NULL,
    `email` VARCHAR(190) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) DEFAULT NULL,
    `role` ENUM('owner','finance','staff') NOT NULL DEFAULT 'staff',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tax_profiles` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(120) NOT NULL,
    `tax_type` ENUM('vat','withholding','service_tax','other') NOT NULL DEFAULT 'vat',
    `calculation_method` ENUM('percentage','fixed') NOT NULL DEFAULT 'percentage',
    `rate` DECIMAL(10,4) NOT NULL DEFAULT 0,
    `fixed_amount` DECIMAL(15,2) DEFAULT NULL,
    `is_compound` TINYINT(1) NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `invoice_tax_lines` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `invoice_id` INT UNSIGNED NOT NULL,
    `tax_profile_id` INT UNSIGNED NOT NULL,
    `tax_name` VARCHAR(120) NOT NULL,
    `tax_rate` DECIMAL(10,4) DEFAULT NULL,
    `tax_amount` DECIMAL(15,2) NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_invoice_tax_invoice` (`invoice_id`),
    CONSTRAINT `fk_invoice_tax_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_invoice_tax_profile` FOREIGN KEY (`tax_profile_id`) REFERENCES `tax_profiles`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `credit_notes` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `credit_note_number` VARCHAR(60) NOT NULL UNIQUE,
    `invoice_id` INT UNSIGNED NOT NULL,
    `client_id` INT UNSIGNED NOT NULL,
    `currency` VARCHAR(3) NOT NULL DEFAULT 'IDR',
    `amount` DECIMAL(15,2) NOT NULL,
    `reason` TEXT DEFAULT NULL,
    `status` ENUM('draft','approved','applied','cancelled') NOT NULL DEFAULT 'draft',
    `issued_at` DATE NOT NULL,
    `applied_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_credit_invoice` (`invoice_id`),
    CONSTRAINT `fk_credit_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices`(`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_credit_client` FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `recurring_templates` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `template_name` VARCHAR(160) NOT NULL,
    `client_id` INT UNSIGNED NOT NULL,
    `currency` VARCHAR(3) NOT NULL DEFAULT 'IDR',
    `frequency` ENUM('weekly','monthly','quarterly','yearly') NOT NULL DEFAULT 'monthly',
    `start_date` DATE NOT NULL,
    `next_issue_date` DATE NOT NULL,
    `tax_rate` DECIMAL(5,2) NOT NULL DEFAULT 0,
    `discount_amount` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `notes` TEXT DEFAULT NULL,
    `status` ENUM('active','paused','ended') NOT NULL DEFAULT 'active',
    `items_json` JSON NOT NULL,
    `last_generated_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_recurring_next_issue` (`next_issue_date`),
    CONSTRAINT `fk_recurring_client` FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `quotes` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `quote_number` VARCHAR(60) NOT NULL UNIQUE,
    `client_id` INT UNSIGNED NOT NULL,
    `currency` VARCHAR(3) NOT NULL DEFAULT 'IDR',
    `subtotal` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `tax_rate` DECIMAL(5,2) NOT NULL DEFAULT 0,
    `tax_amount` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `discount_amount` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `total` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `status` ENUM('draft','sent','accepted','rejected','converted') NOT NULL DEFAULT 'draft',
    `valid_until` DATE DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `converted_invoice_id` INT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_quote_client` FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `quote_items` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `quote_id` INT UNSIGNED NOT NULL,
    `description` VARCHAR(500) NOT NULL,
    `quantity` DECIMAL(10,2) NOT NULL DEFAULT 1,
    `unit` VARCHAR(50) NOT NULL DEFAULT 'pcs',
    `unit_price` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `amount` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_quote_items_quote` FOREIGN KEY (`quote_id`) REFERENCES `quotes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `reminder_logs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `invoice_id` INT UNSIGNED NOT NULL,
    `client_id` INT UNSIGNED NOT NULL,
    `channel` ENUM('email','whatsapp') NOT NULL,
    `reminder_type` ENUM('before_7','before_1','after_3') NOT NULL,
    `sent_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `status` ENUM('sent','failed','skipped') NOT NULL DEFAULT 'sent',
    `response_text` TEXT DEFAULT NULL,
    UNIQUE KEY `uk_reminder_once` (`invoice_id`, `channel`, `reminder_type`),
    CONSTRAINT `fk_reminder_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_reminder_client` FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `reconciliation_records` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `invoice_id` INT UNSIGNED DEFAULT NULL,
    `payment_id` INT UNSIGNED DEFAULT NULL,
    `provider` VARCHAR(50) DEFAULT NULL,
    `provider_reference` VARCHAR(255) DEFAULT NULL,
    `ledger_amount` DECIMAL(15,2) DEFAULT NULL,
    `gateway_amount` DECIMAL(15,2) DEFAULT NULL,
    `bank_amount` DECIMAL(15,2) DEFAULT NULL,
    `variance_amount` DECIMAL(15,2) DEFAULT NULL,
    `status` ENUM('matched','mismatch','pending_review') NOT NULL DEFAULT 'pending_review',
    `note` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_recon_status` (`status`),
    CONSTRAINT `fk_recon_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_recon_payment` FOREIGN KEY (`payment_id`) REFERENCES `payments`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `attachments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `entity_type` ENUM('invoice','payment','credit_note','quote') NOT NULL,
    `entity_id` INT UNSIGNED NOT NULL,
    `file_name` VARCHAR(255) NOT NULL,
    `file_path` VARCHAR(500) NOT NULL,
    `mime_type` VARCHAR(120) DEFAULT NULL,
    `file_size` INT UNSIGNED DEFAULT NULL,
    `uploaded_by_role` VARCHAR(30) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_attachment_entity` (`entity_type`, `entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `export_logs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `export_type` VARCHAR(60) NOT NULL,
    `format` ENUM('csv','xlsx') NOT NULL,
    `filters_json` JSON DEFAULT NULL,
    `downloaded_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `downloaded_by_role` VARCHAR(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
