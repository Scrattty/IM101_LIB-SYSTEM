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
    // Update the status enum to include 'cancelled'
    $updateEnum = "ALTER TABLE room_reservations 
                   MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending'";
    
    $db->exec($updateEnum);
    echo "Status enum updated successfully.\n";
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?> 