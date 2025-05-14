<?php
class Room {
    private $conn;
    private $table_name = "rooms";

    public $room_id;
    public $room_name;
    public $room_type;
    public $capacity;
    public $description;
    public $is_available;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Get all available rooms
    public function getAllRooms() {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE is_available = 1 
                  ORDER BY room_name ASC";
                  
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }

    // Get room by ID
    public function getRoomById() {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE room_id = :room_id";
                  
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":room_id", $this->room_id);
        $stmt->execute();
        
        return $stmt;
    }

    // Check if room is available for a specific time slot
    public function checkRoomAvailability($room_id, $reservation_date, $start_time, $end_time) {
        try {
            $query = "SELECT r.reservation_id 
                      FROM room_reservations r
                      WHERE r.room_id = :room_id 
                      AND r.reservation_date = :reservation_date 
                      AND ((r.start_time >= :start_time AND r.start_time < :end_time) 
                          OR (r.end_time > :start_time AND r.end_time <= :end_time)
                          OR (r.start_time <= :start_time AND r.end_time >= :end_time))
                      AND r.status != 'rejected'";
                      
            $stmt = $this->conn->prepare($query);
            
            // Sanitize inputs
            $room_id = htmlspecialchars(strip_tags($room_id));
            $reservation_date = htmlspecialchars(strip_tags($reservation_date));
            $start_time = htmlspecialchars(strip_tags($start_time));
            $end_time = htmlspecialchars(strip_tags($end_time));
            
            $stmt->bindParam(":room_id", $room_id);
            $stmt->bindParam(":reservation_date", $reservation_date);
            $stmt->bindParam(":start_time", $start_time);
            $stmt->bindParam(":end_time", $end_time);
            
            $stmt->execute();
            
            // If there are no reservations for this time slot, the room is available
            return $stmt->rowCount() === 0;
            
        } catch(PDOException $e) {
            return false;
        }
    }

    // Create a new room (admin function)
    public function createRoom() {
        try {
            $query = "INSERT INTO " . $this->table_name . "
                    (room_name, room_type, capacity, description, is_available)
                    VALUES
                    (:room_name, :room_type, :capacity, :description, :is_available)";

            $stmt = $this->conn->prepare($query);

            // Sanitize inputs
            $this->room_name = htmlspecialchars(strip_tags($this->room_name));
            $this->room_type = htmlspecialchars(strip_tags($this->room_type));
            $this->capacity = htmlspecialchars(strip_tags($this->capacity));
            $this->description = htmlspecialchars(strip_tags($this->description));
            $this->is_available = htmlspecialchars(strip_tags($this->is_available));

            $stmt->bindParam(":room_name", $this->room_name);
            $stmt->bindParam(":room_type", $this->room_type);
            $stmt->bindParam(":capacity", $this->capacity);
            $stmt->bindParam(":description", $this->description);
            $stmt->bindParam(":is_available", $this->is_available);

            if($stmt->execute()) {
                return array(
                    "status" => "success",
                    "message" => "Room created successfully",
                    "room_id" => $this->conn->lastInsertId()
                );
            }

            return array(
                "status" => "error",
                "message" => "Unable to create room"
            );

        } catch(PDOException $e) {
            return array(
                "status" => "error",
                "message" => "Database error: " . $e->getMessage()
            );
        }
    }

    // Update room details (admin function)
    public function updateRoom() {
        try {
            $query = "UPDATE " . $this->table_name . "
                      SET room_name = :room_name,
                          room_type = :room_type,
                          capacity = :capacity,
                          description = :description,
                          is_available = :is_available
                      WHERE room_id = :room_id";

            $stmt = $this->conn->prepare($query);

            // Sanitize inputs
            $this->room_id = htmlspecialchars(strip_tags($this->room_id));
            $this->room_name = htmlspecialchars(strip_tags($this->room_name));
            $this->room_type = htmlspecialchars(strip_tags($this->room_type));
            $this->capacity = htmlspecialchars(strip_tags($this->capacity));
            $this->description = htmlspecialchars(strip_tags($this->description));
            $this->is_available = htmlspecialchars(strip_tags($this->is_available));

            $stmt->bindParam(":room_id", $this->room_id);
            $stmt->bindParam(":room_name", $this->room_name);
            $stmt->bindParam(":room_type", $this->room_type);
            $stmt->bindParam(":capacity", $this->capacity);
            $stmt->bindParam(":description", $this->description);
            $stmt->bindParam(":is_available", $this->is_available);

            if($stmt->execute()) {
                return array(
                    "status" => "success",
                    "message" => "Room updated successfully"
                );
            }

            return array(
                "status" => "error",
                "message" => "Unable to update room"
            );

        } catch(PDOException $e) {
            return array(
                "status" => "error",
                "message" => "Database error: " . $e->getMessage()
            );
        }
    }
}
?> 