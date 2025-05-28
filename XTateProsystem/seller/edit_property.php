<?php
require_once '../inc/db.php';
require_once '../inc/functions.php';
require_once '../inc/auth.php';

// Check if user is logged in and has seller role
checkPermission(['seller']);

// Get seller data
$sellerId = $_SESSION['user_id'];

// Check if property ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('dashboard.php');
}

$propertyId = (int)$_GET['id'];

// Get property details and check if it belongs to the seller
$sql = "SELECT * FROM properties WHERE id = ? AND seller_id = ?";
$property = fetchOne($sql, "ii", [$propertyId, $sellerId]);

// If property not found or doesn't belong to seller, redirect to dashboard
if (!$property) {
    redirect('dashboard.php');
}

// Get property images
$sql = "SELECT * FROM property_images WHERE property_id = ? ORDER BY is_primary DESC, id ASC";
$propertyImages = fetchAll($sql, "i", [$propertyId]);

// Get property types for dropdown
$propertyTypes = getPropertyTypes();

// Process form submission
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
    
    // Validate numeric fields
    if ($price <= 0) {
        $errors[] = 'Price must be greater than zero';
    }
    
    if ($area <= 0) {
        $errors[] = 'Area must be greater than zero';
    }
    
    if ($bedrooms < 0) {
        $errors[] = 'Bedrooms cannot be negative';
    }
    
    if ($bathrooms < 0) {
        $errors[] = 'Bathrooms cannot be negative';
    }
    
    // Validate property type
    if ($propertyTypeId <= 0) {
        $errors[] = 'Please select a property type';
    } else {
        $validType = false;
        foreach ($propertyTypes as $type) {
            if ($type['id'] == $propertyTypeId) {
                $validType = true;
                break;
            }
        }
        
        if (!$validType) {
            $errors[] = 'Invalid property type selected';
        }
    }
    
    // Validate status
    if (!in_array($status, ['active', 'inactive', 'pending'])) {
        $errors[] = 'Invalid status selected';
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
                    WHERE id = ? AND seller_id = ?";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "ssdiiissssiiiiiiiiissii",
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
                $propertyId,
                $sellerId
            );
            
            $stmt->execute();
            $stmt->close();
            
            // Upload and save new images if provided
            if (!empty($_FILES['property_images']['name'][0])) {
                $uploadDir = '../uploads/properties/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                // Count uploaded images
                $totalImages = count($_FILES['property_images']['name']);
                $uploadedImages = 0;
                
                // Check if there are existing images
                $hasPrimary = false;
                foreach ($propertyImages as $image) {
                    if ($image['is_primary'] == 1) {
                        $hasPrimary = true;
                        break;
                    }
                }
                
                for ($i = 0; $i < $totalImages; $i++) {
                    if ($_FILES['property_images']['error'][$i] === UPLOAD_ERR_OK) {
                        $tempName = $_FILES['property_images']['tmp_name'][$i];
                        $originalName = $_FILES['property_images']['name'][$i];
                        $fileSize = $_FILES['property_images']['size'][$i];
                        $fileType = $_FILES['property_images']['type'][$i];
                        
                        // Check if file is an image
                        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                        if (!in_array($fileType, $allowedTypes)) {
                            continue;
                        }
                        
                        // Generate unique filename
                        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
                        $newFileName = $propertyId . '_' . uniqid() . '.' . $extension;
                        $targetPath = $uploadDir . $newFileName;
                        
                        // Move uploaded file
                        if (move_uploaded_file($tempName, $targetPath)) {
                            // Format the path consistently for database storage
                            $filename = basename($targetPath);
                            $dbImagePath = '/uploads/properties/' . $filename;
                            
                            // Insert image record
                            $sql = "INSERT INTO property_images (property_id, image_path, is_primary, created_at) 
                                    VALUES (?, ?, ?, NOW())";
                            
                            // Set as primary if no primary image exists and this is the first upload
                            $isPrimary = (!$hasPrimary && $uploadedImages === 0) ? 1 : 0;
                            
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("isi", $propertyId, $dbImagePath, $isPrimary);
                            $stmt->execute();
                            $stmt->close();
                            
                            $uploadedImages++;
                            
                            // Update primary flag status
                            if ($isPrimary == 1) {
                                $hasPrimary = true;
                            }
                        }
                    }
                }
            }
            
            // Commit transaction
            $conn->commit();
            
            $success = 'Property updated successfully!';
            
            // Refresh property data
            $property = fetchOne("SELECT * FROM properties WHERE id = ?", "i", [$propertyId]);
            
            // Refresh property images
            $propertyImages = fetchAll("SELECT * FROM property_images WHERE property_id = ? ORDER BY is_primary DESC, id ASC", "i", [$propertyId]);
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $errors[] = 'Error updating property: ' . $e->getMessage();
        }
        
        // Close connection
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
                    <h5 class="card-title mb-0">Seller Dashboard</h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="dashboard.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                    <a href="add_property.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-plus-circle me-2"></i> Add Property
                    </a>
                    <a href="inquiries.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-envelope me-2"></i> Inquiries
                        <?php
                        // Get pending inquiries count
                        $sql = "SELECT COUNT(*) as count FROM inquiries i 
                               JOIN properties p ON i.property_id = p.id 
                               WHERE p.seller_id = ? AND i.status = 'pending'";
                        $pendingCount = fetchOne($sql, "i", [$sellerId])['count'];
                        if ($pendingCount > 0):
                        ?>
                            <span class="badge bg-primary rounded-pill ms-1"><?= $pendingCount ?></span>
                        <?php endif; ?>
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
                    <a href="#" class="list-group-item list-group-item-action" data-bs-toggle="modal" data-bs-target="#profileModal">
                        <i class="fas fa-user-edit me-2"></i> Edit Profile
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-lg-9">
            <div class="dashboard-header mb-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="dashboard-title">Edit Property</h2>
                        <p class="dashboard-subtitle">Update your property listing information</p>
                    </div>
                    <div>
                        <a href="../property_details.php?id=<?= $propertyId ?>" class="btn btn-outline-primary me-2" target="_blank">
                            <i class="fas fa-eye me-2"></i> View Property
                        </a>
                        <a href="dashboard.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>
            
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
                    <form id="propertyForm" method="POST" action="<?= $_SERVER['PHP_SELF'] ?>?id=<?= $propertyId ?>" enctype="multipart/form-data">
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
                                            // Handle image path correctly
                                            $imagePath = $image['image_path'];
                                            // Strip leading slash if present for better compatibility
                                            if (substr($imagePath, 0, 1) === '/') {
                                                $imagePath = ltrim($imagePath, '/');
                                            }
                                            // If not an absolute URL, make it relative to the root
                                            if (substr($imagePath, 0, 4) !== 'http') {
                                                $imagePath = '../' . $imagePath;
                                            }
                                            ?>
                                            <img src="<?= $imagePath ?>" class="card-img-top" alt="Property Image" style="height: 150px; object-fit: cover;">
                                            <div class="card-body p-2">
                                                <?php if ($image['is_primary']): ?>
                                                    <div class="badge bg-primary mb-2 primary-badge">Primary Image</div>
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
                            <div class="col-md-12 mb-3">
                                <label for="property_images" class="form-label">Add More Images (Max 10 images, 5MB each)</label>
                                <input type="file" class="form-control" id="property_images" name="property_images[]" accept="image/jpeg, image/png, image/gif" multiple>
                                <div class="form-text">If no images exist, the first uploaded image will be set as the primary image.</div>
                            </div>
                            
                            <div class="col-md-12">
                                <div class="row" id="imagePreviewContainer"></div>
                            </div>
                        </div>
                        
                        <!-- Submit Button -->
                        <div class="d-flex justify-content-between">
                            <a href="dashboard.php" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Update Property</button>
                        </div>
                    </form>
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
                        <input type="text" class="form-control" id="full_name" name="full_name" value="<?= $_SESSION['user_name'] ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?= $_SESSION['user_email'] ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" id="phone" name="phone" value="" required>
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
<script src="../js/property.js"></script>

<?php include '../inc/footer.php'; ?>
