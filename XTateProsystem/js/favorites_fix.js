/**
 * Real Estate Listing System
 * Direct Favorites Implementation JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Favorites Fix JS loaded');
    
    // Check favorite status on page load for each button
    const favoriteButtons = document.querySelectorAll('.favorite-btn');
    
    if (favoriteButtons.length > 0) {
        console.log('Found', favoriteButtons.length, 'favorite buttons');
        
        favoriteButtons.forEach(button => {
            // Get property ID from data attribute
            const propertyId = button.getAttribute('data-property-id');
            if (!propertyId) {
                console.error('No property ID found on favorite button');
                return;
            }

            // Check initial favorite status
            const formData = new FormData();
            formData.append('property_id', propertyId);
            formData.append('action', 'check');

            fetch('api/favorites_fix.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.favorited) {
                    button.classList.remove('btn-outline-danger');
                    button.classList.add('btn-danger', 'favorited');
                    button.innerHTML = '<i class="fas fa-heart"></i> Remove from Favorites';
                }
            })
            .catch(error => console.error('Error checking favorite status:', error));

            // Handle click events
            button.addEventListener('click', function(e) {
                e.preventDefault();
                console.log('Favorite button clicked');
                
                // Show loading state
                const originalText = this.innerHTML;
                const originalClassName = this.className;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                this.disabled = true;
                
                // Create form data
                const formData = new FormData();
                formData.append('property_id', propertyId);
                formData.append('action', 'toggle');
                
                // Send request to server
                fetch('api/favorites_fix.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    console.log('Response status:', response.status);
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Favorite response:', data);
                    
                    // Reset button state
                    this.disabled = false;
                    
                    if (data.success) {
                        if (data.action === 'added') {
                            // Update UI for favorited state
                            this.classList.remove('btn-outline-danger');
                            this.classList.add('btn-danger', 'favorited');
                            this.innerHTML = '<i class="fas fa-heart"></i> Remove from Favorites';
                            
                        } else if (data.action === 'removed') {
                            // Update UI for unfavorited state
                            this.classList.remove('btn-danger', 'favorited');
                            this.classList.add('btn-outline-danger');
                            this.innerHTML = '<i class="far fa-heart"></i> Add to Favorites';
                        
                        }
                    } else {
                        // Error handling
                        this.innerHTML = originalText;
                        this.className = originalClassName;
                        console.error('Error toggling favorite:', data.message);
                        
                        if (data.redirect) {
                            if (confirm('You need to log in first. Go to login page?')) {
                                window.location.href = data.redirect;
                            }
                        } else {
                            alert(data.message || 'An error occurred while toggling favorite status');
                        }
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    this.disabled = false;
                    this.innerHTML = originalText;
                    this.className = originalClassName;
                    alert('An error occurred. Please try again later.');
                });
            });
        });
    } else {
        console.log('No favorite buttons found on page');
    }
});