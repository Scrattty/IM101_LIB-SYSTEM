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

// Initialize response array
$response = array('status' => 'error', 'message' => '');

try {
    // Validate required fields
    $required_fields = ['title', 'authors', 'subject', 'location', 'copies'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Please fill in all required fields.");
        }
    }

    // Sanitize and prepare data
    $title = htmlspecialchars(trim($_POST['title']));
    $authors = htmlspecialchars(trim($_POST['authors']));
    $isbn = htmlspecialchars(trim($_POST['isbn'] ?? ''));
    $publisher = htmlspecialchars(trim($_POST['publisher'] ?? ''));
    $publication_year = htmlspecialchars(trim($_POST['publication_year'] ?? ''));
    $edition = htmlspecialchars(trim($_POST['edition'] ?? ''));
    $subject = htmlspecialchars(trim($_POST['subject']));
    $location = htmlspecialchars(trim($_POST['location']));
    $copies = (int)$_POST['copies'];
    $description = htmlspecialchars(trim($_POST['description'] ?? ''));

    // Handle image upload
    $image_path = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/books/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array($file_extension, $allowed_extensions)) {
            throw new Exception("Invalid file type. Only JPG, JPEG, PNG, and GIF files are allowed.");
        }

        // Generate unique filename
        $filename = uniqid('book_') . '.' . $file_extension;
        $target_path = $upload_dir . $filename;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
            $image_path = 'uploads/books/' . $filename;
        } else {
            throw new Exception("Failed to upload image.");
        }
    }

    // Prepare SQL statement
    $sql = "INSERT INTO books (
        title, authors, isbn, publisher, publication_year, 
        edition, subject, location, copies, description, 
        image_path, created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }

    // Bind parameters
    $stmt->bind_param(
        "ssssssssiss",
        $title, $authors, $isbn, $publisher, $publication_year,
        $edition, $subject, $location, $copies, $description,
        $image_path
    );

    // Execute the statement
    if ($stmt->execute()) {
        $response['status'] = 'success';
        $response['message'] = 'Book added successfully!';
        $response['book_id'] = $conn->insert_id;
    } else {
        throw new Exception("Failed to save book: " . $stmt->error);
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

// Close statement and connection
if (isset($stmt)) {
    $stmt->close();
}
$conn->close();

// Send JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>
