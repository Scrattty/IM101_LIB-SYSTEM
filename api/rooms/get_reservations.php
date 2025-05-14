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
include_once '../models/RoomReservation.php';
include_once '../models/User.php';

// Connect to database
$database = new Database();
$db = $database->getConnection();

if (!$db) {
    http_response_code(503); // Service Unavailable
    echo json_encode(array("status" => "error", "message" => "Unable to connect to database."));
    exit();
}

// Get filter parameters
$status = isset($_GET['status']) ? $_GET['status'] : 'all';
$date = isset($_GET['date']) ? $_GET['date'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Create reservation object
$reservation = new RoomReservation($db);
$result = $reservation->getReservationsWithFilters($status, $date, $search);

// Check if reservations exist
if (!$result) {
    http_response_code(500); // Internal Server Error
    echo json_encode(array("status" => "error", "message" => "Failed to retrieve reservations."));
    exit();
}

// Fetch all reservations
$reservations = array();
$user = new User($db);

while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    // Get user details
    $user->user_id = $row['user_id'];
    $userDetails = $user->getUserById();
    
    // Get members for this reservation
    $members_query = "SELECT name, student_id FROM reservation_members WHERE reservation_id = :reservation_id";
    $members_stmt = $db->prepare($members_query);
    $members_stmt->bindParam(":reservation_id", $row['reservation_id']);
    $members_stmt->execute();
    
    $members = array();
    while ($member = $members_stmt->fetch(PDO::FETCH_ASSOC)) {
        $members[] = array(
            "name" => $member['name'],
            "student_id" => $member['student_id']
        );
    }
    
    $reservation_item = array(
        "reservation_id" => $row['reservation_id'],
        "user_id" => $row['user_id'],
        "user_name" => $userDetails ? $userDetails['first_name'] . ' ' . $userDetails['last_name'] : 'Unknown User',
        "student_id" => $userDetails ? $userDetails['student_employee_id'] : 'N/A',
        "profile_image" => $userDetails && isset($userDetails['profile_image']) ? $userDetails['profile_image'] : null,
        "room_id" => $row['room_id'],
        "room_name" => $row['room_name'],
        "purpose" => $row['purpose'],
        "reservation_date" => $row['reservation_date'],
        "start_time" => $row['start_time'],
        "end_time" => $row['end_time'],
        "status" => $row['status'],
        "admin_assign" => $row['admin_assign'],
        "created_at" => $row['created_at'],
        "members" => $members
    );
    
    $reservations[] = $reservation_item;
}

// Return response
if (count($reservations) > 0) {
    http_response_code(200);
    echo json_encode(array(
        "status" => "success",
        "count" => count($reservations),
        "reservations" => $reservations
    ));
} else {
    http_response_code(200);
    echo json_encode(array(
        "status" => "success",
        "message" => "No reservations found.",
        "count" => 0,
        "reservations" => array()
    ));
}
?> 