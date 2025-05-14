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

// Include necessary files
include_once '../config/db.php';
include_once '../models/User.php';

// Instantiate database and user objects
$database = new Database();
$db = $database->getConnection();

if (!$db) {
    http_response_code(503); // Service Unavailable
    echo json_encode(array("status" => "error", "message" => "Unable to connect to database."));
    exit;
}

$user = new User($db);

// Attempt to get all users
try {
    $stmt = $user->getAllUsers();
    
    if ($stmt) {
        $users_arr = array();
        $users_arr["status"] = "success";
        $users_arr["users"] = array();

        // Add user counts to the response
        $users_arr["total_users"] = $user->getUserCount();
        $users_arr["students_count"] = $user->getUserCountByRole('student');
        $users_arr["faculty_count"] = $user->getUserCountByRole('faculty');
        $users_arr["admin_count"] = $user->getUserCountByRole('admin');

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
            extract($row);
            $user_item = array(
                "user_id" => $user_id,
                "role" => $role,
                "student_employee_id" => $student_employee_id,
                "last_name" => $last_name,
                "first_name" => $first_name,
                "middle_name" => $middle_name,
                "email" => $email,
                // "password" => $password, // Password should not be sent to frontend
                "course" => $course,
                "year_level" => $year_level,
                "section" => $section,
                "profile_image" => $profile_image
            );
            array_push($users_arr["users"], $user_item);
        }
        http_response_code(200);
        echo json_encode($users_arr);
    } else {
        http_response_code(404); // Not Found
        echo json_encode(array("status" => "error", "message" => "No users found."));
    }
} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode(array("status" => "error", "message" => "Error retrieving users: " . $e->getMessage()));
}
?> 