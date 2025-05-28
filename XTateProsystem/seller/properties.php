<?php
require_once '../inc/db.php';
require_once '../inc/functions.php';
require_once '../inc/auth.php';

// Check if user is logged in and has seller role
checkPermission(['seller']);

// Get seller data
$sellerId = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = ?";
$seller = fetchOne($sql, "i", [$sellerId]);

// Get properties for this seller
$sql = "SELECT p.*, 
        (SELECT image_path FROM property_images WHERE property_id = p.id AND is_primary = 1 LIMIT 1) as primary_image
        FROM properties p
        WHERE p.seller_id = ?
        ORDER BY p.created_at DESC";
$properties = fetchAll($sql, "i", [$sellerId]);

// Process property deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_property') {
    $propertyId = isset($_POST['property_id']) ? intval($_POST['property_id']) : 0;
    
    if ($propertyId > 0) {
        // Verify the property belongs to this seller
        $sql = "SELECT id FROM properties WHERE id = ? AND seller_id = ?";
        $property = fetchOne($sql, "ii", [$propertyId, $sellerId]);
        
        if ($property) {
            // Delete property images
            $sql = "DELETE FROM property_images WHERE property_id = ?";
            updateData($sql, "i", [$propertyId]);
            
            // Delete property
            $sql = "DELETE FROM properties WHERE id = ?";
            $result = updateData($sql, "i", [$propertyId]);
            
            if ($result) {
                header("Location: properties.php?success=1");
                exit;
            }
        }
    }
    
    header("Location: properties.php?error=1");
    exit;
}

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
                    <a href="dashboard.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                    <a href="properties.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-home me-2"></i> My Properties
                    </a>
                    <a href="inquiries.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-file-alt me-2"></i> Inquiries
                    </a>
                    <a href="messages.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-comments me-2"></i> Messages
                        <?php 
                        $unreadCount = getUnreadMessagesCount($sellerId);
                        if ($unreadCount > 0): 
                        ?>
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
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">My Properties</h5>
                    <a href="add_property.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus me-1"></i> Add New Property
                    </a>
                </div>
                <div class="card-body">
                    <?php if (isset($_GET['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle me-2"></i> Property deleted successfully!
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_GET['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-circle me-2"></i> An error occurred. Please try again.
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (empty($properties)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i> You haven't listed any properties yet. Click "Add New Property" to get started.
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($properties as $property): ?>
                                <div class="col-md-6 mb-4">
                                    <div class="card h-100">
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
                                        <div class="card-img-top" style="height: 200px; background-image: url('<?= $imagePath ?>'); background-size: cover; background-position: center;"></div>
                                        <div class="card-body">
                                            <h5 class="card-title"><?= $property['title'] ?></h5>
                                            <p class="card-text text-primary fw-bold"><?= formatCurrency($property['price']) ?></p>
                                            <p class="card-text">
                                                <i class="fas fa-map-marker-alt"></i> <?= $property['address'] ?>, <?= $property['city'] ?>, <?= $property['state'] ?>
                                            </p>
                                            <div class="d-flex mb-3">
                                                <div class="me-3"><i class="fas fa-bed me-1"></i> <?= $property['bedrooms'] ?> bd</div>
                                                <div class="me-3"><i class="fas fa-bath me-1"></i> <?= $property['bathrooms'] ?> ba</div>
                                                <div><i class="fas fa-ruler-combined me-1"></i> <?= $property['area'] ?> sqft</div>
                                            </div>
                                            
                                            <div class="d-flex justify-content-between mt-3">
                                                <a href="../property_details.php?id=<?= $property['id'] ?>" class="btn btn-outline-primary btn-sm">
                                                    <i class="fas fa-eye me-1"></i> View
                                                </a>
                                                <a href="edit_property.php?id=<?= $property['id'] ?>" class="btn btn-outline-secondary btn-sm">
                                                    <i class="fas fa-edit me-1"></i> Edit
                                                </a>
                                                <button class="btn btn-outline-danger btn-sm delete-property-btn" 
                                                        data-property-id="<?= $property['id'] ?>"
                                                        data-property-title="<?= htmlspecialchars($property['title'], ENT_QUOTES) ?>">
                                                    <i class="fas fa-trash-alt me-1"></i> Delete
                                                </button>
                                            </div>
                                            
                                            <?php
                                            // Get the status label
                                            $statusClass = '';
                                            $statusText = ucfirst($property['status']);
                                            
                                            switch ($property['status']) {
                                                case 'active':
                                                    $statusClass = 'bg-success';
                                                    break;
                                                case 'pending':
                                                    $statusClass = 'bg-warning';
                                                    break;
                                                case 'sold':
                                                    $statusClass = 'bg-danger';
                                                    break;
                                                default:
                                                    $statusClass = 'bg-secondary';
                                            }
                                            ?>
                                            
                                            <div class="position-absolute top-0 end-0 m-2">
                                                <span class="badge <?= $statusClass ?>"><?= $statusText ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const deleteButtons = document.querySelectorAll('.delete-property-btn');

    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const propertyId = this.getAttribute('data-property-id');
            const propertyTitle = this.getAttribute('data-property-title');

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
                const form = document.createElement('form');
                form.method = 'POST';
                
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

<?php include '../inc/footer.php'; ?>