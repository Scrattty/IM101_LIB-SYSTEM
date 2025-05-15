<?php
$host = "localhost";
$user = "root";
$password = ""; // use your MySQL password
$dbname = "library_db";

// Create connection
$conn = new mysqli($host, $user, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_id'])) {
    $book_id = $_POST['book_id'];
    
    // First get the book's image path
    $stmt = $conn->prepare("SELECT image_path FROM books WHERE id = ?");
    $stmt->bind_param("i", $book_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $book = $result->fetch_assoc();
    
    // Delete the book
    $stmt = $conn->prepare("DELETE FROM books WHERE id = ?");
    $stmt->bind_param("i", $book_id);
    
    if ($stmt->execute()) {
        // If book had an image, delete it
        if ($book && $book['image_path'] && file_exists($book['image_path'])) {
            unlink($book['image_path']);
        }
        echo json_encode(['status' => 'success', 'message' => 'Book deleted successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error deleting book']);
    }
    
    $stmt->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}

$conn->close();
?> 