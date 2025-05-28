/**
 * Real Estate Listing System
 * Property JavaScript File
 */

document.addEventListener('DOMContentLoaded', function() {
    // Property image upload preview
    const propertyImageInput = document.getElementById('property_images');
    const imagePreviewContainer = document.getElementById('imagePreviewContainer');

    if (propertyImageInput && imagePreviewContainer) {
        propertyImageInput.addEventListener('change', function() {
            // Clear existing previews
            imagePreviewContainer.innerHTML = '';

            if (this.files) {
                // Check if files exceed maximum count
                const maxFiles = 10;
                if (this.files.length > maxFiles) {
                    alert(`You can only upload a maximum of ${maxFiles} images.`);
                    this.value = '';
                    return;
                }

                // Check file size
                const maxSize = 5 * 1024 * 1024; // 5MB
                for (let i = 0; i < this.files.length; i++) {
                    if (this.files[i].size > maxSize) {
                        alert(`The file "${this.files[i].name}" exceeds the maximum size of 5MB.`);
                        this.value = '';
                        imagePreviewContainer.innerHTML = '';
                        return;
                    }
                }

                // Create preview for each file
                Array.from(this.files).forEach(file => {
                    // Only process images
                    if (!file.type.match('image.*')) {
                        return;
                    }

                    const reader = new FileReader();

                    reader.onload = function(e) {
                        const previewCard = document.createElement('div');
                        previewCard.className = 'col-md-3 mb-3';

                        const cardContent = `
                            <div class="card">
                                <img src="${e.target.result}" class="card-img-top" alt="Property Image" style="height: 150px; object-fit: cover;">
                                <div class="card-body p-2">
                                    <p class="card-text text-truncate" title="${file.name}">${file.name}</p>
                                </div>
                            </div>
                        `;

                        previewCard.innerHTML = cardContent;
                        imagePreviewContainer.appendChild(previewCard);
                    };

                    reader.readAsDataURL(file);
                });
            }
        });
    }

    // Property form validation
    const propertyForm = document.getElementById('propertyForm');
    if (propertyForm) {
        propertyForm.addEventListener('submit', function(e) {
            let isValid = true;

            // Required fields validation
            const requiredFields = [
                { id: 'title', message: 'Please enter property title' },
                { id: 'description', message: 'Please enter property description' },
                { id: 'price', message: 'Please enter property price' },
                { id: 'address', message: 'Please enter property address' },
                { id: 'city', message: 'Please enter property city' },
                { id: 'state', message: 'Please enter property state' },
                { id: 'zip_code', message: 'Please enter property zip code' }
            ];

            requiredFields.forEach(field => {
                const input = document.getElementById(field.id);
                if (input && input.value.trim() === '') {
                    isValid = false;
                    showValidationError(input, field.message);
                } else if (input) {
                    removeValidationError(input);
                }
            });

            // Price validation
            const priceInput = document.getElementById('price');
            if (priceInput && priceInput.value.trim() !== '') {
                const price = parseFloat(priceInput.value);
                if (isNaN(price) || price <= 0) {
                    isValid = false;
                    showValidationError(priceInput, 'Please enter a valid price');
                }
            }

            // Area validation
            const areaInput = document.getElementById('area');
            if (areaInput && areaInput.value.trim() !== '') {
                const area = parseFloat(areaInput.value);
                if (isNaN(area) || area <= 0) {
                    isValid = false;
                    showValidationError(areaInput, 'Please enter a valid area');
                } else {
                    removeValidationError(areaInput);
                }
            }

            // Number validation for bedrooms and bathrooms
            const bedroomsInput = document.getElementById('bedrooms');
            if (bedroomsInput && bedroomsInput.value.trim() !== '') {
                const bedrooms = parseInt(bedroomsInput.value);
                if (isNaN(bedrooms) || bedrooms < 0) {
                    isValid = false;
                    showValidationError(bedroomsInput, 'Please enter a valid number of bedrooms');
                } else {
                    removeValidationError(bedroomsInput);
                }
            }

            const bathroomsInput = document.getElementById('bathrooms');
            if (bathroomsInput && bathroomsInput.value.trim() !== '') {
                const bathrooms = parseInt(bathroomsInput.value);
                if (isNaN(bathrooms) || bathrooms < 0) {
                    isValid = false;
                    showValidationError(bathroomsInput, 'Please enter a valid number of bathrooms');
                } else {
                    removeValidationError(bathroomsInput);
                }
            }

            // Zip code validation
            const zipCodeInput = document.getElementById('zip_code');
            if (zipCodeInput && zipCodeInput.value.trim() !== '') {
                const zipRegex = /^\d{4,}(-\d{4})?$/;
                if (!zipRegex.test(zipCodeInput.value.trim())) {
                    isValid = false;
                    showValidationError(zipCodeInput, 'Please enter a valid zip code (e.g., 12345 or 12345-6789)');
                } else {
                    removeValidationError(zipCodeInput);
                }
            }

            // Image validation on add property form
            const imageInput = document.getElementById('property_images');
            if (imageInput && window.location.href.includes('add_property.php')) {
                if (imageInput.files.length === 0) {
                    isValid = false;
                    showValidationError(imageInput, 'Please upload at least one property image');
                } else {
                    removeValidationError(imageInput);
                }
            }

            if (!isValid) {
                e.preventDefault();

                // Scroll to first error
                const firstErrorElement = document.querySelector('.is-invalid');
                if (firstErrorElement) {
                    firstErrorElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        });
    }

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

    // Show notification modal
    function showNotification(message, type = 'success') {
        // Don't show notification for localhost
        if (window.location.hostname === 'localhost') return;

        const notification = document.createElement('div');
        notification.className = `modal fade show`;
        notification.style.display = 'block';
        notification.style.backgroundColor = 'rgba(0,0,0,0.5)';

        const icon = type === 'success' ? 'check-circle' : 'exclamation-circle';
        const color = type === 'success' ? '#198754' : '#dc3545';

        notification.innerHTML = `
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-body text-center p-4">
                        <i class="fas fa-${icon} fa-3x mb-3" style="color: ${color}"></i>
                        <h5 class="mb-3">${message}</h5>
                        <button type="button" class="btn btn-sm btn-secondary" onclick="this.closest('.modal').remove()">Close</button>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(notification);
        setTimeout(() => notification.remove(), 3000);
    }

    // Delete property image
    const deleteImageButtons = document.querySelectorAll('.delete-image-btn');
    deleteImageButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();

            if (confirm('Are you sure you want to delete this image?')) {
                const imageId = this.getAttribute('data-image-id');
                const propertyId = this.getAttribute('data-property-id');
                const button = this;
                const imageContainer = button.closest('.col-md-3');

                // Show loading state
                button.disabled = true;
                const originalText = button.innerHTML;
                button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Deleting...';

                fetch('../api/properties.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=delete_image&image_id=${imageId}&property_id=${propertyId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove the image container with animation
                        imageContainer.style.transition = 'all 0.3s ease';
                        imageContainer.style.opacity = '0';
                        setTimeout(() => imageContainer.remove(), 300);

                        // Show success notification modal
                        if (window.location.hostname !== 'localhost') {
                            const modal = document.createElement('div');
                            modal.className = 'modal fade show notification-modal';
                            modal.style.display = 'block';
                            modal.style.backgroundColor = 'rgba(0,0,0,0.5)';
                            modal.innerHTML = `
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-body text-center p-4">
                                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                            <h5 class="mb-3">Image deleted successfully!</h5>
                                            <button type="button" class="btn btn-sm btn-success" onclick="this.closest('.modal').remove()">Close</button>
                                        </div>
                                    </div>
                                </div>
                            `;
                            document.body.appendChild(modal);
                            setTimeout(() => modal.remove(), 3000);
                        }
                    } else {
                        showErrorModal(data.message || 'Failed to delete image');
                        // Reset button state
                        button.disabled = false;
                        button.innerHTML = originalText;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showErrorModal('An error occurred while deleting the image');
                    // Reset button state
                    button.disabled = false;
                    button.innerHTML = originalText;
                });
            }
        });
    });

    // Set primary image
    const setPrimaryButtons = document.querySelectorAll('.set-primary-btn');
    setPrimaryButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();

            const imageId = this.getAttribute('data-image-id');
            const propertyId = this.getAttribute('data-property-id');
            const button = this;

            // Show loading state
            button.disabled = true;
            const originalText = button.innerHTML;
            button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Setting...';

            fetch('../api/properties.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=set_primary_image&image_id=${imageId}&property_id=${propertyId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove primary badge and show all set primary buttons
                    document.querySelectorAll('.primary-badge').forEach(badge => {
                        badge.remove();
                    });
                    document.querySelectorAll('.set-primary-btn').forEach(btn => {
                        btn.style.display = 'inline-block';
                    });

                    // Add primary badge to current card
                    const card = button.closest('.card');
                    const primaryBadge = document.createElement('div');
                    primaryBadge.className = 'badge bg-primary mb-2 primary-badge';
                    primaryBadge.textContent = 'Primary Image';
                    card.querySelector('.card-body').insertBefore(primaryBadge, button);

                    // Hide current set primary button
                    button.style.display = 'none';

                    // Show success notification modal
                    if (window.location.hostname !== 'localhost') {
                        const modal = document.createElement('div');
                        modal.className = 'modal fade show notification-modal';
                        modal.style.display = 'block';
                        modal.style.backgroundColor = 'rgba(0,0,0,0.5)';
                        modal.innerHTML = `
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-body text-center p-4">
                                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                        <h5 class="mb-3">Image set as primary successfully!</h5>
                                        <button type="button" class="btn btn-sm btn-success" onclick="this.closest('.modal').remove()">Close</button>
                                    </div>
                                </div>
                            </div>
                        `;
                        document.body.appendChild(modal);
                        setTimeout(() => modal.remove(), 3000);
                    }
                } else {
                    showErrorModal(data.message || 'Failed to set primary image');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showErrorModal('An error occurred while setting primary image');
            })
            .finally(() => {
                // Reset button state
                button.disabled = false;
                button.innerHTML = originalText;
            });
        });
    });

    // Helper function to show error modal
    function showErrorModal(message) {
        if (window.location.hostname === 'localhost') return;

        const modal = document.createElement('div');
        modal.className = 'modal fade show notification-modal';
        modal.style.display = 'block';
        modal.style.backgroundColor = 'rgba(0,0,0,0.5)';
        modal.innerHTML = `
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-body text-center p-4">
                        <i class="fas fa-exclamation-circle fa-3x text-danger mb-3"></i>
                        <h5 class="mb-3">${message}</h5>
                        <button type="button" class="btn btn-sm btn-danger" onclick="this.closest('.modal').remove()">Close</button>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
        setTimeout(() => modal.remove(), 3000);
    }

    // Property price range slider if exists
    const priceRange = document.getElementById('price-range');
    if (priceRange) {
        const priceMin = document.getElementById('price_min');
        const priceMax = document.getElementById('price_max');
        const priceDisplay = document.getElementById('price-display');

        if (priceMin && priceMax && priceDisplay) {
            noUiSlider.create(priceRange, {
                start: [parseInt(priceMin.value) || 0, parseInt(priceMax.value) || 1000000],
                connect: true,
                step: 5000,
                range: {
                    'min': 0,
                    'max': 1000000
                },
                format: {
                    to: function(value) {
                        return parseInt(value);
                    },
                    from: function(value) {
                        return parseInt(value);
                    }
                }
            });

            priceRange.noUiSlider.on('update', function(values, handle) {
                priceMin.value = values[0];
                priceMax.value = values[1];
                priceDisplay.textContent = `$${values[0].toLocaleString()} - $${values[1].toLocaleString()}`;
            });
        }
    }

    // Inquiry form response buttons
    const approveInquiryButtons = document.querySelectorAll('.approve-inquiry-btn');
    const rejectInquiryButtons = document.querySelectorAll('.reject-inquiry-btn');

    approveInquiryButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();

            const inquiryId = this.getAttribute('data-inquiry-id');
            updateInquiryStatus(inquiryId, 'approved');
        });
    });

    rejectInquiryButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();

            const inquiryId = this.getAttribute('data-inquiry-id');
            updateInquiryStatus(inquiryId, 'rejected');
        });
    });

    function updateInquiryStatus(inquiryId, status) {
        // Show a loading message
        alert("Processing your request... Please wait.");

        // Fix the path to start from root
        fetch('../api/inquiries.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=update_status&inquiry_id=${inquiryId}&status=${status}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message before reloading
                alert(`Inquiry successfully ${status}!`);
                // Update UI or reload page
                window.location.reload();
            } else {
                alert(data.message || `Failed to ${status} inquiry. Please try again.`);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again. Error details: ' + error);
        });
    }
});