<?php
require_once '../inc/db.php';
require_once '../inc/functions.php';
require_once '../inc/auth.php';

// Check if user is logged in and has seller role
checkPermission(['seller']);

// Get seller data
$sellerId = $_SESSION['user_id'];

// Get property types for dropdown
$propertyTypes = getPropertyTypes();

// Process form submission
$errors = [];
$success = '';
$formData = [
    'title' => '',
    'description' => '',
    'price' => '',
    'property_type_id' => '',
    'bedrooms' => '',
    'bathrooms' => '',
    'area' => '',
    'address' => '',
    'city' => '',
    'state' => '',
    'zip_code' => '',
    'year_built' => '',
    'garage' => 0,
    'air_conditioning' => 0,
    'swimming_pool' => 0,
    'backyard' => 0,
    'gym' => 0,
    'fireplace' => 0,
    'security_system' => 0,
    'washer_dryer' => 0
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $formData = [
        'title' => sanitizeInput($_POST['title'] ?? ''),
        'description' => sanitizeInput($_POST['description'] ?? ''),
        'price' => (float)($_POST['price'] ?? 0),
        'property_type_id' => (int)($_POST['property_type_id'] ?? 0),
        'bedrooms' => (int)($_POST['bedrooms'] ?? 0),
        'bathrooms' => (int)($_POST['bathrooms'] ?? 0),
        'area' => (float)($_POST['area'] ?? 0),
        'address' => sanitizeInput($_POST['address'] ?? ''),
        'city' => sanitizeInput($_POST['city'] ?? ''),
        'state' => sanitizeInput($_POST['state'] ?? ''),
        'zip_code' => sanitizeInput($_POST['zip_code'] ?? ''),
        'year_built' => (int)($_POST['year_built'] ?? 0),
        'garage' => isset($_POST['garage']) ? 1 : 0,
        'air_conditioning' => isset($_POST['air_conditioning']) ? 1 : 0,
        'swimming_pool' => isset($_POST['swimming_pool']) ? 1 : 0,
        'backyard' => isset($_POST['backyard']) ? 1 : 0,
        'gym' => isset($_POST['gym']) ? 1 : 0,
        'fireplace' => isset($_POST['fireplace']) ? 1 : 0,
        'security_system' => isset($_POST['security_system']) ? 1 : 0,
        'washer_dryer' => isset($_POST['washer_dryer']) ? 1 : 0
    ];
    
    // Validate required fields
    $requiredFields = ['title', 'description', 'price', 'property_type_id', 'bedrooms', 'bathrooms', 'area', 'address', 'city', 'state', 'zip_code'];
    foreach ($requiredFields as $field) {
        if (empty($formData[$field])) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
        }
    }
    
    // Validate numeric fields
    if ($formData['price'] <= 0) {
        $errors[] = 'Price must be greater than zero';
    }
    
    if ($formData['area'] <= 0) {
        $errors[] = 'Area must be greater than zero';
    }
    
    if ($formData['bedrooms'] < 0) {
        $errors[] = 'Bedrooms cannot be negative';
    }
    
    if ($formData['bathrooms'] < 0) {
        $errors[] = 'Bathrooms cannot be negative';
    }
    
    // Validate property type
    if ($formData['property_type_id'] <= 0) {
        $errors[] = 'Please select a property type';
    } else {
        $validType = false;
        foreach ($propertyTypes as $type) {
            if ($type['id'] == $formData['property_type_id']) {
                $validType = true;
                break;
            }
        }
        
        if (!$validType) {
            $errors[] = 'Invalid property type selected';
        }
    }
    
    // Check if images are uploaded
    if (empty($_FILES['property_images']['name'][0])) {
        $errors[] = 'At least one property image is required';
    }
    
    // If no errors, insert property
    if (empty($errors)) {
        // Begin transaction
        $conn = connectDB();
        $conn->begin_transaction();
        
        try {
            // Insert property
            // Use direct insertion instead of prepared statement to avoid parameter binding issues
            $sql = "INSERT INTO properties (
                        seller_id, title, description, price, property_type_id, 
                        bedrooms, bathrooms, area, address, city, state, zip_code, 
                        year_built, garage, air_conditioning, swimming_pool, 
                        backyard, gym, fireplace, security_system, washer_dryer, 
                        status, created_at
                    ) VALUES (
                        {$sellerId}, 
                        '{$conn->real_escape_string($formData['title'])}', 
                        '{$conn->real_escape_string($formData['description'])}', 
                        {$formData['price']}, 
                        {$formData['property_type_id']}, 
                        {$formData['bedrooms']}, 
                        {$formData['bathrooms']}, 
                        {$formData['area']}, 
                        '{$conn->real_escape_string($formData['address'])}', 
                        '{$conn->real_escape_string($formData['city'])}', 
                        '{$conn->real_escape_string($formData['state'])}', 
                        '{$conn->real_escape_string($formData['zip_code'])}', 
                        {$formData['year_built']}, 
                        {$formData['garage']}, 
                        {$formData['air_conditioning']}, 
                        {$formData['swimming_pool']}, 
                        {$formData['backyard']}, 
                        {$formData['gym']}, 
                        {$formData['fireplace']}, 
                        {$formData['security_system']}, 
                        {$formData['washer_dryer']}, 
                        'active', NOW()
                    )";
            
            $result = $conn->query($sql);
            
            if (!$result) {
                throw new Exception('Error inserting property: ' . $conn->error);
            }
            
            $propertyId = $conn->insert_id;
            
            // Upload and save images
            $uploadDir = '../uploads/properties/';
            
            // Make sure the directory exists with proper permissions
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Check if directory was created successfully
            if (!is_dir($uploadDir) || !is_writable($uploadDir)) {
                // Try an alternative directory if creation failed
                $uploadDir = 'uploads/properties/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                // If still not available, create in the current directory
                if (!is_dir($uploadDir) || !is_writable($uploadDir)) {
                    $uploadDir = './uploads/';
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                }
            }
            
            // Count uploaded images
            $totalImages = count($_FILES['property_images']['name']);
            $uploadedImages = 0;
            
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
                        // Store the web-accessible path in the database
                        // Extract just the filename
                        $filename = basename($targetPath);
                        
                        // Always store image paths consistently as /uploads/properties/filename
                        // This makes it easier to reference them from any page
                        $dbImagePath = '/uploads/properties/' . $filename;
                        
                        // Copy file to the root uploads directory for accessibility
                        // This ensures images are available no matter where they are accessed from
                        if (!is_dir('../uploads/properties')) {
                            mkdir('../uploads/properties', 0777, true);
                        }
                        
                        // Also make a copy to our Replit project root directory for web access
                        copy($targetPath, '../uploads/properties/' . $filename);
                        
                        // Insert image record
                        $sql = "INSERT INTO property_images (property_id, image_path, is_primary, created_at) 
                                VALUES (?, ?, ?, NOW())";
                        
                        $isPrimary = ($uploadedImages === 0) ? 1 : 0; // First image is primary
                        
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("isi", $propertyId, $dbImagePath, $isPrimary);
                        $stmt->execute();
                        $stmt->close();
                        
                        $uploadedImages++;
                    }
                }
            }
            
            // If no images were uploaded, rollback transaction
            if ($uploadedImages === 0) {
                throw new Exception('Failed to upload property images');
            }
            
            // Commit transaction
            $conn->commit();
            
            $success = 'Property added successfully!';
            
            // Clear form data
            $formData = [
                'title' => '',
                'description' => '',
                'price' => '',
                'property_type_id' => '',
                'bedrooms' => '',
                'bathrooms' => '',
                'area' => '',
                'address' => '',
                'city' => '',
                'state' => '',
                'zip_code' => '',
                'year_built' => '',
                'garage' => 0,
                'air_conditioning' => 0,
                'swimming_pool' => 0,
                'backyard' => 0,
                'gym' => 0,
                'fireplace' => 0,
                'security_system' => 0,
                'washer_dryer' => 0
            ];
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $errors[] = 'Error adding property: ' . $e->getMessage();
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
                    <a href="add_property.php" class="list-group-item list-group-item-action active">
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
                <h2 class="dashboard-title">Add New Property</h2>
                <p class="dashboard-subtitle">Fill in the details to list your property</p>
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
                    <p class="mb-0 mt-2">
                        <a href="dashboard.php" class="btn btn-sm btn-primary">Back to Dashboard</a>
                        <a href="add_property.php" class="btn btn-sm btn-outline-primary">Add Another Property</a>
                    </p>
                </div>
            <?php endif; ?>
            
            <?php if (empty($success)): ?>
                <div class="card">
                    <div class="card-body">
                        <form id="propertyForm" method="POST" action="<?= $_SERVER['PHP_SELF'] ?>" enctype="multipart/form-data">
                            <!-- Basic Information -->
                            <h4 class="mb-3">Basic Information</h4>
                            <div class="row mb-4">
                                <div class="col-md-12 mb-3">
                                    <label for="title" class="form-label">Property Title <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="title" name="title" value="<?= $formData['title'] ?>" required>
                                </div>
                                
                                <div class="col-md-12 mb-3">
                                    <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="description" name="description" rows="5" required><?= $formData['description'] ?></textarea>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="price" class="form-label">Price ($) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="price" name="price" min="0" step="0.01" value="<?= $formData['price'] ?>" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="property_type_id" class="form-label">Property Type <span class="text-danger">*</span></label>
                                    <select class="form-select" id="property_type_id" name="property_type_id" required>
                                        <option value="">Select Property Type</option>
                                        <?php foreach ($propertyTypes as $type): ?>
                                            <option value="<?= $type['id'] ?>" <?= $formData['property_type_id'] == $type['id'] ? 'selected' : '' ?>><?= $type['name'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Property Features -->
                            <h4 class="mb-3">Property Features</h4>
                            <div class="row mb-4">
                                <div class="col-md-3 mb-3">
                                    <label for="bedrooms" class="form-label">Bedrooms <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="bedrooms" name="bedrooms" min="0" value="<?= $formData['bedrooms'] ?>" required>
                                </div>
                                
                                <div class="col-md-3 mb-3">
                                    <label for="bathrooms" class="form-label">Bathrooms <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="bathrooms" name="bathrooms" min="0" step="0.5" value="<?= $formData['bathrooms'] ?>" required>
                                </div>
                                
                                <div class="col-md-3 mb-3">
                                    <label for="area" class="form-label">Area (sqft) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="area" name="area" min="0" value="<?= $formData['area'] ?>" required>
                                </div>
                                
                                <div class="col-md-3 mb-3">
                                    <label for="year_built" class="form-label">Year Built</label>
                                    <input type="number" class="form-control" id="year_built" name="year_built" min="1800" max="<?= date('Y') ?>" value="<?= $formData['year_built'] ?>">
                                </div>
                                
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Amenities</label>
                                    <div class="row">
                                        <div class="col-md-3 mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="garage" name="garage" value="1" <?= $formData['garage'] ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="garage">Garage</label>
                                            </div>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="air_conditioning" name="air_conditioning" value="1" <?= $formData['air_conditioning'] ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="air_conditioning">Air Conditioning</label>
                                            </div>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="swimming_pool" name="swimming_pool" value="1" <?= $formData['swimming_pool'] ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="swimming_pool">Swimming Pool</label>
                                            </div>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="backyard" name="backyard" value="1" <?= $formData['backyard'] ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="backyard">Backyard</label>
                                            </div>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="gym" name="gym" value="1" <?= $formData['gym'] ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="gym">Gym</label>
                                            </div>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="fireplace" name="fireplace" value="1" <?= $formData['fireplace'] ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="fireplace">Fireplace</label>
                                            </div>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="security_system" name="security_system" value="1" <?= $formData['security_system'] ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="security_system">Security System</label>
                                            </div>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="washer_dryer" name="washer_dryer" value="1" <?= $formData['washer_dryer'] ? 'checked' : '' ?>>
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
                                    <input type="text" class="form-control" id="address" name="address" value="<?= $formData['address'] ?>" required>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="city" class="form-label">City <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="city" name="city" value="<?= $formData['city'] ?>" required>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="state" class="form-label">Country <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="state" name="state" value="<?= $formData['state'] ?>" required>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="zip_code" class="form-label">Zip Code <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="zip_code" name="zip_code" value="<?= $formData['zip_code'] ?>" required>
                                </div>
                            </div>
                            
                            <!-- Images -->
                            <h4 class="mb-3">Property Images <span class="text-danger">*</span></h4>
                            <div class="row mb-4">
                                <div class="col-md-12 mb-3">
                                    <label for="property_images" class="form-label">Upload Images (Max 10 images, 5MB each)</label>
                                    <input type="file" class="form-control" id="property_images" name="property_images[]" accept="image/jpeg, image/png, image/gif" multiple required>
                                    <div class="form-text">First uploaded image will be set as the primary image.</div>
                                </div>
                                
                                <div class="col-md-12">
                                    <div class="row" id="imagePreviewContainer"></div>
                                </div>
                            </div>
                            
                            <!-- Submit Button -->
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">Add Property</button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
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
