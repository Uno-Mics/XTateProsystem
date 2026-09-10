<?php
require_once '../inc/db.php';
require_once '../inc/functions.php';
require_once '../inc/auth.php';

// Check if user is logged in and has seller role
checkPermission(['seller']);

// Handle property deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_property') {
    $propertyId = isset($_POST['property_id']) ? intval($_POST['property_id']) : 0;
    $sellerId = $_SESSION['user_id'];

    if ($propertyId > 0) {
        // Verify the property belongs to this seller
        $sql = "SELECT * FROM properties WHERE id = ? AND seller_id = ?";
        $property = fetchOne($sql, "ii", [$propertyId, $sellerId]);

        if ($property) {
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
                $conn->query("DELETE FROM properties WHERE id = $propertyId AND seller_id = $sellerId");

                $conn->commit();
                $_SESSION['success_message'] = "Property deleted successfully!";
            } catch (Exception $e) {
                $conn->rollback();
                $_SESSION['error_message'] = "Failed to delete property. Please try again.";
            }
            closeDB($conn);
        }
    }

    header("Location: dashboard.php");
    exit;
}

// Get seller data
$sellerId = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = ?";
$seller = fetchOne($sql, "i", [$sellerId]);

// Get property count
$sql = "SELECT COUNT(*) as count FROM properties WHERE seller_id = ?";
$propertiesCount = fetchOne($sql, "i", [$sellerId])['count'];

// Get active inquiries count
$sql = "SELECT COUNT(*) as count FROM inquiries i 
        JOIN properties p ON i.property_id = p.id 
        WHERE p.seller_id = ? AND i.status = 'pending'";
$pendingInquiriesCount = fetchOne($sql, "i", [$sellerId])['count'];

// Get unread messages count
$unreadCount = getUnreadMessagesCount($sellerId);

// Get recent properties
$sql = "SELECT p.*, pt.name as property_type, 
        (SELECT image_path FROM property_images WHERE property_id = p.id AND is_primary = 1 LIMIT 1) as primary_image
        FROM properties p 
        JOIN property_types pt ON p.property_type_id = pt.id 
        WHERE p.seller_id = ? 
        ORDER BY p.created_at DESC LIMIT 5";
$recentProperties = fetchAll($sql, "i", [$sellerId]);

// Get recent inquiries
$sql = "SELECT i.*, p.title as property_title, p.price, 
        (SELECT image_path FROM property_images WHERE property_id = p.id AND is_primary = 1 LIMIT 1) as property_image,
        u.full_name as buyer_name, u.email as buyer_email
        FROM inquiries i
        JOIN properties p ON i.property_id = p.id
        JOIN users u ON i.buyer_id = u.id
        WHERE p.seller_id = ? 
        ORDER BY i.created_at DESC LIMIT 5";
$recentInquiries = fetchAll($sql, "i", [$sellerId]);

include '../inc/header.php';
?>

<style>
/* ============================================================
   SELLER DASHBOARD — Modern Minimalist + Bento Grid
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

/* ── Bento Metrics ── */
.db-metrics-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
}

.db-metric-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.05);
    transition: transform .2s, box-shadow .2s;
}

.db-metric-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(15, 23, 42, 0.08);
}

.db-metric-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.db-metric-blue   { background: #EFF6FF; color: #2563EB; }
.db-metric-amber  { background: #FFFBEB; color: #D97706; }
.db-metric-purple { background: #FAF5FF; color: #7C3AED; }
.db-metric-emerald{ background: #ECFDF5; color: #059669; }

.db-metric-info {
    min-width: 0;
}

.db-metric-label {
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #64748B;
    margin-bottom: 3px;
}

.db-metric-value {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 1.55rem;
    font-weight: 800;
    color: #0F172A;
    line-height: 1.15;
}

/* ── Panels ── */
.db-panel {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.05);
}

.db-panel-head {
    padding: 18px 22px;
    border-bottom: 1px solid #F1F5F9;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.db-panel-title {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 1rem;
    font-weight: 700;
    color: #0F172A;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

/* ── Modern Tables ── */
.db-table-wrap {
    overflow-x: auto;
}

.db-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.88rem;
}

.db-table th {
    text-align: left;
    padding: 12px 16px;
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #64748B;
    background: #F8FAFC;
    border-bottom: 1px solid #E2E8F0;
}

.db-table td {
    padding: 14px 16px;
    border-bottom: 1px solid #F1F5F9;
    color: #334155;
    vertical-align: middle;
}

.db-table tr:hover td {
    background: #F8FAFC;
}

.db-prop-cell {
    display: flex;
    align-items: center;
    gap: 12px;
}

.db-prop-img {
    width: 50px;
    height: 50px;
    border-radius: 8px;
    object-fit: cover;
    flex-shrink: 0;
    background: #E2E8F0;
}

.db-prop-title {
    font-weight: 600;
    color: #0F172A;
    margin: 0 0 2px;
    max-width: 240px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.db-price-pill {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-weight: 700;
    color: #2563EB;
}

/* Status Chips */
.db-status-chip {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 10px;
    border-radius: 100px;
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.db-status-pending  { background: #FEF3C7; color: #D97706; }
.db-status-approved { background: #ECFDF5; color: #059669; }
.db-status-rejected { background: #FEE2E2; color: #DC2626; }
.db-status-active   { background: #ECFDF5; color: #059669; }
.db-status-inactive { background: #F1F5F9; color: #64748B; }

/* Table Action Buttons */
.db-action-btn {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #E2E8F0;
    background: #FFFFFF;
    color: #64748B;
    text-decoration: none;
    transition: all .15s;
    cursor: pointer;
    font-size: 0;
}

.db-action-btn:hover {
    color: #2563EB;
    border-color: #BFDBFE;
    background: #EFF6FF;
}

.db-action-btn--approve:hover {
    color: #059669;
    border-color: #A7F3D0;
    background: #ECFDF5;
}

.db-action-btn--delete:hover,
.db-action-btn--reject:hover {
    color: #DC2626;
    border-color: #FECACA;
    background: #FEF2F2;
}

/* ── Responsive ── */
@media (max-width: 991.98px) {
    .db-layout {
        grid-template-columns: 1fr;
    }
    .db-metrics-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 575.98px) {
    .db-wrap { padding: 18px 0 40px; }
    .db-metrics-grid {
        grid-template-columns: 1fr;
    }
    .db-hero-banner {
        flex-direction: column;
        align-items: flex-start;
    }
    .db-hero-banner .db-btn {
        width: 100%;
        justify-content: center;
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
                        <a href="dashboard.php" class="db-nav-link active">
                            <i data-lucide="layout-dashboard" style="width:17px;height:17px;"></i>
                            <span>Dashboard</span>
                        </a>
                        <a href="properties.php" class="db-nav-link">
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
                            <?php if ($pendingInquiriesCount > 0): ?>
                                <span class="db-nav-badge"><?= $pendingInquiriesCount ?></span>
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
                        <h1 class="db-hero-title">Welcome back, <?= htmlspecialchars($seller['full_name']) ?> 👋</h1>
                        <p class="db-hero-sub">Manage your listings, reply to prospective buyers, and track inquiries.</p>
                    </div>
                    <div>
                        <a href="add_property.php" class="db-btn db-btn-primary">
                            <i data-lucide="plus" style="width:15px;height:15px;"></i> Add New Property
                        </a>
                    </div>
                </div>

                <!-- Bento Metrics -->
                <div class="db-metrics-grid">
                    <div class="db-metric-card">
                        <div class="db-metric-icon db-metric-blue">
                            <i data-lucide="home" style="width:22px;height:22px;"></i>
                        </div>
                        <div class="db-metric-info">
                            <div class="db-metric-label">Properties Listed</div>
                            <div class="db-metric-value"><?= $propertiesCount ?></div>
                        </div>
                    </div>

                    <div class="db-metric-card">
                        <div class="db-metric-icon db-metric-amber">
                            <i data-lucide="clock" style="width:22px;height:22px;"></i>
                        </div>
                        <div class="db-metric-info">
                            <div class="db-metric-label">Pending Inquiries</div>
                            <div class="db-metric-value"><?= $pendingInquiriesCount ?></div>
                        </div>
                    </div>

                    <div class="db-metric-card">
                        <div class="db-metric-icon db-metric-purple">
                            <i data-lucide="message-square" style="width:22px;height:22px;"></i>
                        </div>
                        <div class="db-metric-info">
                            <div class="db-metric-label">Unread Messages</div>
                            <div class="db-metric-value"><?= $unreadCount ?></div>
                        </div>
                    </div>

                    <div class="db-metric-card">
                        <div class="db-metric-icon db-metric-emerald">
                            <i data-lucide="calendar" style="width:22px;height:22px;"></i>
                        </div>
                        <div class="db-metric-info">
                            <div class="db-metric-label">Member Since</div>
                            <div class="db-metric-value" style="font-size: 1.15rem;"><?= formatDateTime($seller['created_at'], 'M Y') ?></div>
                        </div>
                    </div>
                </div>

                <!-- Recent Inquiries Panel -->
                <div class="db-panel">
                    <div class="db-panel-head">
                        <h3 class="db-panel-title">
                            <i data-lucide="inbox" style="width:17px;height:17px;color:#2563EB;"></i>
                            Recent Inquiries
                        </h3>
                        <?php if (count($recentInquiries) > 0): ?>
                            <a href="inquiries.php" style="font-size: 0.82rem; font-weight: 600; color: #2563EB; text-decoration: none;">
                                View All Inquiries →
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="db-panel-body" style="padding: 0;">
                        <?php if (empty($recentInquiries)): ?>
                            <div style="padding: 32px; text-align: center; color: #94A3B8;">
                                <i data-lucide="inbox" style="width:36px;height:36px;margin-bottom:8px;color:#CBD5E1;"></i>
                                <p style="margin: 0; font-size: 0.9rem;">You haven't received any inquiries yet. List more properties to attract buyer interest.</p>
                            </div>
                        <?php else: ?>
                            <div class="db-table-wrap">
                                <table class="db-table">
                                    <thead>
                                        <tr>
                                            <th>Property</th>
                                            <th>Buyer</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                            <th style="text-align: right;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentInquiries as $inquiry): ?>
                                            <?php
                                                $imagePath = $inquiry['property_image'];
                                                if (!empty($imagePath) && substr($imagePath, 0, 4) !== 'http') {
                                                    $imagePath = '../' . ltrim($imagePath, '/');
                                                } elseif (empty($imagePath)) {
                                                    $imagePath = '../XTate-Image.png';
                                                }
                                                $st = strtolower($inquiry['status'] ?? 'pending');
                                                $chipClass = $st === 'approved' ? 'db-status-approved' : ($st === 'rejected' ? 'db-status-rejected' : 'db-status-pending');
                                            ?>
                                            <tr>
                                                <td>
                                                    <div class="db-prop-cell">
                                                        <img src="<?= htmlspecialchars($imagePath) ?>" alt="Property" class="db-prop-img" onerror="this.src='../XTate-Image.png'">
                                                        <div>
                                                            <div class="db-prop-title"><?= htmlspecialchars($inquiry['property_title']) ?></div>
                                                            <div style="font-size: 0.78rem; color: #2563EB; font-weight: 600;"><?= formatCurrency($inquiry['price']) ?></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div style="font-weight: 600; color: #0F172A;"><?= htmlspecialchars($inquiry['buyer_name']) ?></div>
                                                    <div style="font-size: 0.76rem; color: #64748B;"><?= htmlspecialchars($inquiry['buyer_email']) ?></div>
                                                </td>
                                                <td>
                                                    <span class="db-status-chip <?= $chipClass ?>">
                                                        <?= htmlspecialchars(ucfirst($st)) ?>
                                                    </span>
                                                </td>
                                                <td style="color: #64748B; font-size: 0.8rem;"><?= formatDateTime($inquiry['created_at'], 'd M Y') ?></td>
                                                <td style="text-align: right;">
                                                    <div style="display: inline-flex; gap: 6px;">
                                                        <a href="inquiries.php?id=<?= $inquiry['id'] ?>" class="db-action-btn" title="View Inquiry" data-bs-toggle="tooltip">
                                                            <i data-lucide="eye" style="width:14px;height:14px;"></i>
                                                        </a>
                                                        <?php if ($inquiry['status'] === 'pending'): ?>
                                                            <a href="javascript:void(0);" onclick="approveInquiry(<?= $inquiry['id'] ?>)" class="db-action-btn db-action-btn--approve" title="Approve" data-bs-toggle="tooltip">
                                                                <i data-lucide="check" style="width:14px;height:14px;"></i>
                                                            </a>
                                                            <a href="javascript:void(0);" onclick="rejectInquiry(<?= $inquiry['id'] ?>)" class="db-action-btn db-action-btn--reject" title="Reject" data-bs-toggle="tooltip">
                                                                <i data-lucide="x" style="width:14px;height:14px;"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                        <button class="db-action-btn db-action-btn--delete delete-inquiry-btn" 
                                                                data-inquiry-id="<?= $inquiry['id'] ?>"
                                                                title="Delete Inquiry" data-bs-toggle="tooltip">
                                                            <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
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
                </div>

                <!-- My Properties Panel -->
                <div class="db-panel">
                    <div class="db-panel-head">
                        <h3 class="db-panel-title">
                            <i data-lucide="home" style="width:17px;height:17px;color:#2563EB;"></i>
                            My Properties
                        </h3>
                        <a href="add_property.php" style="font-size: 0.82rem; font-weight: 600; color: #2563EB; text-decoration: none;">
                            + Add New Property
                        </a>
                    </div>
                    <div class="db-panel-body" style="padding: 0;">
                        <?php if (empty($recentProperties)): ?>
                            <div style="padding: 32px; text-align: center; color: #94A3B8;">
                                <i data-lucide="home" style="width:36px;height:36px;margin-bottom:8px;color:#CBD5E1;"></i>
                                <p style="margin: 0; font-size: 0.9rem;">You haven't listed any properties yet. <a href="add_property.php" style="color:#2563EB;font-weight:600;">Add your first property</a> to start attracting buyers.</p>
                            </div>
                        <?php else: ?>
                            <div class="db-table-wrap">
                                <table class="db-table">
                                    <thead>
                                        <tr>
                                            <th>Property</th>
                                            <th>Price</th>
                                            <th>Status</th>
                                            <th>Listed Date</th>
                                            <th style="text-align: right;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentProperties as $property): ?>
                                            <?php
                                                $imagePath = $property['primary_image'];
                                                if (!empty($imagePath) && substr($imagePath, 0, 4) !== 'http') {
                                                    $imagePath = '../' . ltrim($imagePath, '/');
                                                } elseif (empty($imagePath)) {
                                                    $imagePath = '../XTate-Image.png';
                                                }
                                                $isActive = ($property['status'] === 'active');
                                            ?>
                                            <tr>
                                                <td>
                                                    <div class="db-prop-cell">
                                                        <img src="<?= htmlspecialchars($imagePath) ?>" alt="Property" class="db-prop-img" onerror="this.src='../XTate-Image.png'">
                                                        <div>
                                                            <div class="db-prop-title"><?= htmlspecialchars($property['title']) ?></div>
                                                            <div style="font-size: 0.76rem; color: #64748B;">
                                                                <i data-lucide="map-pin" style="width:11px;height:11px;display:inline;"></i>
                                                                <?= htmlspecialchars($property['city']) ?>, <?= htmlspecialchars($property['state']) ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><span class="db-price-pill"><?= formatCurrency($property['price']) ?></span></td>
                                                <td>
                                                    <span class="db-status-chip <?= $isActive ? 'db-status-active' : 'db-status-inactive' ?>">
                                                        <?= ucfirst($property['status']) ?>
                                                    </span>
                                                </td>
                                                <td style="color: #64748B; font-size: 0.8rem;"><?= formatDateTime($property['created_at'], 'd M Y') ?></td>
                                                <td style="text-align: right;">
                                                    <div style="display: inline-flex; gap: 6px;">
                                                        <a href="../property_details.php?id=<?= $property['id'] ?>" class="db-action-btn" title="View Property" data-bs-toggle="tooltip">
                                                            <i data-lucide="eye" style="width:14px;height:14px;"></i>
                                                        </a>
                                                        <a href="edit_property.php?id=<?= $property['id'] ?>" class="db-action-btn" title="Edit Property" data-bs-toggle="tooltip">
                                                            <i data-lucide="edit-3" style="width:14px;height:14px;"></i>
                                                        </a>
                                                        <button class="db-action-btn db-action-btn--delete delete-property-btn" 
                                                                data-property-id="<?= $property['id'] ?>"
                                                                data-property-title="<?= htmlspecialchars($property['title'], ENT_QUOTES) ?>"
                                                                title="Delete Property" data-bs-toggle="tooltip">
                                                            <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
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
                </div>

            </main>

        </div>
    </div>
</div>

<!-- Delete Property Modal -->
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
                <form method="POST" action="dashboard.php" id="deletePropertyForm" style="display: inline;">
                    <input type="hidden" name="action" value="delete_property">
                    <input type="hidden" name="property_id" id="deletePropertyId" value="">
                    <button type="submit" class="btn btn-sm btn-danger" style="border-radius: 8px;">Delete Property</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Delete Inquiry Modal -->
<div class="modal fade" id="deleteInquiryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 16px; border: 1px solid #E2E8F0; overflow: hidden;">
            <div class="modal-header" style="background: #F8FAFC; border-bottom: 1px solid #E2E8F0;">
                <h5 class="modal-title font-weight-bold" style="font-family: 'Plus Jakarta Sans', sans-serif; font-size: 1rem; font-weight: 700;">
                    Delete Inquiry
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="padding: 20px; color: #475569; font-size: 0.9rem;">
                Are you sure you want to delete this inquiry? This action cannot be undone.
            </div>
            <div class="modal-footer" style="border-top: 1px solid #F1F5F9; padding: 14px 20px;">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal" style="border-radius: 8px;">Cancel</button>
                <button type="button" class="btn btn-sm btn-danger confirm-delete-inquiry" style="border-radius: 8px;">Delete</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') lucide.createIcons();

    // Tooltips
    const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltips.forEach(tooltip => new bootstrap.Tooltip(tooltip));

    // Delete Property handling
    const propModalEl = document.getElementById('deletePropertyModal');
    const propModal = propModalEl ? new bootstrap.Modal(propModalEl) : null;
    document.querySelectorAll('.delete-property-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const pid = this.getAttribute('data-property-id');
            const ptitle = this.getAttribute('data-property-title');
            document.getElementById('deletePropertyId').value = pid;
            document.getElementById('deletePropertyTitle').textContent = ptitle;
            if (propModal) propModal.show();
        });
    });

    // Delete Inquiry handling
    let currentInquiryBtn = null;
    const inqModalEl = document.getElementById('deleteInquiryModal');
    const inqModal = inqModalEl ? new bootstrap.Modal(inqModalEl) : null;
    document.querySelectorAll('.delete-inquiry-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            currentInquiryBtn = this;
            if (inqModal) inqModal.show();
        });
    });

    const confirmInqBtn = document.querySelector('.confirm-delete-inquiry');
    if (confirmInqBtn) {
        confirmInqBtn.addEventListener('click', function() {
            if (!currentInquiryBtn) return;
            const inqId = currentInquiryBtn.getAttribute('data-inquiry-id');
            const fd = new FormData();
            fd.append('action', 'delete_inquiry');
            fd.append('inquiry_id', inqId);

            fetch('../api/inquiries.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (inqModal) inqModal.hide();
                    if (data.success) {
                        currentInquiryBtn.closest('tr').remove();
                        if (typeof Notyf !== 'undefined') new Notyf().success('Inquiry deleted.');
                    } else {
                        if (typeof Notyf !== 'undefined') new Notyf().error(data.message || 'Failed to delete.');
                    }
                })
                .catch(() => {
                    if (inqModal) inqModal.hide();
                    if (typeof Notyf !== 'undefined') new Notyf().error('An error occurred.');
                });
        });
    }
});

// Approve Inquiry AJAX
function approveInquiry(inquiryId) {
    const fd = new FormData();
    fd.append('action', 'update_status');
    fd.append('inquiry_id', inquiryId);
    fd.append('status', 'approved');

    fetch('../api/inquiries.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                if (typeof Notyf !== 'undefined') new Notyf().success('Inquiry approved!');
                setTimeout(() => window.location.reload(), 1200);
            } else {
                if (typeof Notyf !== 'undefined') new Notyf().error(data.message || 'Error approving inquiry.');
            }
        })
        .catch(() => {
            if (typeof Notyf !== 'undefined') new Notyf().error('Server error.');
        });
}

// Reject Inquiry AJAX
function rejectInquiry(inquiryId) {
    const fd = new FormData();
    fd.append('action', 'update_status');
    fd.append('inquiry_id', inquiryId);
    fd.append('status', 'rejected');

    fetch('../api/inquiries.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                if (typeof Notyf !== 'undefined') new Notyf().success('Inquiry rejected.');
                setTimeout(() => window.location.reload(), 1200);
            } else {
                if (typeof Notyf !== 'undefined') new Notyf().error(data.message || 'Error rejecting inquiry.');
            }
        })
        .catch(() => {
            if (typeof Notyf !== 'undefined') new Notyf().error('Server error.');
        });
}
</script>

<script src="../js/auth.js"></script>
<?php include '../inc/footer.php'; ?>