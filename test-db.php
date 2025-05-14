<?php
// Include database connection
include_once './api/config/db.php';

// Connect to database
$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    die("Database connection failed.");
}

echo "Connected to database successfully.\n";

// Check room_reservations table structure
try {
    $stmt = $conn->query("DESCRIBE room_reservations");
    echo "\nROOM_RESERVATIONS TABLE STRUCTURE:\n";
    echo "================================\n";
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "Field: {$row['Field']}, Type: {$row['Type']}, Null: {$row['Null']}, Key: {$row['Key']}, Default: {$row['Default']}\n";
    }
} catch(PDOException $e) {
    echo "Error getting table structure: " . $e->getMessage() . "\n";
}

// Check for existing reservations
try {
    $stmt = $conn->query("SELECT COUNT(*) as count FROM room_reservations");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "\nExisting reservations: {$row['count']}\n";
} catch(PDOException $e) {
    echo "Error counting reservations: " . $e->getMessage() . "\n";
}

// First, modify the table to make room_id and room_name nullable
try {
    echo "\nModifying room_reservations table to allow NULL for room_id and room_name...\n";
    
    $conn->exec("ALTER TABLE room_reservations MODIFY room_id INT(11) NULL");
    $conn->exec("ALTER TABLE room_reservations MODIFY room_name VARCHAR(100) NULL");
    
    echo "Table modified successfully.\n";
} catch(PDOException $e) {
    echo "Error modifying table: " . $e->getMessage() . "\n";
}

// Try the admin_assign insertion test
try {
    echo "\nTrying admin_assign insertion test...\n";
    
    $query = "INSERT INTO room_reservations 
              (user_id, purpose, reservation_date, start_time, end_time, status, admin_assign, created_at) 
              VALUES 
              (2, 'Test admin_assign purpose', '2024-05-20', '10:00:00', '11:00:00', 'pending', TRUE, NOW())";
              
    $result = $conn->exec($query);
    
    if($result) {
        $lastId = $conn->lastInsertId();
        echo "Test insertion successful. Last insert ID: {$lastId}\n";
        
        // Verify the insertion
        $stmt = $conn->query("SELECT * FROM room_reservations WHERE reservation_id = {$lastId}");
        $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "Inserted record: \n";
        foreach ($reservation as $key => $value) {
            echo "- {$key}: " . (is_null($value) ? "NULL" : $value) . "\n";
        }
        
        // Clean up test data
        $conn->exec("DELETE FROM room_reservations WHERE purpose = 'Test admin_assign purpose'");
        echo "Test data removed.\n";
    } else {
        echo "Test insertion failed.\n";
    }
} catch(PDOException $e) {
    echo "Error in test insertion: " . $e->getMessage() . "\n";
}

echo "\nDatabase tests completed.\n";
?> 