<?php
// Test script for room reservation workflow

// 1. Create a reservation
$reservationData = [
    'user_id' => 2, // Using an existing user ID
    'purpose' => 'Test reservation for group study',
    'reservation_date' => date('Y-m-d', strtotime('+2 days')), // 2 days from now
    'start_time' => '13:00:00',
    'end_time' => '14:00:00',
    'admin_assign' => true
];

echo "1. Creating reservation with data:\n";
print_r($reservationData);

// Make the API call to create reservation
$ch = curl_init('http://localhost/LIB/api/rooms/reserve.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($reservationData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "\nAPI Response (HTTP $httpCode):\n$response\n\n";

// Parse the response
$responseData = json_decode($response, true);

// Check if reservation was created successfully
if (isset($responseData['status']) && $responseData['status'] === 'success' && isset($responseData['reservation_id'])) {
    $reservationId = $responseData['reservation_id'];
    echo "Reservation created successfully with ID: $reservationId\n\n";
    
    // 2. Now, assign a room to the reservation
    echo "2. Assigning a room to the reservation\n";
    
    $assignRoomData = [
        'reservation_id' => $reservationId,
        'room_id' => 1, // Assuming room ID 1 exists
        'room_name' => 'Study Room 101', // Assuming this is the name for room ID 1
        'status' => 'approved' // Also approve the reservation
    ];
    
    echo "Assign room data:\n";
    print_r($assignRoomData);
    
    // Make the API call to assign a room
    $ch = curl_init('http://localhost/LIB/api/rooms/assign_room.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($assignRoomData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "\nAPI Response (HTTP $httpCode):\n$response\n\n";
    
    // 3. Verify the reservation with assigned room
    echo "3. Verifying the reservation\n";
    
    // Connect to the database
    include_once './api/config/db.php';
    $database = new Database();
    $conn = $database->getConnection();
    
    if ($conn) {
        $stmt = $conn->prepare("SELECT * FROM room_reservations WHERE reservation_id = :id");
        $stmt->bindParam(':id', $reservationId);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "Reservation details from database:\n";
            print_r($reservation);
            
            // Clean up - delete the test reservation
            echo "\nCleaning up - deleting test reservation\n";
            $stmt = $conn->prepare("DELETE FROM room_reservations WHERE reservation_id = :id");
            $stmt->bindParam(':id', $reservationId);
            $stmt->execute();
            echo "Test reservation deleted successfully.\n";
        } else {
            echo "Reservation not found in database.\n";
        }
    } else {
        echo "Failed to connect to database for verification.\n";
    }
} else {
    echo "Failed to create reservation.\n";
}

echo "\nTest completed.\n";
?> 