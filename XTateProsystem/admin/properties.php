
<?php
require_once '../inc/db.php';
require_once '../inc/functions.php';
require_once '../inc/auth.php';

// Check if user is admin
checkPermission(['admin']);

// Handle property deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_property') {
    $propertyId = isset($_POST['property_id']) ? intval($_POST['property_id']) : 0;

    if ($propertyId > 0) {
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
            $conn->query("DELETE FROM properties WHERE id = $propertyId");

            $conn->commit();
            $_SESSION['success_message'] = "Property deleted successfully!";
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error_message'] = "Failed to delete property. Please try again.";
        }
        closeDB($conn);
        header('Location: properties.php');
        exit();
    }
}

// Get all properties with seller information
$sql = "SELECT p.*, u.full_name as seller_name, u.email as seller_email,
        (SELECT image_path FROM property_images WHERE property_id = p.id AND is_primary = 1 LIMIT 1) as primary_image
        FROM properties p
        JOIN users u ON p.seller_id = u.id
        ORDER BY p.created_at DESC";
$properties = fetchAll($sql);

include '../inc/header.php';
?>

<div class="container py-5">
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <?= $_SESSION['success_message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?= $_SESSION['error_message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-3 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Admin Dashboard</h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="dashboard.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                    <a href="reports.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-flag me-2"></i> Reports
                    </a>
                    <a href="users.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-users me-2"></i> Users
                    </a>
                    <a href="properties.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-home me-2"></i> Properties
                    </a>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-lg-9">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Property Management</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Title</th>
                                    <th>Seller</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($properties as $property): ?>
                                <tr>
                                    <td>
                                        <?php
                                        $imagePath = $property['primary_image'];
                                        if (!empty($imagePath)) {
                                            if (substr($imagePath, 0, 4) !== 'http') {
                                                $imagePath = '../uploads/properties/' . basename($imagePath);
                                            }
                                        } else {
                                            $imagePath = '../uploads/properties/default.jpg';
                                        }
                                        ?>
                                        <img src="<?= htmlspecialchars($imagePath) ?>" 
                                             alt="Property" class="img-thumbnail" 
                                             style="width: 80px; height: 60px; object-fit: cover;">
                                    </td>
                                    <td>
                                        <a href="../property_details.php?id=<?= $property['id'] ?>" target="_blank">
                                            <?= htmlspecialchars($property['title']) ?>
                                        </a>
                                        <div class="small text-muted">
                                            <?= $property['bedrooms'] ?> beds • <?= $property['bathrooms'] ?> baths • <?= number_format($property['area']) ?> sqft
                                        </div>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($property['seller_name']) ?>
                                        <div class="small text-muted"><?= htmlspecialchars($property['seller_email']) ?></div>
                                    </td>
                                    <td><?= formatCurrency($property['price']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $property['status'] === 'active' ? 'success' : 'secondary' ?>">
                                            <?= ucfirst($property['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <a href="../property_details.php?id=<?= $property['id'] ?>" 
                                               class="btn btn-xs btn-primary px-2 py-1" target="_blank">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <a href="edit_property.php?id=<?= $property['id'] ?>" 
                                               class="btn btn-xs btn-warning px-2 py-1">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <button type="button" 
                                                    class="btn btn-xs btn-danger px-2 py-1 delete-property-btn"
                                                    data-property-id="<?= $property['id'] ?>"
                                                    data-property-title="<?= htmlspecialchars($property['title']) ?>">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deletePropertyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Property</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete "<span id="propertyTitle"></span>"?</p>
                <p class="text-danger"><small>This action cannot be undone.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="delete_property">
                    <input type="hidden" name="property_id" id="propertyIdInput">
                    <button type="submit" class="btn btn-danger">Delete Property</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const deleteButtons = document.querySelectorAll('.delete-property-btn');
    const deleteModal = new bootstrap.Modal(document.getElementById('deletePropertyModal'));

    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const propertyId = this.getAttribute('data-property-id');
            const propertyTitle = this.getAttribute('data-property-title');

            document.getElementById('propertyTitle').textContent = propertyTitle;
            document.getElementById('propertyIdInput').value = propertyId;

            deleteModal.show();
        });
    });
});
</script>

<?php include '../inc/footer.php'; ?>
