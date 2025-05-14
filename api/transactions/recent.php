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

// Check if request method is GET or if running from command line
if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] === 'GET') {
    // Include necessary files
    include_once __DIR__ . '/../config/db.php';

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
        // First check if required tables exist
        $tables = array('room_reservations', 'users', 'book_transactions', 'books', 'book_inventory');
        $missing_tables = array();
        
        foreach ($tables as $table) {
            $check = $db->query("SHOW TABLES LIKE '$table'");
            if ($check->rowCount() == 0) {
                $missing_tables[] = $table;
            }
        }
        
        if (!empty($missing_tables)) {
            error_log("Missing tables: " . implode(', ', $missing_tables));
            // For now, we'll only use tables that exist
            $query_parts = array();
            
            // Room activities (if table exists)
            if (in_array('room_reservations', $tables) && in_array('users', $tables)) {
                $query_parts[] = "
                    SELECT 
                        'room' as type,
                        CASE 
                            WHEN r.status = 'pending' THEN CONCAT(u.first_name, ' ', u.last_name, ' requested a room')
                            WHEN r.status = 'approved' THEN CONCAT(u.first_name, ' ', u.last_name, '''s room request was approved')
                            WHEN r.status = 'rejected' THEN CONCAT(u.first_name, ' ', u.last_name, '''s room request was rejected')
                        END as message,
                        r.created_at
                    FROM room_reservations r
                    JOIN users u ON r.user_id = u.user_id
                    WHERE r.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                ";
            }
            
            // User registration activities (if table exists)
            if (in_array('users', $tables)) {
                $query_parts[] = "
                    SELECT 
                        'user' as type,
                        CONCAT(u.first_name, ' ', u.last_name, ' registered as a new member') as message,
                        u.created_at
                    FROM users u
                    WHERE u.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                ";
            }
            
            // If no valid query parts, return empty result
            if (empty($query_parts)) {
                echo json_encode(array(
                    "status" => "success",
                    "activities" => array(),
                    "message" => "No activity tables found"
                ));
                exit();
            }
            
            $query = "SELECT 
                        type,
                        message,
                        created_at,
                        TIMESTAMPDIFF(MINUTE, created_at, NOW()) as minutes_ago
                      FROM (" . implode(" UNION ALL ", $query_parts) . ") as activities
                      ORDER BY created_at DESC
                      LIMIT 10";
        } else {
            // Use the original query if all tables exist
            $query = "SELECT 
                        type,
                        message,
                        created_at,
                        TIMESTAMPDIFF(MINUTE, created_at, NOW()) as minutes_ago
                      FROM (
                        -- Room activities
                        SELECT 
                            'room' as type,
                            CASE 
                                WHEN r.status = 'pending' THEN CONCAT(u.first_name, ' ', u.last_name, ' requested a room')
                                WHEN r.status = 'approved' THEN CONCAT(u.first_name, ' ', u.last_name, '''s room request was approved')
                                WHEN r.status = 'rejected' THEN CONCAT(u.first_name, ' ', u.last_name, '''s room request was rejected')
                            END as message,
                            r.created_at
                        FROM room_reservations r
                        JOIN users u ON r.user_id = u.user_id
                        WHERE r.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                        
                        UNION ALL
                        
                        -- Book activities
                        SELECT 
                            'book' as type,
                            CASE 
                                WHEN bt.action = 'borrow' THEN CONCAT(u.first_name, ' ', u.last_name, ' borrowed ', b.title)
                                WHEN bt.action = 'return' THEN CONCAT(u.first_name, ' ', u.last_name, ' returned ', b.title)
                            END as message,
                            bt.created_at
                        FROM book_transactions bt
                        JOIN users u ON bt.user_id = u.user_id
                        JOIN books b ON bt.book_id = b.book_id
                        WHERE bt.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                        
                        UNION ALL
                        
                        -- User registration activities
                        SELECT 
                            'user' as type,
                            CONCAT(u.first_name, ' ', u.last_name, ' registered as a new member') as message,
                            u.created_at
                        FROM users u
                        WHERE u.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                        
                        UNION ALL
                        
                        -- Inventory activities
                        SELECT 
                            'inventory' as type,
                            CONCAT('Admin User added ', bi.quantity, ' new books to inventory') as message,
                            bi.created_at
                        FROM book_inventory bi
                        WHERE bi.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                      ) as activities
                      ORDER BY created_at DESC
                      LIMIT 10";
        }
        
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
                $timeAgo = 'Yesterday';
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
} else {
    http_response_code(405); // Method Not Allowed
    echo json_encode(array("status" => "error", "message" => "Method not allowed. Use GET instead."));
    exit();
}
?> 