<?php
require_once '../inc/db.php';
require_once '../inc/functions.php';
require_once '../inc/auth.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set header to return JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Not logged in'
    ]);
    exit;
}

$userId = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'update_user':
        $userId = $_POST['user_id'] ?? 0;
        $fullName = sanitizeInput($_POST['full_name'] ?? '');
        $phone = sanitizeInput($_POST['phone'] ?? '');
        $address = sanitizeInput($_POST['address'] ?? '');
        $city = sanitizeInput($_POST['city'] ?? '');
        $state = sanitizeInput($_POST['state'] ?? '');
        $zipCode = sanitizeInput($_POST['zip_code'] ?? '');
        $bio = sanitizeInput($_POST['bio'] ?? '');
        $companyName = sanitizeInput($_POST['company_name'] ?? '');

        if (empty($userId) || empty($fullName)) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid parameters'
            ]);
            exit;
        }

        try {
            $sql = "UPDATE users SET 
                    full_name = ?, 
                    phone = ?, 
                    address = ?,
                    city = ?,
                    state = ?,
                    zip_code = ?,
                    bio = ?,
                    company_name = ?,
                    updated_at = NOW() 
                    WHERE id = ?";
            $result = updateData($sql, "ssssssssi", [
                $fullName, 
                $phone, 
                $address,
                $city,
                $state,
                $zipCode,
                $bio,
                $companyName,
                $userId
            ]);

            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'User updated successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to update user'
                ]);
            }
        } catch (Exception $e) {
            error_log("User update error: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'An error occurred while updating user'
            ]);
        }
        break;

    case 'delete_user':
        $userId = $_POST['user_id'] ?? 0;

        if (empty($userId)) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid user ID'
            ]);
            exit;
        }

        try {
            // Start transaction
            $conn = connectDB();
            $conn->begin_transaction();

            // Delete user's messages
            $sql = "DELETE FROM messages WHERE sender_id = ? OR receiver_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $userId, $userId);
            $stmt->execute();

            // Delete user's favorites
            $sql = "DELETE FROM favorites WHERE buyer_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $userId);
            $stmt->execute();

            // Delete user's inquiries
            $sql = "DELETE FROM inquiries WHERE buyer_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $userId);
            $stmt->execute();

            // Finally, delete the user
            $sql = "DELETE FROM users WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $userId);
            $stmt->execute();

            // Commit transaction
            $conn->commit();
            closeDB($conn);

            echo json_encode([
                'success' => true,
                'message' => 'User deleted successfully'
            ]);
        } catch (Exception $e) {
            if ($conn) {
                $conn->rollback();
                closeDB($conn);
            }
            error_log("User deletion error: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'An error occurred while deleting user'
            ]);
        }
        break;
    case 'update_status':
        $userId = $_POST['user_id'] ?? 0;
        $status = $_POST['status'] ?? '';

        if (empty($userId) || !in_array($status, ['active', 'inactive'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid parameters'
            ]);
            exit;
        }

        try {
            $sql = "UPDATE users SET status = ?, updated_at = NOW() WHERE id = ?";
            $result = updateData($sql, "si", [$status, $userId]);

            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'User status updated successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to update user status'
                ]);
            }
        } catch (Exception $e) {
            error_log("Status update error: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'An error occurred while updating status'
            ]);
        }
        break;

    case 'update_user':
        $userId = $_POST['user_id'] ?? $_SESSION['user_id'];
        $fullName = sanitizeInput($_POST['full_name'] ?? '');
        $phone = sanitizeInput($_POST['phone'] ?? '');
        $address = sanitizeInput($_POST['address'] ?? '');
        $city = sanitizeInput($_POST['city'] ?? '');
        $state = sanitizeInput($_POST['state'] ?? '');
        $zipCode = sanitizeInput($_POST['zip_code'] ?? '');
        $bio = sanitizeInput($_POST['bio'] ?? '');
        $companyName = sanitizeInput($_POST['company_name'] ?? '');

        if (empty($fullName)) {
            echo json_encode([
                'success' => false,
                'message' => 'Full name is required'
            ]);
            exit;
        }

        try {
            $sql = "UPDATE users SET 
                    full_name = ?, 
                    phone = ?, 
                    address = ?,
                    city = ?,
                    state = ?,
                    zip_code = ?,
                    bio = ?,
                    company_name = ?,
                    updated_at = NOW() 
                    WHERE id = ?";
            
            $result = updateData($sql, "ssssssssi", [
                $fullName, 
                $phone, 
                $address,
                $city,
                $state,
                $zipCode,
                $bio,
                $companyName,
                $userId
            ]);

            if ($result) {
                $_SESSION['user_name'] = $fullName;
                echo json_encode([
                    'success' => true,
                    'message' => 'Profile updated successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to update profile'
                ]);
            }
        } catch (Exception $e) {
            error_log("Profile update error: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'An error occurred while updating profile'
            ]);
        }
        break;

    case 'change_password':
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_new_password'] ?? '';

        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            echo json_encode([
                'success' => false,
                'message' => 'All password fields are required'
            ]);
            exit;
        }

        if ($newPassword !== $confirmPassword) {
            echo json_encode([
                'success' => false,
                'message' => 'New password and confirmation do not match'
            ]);
            exit;
        }

        try {
            $sql = "SELECT password FROM users WHERE id = ?";
            $user = fetchOne($sql, "i", [$userId]);

            if (!$user || !password_verify($currentPassword, $user['password'])) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Current password is incorrect'
                ]);
                exit;
            }

            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?";
            $result = updateData($sql, "si", [$hashedPassword, $userId]);

            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Password changed successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to change password'
                ]);
            }
        } catch (Exception $e) {
            error_log("Password change error: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'An error occurred while changing password'
            ]);
        }
        break;

    default:
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action'
        ]);
        break;
}

?>