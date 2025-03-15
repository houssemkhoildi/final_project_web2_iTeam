<?php
// models/User.php
class User {
    // Database connection and table name
    private $conn;
    private $table_name = "users";

    // Object properties
    public $id;
    public $username;
    public $email;
    public $password;
    public $first_name;
    public $last_name;
    public $role;
    public $avatar;
    public $active;
    public $created_at;
    public $updated_at;

    // Constructor with database connection
    public function __construct($db) {
        $this->conn = $db;
    }

    // Create new user
    public function create() {
        // Check if username or email already exists
        if($this->usernameExists() || $this->emailExists()) {
            return false;
        }
        
        // Query to insert record
        $query = "INSERT INTO " . $this->table_name . "
                  SET username=:username, email=:email, password=:password, 
                      first_name=:first_name, last_name=:last_name, role=:role";

        // Prepare query
        $stmt = $this->conn->prepare($query);

        // Sanitize inputs
        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->password = htmlspecialchars(strip_tags($this->password));
        $this->first_name = htmlspecialchars(strip_tags($this->first_name));
        $this->last_name = htmlspecialchars(strip_tags($this->last_name));
        $this->role = htmlspecialchars(strip_tags($this->role));

        // Hash the password
        $password_hash = password_hash($this->password, PASSWORD_DEFAULT);

        // Bind values
        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password", $password_hash);
        $stmt->bindParam(":first_name", $this->first_name);
        $stmt->bindParam(":last_name", $this->last_name);
        $stmt->bindParam(":role", $this->role);

        // Execute query
        if($stmt->execute()) {
            return true;
        }

        return false;
    }

    // Login user
    public function login() {
        // Query to read user data
        $query = "SELECT id, username, password, first_name, last_name, role, active
                  FROM " . $this->table_name . "
                  WHERE username = ? OR email = ?
                  LIMIT 0,1";

        // Prepare query
        $stmt = $this->conn->prepare($query);

        // Sanitize inputs
        $this->username = htmlspecialchars(strip_tags($this->username));

        // Bind parameter
        $stmt->bindParam(1, $this->username);
        $stmt->bindParam(2, $this->username); // Also check email

        // Execute query
        $stmt->execute();

        // Get returned rows count
        $num = $stmt->rowCount();

        // If user exists
        if($num > 0) {
            // Get record details
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            // Check if user is active
            if($row['active'] == 0) {
                return false;
            }

            // Assign values to object properties
            $this->id = $row['id'];
            $this->username = $row['username'];
            $this->first_name = $row['first_name'];
            $this->last_name = $row['last_name'];
            $this->role = $row['role'];

            // Verify password
            if(password_verify($this->password, $row['password'])) {
                return true;
            }
        }

        return false;
    }

    // Check if username exists
    public function usernameExists() {
        $query = "SELECT id FROM " . $this->table_name . " WHERE username = ? LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->username);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            return true;
        }
        return false;
    }

    // Check if email exists
    public function emailExists() {
        $query = "SELECT id FROM " . $this->table_name . " WHERE email = ? LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->email);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            return true;
        }
        return false;
    }

    // Read all users
    public function readAll() {
        $query = "SELECT id, username, email, first_name, last_name, role, active, created_at
                  FROM " . $this->table_name . "
                  ORDER BY created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    // Read one user
    public function readOne() {
        $query = "SELECT id, username, email, first_name, last_name, role, avatar, active, created_at, updated_at
                  FROM " . $this->table_name . "
                  WHERE id = ?
                  LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row) {
            $this->username = $row['username'];
            $this->email = $row['email'];
            $this->first_name = $row['first_name'];
            $this->last_name = $row['last_name'];
            $this->role = $row['role'];
            $this->avatar = $row['avatar'];
            $this->active = $row['active'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            return true;
        }

        return false;
    }

    // Update user
    public function update() {
        $query = "UPDATE " . $this->table_name . "
                  SET first_name=:first_name, last_name=:last_name, email=:email, role=:role
                  WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        // Sanitize inputs
        $this->first_name = htmlspecialchars(strip_tags($this->first_name));
        $this->last_name = htmlspecialchars(strip_tags($this->last_name));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->role = htmlspecialchars(strip_tags($this->role));
        $this->id = htmlspecialchars(strip_tags($this->id));

        // Bind parameters
        $stmt->bindParam(':first_name', $this->first_name);
        $stmt->bindParam(':last_name', $this->last_name);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':role', $this->role);
        $stmt->bindParam(':id', $this->id);

        if($stmt->execute()) {
            return true;
        }

        return false;
    }

    // Update password
    public function updatePassword() {
        $query = "UPDATE " . $this->table_name . "
                  SET password=:password
                  WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        // Hash the password
        $password_hash = password_hash($this->password, PASSWORD_DEFAULT);

        // Bind parameters
        $stmt->bindParam(':password', $password_hash);
        $stmt->bindParam(':id', $this->id);

        if($stmt->execute()) {
            return true;
        }

        return false;
    }

    // Toggle active status
    public function toggleActive() {
        $query = "UPDATE " . $this->table_name . "
                  SET active = NOT active
                  WHERE id=:id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);

        if($stmt->execute()) {
            return true;
        }

        return false;
    }

    // Count users
    public function count() {
        $query = "SELECT COUNT(*) as total_users FROM " . $this->table_name;
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['total_users'];
    }
}
?>