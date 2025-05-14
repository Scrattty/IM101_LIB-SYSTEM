<?php
// Set headers for JSON response and CORS
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Check if request method is GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(array("status" => "error", "message" => "Method not allowed. Use GET instead."));
    exit();
}

// Include necessary files
include_once '../config/db.php';

// Get date and time from query parameters if provided
$date = isset($_GET['date']) ? $_GET['date'] : null;
$start_time = isset($_GET['start_time']) ? $_GET['start_time'] : null;
$end_time = isset($_GET['end_time']) ? $_GET['end_time'] : null;

// Database connection
$database = new Database();
$db = $database->getConnection();

if (!$db) {
    http_response_code(503); // Service Unavailable
    echo json_encode(array("status" => "error", "message" => "Unable to connect to database."));
    exit;
}

try {
    // Base query to get all rooms
    $query = "SELECT r.* FROM rooms r WHERE r.is_available = 1";
    
    // If date and time are provided, check for room availability
    if ($date && $start_time && $end_time) {
        $query .= " AND r.room_id NOT IN (
            SELECT room_id FROM room_reservations 
            WHERE reservation_date = :date 
            AND status = 'approved'
            AND ((start_time <= :start_time AND end_time > :start_time)
                OR (start_time < :end_time AND end_time >= :end_time)
                OR (start_time >= :start_time AND end_time <= :end_time))
        )";
    }
    
    $query .= " ORDER BY r.room_name ASC";
    
    $stmt = $db->prepare($query);
    
    // Bind parameters if date and time are provided
    if ($date && $start_time && $end_time) {
        $stmt->bindParam(":date", $date);
        $stmt->bindParam(":start_time", $start_time);
        $stmt->bindParam(":end_time", $end_time);
    }
    
    $stmt->execute();
    
    $rooms = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $rooms[] = array(
            "room_id" => $row['room_id'],
            "room_name" => $row['room_name'],
            "room_type" => $row['room_type'],
            "capacity" => $row['capacity'],
            "description" => $row['description']
        );
    }
    
    // Return success response
    http_response_code(200);
    echo json_encode(array(
        "status" => "success",
        "rooms" => $rooms
    ));
    
} catch (PDOException $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode(array(
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ));
}
?> 