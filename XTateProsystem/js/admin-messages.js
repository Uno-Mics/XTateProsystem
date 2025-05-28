document.addEventListener('DOMContentLoaded', function() {
    const messageContainer = document.getElementById('messageContainer');
    const messageForm = document.getElementById('messageForm');
    const textarea = document.querySelector('textarea[name="message"]');
    const sendButton = document.getElementById('sendMessageBtn');

    // Scroll to bottom of messages
    function scrollToBottom() {
        if (messageContainer) {
            messageContainer.scrollTop = messageContainer.scrollHeight;
        }
    }

    // Format message time
    function formatTime(timestamp) {
        const now = new Date();
        const msgDate = new Date(timestamp);
        return msgDate.toLocaleTimeString('en-US', { 
            hour: '2-digit', 
            minute: '2-digit'
        });
    }

    // Add new message to container
    function addMessage(message, isSent) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `message-item ${isSent ? 'message-sent' : 'message-received'}`;

        const sanitizedMessage = message
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;')
            .replace(/\n/g, '<br>');

        messageDiv.innerHTML = `
            <div class="message-content">
                <p>${sanitizedMessage}</p>
                <small class="message-time">${formatTime(new Date())}</small>
            </div>
        `;

        messageContainer.appendChild(messageDiv);
        scrollToBottom();
    }

    // Initial scroll
    scrollToBottom();

    // Handle form submission
    if (messageForm) {
        messageForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const message = textarea.value.trim();

            if (!message) return;

            // Add message to UI immediately
            addMessage(message, true);

            // Clear textarea
            textarea.value = '';

            try {
                const response = await fetch('../api/messages.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (!data.success) {
                    console.error('Failed to send message:', data.message);
                    // Remove the message if it failed to send
                    messageContainer.lastChild.remove();
                }
            } catch (error) {
                console.error('Error sending message:', error);
                // Remove the message if it failed to send
                messageContainer.lastChild.remove();
            }
        });
    }

    // Poll for new messages
    function pollMessages() {
        const urlParams = new URLSearchParams(window.location.search);
        const contactEmail = urlParams.get('contact_email');
        const user = urlParams.get('user');

        if (!contactEmail && !user) return;

        fetch(`../api/messages.php?action=get_messages&partner_id=${user || ''}&contact_email=${contactEmail || ''}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.messages) {
                    messageContainer.innerHTML = '';
                    data.data.messages.forEach(msg => {
                        addMessage(msg.message, msg.is_sent_by_me);
                    });
                }
            })
            .catch(error => console.error('Error polling messages:', error));
    }

    // Start polling
    if (messageContainer) {
        pollMessages();
        setInterval(pollMessages, 3000);
    }
});