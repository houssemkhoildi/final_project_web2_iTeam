<?php
// models/Product.php
class Product {
    // Database connection and table name
    private $conn;
    private $table_name = "products";

    // Object properties
    public $id;
    public $name;
    public $description;
    public $price;
    public $stock;
    public $category_id;
    public $category_name;
    public $image_url;
    public $created_at;
    public $updated_at;

    // Constructor with database connection
    public function __construct($db) {
        $this->conn = $db;
    }

    // Read all products with category name
    public function readAll($search = "") {
        // Select all query with join for category name
        $query = "SELECT p.id, p.name, p.description, p.price, p.stock, p.category_id, 
                  c.name as category_name, p.image_url, p.created_at, p.updated_at
                  FROM " . $this->table_name . " p
                  LEFT JOIN categories c ON p.category_id = c.id";
        
        // Add search condition if provided
        if (!empty($search)) {
            $query .= " WHERE p.name LIKE :search OR p.description LIKE :search OR c.name LIKE :search";
        }
        
        $query .= " ORDER BY p.created_at DESC";

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

    // Create product
    public function create() {
        // Query to insert record
        $query = "INSERT INTO " . $this->table_name . "
                  SET name=:name, description=:description, price=:price, 
                      stock=:stock, category_id=:category_id, image_url=:image_url";

        // Prepare query
        $stmt = $this->conn->prepare($query);

        // Sanitize inputs
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->price = floatval($this->price);
        $this->stock = intval($this->stock);
        $this->category_id = htmlspecialchars(strip_tags($this->category_id));
        $this->image_url = htmlspecialchars(strip_tags($this->image_url));

        // Bind values
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":price", $this->price);
        $stmt->bindParam(":stock", $this->stock);
        $stmt->bindParam(":category_id", $this->category_id);
        $stmt->bindParam(":image_url", $this->image_url);

        // Execute query
        if($stmt->execute()) {
            return true;
        }

        return false;
    }
    
    // Read one product
    public function readOne() {
        // Query to read one record
        $query = "SELECT p.id, p.name, p.description, p.price, p.stock, p.category_id, 
                  c.name as category_name, p.image_url, p.created_at, p.updated_at
                  FROM " . $this->table_name . " p
                  LEFT JOIN categories c ON p.category_id = c.id
                  WHERE p.id = ?
                  LIMIT 0,1";

        // Prepare query
        $stmt = $this->conn->prepare($query);

        // Bind id of product to be read
        $stmt->bindParam(1, $this->id);

        // Execute query
        $stmt->execute();

        // Get retrieved row
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // Set values to object properties
        if($row) {
            $this->name = $row['name'];
            $this->description = $row['description'];
            $this->price = $row['price'];
            $this->stock = $row['stock'];
            $this->category_id = $row['category_id'];
            $this->category_name = $row['category_name'];
            $this->image_url = $row['image_url'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            return true;
        }
        
        return false;
    }
    
    // Update the product
    public function update() {
        // Update query
        $query = "UPDATE " . $this->table_name . "
                SET name = :name,
                    description = :description,
                    price = :price,
                    stock = :stock,
                    category_id = :category_id,
                    image_url = :image_url
                WHERE id = :id";

        // Prepare query
        $stmt = $this->conn->prepare($query);

        // Sanitize inputs
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->price = floatval($this->price);
        $this->stock = intval($this->stock);
        $this->category_id = htmlspecialchars(strip_tags($this->category_id));
        $this->image_url = htmlspecialchars(strip_tags($this->image_url));
        $this->id = htmlspecialchars(strip_tags($this->id));

        // Bind parameters
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':price', $this->price);
        $stmt->bindParam(':stock', $this->stock);
        $stmt->bindParam(':category_id', $this->category_id);
        $stmt->bindParam(':image_url', $this->image_url);
        $stmt->bindParam(':id', $this->id);

        // Execute query
        if($stmt->execute()) {
            return true;
        }

        return false;
    }
    
    // Delete the product
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
    
    // Get products by category
    public function getByCategory($category_id) {
        $query = "SELECT p.id, p.name, p.description, p.price, p.stock, p.image_url
                  FROM " . $this->table_name . " p
                  WHERE p.category_id = ?
                  ORDER BY p.name ASC";
                  
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $category_id);
        $stmt->execute();
        
        return $stmt;
    }
    
    // Count products
    public function count() {
        $query = "SELECT COUNT(*) as total_products FROM " . $this->table_name;
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['total_products'];
    }
    
    // Get low stock products
    public function getLowStock($threshold = 10) {
        $query = "SELECT p.id, p.name, p.stock, c.name as category_name
                  FROM " . $this->table_name . " p
                  LEFT JOIN categories c ON p.category_id = c.id
                  WHERE p.stock <= ?
                  ORDER BY p.stock ASC";
                  
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $threshold);
        $stmt->execute();
        
        return $stmt;
    }
    
    // Get total product value
    public function getTotalValue() {
        $query = "SELECT SUM(price * stock) as total_value FROM " . $this->table_name;
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['total_value'];
    }
    
    // Export all products to CSV
    public function exportToCSV() {
        $filename = 'products_export_' . date('Y-m-d_H-i-s') . '.csv';
        $filepath = '../exports/' . $filename;
        
        // Create file pointer
        $f = fopen($filepath, 'w');
        
        // Set column headers
        $headers = array('ID', 'Name', 'Description', 'Price', 'Stock', 'Category', 'Created At');
        fputcsv($f, $headers);
        
        // Get all products
        $stmt = $this->readAll();
        
        // Write data rows
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $lineData = array(
                $row['id'],
                $row['name'],
                $row['description'],
                $row['price'],
                $row['stock'],
                $row['category_name'],
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