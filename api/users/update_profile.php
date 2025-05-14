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
    http_response_code(405);
    echo json_encode(array("status" => "error", "message" => "Method not allowed"));
    exit();
}

// Include necessary files
include_once '../config/db.php';
include_once '../models/User.php';

// Get the posted data (from form data or JSON)
$data = array();

// Check if this is a form submission with file upload
if (!empty($_POST) && isset($_FILES['profile_image'])) {
    $data = $_POST;
    $has_file_upload = true;
} else {
    // Handle JSON input for regular profile updates
    $json_data = file_get_contents("php://input");
    $data = json_decode($json_data, true);
    $has_file_upload = false;
}

// Validate essential data
if (!isset($data['user_id']) || empty($data['user_id'])) {
    http_response_code(400);
    echo json_encode(array("status" => "error", "message" => "User ID is required"));
    exit();
}

// Connect to database
$database = new Database();
$db = $database->getConnection();

if (!$db) {
    http_response_code(503);
    echo json_encode(array("status" => "error", "message" => "Unable to connect to database"));
    exit();
}

// Get the current user data to verify password
$query = "SELECT password FROM users WHERE user_id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $data['user_id']);
$stmt->execute();

$user_data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user_data) {
    http_response_code(404);
    echo json_encode(array("status" => "error", "message" => "User not found"));
    exit();
}

// Verify the current password if provided
if (isset($data['current_password']) && !empty($data['current_password'])) {
    if (!password_verify($data['current_password'], $user_data['password'])) {
        http_response_code(401);
        echo json_encode(array("status" => "error", "message" => "Current password is incorrect"));
        exit();
    }
}

// Profile image upload processing
$profile_image_path = null;
if ($has_file_upload && isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
    // Validate file type
    $allowed_types = array('image/jpeg', 'image/png', 'image/gif');
    $file_type = $_FILES['profile_image']['type'];
    
    if (!in_array($file_type, $allowed_types)) {
        http_response_code(400);
        echo json_encode(array("status" => "error", "message" => "Invalid file type. Only JPG, PNG, and GIF files are allowed."));
        exit();
    }
    
    // Extra validation - check if the file is actually an image
    $tmp_name = $_FILES['profile_image']['tmp_name'];
    $image_info = getimagesize($tmp_name);
    if ($image_info === false) {
        http_response_code(400);
        echo json_encode(array("status" => "error", "message" => "Uploaded file is not a valid image."));
        exit();
    }
    
    // Validate file size (max 2MB)
    $max_size = 2 * 1024 * 1024; // 2MB in bytes
    if ($_FILES['profile_image']['size'] > $max_size) {
        http_response_code(400);
        echo json_encode(array("status" => "error", "message" => "File is too large. Maximum size is 2MB."));
        exit();
    }
    
    // Create upload directory if it doesn't exist
    $upload_dir = '../../uploads/profiles/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Generate unique filename
    $file_extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
    $new_filename = 'profile_' . $data['user_id'] . '_' . time() . '.' . $file_extension;
    $upload_path = $upload_dir . $new_filename;
    
    // Move uploaded file
    if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
        $profile_image_path = 'uploads/profiles/' . $new_filename;
    } else {
        http_response_code(500);
        echo json_encode(array("status" => "error", "message" => "Failed to upload profile image"));
        exit();
    }
}

try {
    // Start building the update query
    $update_fields = array();
    $params = array();
    
    // Basic profile fields
    if (isset($data['last_name']) && !empty($data['last_name'])) {
        $update_fields[] = "last_name = :last_name";
        $params[':last_name'] = htmlspecialchars(strip_tags($data['last_name']));
    }
    
    if (isset($data['first_name']) && !empty($data['first_name'])) {
        $update_fields[] = "first_name = :first_name";
        $params[':first_name'] = htmlspecialchars(strip_tags($data['first_name']));
    }
    
    if (isset($data['middle_name'])) {
        $update_fields[] = "middle_name = :middle_name";
        $params[':middle_name'] = htmlspecialchars(strip_tags($data['middle_name']));
    }
    
    if (isset($data['email']) && !empty($data['email'])) {
        $update_fields[] = "email = :email";
        $params[':email'] = htmlspecialchars(strip_tags($data['email']));
    }
    
    // Update password if new one is provided
    if (isset($data['new_password']) && !empty($data['new_password'])) {
        $hashed_password = password_hash($data['new_password'], PASSWORD_DEFAULT);
        $update_fields[] = "password = :password";
        $params[':password'] = $hashed_password;
    }
    
    // Add profile image if uploaded
    if ($profile_image_path) {
        $update_fields[] = "profile_image = :profile_image";
        $params[':profile_image'] = $profile_image_path;
    }
    
    // If there's nothing to update, return success
    if (empty($update_fields)) {
        http_response_code(200);
        echo json_encode(array("status" => "success", "message" => "No changes were made"));
        exit();
    }
    
    // Build and execute the update query
    $query = "UPDATE users SET " . implode(", ", $update_fields) . " WHERE user_id = :user_id";
    $params[':user_id'] = $data['user_id'];
    
    $stmt = $db->prepare($query);
    
    if ($stmt->execute($params)) {
        // Get updated user data
        $query = "SELECT user_id, role, student_employee_id, last_name, first_name, middle_name, 
                        email, course, year_level, section, profile_image 
                 FROM users 
                 WHERE user_id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $data['user_id']);
        $stmt->execute();
        
        $updated_user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        http_response_code(200);
        echo json_encode(array(
            "status" => "success",
            "message" => "Profile updated successfully",
            "user" => $updated_user
        ));
    } else {
        http_response_code(500);
        echo json_encode(array("status" => "error", "message" => "Failed to update profile"));
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array("status" => "error", "message" => "Error: " . $e->getMessage()));
}
?> 