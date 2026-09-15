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

// Counts for sidebar badges
$pendingCount = getPendingInquiriesCount($sellerId);
$unreadCount = getUnreadMessagesCount($sellerId);

// Process property deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_property') {
    $propertyId = isset($_POST['property_id']) ? intval($_POST['property_id']) : 0;
    
    if ($propertyId > 0) {
        // Verify the property belongs to this seller
        $sql = "SELECT id FROM properties WHERE id = ? AND seller_id = ?";
        $property = fetchOne($sql, "ii", [$propertyId, $sellerId]);
        
        if ($property) {
            // Delete property images from disk & db
            $sql = "SELECT image_path FROM property_images WHERE property_id = ?";
            $imgs = fetchAll($sql, "i", [$propertyId]);
            foreach ($imgs as $im) {
                if (file_exists('../' . $im['image_path'])) {
                    @unlink('../' . $im['image_path']);
                }
            }

            $sql = "DELETE FROM property_images WHERE property_id = ?";
            updateData($sql, "i", [$propertyId]);
            updateData("DELETE FROM inquiries WHERE property_id = ?", "i", [$propertyId]);
            updateData("DELETE FROM favorites WHERE property_id = ?", "i", [$propertyId]);
            
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

<style>
/* ============================================================
   SELLER PROPERTIES — Modern Minimalist + Bento Grid
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

.db-btn-primary {
    background: #2563EB;
    color: #FFFFFF;
}

.db-btn-primary:hover {
    background: #1D4ED8;
    color: #FFFFFF;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
    transform: translateY(-1px);
}

/* ── Property Bento Grid ── */
.db-props-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 18px;
}

.db-pcard {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    overflow: hidden;
    transition: transform .2s, box-shadow .2s;
    display: flex;
    flex-direction: column;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.05);
}

.db-pcard:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(15, 23, 42, 0.08);
}

.db-pcard-img-wrap {
    height: 180px;
    width: 100%;
    position: relative;
    overflow: hidden;
    background: #E2E8F0;
}

.db-pcard-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.db-pcard-price {
    position: absolute;
    bottom: 10px;
    left: 10px;
    background: rgba(15, 23, 42, 0.88);
    backdrop-filter: blur(6px);
    color: #FFFFFF;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-weight: 800;
    font-size: 0.92rem;
    padding: 4px 10px;
    border-radius: 8px;
}

.db-pcard-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    font-size: 0.68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    padding: 3px 9px;
    border-radius: 100px;
    backdrop-filter: blur(6px);
}

.db-pcard-badge--active  { background: #ECFDF5; color: #059669; border: 1px solid #A7F3D0; }
.db-pcard-badge--pending { background: #FEF3C7; color: #D97706; border: 1px solid #FDE68A; }
.db-pcard-badge--sold    { background: #FEE2E2; color: #DC2626; border: 1px solid #FECACA; }

.db-pcard-body {
    padding: 16px;
    display: flex;
    flex-direction: column;
    flex: 1;
}

.db-pcard-title {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 1.05rem;
    font-weight: 700;
    margin: 0 0 4px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.db-pcard-title a {
    color: #0F172A;
    text-decoration: none;
}
.db-pcard-title a:hover { color: #2563EB; }

.db-pcard-loc {
    font-size: 0.8rem;
    color: #64748B;
    display: flex;
    align-items: center;
    gap: 4px;
    margin-bottom: 12px;
}

.db-pcard-specs {
    display: flex;
    align-items: center;
    gap: 14px;
    padding-top: 10px;
    border-top: 1px solid #F1F5F9;
    font-size: 0.78rem;
    color: #64748B;
}

.db-pcard-spec-item {
    display: flex;
    align-items: center;
    gap: 4px;
}

.db-pcard-actions {
    display: flex;
    gap: 8px;
    margin-top: 14px;
    padding-top: 12px;
    border-top: 1px solid #F1F5F9;
}

.db-pbtn {
    flex: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
    padding: 7px 10px;
    border-radius: 8px;
    font-size: 0.8rem;
    font-weight: 600;
    text-decoration: none;
    border: 1px solid #E2E8F0;
    background: #FFFFFF;
    color: #475569;
    cursor: pointer;
    transition: all .15s;
}

.db-pbtn:hover {
    background: #F8FAFC;
    color: #0F172A;
    border-color: #CBD5E1;
}

.db-pbtn--edit:hover {
    color: #2563EB;
    border-color: #BFDBFE;
    background: #EFF6FF;
}

.db-pbtn--delete:hover {
    color: #DC2626;
    border-color: #FECACA;
    background: #FEF2F2;
}

/* Empty State */
.db-empty-box {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    padding: 48px 24px;
    text-align: center;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.05);
}

/* ── Responsive ── */
@media (max-width: 991.98px) {
    .db-layout {
        grid-template-columns: 1fr;
    }
    .db-props-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 575.98px) {
    .db-wrap { padding: 18px 0 40px; }
    .db-props-grid {
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
                        <a href="properties.php" class="db-nav-link active">
                            <i data-lucide="home" style="width:17px;height:17px;"></i>
                            <span>My Properties</span>
                        </a>
                        <a href="add_property.php" class="db-nav-link">
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
                        <h1 class="db-hero-title">My Properties</h1>
                        <p class="db-hero-sub">You have <?= count($properties) ?> listed <?= count($properties) === 1 ? 'property' : 'properties' ?> active in the marketplace.</p>
                    </div>
                    <div>
                        <a href="add_property.php" class="db-btn db-btn-primary">
                            <i data-lucide="plus" style="width:15px;height:15px;"></i> Add New Property
                        </a>
                    </div>
                </div>

                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success d-flex align-items-center" style="border-radius: 12px; border: 1px solid #A7F3D0; background: #ECFDF5; color: #065F46;">
                        <i data-lucide="check-circle" style="width:18px;height:18px;margin-right:8px;flex-shrink:0;"></i>
                        <span>Property deleted successfully!</span>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger d-flex align-items-center" style="border-radius: 12px; border: 1px solid #FECACA; background: #FEF2F2; color: #991B1B;">
                        <i data-lucide="alert-circle" style="width:18px;height:18px;margin-right:8px;flex-shrink:0;"></i>
                        <span>An error occurred while deleting the property. Please try again.</span>
                    </div>
                <?php endif; ?>

                <?php if (empty($properties)): ?>
                    <div class="db-empty-box">
                        <i data-lucide="home" style="width:48px;height:48px;color:#CBD5E1;margin-bottom:12px;"></i>
                        <h3 style="font-family: 'Plus Jakarta Sans', sans-serif; font-size: 1.15rem; font-weight: 700; color: #0F172A; margin-bottom: 6px;">
                            No Properties Listed Yet
                        </h3>
                        <p style="color: #64748B; font-size: 0.9rem; max-width: 440px; margin: 0 auto 20px;">
                            Start publishing your property listings to receive verified buyer inquiries and view requests.
                        </p>
                        <a href="add_property.php" class="db-btn db-btn-primary">
                            <i data-lucide="plus" style="width:15px;height:15px;"></i> List Your First Property
                        </a>
                    </div>
                <?php else: ?>
                    <div class="db-props-grid">
                        <?php foreach ($properties as $property): ?>
                            <?php
                                $imagePath = $property['primary_image'];
                                if (!empty($imagePath) && substr($imagePath, 0, 4) !== 'http') {
                                    $imagePath = '../' . ltrim($imagePath, '/');
                                } elseif (empty($imagePath)) {
                                    $imagePath = '../XTate-Image.png';
                                }
                                $st = strtolower($property['status'] ?? 'active');
                                $badgeClass = $st === 'active' ? 'db-pcard-badge--active' : ($st === 'sold' ? 'db-pcard-badge--sold' : 'db-pcard-badge--pending');
                            ?>
                            <div class="db-pcard">
                                <div class="db-pcard-img-wrap">
                                    <img src="<?= htmlspecialchars($imagePath) ?>" alt="<?= htmlspecialchars($property['title']) ?>" class="db-pcard-img" onerror="this.src='../XTate-Image.png'">
                                    <div class="db-pcard-price"><?= formatCurrency($property['price']) ?></div>
                                    <span class="db-pcard-badge <?= $badgeClass ?>"><?= ucfirst($st) ?></span>
                                </div>
                                <div class="db-pcard-body">
                                    <h3 class="db-pcard-title">
                                        <a href="../property_details.php?id=<?= $property['id'] ?>" title="<?= htmlspecialchars($property['title']) ?>">
                                            <?= htmlspecialchars($property['title']) ?>
                                        </a>
                                    </h3>
                                    <div class="db-pcard-loc">
                                        <i data-lucide="map-pin" style="width:12px;height:12px;color:#2563EB;flex-shrink:0;"></i>
                                        <span class="text-truncate"><?= htmlspecialchars($property['address']) ?>, <?= htmlspecialchars($property['city']) ?></span>
                                    </div>
                                    
                                    <div class="db-pcard-specs">
                                        <span class="db-pcard-spec-item"><i data-lucide="bed" style="width:13px;height:13px;"></i> <?= $property['bedrooms'] ?> bd</span>
                                        <span class="db-pcard-spec-item"><i data-lucide="bath" style="width:13px;height:13px;"></i> <?= $property['bathrooms'] ?> ba</span>
                                        <?php $sellerPropArea = (float)($property['area'] ?? $property['area_sqft'] ?? $property['sqft'] ?? 0); ?>
                                        <span class="db-pcard-spec-item"><i data-lucide="maximize-2" style="width:13px;height:13px;"></i> <?= number_format($sellerPropArea) ?> sqft</span>
                                    </div>

                                    <div class="db-pcard-actions">
                                        <a href="../property_details.php?id=<?= $property['id'] ?>" class="db-pbtn" title="View Listing">
                                            <i data-lucide="eye" style="width:13px;height:13px;"></i> View
                                        </a>
                                        <a href="edit_property.php?id=<?= $property['id'] ?>" class="db-pbtn db-pbtn--edit" title="Edit Listing">
                                            <i data-lucide="edit-3" style="width:13px;height:13px;"></i> Edit
                                        </a>
                                        <button type="button" class="db-pbtn db-pbtn--delete delete-property-btn" 
                                                data-property-id="<?= $property['id'] ?>"
                                                data-property-title="<?= htmlspecialchars($property['title'], ENT_QUOTES) ?>"
                                                title="Delete Listing">
                                            <i data-lucide="trash-2" style="width:13px;height:13px;"></i> Delete
                                        </button>
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

<!-- Delete Property Confirmation Modal -->
<div class="modal fade" id="deletePropertyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 16px; border: 1px solid #E2E8F0; overflow: hidden;">
            <div class="modal-header" style="background: #FEF2F2; border-bottom: 1px solid #FECACA;">
                <h5 class="modal-title font-weight-bold" style="font-family: 'Plus Jakarta Sans', sans-serif; font-size: 1rem; color: #DC2626; font-weight: 700;">
                    Delete Property
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="padding: 20px; color: #475569; font-size: 0.9rem;">
                <p>Are you sure you want to delete <strong id="deletePropertyTitle"></strong>?</p>
                <p style="color: #DC2626; margin: 0; font-size: 0.8rem;">All associated images, inquiries, and saved favorites will be permanently removed.</p>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #F1F5F9; padding: 14px 20px;">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal" style="border-radius: 8px;">Cancel</button>
                <form method="POST" action="properties.php" id="deletePropertyForm" style="display: inline;">
                    <input type="hidden" name="action" value="delete_property">
                    <input type="hidden" name="property_id" id="deletePropertyId" value="">
                    <button type="submit" class="btn btn-sm btn-danger" style="border-radius: 8px;">Delete Property</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') lucide.createIcons();

    const deleteModalEl = document.getElementById('deletePropertyModal');
    const deleteModal = deleteModalEl ? new bootstrap.Modal(deleteModalEl) : null;

    document.querySelectorAll('.delete-property-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const pid = this.getAttribute('data-property-id');
            const ptitle = this.getAttribute('data-property-title');
            document.getElementById('deletePropertyId').value = pid;
            document.getElementById('deletePropertyTitle').textContent = ptitle;
            if (deleteModal) deleteModal.show();
        });
    });
});
</script>

<?php include '../inc/footer.php'; ?>