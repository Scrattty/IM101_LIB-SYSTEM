<?php
// Include database configuration
include_once 'api/config/db.php';

// Create a new database connection
$database = new Database();
$db = $database->getConnection();

if (!$db) {
    die("Database connection failed");
}

// Query to get table structure
$query = "DESCRIBE users";
$stmt = $db->prepare($query);
$stmt->execute();

// Display table structure
echo "<h1>Users Table Structure</h1>";
echo "<table border='1'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "<tr>";
    echo "<td>" . $row['Field'] . "</td>";
    echo "<td>" . $row['Type'] . "</td>";
    echo "<td>" . $row['Null'] . "</td>";
    echo "<td>" . $row['Key'] . "</td>";
    echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
    echo "<td>" . $row['Extra'] . "</td>";
    echo "</tr>";
}

echo "</table>";
?> 