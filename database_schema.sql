-- RentEsy Complete Database Schema
-- Version: 2.0 (Includes all required tables)

-- Disable foreign key checks to allow dropping tables easily
SET FOREIGN_KEY_CHECKS = 0;

-- Drop tables in reverse dependency order
DROP TABLE IF EXISTS `registration_tokens`;
DROP TABLE IF EXISTS `auth_tokens`;
DROP TABLE IF EXISTS `email_logs`;
DROP TABLE IF EXISTS `maintenance_notes`;
DROP TABLE IF EXISTS `maintenance_requests`;
DROP TABLE IF EXISTS `payments`;
DROP TABLE IF EXISTS `tenancies`;
DROP TABLE IF EXISTS `property_units`;
DROP TABLE IF EXISTS `properties`;
DROP TABLE IF EXISTS `password_resets`;
DROP TABLE IF EXISTS `users`;

SET FOREIGN_KEY_CHECKS = 1;

-- 1. Users Table
CREATE TABLE `users` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('landlord', 'tenant', 'admin') NOT NULL DEFAULT 'tenant',
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `phone` VARCHAR(20) DEFAULT NULL,
  `profile_picture` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_email` (`email`),
  INDEX `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Password Resets Table
CREATE TABLE `password_resets` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(100) NOT NULL,
  `token` VARCHAR(255) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_email` (`email`),
  INDEX `idx_token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Properties Table
CREATE TABLE `properties` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `landlord_id` INT NOT NULL,
  `name` VARCHAR(255), 
  `address` VARCHAR(255) NOT NULL,
  `city` VARCHAR(100) NOT NULL,
  `division` VARCHAR(100) NOT NULL, -- Renamed from 'state' to match code
  `zip_code` VARCHAR(20) NOT NULL,
  `image_path` VARCHAR(255) DEFAULT NULL,
  `rent_amount` DECIMAL(10, 2) NOT NULL,
  `due_date` TINYINT NOT NULL COMMENT 'Day of the month rent is due',
  `description` TEXT,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`landlord_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Property Units Table (Required for multi-unit properties)
CREATE TABLE `property_units` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `property_id` INT NOT NULL,
    `unit_number` VARCHAR(50) NOT NULL,
    `bedrooms` INT,
    `bathrooms` INT,
    `rent_amount` DECIMAL(10, 2) NOT NULL,
    `status` ENUM('available', 'occupied') NOT NULL DEFAULT 'available',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`property_id`) REFERENCES `properties`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Tenancies Table
CREATE TABLE `tenancies` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `property_id` INT NOT NULL,
  `unit_id` INT NOT NULL, -- Linked to specific unit
  `tenant_id` INT NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE,
  `status` ENUM('active', 'ended', 'pending') NOT NULL DEFAULT 'pending',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`property_id`) REFERENCES `properties`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`unit_id`) REFERENCES `property_units`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`tenant_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Payments Table
CREATE TABLE `payments` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `tenant_id` INT NOT NULL,
  `property_id` INT NOT NULL,
  `amount` DECIMAL(10, 2) NOT NULL,
  `due_date` DATE NOT NULL,
  `paid_date` DATE,
  `status` ENUM('pending', 'paid', 'overdue') NOT NULL DEFAULT 'pending',
  `payment_method` VARCHAR(50),
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`tenant_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`property_id`) REFERENCES `properties`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Maintenance Requests Table
CREATE TABLE `maintenance_requests` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `tenant_id` INT NOT NULL,
  `property_id` INT NOT NULL,
  `subject` VARCHAR(255) NOT NULL,
  `description` TEXT NOT NULL,
  `image_path` VARCHAR(255) DEFAULT NULL,
  `urgency` ENUM('low', 'medium', 'high') NOT NULL DEFAULT 'low',
  `status` ENUM('open', 'in_progress', 'resolved') NOT NULL DEFAULT 'open',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`tenant_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`property_id`) REFERENCES `properties`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. Maintenance Notes Table (For conversation history)
CREATE TABLE `maintenance_notes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `request_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `note` TEXT NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`request_id`) REFERENCES `maintenance_requests`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. Email Logs Table
CREATE TABLE `email_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `recipient` VARCHAR(255) NOT NULL,
    `subject` VARCHAR(255) NOT NULL,
    `body` TEXT,
    `status` ENUM('sent', 'failed') NOT NULL,
    `error_message` TEXT,
    `sent_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. Auth Tokens (For 'Remember Me' functionality)
CREATE TABLE `auth_tokens` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `selector` CHAR(24) NOT NULL UNIQUE,
    `token_hash` CHAR(64) NOT NULL,
    `expires` DATETIME NOT NULL,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 11. Registration Tokens (For inviting tenants)
CREATE TABLE `registration_tokens` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `token` VARCHAR(255) NOT NULL UNIQUE,
    `landlord_id` INT NOT NULL,
    `property_id` INT NOT NULL,
    `unit_id` INT DEFAULT NULL,
    `expires_at` DATETIME NOT NULL,
    `used_by_tenant_id` INT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`landlord_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`property_id`) REFERENCES `properties`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`unit_id`) REFERENCES `property_units`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`used_by_tenant_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample Data
INSERT INTO `users` (`name`, `email`, `password_hash`, `role`, `phone`, `status`, `profile_picture`) VALUES
('Landlord Larry', 'larry@rentesy.com', '$2y$10$D4B2j2R.y4q2A8z2e.f3f.oX.Z.z.z.z.z.z.z.z.z.z.z', 'landlord', '444-555-6666', 'active', NULL),
('Tenant Tina', 'tina@rentesy.com', '$2y$10$D4B2j2R.y4q2A8z2e.f3f.oX.Z.z.z.z.z.z.z.z.z.z.z', 'tenant', '777-888-9999', 'active', NULL);