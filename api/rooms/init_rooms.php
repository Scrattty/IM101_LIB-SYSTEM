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
    // Check if rooms table exists
    $checkTable = $db->query("SHOW TABLES LIKE 'rooms'");
    if ($checkTable->rowCount() == 0) {
        // Create rooms table
        $createTable = "CREATE TABLE IF NOT EXISTS rooms (
            room_id INT PRIMARY KEY AUTO_INCREMENT,
            room_name VARCHAR(50) NOT NULL,
            room_type VARCHAR(50) NOT NULL,
            capacity INT NOT NULL,
            description TEXT,
            is_available BOOLEAN DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $db->exec($createTable);
    }

    // Check if any rooms exist
    $checkRooms = $db->query("SELECT COUNT(*) FROM rooms");
    $roomCount = $checkRooms->fetchColumn();

    if ($roomCount == 0) {
        // Insert default rooms
        $defaultRooms = [
            ['Room 101', 'Study Room', 30, 'Quiet study room with individual tables'],
            ['Room 102', 'Conference Room', 25, 'Conference room with projector and whiteboard'],
            ['Room 103', 'Group Study', 40, 'Large room for group study sessions'],
            ['Room 201', 'Computer Lab', 35, 'Computer lab with 20 workstations'],
            ['Room 202', 'Meeting Room', 30, 'Small meeting room with TV screen']
        ];

        $insertQuery = "INSERT INTO rooms (room_name, room_type, capacity, description) VALUES (?, ?, ?, ?)";
        $stmt = $db->prepare($insertQuery);

        foreach ($defaultRooms as $room) {
            $stmt->execute($room);
        }

        echo "Default rooms have been added to the database.\n";
    } else {
        echo "Rooms already exist in the database.\n";
    }

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?> 