<?php
// Debug mode
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database connection
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'realestate';

try {
    // Connect to database
    $conn = new mysqli($host, $user, $password, $database);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Output connected
    echo "Successfully connected to database.<br>";
    
    // Check if favorites table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'favorites'");
    if ($tableCheck->num_rows > 0) {
        echo "Favorites table exists.<br>";
        
        // Check contents of favorites table
        $result = $conn->query("SELECT * FROM favorites");
        
        if ($result->num_rows > 0) {
            echo "Found " . $result->num_rows . " favorites:<br>";
            
            echo "<table border='1'>";
            echo "<tr><th>ID</th><th>Property ID</th><th>Buyer ID</th><th>Created At</th></tr>";
            
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $row["id"] . "</td>";
                echo "<td>" . $row["property_id"] . "</td>";
                echo "<td>" . $row["buyer_id"] . "</td>";
                echo "<td>" . $row["created_at"] . "</td>";
                echo "</tr>";
            }
            
            echo "</table>";
        } else {
            echo "Favorites table is empty.<br>";
        }
    } else {
        echo "Favorites table does not exist. Attempting to create it.<br>";
        
        // Create the favorites table
        $sql = "CREATE TABLE favorites (
            id INT AUTO_INCREMENT PRIMARY KEY,
            property_id INT NOT NULL,
            buyer_id INT NOT NULL,
            created_at DATETIME NOT NULL,
            UNIQUE KEY buyer_property (buyer_id, property_id)
        )";
        
        if ($conn->query($sql) === TRUE) {
            echo "Favorites table created successfully.<br>";
        } else {
            echo "Error creating favorites table: " . $conn->error . "<br>";
        }
    }
    
    // Test adding a favorite
    echo "<h3>Add a test favorite:</h3>";
    echo "<form method='post' action=''>
        <label>Property ID: <input type='number' name='property_id' value='1'></label><br>
        <label>Buyer ID: <input type='number' name='buyer_id' value='4'></label><br>
        <input type='submit' name='add_favorite' value='Add Favorite'>
    </form>";
    
    // Test removing a favorite
    echo "<h3>Remove a test favorite:</h3>";
    echo "<form method='post' action=''>
        <label>Property ID: <input type='number' name='property_id' value='1'></label><br>
        <label>Buyer ID: <input type='number' name='buyer_id' value='4'></label><br>
        <input type='submit' name='remove_favorite' value='Remove Favorite'>
    </form>";
    
    // Process add favorite
    if (isset($_POST['add_favorite'])) {
        $propertyId = (int)$_POST['property_id'];
        $buyerId = (int)$_POST['buyer_id'];
        
        // Check if property exists
        $checkProperty = $conn->query("SELECT id FROM properties WHERE id = $propertyId");
        if ($checkProperty->num_rows === 0) {
            echo "Warning: Property ID $propertyId does not exist in the database.<br>";
        }
        
        // Check if buyer exists
        $checkBuyer = $conn->query("SELECT id FROM users WHERE id = $buyerId AND role = 'buyer'");
        if ($checkBuyer->num_rows === 0) {
            echo "Warning: Buyer ID $buyerId does not exist in the database.<br>";
        }
        
        $stmt = $conn->prepare("INSERT INTO favorites (property_id, buyer_id, created_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE created_at = NOW()");
        $stmt->bind_param("ii", $propertyId, $buyerId);
        
        if ($stmt->execute()) {
            echo "Successfully added property $propertyId to favorites for buyer $buyerId.<br>";
        } else {
            echo "Error adding favorite: " . $stmt->error . "<br>";
        }
        
        $stmt->close();
    }
    
    // Process remove favorite
    if (isset($_POST['remove_favorite'])) {
        $propertyId = (int)$_POST['property_id'];
        $buyerId = (int)$_POST['buyer_id'];
        
        $stmt = $conn->prepare("DELETE FROM favorites WHERE property_id = ? AND buyer_id = ?");
        $stmt->bind_param("ii", $propertyId, $buyerId);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo "Successfully removed property $propertyId from favorites for buyer $buyerId.<br>";
            } else {
                echo "No favorite found to remove for property $propertyId and buyer $buyerId.<br>";
            }
        } else {
            echo "Error removing favorite: " . $stmt->error . "<br>";
        }
        
        $stmt->close();
    }
    
    // Close connection
    $conn->close();
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>