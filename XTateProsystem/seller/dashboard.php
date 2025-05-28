<?php
require_once '../inc/db.php';
require_once '../inc/functions.php';
require_once '../inc/auth.php';

// Check if user is logged in and has seller role
checkPermission(['seller']);

// Handle property deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_property') {
    $propertyId = isset($_POST['property_id']) ? intval($_POST['property_id']) : 0;
    $sellerId = $_SESSION['user_id'];

    if ($propertyId > 0) {
        // Verify the property belongs to this seller
        $sql = "SELECT * FROM properties WHERE id = ? AND seller_id = ?";
        $property = fetchOne($sql, "ii", [$propertyId, $sellerId]);

        if ($property) {
            $conn = connectDB();
            try {
                $conn->begin_transaction();

                // Delete property images
                $sql = "SELECT image_path FROM property_images WHERE property_id = ?";
                $images = fetchAll($sql, "i", [$propertyId]);

                foreach ($images as $image) {
                    if (file_exists('../' . $image['image_path'])) {
                        unlink('../' . $image['image_path']);
                    }
                }

                // Delete related records
                $conn->query("DELETE FROM property_images WHERE property_id = $propertyId");
                $conn->query("DELETE FROM inquiries WHERE property_id = $propertyId");
                $conn->query("DELETE FROM favorites WHERE property_id = $propertyId");
                $conn->query("DELETE FROM properties WHERE id = $propertyId AND seller_id = $sellerId");

                $conn->commit();

                $_SESSION['success_message'] = "Property deleted successfully!";
            } catch (Exception $e) {
                $conn->rollback();
                $_SESSION['error_message'] = "Failed to delete property. Please try again.";
            }
            closeDB($conn);
        }
    }

    // Redirect back to dashboard
    header("Location: dashboard.php");
    exit;
}

// Get seller data
$sellerId = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = ?";
$seller = fetchOne($sql, "i", [$sellerId]);

// Get property count
$sql = "SELECT COUNT(*) as count FROM properties WHERE seller_id = ?";
$propertiesCount = fetchOne($sql, "i", [$sellerId])['count'];

// Get active inquiries count
$sql = "SELECT COUNT(*) as count FROM inquiries i 
        JOIN properties p ON i.property_id = p.id 
        WHERE p.seller_id = ? AND i.status = 'pending'";
$pendingInquiriesCount = fetchOne($sql, "i", [$sellerId])['count'];

// Get unread messages count
$unreadCount = getUnreadMessagesCount($sellerId);

// Get recent properties
$sql = "SELECT p.*, pt.name as property_type, 
        (SELECT image_path FROM property_images WHERE property_id = p.id AND is_primary = 1 LIMIT 1) as primary_image
        FROM properties p 
        JOIN property_types pt ON p.property_type_id = pt.id 
        WHERE p.seller_id = ? 
        ORDER BY p.created_at DESC LIMIT 5";
$recentProperties = fetchAll($sql, "i", [$sellerId]);

// Get recent inquiries
$sql = "SELECT i.*, p.title as property_title, p.price, 
        (SELECT image_path FROM property_images WHERE property_id = p.id AND is_primary = 1 LIMIT 1) as property_image,
        u.full_name as buyer_name, u.email as buyer_email
        FROM inquiries i
        JOIN properties p ON i.property_id = p.id
        JOIN users u ON i.buyer_id = u.id
        WHERE p.seller_id = ?
        ORDER BY i.created_at DESC LIMIT 5";
$recentInquiries = fetchAll($sql, "i", [$sellerId]);

include '../inc/header.php';
?>

<div class="container py-5">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-3 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Seller Dashboard</h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="dashboard.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                    <a href="add_property.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-plus-circle me-2"></i> Add Property
                    </a>
                    <a href="inquiries.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-envelope me-2"></i> Inquiries
                        <?php if ($pendingInquiriesCount > 0): ?>
                            <span class="badge bg-primary rounded-pill ms-1"><?= $pendingInquiriesCount ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="messages.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-comments me-2"></i> Messages
                        <?php if ($unreadCount > 0): ?>
                            <span class="badge bg-danger rounded-pill ms-1"><?= $unreadCount ?></span>
                        <?php endif; ?>
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
                        <h2 class="dashboard-title">Welcome, <?= $seller['full_name'] ?></h2>
                        <p class="dashboard-subtitle">Manage your property listings and inquiries</p>
                    </div>
                    <div>
                        <a href="add_property.php" class="btn btn-primary">
                            <i class="fas fa-plus-circle me-2"></i> Add New Property
                        </a>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="dashboard-stats mb-4">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-home"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?= $propertiesCount ?></h3>
                        <p>Properties Listed</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?= $pendingInquiriesCount ?></h3>
                        <p>Pending Inquiries</p>
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
                        <h3><?= formatDateTime($seller['created_at'], 'd M Y') ?></h3>
                        <p>Member Since</p>
                    </div>
                </div>
            </div>

            <!-- Recent Inquiries -->
            <div class="dashboard-content mb-4">
                <div class="dashboard-section-title">
                    <h3>Recent Inquiries</h3>
                    <?php if (count($recentInquiries) > 0): ?>
                        <a href="inquiries.php" class="btn btn-sm btn-outline-primary">View All</a>
                    <?php endif; ?>
                </div>

                <?php if (empty($recentInquiries)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> You haven't received any inquiries yet. List more properties to attract buyer interest.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Property</th>
                                    <th>Buyer</th>
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
                                                if (!empty($imagePath)) {
                                                    if (substr($imagePath, 0, 4) !== 'http') {
                                                        $imagePath = '../' . ltrim($imagePath, '/');
                                                    }
                                                } else {
                                                    $imagePath = 'https://images.unsplash.com/photo-1560518883-ce09059eeffa';
                                                }
                                                ?>
                                                <div class="flex-shrink-0" style="width: 50px; height: 50px; background-image: url('<?= $imagePath ?>'); background-size: cover; background-position: center;"></div>
                                                <div class="ms-3">
                                                    <h6 class="mb-0"><?= $inquiry['property_title'] ?></h6>
                                                    <small class="text-muted"><?= formatCurrency($inquiry['price']) ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?= $inquiry['buyer_name'] ?><br>
                                            <small class="text-muted"><?= $inquiry['buyer_email'] ?></small>
                                        </td>
                                        <td><?= formatInquiryStatus($inquiry['status']) ?></td>
                                        <td><?= formatDateTime($inquiry['created_at']) ?></td>
                                        <td>
                                            <a href="inquiries.php?id=<?= $inquiry['id'] ?>" class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="View Inquiry">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($inquiry['status'] === 'pending'): ?>
                                                <a href="javascript:void(0);" onclick="approveInquiry(<?= $inquiry['id'] ?>)" class="btn btn-sm btn-outline-success" data-bs-toggle="tooltip" title="Approve">
                                                    <i class="fas fa-check"></i>
                                                </a>
                                                <a href="javascript:void(0);" onclick="rejectInquiry(<?= $inquiry['id'] ?>)" class="btn btn-sm btn-outline-danger" data-bs-toggle="tooltip" title="Reject">
                                                    <i class="fas fa-times"></i>
                                                </a>
                                            <?php endif; ?>
                                            <button class="btn btn-sm btn-outline-danger delete-inquiry-btn" 
                                                    data-inquiry-id="<?= $inquiry['id'] ?>"
                                                    data-property-title="<?= htmlspecialchars($inquiry['property_title'], ENT_QUOTES) ?>"
                                                    data-bs-toggle="tooltip" 
                                                    title="Delete">
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

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteInquiryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Delete Inquiry</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this inquiry?</p>
                <p class="text-danger"><small>This action cannot be undone.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger confirm-delete">Delete</button>
            </div>
        </div>
    </div>
</div>

<style>
.notification-toast {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
    min-width: 300px;
    background-color: #ffffff;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    border-radius: 8px;
    border: none;
    animation: slideIn 0.3s ease-out;
}

.notification-toast.success {
    border-left: 4px solid #28a745;
}

.notification-toast.error {
    border-left: 4px solid #dc3545;
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
</style>

            <!-- My Properties -->
            <div class="dashboard-content">
                <div class="dashboard-section-title">
                    <h3>My Properties</h3>
                    <?php if (count($recentProperties) > 0): ?>
                        <a href="add_property.php" class="btn btn-sm btn-outline-primary">Add New</a>
                    <?php endif; ?>
                </div>

                <?php if (empty($recentProperties)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> You haven't listed any properties yet. 
                        <a href="add_property.php" class="alert-link">Add your first property</a> to start attracting buyers.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Property</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                    <th>Listed Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentProperties as $property): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php
                                $imagePath = $property['primary_image'];
                                if (!empty($imagePath)) {
                                    if (substr($imagePath, 0, 4) !== 'http') {
                                        $imagePath = '../' . ltrim($imagePath, '/');
                                    }
                                } else {
                                    $imagePath = 'https://images.unsplash.com/photo-1560518883-ce09059eeffa';
                                }
                                ?>
                                <div class="flex-shrink-0" style="width: 50px; height: 50px; background-image: url('<?= $imagePath ?>'); background-size: cover; background-position: center;"></div>
                                                <div class="ms-3">
                                                    <h6 class="mb-0"><?= $property['title'] ?></h6>
                                                    <small class="text-muted"><?= $property['city'] ?>, <?= $property['state'] ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= formatCurrency($property['price']) ?></td>
                                        <td>
                                            <span class="badge <?= $property['status'] === 'active' ? 'bg-success' : 'bg-warning' ?>">
                                                <?= ucfirst($property['status']) ?>
                                            </span>
                                        </td>
                                        <td><?= formatDateTime($property['created_at'], 'd M Y') ?></td>
                                        <td>
                                            <a href="../property_details.php?id=<?= $property['id'] ?>" class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit_property.php?id=<?= $property['id'] ?>" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button class="btn btn-sm btn-outline-danger delete-property-btn" 
                                                    data-property-id="<?= $property['id'] ?>"
                                                    data-property-title="<?= htmlspecialchars($property['title'], ENT_QUOTES) ?>"
                                                    data-bs-toggle="tooltip" 
                                                    title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if (count($recentProperties) == 5 && $propertiesCount > 5): ?>
                        <div class="text-center mt-3">
                            <a href="#" class="btn btn-outline-primary">View All Properties</a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
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
                        <input type="text" class="form-control" id="full_name" name="full_name" value="<?= $seller['full_name'] ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?= $seller['email'] ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" id="phone" name="phone" value="<?= $seller['phone'] ?>" required>
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
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Delete inquiry functionality
    // Inquiry deletion
    const deleteInquiryButtons = document.querySelectorAll('.delete-inquiry-btn');
    const deleteInquiryModal = new bootstrap.Modal(document.getElementById('deleteInquiryModal'));
    let currentInquiryId = null;
    let currentInquiryRow = null;

    deleteInquiryButtons.forEach(button => {
        button.addEventListener('click', function() {
            currentInquiryId = this.getAttribute('data-inquiry-id');
            currentInquiryRow = this.closest('tr');
            deleteInquiryModal.show();
        });
    });

    document.querySelector('.confirm-delete').addEventListener('click', function() {
        if (!currentInquiryId) return;

        const formData = new FormData();
        formData.append('action', 'delete_inquiry');
        formData.append('inquiry_id', currentInquiryId);

        fetch('../api/inquiries.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            deleteInquiryModal.hide();
            if (data.success) {
                // Remove the row
                currentInquiryRow.remove();

                // Show success notification
                const notification = document.createElement('div');
                notification.className = 'alert alert-success position-fixed top-0 start-50 translate-middle-x mt-3';
                notification.style.zIndex = '9999';
                notification.innerHTML = `
                    <div class="d-flex align-items-center">
                        <i class="fas fa-check-circle me-2"></i>
                        <span>Inquiry deleted successfully!</span>
                        <button type="button" class="btn-close ms-3" onclick="this.parentElement.parentElement.remove()"></button>
                    </div>
                `;
                document.body.appendChild(notification);
                setTimeout(() => notification.remove(), 3000);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            deleteInquiryModal.hide();

            // Show error notification
            const notification = document.createElement('div');
            notification.className = 'alert alert-danger position-fixed top-0 start-50 translate-middle-x mt-3';
            notification.style.zIndex = '9999';
            notification.style.backgroundColor = '#dc3545';
            notification.style.color = '#ffffff';
            notification.style.border = 'none';
            notification.style.boxShadow = '0 2px 5px rgba(0,0,0,0.2)';
            notification.style.minWidth = '300px';
            notification.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <span>Failed to delete inquiry. Please try again.</span>
                    <button type="button" class="btn-close ms-3" onclick="this.parentElement.parentElement.remove()"></button>
                </div>
            `;
            document.body.appendChild(notification);
            setTimeout(() => notification.remove(), 3000);
        });
    });

    // Property deletion
    const deletePropertyButtons = document.querySelectorAll('.delete-property-btn');
    let currentPropertyId = null;
    let currentPropertyRow = null;

    deletePropertyButtons.forEach(button => {
        button.addEventListener('click', function() {
            currentPropertyId = this.getAttribute('data-property-id');
            currentPropertyRow = this.closest('tr');

            document.body.appendChild(modal);
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();

            // Handle delete confirmation
            modal.querySelector('.confirm-property-delete').addEventListener('click', function() {
                const formData = new FormData();
                formData.append('action', 'delete_property');
                formData.append('property_id', currentPropertyId);

                fetch('dashboard.php', {
                    method: 'POST',
                    body: formData
                })
                .then(() => {
                    bsModal.hide();

                    // Show success notification
                    const notification = document.createElement('div');
                    notification.className = 'alert alert-success position-fixed top-0 start-50 translate-middle-x mt-3';
                    notification.style.zIndex = '9999';
                    notification.innerHTML = `
                        <div class="d-flex align-items-center">
                            <i class="fas fa-check-circle me-2"></i>
                            <span>Property deleted successfully!</span>
                            <button type="button" class="btn-close ms-3" onclick="this.parentElement.parentElement.remove()"></button>
                        </div>
                    `;
                    document.body.appendChild(notification);

                    setTimeout(() => {
                        notification.remove();
                        window.location.reload();
                    }, 2000);
                })
                .catch(error => {
                    console.error('Error:', error);
                    bsModal.hide();

                    // Show error notification
                    const notification = document.createElement('div');
                    notification.className = 'alert alert-danger position-fixed top-0 start-50 translate-middle-x mt-3';
                    notification.style.zIndex = '9999';
                    notification.style.backgroundColor = '#dc3545';
                    notification.style.color = '#ffffff';
                    notification.style.border = 'none';
                    notification.style.boxShadow = '0 2px 5px rgba(0,0,0,0.2)';
                    notification.style.minWidth = '300px';
                    notification.innerHTML = `
                        <div class="d-flex align-items-center">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <span>Failed to delete property. Please try again.</span>
                            <button type="button" class="btn-close ms-3" onclick="this.parentElement.parentElement.remove()"></button>
                        </div>
                    `;
                    document.body.appendChild(notification);
                    setTimeout(() => notification.remove(), 3000);
                });
            });

            // Clean up modal when hidden
            modal.addEventListener('hidden.bs.modal', function() {
                modal.remove();
            });
        });
    });

    document.querySelector('.confirm-delete').addEventListener('click', function() {
        if (!currentInquiryId) return;

        const formData = new FormData();
        formData.append('action', 'delete_inquiry');
        formData.append('inquiry_id', currentInquiryId);

        // Disable delete button and show loading state
        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Deleting...';

        fetch('../api/inquiries.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            deleteModal.hide();

            if (data.success) {
                // Remove the row
                const row = document.querySelector(`button[data-inquiry-id="${currentInquiryId}"]`).closest('tr');
                row.remove();

                // Show success notification
                const notification = document.createElement('div');
                notification.className = 'notification-toast success';
                notification.innerHTML = `
                    <div class="d-flex p-3 align-items-center">
                        <i class="fas fa-check-circle text-success me-2"></i>
                        <span>Inquiry deleted successfully!</span>
                        <button type="button" class="btn-close ms-auto" onclick="this.parentElement.parentElement.remove()"></button>
                    </div>
                `;
                document.body.appendChild(notification);

                setTimeout(() => {
                    notification.remove();
                }, 3000);
            } else {
                throw new Error(data.message || 'Failed to delete inquiry');
            }
        })
        .catch(error => {
            deleteModal.hide();

            // Show error notification
            const notification = document.createElement('div');
            notification.className = 'notification-toast error';
            notification.innerHTML = `
                <div class="d-flex p-3 align-items-center">
                    <i class="fas fa-exclamation-circle text-danger me-2"></i>
                    <span>${error.message}</span>
                    <button type="button" class="btn-close ms-auto" onclick="this.parentElement.parentElement.remove()"></button>
                </div>
            `;
            document.body.appendChild(notification);

            setTimeout(() => {
                notification.remove();
            }, 3000);
        })
        .finally(() => {
            // Reset delete button
            this.disabled = false;
            this.innerHTML = 'Delete';
        });
    });
    const deleteButtons = document.querySelectorAll('.delete-property-btn');

    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const propertyId = this.getAttribute('data-property-id');
            const propertyTitle = this.getAttribute('data-property-title');
            const propertyRow = this.closest('tr');

            // Create confirmation modal
            const modal = document.createElement('div');
            modal.className = 'modal fade';
            modal.innerHTML = `
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Delete Property</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to delete "${propertyTitle}"?</p>
                            <p class="text-danger"><small>This action cannot be undone.</small></p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-danger confirm-delete">Delete</button>
                        </div>
                    </div>
                </div>
            `;

            document.body.appendChild(modal);
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();

            // Handle delete confirmation
            modal.querySelector('.confirm-delete').addEventListener('click', function() {
                // Disable the delete button and show loading state
                const deleteButton = this;
                deleteButton.disabled = true;
                deleteButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Deleting...';

                const formData = new FormData();
                formData.append('action', 'delete_property');
                formData.append('property_id', propertyId);

                // Create a direct form submission
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'dashboard.php';

                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'delete_property';

                const propertyInput = document.createElement('input');
                propertyInput.type = 'hidden';
                propertyInput.name = 'property_id';
                propertyInput.value = propertyId;

                form.appendChild(actionInput);
                form.appendChild(propertyInput);
                document.body.appendChild(form);

                // Close the confirmation modal first
                bsModal.hide();

                // Wait for modal to close, then show success notification
                setTimeout(() => {
                    const notification = document.createElement('div');
                    notification.className = 'alert alert-success position-fixed top-0 start-50 translate-middle-x mt-3';
                    notification.style.zIndex = '9999';
                    notification.style.backgroundColor = '#4caf50';
                    notification.style.color = '#ffffff';
                    notification.style.border = 'none';
                    notification.style.boxShadow = '0 2px 5px rgba(0,0,0,0.2)';
                    notification.style.minWidth = '300px';
                    notification.innerHTML = `
                        <div class="d-flex align-items-center">
                            <i class="fas fa-check-circle me-2"></i>
                            <span>Property deleted successfully!</span>
                            <button type="button" class="btn-close btn-close-white ms-3" onclick="this.parentElement.parentElement.remove()"></button>
                        </div>
                    `;
                    document.body.appendChild(notification);

                    // Remove notification after 3 seconds
                    setTimeout(() => {
                        notification.remove();
                        // Submit the form after notification is removed
                        form.submit();
                    }, 3000);
                }, 500); // Wait 500ms for modal animation to complete
            });

            // Clean up modal when hidden
            modal.addEventListener('hidden.bs.modal', function() {
                modal.remove();
            });
        });
    });

});
</script>
<script>
// Direct JavaScript functions for inquiry handling
function approveInquiry(inquiryId) {
    // Create modal for approval confirmation
    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.innerHTML = `
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Approve Inquiry</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to approve this inquiry?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success confirm-approve">Approve</button>
                </div>
            </div>
        </div>
    `;

    document.body.appendChild(modal);
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();

    modal.querySelector('.confirm-approve').addEventListener('click', function() {
        const formData = new FormData();
        formData.append('action', 'update_status');
        formData.append('inquiry_id', inquiryId);
        formData.append('status', 'approved');

        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';

        fetch('../api/inquiries.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            bsModal.hide();
            if (data.success) {
                const notification = document.createElement('div');
                notification.className = 'alert alert-success position-fixed text-center';
                notification.style.top = '20px';
                notification.style.left = '50%';
                notification.style.transform = 'translateX(-50%)';
                notification.style.zIndex = '9999';
                notification.style.minWidth = '300px';
                notification.style.backgroundColor = '#28a745';
                notification.style.color = '#ffffff';
                notification.style.border = 'none';
                notification.style.boxShadow = '0 2px 5px rgba(0,0,0,0.2)';
                notification.innerHTML = `
                    <div class="d-flex align-items-center justify-content-center">
                        <i class="fas fa-check-circle me-2"></i>
                        <span>Inquiry approved successfully!</span>
                        <button type="button" class="btn-close btn-close-white ms-3" onclick="this.parentElement.parentElement.remove()"></button>
                    </div>
                `;
                document.body.appendChild(notification);
                setTimeout(() => {
                    notification.remove();
                    window.location.reload();
                }, 2000);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            bsModal.hide();
            showErrorNotification('Failed to approve inquiry. Please try again.');
        });
    });

    modal.addEventListener('hidden.bs.modal', function() {
        modal.remove();
    });
}

function rejectInquiry(inquiryId) {
    // Create modal for rejection confirmation
    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.innerHTML = `
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title">Reject Inquiry</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to reject this inquiry?</p>
                    <p class="text-muted"><small>This action cannot be undone.</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-warning confirm-reject">Reject</button>
                </div>
            </div>
        </div>
    `;

    document.body.appendChild(modal);
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();

    modal.querySelector('.confirm-reject').addEventListener('click', function() {
        const formData = new FormData();
        formData.append('action', 'update_status');
        formData.append('inquiry_id', inquiryId);
        formData.append('status', 'rejected');

        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';

        fetch('../api/inquiries.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            bsModal.hide();
            if (data.success) {
                const notification = document.createElement('div');
                notification.className = 'alert alert-warning position-fixed text-center';
                notification.style.top = '20px';
                notification.style.left = '50%';
                notification.style.transform = 'translateX(-50%)';
                notification.style.zIndex = '9999';
                notification.style.minWidth = '300px';
                notification.style.backgroundColor = '#ffc107';
                notification.style.color = '#ffffff';
                notification.style.border = 'none';
                notification.style.boxShadow = '0 2px 5px rgba(0,0,0,0.2)';
                notification.innerHTML = `
                    <div class="d-flex align-items-center justify-content-center">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <span>Inquiry rejected successfully!</span>
                        <button type="button" class="btn-close btn-close-white ms-3" onclick="this.parentElement.parentElement.remove()"></button>
                    </div>
                `;
                document.body.appendChild(notification);
                setTimeout(() => {
                    notification.remove();
                    window.location.reload();
                }, 2000);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            bsModal.hide();
            showErrorNotification('Failed to reject inquiry. Please try again.');
        });
    });

    modal.addEventListener('hidden.bs.modal', function() {
        modal.remove();
    });
}

function showErrorNotification(message) {
    const notification = document.createElement('div');
    notification.className = 'notification-toast error';
    notification.innerHTML = `
        <div class="d-flex p-3 align-items-center bg-danger text-white">
            <i class="fas fa-exclamation-circle me-2"></i>
            <span>${message}</span>
            <button type="button" class="btn-close btn-close-white ms-auto" onclick="this.parentElement.parentElement.remove()"></button>
        </div>
    `;
    document.body.appendChild(notification);
    setTimeout(() => notification.remove(), 3000);
}

function deleteProperty(propertyId, propertyTitle) {
    const formData = new FormData();
    formData.append('action', 'delete_property');
    formData.append('property_id', propertyId);

    // Create and submit a form directly
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'dashboard.php';

    const propertyInput = document.createElement('input');
    propertyInput.type = 'hidden';
    propertyInput.name = 'property_id';
    propertyInput.value = propertyId;

    form.appendChild(actionInput);
    form.appendChild(propertyInput);
    document.body.appendChild(form);
    form.submit();
}

</script>

<?php include '../inc/footer.php'; ?>