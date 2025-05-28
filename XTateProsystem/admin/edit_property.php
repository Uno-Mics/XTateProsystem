<?php
require_once '../inc/db.php';
require_once '../inc/functions.php';
require_once '../inc/auth.php';

// Check if user is admin
checkPermission(['admin']);

// Handle image actions (delete and set primary)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['image_action'])) {
    $imageId = (int)($_POST['image_id'] ?? 0);
    $propertyId = (int)($_POST['property_id'] ?? 0);
    $action = $_POST['image_action'];

    if ($imageId > 0 && $propertyId > 0) {
        $conn = connectDB();
        $conn->begin_transaction();

        try {
            if ($action === 'delete') {
                // Get image path before deletion
                $sql = "SELECT image_path FROM property_images WHERE id = ? AND property_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ii", $imageId, $propertyId);
                $stmt->execute();
                $result = $stmt->get_result();
                $image = $result->fetch_assoc();

                if ($image) {
                    // Delete the physical file
                    $filePath = '..' . $image['image_path'];
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }

                    // Delete from database
                    $sql = "DELETE FROM property_images WHERE id = ? AND property_id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ii", $imageId, $propertyId);
                    $stmt->execute();

                    // If deleted image was primary, set another image as primary
                    $sql = "SELECT COUNT(*) as count FROM property_images WHERE property_id = ? AND is_primary = 1";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $propertyId);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $count = $result->fetch_assoc()['count'];

                    if ($count === 0) {
                        $sql = "UPDATE property_images SET is_primary = 1 WHERE property_id = ? LIMIT 1";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("i", $propertyId);
                        $stmt->execute();
                    }
                }
            } elseif ($action === 'set_primary') {
                // Remove primary flag from all images
                $sql = "UPDATE property_images SET is_primary = 0 WHERE property_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $propertyId);
                $stmt->execute();

                // Set new primary image
                $sql = "UPDATE property_images SET is_primary = 1 WHERE id = ? AND property_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ii", $imageId, $propertyId);
                $stmt->execute();
            }

            $conn->commit();
            echo json_encode(['success' => true, 'message' => ($action === 'delete') ? 'Image deleted successfully!' : 'Primary image set successfully!']);
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => ($action === 'delete') ? 'Failed to delete image.' : 'Failed to set primary image.']);
            exit;
        }

        $conn->close();
    }
}

// Check if property ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('properties.php');
}

$propertyId = (int)$_GET['id'];

// Get property details
$sql = "SELECT * FROM properties WHERE id = ?";
$property = fetchOne($sql, "i", [$propertyId]);

// If property not found, redirect to properties page
if (!$property) {
    redirect('properties.php');
}

// Get property images
$sql = "SELECT * FROM property_images WHERE property_id = ? ORDER BY is_primary DESC, id ASC";
$propertyImages = fetchAll($sql, "i", [$propertyId]);

// Get property types for dropdown
$propertyTypes = getPropertyTypes();

// Process form submission
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['image_action'])) {
    // Get form data
    $title = sanitizeInput($_POST['title'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $propertyTypeId = (int)($_POST['property_type_id'] ?? 0);
    $bedrooms = (int)($_POST['bedrooms'] ?? 0);
    $bathrooms = (int)($_POST['bathrooms'] ?? 0);
    $area = (float)($_POST['area'] ?? 0);
    $address = sanitizeInput($_POST['address'] ?? '');
    $city = sanitizeInput($_POST['city'] ?? '');
    $state = sanitizeInput($_POST['state'] ?? '');
    $zipCode = sanitizeInput($_POST['zip_code'] ?? '');
    $yearBuilt = (int)($_POST['year_built'] ?? 0);
    $status = sanitizeInput($_POST['status'] ?? 'active');

    // Amenities (checkboxes)
    $garage = isset($_POST['garage']) ? 1 : 0;
    $airConditioning = isset($_POST['air_conditioning']) ? 1 : 0;
    $swimmingPool = isset($_POST['swimming_pool']) ? 1 : 0;
    $backyard = isset($_POST['backyard']) ? 1 : 0;
    $gym = isset($_POST['gym']) ? 1 : 0;
    $fireplace = isset($_POST['fireplace']) ? 1 : 0;
    $securitySystem = isset($_POST['security_system']) ? 1 : 0;
    $washerDryer = isset($_POST['washer_dryer']) ? 1 : 0;

    // Validate required fields
    $requiredFields = [
        'title' => 'Property title',
        'description' => 'Description',
        'price' => 'Price',
        'property_type_id' => 'Property type',
        'bedrooms' => 'Bedrooms',
        'bathrooms' => 'Bathrooms',
        'area' => 'Area',
        'address' => 'Address',
        'city' => 'City',
        'state' => 'State',
        'zip_code' => 'Zip code'
    ];

    foreach ($requiredFields as $field => $label) {
        if (empty($_POST[$field])) {
            $errors[] = $label . ' is required';
        }
    }

    // If no errors, update property
    if (empty($errors)) {
        // Begin transaction
        $conn = connectDB();
        $conn->begin_transaction();

        try {
            // Update property
            $sql = "UPDATE properties SET 
                        title = ?, 
                        description = ?, 
                        price = ?, 
                        property_type_id = ?, 
                        bedrooms = ?, 
                        bathrooms = ?, 
                        area = ?, 
                        address = ?, 
                        city = ?, 
                        state = ?, 
                        zip_code = ?, 
                        year_built = ?, 
                        garage = ?, 
                        air_conditioning = ?, 
                        swimming_pool = ?, 
                        backyard = ?, 
                        gym = ?, 
                        fireplace = ?, 
                        security_system = ?, 
                        washer_dryer = ?, 
                        status = ?,
                        updated_at = NOW()
                    WHERE id = ?";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "ssdiiissssiiiiiiiiissi",
                $title,
                $description,
                $price,
                $propertyTypeId,
                $bedrooms,
                $bathrooms,
                $area,
                $address,
                $city,
                $state,
                $zipCode,
                $yearBuilt,
                $garage,
                $airConditioning,
                $swimmingPool,
                $backyard,
                $gym,
                $fireplace,
                $securitySystem,
                $washerDryer,
                $status,
                $propertyId
            );

            $stmt->execute();

            // Upload and save new images if provided
            if (!empty($_FILES['property_images']['name'][0])) {
                $uploadDir = '../uploads/properties/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                foreach ($_FILES['property_images']['tmp_name'] as $key => $tmp_name) {
                    if ($_FILES['property_images']['error'][$key] === UPLOAD_ERR_OK) {
                        $tempName = $_FILES['property_images']['tmp_name'][$key];
                        $originalName = $_FILES['property_images']['name'][$key];
                        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
                        $newFileName = $propertyId . '_' . uniqid() . '.' . $extension;
                        $targetPath = $uploadDir . $newFileName;

                        if (move_uploaded_file($tempName, $targetPath)) {
                            $isPrimary = (empty($propertyImages) && $key === 0) ? 1 : 0;
                            $sql = "INSERT INTO property_images (property_id, image_path, is_primary) VALUES (?, ?, ?)";
                            $stmt = $conn->prepare($sql);
                            $dbImagePath = '/uploads/properties/' . $newFileName;
                            $stmt->bind_param("isi", $propertyId, $dbImagePath, $isPrimary);
                            $stmt->execute();
                        }
                    }
                }
            }

            $conn->commit();
            $success = 'Property updated successfully!';

            // Refresh property data
            $property = fetchOne("SELECT * FROM properties WHERE id = ?", "i", [$propertyId]);
            $propertyImages = fetchAll("SELECT * FROM property_images WHERE property_id = ? ORDER BY is_primary DESC, id ASC", "i", [$propertyId]);

        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = 'Error updating property: ' . $e->getMessage();
        }

        $conn->close();
    }
}

include '../inc/header.php';
?>

<div class="container py-5">
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
            <div id="alertContainer"></div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?= $error ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <?= $success ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <form method="POST" action="<?= $_SERVER['PHP_SELF'] ?>?id=<?= $propertyId ?>" enctype="multipart/form-data">
                        <!-- Basic Information -->
                        <h4 class="mb-3">Basic Information</h4>
                        <div class="row mb-4">
                            <div class="col-md-12 mb-3">
                                <label for="title" class="form-label">Property Title <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="title" name="title" value="<?= $property['title'] ?>" required>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="description" name="description" rows="5" required><?= $property['description'] ?></textarea>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="price" class="form-label">Price ($) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="price" name="price" min="0" step="0.01" value="<?= $property['price'] ?>" required>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="property_type_id" class="form-label">Property Type <span class="text-danger">*</span></label>
                                <select class="form-select" id="property_type_id" name="property_type_id" required>
                                    <option value="">Select Property Type</option>
                                    <?php foreach ($propertyTypes as $type): ?>
                                        <option value="<?= $type['id'] ?>" <?= $property['property_type_id'] == $type['id'] ? 'selected' : '' ?>><?= $type['name'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="active" <?= $property['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                    <option value="inactive" <?= $property['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                    <option value="pending" <?= $property['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                </select>
                            </div>
                        </div>

                        <!-- Property Features -->
                        <h4 class="mb-3">Property Features</h4>
                        <div class="row mb-4">
                            <div class="col-md-3 mb-3">
                                <label for="bedrooms" class="form-label">Bedrooms <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="bedrooms" name="bedrooms" min="0" value="<?= $property['bedrooms'] ?>" required>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label for="bathrooms" class="form-label">Bathrooms <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="bathrooms" name="bathrooms" min="0" step="0.5" value="<?= $property['bathrooms'] ?>" required>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label for="area" class="form-label">Area (sqft) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="area" name="area" min="0" value="<?= $property['area'] ?>" required>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label for="year_built" class="form-label">Year Built</label>
                                <input type="number" class="form-control" id="year_built" name="year_built" min="1800" max="<?= date('Y') ?>" value="<?= $property['year_built'] ?>">
                            </div>

                            <div class="col-md-12 mb-3">
                                <label class="form-label">Amenities</label>
                                <div class="row">
                                    <div class="col-md-3 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="garage" name="garage" value="1" <?= $property['garage'] ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="garage">Garage</label>
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="air_conditioning" name="air_conditioning" value="1" <?= $property['air_conditioning'] ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="air_conditioning">Air Conditioning</label>
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="swimming_pool" name="swimming_pool" value="1" <?= $property['swimming_pool'] ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="swimming_pool">Swimming Pool</label>
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="backyard" name="backyard" value="1" <?= $property['backyard'] ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="backyard">Backyard</label>
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="gym" name="gym" value="1" <?= $property['gym'] ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="gym">Gym</label>
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="fireplace" name="fireplace" value="1" <?= $property['fireplace'] ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="fireplace">Fireplace</label>
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="security_system" name="security_system" value="1" <?= $property['security_system'] ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="security_system">Security System</label>
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="washer_dryer" name="washer_dryer" value="1" <?= $property['washer_dryer'] ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="washer_dryer">Washer/Dryer</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Location -->
                        <h4 class="mb-3">Location</h4>
                        <div class="row mb-4">
                            <div class="col-md-12 mb-3">
                                <label for="address" class="form-label">Address <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="address" name="address" value="<?= $property['address'] ?>" required>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="city" class="form-label">City <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="city" name="city" value="<?= $property['city'] ?>" required>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="state" class="form-label">Country <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="state" name="state" value="<?= $property['state'] ?>" required>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="zip_code" class="form-label">Zip Code <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="zip_code" name="zip_code" value="<?= $property['zip_code'] ?>" required>
                            </div>
                        </div>

                        <!-- Current Images -->
                        <h4 class="mb-3">Current Images</h4>
                        <div class="row mb-4">
                            <?php if (empty($propertyImages)): ?>
                                <div class="col-12">
                                    <div class="alert alert-info">No images uploaded yet.</div>
                                </div>
                            <?php else: ?>
                                <?php foreach ($propertyImages as $image): ?>
                                    <div class="col-md-3 mb-3">
                                        <div class="card">
                                            <?php
                                            $imagePath = $image['image_path'];
                                            // Handle both absolute URLs and relative paths
                                            if (substr($imagePath, 0, 4) !== 'http') {
                                                // Remove leading slash if present
                                                $imagePath = ltrim($imagePath, '/');
                                                // Add relative path prefix
                                                $imagePath = '../uploads/properties/' . basename($imagePath);
                                                
                                                // Check if file exists, use default if not
                                                $realPath = dirname(__DIR__) . '/uploads/properties/' . basename($imagePath);
                                                if (!file_exists($realPath)) {
                                                    $imagePath = '../uploads/properties/default.jpg';
                                                }
                                            }
                                            ?>
                                            <img src="<?= $imagePath ?>" class="card-img-top" alt="Property Image" style="height: 150px; object-fit: cover;">
                                            <div class="card-body p-2">
                                                <?php if ($image['is_primary']): ?>
                                                    <div class="badge bg-primary mb-2">Primary Image</div>
                                                <?php else: ?>
                                                    <button type="button" class="btn btn-sm btn-outline-primary mb-2 set-primary-btn" 
                                                            data-image-id="<?= $image['id'] ?>" 
                                                            data-property-id="<?= $propertyId ?>">
                                                        Set as Primary
                                                    </button>
                                                <?php endif; ?>

                                                <button type="button" class="btn btn-sm btn-outline-danger delete-image-btn" 
                                                        data-image-id="<?= $image['id'] ?>" 
                                                        data-property-id="<?= $propertyId ?>">
                                                    Delete
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <!-- Upload New Images -->
                        <h4 class="mb-3">Upload New Images</h4>
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <label for="property_images" class="form-label">Add More Images (Max 10 images, 5MB each)</label>
                                <input type="file" class="form-control" id="property_images" name="property_images[]" accept="image/jpeg,image/png,image/gif" multiple>
                                <div class="form-text">If no images exist, the first uploaded image will be set as the primary image.</div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="d-flex justify-content-between">
                            <a href="properties.php" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Update Property</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div class="modal fade" id="confirmationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary confirm-action"></button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = new bootstrap.Modal(document.getElementById('confirmationModal'));
    const modalTitle = document.querySelector('#confirmationModal .modal-title');
    const modalBody = document.querySelector('#confirmationModal .modal-body');
    const confirmButton = document.querySelector('#confirmationModal .confirm-action');

    // Function to show alert
    function showAlert(message, type) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.getElementById('alertContainer').appendChild(alertDiv);

        // Auto-dismiss after 3 seconds
        setTimeout(() => {
            alertDiv.remove();
        }, 3000);
    }

    // Function to handle image actions
    function handleImageAction(imageId, propertyId, action) {
        const formData = new FormData();
        formData.append('image_action', action);
        formData.append('image_id', imageId);
        formData.append('property_id', propertyId);

        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert(data.message, 'success');
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                showAlert(data.message, 'danger');
            }
        })
        .catch(error => {
            showAlert('An error occurred. Please try again.', 'danger');
        });
    }

    // Set Primary Image
    document.querySelectorAll('.set-primary-btn').forEach(button => {
        button.addEventListener('click', function() {
            const imageId = this.dataset.imageId;
            const propertyId = this.dataset.propertyId;

            modalTitle.textContent = 'Set Primary Image';
            modalBody.textContent = 'Are you sure you want to set this as the primary image?';
            confirmButton.className = 'btn btn-primary confirm-action';
            confirmButton.textContent = 'Set Primary';

            confirmButton.onclick = function() {
                modal.hide();
                handleImageAction(imageId, propertyId, 'set_primary');
            };

            modal.show();
        });
    });

    // Delete Image
    document.querySelectorAll('.delete-image-btn').forEach(button => {
        button.addEventListener('click', function() {
            const imageId = this.dataset.imageId;
            const propertyId = this.dataset.propertyId;

            modalTitle.textContent = 'Delete Image';
            modalBody.textContent = 'Are you sure you want to delete this image? This action cannot be undone.';
            confirmButton.className = 'btn btn-danger confirm-action';
            confirmButton.textContent = 'Delete';

            confirmButton.onclick = function() {
                modal.hide();
                handleImageAction(imageId, propertyId, 'delete');
            };

            modal.show();
        });
    });
});
</script>

<?php include '../inc/footer.php'; ?>