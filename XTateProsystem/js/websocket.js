/**
 * Real Estate Listing System
 * WebSocket JavaScript File
 */

class WebSocketManager {
    constructor() {
        this.socket = null;
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 5;
        this.reconnectDelay = 3000; // 3 seconds
        this.isConnecting = false;
        this.messageQueue = [];
        this.messageHandlers = [];
    }
    
    /**
     * Initialize WebSocket connection
     */
    connect() {
        if (this.isConnecting || (this.socket && this.socket.readyState === WebSocket.OPEN)) {
            return;
        }
        
        this.isConnecting = true;
        
        // Determine WebSocket protocol and URL
        const protocol = window.location.protocol === "https:" ? "wss:" : "ws:";
        
        // Try the /ws endpoint first (for servers that support proper WebSocket routing)
        // Fall back to direct PHP file if first connection fails
        const wsUrl = `${protocol}//${window.location.host}/ws`;
        const fallbackUrl = `${protocol}//${window.location.host}/websocket_server.php`;
        
        this.tryConnection(wsUrl, fallbackUrl);
    }
    
    /**
     * Try to establish a WebSocket connection with fallback
     * @param {string} primaryUrl - Primary WebSocket URL to try first
     * @param {string} fallbackUrl - Fallback URL to try if primary fails
     */
    tryConnection(primaryUrl, fallbackUrl) {
        try {
            console.log('Attempting WebSocket connection to:', primaryUrl);
            this.socket = new WebSocket(primaryUrl);
            
            // Set up event handlers
            this.socket.onopen = this.handleOpen.bind(this);
            this.socket.onclose = this.handleClose.bind(this);
            this.socket.onmessage = this.handleMessageEvent.bind(this);
            
            // Custom error handler for primary connection attempt
            const originalErrorHandler = this.handleError.bind(this);
            
            // If first connection fails, try fallback
            this.socket.onerror = (error) => {
                console.warn('Failed to connect to primary WebSocket endpoint, trying fallback...');
                
                try {
                    this.socket = new WebSocket(fallbackUrl);
                    
                    // Set up regular handlers for fallback connection
                    this.socket.onopen = this.handleOpen.bind(this);
                    this.socket.onclose = this.handleClose.bind(this);
                    this.socket.onmessage = this.handleMessageEvent.bind(this);
                    this.socket.onerror = originalErrorHandler;
                    
                } catch (fallbackError) {
                    console.error('WebSocket connection failed completely:', fallbackError);
                    this.isConnecting = false;
                }
            };
        } catch (error) {
            console.error('Failed to create WebSocket connection:', error);
            this.isConnecting = false;
        }
    }
    
    /**
     * Handle WebSocket connection open event
     */
    handleOpen() {
        console.log('WebSocket connection established');
        this.isConnecting = false;
        this.reconnectAttempts = 0;
        
        // Send any queued messages
        this.flushMessageQueue();
    }
    
    /**
     * Handle WebSocket connection close event
     */
    handleClose(event) {
        this.isConnecting = false;
        
        if (!event.wasClean) {
            console.log(`WebSocket connection closed unexpectedly. Code: ${event.code}`);
            this.attemptReconnect();
        } else {
            console.log('WebSocket connection closed cleanly');
        }
    }
    
    /**
     * Handle WebSocket message event
     */
    handleMessageEvent(event) {
        try {
            const data = JSON.parse(event.data);
            this.handleMessage(data);
        } catch (error) {
            console.error('Error parsing WebSocket message:', error);
        }
    }
    
    /**
     * Handle WebSocket error
     */
    handleError(error) {
        console.error('WebSocket error:', error);
        this.isConnecting = false;
    }
    
    /**
     * Attempt to reconnect WebSocket
     */
    attemptReconnect() {
        if (this.reconnectAttempts < this.maxReconnectAttempts) {
            this.reconnectAttempts++;
            console.log(`Attempting to reconnect (${this.reconnectAttempts}/${this.maxReconnectAttempts})...`);
            
            setTimeout(() => {
                this.connect();
            }, this.reconnectDelay * this.reconnectAttempts);
        } else {
            console.log('Max reconnect attempts reached. Please refresh the page to continue.');
        }
    }
    
    /**
     * Send message through WebSocket
     * @param {Object} data - Message data to send
     * @returns {Boolean} - Whether message was sent immediately
     */
    sendMessage(data) {
        if (this.socket && this.socket.readyState === WebSocket.OPEN) {
            this.socket.send(JSON.stringify(data));
            return true;
        } else {
            // Queue message for later sending
            this.messageQueue.push(data);
            
            // Try to connect if not already connecting
            if (!this.isConnecting && (!this.socket || this.socket.readyState === WebSocket.CLOSED)) {
                this.connect();
            }
            
            return false;
        }
    }
    
    /**
     * Send queued messages
     */
    flushMessageQueue() {
        if (this.messageQueue.length === 0 || !this.socket || this.socket.readyState !== WebSocket.OPEN) {
            return;
        }
        
        while (this.messageQueue.length > 0) {
            const message = this.messageQueue.shift();
            this.socket.send(JSON.stringify(message));
        }
    }
    
    /**
     * Register message handler
     * @param {String} type - Message type to handle
     * @param {Function} handler - Callback function
     */
    registerHandler(type, handler) {
        this.messageHandlers.push({ type, handler });
    }
    
    /**
     * Process received message
     * @param {Object} data - Received message data
     */
    handleMessage(data) {
        // Call appropriate handlers
        this.messageHandlers.forEach(({ type, handler }) => {
            if (!data.type || data.type === type) {
                handler(data);
            }
        });
    }
    
    /**
     * Close WebSocket connection
     */
    close() {
        if (this.socket) {
            this.socket.close();
        }
    }
    
    /**
     * Check if WebSocket is connected
     * @returns {Boolean} - Connection status
     */
    isConnected() {
        return this.socket && this.socket.readyState === WebSocket.OPEN;
    }
}

// Create global WebSocket manager instance
const wsManager = new WebSocketManager();

// Connect when page loads
document.addEventListener('DOMContentLoaded', () => {
    // Only connect if on a page that needs real-time messaging
    const messagingElements = document.querySelectorAll('.message-container, .conversation-list');
    if (messagingElements.length > 0) {
        wsManager.connect();
    }
});

// Register default message handlers
wsManager.registerHandler('message', (data) => {
    // This will be handled by more specific code in messaging.js
    console.log('Message received:', data);
});

wsManager.registerHandler('notification', (data) => {
    // Show notification
    showNotification(data.title, data.message);
});

// Helper function to show browser notifications
function showNotification(title, message) {
    // Check if browser supports notifications
    if (!("Notification" in window)) {
        console.log("This browser does not support desktop notifications");
        return;
    }
    
    // Check notification permission
    if (Notification.permission === "granted") {
        // Create notification
        const notification = new Notification(title, {
            body: message,
            icon: '/favicon.ico'
        });
        
        // Close notification after 5 seconds
        setTimeout(() => {
            notification.close();
        }, 5000);
    } else if (Notification.permission !== "denied") {
        // Request permission
        Notification.requestPermission().then(permission => {
            if (permission === "granted") {
                showNotification(title, message);
            }
        });
    }
}
