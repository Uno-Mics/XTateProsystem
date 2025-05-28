<?php
/**
 * Real Estate Listing System
 * WebSocket Server
 * 
 * This script handles WebSocket connections for real-time messaging
 * between buyers and sellers.
 */

require_once 'inc/db.php';
require_once 'inc/functions.php';
require_once 'inc/auth.php';

// Ratchet WebSocket libraries will be used in production
// For this prototype, we're implementing a simplified version

// Handle WebSocket handshake
function handleWebSocketHandshake() {
    // Get the WebSocket key from the headers
    $key = null;
    if (isset($_SERVER['HTTP_SEC_WEBSOCKET_KEY'])) {
        $key = $_SERVER['HTTP_SEC_WEBSOCKET_KEY'];
    } else {
        header('HTTP/1.1 400 Bad Request');
        return false;
    }
    
    // Generate the WebSocket accept header
    $acceptKey = base64_encode(sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
    
    // Send the WebSocket handshake response
    header('HTTP/1.1 101 Switching Protocols');
    header('Upgrade: websocket');
    header('Connection: Upgrade');
    header('Sec-WebSocket-Accept: ' . $acceptKey);
    
    return true;
}

// Decode a WebSocket frame
function decodeWebSocketFrame($data) {
    $len = ord($data[1]) & 127;
    $maskStart = 2;
    
    if ($len == 126) {
        $maskStart = 4;
    } else if ($len == 127) {
        $maskStart = 10;
    }
    
    $masks = substr($data, $maskStart, 4);
    $dataStart = $maskStart + 4;
    $data = substr($data, $dataStart);
    $decoded = '';
    
    for ($i = 0; $i < strlen($data); $i++) {
        $decoded .= $data[$i] ^ $masks[$i % 4];
    }
    
    return $decoded;
}

// Encode a WebSocket frame
function encodeWebSocketFrame($text) {
    $b1 = 0x80 | (0x1 & 0x0f);
    $length = strlen($text);
    
    if ($length <= 125) {
        $header = pack('CC', $b1, $length);
    } else if ($length < 65536) {
        $header = pack('CCn', $b1, 126, $length);
    } else {
        $header = pack('CCNN', $b1, 127, 0, $length);
    }
    
    return $header . $text;
}

// Send a WebSocket message
function sendWebSocketMessage($client, $message) {
    $encoded = encodeWebSocketFrame($message);
    fwrite($client, $encoded);
}

// Handle WebSocket messages
function handleWebSocketConnection($client) {
    // Read client data
    $data = fread($client, 4096);
    
    if (!$data) {
        return false;
    }
    
    // Decode the WebSocket frame
    $message = decodeWebSocketFrame($data);
    
    // Parse the JSON message
    $decoded = json_decode($message, true);
    
    if (!$decoded || !isset($decoded['type'])) {
        return true;
    }
    
    // Handle different message types
    switch ($decoded['type']) {
        case 'message':
            handleChatMessage($client, $decoded);
            break;
        case 'notification':
            handleNotification($client, $decoded);
            break;
    }
    
    return true;
}

// Handle chat messages
function handleChatMessage($client, $messageData) {
    // Validate message data
    if (!isset($messageData['receiver_id']) || !isset($messageData['message'])) {
        return;
    }
    
    // Get sender ID from the session
    session_start();
    if (!isset($_SESSION['user_id'])) {
        return;
    }
    
    $senderId = $_SESSION['user_id'];
    $receiverId = $messageData['receiver_id'];
    $message = $messageData['message'];
    
    // Store message in database
    $sql = "INSERT INTO messages (sender_id, receiver_id, message, is_read, created_at) 
            VALUES (?, ?, ?, 0, NOW())";
    
    $messageId = insertData($sql, "iis", [$senderId, $receiverId, $message]);
    
    if ($messageId) {
        // Format message for WebSocket response
        $response = [
            'type' => 'message',
            'id' => $messageId,
            'sender_id' => $senderId,
            'receiver_id' => $receiverId,
            'message' => $message,
            'is_read' => 0,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        // Send message back to sender for confirmation
        sendWebSocketMessage($client, json_encode($response));
        
        // In a real implementation, we would send the message to the receiver as well
        // through their active WebSocket connection
    }
}

// Handle notifications
function handleNotification($client, $notificationData) {
    // Validate notification data
    if (!isset($notificationData['receiver_id']) || !isset($notificationData['message'])) {
        return;
    }
    
    // Get sender ID from the session
    session_start();
    if (!isset($_SESSION['user_id'])) {
        return;
    }
    
    $senderId = $_SESSION['user_id'];
    $receiverId = $notificationData['receiver_id'];
    $message = $notificationData['message'];
    $title = $notificationData['title'] ?? 'New Notification';
    
    // Store notification in database
    $conn = connectDB();
    if ($conn) {
        $tableCheck = $conn->query("SHOW TABLES LIKE 'notifications'");
        if ($tableCheck && $tableCheck->num_rows > 0) {
            $sql = "INSERT INTO notifications (user_id, message, is_read, created_at) 
                    VALUES (?, ?, 0, NOW())";
            
            insertData($sql, "is", [$receiverId, $message]);
        }
        closeDB($conn);
    }
    
    // Send confirmation to sender
    $response = [
        'type' => 'notification_sent',
        'success' => true,
        'message' => 'Notification sent successfully'
    ];
    
    sendWebSocketMessage($client, json_encode($response));
}

// Main WebSocket handler
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_SERVER['HTTP_UPGRADE']) && strtolower($_SERVER['HTTP_UPGRADE']) === 'websocket') {
    // Perform WebSocket handshake
    if (handleWebSocketHandshake()) {
        // Set timeout to unlimited to keep connection open
        set_time_limit(0);
        
        // Turn off output buffering
        ob_end_clean();
        
        // Get the client connection
        $client = fopen('php://input', 'r');
        
        // Keep connection open for WebSocket communication
        while (true) {
            // Handle WebSocket connection
            if (!handleWebSocketConnection($client)) {
                break;
            }
            
            // Sleep to prevent CPU usage
            usleep(50000); // 50ms
        }
        
        // Close client connection
        fclose($client);
    }
} else {
    // Not a WebSocket request
    header('HTTP/1.1 400 Bad Request');
    echo 'This endpoint only accepts WebSocket connections.';
}