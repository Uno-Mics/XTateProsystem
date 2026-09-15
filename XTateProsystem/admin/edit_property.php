<?php
require_once '../inc/db.php';
require_once '../inc/functions.php';
require_once '../inc/auth.php';

// Check if user is admin
checkPermission(['admin']);

$adminId = $_SESSION['user_id'];
$adminName = $_SESSION['user_name'] ?? 'Admin';

// Handle image actions (delete and set primary) via AJAX
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

            if (function_exists('firestore_sync_property')) {
                firestore_sync_property($propertyId);
            }

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

// Pending reports count for sidebar badge
$sql = "SELECT COUNT(*) as count FROM reports WHERE status = 'pending'";
$pendingReportsResult = fetchOne($sql);
$pendingReports = $pendingReportsResult ? $pendingReportsResult['count'] : 0;

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

    if ($price <= 0) {
        $errors[] = 'Price must be greater than zero';
    }

    if ($area <= 0) {
        $errors[] = 'Area must be greater than zero';
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
                        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                        if (in_array($extension, $allowed)) {
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
            }

            $conn->commit();
            $success = 'Property listing updated successfully!';

            // Refresh property data
            $property = fetchOne("SELECT * FROM properties WHERE id = ?", "i", [$propertyId]);
            $propertyImages = fetchAll("SELECT * FROM property_images WHERE property_id = ? ORDER BY is_primary DESC, id ASC", "i", [$propertyId]);

            // Sync updated property to Cloud Firestore
            if (function_exists('firestore_sync_property')) {
                firestore_sync_property($propertyId);
            }

        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = 'Error updating property: ' . $e->getMessage();
        }

        $conn->close();
    }
}

include '../inc/header.php';
?>

<div class="db-wrap">
    <div class="db-container">
        <div class="db-layout">

            <!-- ══════════ LEFT: ADMIN SIDEBAR ══════════ -->
            <aside class="db-sidebar">
                <div class="db-card db-profile-card">
                    <div class="db-avatar">
                        <?= strtoupper(substr($adminName, 0, 1)) ?>
                    </div>
                    <h3 class="db-user-name"><?= htmlspecialchars($adminName) ?></h3>
                    <span class="db-user-badge">
                        <i data-lucide="shield-check" style="width:13px;height:13px;"></i> Super Admin
                    </span>
                </div>

                <div class="db-card">
                    <nav class="db-nav-group">
                        <a href="dashboard.php" class="db-nav-link">
                            <i data-lucide="layout-dashboard" style="width:17px;height:17px;"></i>
                            <span>Dashboard</span>
                        </a>
                        <a href="reports.php" class="db-nav-link">
                            <i data-lucide="flag" style="width:17px;height:17px;"></i>
                            <span>Reports</span>
                            <?php if ($pendingReports > 0): ?>
                                <span class="db-nav-badge"><?= $pendingReports ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="contacts.php" class="db-nav-link">
                            <i data-lucide="mail" style="width:17px;height:17px;"></i>
                            <span>Contacts</span>
                        </a>
                        <a href="messages.php" class="db-nav-link">
                            <i data-lucide="message-square" style="width:17px;height:17px;"></i>
                            <span>Messages</span>
                        </a>
                        <a href="users.php" class="db-nav-link">
                            <i data-lucide="users" style="width:17px;height:17px;"></i>
                            <span>Users</span>
                        </a>
                        <a href="properties.php" class="db-nav-link active">
                            <i data-lucide="home" style="width:17px;height:17px;"></i>
                            <span>Properties</span>
                        </a>
                    </nav>
                </div>
            </aside>

            <!-- ══════════ RIGHT: MAIN WORKSPACE ══════════ -->
            <main class="db-main">

                <!-- Header Banner -->
                <div class="db-hero-banner">
                    <div>
                        <h1 class="db-hero-title">Edit Property Listing</h1>
                        <p class="db-hero-sub">Update listing information, specifications, pricing, and photo gallery</p>
                    </div>
                    <div class="db-hero-actions">
                        <a href="../property_details.php?id=<?= $propertyId ?>" target="_blank" class="db-btn db-btn-secondary">
                            <i data-lucide="external-link" style="width:15px;height:15px;"></i>
                            <span>View Live</span>
                        </a>
                        <a href="properties.php" class="db-btn db-btn-secondary">
                            <i data-lucide="arrow-left" style="width:15px;height:15px;"></i>
                            <span>All Properties</span>
                        </a>
                    </div>
                </div>

                <div id="alertContainer"></div>

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
                                    $adminFormDesc = $property['description'] ?? '';
                                    while (strpos($adminFormDesc, '&amp;') !== false || strpos($adminFormDesc, '&#') !== false) {
                                        $adminFormDesc = html_entity_decode($adminFormDesc, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                                    }
                                    ?>
                                    <textarea class="modern-textarea" id="description" name="description" rows="5" placeholder="Highlight key selling points, neighborhood atmosphere, views, finishes..." required><?= htmlspecialchars($adminFormDesc, ENT_QUOTES, 'UTF-8') ?></textarea>
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
                                <p class="form-panel__sub">Rooms, total square footage, and construction year</p>
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
                                        <input type="number" class="modern-input has-icon" id="year_built" name="year_built" min="1800" max="<?= date('Y') ?>" value="<?= htmlspecialchars($property['year_built'] ?: '') ?>" placeholder="e.g. 2023">
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
                                    <input type="text" class="modern-input" id="state" name="state" value="<?= htmlspecialchars($property['state']) ?>" placeholder="e.g. Cavite / Philippines" required>
                                </div>

                                <div class="form-group">
                                    <label for="zip_code" class="form-label">Zip / Postal Code <span class="req">*</span></label>
                                    <input type="text" class="modern-input" id="zip_code" name="zip_code" value="<?= htmlspecialchars($property['zip_code']) ?>" placeholder="e.g. 4100" required>
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
                                                    <button type="button" class="btn-gallery-action btn-gallery-action--delete delete-image-btn" 
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

                            <!-- Upload New Images Dropzone -->
                            <h3 class="gallery-subheading mt-4">Upload New Images</h3>
                            <div class="dropzone-box" id="dropzoneBox">
                                <input type="file" class="dropzone-input" id="property_images" name="property_images[]" accept="image/jpeg,image/png,image/webp,image/gif" multiple>
                                <div class="dropzone-content">
                                    <div class="dropzone-icon">
                                        <i data-lucide="upload-cloud"></i>
                                    </div>
                                    <div class="dropzone-text">
                                        <span class="dropzone-primary-text">Click to upload</span> or drag and drop photos
                                    </div>
                                    <div class="dropzone-hint">PNG, JPG, WEBP up to 5MB each (Max 10 files)</div>
                                </div>
                            </div>
                            <div id="filePreviewList" class="file-preview-grid mt-3"></div>
                        </div>
                    </section>

                    <!-- Form Action Bar -->
                    <div class="form-actions-card">
                        <a href="properties.php" class="btn-action-cancel">
                            <span>Cancel</span>
                        </a>
                        <button type="submit" class="btn-action-submit">
                            <i data-lucide="save" style="width:17px;height:17px;"></i>
                            <span>Save Listing Changes</span>
                        </button>
                    </div>

                </form>

            </main>
        </div>
    </div>
</div>

<!-- Modern Confirmation Modal -->
<div class="modal fade" id="confirmationModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modern-modal">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-3"></div>
            <div class="modal-footer">
                <button type="button" class="btn-modal-cancel" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn-modal-primary confirm-action"></button>
            </div>
        </div>
    </div>
</div>

<style>
/* ── Layout & Typography ── */
.db-wrap {
    background-color: #F8FAFC;
    min-height: calc(100vh - 70px);
    padding: 32px 0 64px;
    font-family: 'Inter', sans-serif;
    color: #334155;
    overflow-x: hidden;
}

.db-container {
    max-width: 1240px;
    margin: 0 auto;
    padding: 0 16px;
    width: 100%;
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
    background: linear-gradient(135deg, #6366F1, #2563EB);
    color: #fff;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 1.4rem;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 14px rgba(99, 102, 241, 0.25);
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
    color: #2563EB;
    background: #EFF6FF;
    padding: 3px 10px;
    border-radius: 100px;
}

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
    background: #FEE2E2;
    color: #DC2626;
    padding: 2px 8px;
    border-radius: 100px;
}

/* ── Main Workspace ── */
.db-main {
    display: flex;
    flex-direction: column;
    gap: 20px;
    min-width: 0;
}

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

.btn-top-action {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.82rem;
    font-weight: 600;
    color: #475569;
    background: #F8FAFC;
    border: 1px solid #E2E8F0;
    padding: 7px 14px;
    border-radius: 8px;
    text-decoration: none;
    transition: all .15s ease;
}
.btn-top-action:hover {
    background: #F1F5F9;
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
.modern-alert--success {
    background: #F0FDF4;
    border: 1px solid #86EFAC;
    color: #166534;
}
.modern-alert--danger {
    background: #FEF2F2;
    border: 1px solid #FECACA;
    color: #991B1B;
}

/* Form Panels */
.form-panel {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    overflow: hidden;
    margin-bottom: 20px;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.05);
}

.form-panel__header {
    padding: 20px 24px;
    border-bottom: 1px solid #F1F5F9;
    display: flex;
    align-items: center;
    gap: 14px;
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
.form-panel__icon svg {
    width: 20px;
    height: 20px;
}

.form-panel__title {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 1.05rem;
    font-weight: 700;
    color: #0F172A;
    margin: 0;
}

.form-panel__sub {
    font-size: 0.8rem;
    color: #64748B;
    margin: 2px 0 0;
}

.form-panel__body {
    padding: 24px;
}

/* Form Grids */
.form-grid {
    display: grid;
    gap: 16px 20px;
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

.form-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.form-label {
    font-size: 0.8rem;
    font-weight: 600;
    color: #334155;
    margin: 0;
}
.req {
    color: #DC2626;
}

.modern-input, .modern-textarea, .modern-select {
    width: 100%;
    padding: 10px 14px;
    border-radius: 10px;
    border: 1px solid #E2E8F0;
    font-size: 0.88rem;
    color: #0F172A;
    background: #FFFFFF;
    transition: all .2s ease;
}

.modern-input:focus, .modern-textarea:focus, .modern-select:focus {
    outline: none;
    border-color: #2563EB;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.price-input-wrap {
    position: relative;
    display: flex;
    align-items: center;
}
.price-symbol {
    position: absolute;
    left: 14px;
    font-weight: 700;
    color: #64748B;
    pointer-events: none;
}
.modern-input--price {
    padding-left: 30px;
    font-weight: 600;
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
    user-select: none;
    margin: 0;
}
.amenity-pill input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.amenity-pill__box {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 14px;
    border-radius: 10px;
    border: 1px solid #E2E8F0;
    background: #F8FAFC;
    color: #475569;
    font-size: 0.82rem;
    font-weight: 500;
    transition: all .15s ease;
}
.amenity-pill__box svg {
    width: 16px;
    height: 16px;
    color: #64748B;
    transition: all .15s ease;
}

.amenity-pill:hover .amenity-pill__box {
    background: #F1F5F9;
    border-color: #CBD5E1;
}

.amenity-pill.is-checked .amenity-pill__box,
.amenity-pill input:checked + .amenity-pill__box {
    background: #EFF6FF;
    border-color: #93C5FD;
    color: #1D4ED8;
    font-weight: 600;
}
.amenity-pill.is-checked .amenity-pill__box svg,
.amenity-pill input:checked + .amenity-pill__box svg {
    color: #2563EB;
}

/* Gallery styling */
.gallery-subheading {
    font-size: 0.85rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #64748B;
    margin-bottom: 12px;
}

.gallery-empty-state {
    padding: 30px;
    text-align: center;
    background: #F8FAFC;
    border-radius: 12px;
    border: 1px dashed #CBD5E1;
}

.gallery-card {
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    overflow: hidden;
    background: #FFFFFF;
    transition: all .15s ease;
}
.gallery-card.is-primary-card {
    border-color: #2563EB;
    box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.2);
}

.gallery-thumb-wrap {
    position: relative;
    height: 130px;
    background: #F1F5F9;
}
.gallery-thumb {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.primary-badge {
    position: absolute;
    top: 8px;
    left: 8px;
    background: #2563EB;
    color: #FFFFFF;
    font-size: 0.7rem;
    font-weight: 700;
    padding: 3px 8px;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.gallery-card-actions {
    padding: 8px;
    display: flex;
    gap: 6px;
    background: #FFFFFF;
}

.btn-gallery-action {
    flex: 1;
    font-size: 0.72rem;
    font-weight: 600;
    padding: 5px 8px;
    border-radius: 6px;
    border: 1px solid #E2E8F0;
    background: #F8FAFC;
    color: #334155;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
}
.btn-gallery-action:hover {
    background: #EFF6FF;
    border-color: #93C5FD;
    color: #2563EB;
}
.btn-gallery-action--delete:hover {
    background: #FEF2F2;
    border-color: #FECACA;
    color: #DC2626;
}

/* Dropzone */
.dropzone-box {
    position: relative;
    border: 2px dashed #CBD5E1;
    border-radius: 12px;
    padding: 36px 20px;
    text-align: center;
    background: #F8FAFC;
    cursor: pointer;
    transition: all .2s ease;
}
.dropzone-box:hover, .dropzone-box.dragover {
    border-color: #2563EB;
    background: #EFF6FF;
}

.dropzone-input {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    cursor: pointer;
}

.dropzone-icon {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: #EFF6FF;
    color: #2563EB;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 12px;
}
.dropzone-icon svg {
    width: 24px;
    height: 24px;
}

.dropzone-text {
    font-size: 0.88rem;
    color: #475569;
    margin-bottom: 4px;
}
.dropzone-primary-text {
    color: #2563EB;
    font-weight: 600;
}
.dropzone-hint {
    font-size: 0.75rem;
    color: #94A3B8;
}

/* File preview grid */
.file-preview-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
    gap: 10px;
}
.file-preview-thumb {
    width: 100%;
    height: 80px;
    object-fit: cover;
    border-radius: 8px;
    border: 1px solid #E2E8F0;
}

/* Action Bar */
.form-actions-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    padding: 18px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.05);
}

.btn-action-cancel {
    font-size: 0.85rem;
    font-weight: 600;
    color: #64748B;
    background: #F1F5F9;
    padding: 10px 20px;
    border-radius: 10px;
    text-decoration: none;
    transition: all .15s ease;
}
.btn-action-cancel:hover {
    background: #E2E8F0;
    color: #0F172A;
}

.btn-action-submit {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 0.88rem;
    font-weight: 600;
    color: #FFFFFF;
    background: #2563EB;
    border: none;
    padding: 10px 24px;
    border-radius: 10px;
    cursor: pointer;
    transition: all .2s ease;
    box-shadow: 0 2px 8px rgba(37, 99, 235, 0.25);
}
.btn-action-submit:hover {
    background: #1D4ED8;
}

/* Modal */
.modern-modal {
    border-radius: 16px;
    border: 1px solid #E2E8F0;
    box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04);
}
.btn-modal-cancel {
    background: #F1F5F9;
    color: #475569;
    font-weight: 600;
    font-size: 0.85rem;
    padding: 8px 16px;
    border-radius: 8px;
    border: none;
}
.btn-modal-primary {
    background: #2563EB;
    color: #FFFFFF;
    font-weight: 600;
    font-size: 0.85rem;
    padding: 8px 18px;
    border-radius: 8px;
    border: none;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    // Amenity checkbox pill toggle
    document.querySelectorAll('.amenity-pill input').forEach(input => {
        input.addEventListener('change', function() {
            const pill = this.closest('.amenity-pill');
            if (this.checked) {
                pill.classList.add('is-checked');
            } else {
                pill.classList.remove('is-checked');
            }
        });
    });

    // Image preview on file selection
    const fileInput = document.getElementById('property_images');
    const previewContainer = document.getElementById('filePreviewList');
    if (fileInput && previewContainer) {
        fileInput.addEventListener('change', function() {
            previewContainer.innerHTML = '';
            const files = Array.from(this.files);
            files.forEach(file => {
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.className = 'file-preview-thumb';
                        previewContainer.appendChild(img);
                    };
                    reader.readAsDataURL(file);
                }
            });
        });
    }

    // Modal Confirmation for Image Actions
    const modalEl = document.getElementById('confirmationModal');
    const modal = modalEl ? new bootstrap.Modal(modalEl) : null;
    const modalTitle = document.querySelector('#confirmationModal .modal-title');
    const modalBody = document.querySelector('#confirmationModal .modal-body');
    const confirmButton = document.querySelector('#confirmationModal .confirm-action');

    function showAlert(message, type) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `modern-alert modern-alert--${type === 'success' ? 'success' : 'danger'} mb-3`;
        alertDiv.innerHTML = `
            <i data-lucide="${type === 'success' ? 'check-circle-2' : 'alert-circle'}" style="width:20px;height:20px;flex-shrink:0;"></i>
            <div class="modern-alert__content">
                <strong>${type === 'success' ? 'Success!' : 'Error!'}</strong> ${message}
            </div>
        `;
        const container = document.getElementById('alertContainer');
        if (container) {
            container.innerHTML = '';
            container.appendChild(alertDiv);
            if (typeof lucide !== 'undefined') lucide.createIcons();
            setTimeout(() => alertDiv.remove(), 4000);
        }
    }

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
                setTimeout(() => window.location.reload(), 800);
            } else {
                showAlert(data.message, 'danger');
            }
        })
        .catch(error => {
            showAlert('An error occurred. Please try again.', 'danger');
        });
    }

    // Set Primary Image listener
    document.querySelectorAll('.set-primary-btn').forEach(button => {
        button.addEventListener('click', function() {
            const imageId = this.dataset.imageId;
            const propertyId = this.dataset.propertyId;

            if (modalTitle) modalTitle.textContent = 'Set Primary Image';
            if (modalBody) modalBody.textContent = 'Are you sure you want to set this photo as the primary cover image?';
            if (confirmButton) {
                confirmButton.className = 'btn-modal-primary confirm-action';
                confirmButton.textContent = 'Set Primary';
                confirmButton.onclick = function() {
                    if (modal) modal.hide();
                    handleImageAction(imageId, propertyId, 'set_primary');
                };
            }
            if (modal) modal.show();
        });
    });

    // Delete Image listener
    document.querySelectorAll('.delete-image-btn').forEach(button => {
        button.addEventListener('click', function() {
            const imageId = this.dataset.imageId;
            const propertyId = this.dataset.propertyId;

            if (modalTitle) modalTitle.textContent = 'Delete Photo';
            if (modalBody) modalBody.textContent = 'Are you sure you want to permanently delete this photo? This cannot be undone.';
            if (confirmButton) {
                confirmButton.className = 'btn btn-danger confirm-action';
                confirmButton.style.borderRadius = '8px';
                confirmButton.textContent = 'Delete Photo';
                confirmButton.onclick = function() {
                    if (modal) modal.hide();
                    handleImageAction(imageId, propertyId, 'delete');
                };
            }
            if (modal) modal.show();
        });
    });
});
</script>

<?php include '../inc/footer.php'; ?>