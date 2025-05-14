<?php
class User {
    private $conn;
    private $table_name = "users";

    public $user_id;
    public $role;
    public $student_employee_id;
    public $last_name;
    public $first_name;
    public $middle_name;
    public $email;
    public $password;
    public $course;
    public $year_level;
    public $section;
    public $profile_image;
    public $created_at;
    public $updated_at;
    public $current_password;
    public $new_password;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Register new user
    public function register() {
        try {
            // Check if email already exists
            $query = "SELECT user_id FROM " . $this->table_name . " WHERE email = :email OR student_employee_id = :student_employee_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":email", $this->email);
            $stmt->bindParam(":student_employee_id", $this->student_employee_id);
            $stmt->execute();

            if($stmt->rowCount() > 0) {
                return array(
                    "status" => "error",
                    "message" => "Email or Student/Employee ID already exists"
                );
            }

            // Hash password
            $hashed_password = password_hash($this->password, PASSWORD_DEFAULT);

            // Insert query
            $query = "INSERT INTO " . $this->table_name . "
                    (role, student_employee_id, last_name, first_name, middle_name, 
                     email, password, course, year_level, section)
                    VALUES
                    (:role, :student_employee_id, :last_name, :first_name, :middle_name,
                     :email, :password, :course, :year_level, :section)";

            $stmt = $this->conn->prepare($query);

            // Sanitize and bind values
            $this->role = htmlspecialchars(strip_tags($this->role));
            $this->student_employee_id = htmlspecialchars(strip_tags($this->student_employee_id));
            $this->last_name = htmlspecialchars(strip_tags($this->last_name));
            $this->first_name = htmlspecialchars(strip_tags($this->first_name));
            $this->middle_name = htmlspecialchars(strip_tags($this->middle_name));
            $this->email = htmlspecialchars(strip_tags($this->email));
            $this->course = htmlspecialchars(strip_tags($this->course));
            $this->section = htmlspecialchars(strip_tags($this->section));

            $stmt->bindParam(":role", $this->role);
            $stmt->bindParam(":student_employee_id", $this->student_employee_id);
            $stmt->bindParam(":last_name", $this->last_name);
            $stmt->bindParam(":first_name", $this->first_name);
            $stmt->bindParam(":middle_name", $this->middle_name);
            $stmt->bindParam(":email", $this->email);
            $stmt->bindParam(":password", $hashed_password);
            $stmt->bindParam(":course", $this->course);
            $stmt->bindParam(":year_level", $this->year_level);
            $stmt->bindParam(":section", $this->section);

            if($stmt->execute()) {
                return array(
                    "status" => "success",
                    "message" => "Registration successful"
                );
            }

            return array(
                "status" => "error",
                "message" => "Registration failed"
            );

        } catch(PDOException $e) {
            return array(
                "status" => "error",
                "message" => "Database error: " . $e->getMessage()
            );
        }
    }

    // Login user
    public function login() {
        try {
            $query = "SELECT user_id, role, student_employee_id, last_name, first_name, 
                            middle_name, email, password, course, year_level, section, profile_image
                     FROM " . $this->table_name . "
                     WHERE (email = :email OR student_employee_id = :student_employee_id)";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":email", $this->email);
            $stmt->bindParam(":student_employee_id", $this->student_employee_id);
            $stmt->execute();

            if($stmt->rowCount() > 0) {
                $row = $stmt->fetch();

                if(password_verify($this->password, $row['password'])) {
                    // Remove password from the array
                    unset($row['password']);
                    
                    return array(
                        "status" => "success",
                        "message" => "Login successful",
                        "user" => $row
                    );
                }
            }

            return array(
                "status" => "error",
                "message" => "Invalid credentials"
            );

        } catch(PDOException $e) {
            return array(
                "status" => "error",
                "message" => "Database error: " . $e->getMessage()
            );
        }
    }

    // Get user by ID
    public function getUserById() {
        try {
            $query = "SELECT * FROM " . $this->table_name . " WHERE user_id = :user_id";
            $stmt = $this->conn->prepare($query);
            
            // Sanitize input
            $this->user_id = htmlspecialchars(strip_tags($this->user_id));
            
            // Bind parameter
            $stmt->bindParam(":user_id", $this->user_id);
            
            // Execute query
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                return $stmt->fetch(PDO::FETCH_ASSOC);
            }
            return null;
        } catch(PDOException $e) {
            error_log("Error in getUserById: " . $e->getMessage());
            return null;
        }
    }

    // Get all users
    public function getAllUsers() {
        try {
            $query = "SELECT * FROM " . $this->table_name . " ORDER BY last_name, first_name";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt;
        } catch(PDOException $e) {
            error_log("Error in getAllUsers: " . $e->getMessage());
            return false;
        }
    }

    // Get users by role
    public function getUsersByRole($role) {
        try {
            $query = "SELECT * FROM " . $this->table_name . " WHERE role = :role ORDER BY last_name, first_name";
            $stmt = $this->conn->prepare($query);
            
            // Sanitize input
            $role = htmlspecialchars(strip_tags($role));
            
            // Bind parameter
            $stmt->bindParam(":role", $role);
            
            // Execute query
            $stmt->execute();
            return $stmt;
        } catch(PDOException $e) {
            error_log("Error in getUsersByRole: " . $e->getMessage());
            return false;
        }
    }

    // Count users by role
    public function countUsersByRole($role = null) {
        try {
            if ($role) {
                $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " WHERE role = :role";
                $stmt = $this->conn->prepare($query);
                
                // Sanitize input
                $role = htmlspecialchars(strip_tags($role));
                
                // Bind parameter
                $stmt->bindParam(":role", $role);
            } else {
                $query = "SELECT COUNT(*) as count FROM " . $this->table_name;
                $stmt = $this->conn->prepare($query);
            }
            
            // Execute query
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $row['count'];
        } catch(PDOException $e) {
            error_log("Error in countUsersByRole: " . $e->getMessage());
            return 0;
        }
    }

    // Get total user count
    public function getUserCount() {
        try {
            $query = "SELECT COUNT(*) as count FROM " . $this->table_name;
            $stmt = $this->conn->prepare($query);
            
            // Execute query
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $row['count'];
        } catch(PDOException $e) {
            error_log("Error in getUserCount: " . $e->getMessage());
            return 0;
        }
    }

    // Get user count by role
    public function getUserCountByRole($role) {
        try {
            $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " WHERE role = :role";
            $stmt = $this->conn->prepare($query);
            
            // Sanitize input
            $role = htmlspecialchars(strip_tags($role));
            
            // Bind parameter
            $stmt->bindParam(":role", $role);
            
            // Execute query
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $row['count'];
        } catch(PDOException $e) {
            error_log("Error in getUserCountByRole: " . $e->getMessage());
            return 0;
        }
    }

    // Delete a user
    public function deleteUser($userId) {
        try {
            // First, check if the user exists
            $checkQuery = "SELECT user_id FROM " . $this->table_name . " WHERE user_id = :user_id";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->bindParam(':user_id', $userId);
            $checkStmt->execute();
            
            if ($checkStmt->rowCount() === 0) {
                return false; // User not found
            }
            
            // Delete the user
            $deleteQuery = "DELETE FROM " . $this->table_name . " WHERE user_id = :user_id";
            $deleteStmt = $this->conn->prepare($deleteQuery);
            $deleteStmt->bindParam(':user_id', $userId);
            
            return $deleteStmt->execute();
        } catch (PDOException $e) {
            throw new Exception("Database error: " . $e->getMessage());
        }
    }

    // Update user profile
    public function updateProfile() {
        try {
            // First verify the current password
            $query = "SELECT password FROM " . $this->table_name . " WHERE user_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $this->user_id);
            $stmt->execute();

            if ($stmt->rowCount() === 0) {
                return array(
                    "status" => "error",
                    "message" => "User not found"
                );
            }

            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!password_verify($this->current_password, $row['password'])) {
                return array(
                    "status" => "error",
                    "message" => "Current password is incorrect"
                );
            }

            // Validate new password if provided
            if (!empty($this->new_password)) {
                // Check password length
                if (strlen($this->new_password) < 8) {
                    return array(
                        "status" => "error",
                        "message" => "New password must be at least 8 characters long"
                    );
                }

                // Check for at least one uppercase letter
                if (!preg_match('/[A-Z]/', $this->new_password)) {
                    return array(
                        "status" => "error",
                        "message" => "New password must contain at least one uppercase letter"
                    );
                }

                // Check for at least one lowercase letter
                if (!preg_match('/[a-z]/', $this->new_password)) {
                    return array(
                        "status" => "error",
                        "message" => "New password must contain at least one lowercase letter"
                    );
                }

                // Check for at least one number
                if (!preg_match('/[0-9]/', $this->new_password)) {
                    return array(
                        "status" => "error",
                        "message" => "New password must contain at least one number"
                    );
                }

                // Check for at least one special character
                if (!preg_match('/[!@#$%^&*()\-_=+{};:,<.>]/', $this->new_password)) {
                    return array(
                        "status" => "error",
                        "message" => "New password must contain at least one special character (!@#$%^&*()-_=+{};:,<.>)"
                    );
                }
            }

            // Check if email is already taken by another user
            if (!empty($this->email)) {
                $query = "SELECT user_id FROM " . $this->table_name . " 
                         WHERE email = :email AND user_id != :user_id";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(":email", $this->email);
                $stmt->bindParam(":user_id", $this->user_id);
                $stmt->execute();

                if ($stmt->rowCount() > 0) {
                    return array(
                        "status" => "error",
                        "message" => "Email is already taken by another user"
                    );
                }
            }

            // Prepare update query
            $query = "UPDATE " . $this->table_name . "
                     SET last_name = :last_name,
                         first_name = :first_name,
                         middle_name = :middle_name,
                         email = :email";

            // Add password update if new password is provided
            if (!empty($this->new_password)) {
                $query .= ", password = :password";
            }

            $query .= " WHERE user_id = :user_id";

            $stmt = $this->conn->prepare($query);

            // Sanitize inputs
            $this->last_name = htmlspecialchars(strip_tags($this->last_name));
            $this->first_name = htmlspecialchars(strip_tags($this->first_name));
            $this->middle_name = htmlspecialchars(strip_tags($this->middle_name));
            $this->email = htmlspecialchars(strip_tags($this->email));

            // Bind parameters
            $stmt->bindParam(":last_name", $this->last_name);
            $stmt->bindParam(":first_name", $this->first_name);
            $stmt->bindParam(":middle_name", $this->middle_name);
            $stmt->bindParam(":email", $this->email);
            $stmt->bindParam(":user_id", $this->user_id);

            // Bind new password if provided
            if (!empty($this->new_password)) {
                $hashed_password = password_hash($this->new_password, PASSWORD_DEFAULT);
                $stmt->bindParam(":password", $hashed_password);
            }

            if ($stmt->execute()) {
                return array(
                    "status" => "success",
                    "message" => "Profile updated successfully"
                );
            }

            return array(
                "status" => "error",
                "message" => "Unable to update profile"
            );

        } catch(PDOException $e) {
            error_log("Error in updateProfile: " . $e->getMessage());
            return array(
                "status" => "error",
                "message" => "Database error: " . $e->getMessage()
            );
        }
    }
}
?> 