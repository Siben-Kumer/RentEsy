<?php

// Database configuration
return [
    'host' => 'localhost',
    'dbname' => 'rentesy_db',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',

    // SMTP Configuration
    'smtp_host' => 'smtp.example.com', // e.g., smtp.gmail.com
    'smtp_port' => 587,             // 587 for TLS, 465 for SSL
    'smtp_user' => 'user@example.com',
    'smtp_pass' => 'your_password_here', // For Gmail, use an App Password
    'smtp_from' => 'no-reply@example.com',
    'smtp_from_name' => 'RentEsy'
];
