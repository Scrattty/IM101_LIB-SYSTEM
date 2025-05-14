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

// Get POST data
$data = json_decode(file_get_contents("php://input"));

// Validate required fields
if (!isset($data->reservation_id) || !isset($data->status)) {
    http_response_code(400); // Bad Request
    echo json_encode(array("status" => "error", "message" => "Missing required fields."));
    exit();
}

// Validate status
$allowed_statuses = array('pending', 'approved', 'rejected');
if (!in_array($data->status, $allowed_statuses)) {
    http_response_code(400); // Bad Request
    echo json_encode(array("status" => "error", "message" => "Invalid status value."));
    exit();
}

// Database connection
$database = new Database();
$db = $database->getConnection();

if (!$db) {
    http_response_code(503); // Service Unavailable
    echo json_encode(array("status" => "error", "message" => "Unable to connect to database."));
    exit;
}

try {
    // Update reservation status
    $query = "UPDATE room_reservations SET status = :status WHERE reservation_id = :reservation_id";
    $stmt = $db->prepare($query);
    
    $stmt->bindParam(":status", $data->status);
    $stmt->bindParam(":reservation_id", $data->reservation_id);
    
    if ($stmt->execute()) {
        // Return success response
        http_response_code(200);
        echo json_encode(array(
            "status" => "success",
            "message" => "Reservation status updated successfully."
        ));
    } else {
        throw new PDOException("Failed to update reservation status.");
    }
    
} catch (PDOException $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode(array(
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ));
}
?> 