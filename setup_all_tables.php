<?php
require_once 'includes/db_connection.php';

try {
    // Create users table
    $conn->exec("CREATE TABLE IF NOT EXISTS users (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Create user_sessions table
    $conn->exec("CREATE TABLE IF NOT EXISTS user_sessions (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        token VARCHAR(255) NOT NULL,
        expires_at DATETIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    // Create admins table
    $conn->exec("CREATE TABLE IF NOT EXISTS admins (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
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
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
        FOREIGN KEY (admin_id) REFERENCES admins(id)
    )");

    // Create events table
    $conn->exec("CREATE TABLE IF NOT EXISTS events (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Create tasks table
    $conn->exec("CREATE TABLE IF NOT EXISTS tasks (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        due_date DATE,
        status VARCHAR(50) DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Create event_tasks table
    $conn->exec("CREATE TABLE IF NOT EXISTS event_tasks (
        id INT PRIMARY KEY AUTO_INCREMENT,
        event_id INT NOT NULL,
        task_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
        FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
    )");

    // Create templates table
    $conn->exec("CREATE TABLE IF NOT EXISTS templates (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Create template_tasks table
    $conn->exec("CREATE TABLE IF NOT EXISTS template_tasks (
        id INT PRIMARY KEY AUTO_INCREMENT,
        template_id INT NOT NULL,
        task_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (template_id) REFERENCES templates(id) ON DELETE CASCADE,
        FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
    )");

    // Create shopping_events table
    $conn->exec("CREATE TABLE IF NOT EXISTS shopping_events (
        id INT PRIMARY KEY AUTO_INCREMENT,
        event_id INT NOT NULL,
        budget DECIMAL(10,2),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
    )");

    // Create shopping_items table
    $conn->exec("CREATE TABLE IF NOT EXISTS shopping_items (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        estimated_price DECIMAL(10,2),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Create shopping_tasks table
    $conn->exec("CREATE TABLE IF NOT EXISTS shopping_tasks (
        id INT PRIMARY KEY AUTO_INCREMENT,
        shopping_event_id INT NOT NULL,
        shopping_item_id INT NOT NULL,
        quantity INT DEFAULT 1,
        status VARCHAR(50) DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (shopping_event_id) REFERENCES shopping_events(id) ON DELETE CASCADE,
        FOREIGN KEY (shopping_item_id) REFERENCES shopping_items(id) ON DELETE CASCADE
    )");

    // Insert sample data for admins
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
    $conn->exec("SET FOREIGN_KEY_CHECKS = 0");
    $conn->exec("TRUNCATE TABLE shopping_tasks");
    $conn->exec("TRUNCATE TABLE shopping_items");
    $conn->exec("TRUNCATE TABLE shopping_events");
    $conn->exec("TRUNCATE TABLE template_tasks");
    $conn->exec("TRUNCATE TABLE templates");
    $conn->exec("TRUNCATE TABLE event_tasks");
    $conn->exec("TRUNCATE TABLE tasks");
    $conn->exec("TRUNCATE TABLE events");
    $conn->exec("TRUNCATE TABLE contact_messages");
    $conn->exec("TRUNCATE TABLE admin_credentials");
    $conn->exec("TRUNCATE TABLE admins");
    $conn->exec("TRUNCATE TABLE user_sessions");
    $conn->exec("TRUNCATE TABLE users");
    $conn->exec("SET FOREIGN_KEY_CHECKS = 1");

    // Insert admins and their credentials
    foreach ($admins as $admin) {
        $stmt = $conn->prepare("INSERT INTO admins (name, email, role) VALUES (?, ?, ?)");
        $stmt->execute([$admin['name'], $admin['email'], $admin['role']]);
        
        $adminId = $conn->lastInsertId();
        
        // Create admin credentials
        $username = strtolower(explode('@', $admin['email'])[0]);
        $password = password_hash('admin123', PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("INSERT INTO admin_credentials (admin_id, username, password) VALUES (?, ?, ?)");
        $stmt->execute([$adminId, $username, $password]);

        // Add sample contact messages for each admin
        $messages = [
            [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'message' => 'Need help with wedding planning',
                'is_read' => true
            ],
            [
                'name' => 'Jane Smith',
                'email' => 'jane@example.com',
                'message' => 'Interested in festival organization',
                'is_read' => false
            ]
        ];

        foreach ($messages as $message) {
            $stmt = $conn->prepare("INSERT INTO contact_messages (admin_id, name, email, message, is_read) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$adminId, $message['name'], $message['email'], $message['message'], $message['is_read']]);
        }
    }

    echo "Database setup completed successfully!\n\n";
    echo "Tables created:\n";
    $tables = $conn->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        echo "- $table\n";
    }

    echo "\nSample data has been inserted into the following tables:\n";
    echo "- admins\n";
    echo "- admin_credentials\n";
    echo "- contact_messages\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
} 