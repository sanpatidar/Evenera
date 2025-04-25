<?php
require_once 'includes/db_connection.php';

try {
    // Create admins table
    $conn->exec("CREATE TABLE IF NOT EXISTS admins (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        role VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Create admin_credentials table
    $conn->exec("CREATE TABLE IF NOT EXISTS admin_credentials (
        id INT PRIMARY KEY AUTO_INCREMENT,
        admin_id INT NOT NULL,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (admin_id) REFERENCES admins(id)
    )");

    // Insert default admin users
    $admins = [
        [
            'name' => 'Atishay Sodhiya',
            'email' => 'admin@evenera.com',
            'role' => 'System Administrator'
        ],
        [
            'name' => 'Sanskar Patidar',
            'email' => 'support@evenera.com',
            'role' => 'Customer Support'
        ],
        [
            'name' => 'Nitish Pandey',
            'email' => 'events@evenera.com',
            'role' => 'Event Management'
        ]
    ];

    foreach ($admins as $admin) {
        // Check if admin already exists
        $stmt = $conn->prepare("SELECT id FROM admins WHERE email = ?");
        $stmt->execute([$admin['email']]);
        $existing = $stmt->fetch();

        if (!$existing) {
            // Insert admin
            $stmt = $conn->prepare("INSERT INTO admins (name, email, role) VALUES (?, ?, ?)");
            $stmt->execute([$admin['name'], $admin['email'], $admin['role']]);
            
            // Get admin id
            $adminId = $conn->lastInsertId();
            
            // Create admin credentials
            // Username is first part of email before @
            $username = strtolower(explode('@', $admin['email'])[0]);
            // Default password is "admin123"
            $password = password_hash('admin123', PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("INSERT INTO admin_credentials (admin_id, username, password) VALUES (?, ?, ?)");
            $stmt->execute([$adminId, $username, $password]);
        }
    }

    echo "Admin setup completed successfully!";
    echo "\n\nDefault login credentials:";
    echo "\nUsername: admin";
    echo "\nPassword: admin123";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 