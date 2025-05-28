
<?php
require_once '../inc/db.php';
require_once '../inc/functions.php';
require_once '../inc/auth.php';

header('Content-Type: application/json');

// Check if user is logged in and is a buyer
if (!isLoggedIn() || $_SESSION['user_role'] !== 'buyer') {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$reporterId = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';
$reason = sanitizeInput($_POST['reason'] ?? '');

if (empty($reason)) {
    echo json_encode(['error' => 'Please provide a reason for the report']);
    exit;
}

try {
    if ($action === 'report_seller' && isset($_POST['seller_id'])) {
        $sellerId = (int)$_POST['seller_id'];
        $sql = "INSERT INTO reports (reporter_id, seller_id, reason) VALUES (?, ?, ?)";
        insertData($sql, "iis", [$reporterId, $sellerId, $reason]);
        
        echo json_encode(['success' => true, 'message' => 'Seller has been reported successfully']);
    }
    elseif ($action === 'report_property' && isset($_POST['property_id'])) {
        $propertyId = (int)$_POST['property_id'];
        $sql = "INSERT INTO reports (reporter_id, property_id, reason) VALUES (?, ?, ?)";
        insertData($sql, "iis", [$reporterId, $propertyId, $reason]);
        
        echo json_encode(['success' => true, 'message' => 'Property has been reported successfully']);
    }
    else {
        echo json_encode(['error' => 'Invalid action']);
    }
} catch (Exception $e) {
    error_log("Error submitting report: " . $e->getMessage());
    echo json_encode(['error' => 'Failed to submit report']);
}
