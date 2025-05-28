/**
 * Real Estate Listing System
 * Messaging JavaScript File
 */

document.addEventListener('DOMContentLoaded', function() {
    const messageContainer = document.getElementById('messageContainer');
    const messageForm = document.getElementById('messageForm');
    let lastMessageId = 0;

    // Format message time
    function formatTime(timestamp) {
        const msgDate = new Date(timestamp);
        return msgDate.toLocaleTimeString('en-US', { 
            hour: '2-digit', 
            minute: '2-digit',
            hour12: true 
        });
    }

    // Scroll to bottom of messages
    function scrollToBottom() {
        if (messageContainer) {
            messageContainer.scrollTop = messageContainer.scrollHeight;
        }
    }

    // Add new message to container
    function addMessage(messageData, shouldScroll = true) {
        if (!messageData || !messageContainer) return;

        // Check for duplicate message
        const existingMessage = document.querySelector(`[data-message-id="${messageData.id}"]`);
        if (existingMessage) return;

        const messageDiv = document.createElement('div');
        messageDiv.className = `message-item ${messageData.is_sent_by_me ? 'message-sent' : 'message-received'}`;
        messageDiv.dataset.messageId = messageData.id || 'temp-' + Date.now();

        const sanitizedMessage = (messageData.message || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;')
            .replace(/\n/g, '<br>');

        messageDiv.innerHTML = `
            <div class="message-content">
                <p>${sanitizedMessage}</p>
                <small class="message-time">${formatTime(messageData.created_at || messageData.timestamp || new Date())}</small>
            </div>
        `;

        messageContainer.appendChild(messageDiv);

        if (messageData.id && messageData.id > lastMessageId) {
            lastMessageId = messageData.id;
        }

        if (shouldScroll) {
            scrollToBottom();
        }
    }

    // Load messages with error handling
    function loadMessages() {
        const urlParams = new URLSearchParams(window.location.search);
        const partnerId = urlParams.get('user');

        if (!partnerId || !messageContainer) return;

        fetch(`../api/messages.php?action=get_messages&partner_id=${partnerId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success && Array.isArray(data.data.messages)) {
                    if (data.data.messages.length > 0) {
                        // Only clear if we have new messages to display
                        messageContainer.innerHTML = '';
                        data.data.messages.forEach(msg => addMessage(msg, false));
                        scrollToBottom();
                    }
                }
            })
            .catch(error => console.error('Error loading messages:', error));
    }

    // Initial load
    if (messageContainer) {
        loadMessages();
        // Poll for new messages every 5 seconds
        setInterval(loadMessages, 5000);
    }

    // Handle form submission
    if (messageForm) {
        messageForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const textarea = this.querySelector('textarea[name="message"]');
            const message = textarea.value.trim();
            if (!message) return;

            const urlParams = new URLSearchParams(window.location.search);
            const receiverId = urlParams.get('user');
            if (!receiverId) return;

            const formData = new FormData(this);
            textarea.value = '';
            textarea.style.height = 'auto';

            // Add temporary message
            const tempMessageData = {
                id: 'temp-' + Date.now(),
                message: message,
                is_sent_by_me: true,
                timestamp: new Date()
            };
            addMessage(tempMessageData);

            fetch('../api/messages.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Load messages immediately after successful send
                    loadMessages();
                } else {
                    console.error('Failed to send message:', data.message);
                    const tempMsg = document.querySelector(`[data-message-id="${tempMessageData.id}"]`);
                    if (tempMsg) tempMsg.remove();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                const tempMsg = document.querySelector(`[data-message-id="${tempMessageData.id}"]`);
                if (tempMsg) tempMsg.remove();
            });
        });

        // Auto-expand textarea
        const textarea = messageForm.querySelector('textarea');
        if (textarea) {
            textarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });
        }
    }
});

// Mark messages as read
function markMessagesAsRead(partnerId) {
    if (!partnerId) return;

    const formData = new FormData();
    formData.append('action', 'mark_read');
    formData.append('sender_id', partnerId);

    fetch('../api/messages.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateUnreadMessageCount();
        }
    })
    .catch(error => console.error('Error marking messages as read:', error));
}

// Update unread message count
function updateUnreadMessageCount() {
    fetch('../api/messages.php?action=unread_count')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const count = data.data.unread_count;
                const badges = document.querySelectorAll('.unread-message-badge');
                badges.forEach(badge => {
                    badge.style.display = count > 0 ? 'inline-block' : 'none';
                    if (count > 0) badge.textContent = count;
                });
            }
        })
        .catch(error => console.error('Error updating unread count:', error));
}

// Initialize WebSocket connection (modified to integrate with new addMessage)
function initializeWebSocket() {
    // Check if WebSocket manager exists (defined in websocket.js)
    if (typeof wsManager === 'undefined') {
        console.warn('WebSocket manager not found, real-time messaging unavailable');
        return;
    }
    
    // Connect to WebSocket server
    wsManager.connect();
    
    // Register message handler
    wsManager.registerHandler('message', function(data) {
        // Handle incoming messages
        if (data.type === 'message') {
            // Get current conversation partner
            const urlParams = new URLSearchParams(window.location.search);
            const currentPartnerId = urlParams.get('user');
            
            // If this message is from/to the current conversation partner, display it
            if ((data.sender_id == currentPartnerId) || (data.receiver_id == currentPartnerId)) {
                addMessage({
                    id: data.id,
                    message: data.message,
                    is_sent_by_me: data.sender_id !== currentPartnerId,
                    timestamp: data.timestamp
                });
                
                // Mark message as read if it's from current partner
                if (data.sender_id == currentPartnerId) {
                    markMessagesAsRead(currentPartnerId);
                }
            }
            
            // Update unread message count
            updateUnreadMessageCount();
        }
    });
}