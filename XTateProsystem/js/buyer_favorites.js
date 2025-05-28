/**
 * Real Estate Listing System
 * Buyer Favorites JavaScript File
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Buyer favorites JS loaded');

    // Handle both remove favorite forms and favorite buttons
    const removeForms = document.querySelectorAll('.remove-favorite-form');
    const favoriteButtons = document.querySelectorAll('.favorite-btn');

    // Function to show confirmation modal
    function showConfirmationModal(title, message, confirmText, confirmCallback) {
        // Remove existing modal if any
        const existingModal = document.getElementById('confirmationModal');
        if (existingModal) {
            existingModal.remove();
        }

        // Create modal HTML
        const modalHtml = `
            <div class="modal fade" id="confirmationModal" tabindex="-1" role="dialog" aria-labelledby="confirmationModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="confirmationModalLabel">${title}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            ${message}
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-danger" id="confirmAction">${confirmText}</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Add modal to body
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        
        // Initialize and show the modal
        const modal = new bootstrap.Modal(document.getElementById('confirmationModal'));
        modal.show();

        // Handle confirm button click
        document.getElementById('confirmAction').addEventListener('click', function() {
            modal.hide();
            confirmCallback();
        });
    }

    // Handle remove favorite forms (for favorites page)
    if (removeForms.length > 0) {
        console.log('Found', removeForms.length, 'remove forms');

        removeForms.forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                const propertyId = this.querySelector('input[name="property_id"]').value;
                const propertyCard = this.closest('.col-md-6');

                showConfirmationModal(
                    'Remove from Favorites',
                    'Are you sure you want to remove this property from your favorites?',
                    'Remove',
                    () => {
                        const formData = new FormData();
                        formData.append('property_id', propertyId);
                        formData.append('action', 'remove');

                        // Send request to server
                        fetch('../api/favorite.php', {
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
                                // Show success notification
                                const notification = document.createElement('div');
                                notification.className = 'alert alert-success position-fixed top-0 start-50 translate-middle-x mt-3';
                                notification.style.zIndex = '9999';
                                notification.innerHTML = `
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-check-circle me-2"></i>
                                        <span>Property has been removed from favorites!</span>
                                        <button type="button" class="btn-close ms-3" onclick="this.parentElement.parentElement.remove()"></button>
                                    </div>
                                `;
                                document.body.appendChild(notification);
                                
                                // Remove the property card with animation
                                propertyCard.style.transition = 'opacity 0.5s ease';
                                propertyCard.style.opacity = '0';
                                
                                setTimeout(() => {
                                    propertyCard.remove();
                                    
                                    // Check if there are no more properties
                                    const remainingCards = document.querySelectorAll('.property-card');
                                    if (remainingCards.length === 0) {
                                        const cardBody = document.querySelector('.card-body');
                                        cardBody.innerHTML = `
                                            <div class="alert alert-info">
                                                <i class="fas fa-info-circle me-2"></i> You haven't added any properties to your favorites yet. Browse the <a href="../search.php" class="alert-link">property listings</a> to find properties you like.
                                            </div>
                                        `;
                                    }
                                }, 500);

                                // Remove notification after 3 seconds
                                setTimeout(() => notification.remove(), 3000);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('An error occurred while removing the property. Please try again.');
                        });
                    }
                );
            });
        });
    }

    // Handle favorite buttons (for property details page)
    if (favoriteButtons.length > 0) {
        console.log('Found', favoriteButtons.length, 'favorite buttons');

        favoriteButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();

                const propertyId = this.getAttribute('data-property-id');
                const isFavorited = this.classList.contains('favorited');

                const title = isFavorited ? 'Remove from Favorites' : 'Add to Favorites';
                const message = isFavorited ? 
                    'Are you sure you want to remove this property from your favorites?' :
                    'Would you like to add this property to your favorites?';
                const confirmText = isFavorited ? 'Remove' : 'Add';

                showConfirmationModal(
                    title,
                    message,
                    confirmText,
                    () => {
                        const formData = new FormData();
                        formData.append('property_id', propertyId);
                        formData.append('action', 'toggle');

                        // Send request to server
                        fetch('../api/favorite.php', {
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
                                if (data.action === 'added') {
                                    // Update UI for favorited state
                                    this.classList.remove('btn-outline-danger');
                                    this.classList.add('btn-danger', 'favorited');
                                    this.innerHTML = '<i class="fas fa-heart"></i> Remove from Favorites';
                                    
                                    // Show success notification
                                    const notification = document.createElement('div');
                                    notification.className = 'alert alert-success position-fixed top-0 start-50 translate-middle-x mt-3';
                                    notification.style.zIndex = '9999';
                                    notification.innerHTML = `
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-check-circle me-2"></i>
                                            <span>Property added to favorites!</span>
                                            <button type="button" class="btn-close ms-3" onclick="this.parentElement.parentElement.remove()"></button>
                                        </div>
                                    `;
                                    document.body.appendChild(notification);
                                    setTimeout(() => notification.remove(), 3000);
                                } else if (data.action === 'removed') {
                                    // Update UI for unfavorited state
                                    this.classList.remove('btn-danger', 'favorited');
                                    this.classList.add('btn-outline-danger');
                                    this.innerHTML = '<i class="far fa-heart"></i> Add to Favorites';
                                    
                                    // Show success notification
                                    const notification = document.createElement('div');
                                    notification.className = 'alert alert-info position-fixed top-0 start-50 translate-middle-x mt-3';
                                    notification.style.zIndex = '9999';
                                    notification.innerHTML = `
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-check-circle me-2"></i>
                                            <span>Property removed from favorites!</span>
                                            <button type="button" class="btn-close ms-3" onclick="this.parentElement.parentElement.remove()"></button>
                                        </div>
                                    `;
                                    document.body.appendChild(notification);
                                    setTimeout(() => notification.remove(), 3000);
                                }
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('An error occurred. Please try again.');
                        });
                    }
                );
            });
        });
    }
});