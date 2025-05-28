<?php
require_once '../inc/db.php';
require_once '../inc/functions.php';
require_once '../inc/auth.php';

/**
 * WebSocket server for real-time messaging
 * 
 * This file should be run as a standalone PHP script
 * using CLI: php websocket_server.php
 */

// Check if running in CLI
if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line.");
}

// Make sure WebSocket extension is loaded
if (!extension_loaded('sockets')) {
    die("Socket extension not loaded. Please install the PHP sockets extension.");
}

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define WebSocket server constants
define('HOST', '0.0.0.0');
define('PORT', 8080);
define('MAX_CLIENTS', 100);

/**
 * WebSocket server class
 */
class WebSocketServer {
    private $master;
    private $sockets = [];
    private $clients = [];
    private $handshaked = [];

    /**
     * Constructor
     */
    public function __construct() {
        // Create master socket
        $this->master = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        
        // Set options
        socket_set_option($this->master, SOL_SOCKET, SO_REUSEADDR, 1);
        socket_bind($this->master, HOST, PORT);
        socket_listen($this->master, MAX_CLIENTS);
        
        // Add master socket to sockets array
        $this->sockets[] = $this->master;
        
        echo "WebSocket server started on " . HOST . ":" . PORT . "\n";
    }

    /**
     * Run the server
     */
    public function run() {
        while (true) {
            // Make a copy of the sockets array
            $read = $this->sockets;
            
            // Check for socket activity (read, write, error)
            $write = $except = null;
            socket_select($read, $write, $except, null);
            
            // Loop through the readable sockets
            foreach ($read as $socket) {
                // If it's the master socket, handle new connections
                if ($socket === $this->master) {
                    $client = socket_accept($this->master);
                    
                    if ($client < 0) {
                        echo "Socket accept failed: " . socket_strerror(socket_last_error($this->master)) . "\n";
                        continue;
                    }
                    
                    // Add the new client to the sockets array
                    $this->sockets[] = $client;
                    $this->clients[intval($client)] = $client;
                    
                    echo "New client connected: " . intval($client) . "\n";
                } else {
                    // Read data from the client socket
                    $bytes = @socket_recv($socket, $buffer, 4096, 0);
                    
                    // If the client is disconnected
                    if ($bytes === false || $bytes === 0) {
                        $this->disconnectClient($socket);
                        continue;
                    }
                    
                    // If the client hasn't been handshaked yet
                    if (!isset($this->handshaked[intval($socket)])) {
                        $this->handshake($socket, $buffer);
                    } else {
                        // Decode the WebSocket frame
                        $message = $this->decode($buffer);
                        $this->processMessage($socket, $message);
                    }
                }
            }
        }
    }

    /**
     * Handshake with the client
     */
    private function handshake($client, $headers) {
        // Extract the WebSocket key from the headers
        if (preg_match('/Sec-WebSocket-Key:\s+(.*)\r\n/', $headers, $matches)) {
            $key = $matches[1];
            
            // Create the WebSocket accept key
            $acceptKey = base64_encode(sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
            
            // Send the handshake response
            $response = "HTTP/1.1 101 Switching Protocols\r\n" .
                        "Upgrade: websocket\r\n" .
                        "Connection: Upgrade\r\n" .
                        "Sec-WebSocket-Accept: $acceptKey\r\n\r\n";
            
            socket_write($client, $response, strlen($response));
            
            // Mark the client as handshaked
            $this->handshaked[intval($client)] = true;
            
            echo "Client " . intval($client) . " handshaked\n";
            
            // Send welcome message
            $welcomeMsg = json_encode([
                'type' => 'system',
                'message' => 'Welcome to the Real Estate Messaging System!'
            ]);
            
            $this->send($client, $welcomeMsg);
        }
    }

    /**
     * Process a message from a client
     */
    private function processMessage($client, $message) {
        // Try to decode the message as JSON
        $data = @json_decode($message, true);
        
        if (!$data || !isset($data['receiver_id']) || !isset($data['message'])) {
            echo "Invalid message format\n";
            return;
        }
        
        echo "Message from client " . intval($client) . " to user " . $data['receiver_id'] . ": " . $data['message'] . "\n";
        
        // Extract session from the client (this would be implemented with authentication)
        $userId = $this->getClientUserId($client);
        
        if (!$userId) {
            echo "Client not authenticated\n";
            
            // Send error message
            $errorMsg = json_encode([
                'type' => 'error',
                'message' => 'You are not authenticated'
            ]);
            
            $this->send($client, $errorMsg);
            return;
        }
        
        // Save message to database
        $receiverId = intval($data['receiver_id']);
        $messageText = $data['message'];
        
        // Insert message into database
        $conn = connectDB();
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message, is_read, created_at) VALUES (?, ?, ?, 0, NOW())");
        $stmt->bind_param("iis", $userId, $receiverId, $messageText);
        $stmt->execute();
        $messageId = $stmt->insert_id;
        $stmt->close();
        
        // Get the created timestamp
        $stmt = $conn->prepare("SELECT created_at FROM messages WHERE id = ?");
        $stmt->bind_param("i", $messageId);
        $stmt->execute();
        $result = $stmt->get_result();
        $message = $result->fetch_assoc();
        $timestamp = $message['created_at'];
        $stmt->close();
        closeDB($conn);
        
        // Send confirmation to sender
        $confirmationMsg = json_encode([
            'type' => 'sent_confirmation',
            'message_id' => $messageId,
            'message' => $messageText,
            'receiver_id' => $receiverId,
            'created_at' => $timestamp
        ]);
        
        $this->send($client, $confirmationMsg);
        
        // Send message to receiver if online
        $receiverClient = $this->findClientByUserId($receiverId);
        
        if ($receiverClient) {
            $messageData = json_encode([
                'type' => 'message',
                'message_id' => $messageId,
                'sender_id' => $userId,
                'message' => $messageText,
                'created_at' => $timestamp
            ]);
            
            $this->send($receiverClient, $messageData);
        }
    }

    /**
     * Send a message to a client
     */
    private function send($client, $message) {
        $encodedMessage = $this->encode($message);
        socket_write($client, $encodedMessage, strlen($encodedMessage));
    }

    /**
     * Disconnect a client
     */
    private function disconnectClient($client) {
        // Remove the client from the arrays
        $index = array_search($client, $this->sockets);
        if ($index !== false) {
            unset($this->sockets[$index]);
        }
        
        $clientId = intval($client);
        unset($this->clients[$clientId]);
        unset($this->handshaked[$clientId]);
        
        // Close the socket
        socket_close($client);
        
        echo "Client disconnected: " . $clientId . "\n";
    }

    /**
     * Encode a message for WebSocket transmission
     */
    private function encode($message) {
        $length = strlen($message);
        $response = "";
        
        // First byte: fin + opcode
        $response .= chr(0x81); // FIN + text frame
        
        // Second byte: mask + payload length
        if ($length <= 125) {
            $response .= chr($length);
        } elseif ($length <= 65535) {
            $response .= chr(126) . chr(($length >> 8) & 0xFF) . chr($length & 0xFF);
        } else {
            $response .= chr(127) . chr(0) . chr(0) . chr(0) . chr(0)
                      . chr(($length >> 24) & 0xFF) . chr(($length >> 16) & 0xFF)
                      . chr(($length >> 8) & 0xFF) . chr($length & 0xFF);
        }
        
        // Append the message
        $response .= $message;
        
        return $response;
    }

    /**
     * Decode a WebSocket frame
     */
    private function decode($buffer) {
        $length = ord($buffer[1]) & 127;
        $maskStart = 2;
        
        if ($length === 126) {
            $maskStart = 4;
        } elseif ($length === 127) {
            $maskStart = 10;
        }
        
        $masks = substr($buffer, $maskStart, 4);
        $data = substr($buffer, $maskStart + 4);
        $message = "";
        
        for ($i = 0; $i < strlen($data); $i++) {
            $message .= $data[$i] ^ $masks[$i % 4];
        }
        
        return $message;
    }

    /**
     * Get the user ID associated with a client (implementation example)
     * In a real application, this would use sessions or tokens
     */
    private function getClientUserId($client) {
        // This is a simplified example
        // In a real application, you would use authentication
        // For now, we'll just return a test user ID
        return 1; // Example user ID
    }

    /**
     * Find a client by user ID
     */
    private function findClientByUserId($userId) {
        // In a real application, you would maintain a mapping of user IDs to clients
        // For now, this is just a placeholder
        return null;
    }
}

// Create and run the WebSocket server
$server = new WebSocketServer();
$server->run();
?>
