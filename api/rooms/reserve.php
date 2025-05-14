<?php
// Set headers for JSON response and CORS
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

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

// Debug: output received data
$debug_data = json_encode($data);
error_log("Received data: " . $debug_data);

// Check if data is complete
if (
    empty($data->user_id) ||
    empty($data->purpose) ||
    empty($data->reservation_date) ||
    empty($data->start_time) ||
    empty($data->end_time)
) {
    http_response_code(400); // Bad Request
    echo json_encode(array("status" => "error", "message" => "Missing required fields for room reservation."));
    exit();
}

// Instantiate database and reservation objects
$database = new Database();
$db = $database->getConnection();

if (!$db) {
    http_response_code(503); // Service Unavailable
    echo json_encode(array("status" => "error", "message" => "Unable to connect to database."));
    exit();
}

try {
    // Create the reservation with admin_assign flag
    $reservation = new RoomReservation($db);

    // Set values
    $reservation->user_id = $data->user_id;
    $reservation->purpose = $data->purpose;
    $reservation->reservation_date = $data->reservation_date;
    $reservation->start_time = $data->start_time;
    $reservation->end_time = $data->end_time;
    $reservation->status = "pending"; // Default status

    // Set admin_assign flag if provided
    $reservation->admin_assign = isset($data->admin_assign) ? $data->admin_assign : true;

    // Debug: log data being used
    error_log("Using data for reservation: user_id={$reservation->user_id}, date={$reservation->reservation_date}, admin_assign={$reservation->admin_assign}");

    // Create reservation without room assignment
    $result = $reservation->createReservation();

    // Return response
    if ($result["status"] === "success") {
        http_response_code(201); // Created
        echo json_encode($result);
    } else {
        http_response_code(500); // Internal Server Error
        echo json_encode($result);
    }
} catch (Exception $e) {
    // Catch any exceptions and return helpful error message
    http_response_code(500);
    echo json_encode(array(
        "status" => "error",
        "message" => "Exception: " . $e->getMessage()
    ));
}
?> 