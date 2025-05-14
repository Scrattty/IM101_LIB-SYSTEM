<?php
// Prevent any output
ob_start();

// Basic error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/../logs/php_errors.log');

// Set session parameters before starting
ini_set('session.cookie_path', '/');
ini_set('session.cookie_domain', '');
ini_set('session.cookie_secure', '0');
ini_set('session.cookie_httponly', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_samesite', 'Lax');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Detailed session debugging
error_log("=== Detailed Session Debug Info ===");
error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);
error_log("Request URI: " . $_SERVER['REQUEST_URI']);
error_log("HTTP Host: " . $_SERVER['HTTP_HOST']);
error_log("Script Filename: " . $_SERVER['SCRIPT_FILENAME']);
error_log("Document Root: " . $_SERVER['DOCUMENT_ROOT']);
error_log("Session Status: " . session_status());
error_log("Session ID: " . session_id());
error_log("Session Name: " . session_name());
error_log("Session Path: " . session_save_path());
error_log("Session Cookie Parameters: " . print_r(session_get_cookie_params(), true));
error_log("All Cookies: " . print_r($_COOKIE, true));
error_log("All Session Data: " . print_r($_SESSION, true));
error_log("All Request Headers: " . print_r(getallheaders(), true));
error_log("=== End Detailed Session Debug Info ===");

// Function to send JSON response
function sendJsonResponse($data, $statusCode = 200) {
    ob_clean();
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Function to send error response
function sendErrorResponse($message, $statusCode = 500) {
    error_log("Error Response: " . $message . " (Status: " . $statusCode . ")");
    error_log("Current Session State: " . print_r($_SESSION, true));
    sendJsonResponse(['error' => $message], $statusCode);
}

try {
    // Check if the required files exist
    $databaseFile = '../api/config/db.php';
    if (!file_exists($databaseFile)) {
        sendErrorResponse("Database configuration file not found at: " . $databaseFile, 500);
    }

    // Include database configuration
    require_once $databaseFile;

    // Check if user is logged in - using the same check as other admin pages
    if (!isset($_SESSION['user_id'])) {
        error_log("Session Debug - No user_id found. Full session data: " . print_r($_SESSION, true));
        error_log("Session Debug - All cookies: " . print_r($_COOKIE, true));
        error_log("Session Debug - Session ID: " . session_id());
        error_log("Session Debug - Session name: " . session_name());
        error_log("Session Debug - Session status: " . session_status());
        sendErrorResponse("Not logged in. Please log in again.", 401);
    }

    // Check if user is admin
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        error_log("Session Debug - User is not admin. Role: " . ($_SESSION['role'] ?? 'not set'));
        error_log("Session Debug - Full session data: " . print_r($_SESSION, true));
        sendErrorResponse("Not authorized as admin", 401);
    }

    // Log successful authentication
    error_log("User authenticated successfully. User ID: " . $_SESSION['user_id'] . ", Role: " . $_SESSION['role']);

    // Create database connection
    $db = new Database();
    $conn = $db->getConnection();
    
    if (!$conn) {
        sendErrorResponse("Database connection failed", 500);
    }

    // Test database connection
    try {
        $conn->query("SELECT 1");
    } catch (PDOException $e) {
        sendErrorResponse("Database connection test failed: " . $e->getMessage(), 500);
    }

    // Handle different request methods
    $method = $_SERVER['REQUEST_METHOD'];

    switch ($method) {
        case 'GET':
            // Check if tables exist
            $tables = ['announcements', 'users'];
            foreach ($tables as $table) {
                $result = $conn->query("SHOW TABLES LIKE '$table'");
                if ($result->rowCount() === 0) {
                    sendErrorResponse("Required table '$table' does not exist", 500);
                }
            }

            // Get all announcements
            $query = "SELECT a.*, CONCAT(u.first_name, ' ', u.last_name) as posted_by 
                     FROM announcements a 
                     JOIN users u ON a.user_id = u.user_id 
                     ORDER BY a.created_at DESC";
            
            try {
                $stmt = $conn->prepare($query);
                $stmt->execute();
                $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
                sendJsonResponse($announcements);
            } catch (PDOException $e) {
                sendErrorResponse("Database query failed: " . $e->getMessage(), 500);
            }
            break;

        case 'POST':
            // Create new announcement
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['type']) || !isset($data['title']) || !isset($data['content'])) {
                sendErrorResponse('Missing required fields', 400);
            }

            try {
                $query = "INSERT INTO announcements (user_id, type, title, content) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->execute([$_SESSION['user_id'], $data['type'], $data['title'], $data['content']]);
                
                $announcement_id = $conn->lastInsertId();
                
                // Get the created announcement with user info
                $query = "SELECT a.*, CONCAT(u.first_name, ' ', u.last_name) as posted_by 
                         FROM announcements a 
                         JOIN users u ON a.user_id = u.user_id 
                         WHERE a.announcement_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->execute([$announcement_id]);
                $announcement = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$announcement) {
                    sendErrorResponse('Failed to retrieve created announcement', 500);
                }

                // Send success response
                sendJsonResponse([
                    'status' => 'success',
                    'message' => 'Announcement created successfully',
                    'data' => $announcement
                ]);
            } catch (PDOException $e) {
                error_log("Database error in POST announcement: " . $e->getMessage());
                sendErrorResponse('Database error while creating announcement: ' . $e->getMessage(), 500);
            }
            break;

        case 'PUT':
            // Update announcement
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Debug log the incoming data
            error_log("PUT request data: " . print_r($data, true));
            error_log("Session user_id: " . $_SESSION['user_id']);
            
            if (!isset($data['announcement_id']) || !isset($data['type']) || !isset($data['title']) || !isset($data['content'])) {
                error_log("Missing required fields in PUT request. Data received: " . print_r($data, true));
                sendErrorResponse('Missing required fields', 400);
            }

            try {
                // Ensure announcement_id is an integer
                $announcement_id = intval($data['announcement_id']);
                if ($announcement_id <= 0) {
                    error_log("Invalid announcement_id: " . $data['announcement_id']);
                    sendErrorResponse('Invalid announcement ID', 400);
                }

                // First check if the announcement exists and belongs to the user
                $checkQuery = "SELECT announcement_id FROM announcements WHERE announcement_id = ? AND user_id = ?";
                $checkStmt = $conn->prepare($checkQuery);
                $checkStmt->execute([$announcement_id, $_SESSION['user_id']]);
                
                if ($checkStmt->rowCount() === 0) {
                    error_log("Announcement not found or unauthorized. ID: $announcement_id, User: " . $_SESSION['user_id']);
                    sendErrorResponse('Announcement not found or unauthorized', 404);
                }

                // Proceed with update
                $query = "UPDATE announcements SET type = ?, title = ?, content = ? WHERE announcement_id = ? AND user_id = ?";
                $stmt = $conn->prepare($query);
                $result = $stmt->execute([$data['type'], $data['title'], $data['content'], $announcement_id, $_SESSION['user_id']]);
                
                if (!$result) {
                    error_log("Update failed. Error: " . print_r($stmt->errorInfo(), true));
                    sendErrorResponse('Failed to update announcement', 500);
                }
                
                // Get the updated announcement
                $query = "SELECT a.*, CONCAT(u.first_name, ' ', u.last_name) as posted_by 
                         FROM announcements a 
                         JOIN users u ON a.user_id = u.user_id 
                         WHERE a.announcement_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->execute([$announcement_id]);
                $announcement = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$announcement) {
                    error_log("Failed to retrieve updated announcement. ID: $announcement_id");
                    sendErrorResponse('Failed to retrieve updated announcement', 500);
                }

                // Send success response
                sendJsonResponse([
                    'status' => 'success',
                    'message' => 'Announcement updated successfully',
                    'data' => $announcement
                ]);
            } catch (PDOException $e) {
                error_log("Database error in PUT announcement: " . $e->getMessage());
                sendErrorResponse('Database error while updating announcement: ' . $e->getMessage(), 500);
            }
            break;

        case 'DELETE':
            // Delete announcement
            $announcement_id = $_GET['id'] ?? null;
            
            if (!$announcement_id) {
                sendErrorResponse('Missing announcement ID', 400);
            }

            try {
                $query = "DELETE FROM announcements WHERE announcement_id = ? AND user_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->execute([$announcement_id, $_SESSION['user_id']]);
                
                if ($stmt->rowCount() === 0) {
                    sendErrorResponse('Announcement not found or unauthorized', 404);
                }
                
                // Send success response
                sendJsonResponse([
                    'status' => 'success',
                    'message' => 'Announcement deleted successfully'
                ]);
            } catch (PDOException $e) {
                error_log("Database error in DELETE announcement: " . $e->getMessage());
                sendErrorResponse('Database error while deleting announcement: ' . $e->getMessage(), 500);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
} catch (Exception $e) {
    error_log("Error in announcements.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    sendErrorResponse("An unexpected error occurred: " . $e->getMessage());
}

// If we get here, something went wrong
sendErrorResponse("Invalid request method", 405);
?> 