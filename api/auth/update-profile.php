<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Verify this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(array(
        "status" => "error",
        "message" => "Method not allowed. Only POST requests are accepted."
    ));
    exit();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(array(
        "status" => "error",
        "message" => "Not authorized. Please login."
    ));
    exit();
}

// Include required files
include_once '../config/db.php';
include_once '../models/User.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

if (!$db) {
    http_response_code(500);
    echo json_encode(array(
        "status" => "error",
        "message" => "Database connection failed"
    ));
    exit();
}

// Initialize user object
$user = new User($db);

// Get posted data
$data = json_decode(file_get_contents("php://input"));

// Validate required fields
if (
    !empty($data->current_password) &&
    !empty($data->last_name) &&
    !empty($data->first_name) &&
    !empty($data->email)
) {
    // Set user property values
    $user->user_id = $_SESSION['user_id'];
    $user->current_password = $data->current_password;
    $user->last_name = $data->last_name;
    $user->first_name = $data->first_name;
    $user->middle_name = $data->middle_name ?? null;
    $user->email = $data->email;
    
    // Set new password if provided
    if (!empty($data->new_password)) {
        $user->new_password = $data->new_password;
    }

    // Update the profile
    $result = $user->updateProfile();

    // Set response code
    http_response_code($result["status"] === "success" ? 200 : 400);

    // Return response
    echo json_encode($result);
} else {
    // Set response code
    http_response_code(400);

    // Return error message
    echo json_encode(array(
        "status" => "error",
        "message" => "Unable to update profile. Data is incomplete. Required fields: current_password, last_name, first_name, email"
    ));
}
?> 