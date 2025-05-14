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

// Check if data is complete
if (empty($data->reservation_id)) {
    http_response_code(400); // Bad Request
    echo json_encode(array("status" => "error", "message" => "Missing reservation ID."));
    exit();
}

// Instantiate database
$database = new Database();
$db = $database->getConnection();

if (!$db) {
    http_response_code(503); // Service Unavailable
    echo json_encode(array("status" => "error", "message" => "Unable to connect to database."));
    exit;
}

try {
    // Begin transaction
    $db->beginTransaction();
    
    // First, verify the reservation exists and is in pending status
    $checkQuery = "SELECT * FROM room_reservations WHERE reservation_id = :reservation_id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(":reservation_id", $data->reservation_id);
    $checkStmt->execute();
    
    $reservation = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reservation) {
        throw new Exception("Reservation not found.");
    }
    
    if ($reservation['status'] !== 'pending') {
        throw new Exception("Only pending reservations can be cancelled.");
    }
    
    // Delete reservation members first (to maintain referential integrity)
    $deleteMembers = "DELETE FROM room_reservation_members WHERE reservation_id = :reservation_id";
    $deleteStmt = $db->prepare($deleteMembers);
    $deleteStmt->bindParam(":reservation_id", $data->reservation_id);
    $deleteStmt->execute();
    
    // Delete the reservation
    $deleteReservation = "DELETE FROM room_reservations WHERE reservation_id = :reservation_id";
    $deleteStmt = $db->prepare($deleteReservation);
    $deleteStmt->bindParam(":reservation_id", $data->reservation_id);
    $deleteStmt->execute();
    
    // Commit the transaction
    $db->commit();
    
    // Return success response
    http_response_code(200);
    echo json_encode(array(
        "status" => "success",
        "message" => "Reservation cancelled successfully."
    ));
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    
    http_response_code(400); // Bad Request
    echo json_encode(array(
        "status" => "error",
        "message" => $e->getMessage()
    ));
} catch (PDOException $e) {
    // Rollback transaction on database error
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    
    http_response_code(500); // Internal Server Error
    echo json_encode(array(
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ));
}
?> 