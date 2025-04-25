<?php
require_once 'includes/db_connection.php';

try {
    // Create event_templates table
    $conn->exec("CREATE TABLE IF NOT EXISTS event_templates (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        type VARCHAR(50) NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Insert some sample templates
    $templates = [
        [
            'name' => 'Classic Wedding',
            'type' => 'wedding',
            'description' => 'A traditional wedding template with all the classic elements.'
        ],
        [
            'name' => 'Modern Wedding',
            'type' => 'wedding',
            'description' => 'A contemporary wedding template with modern touches.'
        ],
        [
            'name' => 'Cultural Festival',
            'type' => 'festival',
            'description' => 'A template for organizing cultural festivals and celebrations.'
        ],
        [
            'name' => 'Music Festival',
            'type' => 'festival',
            'description' => 'Perfect template for organizing music festivals and concerts.'
        ],
        [
            'name' => 'Adventure Trip',
            'type' => 'trip',
            'description' => 'Template for planning adventure trips and expeditions.'
        ]
    ];
    
    // Clear existing templates
    $conn->exec("DELETE FROM event_templates");
    
    // Insert sample templates
    $stmt = $conn->prepare("INSERT INTO event_templates (name, type, description) VALUES (?, ?, ?)");
    foreach ($templates as $template) {
        $stmt->execute([$template['name'], $template['type'], $template['description']]);
    }
    
    echo "Event templates table created and populated successfully!\n";
    
    // Verify the table structure
    $stmt = $conn->query("DESCRIBE event_templates");
    echo "\nEvent Templates Table Structure:\n";
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "{$row['Field']} - {$row['Type']} - {$row['Null']} - {$row['Key']} - {$row['Default']}\n";
    }
    
    // Show sample data
    $stmt = $conn->query("SELECT * FROM event_templates");
    echo "\nSample Templates:\n";
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- {$row['name']} ({$row['type']})\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
} 