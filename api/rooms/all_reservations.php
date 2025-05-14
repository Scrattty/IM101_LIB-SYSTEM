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

// Include necessary files
include_once '../config/db.php';
include_once '../models/RoomReservation.php';

// Instantiate database and reservation objects
$database = new Database();
$db = $database->getConnection();

if (!$db) {
    http_response_code(503); // Service Unavailable
    echo json_encode(array("status" => "error", "message" => "Unable to connect to database."));
    exit;
}

$reservation = new RoomReservation($db);

try {
    // Get all reservations
    $stmt = $reservation->getAllReservations();
    
    if ($stmt && $stmt->rowCount() > 0) {
        $reservations_arr = array();
        $reservations_arr["status"] = "success";
        $reservations_arr["reservations"] = array();
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            extract($row);
            
            $user_name = $first_name . ' ' . $last_name;
            
            $reservation_item = array(
                "reservation_id" => $reservation_id,
                "user_id" => $user_id,
                "user_name" => $user_name,
                "student_employee_id" => $student_employee_id,
                "room_id" => $room_id,
                "room_name" => $room_name,
                "purpose" => $purpose,
                "reservation_date" => $reservation_date,
                "start_time" => $start_time,
                "end_time" => $end_time,
                "status" => $status,
                "created_at" => $created_at
            );
            
            array_push($reservations_arr["reservations"], $reservation_item);
        }
        
        http_response_code(200);
        echo json_encode($reservations_arr);
    } else {
        http_response_code(200);
        echo json_encode(array(
            "status" => "success",
            "message" => "No reservations found.",
            "reservations" => array()
        ));
    }
} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode(array("status" => "error", "message" => "Error retrieving reservations: " . $e->getMessage()));
}
?> 