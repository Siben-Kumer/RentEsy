<?php

// Load database configuration
$config = require __DIR__ . '/config.php';

$host = $config['host'];
$db_name = $config['dbname'];
$username = $config['username'];
$password = $config['password'];
$charset = $config['charset'];

try {
    // Connect to MySQL server without specifying a database
    /*
    $pdo_temp = new PDO("mysql:host=$host", $username, $password);
    $pdo_temp->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create the database if it doesn't exist
    $pdo_temp->exec("CREATE DATABASE IF NOT EXISTS `$db_name`");
    */

    // Now connect to the newly created (or existing) database
    $dsn = "mysql:host=$host;dbname=$db_name;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, $username, $password, $options);

    /**
     * Checks if the required tables exist and creates them if they do not.
     *
     * @param PDO $pdo The PDO database connection object.
     * @return void
     */
    function createTablesIfNeeded(PDO $pdo): void
    {
        $commands = [
            'CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(100) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                role ENUM("landlord", "tenant") NOT NULL,
                status ENUM("active", "inactive") NOT NULL DEFAULT "active",
                phone VARCHAR(20) DEFAULT NULL,
                verification_token VARCHAR(64) DEFAULT NULL,
                is_verified TINYINT(1) DEFAULT 1,
                profile_picture VARCHAR(255) DEFAULT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )',
            'CREATE TABLE IF NOT EXISTS properties (
                id INT NOT NULL AUTO_INCREMENT,
                landlord_id INT NOT NULL,
                address VARCHAR(255) NOT NULL,
                city VARCHAR(100) NOT NULL,
                division VARCHAR(100) NOT NULL,
                zip_code VARCHAR(20) NOT NULL,
                image_path VARCHAR(255) DEFAULT NULL,
                rent_amount DECIMAL(10, 2) NOT NULL,
                due_date TINYINT NOT NULL COMMENT "Day of the month rent is due",
                description TEXT,
                status ENUM("active", "inactive") NOT NULL DEFAULT "active",
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                FOREIGN KEY (`landlord_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
            )',
            'CREATE TABLE IF NOT EXISTS property_units (
                id INT AUTO_INCREMENT PRIMARY KEY,
                property_id INT NOT NULL,
                unit_number VARCHAR(50) NOT NULL,
                bedrooms INT,
                bathrooms INT,
                rent_amount DECIMAL(10, 2) NOT NULL,
                status ENUM("available", "occupied") NOT NULL DEFAULT "available",
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
            )',
            'CREATE TABLE IF NOT EXISTS tenancies (
                id INT NOT NULL AUTO_INCREMENT,
                property_id INT NOT NULL,
                unit_id INT NOT NULL,
                tenant_id INT NOT NULL,
                start_date DATE NOT NULL,
                end_date DATE,
                status ENUM("active", "ended", "pending") NOT NULL DEFAULT "pending",
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                FOREIGN KEY (`property_id`) REFERENCES `properties`(`id`) ON DELETE CASCADE,
                FOREIGN KEY (`unit_id`) REFERENCES `property_units`(`id`) ON DELETE CASCADE,
                FOREIGN KEY (`tenant_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
            )',
            'CREATE TABLE IF NOT EXISTS payments (
                id INT NOT NULL AUTO_INCREMENT,
                tenant_id INT NOT NULL,
                property_id INT NOT NULL,
                amount DECIMAL(10, 2) NOT NULL,
                due_date DATE NOT NULL,
                paid_date DATE,
                status ENUM("pending", "paid", "overdue") NOT NULL DEFAULT "pending",
                payment_method VARCHAR(50),
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                FOREIGN KEY (`tenant_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
                FOREIGN KEY (`property_id`) REFERENCES `properties`(`id`) ON DELETE CASCADE
            )',
            'CREATE TABLE IF NOT EXISTS maintenance_requests (
                id INT NOT NULL AUTO_INCREMENT,
                tenant_id INT NOT NULL,
                property_id INT NOT NULL,
                subject VARCHAR(255) NOT NULL,
                description TEXT NOT NULL,
                image_path VARCHAR(255) DEFAULT NULL,
                urgency ENUM("low", "medium", "high") NOT NULL DEFAULT "low",
                status ENUM("open", "in_progress", "resolved") NOT NULL DEFAULT "open",
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                FOREIGN KEY (`tenant_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
                FOREIGN KEY (`property_id`) REFERENCES `properties`(`id`) ON DELETE CASCADE
            )',
            'CREATE TABLE IF NOT EXISTS maintenance_notes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                request_id INT NOT NULL,
                user_id INT NOT NULL,
                note TEXT NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (request_id) REFERENCES maintenance_requests(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )',
            'CREATE TABLE IF NOT EXISTS email_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                recipient VARCHAR(255) NOT NULL,
                subject VARCHAR(255) NOT NULL,
                body TEXT,
                status ENUM("sent", "failed") NOT NULL,
                error_message TEXT,
                sent_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            )',
            'CREATE TABLE IF NOT EXISTS auth_tokens (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                selector CHAR(24) NOT NULL UNIQUE,
                token_hash CHAR(64) NOT NULL,
                expires DATETIME NOT NULL,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )',
            'CREATE TABLE IF NOT EXISTS registration_tokens (
                id INT AUTO_INCREMENT PRIMARY KEY,
                token VARCHAR(255) NOT NULL UNIQUE,
                landlord_id INT NOT NULL,
                property_id INT NOT NULL,
                unit_id INT DEFAULT NULL,
                expires_at DATETIME NOT NULL,
                used_by_tenant_id INT DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (landlord_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
                FOREIGN KEY (unit_id) REFERENCES property_units(id) ON DELETE SET NULL,
                FOREIGN KEY (used_by_tenant_id) REFERENCES users(id) ON DELETE SET NULL
            )'
        ];

        // Execute each command
        foreach ($commands as $command) {
            $pdo->exec($command);
        }
    }

    // Create tables if they don't exist
    // createTablesIfNeeded($pdo);

    // --- Schema Migration: Add image_path column if it doesn't exist ---
    /*
    $stmt_check_column = $pdo->query("SHOW COLUMNS FROM `properties` LIKE 'image_path'");
    if ($stmt_check_column->rowCount() == 0) {
        $pdo->exec("ALTER TABLE `properties` ADD COLUMN `image_path` VARCHAR(255) DEFAULT NULL AFTER `zip_code`");
    }

    // --- Schema Migration: Add profile_picture column to users if it doesn't exist ---
    $stmt_check_user_column = $pdo->query("SHOW COLUMNS FROM `users` LIKE 'profile_picture'");
    if ($stmt_check_user_column->rowCount() == 0) {
        $pdo->exec("ALTER TABLE `users` ADD COLUMN `profile_picture` VARCHAR(255) DEFAULT NULL AFTER `phone`");
    }

    // --- Schema Migration: Create property_units table if it doesn't exist ---
    $pdo->exec('CREATE TABLE IF NOT EXISTS property_units (
        id INT AUTO_INCREMENT PRIMARY KEY,
        property_id INT NOT NULL,
        unit_number VARCHAR(50) NOT NULL,
        bedrooms INT,
        bathrooms INT,
        rent_amount DECIMAL(10, 2) NOT NULL,
        status ENUM("available", "occupied") NOT NULL DEFAULT "available",
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
    )');

    // --- Schema Migration: Remove 'admin' role and migrate existing admins to 'landlord' ---
    // First, update any existing 'admin' users to 'landlord'
    $pdo->exec("UPDATE users SET role = 'landlord' WHERE role = 'admin'");
    // Then, modify the table structure to remove the 'admin' option from the ENUM
    $pdo->exec("ALTER TABLE users MODIFY role ENUM('landlord', 'tenant') NOT NULL");

    // --- Schema Migration: Rename 'state' column to 'division' in 'properties' table ---
    $stmt_check_state_column = $pdo->query("SHOW COLUMNS FROM `properties` LIKE 'state'");
    if ($stmt_check_state_column->rowCount() > 0) {
        $pdo->exec("ALTER TABLE `properties` CHANGE COLUMN `state` `division` VARCHAR(100) NOT NULL");
    }

    // --- Schema Migration: Add 'unit_id' to 'tenancies' table ---
    $stmt_check_unit_id_column = $pdo->query("SHOW COLUMNS FROM `tenancies` LIKE 'unit_id'");
    if ($stmt_check_unit_id_column->rowCount() == 0) {
        // Add the column first, allowing NULL temporarily to handle existing rows
        $pdo->exec("ALTER TABLE `tenancies` ADD COLUMN `unit_id` INT NULL AFTER `property_id`");
        // Note: In a real-world scenario with existing data, you would need a strategy
        // to populate the new unit_id column for existing tenancies before setting it to NOT NULL.
        // For this project, we'll assume new tenancies will populate it correctly.
        // We will now alter it to be NOT NULL for future entries.
        // $pdo->exec("ALTER TABLE tenancies MODIFY unit_id INT NOT NULL");

        // Add the foreign key constraint
        // We will check if the constraint already exists
        $stmt_check_fk = $pdo->query("
            SELECT CONSTRAINT_NAME 
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
            WHERE TABLE_NAME = 'tenancies' 
              AND COLUMN_NAME = 'unit_id' 
              AND REFERENCED_TABLE_NAME = 'property_units'
        ");
        if ($stmt_check_fk->rowCount() == 0) {
            $pdo->exec("ALTER TABLE `tenancies` ADD CONSTRAINT `fk_tenancies_unit_id` FOREIGN KEY (`unit_id`) REFERENCES `property_units`(`id`) ON DELETE CASCADE");
        }
    }

    // --- Schema Migration: Add 'status' to 'users' table ---
    $stmt_check_status_column = $pdo->query("SHOW COLUMNS FROM `users` LIKE 'status'");
    if ($stmt_check_status_column->rowCount() == 0) {
        $pdo->exec("ALTER TABLE `users` ADD COLUMN `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active' AFTER `role`");
    }

    // --- Schema Migration: Add 'image_path' to 'maintenance_requests' table ---
    $stmt_check_mr_image_column = $pdo->query("SHOW COLUMNS FROM `maintenance_requests` LIKE 'image_path'");
    if ($stmt_check_mr_image_column->rowCount() == 0) {
        $pdo->exec("ALTER TABLE `maintenance_requests` ADD COLUMN `image_path` VARCHAR(255) DEFAULT NULL AFTER `description`");
    }

    // --- Schema Migration: Add 'urgency' to 'maintenance_requests' table ---
    $stmt_check_mr_urgency_column = $pdo->query("SHOW COLUMNS FROM `maintenance_requests` LIKE 'urgency'");
    if ($stmt_check_mr_urgency_column->rowCount() == 0) {
        $pdo->exec("ALTER TABLE `maintenance_requests` ADD COLUMN `urgency` ENUM('low', 'medium', 'high') NOT NULL DEFAULT 'low' AFTER `image_path`");
    }
    */

    // --- Schema Migration: Add transaction_id to payments table ---
    $stmt_check_transaction_id = $pdo->query("SHOW COLUMNS FROM `payments` LIKE 'transaction_id'");
    if ($stmt_check_transaction_id->rowCount() == 0) {
        $pdo->exec("ALTER TABLE `payments` ADD COLUMN `transaction_id` VARCHAR(255) DEFAULT NULL AFTER `payment_method`");
    }

    // --- Schema Migration: Update status ENUM in payments table ---
    $stmt_check_status_enum = $pdo->query("SHOW COLUMNS FROM `payments` LIKE 'status'");
    $status_row = $stmt_check_status_enum->fetch(PDO::FETCH_ASSOC);
    if (strpos($status_row['Type'], "'pending_approval'") === false) {
         $pdo->exec("ALTER TABLE `payments` MODIFY COLUMN `status` ENUM('pending', 'paid', 'overdue', 'pending_approval') NOT NULL DEFAULT 'pending'");
    }

    // --- Schema Migration: Create notices table ---
    $pdo->exec("CREATE TABLE IF NOT EXISTS `notices` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `landlord_id` INT NOT NULL,
        `title` VARCHAR(255) NOT NULL,
        `message` TEXT NOT NULL,
        `type` ENUM('all', 'selected') NOT NULL DEFAULT 'all',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`landlord_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // --- Schema Migration: Create notice_targets table ---
    $pdo->exec("CREATE TABLE IF NOT EXISTS `notice_targets` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `notice_id` INT NOT NULL,
        `property_id` INT NOT NULL,
        FOREIGN KEY (`notice_id`) REFERENCES `notices`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`property_id`) REFERENCES `properties`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // --- Schema Migration: Add verification columns to users table ---
    $stmt_check_verif_token = $pdo->query("SHOW COLUMNS FROM `users` LIKE 'verification_token'");
    if ($stmt_check_verif_token->rowCount() == 0) {
        $pdo->exec("ALTER TABLE `users` ADD COLUMN `verification_token` VARCHAR(64) DEFAULT NULL AFTER `phone` ");
    }

    $stmt_check_is_verified = $pdo->query("SHOW COLUMNS FROM `users` LIKE 'is_verified'");
    if ($stmt_check_is_verified->rowCount() == 0) {
        $pdo->exec("ALTER TABLE `users` ADD COLUMN `is_verified` TINYINT(1) DEFAULT 1 AFTER `verification_token` ");
    }

    // --- Schema Migration: Add expiry_date to notices table ---
    $stmt_check_notice_expiry = $pdo->query("SHOW COLUMNS FROM `notices` LIKE 'expiry_date'");
    if ($stmt_check_notice_expiry->rowCount() == 0) {
        $pdo->exec("ALTER TABLE `notices` ADD COLUMN `expiry_date` DATE DEFAULT NULL AFTER `type`");
    }

} catch (PDOException $e) {
    // Log the error securely
    error_log("Database Error: " . $e->getMessage());
    // Show a generic message to the user
    die("A database error occurred. Please try again later.");
}

// The $pdo object can now be used by including this file in other PHP scripts.
// echo "Database connection successful and tables are ready.";