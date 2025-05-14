<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    // Set session parameters
    ini_set('session.cookie_path', '/');
    ini_set('session.cookie_domain', '');
    ini_set('session.cookie_secure', '0');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_samesite', 'Lax');
    
    session_start();
}

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log all incoming request data
error_log("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
error_log("CONTENT_TYPE: " . (isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : 'not set'));
error_log("HTTP_ACCEPT: " . (isset($_SERVER['HTTP_ACCEPT']) ? $_SERVER['HTTP_ACCEPT'] : 'not set'));

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
    error_log("Invalid request method: " . $_SERVER['REQUEST_METHOD']);
    http_response_code(405);
    echo json_encode(array(
        "status" => "error",
        "message" => "Method not allowed. Only POST requests are accepted."
    ));
    exit();
}

// Log the raw input
$raw_input = file_get_contents("php://input");
error_log("Raw input received: " . $raw_input);

// Check if raw input is empty
if (empty($raw_input)) {
    error_log("Error: Empty raw input received");
    http_response_code(400);
    echo json_encode(array(
        "status" => "error",
        "message" => "No data received"
    ));
    exit();
}

// Include required files
$db_file = '../config/db.php';
$user_file = '../models/User.php';

if (!file_exists($db_file)) {
    error_log("Database config file not found: " . $db_file);
    http_response_code(500);
    echo json_encode(array(
        "status" => "error",
        "message" => "Server configuration error"
    ));
    exit();
}

if (!file_exists($user_file)) {
    error_log("User model file not found: " . $user_file);
    http_response_code(500);
    echo json_encode(array(
        "status" => "error",
        "message" => "Server configuration error"
    ));
    exit();
}

include_once $db_file;
include_once $user_file;

try {
    // Get database connection
    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        error_log("Database connection failed: Connection object is null");
        http_response_code(500);
        echo json_encode(array(
            "status" => "error",
            "message" => "Database connection failed"
        ));
        exit();
    }

    // Test the connection
    try {
        $db->query("SELECT 1");
        error_log("Database connection test successful");
    } catch (PDOException $e) {
        error_log("Database connection test failed: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(array(
            "status" => "error",
            "message" => "Database connection failed: " . $e->getMessage()
        ));
        exit();
    }

    // Initialize user object
    $user = new User($db);

    // Get posted data
    $data = json_decode($raw_input);

    // Log JSON decode errors if any
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON decode error: " . json_last_error_msg());
        error_log("Raw input that caused error: " . $raw_input);
        http_response_code(400);
        echo json_encode(array(
            "status" => "error",
            "message" => "Invalid JSON data: " . json_last_error_msg()
        ));
        exit();
    }

    // Log the decoded data
    error_log("Decoded login data: " . print_r($data, true));

    // Check if data is null after decoding
    if ($data === null) {
        error_log("Error: Decoded data is null");
        http_response_code(400);
        echo json_encode(array(
            "status" => "error",
            "message" => "Invalid data format"
        ));
        exit();
    }

    // Validate required fields
    if(
        !empty($data->student_employee_id) &&
        !empty($data->password) &&
        !empty($data->role)
    ) {
        error_log("All required fields present");
        // Set user property values
        $user->student_employee_id = $data->student_employee_id;
        $user->password = $data->password;
        $user->role = $data->role;

        // Attempt to login
        $result = $user->login();
        error_log("Login result: " . print_r($result, true));
        
        if($result["status"] === "success") {
            // Check if the role matches
            if($result["user"]["role"] !== $data->role) {
                error_log("Role mismatch: Expected " . $data->role . ", got " . $result["user"]["role"]);
                http_response_code(401);
                echo json_encode(array(
                    "status" => "error",
                    "message" => "Invalid role for this account. Please select the correct role."
                ));
                exit();
            }
            
            // Set session variables
            $_SESSION['user_id'] = $result["user"]["user_id"];
            $_SESSION['role'] = $result["user"]["role"];
            $_SESSION['student_employee_id'] = $result["user"]["student_employee_id"];
            $_SESSION['name'] = $result["user"]["first_name"] . ' ' . $result["user"]["last_name"];
            
            // Log session data for debugging
            error_log("Session data after login: " . print_r($_SESSION, true));
            error_log("Session ID: " . session_id());
            error_log("Session cookie parameters: " . print_r(session_get_cookie_params(), true));
            
            http_response_code(200);
            echo json_encode($result);
        } else {
            // Clear any existing session data on failed login
            session_unset();
            session_destroy();
            
            http_response_code(401);
            echo json_encode($result);
        }
    } else {
        error_log("Missing required fields in login request");
        error_log("student_employee_id: " . (!empty($data->student_employee_id) ? "present" : "missing"));
        error_log("password: " . (!empty($data->password) ? "present" : "missing"));
        error_log("role: " . (!empty($data->role) ? "present" : "missing"));
        
        http_response_code(400);
        echo json_encode(array(
            "status" => "error",
            "message" => "Unable to login. Please check: Student/Employee Number, password, role"
        ));
    }
} catch (Exception $e) {
    error_log("Unexpected error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(array(
        "status" => "error",
        "message" => "An unexpected error occurred: " . $e->getMessage()
    ));
}
?> 