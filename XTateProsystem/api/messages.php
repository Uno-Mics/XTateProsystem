<?php
require_once '../inc/db.php';
require_once '../inc/functions.php';
require_once '../inc/auth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized'
    ]);
    exit;
}

// Get user ID
$userId = $_SESSION['user_id'];

// Handle requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle POST requests
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if ($action === 'send_message') {
        // Send message to partner
        $receiverId = isset($_POST['receiver_id']) ? intval($_POST['receiver_id']) : 0;
        $message = isset($_POST['message']) ? trim($_POST['message']) : '';
        
        header('Content-Type: application/json');
        
        if (empty($message)) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Message cannot be empty'
            ]);
            exit;
        }

        if ($receiverId <= 0) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Invalid receiver'
            ]);
            exit;
        }

        // Verify receiver exists
        $sql = "SELECT id FROM users WHERE id = ?";
        $receiver = fetchOne($sql, "i", [$receiverId]);

        if (!$receiver) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Receiver not found'
            ]);
            exit;
        }

        // Insert message
        $sql = "INSERT INTO messages (sender_id, receiver_id, message, is_read, created_at) 
                VALUES (?, ?, ?, 0, NOW())";
        $messageId = insertData($sql, "iis", [$userId, $receiverId, $message]);

        if ($messageId) {
            // If there's a HTTP_REFERER header, redirect back to it
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Message sent successfully',
                'data' => [
                    'message_id' => $messageId,
                    'created_at' => date('Y-m-d H:i:s'),
                    'timestamp' => (new DateTime())->format('c')
                ]
            ]);
            exit;
        } else {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Failed to send message'
            ]);
            exit;
        }
    } else if ($action === 'mark_read') {
        // Mark messages as read from sender
        $senderId = isset($_POST['sender_id']) ? intval($_POST['sender_id']) : 0;

        if ($senderId <= 0) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Invalid sender'
            ]);
            exit;
        }

        // Update messages
        $sql = "UPDATE messages SET is_read = 1 
                WHERE sender_id = ? AND receiver_id = ? AND is_read = 0";
        $result = updateData($sql, "ii", [$senderId, $userId]);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Messages marked as read'
        ]);
        exit;
    } else {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action'
        ]);
        exit;
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Handle GET requests
    $action = isset($_GET['action']) ? $_GET['action'] : '';

    if ($action === 'get_contact_details') {
        $contactId = isset($_GET['contact_id']) ? intval($_GET['contact_id']) : 0;

        if ($contactId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid contact ID']);
            exit;
        }

        $sql = "SELECT * FROM contacts WHERE id = ?";
        $contact = fetchOne($sql, "i", [$contactId]);

        if ($contact) {
            echo json_encode([
                'success' => true,
                'data' => $contact
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Contact not found']);
        }
        exit;
    } else if ($action === 'get_messages') {
        // Get messages between user and partner
        $partnerId = isset($_GET['partner_id']) ? intval($_GET['partner_id']) : 0;
        $contactEmail = isset($_GET['contact_email']) ? $_GET['contact_email'] : '';

        // If contact email is provided, get user ID from email
        if (!empty($contactEmail)) {
            $sql = "SELECT id FROM users WHERE email = ?";
            $user = fetchOne($sql, "s", [$contactEmail]);
            if ($user) {
                $partnerId = $user['id'];
            }
        }

        if ($partnerId <= 0) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Invalid partner'
            ]);
            exit;
        }

        // Get messages
        $sql = "SELECT m.id, m.sender_id, m.receiver_id, m.message, m.is_read, m.created_at,
                s.full_name as sender_name, 
                r.full_name as receiver_name 
                FROM messages m
                JOIN users s ON m.sender_id = s.id
                JOIN users r ON m.receiver_id = r.id
                WHERE (m.sender_id = ? AND m.receiver_id = ?) 
                   OR (m.sender_id = ? AND m.receiver_id = ?)
                ORDER BY m.created_at ASC";

        $messages = fetchAll($sql, "iiii", [$userId, $partnerId, $partnerId, $userId]);

        // Mark messages as read
        $sql = "UPDATE messages SET is_read = 1 
                WHERE sender_id = ? AND receiver_id = ? AND is_read = 0";
        updateData($sql, "ii", [$partnerId, $userId]);

        // Format messages
        $formattedMessages = [];
        foreach ($messages as $message) {
            $isSender = $message['sender_id'] == $userId;

            $formattedMessages[] = [
                'id' => $message['id'],
                'sender_id' => $message['sender_id'],
                'receiver_id' => $message['receiver_id'],
                'message' => $message['message'],
                'is_read' => $message['is_read'],
                'created_at' => $message['created_at'],
                'sender_name' => $message['sender_name'],
                'receiver_name' => $message['receiver_name'],
                'is_sent_by_me' => $isSender,
                'formatted_time' => formatMessageTime($message['created_at'])
            ];
        }

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => [
                'messages' => $formattedMessages
            ]
        ]);
        exit;
    } else if ($action === 'get_partners') {
        // Get conversation partners
        $sql = "SELECT DISTINCT 
                CASE 
                    WHEN m.sender_id = ? THEN m.receiver_id 
                    ELSE m.sender_id 
                END as partner_id,
                u.full_name,
                u.email,
                u.role,
                (SELECT COUNT(*) FROM messages 
                 WHERE sender_id = partner_id AND receiver_id = ? AND is_read = 0) as unread_count,
                (SELECT MAX(created_at) FROM messages 
                 WHERE (sender_id = ? AND receiver_id = partner_id) 
                    OR (sender_id = partner_id AND receiver_id = ?)) as last_message_time
                FROM messages m
                JOIN users u ON u.id = 
                    CASE 
                        WHEN m.sender_id = ? THEN m.receiver_id 
                        ELSE m.sender_id 
                    END
                WHERE m.sender_id = ? OR m.receiver_id = ?
                ORDER BY last_message_time DESC";

        $partners = fetchAll($sql, "iiiiiii", [$userId, $userId, $userId, $userId, $userId, $userId, $userId]);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => [
                'partners' => $partners
            ]
        ]);
        exit;
    } else if ($action === 'unread_count') {
        // Get unread messages count
        $sql = "SELECT COUNT(*) as count FROM messages WHERE receiver_id = ? AND is_read = 0";
        $result = fetchOne($sql, "i", [$userId]);
        $count = $result ? $result['count'] : 0;

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => [
                'unread_count' => $count
            ]
        ]);
        exit;
    } else {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action'
        ]);
        exit;
    }
} else {
    // Handle other HTTP methods
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}
?>