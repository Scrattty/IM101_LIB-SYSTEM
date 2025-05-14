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

// Log the start of the script
error_log("Starting recent.php script");

// Create database connection
$database = new Database();
$db = $database->getConnection();

if (!$db) {
    error_log("Database connection failed in recent.php");
    http_response_code(503);
    echo json_encode(array("status" => "error", "message" => "Unable to connect to database."));
    exit();
}

error_log("Database connection successful in recent.php");

try {
    // Query to get recent activities from room reservations
    $query = "SELECT 
                'room' as type,
                CASE 
                    WHEN r.status = 'approved' THEN CONCAT(u.first_name, ' ', u.last_name, '''s room request for ', COALESCE(r.room_name, 'a room'), ' was approved')
                    WHEN r.status = 'rejected' THEN CONCAT(u.first_name, ' ', u.last_name, '''s room request for ', COALESCE(r.room_name, 'a room'), ' was rejected')
                    ELSE CONCAT(u.first_name, ' ', u.last_name, ' requested ', COALESCE(r.room_name, 'a room'))
                END as message,
                r.created_at,
                TIMESTAMPDIFF(MINUTE, r.created_at, NOW()) as minutes_ago
              FROM room_reservations r
              JOIN users u ON r.user_id = u.user_id
              WHERE r.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
              
              UNION ALL
              
              -- Add book transactions here when implemented
              -- SELECT 'book' as type, ... FROM book_transactions
              
              ORDER BY created_at DESC
              LIMIT 10";
              
    error_log("Executing query: " . $query);
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $activities = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Format the time ago
        $timeAgo = '';
        if ($row['minutes_ago'] < 60) {
            $timeAgo = $row['minutes_ago'] . ' minutes ago';
        } else if ($row['minutes_ago'] < 1440) { // Less than 24 hours
            $hours = floor($row['minutes_ago'] / 60);
            $timeAgo = $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
        } else {
            $days = floor($row['minutes_ago'] / 1440);
            $timeAgo = $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
        }
        
        $activities[] = array(
            "type" => $row['type'],
            "message" => $row['message'],
            "time_ago" => $timeAgo,
            "created_at" => $row['created_at']
        );
    }
    
    error_log("Found " . count($activities) . " activities");
    
    // Return success response
    http_response_code(200);
    echo json_encode(array(
        "status" => "success",
        "activities" => $activities
    ));
    
} catch (PDOException $e) {
    error_log("Database error in recent.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(array(
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ));
}
?> 