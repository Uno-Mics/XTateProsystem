<?php
// Maximum error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session for authentication
session_start();

// Set content type to JSON
header('Content-Type: application/json');

// Verify user is logged in and is a buyer
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'buyer') {
    echo json_encode([
        'success' => false,
        'message' => 'You must be logged in as a buyer to use favorites',
        'redirect' => '/login.php'
    ]);
    exit;
}

// Check if property ID is set
if (!isset($_POST['property_id']) || !is_numeric($_POST['property_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid property ID'
    ]);
    exit;
}

// Get the action and property ID
$action = isset($_POST['action']) ? $_POST['action'] : 'toggle';
$propertyId = (int)$_POST['property_id'];
$buyerId = (int)$_SESSION['user_id'];

// Connect to the database directly
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'realestate';

try {
    // Create database connection
    $conn = new mysqli($host, $user, $password, $database);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    
    // Create favorites table if it doesn't exist
    $createTableSQL = "CREATE TABLE IF NOT EXISTS favorites (
        id INT AUTO_INCREMENT PRIMARY KEY,
        property_id INT NOT NULL,
        buyer_id INT NOT NULL,
        created_at DATETIME NOT NULL,
        UNIQUE KEY buyer_property (buyer_id, property_id)
    )";
    
    if (!$conn->query($createTableSQL)) {
        throw new Exception("Failed to create favorites table: " . $conn->error);
    }
    
    // Check if entry already exists
    $stmt = $conn->prepare("SELECT id FROM favorites WHERE property_id = ? AND buyer_id = ?");
    $stmt->bind_param("ii", $propertyId, $buyerId);
    $stmt->execute();
    $result = $stmt->get_result();
    $isFavorited = ($result->num_rows > 0);
    $stmt->close();
    
    if ($action === 'check') {
        // Just return the current status
        echo json_encode([
            'success' => true,
            'favorited' => $isFavorited
        ]);
        exit;
    } 
    else if (($action === 'toggle' && $isFavorited) || $action === 'remove') {
        // Remove from favorites
        $stmt = $conn->prepare("DELETE FROM favorites WHERE property_id = ? AND buyer_id = ?");
        $stmt->bind_param("ii", $propertyId, $buyerId);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'action' => 'removed',
                'message' => 'Property removed from favorites'
            ]);
        } else {
            throw new Exception("Failed to remove favorite: " . $stmt->error);
        }
        $stmt->close();
    } 
    else if (($action === 'toggle' && !$isFavorited) || $action === 'add') {
        // Add to favorites
        $stmt = $conn->prepare("INSERT INTO favorites (property_id, buyer_id, created_at) VALUES (?, ?, NOW())");
        $stmt->bind_param("ii", $propertyId, $buyerId);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'action' => 'added',
                'message' => 'Property added to favorites'
            ]);
        } else {
            throw new Exception("Failed to add favorite: " . $stmt->error);
        }
        $stmt->close();
    }
    else {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action: ' . $action
        ]);
    }
    
    // Close database connection
    $conn->close();
} catch (Exception $e) {
    // Log error
    error_log("Favorites Error: " . $e->getMessage());
    
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
}
?>