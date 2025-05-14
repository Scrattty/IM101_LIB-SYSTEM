<?php
// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Include database connection
include_once './api/config/db.php';

echo "Testing database connection...\n";

// Create database connection
$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    die("Database connection failed.\n");
}

echo "Database connection successful!\n\n";

// Test required tables
$tables = ['users', 'rooms', 'room_reservations', 'room_reservation_members'];

foreach ($tables as $table) {
    try {
        $query = "SHOW TABLES LIKE '$table'";
        $stmt = $conn->query($query);
        
        if ($stmt->rowCount() > 0) {
            echo "Table '$table' exists.\n";
            
            // Count rows in table
            $countQuery = "SELECT COUNT(*) as count FROM $table";
            $countStmt = $conn->query($countQuery);
            $row = $countStmt->fetch(PDO::FETCH_ASSOC);
            echo "Number of records in '$table': " . $row['count'] . "\n";
            
            // Show table structure
            echo "Table structure for '$table':\n";
            $structureQuery = "DESCRIBE $table";
            $structureStmt = $conn->query($structureQuery);
            while ($field = $structureStmt->fetch(PDO::FETCH_ASSOC)) {
                echo "- {$field['Field']}: {$field['Type']} ({$field['Null']})\n";
            }
            echo "\n";
        } else {
            echo "Table '$table' does not exist!\n\n";
        }
    } catch (PDOException $e) {
        echo "Error checking table '$table': " . $e->getMessage() . "\n\n";
    }
}

// Test a sample query from admin_reservations.php
echo "Testing admin_reservations query...\n";
try {
    $query = "SELECT r.*, u.first_name, u.last_name, u.student_employee_id 
              FROM room_reservations r 
              JOIN users u ON r.user_id = u.user_id 
              ORDER BY r.created_at DESC";
              
    $stmt = $conn->prepare($query);
    $stmt->execute();
    
    echo "Query executed successfully.\n";
    echo "Number of reservations found: " . $stmt->rowCount() . "\n\n";
    
    if ($stmt->rowCount() > 0) {
        echo "Sample reservation data:\n";
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        print_r($row);
    }
} catch (PDOException $e) {
    echo "Error executing admin_reservations query: " . $e->getMessage() . "\n";
}

echo "\nDatabase test completed.\n";
?> 