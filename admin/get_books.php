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

// Fetch all books
$sql = "SELECT * FROM books";
$result = $conn->query($sql);

$books = array();
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $books[] = $row;
    }
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($books);

$conn->close();
?> 