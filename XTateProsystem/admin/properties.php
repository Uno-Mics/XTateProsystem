<?php
require_once '../inc/db.php';
require_once '../inc/functions.php';
require_once '../inc/auth.php';

// Check if user is admin
checkPermission(['admin']);

$adminId = $_SESSION['user_id'];
$adminName = $_SESSION['user_name'] ?? 'Admin';

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
            $_SESSION['success_message'] = "Property listing deleted successfully!";
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error_message'] = "Failed to delete property. Please try again.";
        }
        closeDB($conn);

        // Also delete from Cloud Firestore
        try {
            firestore_delete_property($propertyId);
        } catch (Exception $fe) {
            error_log("Firestore property deletion warning: " . $fe->getMessage());
        }

        header('Location: properties.php');
        exit();
    }
}

// Get all properties from Firestore (status 'all' so admin sees all listed properties)
$properties = firestore_get_properties(['status' => 'all']);

// Populate seller information for any properties missing it
foreach ($properties as &$prop) {
    if (empty($prop['seller_name']) && !empty($prop['seller_id'])) {
        $seller = firestore_get_user_by_id($prop['seller_id']);
        if ($seller) {
            $prop['seller_name'] = $seller['full_name'] ?? 'Unknown Seller';
            $prop['seller_email'] = $seller['email'] ?? '';
        } else {
            $prop['seller_name'] = 'Seller #' . $prop['seller_id'];
            $prop['seller_email'] = '';
        }
    }
}
unset($prop);

// Fallback to MySQL if Firestore returned no properties
if (empty($properties)) {
    $sql = "SELECT p.*, u.full_name as seller_name, u.email as seller_email,
            (SELECT image_path FROM property_images WHERE property_id = p.id AND is_primary = 1 LIMIT 1) as primary_image
            FROM properties p
            JOIN users u ON p.seller_id = u.id
            ORDER BY p.created_at DESC";
    $properties = fetchAll($sql);
}

// Calculate summary stats
$totalProperties = count($properties);
$activeCount = 0;
$totalPortfolioValue = 0;
foreach ($properties as $p) {
    if (($p['status'] ?? '') === 'active') {
        $activeCount++;
        $totalPortfolioValue += floatval($p['price'] ?? 0);
    }
}

// Pending reports count for sidebar badge
$sql = "SELECT COUNT(*) as count FROM reports WHERE status = 'pending'";
$pendingReportsResult = fetchOne($sql);
$pendingReports = $pendingReportsResult ? $pendingReportsResult['count'] : 0;

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
                        <i data-lucide="shield" style="width:13px;height:13px;"></i> Super Admin
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
                        <h1 class="db-hero-title">Property Management</h1>
                        <p class="db-hero-desc">Supervise listed real estate inventory, seller ownership, and publication status</p>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="stat-pill stat-pill--total">
                            <i data-lucide="home" style="width:14px;height:14px;"></i>
                            <span><?= $totalProperties ?> Properties</span>
                        </span>
                        <span class="stat-pill stat-pill--active">
                            <i data-lucide="check-circle" style="width:14px;height:14px;"></i>
                            <span><?= $activeCount ?> Active</span>
                        </span>
                        <span class="stat-pill stat-pill--value">
                            <i data-lucide="wallet" style="width:14px;height:14px;"></i>
                            <span><?= formatCurrency($totalPortfolioValue) ?> Total Value</span>
                        </span>
                    </div>
                </div>

                <!-- Session Flash Messages -->
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="modern-alert modern-alert--success">
                        <i data-lucide="check-circle-2" style="width:20px;height:20px;flex-shrink:0;"></i>
                        <div class="modern-alert__content">
                            <strong>Success!</strong> <?= htmlspecialchars($_SESSION['success_message']) ?>
                        </div>
                    </div>
                    <?php unset($_SESSION['success_message']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="modern-alert modern-alert--danger">
                        <i data-lucide="alert-triangle" style="width:20px;height:20px;flex-shrink:0;"></i>
                        <div class="modern-alert__content">
                            <strong>Error!</strong> <?= htmlspecialchars($_SESSION['error_message']) ?>
                        </div>
                    </div>
                    <?php unset($_SESSION['error_message']); ?>
                <?php endif; ?>

                <!-- Bento Properties Table Card -->
                <div class="db-card table-card">
                    <div class="card-head-bar d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <i data-lucide="layers" style="width:18px;height:18px;color:#2563EB;"></i>
                            <h2 class="card-head-title">All Real Estate Listings</h2>
                        </div>
                        <span class="text-muted small fw-medium">Showing <?= $totalProperties ?> entries</span>
                    </div>

                    <?php if (empty($properties)): ?>
                        <div class="empty-state-box">
                            <div class="empty-icon-circle">
                                <i data-lucide="home"></i>
                            </div>
                            <h3>No properties found</h3>
                            <p>There are currently no property listings registered on the platform.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="modern-table">
                                <thead>
                                    <tr>
                                        <th style="width: 320px;">Property</th>
                                        <th>Seller</th>
                                        <th>Price</th>
                                        <th>Status</th>
                                        <th style="text-align: right;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($properties as $property): 
                                        $imagePath = $property['primary_image'] ?? '';
                                        if (!empty($imagePath)) {
                                            if (substr($imagePath, 0, 4) !== 'http') {
                                                $imagePath = '../uploads/properties/' . basename($imagePath);
                                            }
                                        } else {
                                            $imagePath = '../uploads/properties/default.jpg';
                                        }
                                        $status = strtolower($property['status'] ?? 'active');
                                    ?>
                                    <tr>
                                        <!-- Property Thumbnail & Info -->
                                        <td>
                                            <div class="prop-item-cell">
                                                <img src="<?= htmlspecialchars($imagePath) ?>" 
                                                     alt="Property" 
                                                     class="prop-thumb" 
                                                     onerror="this.onerror=null;this.src='../uploads/properties/default.jpg';">
                                                <div class="prop-info-wrap">
                                                    <a href="../property_details.php?id=<?= $property['id'] ?>" target="_blank" class="prop-title-link">
                                                        <span><?= htmlspecialchars($property['title']) ?></span>
                                                        <i data-lucide="external-link" style="width:12px;height:12px;"></i>
                                                    </a>
                                                    <div class="prop-meta-specs">
                                                        <span><?= (int)($property['bedrooms'] ?? 0) ?> beds</span>
                                                        <span>•</span>
                                                        <span><?= (int)($property['bathrooms'] ?? 0) ?> baths</span>
                                                        <span>•</span>
                                                        <span><?= number_format((float)($property['area'] ?? 0)) ?> sqft</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Seller Info -->
                                        <td>
                                            <div class="seller-meta-cell">
                                                <div class="seller-name-title"><?= htmlspecialchars($property['seller_name'] ?? 'Unknown') ?></div>
                                                <div class="seller-email-sub"><?= htmlspecialchars($property['seller_email'] ?? '') ?></div>
                                            </div>
                                        </td>

                                        <!-- Price -->
                                        <td>
                                            <span class="prop-price-text"><?= formatCurrency($property['price'] ?? 0) ?></span>
                                        </td>

                                        <!-- Status -->
                                        <td>
                                            <span class="status-chip <?= $status === 'active' ? 'status-chip--green' : 'status-chip--gray' ?>">
                                                <?= ucfirst($status) ?>
                                            </span>
                                        </td>

                                        <!-- Actions -->
                                        <td style="text-align: right;">
                                            <div class="d-inline-flex align-items-center gap-1">
                                                <a href="../property_details.php?id=<?= $property['id'] ?>" 
                                                   class="btn-table-action" 
                                                   target="_blank" 
                                                   title="View Property Details">
                                                    <i data-lucide="eye"></i>
                                                </a>
                                                <a href="edit_property.php?id=<?= $property['id'] ?>" 
                                                   class="btn-table-action btn-table-action--edit" 
                                                   title="Edit Property">
                                                    <i data-lucide="edit-3"></i>
                                                </a>
                                                <button type="button" 
                                                        class="btn-table-action btn-table-action--delete delete-property-btn"
                                                        data-property-id="<?= $property['id'] ?>"
                                                        data-property-title="<?= htmlspecialchars($property['title']) ?>"
                                                        title="Delete Property">
                                                    <i data-lucide="trash-2"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

            </main>
        </div>
    </div>
</div>

<!-- Modern Delete Confirmation Modal -->
<div class="modal fade" id="deletePropertyModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modern-modal">
            <div class="modal-header" style="border-bottom: 1px solid #FEE2E2; background:#FEF2F2;">
                <div class="d-flex align-items-center gap-2">
                    <div class="modal-header-icon" style="background:#FEE2E2;">
                        <i data-lucide="alert-triangle" style="width:18px;height:18px;color:#DC2626;"></i>
                    </div>
                    <h5 class="modal-title" style="color:#991B1B;">Delete Property Listing</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-4 text-center">
                <div class="mb-3">
                    <div style="width:56px;height:56px;border-radius:50%;background:#FEE2E2;color:#DC2626;display:inline-flex;align-items:center;justify-content:center;">
                        <i data-lucide="trash-2" style="width:28px;height:28px;"></i>
                    </div>
                </div>
                <h5 style="color:#0F172A;font-weight:700;margin-bottom:8px;">Are you sure?</h5>
                <p class="text-muted mb-1">Are you sure you want to delete <strong id="propertyTitle" style="color:#0F172A;"></strong>?</p>
                <p class="small text-danger mb-0">This will remove the property images, inquiries, favorites, and live listing data permanently.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-modal-cancel" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="delete_property">
                    <input type="hidden" name="property_id" id="propertyIdInput">
                    <button type="submit" class="btn-modal-danger">
                        <i data-lucide="trash-2" style="width:15px;height:15px;"></i>
                        <span>Delete Listing</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
/* ── Global layout overrides for Admin ── */
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

.stat-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.8rem;
    font-weight: 600;
    padding: 6px 14px;
    border-radius: 100px;
}
.stat-pill--total {
    background: #F1F5F9;
    color: #334155;
}
.stat-pill--active {
    background: #ECFDF5;
    color: #059669;
}
.stat-pill--value {
    background: #EFF6FF;
    color: #2563EB;
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

/* Card Header */
.card-head-bar {
    padding: 20px 24px;
    border-bottom: 1px solid #F1F5F9;
}

.card-head-title {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 1.05rem;
    font-weight: 700;
    color: #0F172A;
    margin: 0;
}

/* Modern Table */
.modern-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

.modern-table th {
    background: #F8FAFC;
    color: #64748B;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 14px 20px;
    border-bottom: 1px solid #E2E8F0;
    white-space: nowrap;
}

.modern-table td {
    padding: 16px 20px;
    border-bottom: 1px solid #F1F5F9;
    vertical-align: middle;
    font-size: 0.88rem;
}

.modern-table tr:hover td {
    background: #F8FAFC;
}

/* Property cell */
.prop-item-cell {
    display: flex;
    align-items: center;
    gap: 14px;
}

.prop-thumb {
    width: 72px;
    height: 54px;
    border-radius: 8px;
    object-fit: cover;
    border: 1px solid #E2E8F0;
    flex-shrink: 0;
}

.prop-info-wrap {
    min-width: 0;
}

.prop-title-link {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-weight: 700;
    color: #0F172A;
    font-size: 0.92rem;
    text-decoration: none;
    line-height: 1.3;
    margin-bottom: 4px;
}
.prop-title-link:hover {
    color: #2563EB;
}

.prop-meta-specs {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.76rem;
    color: #64748B;
}

/* Seller cell */
.seller-meta-cell {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.seller-name-title {
    font-weight: 600;
    color: #0F172A;
    font-size: 0.88rem;
}

.seller-email-sub {
    font-size: 0.76rem;
    color: #64748B;
}

/* Price */
.prop-price-text {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-weight: 700;
    font-size: 0.95rem;
    color: #0F172A;
}

/* Status Chips */
.status-chip {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 100px;
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: capitalize;
    white-space: nowrap;
}
.status-chip--green {
    background: #ECFDF5;
    color: #059669;
}
.status-chip--gray {
    background: #F1F5F9;
    color: #64748B;
}

/* Action buttons */
.btn-table-action {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: #F1F5F9;
    color: #475569;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: none;
    cursor: pointer;
    transition: all .15s ease;
    text-decoration: none;
}
.btn-table-action:hover {
    background: #E2E8F0;
    color: #0F172A;
}
.btn-table-action svg {
    width: 15px;
    height: 15px;
}

.btn-table-action--edit:hover {
    background: #FEF3C7;
    color: #D97706;
}

.btn-table-action--delete:hover {
    background: #FEE2E2;
    color: #DC2626;
}

.table-card,
.db-card:has(.modern-table),
.db-card:has(.table-responsive) {
    overflow: visible !important;
}

.table-responsive {
    overflow: visible !important;
    min-height: 240px;
    padding-bottom: 24px;
}

/* Empty state */
.empty-state-box {
    padding: 60px 20px;
    text-align: center;
    max-width: 480px;
    margin: 0 auto;
}

.empty-icon-circle {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: #EFF6FF;
    color: #2563EB;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 16px;
}
.empty-icon-circle svg {
    width: 30px;
    height: 30px;
}

.empty-state-box h3 {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 1.2rem;
    font-weight: 700;
    color: #0F172A;
    margin-bottom: 8px;
}

.empty-state-box p {
    font-size: 0.85rem;
    color: #64748B;
    line-height: 1.5;
    margin: 0;
}

/* Modal styling */
.modern-modal {
    border-radius: 16px;
    border: 1px solid #E2E8F0;
    box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04);
}

.modal-header-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
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

.btn-modal-danger {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #DC2626;
    color: #FFFFFF;
    font-weight: 600;
    font-size: 0.85rem;
    padding: 8px 18px;
    border-radius: 8px;
    border: none;
    box-shadow: 0 2px 8px rgba(220,38,38,0.25);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    const deleteButtons = document.querySelectorAll('.delete-property-btn');
    const deleteModalEl = document.getElementById('deletePropertyModal');
    const deleteModal = deleteModalEl ? new bootstrap.Modal(deleteModalEl) : null;

    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const propertyId = this.getAttribute('data-property-id');
            const propertyTitle = this.getAttribute('data-property-title');

            const titleEl = document.getElementById('propertyTitle');
            if (titleEl) titleEl.textContent = propertyTitle;
            const idInput = document.getElementById('propertyIdInput');
            if (idInput) idInput.value = propertyId;

            if (deleteModal) deleteModal.show();
        });
    });
});
</script>

<?php include '../inc/footer.php'; ?>
