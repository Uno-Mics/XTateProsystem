
// Success notification modal HTML
const notificationModalHtml = `
<div class="modal fade" id="successModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center p-4">
                <i class="fas fa-check-circle text-success mb-3" style="font-size: 3rem;"></i>
                <h4 class="mb-3">Success!</h4>
                <p class="mb-0">Profile updated successfully.</p>
            </div>
            <div class="modal-footer border-0 justify-content-center">
                <button type="button" class="btn btn-primary px-4" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>`;

// Add modal to document
document.body.insertAdjacentHTML('beforeend', notificationModalHtml);

// Initialize success modal
const successModal = new bootstrap.Modal(document.getElementById('successModal'));

// Handle profile form submission
document.getElementById('profileForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('../api/users.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Show success modal
            successModal.show();
            
            // Don't refresh immediately, wait for modal acknowledgment
            document.getElementById('successModal').addEventListener('hidden.bs.modal', function() {
                location.reload();
            }, { once: true }); // Ensure the event only fires once
        } else {
            throw new Error(data.message || 'Failed to update profile');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        // Show error alert
        const alertHtml = `
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i> ${error.message || 'An error occurred while updating profile'}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>`;
        this.insertAdjacentHTML('beforebegin', alertHtml);
    });
});
