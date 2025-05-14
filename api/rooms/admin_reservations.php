<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

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
include_once '../models/RoomReservation.php';

// Log the start of the script
error_log("Starting admin_reservations.php script");

// Create database connection
$database = new Database();
$db = $database->getConnection();

if (!$db) {
    error_log("Database connection failed in admin_reservations.php");
    http_response_code(503);
    echo json_encode(array("status" => "error", "message" => "Unable to connect to database."));
    exit();
}

error_log("Database connection successful in admin_reservations.php");

try {
    // Query to get all reservations with user information
    $query = "SELECT r.*, u.first_name, u.last_name, u.student_employee_id 
              FROM room_reservations r 
              JOIN users u ON r.user_id = u.user_id 
              WHERE r.status = 'pending'
              ORDER BY r.created_at DESC";
              
    error_log("Executing query: " . $query);
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $reservations = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Format the user's name
        $userName = $row['first_name'] . ' ' . $row['last_name'];
        
        // Get additional members if any
        $membersQuery = "SELECT name, student_id FROM room_reservation_members 
                        WHERE reservation_id = :reservation_id";
        $membersStmt = $db->prepare($membersQuery);
        $membersStmt->bindParam(":reservation_id", $row['reservation_id']);
        $membersStmt->execute();
        
        $members = array();
        while ($member = $membersStmt->fetch(PDO::FETCH_ASSOC)) {
            $members[] = array(
                "name" => $member['name'],
                "student_id" => $member['student_id']
            );
        }
        
        // Add reservation to array with formatted data
        $reservations[] = array(
            "reservation_id" => $row['reservation_id'],
            "user_name" => $userName,
            "student_id" => $row['student_employee_id'],
            "room_name" => $row['room_name'],
            "purpose" => $row['purpose'],
            "reservation_date" => $row['reservation_date'],
            "start_time" => $row['start_time'],
            "end_time" => $row['end_time'],
            "status" => $row['status'],
            "created_at" => $row['created_at'],
            "members" => $members
        );
    }
    
    error_log("Found " . count($reservations) . " reservations");
    
    // Return success response
    http_response_code(200);
    echo json_encode(array(
        "status" => "success",
        "reservations" => $reservations
    ));
    
} catch (PDOException $e) {
    error_log("Database error in admin_reservations.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(array(
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ));
}
?> 