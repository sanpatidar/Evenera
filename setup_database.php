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

    // Create contact_messages table
    $conn->exec("CREATE TABLE IF NOT EXISTS contact_messages (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT,
        admin_id INT NOT NULL,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        is_read BOOLEAN DEFAULT FALSE,
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

    // Clear existing data
    $conn->exec("DELETE FROM contact_messages");
    $conn->exec("DELETE FROM admin_credentials");
    $conn->exec("DELETE FROM admins");
    
    foreach ($admins as $admin) {
        // Insert admin
        $stmt = $conn->prepare("INSERT INTO admins (name, email, role) VALUES (?, ?, ?)");
        $stmt->execute([$admin['name'], $admin['email'], $admin['role']]);
        
        // Get admin id
        $adminId = $conn->lastInsertId();
        
        // Create admin credentials
        // Username is first part of email before @
        $username = strtolower(explode('@', $admin['email'])[0]);
        // Password is "admin123"
        $password = password_hash('admin123', PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("INSERT INTO admin_credentials (admin_id, username, password) VALUES (?, ?, ?)");
        $stmt->execute([$adminId, $username, $password]);

        // Add some sample contact messages
        if ($adminId == 1) {  // Only add sample messages for the first admin
            $sampleMessages = [
                [
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                    'message' => 'Hello, I need help with event planning.',
                    'is_read' => true
                ],
                [
                    'name' => 'Jane Smith',
                    'email' => 'jane@example.com',
                    'message' => 'Interested in wedding planning services.',
                    'is_read' => false
                ]
            ];

            foreach ($sampleMessages as $message) {
                $stmt = $conn->prepare("INSERT INTO contact_messages (admin_id, name, email, message, is_read) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$adminId, $message['name'], $message['email'], $message['message'], $message['is_read']]);
            }
        }
    }

    echo "Database setup completed successfully!\n\n";
    echo "You can now log in with the following credentials:\n";
    echo "1. System Administrator:\n";
    echo "   Username: admin\n";
    echo "   Password: admin123\n\n";
    echo "2. Customer Support:\n";
    echo "   Username: support\n";
    echo "   Password: admin123\n\n";
    echo "3. Event Management:\n";
    echo "   Username: events\n";
    echo "   Password: admin123\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
} 