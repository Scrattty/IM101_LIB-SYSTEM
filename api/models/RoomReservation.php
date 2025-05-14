<?php
class RoomReservation {
    private $conn;
    private $table_name = "room_reservations";

    public $reservation_id;
    public $user_id;
    public $room_id;
    public $room_name;
    public $purpose;
    public $reservation_date;
    public $start_time;
    public $end_time;
    public $status;
    public $created_at;
    public $admin_assign;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create new room reservation
    public function createReservation() {
        try {
            // Only check for room conflicts if room_id is provided (not for admin_assign)
            if (!empty($this->room_id)) {
                // Check if room is already reserved for the given time
                $check_query = "SELECT reservation_id FROM " . $this->table_name . " 
                                WHERE room_id = :room_id 
                                AND reservation_date = :reservation_date 
                                AND ((start_time >= :start_time AND start_time < :end_time) 
                                    OR (end_time > :start_time AND end_time <= :end_time)
                                    OR (start_time <= :start_time AND end_time >= :end_time))
                                AND status != 'rejected'";
                
                $check_stmt = $this->conn->prepare($check_query);
                
                // Sanitize and bind values
                $this->room_id = htmlspecialchars(strip_tags($this->room_id));
                $this->reservation_date = htmlspecialchars(strip_tags($this->reservation_date));
                $this->start_time = htmlspecialchars(strip_tags($this->start_time));
                $this->end_time = htmlspecialchars(strip_tags($this->end_time));
                
                $check_stmt->bindParam(":room_id", $this->room_id);
                $check_stmt->bindParam(":reservation_date", $this->reservation_date);
                $check_stmt->bindParam(":start_time", $this->start_time);
                $check_stmt->bindParam(":end_time", $this->end_time);
                
                $check_stmt->execute();
                
                if ($check_stmt->rowCount() > 0) {
                    return array(
                        "status" => "error",
                        "message" => "Room is already reserved for this time period"
                    );
                }
            }
            
            // Prepare the query based on whether we have room data or if admin will assign later
            if ($this->admin_assign) {
                // For admin to assign a room later
                $query = "INSERT INTO " . $this->table_name . "
                        (user_id, purpose, reservation_date, start_time, end_time, 
                         status, admin_assign, created_at)
                        VALUES
                        (:user_id, :purpose, :reservation_date, :start_time, :end_time,
                         :status, TRUE, NOW())";

                $stmt = $this->conn->prepare($query);

                // Sanitize values
                $this->user_id = htmlspecialchars(strip_tags($this->user_id));
                $this->purpose = htmlspecialchars(strip_tags($this->purpose));
                $this->reservation_date = htmlspecialchars(strip_tags($this->reservation_date));
                $this->start_time = htmlspecialchars(strip_tags($this->start_time));
                $this->end_time = htmlspecialchars(strip_tags($this->end_time));
                $this->status = htmlspecialchars(strip_tags($this->status));

                // Log the query and data for debugging
                error_log("SQL for admin_assign: " . $query);
                error_log("Data: user_id={$this->user_id}, date={$this->reservation_date}, admin_assign=TRUE");

                $stmt->bindParam(":user_id", $this->user_id);
                $stmt->bindParam(":purpose", $this->purpose);
                $stmt->bindParam(":reservation_date", $this->reservation_date);
                $stmt->bindParam(":start_time", $this->start_time);
                $stmt->bindParam(":end_time", $this->end_time);
                $stmt->bindParam(":status", $this->status);
            } else {
                // Traditional reservation with room selection
                $query = "INSERT INTO " . $this->table_name . "
                        (user_id, room_id, room_name, purpose, reservation_date, 
                         start_time, end_time, status, admin_assign, created_at)
                        VALUES
                        (:user_id, :room_id, :room_name, :purpose, :reservation_date,
                         :start_time, :end_time, :status, FALSE, NOW())";

                $stmt = $this->conn->prepare($query);

                // Sanitize values
                $this->user_id = htmlspecialchars(strip_tags($this->user_id));
                $this->room_id = htmlspecialchars(strip_tags($this->room_id));
                $this->room_name = htmlspecialchars(strip_tags($this->room_name));
                $this->purpose = htmlspecialchars(strip_tags($this->purpose));
                $this->reservation_date = htmlspecialchars(strip_tags($this->reservation_date));
                $this->start_time = htmlspecialchars(strip_tags($this->start_time));
                $this->end_time = htmlspecialchars(strip_tags($this->end_time));
                $this->status = htmlspecialchars(strip_tags($this->status));

                // Log the query and data for debugging
                error_log("SQL for traditional: " . $query);
                error_log("Data: user_id={$this->user_id}, room_id={$this->room_id}, date={$this->reservation_date}, admin_assign=FALSE");

                $stmt->bindParam(":user_id", $this->user_id);
                $stmt->bindParam(":room_id", $this->room_id);
                $stmt->bindParam(":room_name", $this->room_name);
                $stmt->bindParam(":purpose", $this->purpose);
                $stmt->bindParam(":reservation_date", $this->reservation_date);
                $stmt->bindParam(":start_time", $this->start_time);
                $stmt->bindParam(":end_time", $this->end_time);
                $stmt->bindParam(":status", $this->status);
            }

            if ($stmt->execute()) {
                return array(
                    "status" => "success",
                    "message" => "Reservation created successfully",
                    "reservation_id" => $this->conn->lastInsertId()
                );
            }

            return array(
                "status" => "error",
                "message" => "Unable to create reservation"
            );

        } catch(PDOException $e) {
            error_log("Database error in createReservation: " . $e->getMessage());
            error_log("SQL state: " . $e->getCode());
            return array(
                "status" => "error",
                "message" => "Database error: " . $e->getMessage()
            );
        }
    }

    // Get all reservations
    public function getAllReservations() {
        $query = "SELECT r.*, u.first_name, u.last_name, u.student_employee_id 
                  FROM " . $this->table_name . " r
                  LEFT JOIN users u ON r.user_id = u.user_id
                  ORDER BY r.reservation_date DESC, r.start_time ASC";
                  
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }

    // Get reservations by user ID
    public function getReservationsByUser() {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE user_id = :user_id
                  ORDER BY reservation_date DESC, start_time ASC";
                  
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->execute();
        
        return $stmt;
    }

    // Update reservation status
    public function updateStatus() {
        try {
            $query = "UPDATE " . $this->table_name . "
                      SET status = :status
                      WHERE reservation_id = :reservation_id";
                      
            $stmt = $this->conn->prepare($query);
            
            $this->status = htmlspecialchars(strip_tags($this->status));
            $this->reservation_id = htmlspecialchars(strip_tags($this->reservation_id));
            
            $stmt->bindParam(":status", $this->status);
            $stmt->bindParam(":reservation_id", $this->reservation_id);
            
            if($stmt->execute()) {
                return array(
                    "status" => "success",
                    "message" => "Reservation status updated successfully"
                );
            }
            
            return array(
                "status" => "error",
                "message" => "Unable to update reservation status"
            );
            
        } catch(PDOException $e) {
            return array(
                "status" => "error",
                "message" => "Database error: " . $e->getMessage()
            );
        }
    }

    // Assign room to reservation (for admin)
    public function assignRoom() {
        try {
            $query = "UPDATE " . $this->table_name . "
                      SET room_id = :room_id, room_name = :room_name, admin_assign = FALSE
                      WHERE reservation_id = :reservation_id";
                      
            $stmt = $this->conn->prepare($query);
            
            $this->room_id = htmlspecialchars(strip_tags($this->room_id));
            $this->room_name = htmlspecialchars(strip_tags($this->room_name));
            $this->reservation_id = htmlspecialchars(strip_tags($this->reservation_id));
            
            $stmt->bindParam(":room_id", $this->room_id);
            $stmt->bindParam(":room_name", $this->room_name);
            $stmt->bindParam(":reservation_id", $this->reservation_id);
            
            if($stmt->execute()) {
                return array(
                    "status" => "success",
                    "message" => "Room assigned successfully"
                );
            }
            
            return array(
                "status" => "error",
                "message" => "Unable to assign room"
            );
            
        } catch(PDOException $e) {
            return array(
                "status" => "error",
                "message" => "Database error: " . $e->getMessage()
            );
        }
    }

    // Delete a reservation
    public function deleteReservation() {
        try {
            $query = "DELETE FROM " . $this->table_name . " 
                      WHERE reservation_id = :reservation_id";
                      
            $stmt = $this->conn->prepare($query);
            
            $this->reservation_id = htmlspecialchars(strip_tags($this->reservation_id));
            
            $stmt->bindParam(":reservation_id", $this->reservation_id);
            
            if($stmt->execute()) {
                return array(
                    "status" => "success",
                    "message" => "Reservation deleted successfully"
                );
            }
            
            return array(
                "status" => "error",
                "message" => "Unable to delete reservation"
            );
            
        } catch(PDOException $e) {
            return array(
                "status" => "error",
                "message" => "Database error: " . $e->getMessage()
            );
        }
    }

    // Get reservation by ID
    public function getReservationById() {
        try {
            $query = "SELECT * FROM " . $this->table_name . " 
                      WHERE reservation_id = :reservation_id";
                      
            $stmt = $this->conn->prepare($query);
            $this->reservation_id = htmlspecialchars(strip_tags($this->reservation_id));
            $stmt->bindParam(":reservation_id", $this->reservation_id);
            $stmt->execute();
            
            return $stmt;
        } catch(PDOException $e) {
            error_log("Error in getReservationById: " . $e->getMessage());
            return false;
        }
    }
    
    // Assign room and update status in one operation
    public function assignRoomAndUpdateStatus() {
        try {
            $query = "UPDATE " . $this->table_name . "
                      SET room_id = :room_id, 
                          room_name = :room_name, 
                          status = :status, 
                          admin_assign = FALSE
                      WHERE reservation_id = :reservation_id";
                      
            $stmt = $this->conn->prepare($query);
            
            $this->room_id = htmlspecialchars(strip_tags($this->room_id));
            $this->room_name = htmlspecialchars(strip_tags($this->room_name));
            $this->status = htmlspecialchars(strip_tags($this->status));
            $this->reservation_id = htmlspecialchars(strip_tags($this->reservation_id));
            
            $stmt->bindParam(":room_id", $this->room_id);
            $stmt->bindParam(":room_name", $this->room_name);
            $stmt->bindParam(":status", $this->status);
            $stmt->bindParam(":reservation_id", $this->reservation_id);
            
            if($stmt->execute()) {
                return array(
                    "status" => "success",
                    "message" => "Room assigned and status updated successfully"
                );
            }
            
            return array(
                "status" => "error",
                "message" => "Unable to assign room and update status"
            );
            
        } catch(PDOException $e) {
            error_log("Error in assignRoomAndUpdateStatus: " . $e->getMessage());
            return array(
                "status" => "error",
                "message" => "Database error: " . $e->getMessage()
            );
        }
    }

    // Get reservations with filters for admin panel
    public function getReservationsWithFilters($status = 'all', $date = '', $search = '') {
        try {
            $query = "SELECT * FROM " . $this->table_name . " WHERE 1=1";
            $params = array();
            
            // Add status filter if specified
            if ($status !== 'all') {
                $query .= " AND status = :status";
                $params[':status'] = $status;
            }
            
            // Add date filter if specified
            if ($date !== '') {
                $query .= " AND reservation_date = :date";
                $params[':date'] = $date;
            }
            
            // Add search filter if specified
            if ($search !== '') {
                $query .= " AND (purpose LIKE :search OR reservation_id LIKE :search OR room_name LIKE :search)";
                $params[':search'] = "%{$search}%";
            }
            
            // Order by most recent first
            $query .= " ORDER BY created_at DESC";
            
            $stmt = $this->conn->prepare($query);
            
            // Bind parameters
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $stmt->execute();
            return $stmt;
        } catch(PDOException $e) {
            error_log("Error in getReservationsWithFilters: " . $e->getMessage());
            return false;
        }
    }
    
    // Check if a room is available for a specific time slot
    public function isRoomAvailable($room_id, $reservation_date, $start_time, $end_time, $exclude_reservation_id = null) {
        try {
            $query = "SELECT reservation_id FROM " . $this->table_name . " 
                      WHERE room_id = :room_id 
                      AND reservation_date = :reservation_date 
                      AND ((start_time >= :start_time AND start_time < :end_time) 
                          OR (end_time > :start_time AND end_time <= :end_time)
                          OR (start_time <= :start_time AND end_time >= :end_time))
                      AND status != 'rejected'";
            
            // Exclude current reservation if provided
            if ($exclude_reservation_id) {
                $query .= " AND reservation_id != :exclude_id";
            }
            
            $stmt = $this->conn->prepare($query);
            
            // Bind parameters
            $stmt->bindParam(":room_id", $room_id);
            $stmt->bindParam(":reservation_date", $reservation_date);
            $stmt->bindParam(":start_time", $start_time);
            $stmt->bindParam(":end_time", $end_time);
            
            if ($exclude_reservation_id) {
                $stmt->bindParam(":exclude_id", $exclude_reservation_id);
            }
            
            $stmt->execute();
            
            // If there are no reservations for this time slot, the room is available
            return $stmt->rowCount() === 0;
        } catch(PDOException $e) {
            error_log("Error in isRoomAvailable: " . $e->getMessage());
            return false;
        }
    }
}
?> 