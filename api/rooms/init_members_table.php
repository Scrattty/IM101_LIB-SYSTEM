<?php
// Include necessary files
include_once __DIR__ . '/../config/db.php';

// Database connection
$database = new Database();
$db = $database->getConnection();

if (!$db) {
    die("Unable to connect to database.");
}

try {
    // Create room_reservation_members table
    $createMembersTable = "CREATE TABLE IF NOT EXISTS room_reservation_members (
        member_id INT PRIMARY KEY AUTO_INCREMENT,
        reservation_id INT NOT NULL,
        name VARCHAR(100) NOT NULL,
        student_id VARCHAR(20) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (reservation_id) REFERENCES room_reservations(reservation_id) ON DELETE CASCADE
    )";
    
    $db->exec($createMembersTable);
    echo "Room reservation members table created successfully.\n";
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?> 