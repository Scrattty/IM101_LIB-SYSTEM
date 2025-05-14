<?php
// Set headers for JSON response and CORS
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, DELETE, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Check if request method is POST or DELETE
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(array("status" => "error", "message" => "Method not allowed"));
    exit();
}

// Include necessary files
include_once '../config/db.php';
include_once '../models/User.php';

// Get user_id from the request
$data = json_decode(file_get_contents("php://input"));

if (!isset($data->user_id) || empty($data->user_id)) {
    http_response_code(400);
    echo json_encode(array("status" => "error", "message" => "User ID is required"));
    exit();
}

// Create database connection
$database = new Database();
$db = $database->getConnection();

if (!$db) {
    http_response_code(503);
    echo json_encode(array("status" => "error", "message" => "Unable to connect to database"));
    exit();
}

// Initialize user object
$user = new User($db);

try {
    // Delete user
    $result = $user->deleteUser($data->user_id);
    
    if ($result) {
        http_response_code(200);
        echo json_encode(array(
            "status" => "success",
            "message" => "User deleted successfully"
        ));
    } else {
        http_response_code(404);
        echo json_encode(array(
            "status" => "error",
            "message" => "User not found or could not be deleted"
        ));
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        "status" => "error",
        "message" => "Error: " . $e->getMessage()
    ));
}
?> 