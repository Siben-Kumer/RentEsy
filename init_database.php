<?php
// A simple security check to prevent running this in a production environment.
// You should set this constant in your application's bootstrap file.
if (defined('APP_ENV') && APP_ENV === 'production') {
    header('HTTP/1.1 500 Internal Server Error');
    die('This script cannot be run in a production environment.');
}

// --- Database Configuration ---
$host = 'localhost';
$user = 'root';
$password = '';
$dbName = 'rentesy_db';
$sqlFile = 'database_schema.sql';

echo "<h1>Database Initialization Script</h1>";

try {
    // --- 1. Connect to MySQL Server ---
    $pdo = new PDO("mysql:host=$host", $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "<p>&#10004; Successfully connected to MySQL server.</p>";

    // --- 2. Check if database and tables already exist (Security Measure) ---
    $stmt = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$dbName'");
    if ($stmt->fetch()) {
        // Database exists, now check if tables exist to prevent accidental overwrite
        $pdo->exec("USE `$dbName`");
        $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
        if ($stmt->rowCount() > 0) {
            die("<p style='color:red;'>&#10060; <strong>Error:</strong> Database '$dbName' and its tables already seem to exist. Halting script to prevent accidental data loss. If you want to re-initialize, please drop the database manually first.</p>");
        }
    }

    // --- 3. Create the database if it doesn't exist ---
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<p>&#10004; Database '$dbName' created or already exists.</p>";

    // --- 4. Select the database ---
    $pdo->exec("USE `$dbName`");
    echo "<p>&#10004; Switched to database '$dbName'.</p>";

    // --- 5. Execute the SQL from database_schema.sql ---
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL schema file not found at '$sqlFile'. Make sure the file is in the same directory as this script.");
    }
    $sql = file_get_contents($sqlFile);
    $pdo->exec($sql);
    echo "<p>&#10004; Successfully executed SQL commands from '$sqlFile'.</p>";

    // --- 6. Check if tables were created successfully ---
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (count($tables) > 0) {
        echo "<div style='border:1px solid green; padding: 10px; margin-top:10px;'>";
        echo "<h3>&#10004; Success!</h3>";
        echo "<p>The database has been initialized. The following tables were created:</p>";
        echo "<ul>";
        foreach ($tables as $table) {
            echo "<li>$table</li>";
        }
        echo "</ul>";
        echo "</div>";
    } else {
        throw new Exception("Table creation verification failed. Although the script ran, no tables were found in the database.");
    }

} catch (PDOException $e) {
    die("<p style='color:red;'>&#10060; <strong>Database Error:</strong> " . $e->getMessage() . "</p>");
} catch (Exception $e) {
    die("<p style='color:red;'>&#10060; <strong>Error:</strong> " . $e->getMessage() . "</p>");
}

?>
