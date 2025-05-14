<?php
// Include database connection
include_once './api/config/db.php';

// Connect to database
$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    die("Database connection failed.");
}

echo "Connected to database successfully.<br>";

// SQL to create rooms table
$roomsTable = "
CREATE TABLE IF NOT EXISTS `rooms` (
  `room_id` int(11) NOT NULL AUTO_INCREMENT,
  `room_name` varchar(100) NOT NULL,
  `room_type` varchar(50) NOT NULL,
  `capacity` int(11) NOT NULL DEFAULT 0,
  `description` text DEFAULT NULL,
  `is_available` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`room_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

// Execute query
try {
    $conn->exec($roomsTable);
    echo "Table 'rooms' created successfully.<br>";
} catch(PDOException $e) {
    echo "Error creating table 'rooms': " . $e->getMessage() . "<br>";
}

// SQL to create room_reservations table
$reservationsTable = "
CREATE TABLE IF NOT EXISTS `room_reservations` (
  `reservation_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `room_id` int(11) NULL,
  `room_name` varchar(100) NULL,
  `purpose` text NOT NULL,
  `reservation_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `status` enum('pending', 'approved', 'rejected', 'completed') NOT NULL DEFAULT 'pending',
  `admin_assign` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`reservation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

// Execute query
try {
    $conn->exec($reservationsTable);
    echo "Table 'room_reservations' created successfully.<br>";
} catch(PDOException $e) {
    echo "Error creating table 'room_reservations': " . $e->getMessage() . "<br>";
}

// SQL to create reservation_members table
$membersTable = "
CREATE TABLE IF NOT EXISTS `reservation_members` (
  `member_id` int(11) NOT NULL AUTO_INCREMENT,
  `reservation_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `student_id` varchar(100) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`member_id`),
  KEY `reservation_id` (`reservation_id`),
  CONSTRAINT `reservation_members_ibfk_1` FOREIGN KEY (`reservation_id`) REFERENCES `room_reservations` (`reservation_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

// Execute query
try {
    $conn->exec($membersTable);
    echo "Table 'reservation_members' created successfully.<br>";
} catch(PDOException $e) {
    echo "Error creating table 'reservation_members': " . $e->getMessage() . "<br>";
}

// Check if admin_assign column exists, add it if it doesn't
try {
    // First check if the column exists
    $checkColumn = $conn->query("SHOW COLUMNS FROM room_reservations LIKE 'admin_assign'");
    
    if ($checkColumn->rowCount() == 0) {
        // Column doesn't exist, add it
        $conn->exec("ALTER TABLE room_reservations ADD COLUMN admin_assign BOOLEAN DEFAULT FALSE AFTER status");
        echo "Added admin_assign column to room_reservations table<br>";
    } else {
        echo "admin_assign column already exists in room_reservations table<br>";
    }
} catch(PDOException $e) {
    echo "Error checking/adding admin_assign column: " . $e->getMessage() . "<br>";
}

// Check if rooms table is empty
$checkRooms = "SELECT COUNT(*) as count FROM rooms";
$stmt = $conn->query($checkRooms);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row['count'] == 0) {
    // Insert sample rooms
    $sampleRooms = "
    INSERT INTO `rooms` (`room_name`, `room_type`, `capacity`, `description`, `is_available`)
    VALUES
      ('Study Room 101', 'Study Room', 10, 'Small study room suitable for group discussions', 1),
      ('Study Room 102', 'Study Room', 8, 'Small study room with whiteboard', 1),
      ('Computer Lab 201', 'Computer Lab', 30, 'Computer laboratory with internet access', 1),
      ('Library Conference Room', 'Conference Room', 20, 'Large conference room with projector', 1),
      ('Reading Lounge', 'Lounge', 15, 'Quiet reading area with comfortable seating', 1);
    ";
    
    try {
        $conn->exec($sampleRooms);
        echo "Sample rooms inserted successfully.<br>";
    } catch(PDOException $e) {
        echo "Error inserting sample rooms: " . $e->getMessage() . "<br>";
    }
} else {
    echo "Rooms table already has data.<br>";
}

echo "<br>Setup completed.";
?>