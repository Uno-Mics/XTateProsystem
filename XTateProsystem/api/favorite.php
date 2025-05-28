<?php
// Enable all error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../inc/db.php';
require_once '../inc/functions.php';
require_once '../inc/auth.php';

// Start session
startSession();

// Set content type to JSON
header('Content-Type: application/json');

// Log request data
error_log("Favorite API Request: " . $_SERVER['REQUEST_METHOD']);
error_log("POST data: " . print_r($_POST, true));
error_log("SESSION data: " . print_r($_SESSION, true));

// Check if user is logged in and is a buyer
if (!isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'message' => 'You must be logged in to manage favorites',
        'redirect' => '/login.php'
    ]);
    exit;
}

if ($_SESSION['user_role'] !== 'buyer') {
    echo json_encode([
        'success' => false,
        'message' => 'Only buyers can manage favorites'
    ]);
    exit;
}

// Get the property ID
$propertyId = isset($_POST['property_id']) ? (int)$_POST['property_id'] : 0;
$userId = (int)$_SESSION['user_id'];
$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($propertyId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid property ID'
    ]);
    exit;
}

// Connect to database directly
try {
    $conn = connectDB();
    if (!$conn) {
        throw new Exception("Database connection failed");
    }
    
    // First ensure the favorites table exists
    $createTableSQL = "CREATE TABLE IF NOT EXISTS favorites (
        id INT AUTO_INCREMENT PRIMARY KEY,
        property_id INT NOT NULL,
        buyer_id INT NOT NULL,
        created_at DATETIME NOT NULL,
        UNIQUE KEY buyer_property (buyer_id, property_id)
    )";
    
    if ($conn->query($createTableSQL) !== TRUE) {
        throw new Exception("Error creating favorites table: " . $conn->error);
    }
    
    // Check if property exists
    $checkPropertySQL = "SELECT id FROM properties WHERE id = ?";
    $stmt = $conn->prepare($checkPropertySQL);
    $stmt->bind_param("i", $propertyId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        closeDB($conn);
        echo json_encode([
            'success' => false,
            'message' => 'Property not found'
        ]);
        exit;
    }
    
    if ($action === 'toggle') {
        // Check if already favorited
        $checkSQL = "SELECT id FROM favorites WHERE property_id = ? AND buyer_id = ?";
        $stmt = $conn->prepare($checkSQL);
        $stmt->bind_param("ii", $propertyId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Already exists, so remove it
            $deleteSQL = "DELETE FROM favorites WHERE property_id = ? AND buyer_id = ?";
            $stmt = $conn->prepare($deleteSQL);
            $stmt->bind_param("ii", $propertyId, $userId);
            
            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true,
                    'action' => 'removed',
                    'message' => 'Property removed from favorites'
                ]);
            } else {
                throw new Exception("Error removing from favorites: " . $stmt->error);
            }
        } else {
            // Does not exist, so add it
            $insertSQL = "INSERT INTO favorites (property_id, buyer_id, created_at) VALUES (?, ?, NOW())";
            $stmt = $conn->prepare($insertSQL);
            $stmt->bind_param("ii", $propertyId, $userId);
            
            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true,
                    'action' => 'added',
                    'message' => 'Property added to favorites'
                ]);
            } else {
                throw new Exception("Error adding to favorites: " . $stmt->error);
            }
        }
    } else if ($action === 'add') {
        // Add to favorites
        $insertSQL = "INSERT INTO favorites (property_id, buyer_id, created_at) VALUES (?, ?, NOW())
                      ON DUPLICATE KEY UPDATE created_at = NOW()";
        $stmt = $conn->prepare($insertSQL);
        $stmt->bind_param("ii", $propertyId, $userId);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'action' => 'added',
                'message' => 'Property added to favorites'
            ]);
        } else {
            throw new Exception("Error adding to favorites: " . $stmt->error);
        }
    } else if ($action === 'remove') {
        // Remove from favorites
        $deleteSQL = "DELETE FROM favorites WHERE property_id = ? AND buyer_id = ?";
        $stmt = $conn->prepare($deleteSQL);
        $stmt->bind_param("ii", $propertyId, $userId);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'action' => 'removed',
                'message' => 'Property removed from favorites'
            ]);
        } else {
            throw new Exception("Error removing from favorites: " . $stmt->error);
        }
    } else if ($action === 'check') {
        // Check if property is favorited
        $checkSQL = "SELECT id FROM favorites WHERE property_id = ? AND buyer_id = ?";
        $stmt = $conn->prepare($checkSQL);
        $stmt->bind_param("ii", $propertyId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        echo json_encode([
            'success' => true,
            'favorited' => ($result->num_rows > 0)
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action'
        ]);
    }
    
    closeDB($conn);
} catch (Exception $e) {
    error_log("Favorites API Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred: ' . $e->getMessage(),
        'error' => $e->getMessage()
    ]);
}