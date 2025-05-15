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
    $title = $_POST['title'];
    $authors = $_POST['authors'];
    $isbn = $_POST['isbn'];
    $publisher = $_POST['publisher'];
    $year = $_POST['publication_year'];
    $edition = $_POST['edition'];
    $subject = $_POST['subject'];
    $location = $_POST['location'];
    $copies = $_POST['copies'];
    $description = $_POST['description'];

    // Handle image upload
    $imagePath = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $imageName = basename($_FILES['image']['name']);
        $targetDir = "uploads/";
        $imagePath = $targetDir . time() . "_" . $imageName;
        move_uploaded_file($_FILES['image']['tmp_name'], $imagePath);
        
        // Delete old image if exists
        $stmt = $conn->prepare("SELECT image_path FROM books WHERE id = ?");
        $stmt->bind_param("i", $book_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $oldBook = $result->fetch_assoc();
        if ($oldBook && $oldBook['image_path'] && file_exists($oldBook['image_path'])) {
            unlink($oldBook['image_path']);
        }
    }

    // Update book
    if ($imagePath) {
        $stmt = $conn->prepare("UPDATE books SET title=?, authors=?, isbn=?, publisher=?, publication_year=?, edition=?, subject=?, location=?, copies=?, image_path=?, description=? WHERE id=?");
        $stmt->bind_param("ssssisssissi", $title, $authors, $isbn, $publisher, $year, $edition, $subject, $location, $copies, $imagePath, $description, $book_id);
    } else {
        $stmt = $conn->prepare("UPDATE books SET title=?, authors=?, isbn=?, publisher=?, publication_year=?, edition=?, subject=?, location=?, copies=?, description=? WHERE id=?");
        $stmt->bind_param("ssssisssisi", $title, $authors, $isbn, $publisher, $year, $edition, $subject, $location, $copies, $description, $book_id);
    }

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Book updated successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error updating book: ' . $stmt->error]);
    }

    $stmt->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}

$conn->close();
?> 