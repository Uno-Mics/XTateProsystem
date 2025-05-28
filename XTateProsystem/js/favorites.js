/**
 * Real Estate Listing System
 * Favorites JavaScript File
 */

document.addEventListener('DOMContentLoaded', function() {
    const favoriteButtons = document.querySelectorAll('.favorite-btn');

    if (favoriteButtons.length > 0) {
        favoriteButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();

                const propertyId = this.getAttribute('data-property-id');
                if (!propertyId) {
                    console.error('No property ID found on favorite button');
                    return;
                }

                const formData = new FormData();
                formData.append('property_id', propertyId);
                formData.append('action', 'toggle');

                fetch('api/favorites_fix.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (data.action === 'added') {
                            this.classList.remove('btn-outline-danger');
                            this.classList.add('btn-danger', 'favorited');
                            this.innerHTML = '<i class="fas fa-heart"></i> Remove from Favorites';

                            const notification = document.createElement('div');
                            notification.className = 'alert alert-success position-fixed top-0 start-50 translate-middle-x mt-3';
                            notification.style.zIndex = '9999';
                            notification.innerHTML = `
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-check-circle me-2"></i>
                                    <span>Property added to favorites</span>
                                    <button type="button" class="btn-close ms-3" onclick="this.parentElement.parentElement.remove()"></button>
                                </div>
                            `;
                            document.body.appendChild(notification);
                            setTimeout(() => notification.remove(), 3000);
                        } else {
                            this.classList.remove('btn-danger', 'favorited');
                            this.classList.add('btn-outline-danger');
                            this.innerHTML = '<i class="far fa-heart"></i> Add to Favorites';

                            const notification = document.createElement('div');
                            notification.className = 'alert alert-info position-fixed top-0 start-50 translate-middle-x mt-3';
                            notification.style.zIndex = '9999';
                            notification.innerHTML = `
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-check-circle me-2"></i>
                                    <span>Property removed from favorites</span>
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
                    alert('An error occurred. Please try again later.');
                });
            });
        });
    }

    // Check favorite status on page load
    const propertyId = document.querySelector('.favorite-btn')?.getAttribute('data-property-id');
    if (propertyId) {
        fetch(`api/favorites_fix.php?property_id=${propertyId}&action=check`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.favorited) {
                    const btn = document.querySelector('.favorite-btn');
                    btn.classList.remove('btn-outline-danger');
                    btn.classList.add('btn-danger', 'favorited');
                    btn.innerHTML = '<i class="fas fa-heart"></i> Remove from Favorites';
                }
            });
    }
});