
document.addEventListener('DOMContentLoaded', function() {
    const contactItems = document.querySelectorAll('.contact-item');
    const messageView = document.querySelector('.message-view');
    const messageContent = document.querySelector('.message-content');
    const messagePlaceholder = document.querySelector('.message-placeholder');
    const searchInput = document.getElementById('searchContacts');
    const replyModal = new bootstrap.Modal(document.getElementById('replyModal'));
    
    let currentContactId = null;

    // Search functionality
    searchInput.addEventListener('input', function(e) {
        const searchText = e.target.value.toLowerCase();
        contactItems.forEach(item => {
            const name = item.querySelector('.contact-name').textContent.toLowerCase();
            const subject = item.querySelector('.contact-subject').textContent.toLowerCase();
            if (name.includes(searchText) || subject.includes(searchText)) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    });

    // Contact item click handler
    contactItems.forEach(item => {
        item.addEventListener('click', function() {
            const contactId = this.dataset.contactId;
            currentContactId = contactId;
            
            // Remove active class from all items
            contactItems.forEach(i => i.classList.remove('active'));
            
            // Add active class to clicked item
            this.classList.add('active');
            
            // Remove unread status if present
            if (this.classList.contains('unread')) {
                this.classList.remove('unread');
                this.querySelector('.unread-badge')?.remove();
                updateMessageStatus(contactId, 'read');
            }
            
            // Fetch and display message details
            fetchMessageDetails(contactId);

            // Show message content and hide placeholder
            messagePlaceholder.style.display = 'none';
            messageContent.style.display = 'block';
        });
    });

    // Fetch message details
    function fetchMessageDetails(contactId) {
        fetch(`../api/messages.php?action=get_contact_details&contact_id=${contactId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayMessageDetails(data.data);
                } else {
                    console.error('Error fetching contact details:', data.message);
                }
            })
            .catch(error => console.error('Error:', error));
    }

    // Display message details
    function displayMessageDetails(contact) {
        messageContent.querySelector('.message-subject').textContent = contact.subject;
        messageContent.querySelector('.message-sender').textContent = contact.name;
        messageContent.querySelector('.message-date').textContent = new Date(contact.created_at).toLocaleString();
        messageContent.querySelector('.message-text').textContent = contact.message;
        messageContent.querySelector('.contact-email').textContent = contact.email;
        messageContent.querySelector('.contact-phone').textContent = contact.phone || 'Not provided';
        messageContent.querySelector('.contact-type').textContent = contact.user_type || 'General';
    }

    // Update message status
    function updateMessageStatus(contactId, status) {
        fetch('../api/messages.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'update_contact_status',
                contact_id: contactId,
                status: status
            })
        });
    }

    // Reply button handler
    document.querySelector('.reply-btn')?.addEventListener('click', function() {
        if (!currentContactId) return;
        
        // Fetch contact details to get user information
        fetch(`../api/messages.php?action=get_contact_details&contact_id=${currentContactId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const contactEmail = data.data.email;
                    // Redirect to messages page with the user's email
                    window.location.href = `messages.php?contact_email=${encodeURIComponent(contactEmail)}`;
                }
            })
            .catch(error => console.error('Error:', error));
    });

    // Delete button handler
    document.querySelector('.delete-btn')?.addEventListener('click', function() {
        if (!confirm('Are you sure you want to delete this message?')) return;

        fetch('../api/messages.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'delete_contact',
                contact_id: currentContactId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const contactItem = document.querySelector(`[data-contact-id="${currentContactId}"]`);
                if (contactItem) {
                    contactItem.remove();
                }
                messagePlaceholder.style.display = 'flex';
                messageContent.style.display = 'none';
                currentContactId = null;
            }
        })
        .catch(error => console.error('Error:', error));
    });
});
