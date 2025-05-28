/**
 * Real Estate Listing System
 * Main JavaScript File
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize Bootstrap popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Search form validation
    const searchForm = document.querySelector('.search-form');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            const location = document.getElementById('location');
            const propertyType = document.getElementById('property_type');

            if (location && propertyType) {
                if (location.value.trim() === '' && propertyType.value === '') {
                    e.preventDefault();
                    alert('Please select at least one search criteria');
                }
            }
        });
    }

    // Property carousel/slider if exists
    const propertySlider = document.querySelector('.property-slider');
    if (propertySlider) {
        // Check if there are multiple images
        const propertyImages = document.querySelectorAll('.carousel-item');
        if (propertyImages.length > 1) {
            // Initialize the carousel
            var propertyCarousel = new bootstrap.Carousel(document.querySelector('#propertyCarousel'), {
                interval: 5000,
                wrap: true
            });

            // Add keyboard navigation
            document.addEventListener('keydown', function(e) {
                if (e.key === 'ArrowLeft') {
                    propertyCarousel.prev();
                } else if (e.key === 'ArrowRight') {
                    propertyCarousel.next();
                }
            });
        }
    }

    // Contact form validation
    const contactForm = document.getElementById('contactForm');
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            let isValid = true;

            // Name validation
            const nameInput = document.getElementById('name');
            if (nameInput && nameInput.value.trim() === '') {
                isValid = false;
                showValidationError(nameInput, 'Please enter your name');
            } else if (nameInput) {
                removeValidationError(nameInput);
            }

            // Email validation
            const emailInput = document.getElementById('email');
            if (emailInput) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (emailInput.value.trim() === '') {
                    isValid = false;
                    showValidationError(emailInput, 'Please enter your email');
                } else if (!emailRegex.test(emailInput.value.trim())) {
                    isValid = false;
                    showValidationError(emailInput, 'Please enter a valid email');
                } else {
                    removeValidationError(emailInput);
                }
            }

            // Message validation
            const messageInput = document.getElementById('message');
            if (messageInput && messageInput.value.trim() === '') {
                isValid = false;
                showValidationError(messageInput, 'Please enter your message');
            } else if (messageInput) {
                removeValidationError(messageInput);
            }

            if (!isValid) {
                e.preventDefault();
            }
        });
    }

    // Inquiry form validation
    const inquiryForm = document.getElementById('inquiryForm');
    if (inquiryForm) {
        inquiryForm.addEventListener('submit', function(e) {
            let isValid = true;

            // Message validation
            const messageInput = document.getElementById('inquiry_message');
            if (messageInput && messageInput.value.trim() === '') {
                isValid = false;
                showValidationError(messageInput, 'Please enter your inquiry message');
            } else if (messageInput) {
                removeValidationError(messageInput);
            }

            if (!isValid) {
                e.preventDefault();
            }
        });
    }

    // Property filter functionality
    const filterForm = document.getElementById('filterForm');
    if (filterForm) {
        const priceMinInput = document.getElementById('price_min');
        const priceMaxInput = document.getElementById('price_max');

        filterForm.addEventListener('submit', function(e) {
            // Validate price range
            if (priceMinInput && priceMaxInput) {
                const minPrice = parseFloat(priceMinInput.value) || 0;
                const maxPrice = parseFloat(priceMaxInput.value) || 0;

                if (maxPrice > 0 && minPrice > maxPrice) {
                    e.preventDefault();
                    alert('Minimum price cannot be greater than maximum price');
                }
            }
        });
    }

    // Add to favorites functionality
    const favoriteButtons = document.querySelectorAll('.favorite-btn');
    favoriteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();

            // Debug information
            console.log('Favorite button clicked');

            const propertyId = this.getAttribute('data-property-id');
            const isFavorite = this.classList.contains('favorited');

            console.log(`Property ID: ${propertyId}, Is Favorited: ${isFavorite}`);

            // Send AJAX request to add/remove favorite - using relative path
            fetch('api/properties.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=${isFavorite ? 'remove_favorite' : 'add_favorite'}&property_id=${propertyId}`
            })
            .then(response => {
                // Log response for debugging
                console.log('Favorites API Response:', response);
                return response.json();
            })
            .then(data => {
                // Log data for debugging
                console.log('Favorites API Data:', data);

                if (data.success) {
                    // Update UI for the favorites button
                    if (isFavorite) {
                        // Remove from favorites
                        this.classList.remove('favorited');
                        this.classList.remove('btn-danger');
                        this.classList.add('btn-outline-danger');

                        // Update icon
                        const icon = this.querySelector('i');
                        if (icon) {
                            icon.classList.remove('fas');
                            icon.classList.add('far');
                        }

                        // Update text
                        this.innerHTML = '<i class="far fa-heart"></i> Add to Favorites';
                        this.setAttribute('title', 'Add to Favorites');

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
                    } else {
                        // Add to favorites
                        this.classList.add('favorited');
                        this.classList.remove('btn-outline-danger');
                        this.classList.add('btn-danger');

                        // Update icon
                        const icon = this.querySelector('i');
                        if (icon) {
                            icon.classList.remove('far');
                            icon.classList.add('fas');
                        }

                        // Update text
                        this.innerHTML = '<i class="fas fa-heart"></i> Remove from Favorites';
                        this.setAttribute('title', 'Remove from Favorites');

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
                    }

                    // Update tooltip if bootstrap is available
                    if (typeof bootstrap !== 'undefined') {
                        const tooltip = bootstrap.Tooltip.getInstance(this);
                        if (tooltip) {
                            tooltip.dispose();
                            new bootstrap.Tooltip(this);
                        }
                    }
                } else {
                    // If not logged in, redirect to login page
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    } else {
                        const notification = document.createElement('div');
                        notification.className = 'alert alert-danger position-fixed top-0 start-50 translate-middle-x mt-3';
                        notification.style.zIndex = '9999';
                        notification.innerHTML = `
                            <div class="d-flex align-items-center">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <span>${data.message || 'An error occurred. Please try again.'}</span>
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
                alert('An error occurred while updating favorites. Please try again.');
            });
        });
    });

    // User Status Toggle
    const statusToggleButtons = document.querySelectorAll('.status-toggle-btn');
    if (statusToggleButtons.length > 0) {
        statusToggleButtons.forEach(button => {
            button.addEventListener('click', function() {
                const userId = this.getAttribute('data-user-id');
                const currentStatus = this.getAttribute('data-current-status');
                const newStatus = currentStatus === 'active' ? 'inactive' : 'active';

                // Create and show modal
                const modalHtml = `
                    <div class="modal fade" id="statusConfirmModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header ${newStatus === 'active' ? 'bg-success' : 'bg-danger'} text-white">
                                    <h5 class="modal-title">${newStatus === 'active' ? 'Activate' : 'Deactivate'} User Account</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <p>Are you sure you want to ${newStatus === 'active' ? 'activate' : 'deactivate'} this user's account?</p>
                                    <p class="text-muted"><small>This will ${newStatus === 'active' ? 'allow' : 'prevent'} the user from accessing their account.</small></p>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="button" class="btn ${newStatus === 'active' ? 'btn-success' : 'btn-danger'} confirm-status-change">
                                        ${newStatus === 'active' ? 'Activate' : 'Deactivate'} Account
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                // Remove existing modal if any
                const existingModal = document.getElementById('statusConfirmModal');
                if (existingModal) {
                    existingModal.remove();
                }

                // Add new modal to body
                document.body.insertAdjacentHTML('beforeend', modalHtml);
                
                // Initialize and show modal
                const modal = new bootstrap.Modal(document.getElementById('statusConfirmModal'));
                modal.show();

                // Handle confirm button click
                document.querySelector('.confirm-status-change').addEventListener('click', function() {
                    modal.hide();
                    fetch('../api/users.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=update_status&user_id=${userId}&status=${newStatus}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Remove modal backdrop
                            const modalBackdrop = document.querySelector('.modal-backdrop');
                            if (modalBackdrop) {
                                modalBackdrop.remove();
                            }
                            
                            // Update button state
                            button.setAttribute('data-current-status', newStatus);
                            button.textContent = newStatus === 'active' ? 'Deactivate Account' : 'Activate Account';

                            // Find and update the status badge in the same row
                            const row = button.closest('tr');
                            const statusBadge = row.querySelector('.badge');

                            // Update badge class and text
                            statusBadge.className = `badge ${newStatus === 'active' ? 'bg-success' : 'bg-danger'}`;
                            statusBadge.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);

                            // Show notification
                            const notification = document.createElement('div');
                            notification.className = `alert alert-${newStatus === 'active' ? 'success' : 'danger'} position-fixed top-0 start-50 translate-middle-x mt-3`;
                            notification.style.zIndex = '9999';
                            notification.innerHTML = `
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-${newStatus === 'active' ? 'check' : 'ban'}-circle me-2"></i>
                                    <span>User account successfully ${newStatus === 'active' ? 'activated' : 'deactivated'}</span>
                                    <button type="button" class="btn-close ms-3" onclick="this.parentElement.parentElement.remove()"></button>
                                </div>
                            `;
                            document.body.appendChild(notification);
                            setTimeout(() => notification.remove(), 3000);
                        } else {
                            alert(data.message || 'Failed to update user status');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while updating user status');
                    });
                });
            });
        });
    }

    // Delete confirmation
    const deleteButtons = document.querySelectorAll('.delete-btn');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    });

    // Function to display validation error
    function showValidationError(inputElement, errorMessage) {
        inputElement.classList.add('is-invalid');

        // Remove existing error message if any
        const existingErrorMsg = inputElement.nextElementSibling;
        if (existingErrorMsg && existingErrorMsg.classList.contains('invalid-feedback')) {
            existingErrorMsg.remove();
        }

        const errorElement = document.createElement('div');
        errorElement.className = 'invalid-feedback';
        errorElement.textContent = errorMessage;

        inputElement.parentNode.insertBefore(errorElement, inputElement.nextSibling);
    }

    // Function to remove validation error
    function removeValidationError(inputElement) {
        inputElement.classList.remove('is-invalid');

        const existingErrorMsg = inputElement.nextElementSibling;
        if (existingErrorMsg && existingErrorMsg.classList.contains('invalid-feedback')) {
            existingErrorMsg.remove();
        }
    }

    // Function to show UI notification
    function showNotification(title, message, type) {
        const notificationDiv = document.createElement('div');
        notificationDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-4`;
        notificationDiv.style.zIndex = '1050';
        notificationDiv.innerHTML = `
            <i class="fas fa-check-circle me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        document.body.appendChild(notificationDiv);
        setTimeout(() => notificationDiv.remove(), 3000);
    }


// Custom dropdown positioning for inquiry actions
document.addEventListener('show.bs.dropdown', function(e) {
    if (e.target.closest('.table-responsive')) {
        const button = e.target.querySelector('.action-dropdown-btn');
        const menu = e.target.querySelector('.dropdown-menu');
        const buttonRect = button.getBoundingClientRect();

        menu.style.top = buttonRect.bottom + 'px';
        menu.style.left = buttonRect.left + 'px';
    }
});



    // Charts for admin dashboard
    const propertiesChartCanvas = document.getElementById('propertiesChart');
    if (propertiesChartCanvas) {
        fetch('api/properties.php?action=get_stats')
            .then(response => response.json())
            .then(data => {
                const ctx = propertiesChartCanvas.getContext('2d');
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            label: 'Properties by Type',
                            data: data.values,
                            backgroundColor: [
                                '#1e88e5',
                                '#42a5f5',
                                '#64b5f6',
                                '#90caf9',
                                '#bbdefb'
                            ],
                            borderColor: [
                                '#0d47a1',
                                '#0d47a1',
                                '#0d47a1',
                                '#0d47a1',
                                '#0d47a1'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'top',
                            },
                            title: {
                                display: true,
                                text: 'Properties by Type'
                            }
                        }
                    }
                });
            })
            .catch(error => {
                console.error('Error loading chart data:', error);
            });
    }

    const usersChartCanvas = document.getElementById('usersChart');
    if (usersChartCanvas) {
        fetch('api/users.php?action=get_stats')
            .then(response => response.json())
            .then(data => {
                const ctx = usersChartCanvas.getContext('2d');
                new Chart(ctx, {
                    type: 'pie',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            label: 'Users by Role',
                            data: data.values,
                            backgroundColor: [
                                '#1e88e5',
                                '#ff8f00',
                                '#4caf50'
                            ],
                            borderColor: [
                                '#0d47a1',
                                '#ef6c00',
                                '#2e7d32'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'top',
                            },
                            title: {
                                display: true,
                                text: 'Users by Role'
                            }
                        }
                    }
                });
            })
            .catch(error => {
                console.error('Error loading chart data:', error);
            });
    }
});