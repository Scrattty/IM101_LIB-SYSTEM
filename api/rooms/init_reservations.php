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
    // Check if room_reservations table exists
    $checkTable = $db->query("SHOW TABLES LIKE 'room_reservations'");
    if ($checkTable->rowCount() == 0) {
        // Create room_reservations table
        $createTable = "CREATE TABLE IF NOT EXISTS room_reservations (
            reservation_id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            room_id INT,
            room_name VARCHAR(50),
            purpose TEXT NOT NULL,
            reservation_date DATE NOT NULL,
            start_time TIME NOT NULL,
            end_time TIME NOT NULL,
            status ENUM('pending', 'approved', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending',
            admin_assign BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
            FOREIGN KEY (room_id) REFERENCES rooms(room_id) ON DELETE SET NULL
        )";
        $db->exec($createTable);

        // Create room_reservation_members table for storing additional members
        $createMembersTable = "CREATE TABLE IF NOT EXISTS room_reservation_members (
            member_id INT PRIMARY KEY AUTO_INCREMENT,
            reservation_id INT NOT NULL,
            name VARCHAR(100) NOT NULL,
            student_id VARCHAR(20) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (reservation_id) REFERENCES room_reservations(reservation_id) ON DELETE CASCADE
        )";
        $db->exec($createMembersTable);

        echo "Room reservations tables have been created successfully.\n";
    } else {
        echo "Room reservations tables already exist.\n";
    }

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?> 