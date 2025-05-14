<?php
// Set headers for JSON response and CORS
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(array("status" => "error", "message" => "Method not allowed. Use POST instead."));
    exit();
}

// Include necessary files
include_once '../config/db.php';
include_once '../models/RoomReservation.php';

// Get posted data
$data = json_decode(file_get_contents("php://input"));

// Check if all required data is present
if (!isset($data->reservation_id) || !isset($data->room_id) || !isset($data->room_name) || !isset($data->status)) {
    http_response_code(400);
    echo json_encode(array("status" => "error", "message" => "Missing required data."));
    exit();
}

// Create database connection
$database = new Database();
$db = $database->getConnection();

if (!$db) {
    http_response_code(503);
    echo json_encode(array("status" => "error", "message" => "Unable to connect to database."));
    exit();
}

// Create reservation object
$reservation = new RoomReservation($db);

try {
    // Set the reservation ID
    $reservation->reservation_id = $data->reservation_id;
    $reservation->room_id = $data->room_id;
    $reservation->room_name = $data->room_name;
    $reservation->status = $data->status;
    
    // Check if room is available for the requested time slot
    $reservationDetails = $reservation->getReservationById();
    $reservationData = $reservationDetails->fetch(PDO::FETCH_ASSOC);
    
    if (!$reservationData) {
        http_response_code(404);
        echo json_encode(array("status" => "error", "message" => "Reservation not found."));
        exit();
    }
    
    // Check room availability
    $isAvailable = $reservation->isRoomAvailable(
        $data->room_id,
        $reservationData['reservation_date'],
        $reservationData['start_time'],
        $reservationData['end_time'],
        $data->reservation_id // Exclude current reservation from check
    );
    
    if (!$isAvailable) {
        http_response_code(409); // Conflict
        echo json_encode(array(
            "status" => "error",
            "message" => "Room is not available for the requested time slot."
        ));
        exit();
    }
    
    // Assign room and update status
    $result = $reservation->assignRoomAndUpdateStatus();
    
    if ($result['status'] === 'success') {
        http_response_code(200);
        echo json_encode(array(
            "status" => "success",
            "message" => "Room assigned and status updated successfully"
        ));
    } else {
        http_response_code(500);
        echo json_encode($result);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        "status" => "error",
        "message" => "Error: " . $e->getMessage()
    ));
}
?> 