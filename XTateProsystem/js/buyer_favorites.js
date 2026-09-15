/**
 * Real Estate Listing System
 * Buyer Favorites JavaScript File — Modernized
 */

document.addEventListener('DOMContentLoaded', function() {
    // Resolve API path depending on directory (/buyer/ vs root)
    const isBuyerDir = window.location.pathname.includes('/buyer/');
    const apiPath = isBuyerDir ? '../api/favorite.php' : 'api/favorite.php';

    // Toast helper using Notyf if available, with graceful fallback
    function showToast(message, type = 'success') {
        if (typeof Notyf !== 'undefined') {
            const notyf = new Notyf({
                duration: 3500,
                position: { x: 'right', y: 'top' },
                dismissible: true,
                types: [
                    {
                        type: 'info',
                        background: '#2563EB',
                        icon: false
                    }
                ]
            });
            if (type === 'success') {
                notyf.success(message);
            } else if (type === 'info') {
                notyf.open({ type: 'info', message: message });
            } else {
                notyf.error(message);
            }
        } else {
            const notification = document.createElement('div');
            notification.className = `modern-alert modern-alert--${type === 'info' ? 'info' : (type === 'error' ? 'danger' : 'success')} position-fixed top-0 start-50 translate-middle-x mt-3 shadow-lg`;
            notification.style.zIndex = '9999';
            notification.style.minWidth = '320px';
            notification.innerHTML = `
                <div class="d-flex align-items-center justify-content-between w-100 gap-2">
                    <span>${message}</span>
                    <button type="button" class="btn-close ms-2" style="font-size:0.75rem;" onclick="this.closest('.modern-alert').remove()"></button>
                </div>
            `;
            document.body.appendChild(notification);
            setTimeout(() => {
                if (notification.parentNode) notification.remove();
            }, 3500);
        }
    }

    // Modern Confirmation Modal
    function showConfirmationModal(title, message, confirmText, confirmCallback) {
        const existingModal = document.getElementById('xtateConfirmModal');
        if (existingModal) {
            existingModal.remove();
        }

        const isDanger = confirmText.toLowerCase().includes('remove') || confirmText.toLowerCase().includes('delete');
        const confirmBtnClass = isDanger ? 'btn-danger' : 'btn-primary';

        const modalHtml = `
            <div class="modal fade" id="xtateConfirmModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
                    <div class="modal-content" style="border-radius: 16px; border: 1px solid #E2E8F0; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); overflow: hidden;">
                        <div class="modal-header border-0 pb-0 pt-4 px-4">
                            <div style="width: 42px; height: 42px; border-radius: 50%; background: ${isDanger ? '#FEF2F2' : '#EFF6FF'}; color: ${isDanger ? '#EF4444' : '#2563EB'}; display: flex; align-items: center; justify-content: center; margin-right: 12px; flex-shrink: 0;">
                                <i data-lucide="${isDanger ? 'alert-triangle' : 'help-circle'}" style="width: 20px; height: 20px;"></i>
                            </div>
                            <h5 class="modal-title fw-bold" style="font-family: 'Plus Jakarta Sans', sans-serif; font-size: 1.1rem; color: #0F172A;">${title}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="font-size: 0.8rem;"></button>
                        </div>
                        <div class="modal-body px-4 py-3" style="color: #64748B; font-size: 0.9rem; line-height: 1.5;">
                            ${message}
                        </div>
                        <div class="modal-footer border-0 pt-2 pb-4 px-4 gap-2">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="border-radius: 10px; font-weight: 600; font-size: 0.88rem; padding: 8px 16px; border: 1px solid #E2E8F0; color: #64748B;">Cancel</button>
                            <button type="button" class="btn ${confirmBtnClass}" id="xtateConfirmBtn" style="border-radius: 10px; font-weight: 600; font-size: 0.88rem; padding: 8px 18px;">${confirmText}</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHtml);
        if (typeof lucide !== 'undefined') lucide.createIcons();

        const modalEl = document.getElementById('xtateConfirmModal');
        const modal = new bootstrap.Modal(modalEl);
        modal.show();

        document.getElementById('xtateConfirmBtn').addEventListener('click', function() {
            modal.hide();
            confirmCallback();
        });

        modalEl.addEventListener('hidden.bs.modal', function() {
            modalEl.remove();
        });
    }

    // 1. Remove Favorite Handler (Used on Favorites Page)
    const removeForms = document.querySelectorAll('.remove-favorite-form');
    if (removeForms.length > 0) {
        removeForms.forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                const propertyId = this.querySelector('input[name="property_id"]').value;
                const propertyCard = this.closest('.db-pcard, [data-property-id], .col-md-6');

                showConfirmationModal(
                    'Remove from Favorites',
                    'Are you sure you want to remove this property from your saved listings?',
                    'Remove',
                    () => {
                        const formData = new FormData();
                        formData.append('property_id', propertyId);
                        formData.append('action', 'remove');

                        fetch(apiPath, {
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
                                showToast('Property removed from favorites', 'info');

                                if (propertyCard) {
                                    propertyCard.style.transition = 'all 0.3s ease';
                                    propertyCard.style.opacity = '0';
                                    propertyCard.style.transform = 'scale(0.95)';

                                    setTimeout(() => {
                                        propertyCard.remove();

                                        // Update count pill
                                        const remainingCards = document.querySelectorAll('#favoritesGrid .db-pcard, .db-props-grid .db-pcard');
                                        const statPill = document.querySelector('.favorites-stat-pill span');
                                        if (statPill) {
                                            statPill.textContent = `${remainingCards.length} Saved Listings`;
                                        }

                                        // If empty, show modern empty state
                                        if (remainingCards.length === 0) {
                                            const grid = document.getElementById('favoritesGrid') || document.querySelector('.db-props-grid');
                                            if (grid) {
                                                const searchLink = isBuyerDir ? '../search.php' : 'search.php';
                                                grid.outerHTML = `
                                                    <div class="db-card">
                                                        <div class="favorites-empty-state">
                                                            <div class="empty-icon-circle">
                                                                <i data-lucide="heart-off"></i>
                                                            </div>
                                                            <h3>No favorites saved yet</h3>
                                                            <p>You haven't added any properties to your favorites yet. Browse through our premium property catalog and tap the heart icon on properties that catch your eye.</p>
                                                            <a href="${searchLink}" class="db-btn db-btn-primary">
                                                                <i data-lucide="compass" style="width:17px;height:17px;"></i>
                                                                <span>Browse Properties</span>
                                                            </a>
                                                        </div>
                                                    </div>
                                                `;
                                                if (typeof lucide !== 'undefined') lucide.createIcons();
                                            }
                                        }
                                    }, 300);
                                }
                            } else {
                                showToast(data.message || 'Failed to update favorites', 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Favorites removal error:', error);
                            showToast('An error occurred while removing the property. Please try again.', 'error');
                        });
                    }
                );
            });
        });
    }

    // 2. Favorite Toggle Buttons (Used on Property Details and Search Cards)
    const favoriteButtons = document.querySelectorAll('.favorite-btn');
    if (favoriteButtons.length > 0) {
        favoriteButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();

                const propertyId = this.getAttribute('data-property-id');
                const isFavorited = this.classList.contains('favorited');

                const title = isFavorited ? 'Remove from Favorites' : 'Add to Favorites';
                const message = isFavorited ?
                    'Are you sure you want to remove this property from your favorites?' :
                    'Would you like to bookmark this property to your saved favorites?';
                const confirmText = isFavorited ? 'Remove' : 'Save';

                showConfirmationModal(
                    title,
                    message,
                    confirmText,
                    () => {
                        const formData = new FormData();
                        formData.append('property_id', propertyId);
                        formData.append('action', 'toggle');

                        fetch(apiPath, {
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
                                    this.classList.add('favorited');
                                    this.innerHTML = '<i data-lucide="heart" style="width:15px;height:15px;fill:#EF4444;color:#EF4444;"></i> Remove from Favorites';
                                    showToast('Property added to your favorites!', 'success');
                                } else if (data.action === 'removed') {
                                    this.classList.remove('favorited');
                                    this.innerHTML = '<i data-lucide="heart" style="width:15px;height:15px;"></i> Save Property';
                                    showToast('Property removed from favorites', 'info');
                                }
                                if (typeof lucide !== 'undefined') lucide.createIcons();
                            } else {
                                if (data.redirect) {
                                    window.location.href = data.redirect;
                                } else {
                                    showToast(data.message || 'Action failed', 'error');
                                }
                            }
                        })
                        .catch(error => {
                            console.error('Favorites toggle error:', error);
                            showToast('An error occurred. Please try again.', 'error');
                        });
                    }
                );
            });
        });
    }
});