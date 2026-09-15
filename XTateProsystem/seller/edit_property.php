<?php
require_once '../inc/db.php';
require_once '../inc/functions.php';
require_once '../inc/auth.php';

// Check if user is logged in and has seller role
checkPermission(['seller']);

// Get seller data
$sellerId = $_SESSION['user_id'];
$sellerName = $_SESSION['user_name'] ?? 'Seller';

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

// Badges count
$pendingCount = getPendingInquiriesCount($sellerId);
$unreadCount = getUnreadMessagesCount($sellerId);

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

            // Sync updated property to Cloud Firestore
            if (function_exists('firestore_sync_property')) {
                firestore_sync_property($propertyId);
            }
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

<div class="db-wrap">
    <div class="db-container">
        <div class="db-layout">

            <!-- Sidebar -->
            <aside class="db-sidebar">
                <!-- Profile Card -->
                <div class="db-card db-profile-card">
                    <div class="db-avatar"><?= strtoupper(substr($sellerName, 0, 1)) ?></div>
                    <div class="db-user-name"><?= htmlspecialchars($sellerName) ?></div>
                    <span class="db-user-badge">
                        <i data-lucide="badge-check" style="width:13px;height:13px;"></i> Verified Seller
                    </span>
                </div>

                <!-- Navigation -->
                <div class="db-card db-nav-group">
                    <a href="dashboard.php" class="db-nav-link">
                        <i data-lucide="layout-dashboard"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="properties.php" class="db-nav-link active">
                        <i data-lucide="home"></i>
                        <span>My Properties</span>
                    </a>
                    <a href="add_property.php" class="db-nav-link">
                        <i data-lucide="plus-circle"></i>
                        <span>Add Property</span>
                    </a>
                    <a href="inquiries.php" class="db-nav-link">
                        <i data-lucide="inbox"></i>
                        <span>Inquiries</span>
                        <?php if ($pendingCount > 0): ?>
                            <span class="db-nav-badge"><?= $pendingCount ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="messages.php" class="db-nav-link">
                        <i data-lucide="message-square"></i>
                        <span>Messages</span>
                        <?php if ($unreadCount > 0): ?>
                            <span class="db-nav-badge db-nav-badge--danger"><?= $unreadCount ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="profile.php" class="db-nav-link">
                        <i data-lucide="user-cog"></i>
                        <span>Edit Profile</span>
                    </a>
                </div>
            </aside>

            <!-- Main Workspace -->
            <main class="db-main">

                <!-- Header Banner -->
                <div class="db-hero-banner">
                    <div>
                        <h1 class="db-hero-title">Edit Property</h1>
                        <p class="db-hero-desc">Update listing information, specifications, and gallery photos</p>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <a href="../property_details.php?id=<?= $propertyId ?>" target="_blank" class="btn-db-action btn-db-action--secondary">
                            <i data-lucide="external-link" style="width:16px;height:16px;"></i>
                            <span>View Live</span>
                        </a>
                        <a href="properties.php" class="btn-db-action btn-db-action--secondary">
                            <i data-lucide="arrow-left" style="width:16px;height:16px;"></i>
                            <span>All Properties</span>
                        </a>
                    </div>
                </div>

                <!-- Alert Messages -->
                <?php if (!empty($errors)): ?>
                    <div class="modern-alert modern-alert--danger">
                        <i data-lucide="alert-circle" style="width:20px;height:20px;flex-shrink:0;"></i>
                        <div class="modern-alert__content">
                            <div class="fw-bold mb-1">Please correct the following errors:</div>
                            <ul class="mb-0 ps-3">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="modern-alert modern-alert--success">
                        <i data-lucide="check-circle-2" style="width:20px;height:20px;flex-shrink:0;"></i>
                        <div class="modern-alert__content">
                            <strong>Success!</strong> <?= htmlspecialchars($success) ?>
                        </div>
                    </div>
                <?php endif; ?>

                <form id="propertyForm" method="POST" action="<?= $_SERVER['PHP_SELF'] ?>?id=<?= $propertyId ?>" enctype="multipart/form-data">

                    <!-- Panel 1: Basic Information -->
                    <section class="form-panel">
                        <div class="form-panel__header">
                            <div class="form-panel__icon">
                                <i data-lucide="file-text"></i>
                            </div>
                            <div>
                                <h2 class="form-panel__title">Basic Information</h2>
                                <p class="form-panel__sub">Core title, listing status, pricing and description</p>
                            </div>
                        </div>
                        <div class="form-panel__body">
                            <div class="form-grid form-grid--2">
                                <div class="form-group form-grid--full">
                                    <label for="title" class="form-label">Property Title <span class="req">*</span></label>
                                    <input type="text" class="modern-input" id="title" name="title" value="<?= htmlspecialchars($property['title']) ?>" placeholder="e.g. Modern Minimalist Villa with Ocean View" required>
                                </div>

                                <div class="form-group">
                                    <label for="property_type_id" class="form-label">Property Type <span class="req">*</span></label>
                                    <div class="select-wrapper">
                                        <select class="modern-select" id="property_type_id" name="property_type_id" required>
                                            <option value="">Select property type</option>
                                            <?php foreach ($propertyTypes as $type): ?>
                                                <option value="<?= $type['id'] ?>" <?= $property['property_type_id'] == $type['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($type['name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="status" class="form-label">Listing Status <span class="req">*</span></label>
                                    <div class="select-wrapper">
                                        <select class="modern-select" id="status" name="status" required>
                                            <option value="active" <?= $property['status'] === 'active' ? 'selected' : '' ?>>Active (Publicly Listed)</option>
                                            <option value="pending" <?= $property['status'] === 'pending' ? 'selected' : '' ?>>Pending (Under Review)</option>
                                            <option value="inactive" <?= $property['status'] === 'inactive' ? 'selected' : '' ?>>Inactive (Hidden)</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group form-grid--full">
                                    <label for="price" class="form-label">Price (USD) <span class="req">*</span></label>
                                    <div class="price-input-wrap">
                                        <span class="price-symbol">$</span>
                                        <input type="number" class="modern-input modern-input--price" id="price" name="price" min="0" step="0.01" value="<?= htmlspecialchars($property['price']) ?>" placeholder="0.00" required>
                                    </div>
                                </div>

                                <div class="form-group form-grid--full">
                                    <label for="description" class="form-label">Property Description <span class="req">*</span></label>
                                    <?php 
                                    $formDesc = $property['description'] ?? '';
                                    while (strpos($formDesc, '&amp;') !== false || strpos($formDesc, '&#') !== false) {
                                        $formDesc = html_entity_decode($formDesc, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                                    }
                                    ?>
                                    <textarea class="modern-textarea" id="description" name="description" rows="5" placeholder="Highlight key selling points, neighborhood atmosphere, views, finishes..." required><?= htmlspecialchars($formDesc, ENT_QUOTES, 'UTF-8') ?></textarea>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Panel 2: Property Specifications -->
                    <section class="form-panel">
                        <div class="form-panel__header">
                            <div class="form-panel__icon">
                                <i data-lucide="sliders"></i>
                            </div>
                            <div>
                                <h2 class="form-panel__title">Property Specifications</h2>
                                <p class="form-panel__sub">Rooms, total square footage, and build date</p>
                            </div>
                        </div>
                        <div class="form-panel__body">
                            <div class="form-grid form-grid--4">
                                <div class="form-group">
                                    <label for="bedrooms" class="form-label">Bedrooms <span class="req">*</span></label>
                                    <div class="input-with-icon">
                                        <i data-lucide="bed-double" class="field-icon"></i>
                                        <input type="number" class="modern-input has-icon" id="bedrooms" name="bedrooms" min="0" value="<?= htmlspecialchars($property['bedrooms']) ?>" placeholder="0" required>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="bathrooms" class="form-label">Bathrooms <span class="req">*</span></label>
                                    <div class="input-with-icon">
                                        <i data-lucide="bath" class="field-icon"></i>
                                        <input type="number" class="modern-input has-icon" id="bathrooms" name="bathrooms" min="0" step="0.5" value="<?= htmlspecialchars($property['bathrooms']) ?>" placeholder="0" required>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="area" class="form-label">Area (sqft) <span class="req">*</span></label>
                                    <div class="input-with-icon">
                                        <i data-lucide="maximize-2" class="field-icon"></i>
                                        <input type="number" step="any" class="modern-input has-icon" id="area" name="area" min="0" value="<?= htmlspecialchars($property['area']) ?>" placeholder="0" required>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="year_built" class="form-label">Year Built</label>
                                    <div class="input-with-icon">
                                        <i data-lucide="calendar" class="field-icon"></i>
                                        <input type="number" class="modern-input has-icon" id="year_built" name="year_built" min="1800" max="<?= date('Y') ?>" value="<?= htmlspecialchars($property['year_built'] ?: '') ?>" placeholder="e.g. 2021">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Panel 3: Location Details -->
                    <section class="form-panel">
                        <div class="form-panel__header">
                            <div class="form-panel__icon">
                                <i data-lucide="map-pin"></i>
                            </div>
                            <div>
                                <h2 class="form-panel__title">Location Details</h2>
                                <p class="form-panel__sub">Exact street address and geographic markers</p>
                            </div>
                        </div>
                        <div class="form-panel__body">
                            <div class="form-grid form-grid--3">
                                <div class="form-group form-grid--full">
                                    <label for="address" class="form-label">Street Address <span class="req">*</span></label>
                                    <input type="text" class="modern-input" id="address" name="address" value="<?= htmlspecialchars($property['address']) ?>" placeholder="e.g. 742 Evergreen Terrace, Suite 400" required>
                                </div>

                                <div class="form-group">
                                    <label for="city" class="form-label">City <span class="req">*</span></label>
                                    <input type="text" class="modern-input" id="city" name="city" value="<?= htmlspecialchars($property['city']) ?>" placeholder="e.g. Springfield" required>
                                </div>

                                <div class="form-group">
                                    <label for="state" class="form-label">State / Country <span class="req">*</span></label>
                                    <input type="text" class="modern-input" id="state" name="state" value="<?= htmlspecialchars($property['state']) ?>" placeholder="e.g. Oregon / US" required>
                                </div>

                                <div class="form-group">
                                    <label for="zip_code" class="form-label">Zip / Postal Code <span class="req">*</span></label>
                                    <input type="text" class="modern-input" id="zip_code" name="zip_code" value="<?= htmlspecialchars($property['zip_code']) ?>" placeholder="e.g. 97477" required>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Panel 4: Amenities & Features -->
                    <section class="form-panel">
                        <div class="form-panel__header">
                            <div class="form-panel__icon">
                                <i data-lucide="sparkles"></i>
                            </div>
                            <div>
                                <h2 class="form-panel__title">Amenities & Highlights</h2>
                                <p class="form-panel__sub">Select all features included with this property</p>
                            </div>
                        </div>
                        <div class="form-panel__body">
                            <div class="amenities-grid">
                                <label class="amenity-pill <?= $property['garage'] ? 'is-checked' : '' ?>">
                                    <input type="checkbox" id="garage" name="garage" value="1" <?= $property['garage'] ? 'checked' : '' ?>>
                                    <span class="amenity-pill__box">
                                        <i data-lucide="warehouse"></i>
                                        <span>Garage</span>
                                    </span>
                                </label>

                                <label class="amenity-pill <?= $property['air_conditioning'] ? 'is-checked' : '' ?>">
                                    <input type="checkbox" id="air_conditioning" name="air_conditioning" value="1" <?= $property['air_conditioning'] ? 'checked' : '' ?>>
                                    <span class="amenity-pill__box">
                                        <i data-lucide="wind"></i>
                                        <span>Air Conditioning</span>
                                    </span>
                                </label>

                                <label class="amenity-pill <?= $property['swimming_pool'] ? 'is-checked' : '' ?>">
                                    <input type="checkbox" id="swimming_pool" name="swimming_pool" value="1" <?= $property['swimming_pool'] ? 'checked' : '' ?>>
                                    <span class="amenity-pill__box">
                                        <i data-lucide="waves"></i>
                                        <span>Swimming Pool</span>
                                    </span>
                                </label>

                                <label class="amenity-pill <?= $property['backyard'] ? 'is-checked' : '' ?>">
                                    <input type="checkbox" id="backyard" name="backyard" value="1" <?= $property['backyard'] ? 'checked' : '' ?>>
                                    <span class="amenity-pill__box">
                                        <i data-lucide="trees"></i>
                                        <span>Backyard Garden</span>
                                    </span>
                                </label>

                                <label class="amenity-pill <?= $property['gym'] ? 'is-checked' : '' ?>">
                                    <input type="checkbox" id="gym" name="gym" value="1" <?= $property['gym'] ? 'checked' : '' ?>>
                                    <span class="amenity-pill__box">
                                        <i data-lucide="dumbbell"></i>
                                        <span>Fitness Gym</span>
                                    </span>
                                </label>

                                <label class="amenity-pill <?= $property['fireplace'] ? 'is-checked' : '' ?>">
                                    <input type="checkbox" id="fireplace" name="fireplace" value="1" <?= $property['fireplace'] ? 'checked' : '' ?>>
                                    <span class="amenity-pill__box">
                                        <i data-lucide="flame"></i>
                                        <span>Fireplace</span>
                                    </span>
                                </label>

                                <label class="amenity-pill <?= $property['security_system'] ? 'is-checked' : '' ?>">
                                    <input type="checkbox" id="security_system" name="security_system" value="1" <?= $property['security_system'] ? 'checked' : '' ?>>
                                    <span class="amenity-pill__box">
                                        <i data-lucide="shield-check"></i>
                                        <span>Security System</span>
                                    </span>
                                </label>

                                <label class="amenity-pill <?= $property['washer_dryer'] ? 'is-checked' : '' ?>">
                                    <input type="checkbox" id="washer_dryer" name="washer_dryer" value="1" <?= $property['washer_dryer'] ? 'checked' : '' ?>>
                                    <span class="amenity-pill__box">
                                        <i data-lucide="refresh-cw"></i>
                                        <span>Washer / Dryer</span>
                                    </span>
                                </label>
                            </div>
                        </div>
                    </section>

                    <!-- Panel 5: Manage Gallery Images -->
                    <section class="form-panel">
                        <div class="form-panel__header">
                            <div class="form-panel__icon">
                                <i data-lucide="image"></i>
                            </div>
                            <div>
                                <h2 class="form-panel__title">Property Gallery</h2>
                                <p class="form-panel__sub">Manage current listing photos or upload new high-resolution images</p>
                            </div>
                        </div>
                        <div class="form-panel__body">
                            <!-- Current Images -->
                            <h3 class="gallery-subheading">Current Photos (<?= count($propertyImages) ?>)</h3>
                            <?php if (empty($propertyImages)): ?>
                                <div class="gallery-empty-state">
                                    <i data-lucide="image-off" style="width:36px;height:36px;color:#94A3B8;"></i>
                                    <p>No photos uploaded for this property yet.</p>
                                </div>
                            <?php else: ?>
                                <div class="row g-3 mb-4">
                                    <?php foreach ($propertyImages as $image): 
                                        $imagePath = $image['image_path'];
                                        if (substr($imagePath, 0, 1) === '/') {
                                            $imagePath = ltrim($imagePath, '/');
                                        }
                                        if (substr($imagePath, 0, 4) !== 'http') {
                                            $imagePath = '../' . $imagePath;
                                        }
                                    ?>
                                        <div class="col-6 col-md-3">
                                            <div class="gallery-card <?= $image['is_primary'] ? 'is-primary-card' : '' ?>">
                                                <div class="gallery-thumb-wrap">
                                                    <img src="<?= htmlspecialchars($imagePath) ?>" alt="Property Image" class="gallery-thumb">
                                                    <?php if ($image['is_primary']): ?>
                                                        <span class="primary-badge gallery-badge-pill">
                                                            <i data-lucide="star" style="width:11px;height:11px;"></i> Primary
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="gallery-card-actions">
                                                    <?php if (!$image['is_primary']): ?>
                                                        <button type="button" class="btn-gallery-action set-primary-btn" 
                                                                data-image-id="<?= $image['id'] ?>" 
                                                                data-property-id="<?= $propertyId ?>">
                                                            <i data-lucide="star" style="width:12px;height:12px;"></i> Set Primary
                                                        </button>
                                                    <?php endif; ?>
                                                    <button type="button" class="btn-gallery-action btn-gallery-action--danger delete-image-btn" 
                                                            data-image-id="<?= $image['id'] ?>" 
                                                            data-property-id="<?= $propertyId ?>">
                                                        <i data-lucide="trash-2" style="width:12px;height:12px;"></i> Delete
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <!-- Upload New Images -->
                            <h3 class="gallery-subheading">Upload More Photos</h3>
                            <div class="upload-dropzone" id="dropzoneContainer" onclick="document.getElementById('property_images').click();">
                                <input type="file" id="property_images" name="property_images[]" accept="image/jpeg, image/png, image/gif" multiple style="display: none;">
                                <div class="upload-dropzone__icon">
                                    <i data-lucide="upload-cloud"></i>
                                </div>
                                <div class="upload-dropzone__title">Click to upload new photos</div>
                                <div class="upload-dropzone__sub">PNG, JPG or GIF up to 5MB each (Max 10 images)</div>
                            </div>

                            <div class="row g-3 mt-3" id="imagePreviewContainer"></div>
                        </div>
                    </section>

                    <!-- Sticky Bottom Bar -->
                    <div class="form-actions-bar">
                        <a href="properties.php" class="btn-db-action btn-db-action--secondary">
                            Cancel
                        </a>
                        <button type="submit" class="btn-db-action btn-db-action--primary">
                            <i data-lucide="check" style="width:18px;height:18px;"></i>
                            <span>Update Property</span>
                        </button>
                    </div>

                </form>

            </main>
        </div>
    </div>
</div>

<style>
/* ── Design Tokens & Base Layout ── */
.db-wrap {
    background: #F8FAFC;
    min-height: 100vh;
    padding: 30px 0 70px;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    color: #0F172A;
    overflow-x: hidden;
}

.db-container {
    max-width: 1240px;
    margin: 0 auto;
    padding: 0 16px;
}

.db-layout {
    display: grid;
    grid-template-columns: 260px minmax(0, 1fr);
    gap: 24px;
    align-items: start;
}

@media (max-width: 991px) {
    .db-layout {
        grid-template-columns: 1fr;
    }
}

/* ── Sidebar ── */
.db-sidebar {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.db-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.05);
}

.db-profile-card {
    padding: 22px;
    text-align: center;
    background: linear-gradient(180deg, #FFFFFF 0%, #F8FAFC 100%);
}

.db-avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    margin: 0 auto 12px;
    background: linear-gradient(135deg, #2563EB, #7C3AED);
    color: #fff;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 1.4rem;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 14px rgba(37, 99, 235, 0.25);
}

.db-user-name {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 1.05rem;
    font-weight: 700;
    color: #0F172A;
    margin: 0 0 4px;
}

.db-user-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #059669;
    background: #ECFDF5;
    padding: 3px 10px;
    border-radius: 100px;
}

/* Nav links */
.db-nav-group {
    padding: 10px;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.db-nav-link {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 14px;
    border-radius: 10px;
    color: #64748B;
    font-size: 0.88rem;
    font-weight: 500;
    text-decoration: none;
    transition: all .15s ease;
}

.db-nav-link i, .db-nav-link svg {
    width: 18px;
    height: 18px;
    flex-shrink: 0;
}

.db-nav-link:hover {
    background: #F1F5F9;
    color: #0F172A;
}

.db-nav-link.active {
    background: #EFF6FF;
    color: #2563EB;
    font-weight: 600;
}

.db-nav-badge {
    margin-left: auto;
    font-size: 0.7rem;
    font-weight: 700;
    background: #EFF6FF;
    color: #2563EB;
    padding: 2px 8px;
    border-radius: 100px;
}
.db-nav-badge--danger {
    background: #FEE2E2;
    color: #DC2626;
}

/* ── Main Workspace ── */
.db-main {
    display: flex;
    flex-direction: column;
    gap: 20px;
    min-width: 0;
}

/* Header Banner */
.db-hero-banner {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    padding: 24px 28px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.05);
}

.db-hero-title {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 1.45rem;
    font-weight: 800;
    color: #0F172A;
    margin: 0 0 4px;
    letter-spacing: -0.02em;
}

.db-hero-desc {
    color: #64748B;
    font-size: 0.88rem;
    margin: 0;
}

.btn-db-action {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 9px 18px;
    border-radius: 10px;
    font-size: 0.88rem;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    border: none;
    transition: all .15s ease;
}

.btn-db-action--primary {
    background: #2563EB;
    color: #FFFFFF;
    box-shadow: 0 2px 8px rgba(37, 99, 235, 0.25);
}
.btn-db-action--primary:hover {
    background: #1D4ED8;
    color: #FFFFFF;
    transform: translateY(-1px);
}

.btn-db-action--secondary {
    background: #F1F5F9;
    color: #334155;
}
.btn-db-action--secondary:hover {
    background: #E2E8F0;
    color: #0F172A;
}

/* Modern Alerts */
.modern-alert {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 16px 20px;
    border-radius: 12px;
    font-size: 0.88rem;
}
.modern-alert--danger {
    background: #FEF2F2;
    border: 1px solid #FCA5A5;
    color: #991B1B;
}
.modern-alert--success {
    background: #F0FDF4;
    border: 1px solid #86EFAC;
    color: #166534;
}

/* ── Form Panels (Bento) ── */
.form-panel {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.05);
    margin-bottom: 20px;
}

.form-panel__header {
    padding: 20px 24px;
    border-bottom: 1px solid #F1F5F9;
    display: flex;
    align-items: center;
    gap: 14px;
    background: #FFFFFF;
}

.form-panel__icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: #EFF6FF;
    color: #2563EB;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.form-panel__icon svg, .form-panel__icon i {
    width: 20px;
    height: 20px;
}

.form-panel__title {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 1.05rem;
    font-weight: 700;
    color: #0F172A;
    margin: 0 0 2px;
}

.form-panel__sub {
    font-size: 0.82rem;
    color: #64748B;
    margin: 0;
}

.form-panel__body {
    padding: 24px;
}

/* Form Grids */
.form-grid {
    display: grid;
    gap: 18px;
}
.form-grid--2 {
    grid-template-columns: repeat(2, 1fr);
}
.form-grid--3 {
    grid-template-columns: repeat(3, 1fr);
}
.form-grid--4 {
    grid-template-columns: repeat(4, 1fr);
}
.form-grid--full {
    grid-column: 1 / -1;
}

@media (max-width: 768px) {
    .form-grid--2, .form-grid--3, .form-grid--4 {
        grid-template-columns: 1fr;
    }
}

/* Inputs */
.form-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.form-label {
    font-size: 0.82rem;
    font-weight: 600;
    color: #334155;
    margin: 0;
}

.req {
    color: #EF4444;
}

.modern-input, .modern-select, .modern-textarea {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid #CBD5E1;
    border-radius: 10px;
    font-size: 0.88rem;
    color: #0F172A;
    background: #FFFFFF;
    outline: none;
    transition: all .15s ease;
    font-family: inherit;
}

.modern-input:focus, .modern-select:focus, .modern-textarea:focus {
    border-color: #2563EB;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
}

.price-input-wrap {
    position: relative;
    display: flex;
    align-items: center;
}

.price-symbol {
    position: absolute;
    left: 14px;
    color: #64748B;
    font-weight: 600;
    font-size: 0.95rem;
    pointer-events: none;
}

.modern-input--price {
    padding-left: 28px;
    font-weight: 600;
    font-size: 1rem;
    color: #0F172A;
}

.input-with-icon {
    position: relative;
    display: flex;
    align-items: center;
}

.field-icon {
    position: absolute;
    left: 12px;
    width: 16px;
    height: 16px;
    color: #94A3B8;
    pointer-events: none;
}

.modern-input.has-icon {
    padding-left: 36px;
}

/* Amenities Grid */
.amenities-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 12px;
}

.amenity-pill {
    cursor: pointer;
    margin: 0;
}

.amenity-pill input {
    display: none;
}

.amenity-pill__box {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 14px;
    border: 1.5px solid #E2E8F0;
    border-radius: 10px;
    background: #F8FAFC;
    color: #475569;
    font-size: 0.85rem;
    font-weight: 500;
    transition: all .15s ease;
}

.amenity-pill__box svg, .amenity-pill__box i {
    width: 17px;
    height: 17px;
    color: #64748B;
    flex-shrink: 0;
    transition: color .15s ease;
}

.amenity-pill:hover .amenity-pill__box {
    background: #F1F5F9;
    border-color: #CBD5E1;
    color: #0F172A;
}

.amenity-pill input:checked + .amenity-pill__box,
.amenity-pill.is-checked .amenity-pill__box {
    background: #EFF6FF;
    border-color: #2563EB;
    color: #1D4ED8;
    font-weight: 600;
}

.amenity-pill input:checked + .amenity-pill__box svg,
.amenity-pill.is-checked .amenity-pill__box svg {
    color: #2563EB;
}

/* Gallery Management */
.gallery-subheading {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 0.95rem;
    font-weight: 700;
    color: #334155;
    margin: 0 0 12px;
}

.gallery-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    overflow: hidden;
    transition: all .2s ease;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
}
.gallery-card:hover {
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08);
}
.gallery-card.is-primary-card {
    border-color: #2563EB;
    box-shadow: 0 0 0 1px #2563EB, 0 4px 12px rgba(37, 99, 235, 0.1);
}

.gallery-thumb-wrap {
    position: relative;
    width: 100%;
    height: 130px;
    overflow: hidden;
    background: #F1F5F9;
}

.gallery-thumb {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform .3s ease;
}
.gallery-card:hover .gallery-thumb {
    transform: scale(1.03);
}

.gallery-badge-pill {
    position: absolute;
    top: 8px;
    left: 8px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 0.7rem;
    font-weight: 700;
    padding: 3px 8px;
    border-radius: 100px;
    background: #2563EB;
    color: #FFFFFF;
    box-shadow: 0 2px 6px rgba(37, 99, 235, 0.3);
}

.gallery-card-actions {
    padding: 8px;
    display: flex;
    flex-direction: column;
    gap: 6px;
    background: #FFFFFF;
}

.btn-gallery-action {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 6px 10px;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
    border: 1px solid #E2E8F0;
    background: #F8FAFC;
    color: #334155;
    cursor: pointer;
    transition: all .15s ease;
}
.btn-gallery-action:hover {
    background: #EFF6FF;
    border-color: #BFDBFE;
    color: #2563EB;
}

.btn-gallery-action--danger {
    color: #DC2626;
    background: #FFF5F5;
    border-color: #FEE2E2;
}
.btn-gallery-action--danger:hover {
    background: #FEE2E2;
    border-color: #FCA5A5;
    color: #B91C1C;
}

.gallery-empty-state {
    padding: 30px;
    text-align: center;
    background: #F8FAFC;
    border-radius: 12px;
    border: 1px dashed #CBD5E1;
    color: #64748B;
    font-size: 0.88rem;
    margin-bottom: 20px;
}

/* Upload Dropzone */
.upload-dropzone {
    border: 2px dashed #CBD5E1;
    border-radius: 14px;
    padding: 36px 20px;
    text-align: center;
    background: #F8FAFC;
    cursor: pointer;
    transition: all .2s ease;
}

.upload-dropzone:hover {
    border-color: #2563EB;
    background: #EFF6FF;
}

.upload-dropzone__icon {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: #E2E8F0;
    color: #475569;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 12px;
    transition: all .2s ease;
}

.upload-dropzone:hover .upload-dropzone__icon {
    background: #2563EB;
    color: #FFFFFF;
}

.upload-dropzone__icon svg, .upload-dropzone__icon i {
    width: 24px;
    height: 24px;
}

.upload-dropzone__title {
    font-weight: 700;
    font-size: 0.95rem;
    color: #0F172A;
    margin-bottom: 4px;
}

.upload-dropzone__sub {
    font-size: 0.8rem;
    color: #64748B;
}

/* Form Actions Bottom Bar */
.form-actions-bar {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 12px;
    padding: 16px 24px;
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.05);
}
</style>

<script src="../js/property.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
    
    // Checkbox styling sync
    document.querySelectorAll('.amenity-pill input').forEach(function(input) {
        input.addEventListener('change', function() {
            var pill = this.closest('.amenity-pill');
            if (this.checked) {
                pill.classList.add('is-checked');
            } else {
                pill.classList.remove('is-checked');
            }
        });
    });
});
</script>

<?php include '../inc/footer.php'; ?>
