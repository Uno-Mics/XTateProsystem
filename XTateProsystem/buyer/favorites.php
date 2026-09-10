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

// Mark all favorites as viewed when accessing the favorites tab
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    markFavoritesAsViewed($buyerId);
}

// Process adding or removing favorites
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $propertyId = isset($_POST['property_id']) ? intval($_POST['property_id']) : 0;
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    if ($propertyId > 0) {
        try {
            $conn = connectDB();
            if (!$conn) {
                throw new Exception("Database connection failed");
            }
            
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
                $stmt = $conn->prepare("INSERT INTO favorites (property_id, buyer_id, created_at, is_viewed) VALUES (?, ?, NOW(), 0) ON DUPLICATE KEY UPDATE is_viewed = 0");
                if (!$stmt) {
                    throw new Exception("Prepare statement failed: " . $conn->error);
                }
                
                $stmt->bind_param("ii", $propertyId, $buyerId);
                $success = $stmt->execute();
                $stmt->close();
                
                if ($success) {
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
                $stmt = $conn->prepare("DELETE FROM favorites WHERE buyer_id = ? AND property_id = ?");
                if (!$stmt) {
                    throw new Exception("Prepare statement failed: " . $conn->error);
                }
                
                $stmt->bind_param("ii", $buyerId, $propertyId);
                $success = $stmt->execute();
                
                $legacyStmt = $conn->prepare("DELETE FROM buyer_favorites WHERE buyer_id = ? AND property_id = ?");
                if ($legacyStmt) {
                    $legacyStmt->bind_param("ii", $buyerId, $propertyId);
                    $legacyStmt->execute();
                    $legacyStmt->close();
                }
                
                $stmt->close();
                
                if ($success) {
                    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => true]);
                        exit;
                    }
                    
                    header("Location: favorites.php?removed=1");
                    exit;
                } else {
                    throw new Exception("Failed to remove favorite");
                }
            }
            
            closeDB($conn);
        } catch (Exception $e) {
            error_log("Favorite action error: " . $e->getMessage());
            
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
                exit;
            }
            
            header("Location: favorites.php?error=1");
            exit;
        }
    }
    
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'An error occurred: Invalid property ID']);
        exit;
    }
    
    header("Location: favorites.php?error=1");
    exit;
}

// Fetch favorites
try {
    $conn = connectDB();
    if (!$conn) {
        throw new Exception("Database connection failed");
    }
    
    $createTableSQL = "CREATE TABLE IF NOT EXISTS favorites (
        id INT AUTO_INCREMENT PRIMARY KEY,
        property_id INT NOT NULL,
        buyer_id INT NOT NULL,
        created_at DATETIME NOT NULL,
        UNIQUE KEY buyer_property (buyer_id, property_id)
    )";
    $conn->query($createTableSQL);
    
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
    $stmt->bind_param("i", $buyerId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $favorites = [];
    while ($row = $result->fetch_assoc()) {
        $favorites[] = $row;
    }
    $stmt->close();
    
    // Check legacy fallback
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
    closeDB($conn);
} catch (Exception $e) {
    error_log("Error fetching favorites: " . $e->getMessage());
    $favorites = [];
}

$unviewedFavoritesCount = getUnviewedFavoritesCount($buyerId);
$unreadCount = getUnreadMessagesCount($buyerId);

include '../inc/header.php';
?>

<div class="db-wrap">
    <div class="db-container">
        <div class="db-layout">

            <!-- ══════════ LEFT: BUYER SIDEBAR ══════════ -->
            <aside class="db-sidebar">
                <div class="db-card db-profile-card">
                    <div class="db-avatar">
                        <?= strtoupper(substr($buyer['full_name'] ?? 'B', 0, 1)) ?>
                    </div>
                    <h3 class="db-user-name"><?= htmlspecialchars($buyer['full_name']) ?></h3>
                    <span class="db-user-badge">
                        <i data-lucide="shield-check" style="width:13px;height:13px;"></i> Verified Buyer
                    </span>
                </div>

                <div class="db-card">
                    <nav class="db-nav-group">
                        <a href="dashboard.php" class="db-nav-link">
                            <i data-lucide="layout-dashboard" style="width:17px;height:17px;"></i>
                            <span>Dashboard</span>
                        </a>
                        <a href="favorites.php" class="db-nav-link active">
                            <i data-lucide="heart" style="width:17px;height:17px;"></i>
                            <span>My Favorites</span>
                            <?php if ($unviewedFavoritesCount > 0): ?>
                                <span class="db-nav-badge"><?= $unviewedFavoritesCount ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="messages.php" class="db-nav-link">
                            <i data-lucide="message-square" style="width:17px;height:17px;"></i>
                            <span>Messages</span>
                            <?php if ($unreadCount > 0): ?>
                                <span class="db-nav-badge db-nav-badge--danger"><?= $unreadCount ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="../search.php" class="db-nav-link">
                            <i data-lucide="search" style="width:17px;height:17px;"></i>
                            <span>Find Properties</span>
                        </a>
                        <a href="profile.php" class="db-nav-link">
                            <i data-lucide="user-cog" style="width:17px;height:17px;"></i>
                            <span>Edit Profile</span>
                        </a>
                    </nav>
                </div>
            </aside>

            <!-- ══════════ RIGHT: MAIN WORKSPACE ══════════ -->
            <main class="db-main">

                <!-- Header Banner -->
                <div class="db-hero-banner">
                    <div>
                        <h1 class="db-hero-title">My Saved Properties</h1>
                        <p class="db-hero-desc">Quickly access, compare, and manage your bookmarked homes and investment listings</p>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="favorites-stat-pill">
                            <i data-lucide="heart" style="width:15px;height:15px;color:#EF4444;fill:#FEE2E2;"></i>
                            <span><?= count($favorites) ?> Saved Listings</span>
                        </span>
                        <a href="../search.php" class="btn-db-action btn-db-action--primary">
                            <i data-lucide="search" style="width:16px;height:16px;"></i>
                            <span>Explore More</span>
                        </a>
                    </div>
                </div>

                <!-- Alert Messages -->
                <?php if (isset($_GET['success'])): ?>
                    <div class="modern-alert modern-alert--success">
                        <i data-lucide="check-circle-2" style="width:20px;height:20px;flex-shrink:0;"></i>
                        <div class="modern-alert__content">
                            <strong>Success!</strong> Property added to your favorites.
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['removed'])): ?>
                    <div class="modern-alert modern-alert--info">
                        <i data-lucide="info" style="width:20px;height:20px;flex-shrink:0;"></i>
                        <div class="modern-alert__content">
                            Property removed from your favorites list.
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['error'])): ?>
                    <div class="modern-alert modern-alert--danger">
                        <i data-lucide="alert-circle" style="width:20px;height:20px;flex-shrink:0;"></i>
                        <div class="modern-alert__content">
                            An error occurred while updating your favorites. Please try again.
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Favorites Grid / Empty State -->
                <?php if (empty($favorites)): ?>
                    <div class="db-card">
                        <div class="favorites-empty-state">
                            <div class="empty-icon-circle">
                                <i data-lucide="heart-off"></i>
                            </div>
                            <h3>No favorites saved yet</h3>
                            <p>You haven't added any properties to your favorites yet. Browse through our premium property catalog and tap the heart icon on properties that catch your eye.</p>
                            <a href="../search.php" class="btn-db-action btn-db-action--primary">
                                <i data-lucide="compass" style="width:17px;height:17px;"></i>
                                <span>Browse Properties</span>
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="row g-4">
                        <?php foreach ($favorites as $property): 
                            $imagePath = $property['primary_image'];
                            if (empty($imagePath)) {
                                $imagePath = '../img/property-placeholder.jpg';
                            } else if (substr($imagePath, 0, 4) !== 'http') {
                                $imagePath = ltrim($imagePath, '/');
                                $imagePath = '../' . $imagePath;
                            }
                        ?>
                            <div class="col-md-6">
                                <div class="db-pcard h-100">
                                    <div class="db-pcard-img" style="background-image: url('<?= htmlspecialchars($imagePath) ?>');">
                                        <div class="db-pcard-price">
                                            <?= formatCurrency($property['price']) ?>
                                        </div>
                                        <form method="POST" class="remove-favorite-form" style="position: absolute; top: 12px; right: 12px; z-index: 2;">
                                            <input type="hidden" name="action" value="remove">
                                            <input type="hidden" name="property_id" value="<?= $property['id'] ?>">
                                            <button type="submit" class="btn-heart-remove remove-favorite-btn" data-property-id="<?= $property['id'] ?>" title="Remove from Favorites">
                                                <i data-lucide="heart" style="width:16px;height:16px;fill:#EF4444;color:#EF4444;"></i>
                                            </button>
                                        </form>
                                    </div>

                                    <div class="db-pcard-body">
                                        <h3 class="db-pcard-title">
                                            <a href="../property_details.php?id=<?= $property['id'] ?>" title="<?= htmlspecialchars($property['title']) ?>">
                                                <?= htmlspecialchars($property['title']) ?>
                                            </a>
                                        </h3>
                                        <div class="db-pcard-loc">
                                            <i data-lucide="map-pin" style="width:13px;height:13px;flex-shrink:0;"></i>
                                            <span><?= htmlspecialchars($property['address'] . ', ' . $property['city'] . ', ' . $property['state']) ?></span>
                                        </div>

                                        <div class="db-pcard-specs">
                                            <div class="db-pcard-spec-item">
                                                <i data-lucide="bed-double" style="width:14px;height:14px;"></i>
                                                <span><?= (int)$property['bedrooms'] ?> Beds</span>
                                            </div>
                                            <div class="db-pcard-spec-item">
                                                <i data-lucide="bath" style="width:14px;height:14px;"></i>
                                                <span><?= (float)$property['bathrooms'] ?> Baths</span>
                                            </div>
                                            <div class="db-pcard-spec-item">
                                                <i data-lucide="maximize-2" style="width:14px;height:14px;"></i>
                                                <span><?= number_format($property['area']) ?> sqft</span>
                                            </div>
                                        </div>

                                        <div class="db-pcard-actions">
                                            <a href="../property_details.php?id=<?= $property['id'] ?>" class="btn-card-action btn-card-action--view">
                                                <i data-lucide="eye" style="width:15px;height:15px;"></i>
                                                <span>View Details</span>
                                            </a>
                                            <a href="messages.php?user=<?= $property['seller_id'] ?>" class="btn-card-action btn-card-action--chat">
                                                <i data-lucide="message-square" style="width:15px;height:15px;"></i>
                                                <span>Contact Seller</span>
                                            </a>
                                        </div>

                                        <div class="favorited-date-footnote">
                                            <i data-lucide="clock" style="width:12px;height:12px;"></i>
                                            <span>Saved <?= date('M d, Y', strtotime($property['favorited_at'])) ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            </main>
        </div>
    </div>
</div>

<style>
/* ── Base Tokens & Layout ── */
.db-wrap {
    background: #F8FAFC;
    min-height: 100vh;
    padding: 30px 0 70px;
    width: 100%;
    overflow-x: hidden;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    color: #0F172A;
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
    background: linear-gradient(135deg, #2563EB, #06B6D4);
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

.favorites-stat-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.82rem;
    font-weight: 700;
    padding: 6px 14px;
    border-radius: 100px;
    background: #FEF2F2;
    color: #DC2626;
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

/* Alerts */
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
.modern-alert--info {
    background: #EFF6FF;
    border: 1px solid #BFDBFE;
    color: #1E40AF;
}
.modern-alert--danger {
    background: #FEF2F2;
    border: 1px solid #FCA5A5;
    color: #991B1B;
}

/* Empty State */
.favorites-empty-state {
    padding: 60px 24px;
    text-align: center;
    max-width: 520px;
    margin: 0 auto;
}

.empty-icon-circle {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: #FEF2F2;
    color: #EF4444;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 16px;
}
.empty-icon-circle svg {
    width: 32px;
    height: 32px;
}

.favorites-empty-state h3 {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 1.25rem;
    font-weight: 700;
    color: #0F172A;
    margin-bottom: 8px;
}

.favorites-empty-state p {
    font-size: 0.88rem;
    color: #64748B;
    line-height: 1.5;
    margin-bottom: 20px;
}

/* ── Bento Property Card ── */
.db-pcard {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    transition: all .2s ease;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.05);
}

.db-pcard:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.08);
}

.db-pcard-img {
    height: 200px;
    background-size: cover;
    background-position: center;
    position: relative;
    background-color: #E2E8F0;
}

.db-pcard-price {
    position: absolute;
    bottom: 12px;
    left: 12px;
    background: rgba(15, 23, 42, 0.85);
    backdrop-filter: blur(8px);
    color: #fff;
    padding: 5px 12px;
    border-radius: 8px;
    font-weight: 700;
    font-size: 0.95rem;
    letter-spacing: -0.01em;
}

.btn-heart-remove {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(4px);
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all .15s ease;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
}
.btn-heart-remove:hover {
    transform: scale(1.1);
    background: #FFFFFF;
}

.db-pcard-body {
    padding: 18px;
    display: flex;
    flex-direction: column;
    flex-grow: 1;
}

.db-pcard-title {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 1rem;
    font-weight: 700;
    margin: 0 0 6px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.db-pcard-title a {
    color: #0F172A;
    text-decoration: none;
    transition: color .15s ease;
}
.db-pcard-title a:hover {
    color: #2563EB;
}

.db-pcard-loc {
    font-size: 0.8rem;
    color: #64748B;
    display: flex;
    align-items: center;
    gap: 4px;
    margin-bottom: 14px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.db-pcard-specs {
    display: flex;
    align-items: center;
    gap: 16px;
    padding-bottom: 14px;
    border-bottom: 1px solid #F1F5F9;
    font-size: 0.78rem;
    color: #64748B;
}

.db-pcard-spec-item {
    display: flex;
    align-items: center;
    gap: 5px;
}

.db-pcard-actions {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    margin-top: 14px;
}

.btn-card-action {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 8px 12px;
    border-radius: 8px;
    font-size: 0.8rem;
    font-weight: 600;
    text-decoration: none;
    transition: all .15s ease;
}

.btn-card-action--view {
    background: #EFF6FF;
    color: #2563EB;
}
.btn-card-action--view:hover {
    background: #DBEAFE;
    color: #1D4ED8;
}

.btn-card-action--chat {
    background: #F0FDF4;
    color: #059669;
}
.btn-card-action--chat:hover {
    background: #DCFCE7;
    color: #047857;
}

.favorited-date-footnote {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 0.72rem;
    color: #94A3B8;
    margin-top: 12px;
}
</style>

<script src="../js/buyer_favorites.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});
</script>

<?php include '../inc/footer.php'; ?>