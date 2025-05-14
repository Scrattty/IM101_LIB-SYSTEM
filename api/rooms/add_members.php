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

// Get posted data
$data = json_decode(file_get_contents("php://input"));

// Check if data is complete
if (empty($data->reservation_id) || empty($data->members) || !is_array($data->members)) {
    http_response_code(400); // Bad Request
    echo json_encode(array("status" => "error", "message" => "Missing required fields or invalid format."));
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
    // Start transaction
    $db->beginTransaction();
    
    // Prepare statement for inserting members
    $query = "INSERT INTO reservation_members (reservation_id, name, student_id) 
              VALUES (:reservation_id, :member_name, :student_id)";
    $stmt = $db->prepare($query);
    
    // Insert each member
    foreach ($data->members as $member) {
        // Validate member data
        if (empty($member->name) || empty($member->id)) {
            continue; // Skip invalid members
        }
        
        // Sanitize inputs
        $reservation_id = htmlspecialchars(strip_tags($data->reservation_id));
        $member_name = htmlspecialchars(strip_tags($member->name));
        $student_id = htmlspecialchars(strip_tags($member->id));
        
        // Bind values
        $stmt->bindParam(":reservation_id", $reservation_id);
        $stmt->bindParam(":member_name", $member_name);
        $stmt->bindParam(":student_id", $student_id);
        
        // Execute statement
        $stmt->execute();
    }
    
    // Commit transaction
    $db->commit();
    
    // Return success response
    http_response_code(201); // Created
    echo json_encode(array(
        "status" => "success",
        "message" => "Group members added successfully."
    ));
    
} catch (PDOException $e) {
    // Rollback transaction on error
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