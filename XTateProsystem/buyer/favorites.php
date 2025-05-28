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

// Process adding or removing favorites
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $propertyId = isset($_POST['property_id']) ? intval($_POST['property_id']) : 0;
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    if ($propertyId > 0) {
        try {
            // Connect to the database directly for reliable operation
            $conn = connectDB();
            if (!$conn) {
                throw new Exception("Database connection failed");
            }
            
            // Create favorites table if it doesn't exist
            $createTableSQL = "CREATE TABLE IF NOT EXISTS favorites (
                id INT AUTO_INCREMENT PRIMARY KEY,
                property_id INT NOT NULL,
                buyer_id INT NOT NULL,
                created_at DATETIME NOT NULL,
                UNIQUE KEY buyer_property (buyer_id, property_id)
            )";
            
            if (!$conn->query($createTableSQL)) {
                throw new Exception("Error creating favorites table: " . $conn->error);
            }
            
            if ($action === 'add' || $action === 'add_favorite') {
                // Add to favorites using prepared statement
                $stmt = $conn->prepare("INSERT INTO favorites (property_id, buyer_id, created_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE created_at = NOW()");
                if (!$stmt) {
                    throw new Exception("Prepare statement failed: " . $conn->error);
                }
                
                $stmt->bind_param("ii", $propertyId, $buyerId);
                $success = $stmt->execute();
                $stmt->close();
                
                if ($success) {
                    // Redirect back to referring page or to favorites page
                    if (isset($_SERVER['HTTP_REFERER'])) {
                        header("Location: " . $_SERVER['HTTP_REFERER']);
                        exit;
                    } else {
                        header("Location: favorites.php?success=1");
                        exit;
                    }
                } else {
                    throw new Exception("Failed to add favorite");
                }
            } elseif ($action === 'remove' || $action === 'remove_favorite') {
                // Remove from favorites using prepared statement
                $stmt = $conn->prepare("DELETE FROM favorites WHERE buyer_id = ? AND property_id = ?");
                if (!$stmt) {
                    throw new Exception("Prepare statement failed: " . $conn->error);
                }
                
                $stmt->bind_param("ii", $buyerId, $propertyId);
                $success = $stmt->execute();
                
                // Also remove from legacy table if it exists
                $legacyStmt = $conn->prepare("DELETE FROM buyer_favorites WHERE buyer_id = ? AND property_id = ?");
                if ($legacyStmt) {
                    $legacyStmt->bind_param("ii", $buyerId, $propertyId);
                    $legacyStmt->execute();
                    $legacyStmt->close();
                }
                
                $stmt->close();
                
                if ($success) {
                    // If the request is from an AJAX call, return JSON response
                    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => true]);
                        exit;
                    }
                    
                    // Otherwise, redirect to favorites page
                    header("Location: favorites.php?removed=1");
                    exit;
                } else {
                    throw new Exception("Failed to remove favorite");
                }
            }
            
            // Close the database connection
            closeDB($conn);
        } catch (Exception $e) {
            error_log("Favorite action error: " . $e->getMessage());
            
            // If the request is from an AJAX call, return JSON response
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
                exit;
            }
            
            // Otherwise, redirect to favorites page
            header("Location: favorites.php?error=1");
            exit;
        }
    }
    
    // If we reached here, there was an error
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'An error occurred: Invalid property ID']);
        exit;
    }
    
    header("Location: favorites.php?error=1");
    exit;
}

// Direct database approach to get favorites
error_log("Getting favorites for buyer ID: " . $buyerId);
try {
    $conn = connectDB();
    if (!$conn) {
        throw new Exception("Database connection failed");
    }
    
    // First ensure the favorites table exists
    $createTableSQL = "CREATE TABLE IF NOT EXISTS favorites (
        id INT AUTO_INCREMENT PRIMARY KEY,
        property_id INT NOT NULL,
        buyer_id INT NOT NULL,
        created_at DATETIME NOT NULL,
        UNIQUE KEY buyer_property (buyer_id, property_id)
    )";
    
    if (!$conn->query($createTableSQL)) {
        throw new Exception("Error creating favorites table: " . $conn->error);
    }
    
    // Use a direct query to get favorites from the database
    $sql = "SELECT p.*, 
            f.created_at as favorited_at,
            (SELECT image_path FROM property_images WHERE property_id = p.id AND is_primary = 1 LIMIT 1) as primary_image,
            u.full_name as seller_name, 
            u.email as seller_email, 
            u.phone as seller_phone
            FROM favorites f
            JOIN properties p ON f.property_id = p.id
            JOIN users u ON p.seller_id = u.id
            WHERE f.buyer_id = ?
            ORDER BY f.created_at DESC";
            
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare statement failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $buyerId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Fetch all the data
    $favorites = [];
    while ($row = $result->fetch_assoc()) {
        $favorites[] = $row;
    }
    
    $stmt->close();
    
    // If no results, check the legacy table as fallback
    if (empty($favorites)) {
        $legacySQL = "SELECT p.*, 
                     bf.created_at as favorited_at,
                     (SELECT image_path FROM property_images WHERE property_id = p.id AND is_primary = 1 LIMIT 1) as primary_image,
                     u.full_name as seller_name, 
                     u.email as seller_email, 
                     u.phone as seller_phone
                     FROM buyer_favorites bf
                     JOIN properties p ON bf.property_id = p.id
                     JOIN users u ON p.seller_id = u.id
                     WHERE bf.buyer_id = ?
                     ORDER BY bf.created_at DESC";
        
        $stmt = $conn->prepare($legacySQL);
        if ($stmt) {
            $stmt->bind_param("i", $buyerId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $favorites[] = $row;
                
                // Migrate this entry to the new favorites table
                $migrateSQL = "INSERT IGNORE INTO favorites (property_id, buyer_id, created_at) VALUES (?, ?, ?)";
                $migrateStmt = $conn->prepare($migrateSQL);
                if ($migrateStmt) {
                    $migrateStmt->bind_param("iis", $row['id'], $buyerId, $row['favorited_at']);
                    $migrateStmt->execute();
                    $migrateStmt->close();
                }
            }
            
            $stmt->close();
        }
    }
    
    // Close the database connection
    closeDB($conn);
    
    // If no results, don't use demo data, just show empty state
    if (empty($favorites)) {
        error_log("No favorites found in database");
        $favorites = []; // Empty array instead of demo data
    } else {
        error_log("Found " . count($favorites) . " favorites");
    }
} catch (Exception $e) {
    error_log("Error fetching favorites: " . $e->getMessage());
    // Use empty array instead of demo data
    $favorites = [];
}

include '../inc/header.php';
?>

<div class="container py-5">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-3 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Buyer Dashboard</h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="dashboard.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                    <a href="favorites.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-heart me-2"></i> My Favorites
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
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Favorite Properties</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($_GET['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle me-2"></i> Property added to favorites successfully!
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_GET['removed'])): ?>
                        <div class="alert alert-info alert-dismissible fade show">
                            <i class="fas fa-info-circle me-2"></i> Property removed from favorites.
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_GET['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-circle me-2"></i> An error occurred. Please try again.
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (empty($favorites)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i> You haven't added any properties to your favorites yet. Browse the <a href="../search.php" class="alert-link">property listings</a> to find properties you like.
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($favorites as $property): ?>
                                <div class="col-md-6 mb-4">
                                    <div class="card h-100 property-card">
                                        <?php
                            $imagePath = $property['primary_image'];
                            // If image path is empty, use fallback
                            if (empty($imagePath)) {
                                $imagePath = '../img/property-placeholder.jpg';
                            }
                            // If not an external URL, make it relative to root
                            else if (substr($imagePath, 0, 4) !== 'http') {
                                // Remove leading slash if present
                                $imagePath = ltrim($imagePath, '/');
                                // Add path to parent directory since we're in buyer/
                                $imagePath = '../' . $imagePath;
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
                                                    <i class="fas fa-eye me-1"></i> View Details
                                                </a>
                                                <a href="messages.php?user=<?= $property['seller_id'] ?>" class="btn btn-outline-success btn-sm">
                                                    <i class="fas fa-comment me-1"></i> Contact Seller
                                                </a>
                                                <form method="POST" class="remove-favorite-form">
                                                    <input type="hidden" name="action" value="remove">
                                                    <input type="hidden" name="property_id" value="<?= $property['id'] ?>">
                                                    <button type="submit" class="btn btn-outline-danger btn-sm remove-favorite-btn" data-property-id="<?= $property['id'] ?>">
                                                        <i class="fas fa-heart-broken me-1"></i> Remove
                                                    </button>
                                                </form>
                                            </div>
                                            
                                            <small class="text-muted mt-2 d-block">Added to favorites: <?= formatDateTime($property['favorited_at']) ?></small>
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

<script src="../js/buyer_favorites.js"></script>

<?php include '../inc/footer.php'; ?>