<?php
// Database connection
$host = "localhost";
$user = "root";
$password = "";
$dbname = "library_db";

// Create connection
$conn = new mysqli($host, $user, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die(json_encode(['status' => 'error', 'message' => 'Connection failed: ' . $conn->connect_error]));
}

try {
    // Get all books that have copies available
    $sql = "SELECT id, title, authors, isbn, publisher, publication_year, 
            edition, subject, location, copies, description, image_path 
            FROM books 
            WHERE copies > 0 
            ORDER BY title ASC";
    
    $result = $conn->query($sql);
    
    if ($result) {
        $books = array();
        while ($row = $result->fetch_assoc()) {
            // Format the data for frontend display
            $books[] = array(
                'id' => $row['id'],
                'title' => $row['title'],
                'authors' => $row['authors'],
                'isbn' => $row['isbn'],
                'publisher' => $row['publisher'],
                'publication_year' => $row['publication_year'],
                'edition' => $row['edition'],
                'subject' => $row['subject'],
                'location' => $row['location'],
                'copies' => $row['copies'],
                'description' => $row['description'],
                'image_path' => $row['image_path']
            );
        }
        
        echo json_encode(['status' => 'success', 'books' => $books]);
    } else {
        throw new Exception("Error fetching books: " . $conn->error);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$conn->close();
?> 