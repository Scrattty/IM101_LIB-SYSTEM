<?php
// Include database connection
include_once './api/config/db.php';

// Connect to database
$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    die("Database connection failed.");
}

echo "Database connection successful\n\n";

// Check rooms table
try {
    echo "ROOMS TABLE:\n";
    echo "============\n";
    $query = "SELECT * FROM rooms";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "ID: " . $row['room_id'] . " | Name: " . $row['room_name'] . 
                 " | Type: " . $row['room_type'] . " | Capacity: " . $row['capacity'] . "\n";
        }
    } else {
        echo "No rooms found in database.\n";
    }
} catch (PDOException $e) {
    echo "Error querying rooms table: " . $e->getMessage() . "\n";
}

echo "\n";

// Check room_reservations table structure
try {
    echo "ROOM_RESERVATIONS TABLE STRUCTURE:\n";
    echo "================================\n";
    $query = "DESCRIBE room_reservations";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "Field: " . $row['Field'] . " | Type: " . $row['Type'] . " | Null: " . $row['Null'] . " | Key: " . $row['Key'] . "\n";
        }
    }
} catch (PDOException $e) {
    echo "Error querying room_reservations table structure: " . $e->getMessage() . "\n";
}

echo "\n";

// Check room_reservation_members table structure
try {
    echo "ROOM_RESERVATION_MEMBERS TABLE STRUCTURE:\n";
    echo "======================================\n";
    $query = "DESCRIBE room_reservation_members";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "Field: " . $row['Field'] . " | Type: " . $row['Type'] . " | Null: " . $row['Null'] . " | Key: " . $row['Key'] . "\n";
        }
    }
} catch (PDOException $e) {
    echo "Error querying room_reservation_members table structure: " . $e->getMessage() . "\n";
}
?> 