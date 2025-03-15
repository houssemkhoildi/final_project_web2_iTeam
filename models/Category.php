<?php
// models/Category.php
class Category {
    // Database connection and table name
    private $conn;
    private $table_name = "categories";

    // Object properties
    public $id;
    public $name;
    public $description;
    public $created_at;
    public $updated_at;

    // Constructor with database connection
    public function __construct($db) {
        $this->conn = $db;
    }

    // Read all categories
    public function readAll($search = "") {
        // Select all query
        $query = "SELECT id, name, description, created_at, updated_at FROM " . $this->table_name;
        
        // Add search condition if provided
        if (!empty($search)) {
            $query .= " WHERE name LIKE :search OR description LIKE :search";
        }
        
        $query .= " ORDER BY name ASC";

        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind search parameter if provided
        if (!empty($search)) {
            $searchParam = "%{$search}%";
            $stmt->bindParam(":search", $searchParam);
        }

        // Execute query
        $stmt->execute();

        return $stmt;
    }

    // Create category
    public function create() {
        // Query to insert record
        $query = "INSERT INTO " . $this->table_name . " SET
                  name=:name, description=:description";

        // Prepare query
        $stmt = $this->conn->prepare($query);

        // Sanitize inputs
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->description = htmlspecialchars(strip_tags($this->description));

        // Bind values
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":description", $this->description);

        // Execute query
        if($stmt->execute()) {
            return true;
        }

        return false;
    }
    
    // Read one category
    public function readOne() {
        // Query to read one record
        $query = "SELECT id, name, description, created_at, updated_at
                  FROM " . $this->table_name . "
                  WHERE id = ?
                  LIMIT 0,1";

        // Prepare query
        $stmt = $this->conn->prepare($query);

        // Bind id of category to be read
        $stmt->bindParam(1, $this->id);

        // Execute query
        $stmt->execute();

        // Get retrieved row
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // Set values to object properties
        if($row) {
            $this->name = $row['name'];
            $this->description = $row['description'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            return true;
        }
        
        return false;
    }
    
    // Update the category
    public function update() {
        // Update query
        $query = "UPDATE " . $this->table_name . "
                SET name = :name,
                    description = :description
                WHERE id = :id";

        // Prepare query
        $stmt = $this->conn->prepare($query);

        // Sanitize inputs
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->id = htmlspecialchars(strip_tags($this->id));

        // Bind parameters
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':id', $this->id);

        // Execute query
        if($stmt->execute()) {
            return true;
        }

        return false;
    }
    
    // Delete the category
    public function delete() {
        // Delete query
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";

        // Prepare query
        $stmt = $this->conn->prepare($query);

        // Sanitize input
        $this->id = htmlspecialchars(strip_tags($this->id));

        // Bind parameter
        $stmt->bindParam(1, $this->id);

        // Execute query
        if($stmt->execute()) {
            return true;
        }

        return false;
    }
    
    // Check if category has products
    public function hasProducts() {
        $query = "SELECT COUNT(*) as count
                  FROM products
                  WHERE category_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['count'] > 0;
    }
    
    // Count categories
    public function count() {
        $query = "SELECT COUNT(*) as total_categories FROM " . $this->table_name;
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['total_categories'];
    }
    
    // Export all categories to CSV
    public function exportToCSV() {
        $filename = 'categories_export_' . date('Y-m-d_H-i-s') . '.csv';
        $filepath = '../exports/' . $filename;
        
        // Create file pointer
        $f = fopen($filepath, 'w');
        
        // Set column headers
        $headers = array('ID', 'Name', 'Description', 'Created At');
        fputcsv($f, $headers);
        
        // Get all categories
        $stmt = $this->readAll();
        
        // Write data rows
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $lineData = array(
                $row['id'],
                $row['name'],
                $row['description'],
                $row['created_at']
            );
            fputcsv($f, $lineData);
        }
        
        // Close the file
        fclose($f);
        
        return $filename;
    }
}
?>