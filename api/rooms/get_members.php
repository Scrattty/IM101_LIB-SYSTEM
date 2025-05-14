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

// Get reservation ID from the request
if (!isset($_GET['reservation_id']) || empty($_GET['reservation_id'])) {
    http_response_code(400); // Bad Request
    echo json_encode(array("status" => "error", "message" => "Missing reservation ID parameter."));
    exit();
}

$reservation_id = intval($_GET['reservation_id']);

// Database connection
$database = new Database();
$db = $database->getConnection();

if (!$db) {
    http_response_code(503); // Service Unavailable
    echo json_encode(array("status" => "error", "message" => "Unable to connect to database."));
    exit;
}

try {
    // Prepare query to get reservation members
    $query = "SELECT * FROM room_reservation_members 
              WHERE reservation_id = :reservation_id 
              ORDER BY member_name ASC";
              
    $stmt = $db->prepare($query);
    
    // Bind parameter
    $stmt->bindParam(":reservation_id", $reservation_id);
    
    // Execute query
    $stmt->execute();
    
    // Check if query was successful
    if ($stmt) {
        // Get all members
        $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Return success response with members
        http_response_code(200);
        echo json_encode(array(
            "status" => "success",
            "members" => $members
        ));
    } else {
        http_response_code(500); // Internal Server Error
        echo json_encode(array(
            "status" => "error",
            "message" => "Failed to retrieve reservation members."
        ));
    }
} catch (PDOException $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode(array(
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ));
}
?> 