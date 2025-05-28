<?php
require_once '../inc/db.php';
require_once '../inc/functions.php';
require_once '../inc/auth.php';

// Start session
startSession();

// Set header to return JSON
header('Content-Type: application/json');

// Handle request based on action
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

switch ($action) {
    case 'update_status':
        updateInquiryStatus();
        break;
    case 'delete_inquiry':
        deleteInquiry();
        break;
    default:
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action'
        ]);
        break;
}

/**
 * Update inquiry status (approve/reject)
 */
function updateInquiryStatus() {
    // Check if user is logged in and is a seller
    if (!isLoggedIn()) {
        echo json_encode([
            'success' => false,
            'message' => 'You must be logged in to update inquiry status',
            'redirect' => '/login.php'
        ]);
        return;
    }
    
    if ($_SESSION['user_role'] !== 'seller' && $_SESSION['user_role'] !== 'admin') {
        echo json_encode([
            'success' => false,
            'message' => 'You do not have permission to update inquiry status'
        ]);
        return;
    }
    
    $inquiryId = (int)($_POST['inquiry_id'] ?? 0);
    $status = sanitizeInput($_POST['status'] ?? '');
    
    if ($inquiryId <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid inquiry ID'
        ]);
        return;
    }
    
    if (!in_array($status, ['approved', 'rejected'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid status'
        ]);
        return;
    }
    
    // Get inquiry details to verify ownership (skip for admin)
    if ($_SESSION['user_role'] === 'seller') {
        $sql = "SELECT i.*, p.seller_id 
                FROM inquiries i 
                JOIN properties p ON i.property_id = p.id 
                WHERE i.id = ?";
        
        $inquiry = fetchOne($sql, "i", [$inquiryId]);
        
        if (!$inquiry) {
            echo json_encode([
                'success' => false,
                'message' => 'Inquiry not found'
            ]);
            return;
        }
        
        // Check if the inquiry is for a property owned by this seller
        if ($inquiry['seller_id'] != $_SESSION['user_id']) {
            echo json_encode([
                'success' => false,
                'message' => 'You do not have permission to update this inquiry'
            ]);
            return;
        }
    }
    
    // Update inquiry status
    $sql = "UPDATE inquiries SET status = ?, updated_at = NOW() WHERE id = ?";
    $result = updateData($sql, "si", [$status, $inquiryId]);
    
    if ($result) {
        // Get inquiry details for notification
        $inquiry = getInquiryById($inquiryId);
        
        // Create notification for buyer
        $notificationMessage = "Your inquiry for {$inquiry['property_title']} has been " . 
                               ($status === 'approved' ? 'approved' : 'rejected') . 
                               " by the seller.";
        
        // Check if notifications table exists before inserting
        $conn = connectDB();
        if ($conn) {
            $tableCheck = $conn->query("SHOW TABLES LIKE 'notifications'");
            if ($tableCheck && $tableCheck->num_rows > 0) {
                $sql = "INSERT INTO notifications (user_id, message, is_read, created_at) 
                        VALUES (?, ?, 0, NOW())";
                
                insertData($sql, "is", [$inquiry['buyer_id'], $notificationMessage]);
            }
            closeDB($conn);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Inquiry status updated successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update inquiry status'
        ]);
    }
}

/**
 * Delete an inquiry
 */
function deleteInquiry() {
    // Check if user is logged in
    if (!isLoggedIn()) {
        echo json_encode([
            'success' => false,
            'message' => 'You must be logged in to delete an inquiry',
            'redirect' => '/login.php'
        ]);
        return;
    }
    
    $inquiryId = (int)($_POST['inquiry_id'] ?? 0);
    
    if ($inquiryId <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid inquiry ID'
        ]);
        return;
    }
    
    // Get inquiry details to verify ownership
    $sql = "SELECT i.*, p.seller_id 
            FROM inquiries i 
            JOIN properties p ON i.property_id = p.id 
            WHERE i.id = ?";
    
    $inquiry = fetchOne($sql, "i", [$inquiryId]);
    
    if (!$inquiry) {
        echo json_encode([
            'success' => false,
            'message' => 'Inquiry not found'
        ]);
        return;
    }
    
    // Check permissions based on role
    if ($_SESSION['user_role'] === 'admin') {
        // Admin can delete any inquiry
    } elseif ($_SESSION['user_role'] === 'seller') {
        // Seller can only delete inquiries for their properties
        if ($inquiry['seller_id'] != $_SESSION['user_id']) {
            echo json_encode([
                'success' => false,
                'message' => 'You do not have permission to delete this inquiry'
            ]);
            return;
        }
    } elseif ($_SESSION['user_role'] === 'buyer') {
        // Buyer can only delete their own inquiries
        if ($inquiry['buyer_id'] != $_SESSION['user_id']) {
            echo json_encode([
                'success' => false,
                'message' => 'You do not have permission to delete this inquiry'
            ]);
            return;
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'You do not have permission to delete inquiries'
        ]);
        return;
    }
    
    // Delete inquiry
    $sql = "DELETE FROM inquiries WHERE id = ?";
    $result = deleteData($sql, "i", [$inquiryId]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Inquiry deleted successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to delete inquiry'
        ]);
    }
}
?>
