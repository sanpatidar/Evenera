<?php
require_once 'includes/db_connection.php';

try {
    // Check contact_messages table
    $stmt = $conn->query("DESCRIBE contact_messages");
    echo "Contact Messages Table Structure:\n";
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "{$row['Field']} - {$row['Type']} - {$row['Null']} - {$row['Key']} - {$row['Default']}\n";
    }
    
    // Check if there are any records
    $stmt = $conn->query("SELECT COUNT(*) FROM contact_messages");
    $count = $stmt->fetchColumn();
    echo "\nTotal contact messages: " . $count . "\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
} 