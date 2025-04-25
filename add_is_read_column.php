<?php
require_once 'includes/db_connection.php';

try {
    // Add is_read column if it doesn't exist
    $conn->exec("ALTER TABLE contact_messages ADD COLUMN IF NOT EXISTS is_read BOOLEAN DEFAULT FALSE");
    echo "Successfully added is_read column\n";
    
    // Verify the column was added
    $stmt = $conn->query("DESCRIBE contact_messages");
    echo "\nUpdated Contact Messages Table Structure:\n";
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "{$row['Field']} - {$row['Type']} - {$row['Null']} - {$row['Key']} - {$row['Default']}\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
} 