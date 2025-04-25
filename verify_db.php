<?php
require_once 'includes/db_connection.php';

try {
    // Tables to check
    $tables = ['admins', 'admin_credentials', 'contact_messages'];
    
    foreach ($tables as $table) {
        // Check if table exists
        $stmt = $conn->query("SHOW TABLES LIKE '$table'");
        $tableExists = $stmt->rowCount() > 0;
        
        echo "<h3>Table: $table</h3>";
        if ($tableExists) {
            echo "✅ Table exists<br>";
            
            // Get table structure
            $stmt = $conn->query("DESCRIBE $table");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<h4>Table Structure:</h4>";
            echo "<table border='1'>";
            echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
            foreach ($columns as $column) {
                echo "<tr>";
                foreach ($column as $key => $value) {
                    echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
                }
                echo "</tr>";
            }
            echo "</table><br><br>";
        } else {
            echo "❌ Table does not exist<br><br>";
        }
    }
    
} catch (PDOException $e) {
    echo "Error: " . htmlspecialchars($e->getMessage());
}
?> 