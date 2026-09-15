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

// Counts for sidebar badges
$pendingCount = getPendingInquiriesCount($sellerId);
$unreadCount = getUnreadMessagesCount($sellerId);

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
        $conn = connectDB();
        $conn->begin_transaction();
        
        try {
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
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $totalImages = count($_FILES['property_images']['name']);
            $uploadedImages = 0;
            
            for ($i = 0; $i < $totalImages; $i++) {
                if ($_FILES['property_images']['error'][$i] === UPLOAD_ERR_OK) {
                    $tempName = $_FILES['property_images']['tmp_name'][$i];
                    $originalName = $_FILES['property_images']['name'][$i];
                    $fileType = $_FILES['property_images']['type'][$i];
                    
                    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                    if (!in_array($fileType, $allowedTypes)) {
                        continue;
                    }
                    
                    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
                    $newFileName = $propertyId . '_' . uniqid() . '.' . $extension;
                    $targetPath = $uploadDir . $newFileName;
                    
                    if (move_uploaded_file($tempName, $targetPath)) {
                        $filename = basename($targetPath);
                        $dbImagePath = '/uploads/properties/' . $filename;
                        
                        $sql = "INSERT INTO property_images (property_id, image_path, is_primary, created_at) 
                                VALUES (?, ?, ?, NOW())";
                        
                        $isPrimary = ($uploadedImages === 0) ? 1 : 0;
                        
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("isi", $propertyId, $dbImagePath, $isPrimary);
                        $stmt->execute();
                        $stmt->close();
                        
                        $uploadedImages++;
                    }
                }
            }
            
            if ($uploadedImages === 0) {
                throw new Exception('Failed to upload property images. Please ensure valid JPG, PNG, or WebP files are used.');
            }
            
            $conn->commit();
            $success = 'Property published successfully!';

            // Sync new property to Cloud Firestore
            if (function_exists('firestore_sync_property')) {
                firestore_sync_property($propertyId);
            }
            
            // Clear form
            $formData = [
                'title' => '', 'description' => '', 'price' => '', 'property_type_id' => '',
                'bedrooms' => '', 'bathrooms' => '', 'area' => '', 'address' => '',
                'city' => '', 'state' => '', 'zip_code' => '', 'year_built' => '',
                'garage' => 0, 'air_conditioning' => 0, 'swimming_pool' => 0,
                'backyard' => 0, 'gym' => 0, 'fireplace' => 0,
                'security_system' => 0, 'washer_dryer' => 0
            ];
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = 'Error adding property: ' . $e->getMessage();
        }
        
        $conn->close();
    }
}

include '../inc/header.php';
?>

<style>
/* ============================================================
   ADD PROPERTY — Modern Minimalist + Bento Grid
   ============================================================ */

.db-wrap {
    background: #F8FAFC;
    min-height: 100vh;
    padding: 30px 0 70px;
    width: 100%;
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

.db-hero-sub {
    font-size: 0.88rem;
    color: #64748B;
    margin: 0;
}

.db-btn {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 9px 16px;
    border-radius: 9px;
    font-size: 0.85rem;
    font-weight: 600;
    text-decoration: none;
    transition: all .2s;
    cursor: pointer;
    border: none;
}

.db-btn-secondary {
    background: #F8FAFC;
    color: #475569;
    border: 1px solid #E2E8F0;
}

.db-btn-secondary:hover {
    background: #F1F5F9;
    color: #0F172A;
}

.db-btn-primary {
    background: #2563EB;
    color: #FFFFFF;
}

.db-btn-primary:hover {
    background: #1D4ED8;
    color: #FFFFFF;
    box-shadow: 0 4px 14px rgba(37, 99, 235, 0.25);
    transform: translateY(-1px);
}

/* ── Bento Form Panels ── */
.db-form-panel {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.05);
    margin-bottom: 20px;
}

.db-form-panel-title {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 1.05rem;
    font-weight: 700;
    color: #0F172A;
    margin-bottom: 18px;
    display: flex;
    align-items: center;
    gap: 8px;
    padding-bottom: 12px;
    border-bottom: 1px solid #F1F5F9;
}

.db-label {
    font-size: 0.76rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #475569;
    margin-bottom: 6px;
    display: block;
}

.db-label .req { color: #DC2626; }

.db-input,
.db-select,
.db-textarea {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid #CBD5E1;
    border-radius: 10px;
    font-size: 0.9rem;
    color: #0F172A;
    font-family: 'Inter', sans-serif;
    transition: all .15s ease;
    background: #FFFFFF;
}

.db-input:focus,
.db-select:focus,
.db-textarea:focus {
    outline: none;
    border-color: #2563EB;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

/* Amenities Grid */
.db-amenities-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
}

.db-amenity-checkbox {
    position: relative;
}

.db-amenity-checkbox input {
    position: absolute;
    opacity: 0;
    cursor: pointer;
}

.db-amenity-label {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 14px;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    background: #F8FAFC;
    color: #475569;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    transition: all .2s;
    user-select: none;
}

.db-amenity-checkbox input:checked + .db-amenity-label {
    background: #EFF6FF;
    border-color: #2563EB;
    color: #2563EB;
    box-shadow: 0 2px 8px rgba(37, 99, 235, 0.15);
}

/* Upload zone */
.db-upload-zone {
    border: 2px dashed #CBD5E1;
    border-radius: 14px;
    padding: 32px 20px;
    text-align: center;
    background: #F8FAFC;
    cursor: pointer;
    transition: all .2s;
}

.db-upload-zone:hover {
    border-color: #2563EB;
    background: #EFF6FF;
}

/* ── Responsive ── */
@media (max-width: 991.98px) {
    .db-layout {
        grid-template-columns: 1fr;
    }
    .db-amenities-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 575.98px) {
    .db-wrap { padding: 18px 0 40px; }
    .db-amenities-grid {
        grid-template-columns: 1fr;
    }
    .db-hero-banner {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>

<div class="db-wrap">
    <div class="db-container">
        <div class="db-layout">

            <!-- ══════════ LEFT: SELLER SIDEBAR ══════════ -->
            <aside class="db-sidebar">
                <div class="db-card db-profile-card">
                    <div class="db-avatar">
                        <?= strtoupper(substr($seller['full_name'] ?? 'S', 0, 1)) ?>
                    </div>
                    <h3 class="db-user-name"><?= htmlspecialchars($seller['full_name']) ?></h3>
                    <span class="db-user-badge">
                        <i data-lucide="badge-check" style="width:13px;height:13px;"></i> Verified Seller
                    </span>
                </div>

                <div class="db-card">
                    <nav class="db-nav-group">
                        <a href="dashboard.php" class="db-nav-link">
                            <i data-lucide="layout-dashboard" style="width:17px;height:17px;"></i>
                            <span>Dashboard</span>
                        </a>
                        <a href="properties.php" class="db-nav-link">
                            <i data-lucide="home" style="width:17px;height:17px;"></i>
                            <span>My Properties</span>
                        </a>
                        <a href="add_property.php" class="db-nav-link active">
                            <i data-lucide="plus-circle" style="width:17px;height:17px;"></i>
                            <span>Add Property</span>
                        </a>
                        <a href="inquiries.php" class="db-nav-link">
                            <i data-lucide="inbox" style="width:17px;height:17px;"></i>
                            <span>Inquiries</span>
                            <?php if ($pendingCount > 0): ?>
                                <span class="db-nav-badge"><?= $pendingCount ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="messages.php" class="db-nav-link">
                            <i data-lucide="message-square" style="width:17px;height:17px;"></i>
                            <span>Messages</span>
                            <?php if ($unreadCount > 0): ?>
                                <span class="db-nav-badge db-nav-badge--danger"><?= $unreadCount ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="profile.php" class="db-nav-link">
                            <i data-lucide="user-cog" style="width:17px;height:17px;"></i>
                            <span>Edit Profile</span>
                        </a>
                    </nav>
                </div>
            </aside>

            <!-- ══════════ RIGHT: BENTO WORKSPACE ══════════ -->
            <main class="db-main">

                <!-- Welcome Banner -->
                <div class="db-hero-banner">
                    <div>
                        <h1 class="db-hero-title">Add New Property</h1>
                        <p class="db-hero-sub">Fill in the specifications, details, and photos to list your property.</p>
                    </div>
                    <div>
                        <a href="properties.php" class="db-btn db-btn-secondary">
                            <i data-lucide="arrow-left" style="width:15px;height:15px;"></i> Back to Listings
                        </a>
                    </div>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger" style="border-radius: 12px; border: 1px solid #FECACA; background: #FEF2F2; color: #991B1B;">
                        <div class="d-flex align-items-center mb-2">
                            <i data-lucide="alert-triangle" style="width:18px;height:18px;margin-right:8px;flex-shrink:0;"></i>
                            <strong>Please fix the following issues:</strong>
                        </div>
                        <ul class="mb-0 ps-3">
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success d-flex align-items-center" style="border-radius: 12px; border: 1px solid #A7F3D0; background: #ECFDF5; color: #065F46;">
                        <i data-lucide="check-circle" style="width:18px;height:18px;margin-right:8px;flex-shrink:0;"></i>
                        <div>
                            <strong><?= htmlspecialchars($success) ?></strong>
                            <a href="properties.php" class="alert-link ms-2" style="color: #065F46; font-weight: 700;">View your listings →</a>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="add_property.php" enctype="multipart/form-data" novalidate>

                    <!-- Card 1: Basic Information -->
                    <div class="db-form-panel">
                        <h3 class="db-form-panel-title">
                            <i data-lucide="info" style="width:18px;height:18px;color:#2563EB;"></i>
                            Basic Information
                        </h3>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="db-label" for="title">Property Title <span class="req">*</span></label>
                                <input type="text" class="db-input" id="title" name="title" value="<?= htmlspecialchars($formData['title']) ?>" placeholder="e.g. Amber Abode — Luxury 4-Bedroom Villa" required>
                            </div>
                            <div class="col-12">
                                <label class="db-label" for="description">Detailed Description <span class="req">*</span></label>
                                <textarea class="db-textarea" id="description" name="description" rows="4" placeholder="Describe the architectural style, natural light, neighbourhood highlights, and unique perks..." required><?= htmlspecialchars($formData['description']) ?></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="db-label" for="price">Price (USD) <span class="req">*</span></label>
                                <input type="number" step="any" class="db-input" id="price" name="price" value="<?= htmlspecialchars($formData['price']) ?>" placeholder="e.g. 6500000" required>
                            </div>
                            <div class="col-md-6">
                                <label class="db-label" for="property_type_id">Property Type <span class="req">*</span></label>
                                <select class="db-select" id="property_type_id" name="property_type_id" required>
                                    <option value="">Select Property Type</option>
                                    <?php foreach ($propertyTypes as $type): ?>
                                        <option value="<?= $type['id'] ?>" <?= $formData['property_type_id'] == $type['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($type['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Card 2: Specifications -->
                    <div class="db-form-panel">
                        <h3 class="db-form-panel-title">
                            <i data-lucide="sliders" style="width:18px;height:18px;color:#2563EB;"></i>
                            Property Specifications
                        </h3>
                        <div class="row g-3">
                            <div class="col-md-3 col-6">
                                <label class="db-label" for="bedrooms">Bedrooms <span class="req">*</span></label>
                                <input type="number" class="db-input" id="bedrooms" name="bedrooms" value="<?= htmlspecialchars($formData['bedrooms']) ?>" min="0" placeholder="e.g. 4" required>
                            </div>
                            <div class="col-md-3 col-6">
                                <label class="db-label" for="bathrooms">Bathrooms <span class="req">*</span></label>
                                <input type="number" class="db-input" id="bathrooms" name="bathrooms" value="<?= htmlspecialchars($formData['bathrooms']) ?>" min="0" placeholder="e.g. 3" required>
                            </div>
                            <div class="col-md-3 col-6">
                                <label class="db-label" for="area">Area (Sq. Ft.) <span class="req">*</span></label>
                                <input type="number" step="any" class="db-input" id="area" name="area" value="<?= htmlspecialchars($formData['area']) ?>" min="0" placeholder="e.g. 3500" required>
                            </div>
                            <div class="col-md-3 col-6">
                                <label class="db-label" for="year_built">Year Built</label>
                                <input type="number" class="db-input" id="year_built" name="year_built" value="<?= htmlspecialchars($formData['year_built']) ?>" min="1800" max="<?= date('Y') + 1 ?>" placeholder="e.g. <?= date('Y') ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Card 3: Location Details -->
                    <div class="db-form-panel">
                        <h3 class="db-form-panel-title">
                            <i data-lucide="map-pin" style="width:18px;height:18px;color:#2563EB;"></i>
                            Location Details
                        </h3>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="db-label" for="address">Street Address <span class="req">*</span></label>
                                <input type="text" class="db-input" id="address" name="address" value="<?= htmlspecialchars($formData['address']) ?>" placeholder="e.g. 123 Crescent Ave" required>
                            </div>
                            <div class="col-md-4">
                                <label class="db-label" for="city">City <span class="req">*</span></label>
                                <input type="text" class="db-input" id="city" name="city" value="<?= htmlspecialchars($formData['city']) ?>" placeholder="e.g. Cavite City" required>
                            </div>
                            <div class="col-md-4">
                                <label class="db-label" for="state">State / Province <span class="req">*</span></label>
                                <input type="text" class="db-input" id="state" name="state" value="<?= htmlspecialchars($formData['state']) ?>" placeholder="e.g. Cavite" required>
                            </div>
                            <div class="col-md-4">
                                <label class="db-label" for="zip_code">ZIP Code <span class="req">*</span></label>
                                <input type="text" class="db-input" id="zip_code" name="zip_code" value="<?= htmlspecialchars($formData['zip_code']) ?>" placeholder="e.g. 4100" required>
                            </div>
                        </div>
                    </div>

                    <!-- Card 4: Amenities Bento Selection -->
                    <div class="db-form-panel">
                        <h3 class="db-form-panel-title">
                            <i data-lucide="sparkles" style="width:18px;height:18px;color:#2563EB;"></i>
                            Amenities & Features
                        </h3>
                        <div class="db-amenities-grid">
                            <div class="db-amenity-checkbox">
                                <input type="checkbox" id="garage" name="garage" value="1" <?= $formData['garage'] ? 'checked' : '' ?>>
                                <label class="db-amenity-label" for="garage">
                                    <i data-lucide="warehouse" style="width:16px;height:16px;"></i> Garage
                                </label>
                            </div>
                            <div class="db-amenity-checkbox">
                                <input type="checkbox" id="air_conditioning" name="air_conditioning" value="1" <?= $formData['air_conditioning'] ? 'checked' : '' ?>>
                                <label class="db-amenity-label" for="air_conditioning">
                                    <i data-lucide="fan" style="width:16px;height:16px;"></i> Air Conditioning
                                </label>
                            </div>
                            <div class="db-amenity-checkbox">
                                <input type="checkbox" id="swimming_pool" name="swimming_pool" value="1" <?= $formData['swimming_pool'] ? 'checked' : '' ?>>
                                <label class="db-amenity-label" for="swimming_pool">
                                    <i data-lucide="waves" style="width:16px;height:16px;"></i> Swimming Pool
                                </label>
                            </div>
                            <div class="db-amenity-checkbox">
                                <input type="checkbox" id="backyard" name="backyard" value="1" <?= $formData['backyard'] ? 'checked' : '' ?>>
                                <label class="db-amenity-label" for="backyard">
                                    <i data-lucide="trees" style="width:16px;height:16px;"></i> Backyard
                                </label>
                            </div>
                            <div class="db-amenity-checkbox">
                                <input type="checkbox" id="gym" name="gym" value="1" <?= $formData['gym'] ? 'checked' : '' ?>>
                                <label class="db-amenity-label" for="gym">
                                    <i data-lucide="dumbbell" style="width:16px;height:16px;"></i> Fitness Gym
                                </label>
                            </div>
                            <div class="db-amenity-checkbox">
                                <input type="checkbox" id="fireplace" name="fireplace" value="1" <?= $formData['fireplace'] ? 'checked' : '' ?>>
                                <label class="db-amenity-label" for="fireplace">
                                    <i data-lucide="flame" style="width:16px;height:16px;"></i> Fireplace
                                </label>
                            </div>
                            <div class="db-amenity-checkbox">
                                <input type="checkbox" id="security_system" name="security_system" value="1" <?= $formData['security_system'] ? 'checked' : '' ?>>
                                <label class="db-amenity-label" for="security_system">
                                    <i data-lucide="shield" style="width:16px;height:16px;"></i> Security System
                                </label>
                            </div>
                            <div class="db-amenity-checkbox">
                                <input type="checkbox" id="washer_dryer" name="washer_dryer" value="1" <?= $formData['washer_dryer'] ? 'checked' : '' ?>>
                                <label class="db-amenity-label" for="washer_dryer">
                                    <i data-lucide="shirt" style="width:16px;height:16px;"></i> Washer / Dryer
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Card 5: Media & Photos -->
                    <div class="db-form-panel">
                        <h3 class="db-form-panel-title">
                            <i data-lucide="images" style="width:18px;height:18px;color:#2563EB;"></i>
                            Property Photos <span class="req">*</span>
                        </h3>
                        <p style="font-size: 0.85rem; color: #64748B; margin-bottom: 16px;">
                            Upload high-resolution property images. The first image uploaded will serve as the primary showcase photo.
                        </p>
                        
                        <div class="db-upload-zone" onclick="document.getElementById('property_images').click();">
                            <i data-lucide="upload-cloud" style="width:40px;height:40px;color:#2563EB;margin-bottom:8px;"></i>
                            <div style="font-weight: 700; color: #0F172A; font-size: 0.95rem; margin-bottom: 4px;">
                                Click or drag photos here to upload
                            </div>
                            <div style="font-size: 0.8rem; color: #94A3B8;">Supports JPG, PNG, WebP (Multiple selections allowed)</div>
                            <input type="file" class="d-none" id="property_images" name="property_images[]" multiple accept="image/*" required>
                        </div>
                        <div id="fileListPreview" style="margin-top: 12px; font-size: 0.85rem; color: #475569;"></div>
                    </div>

                    <!-- Submit Bar -->
                    <div class="d-flex justify-content-end gap-3" style="margin-top: 10px;">
                        <a href="properties.php" class="db-btn db-btn-secondary" style="padding: 11px 22px;">Cancel</a>
                        <button type="submit" class="db-btn db-btn-primary" style="padding: 11px 28px; font-size: 0.92rem;">
                            <i data-lucide="check" style="width:16px;height:16px;"></i> Publish Property
                        </button>
                    </div>

                </form>

            </main>

        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') lucide.createIcons();

    // File preview
    const fileInput = document.getElementById('property_images');
    const preview = document.getElementById('fileListPreview');
    if (fileInput && preview) {
        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                preview.innerHTML = `<strong>Selected ${this.files.length} file(s):</strong> ` + 
                    Array.from(this.files).map(f => f.name).join(', ');
            } else {
                preview.innerHTML = '';
            }
        });
    }
});
</script>

<?php include '../inc/footer.php'; ?>
