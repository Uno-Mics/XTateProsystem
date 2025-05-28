<?php
require_once '../inc/db.php';
require_once '../inc/functions.php';
require_once '../inc/auth.php';

// Check if user is logged in and has buyer role
checkPermission(['buyer']);

// Get buyer data
$buyerId = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = ?";
$buyer = fetchOne($sql, "i", [$buyerId]);

// Get favorite properties count - Using a fallback for development
$sql = "SELECT COUNT(*) as count FROM favorites WHERE buyer_id = ?";
$favoritesResult = fetchOne($sql, "i", [$buyerId]);
$favoritesCount = $favoritesResult ? $favoritesResult['count'] : 0;

// Get inquiries count - Using a fallback for development
$sql = "SELECT COUNT(*) as count FROM inquiries WHERE buyer_id = ?";
$inquiriesResult = fetchOne($sql, "i", [$buyerId]);
$inquiriesCount = $inquiriesResult ? $inquiriesResult['count'] : 0;

// Get recent properties
$sql = "SELECT p.*, pt.name as property_type, 
        (SELECT image_path FROM property_images WHERE property_id = p.id AND is_primary = 1 LIMIT 1) as primary_image
        FROM properties p 
        JOIN property_types pt ON p.property_type_id = pt.id 
        WHERE p.status = 'active' 
        ORDER BY p.created_at DESC LIMIT 5";
$recentProperties = fetchAll($sql);

// Get recent inquiries
$sql = "SELECT i.*, p.title as property_title, p.price, p.seller_id,
        (SELECT image_path FROM property_images WHERE property_id = p.id AND is_primary = 1 LIMIT 1) as property_image,
        u.full_name as seller_name, u.email as seller_email
        FROM inquiries i
        JOIN properties p ON i.property_id = p.id
        JOIN users u ON p.seller_id = u.id
        WHERE i.buyer_id = ?
        ORDER BY i.created_at DESC LIMIT 5";
$recentInquiries = fetchAll($sql, "i", [$buyerId]);

include '../inc/header.php';
?>

<style>

.notification-toast {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
    min-width: 300px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    border-radius: 4px;
    animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

#confirmDeleteModal .modal-content {
    border-radius: 8px;
}

#confirmDeleteModal .modal-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    border-radius: 8px 8px 0 0;
}

#confirmDeleteModal .modal-footer {
    border-top: 1px solid #dee2e6;
    padding: 1rem;
}

#confirmDeleteModal .btn-danger {
    background-color: #dc3545;
    border-color: #dc3545;
}

#confirmDeleteModal .btn-danger:hover {
    background-color: #c82333;
    border-color: #bd2130;
}
</style>

<div class="container py-5">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-3 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Buyer Dashboard</h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="dashboard.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                    <a href="favorites.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-heart me-2"></i> My Favorites
                        <?php if ($favoritesCount > 0): ?>
                            <span class="badge bg-primary rounded-pill ms-1"><?= $favoritesCount ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="messages.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-comments me-2"></i> Messages
                        <?php 
                        $unreadCount = getUnreadMessagesCount($buyerId);
                        if ($unreadCount > 0): 
                        ?>
                            <span class="badge bg-danger rounded-pill ms-1"><?= $unreadCount ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="../search.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-search me-2"></i> Find Properties
                    </a>
                    <a href="profile.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-user-edit me-2"></i> Edit Profile
                    </a>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-lg-9">
            <!-- Welcome Section -->
            <div class="dashboard-header mb-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="dashboard-title">Welcome, <?= $buyer['full_name'] ?></h2>
                        <p class="dashboard-subtitle">Here's what's happening with your account</p>
                    </div>
                    <div>
                        <a href="../search.php" class="btn btn-primary">
                            <i class="fas fa-search me-2"></i> Find Properties
                        </a>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="dashboard-stats mb-4">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-heart"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?= $favoritesCount ?></h3>
                        <p>Favorite Properties</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?= $inquiriesCount ?></h3>
                        <p>Property Inquiries</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-comments"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?= $unreadCount ?></h3>
                        <p>Unread Messages</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?= formatDateTime($buyer['created_at'], 'd M Y') ?></h3>
                        <p>Member Since</p>
                    </div>
                </div>
            </div>

            <!-- Recent Inquiries -->
            <div class="dashboard-content mb-4">
                <div class="dashboard-section-title">
                    <h3>Recent Inquiries</h3>
                    <?php if (count($recentInquiries) > 0): ?>
                        <a href="#" class="btn btn-sm btn-outline-primary">View All</a>
                    <?php endif; ?>
                </div>

                <?php if (empty($recentInquiries)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> You haven't made any inquiries yet. Browse properties and make inquiries to see them here.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Property</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentInquiries as $inquiry): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php
                                                    $imagePath = $inquiry['property_image'];
                                                    // If not an external URL and not empty, make it relative to root
                                                    if (!empty($imagePath) && substr($imagePath, 0, 4) !== 'http') {
                                                        // Remove leading slash if present
                                                        $imagePath = ltrim($imagePath, '/');
                                                        // Add path to parent directory since we're in buyer/
                                                        $imagePath = '../' . $imagePath;
                                                    } else if (empty($imagePath)) {
                                                        // Fallback image
                                                        $imagePath = 'https://images.unsplash.com/photo-1560518883-ce09059eeffa';
                                                    }
                                                ?>
                                                <div class="flex-shrink-0" style="width: 50px; height: 50px; background-image: url('<?= $imagePath ?>'); background-size: cover; background-position: center;"></div>
                                                <div class="ms-3">
                                                    <h6 class="mb-0"><?= $inquiry['property_title'] ?></h6>
                                                    <small class="text-muted"><?= $inquiry['seller_name'] ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= formatCurrency($inquiry['price']) ?></td>
                                        <td><?= formatInquiryStatus($inquiry['status']) ?></td>
                                        <td><?= formatDateTime($inquiry['created_at']) ?></td>
                                        <td>
                                            <a href="../property_details.php?id=<?= $inquiry['property_id'] ?>" class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="View Property">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="messages.php?user=<?= $inquiry['seller_id'] ?>" class="btn btn-sm btn-outline-success" data-bs-toggle="tooltip" title="Message Seller">
                                                <i class="fas fa-comment"></i>
                                            </a>
                                            <button class="btn btn-sm btn-outline-danger delete-inquiry" data-inquiry-id="<?= $inquiry['id'] ?>" data-bs-toggle="tooltip" title="Delete Inquiry">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Recent Properties -->
            <div class="dashboard-content">
                <div class="dashboard-section-title">
                    <h3>Recently Added Properties</h3>
                    <a href="../search.php" class="btn btn-sm btn-outline-primary">Browse All</a>
                </div>

                <div class="row">
                    <?php foreach ($recentProperties as $property): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="property-card">
                                <?php
                                    $imagePath = $property['primary_image'];
                                    // If not an external URL and not empty, make it relative to root
                                    if (!empty($imagePath) && substr($imagePath, 0, 4) !== 'http') {
                                        // Remove leading slash if present
                                        $imagePath = ltrim($imagePath, '/');
                                        // Add path to parent directory since we're in buyer/
                                        $imagePath = '../' . $imagePath;
                                    } else if (empty($imagePath)) {
                                        // Fallback image
                                        $imagePath = 'https://images.unsplash.com/photo-1560518883-ce09059eeffa';
                                    }
                                ?>
                                <div class="property-image" style="background-image: url('<?= $imagePath ?>');">
                                    <div class="property-price"><?= formatCurrency($property['price']) ?></div>
                                </div>
                                <div class="property-content">
                                    <h3 class="property-title">
                                        <a href="../property_details.php?id=<?= $property['id'] ?>"><?= $property['title'] ?></a>
                                    </h3>
                                    <div class="property-location">
                                        <i class="fas fa-map-marker-alt"></i> <?= $property['city'] ?>, <?= $property['state'] ?>
                                    </div>
                                    <div class="property-features">
                                        <div class="feature">
                                            <i class="fas fa-bed"></i> <?= $property['bedrooms'] ?> Beds
                                        </div>
                                        <div class="feature">
                                            <i class="fas fa-bath"></i> <?= $property['bathrooms'] ?> Baths
                                        </div>
                                        <div class="feature">
                                            <i class="fas fa-ruler-combined"></i> <?= number_format($property['area']) ?> sqft
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Profile Modal -->
<div class="modal fade" id="profileModal" tabindex="-1" aria-labelledby="profileModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="profileModalLabel">Edit Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="profileForm" method="POST" action="../api/users.php">
                    <input type="hidden" name="action" value="update_profile">

                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" value="<?= $buyer['full_name'] ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?= $buyer['email'] ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" id="phone" name="phone" value="<?= $buyer['phone'] ?>" required>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Update Profile</button>
                    </div>
                </form>

                <hr>

                <form id="passwordForm" method="POST" action="../api/users.php">
                    <input type="hidden" name="action" value="change_password">

                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current Password</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>

                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                    </div>

                    <div class="mb-3">
                        <label for="confirm_new_password" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_new_password" name="confirm_new_password" required>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-outline-primary">Change Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="../js/auth.js"></script>

<?php include '../inc/footer.php'; ?>


<!-- Delete Confirmation Modal -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmDeleteModalLabel">Delete Inquiry</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this inquiry?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltips.forEach(tooltip => new bootstrap.Tooltip(tooltip));

    let currentButton = null;
    const deleteModal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));

    // Handle delete inquiry
    document.querySelectorAll('.delete-inquiry').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            currentButton = this;
            deleteModal.show();
        });
    });

    // Handle confirmation
    document.getElementById('confirmDelete').addEventListener('click', function() {
        if (!currentButton) return;

        const inquiryId = currentButton.getAttribute('data-inquiry-id');
        const formData = new FormData();
        formData.append('action', 'delete_inquiry');
        formData.append('inquiry_id', inquiryId);

        fetch('../api/inquiries.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            deleteModal.hide();
            if (data.success) {
                // Remove the row from the table
                currentButton.closest('tr').remove();

                // Show success notification
                const notification = document.createElement('div');
                notification.className = 'alert alert-success position-fixed text-center';
                notification.style.top = '20px';
                notification.style.left = '50%';
                notification.style.transform = 'translateX(-50%)';
                notification.style.zIndex = '9999';
                notification.style.minWidth = '300px';
                notification.innerHTML = `
                    <div class="d-flex align-items-center justify-content-center">
                        <i class="fas fa-check-circle me-2"></i>
                        <span>Inquiry deleted successfully!</span>
                        <button type="button" class="btn-close ms-3" onclick="this.parentElement.parentElement.remove()"></button>
                    </div>
                `;
                document.body.appendChild(notification);
                setTimeout(() => {
                    notification.remove();
                    window.location.reload(); // Reload the page
                }, 2000);
            } else {
                // Show error notification
                const notification = document.createElement('div');
                notification.className = 'notification-toast alert alert-danger';
                notification.innerHTML = `
                    <div class="d-flex align-items-center">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <span>${data.message || 'Failed to delete inquiry'}</span>
                        <button type="button" class="btn-close ms-3" onclick="this.parentElement.parentElement.remove()"></button>
                    </div>
                `;
                document.body.appendChild(notification);
                setTimeout(() => notification.remove(), 3000);
            }
        })
        .catch(error => {
            deleteModal.hide();
            console.error('Error:', error);
            // Show error notification
            const notification = document.createElement('div');
            notification.className = 'notification-toast alert alert-danger';
            notification.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <span>An error occurred while deleting the inquiry</span>
                    <button type="button" class="btn-close ms-3" onclick="this.parentElement.parentElement.remove()"></button>
                </div>
            `;
            document.body.appendChild(notification);
            setTimeout(() => notification.remove(), 3000);
        });
    });
});
</script>