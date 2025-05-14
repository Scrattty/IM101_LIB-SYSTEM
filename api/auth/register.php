<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Log the raw input
$raw_input = file_get_contents("php://input");
error_log("Received registration request: " . $raw_input);

include_once '../config/db.php';
include_once '../models/User.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

if (!$db) {
    error_log("Database connection failed");
    http_response_code(500);
    echo json_encode(array(
        "status" => "error",
        "message" => "Database connection failed"
    ));
    exit;
}

// Initialize user object
$user = new User($db);

// Get posted data
$data = json_decode($raw_input);

if (json_last_error() !== JSON_ERROR_NONE) {
    error_log("JSON decode error: " . json_last_error_msg());
    http_response_code(400);
    echo json_encode(array(
        "status" => "error",
        "message" => "Invalid JSON data: " . json_last_error_msg()
    ));
    exit;
}

// Log the decoded data
error_log("Decoded registration data: " . print_r($data, true));

// Validate required fields
if(
    !empty($data->role) &&
    !empty($data->student_employee_id) &&
    !empty($data->last_name) &&
    !empty($data->first_name) &&
    !empty($data->email) &&
    !empty($data->password)
) {
    // Set user property values
    $user->role = $data->role;
    $user->student_employee_id = $data->student_employee_id;
    $user->last_name = $data->last_name;
    $user->first_name = $data->first_name;
    $user->middle_name = $data->middle_name ?? null;
    $user->email = $data->email;
    $user->password = $data->password;
    $user->course = $data->course ?? null;
    $user->year_level = $data->year_level ?? null;
    $user->section = $data->section ?? null;

    // Register the user
    $result = $user->register();
    error_log("Registration result: " . print_r($result, true));

    // Set response code
    http_response_code($result["status"] === "success" ? 201 : 400);

    // Return response
    echo json_encode($result);
} else {
    error_log("Registration failed: Missing required fields");
    // Set response code
    http_response_code(400);

    // Return error message
    echo json_encode(array(
        "status" => "error",
        "message" => "Unable to register. Data is incomplete. Required fields: role, student_employee_id, last_name, first_name, email, password"
    ));
}
?> 