<?php
require_once '../inc/db.php';
require_once '../inc/functions.php';
require_once '../inc/auth.php';

// Check if user is logged in and has seller role
checkPermission(['seller']);

// Get seller data
$sellerId = $_SESSION['user_id'];
$sellerName = $_SESSION['user_name'] ?? 'Seller';
$sql = "SELECT * FROM users WHERE id = ?";
$seller = fetchOne($sql, "i", [$sellerId]);

// Get inquiries for this seller's properties
$sql = "SELECT i.*, p.title as property_title, p.price, p.address, p.city, p.state,
         u.full_name as buyer_name, u.email as buyer_email, u.phone as buyer_phone,
         pi.image_path as property_image
         FROM inquiries i
         JOIN properties p ON i.property_id = p.id
         JOIN users u ON i.buyer_id = u.id
         LEFT JOIN property_images pi ON p.id = pi.property_id AND pi.is_primary = 1
         WHERE p.seller_id = ?
         ORDER BY i.created_at DESC";
$inquiries = fetchAll($sql, "i", [$sellerId]);

// Badges count
$pendingCount = getPendingInquiriesCount($sellerId);
$unreadCount = getUnreadMessagesCount($sellerId);

// Process status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $inquiryId = isset($_POST['inquiry_id']) ? intval($_POST['inquiry_id']) : 0;
    $status = isset($_POST['status']) ? sanitizeInput($_POST['status']) : '';

    if ($inquiryId > 0 && in_array($status, ['pending', 'responded', 'scheduled', 'completed', 'rejected', 'approved'])) {
        $sql = "UPDATE inquiries SET status = ?, updated_at = NOW() WHERE id = ?";
        $result = updateData($sql, "si", [$status, $inquiryId]);

        if ($result) {
            header("Location: inquiries.php?success=1");
            exit;
        }
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
                    <a href="properties.php" class="db-nav-link">
                        <i data-lucide="home"></i>
                        <span>My Properties</span>
                    </a>
                    <a href="add_property.php" class="db-nav-link">
                        <i data-lucide="plus-circle"></i>
                        <span>Add Property</span>
                    </a>
                    <a href="inquiries.php" class="db-nav-link active">
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
                        <h1 class="db-hero-title">Property Inquiries</h1>
                        <p class="db-hero-desc">Review prospect inquiries, coordinate property visits, and communicate directly with buyers</p>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="inquiries-stat-pill">
                            <i data-lucide="inbox" style="width:15px;height:15px;"></i>
                            <span><?= count($inquiries) ?> Total Leads</span>
                        </span>
                        <?php if ($pendingCount > 0): ?>
                            <span class="inquiries-stat-pill inquiries-stat-pill--pending">
                                <i data-lucide="clock" style="width:15px;height:15px;"></i>
                                <span><?= $pendingCount ?> Needs Response</span>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Alert Messages -->
                <?php if (isset($_GET['success'])): ?>
                    <div class="modern-alert modern-alert--success">
                        <i data-lucide="check-circle-2" style="width:20px;height:20px;flex-shrink:0;"></i>
                        <div class="modern-alert__content">
                            <strong>Success!</strong> Inquiry status updated successfully.
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Inquiries Bento Table Card -->
                <div class="db-card">
                    <div class="inquiries-card-header">
                        <div class="d-flex align-items-center gap-2">
                            <i data-lucide="list-filter" style="width:18px;height:18px;color:#2563EB;"></i>
                            <h2 class="inquiries-card-title">All Inquiries</h2>
                        </div>
                    </div>

                    <?php if (empty($inquiries)): ?>
                        <div class="inquiries-empty-state">
                            <div class="empty-icon-circle">
                                <i data-lucide="inbox"></i>
                            </div>
                            <h3>No inquiries yet</h3>
                            <p>You haven't received any buyer inquiries yet. When prospective buyers express interest in your listings, they will appear here in real-time.</p>
                            <a href="properties.php" class="btn-db-action btn-db-action--primary">
                                <i data-lucide="eye" style="width:16px;height:16px;"></i>
                                <span>Check Your Listings</span>
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="modern-table">
                                <thead>
                                    <tr>
                                        <th>Property</th>
                                        <th>Prospective Buyer</th>
                                        <th>Message</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th style="text-align: right;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($inquiries as $inquiry): 
                                        $imagePath = $inquiry['property_image'];
                                        if (!empty($imagePath)) {
                                            if (substr($imagePath, 0, 4) !== 'http') {
                                                $imagePath = '../uploads/properties/' . basename($imagePath);
                                                $realPath = dirname(__DIR__) . '/uploads/properties/' . basename($imagePath);
                                                if (!file_exists($realPath)) {
                                                    $imagePath = '../uploads/properties/default.jpg';
                                                }
                                            }
                                        } else {
                                            $imagePath = '../uploads/properties/default.jpg';
                                        }

                                        // Status badge styling
                                        $statusKey = strtolower($inquiry['status'] ?? 'pending');
                                        $statusClass = 'status-chip--pending';
                                        if ($statusKey === 'responded') $statusClass = 'status-chip--blue';
                                        if ($statusKey === 'scheduled') $statusClass = 'status-chip--purple';
                                        if ($statusKey === 'completed') $statusClass = 'status-chip--gray';
                                        if ($statusKey === 'approved') $statusClass = 'status-chip--green';
                                        if ($statusKey === 'rejected') $statusClass = 'status-chip--red';

                                        $buyerInitials = strtoupper(substr($inquiry['buyer_name'] ?? 'B', 0, 1));
                                    ?>
                                        <tr>
                                            <!-- Property -->
                                            <td>
                                                <div class="prop-lead-cell">
                                                    <img src="<?= htmlspecialchars($imagePath) ?>" alt="Property" class="prop-lead-thumb">
                                                    <div>
                                                        <div class="prop-lead-title" title="<?= htmlspecialchars($inquiry['property_title']) ?>">
                                                            <?= htmlspecialchars($inquiry['property_title']) ?>
                                                        </div>
                                                        <div class="prop-lead-price"><?= formatCurrency($inquiry['price']) ?></div>
                                                    </div>
                                                </div>
                                            </td>

                                            <!-- Buyer -->
                                            <td>
                                                <div class="buyer-lead-cell">
                                                    <div class="buyer-avatar"><?= $buyerInitials ?></div>
                                                    <div>
                                                        <div class="buyer-name"><?= htmlspecialchars($inquiry['buyer_name']) ?></div>
                                                        <div class="buyer-contact"><?= htmlspecialchars($inquiry['buyer_email']) ?></div>
                                                        <?php if (!empty($inquiry['buyer_phone'])): ?>
                                                            <div class="buyer-contact"><?= htmlspecialchars($inquiry['buyer_phone']) ?></div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>

                                            <!-- Message Snippet -->
                                            <td>
                                                <div class="message-snippet-box">
                                                    <p class="message-snippet-text"><?= htmlspecialchars(mb_strimwidth($inquiry['message'], 0, 55, '...')) ?></p>
                                                    <button type="button" class="btn-view-msg" data-bs-toggle="modal" data-bs-target="#messageModal-<?= $inquiry['id'] ?>">
                                                        Read Full <i data-lucide="chevron-right" style="width:13px;height:13px;"></i>
                                                    </button>
                                                </div>
                                            </td>

                                            <!-- Date -->
                                            <td>
                                                <div class="inquiry-date-cell">
                                                    <i data-lucide="calendar" style="width:14px;height:14px;color:#94A3B8;"></i>
                                                    <span><?= date('M d, Y', strtotime($inquiry['created_at'])) ?></span>
                                                </div>
                                                <div class="inquiry-time-cell"><?= date('h:i A', strtotime($inquiry['created_at'])) ?></div>
                                            </td>

                                            <!-- Status -->
                                            <td>
                                                <span class="status-chip <?= $statusClass ?>">
                                                    <?= ucfirst(htmlspecialchars($inquiry['status'])) ?>
                                                </span>
                                            </td>

                                            <!-- Actions -->
                                            <td style="text-align: right;">
                                                <div class="d-inline-flex align-items-center gap-1">
                                                    <!-- Status Dropdown -->
                                                    <div class="dropdown">
                                                        <button class="btn-action-icon" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Change Status">
                                                            <i data-lucide="more-horizontal"></i>
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-end modern-dropdown">
                                                            <li class="dropdown-header">Update Status</li>
                                                            <li>
                                                                <form method="POST" action="inquiries.php">
                                                                    <input type="hidden" name="action" value="update_status">
                                                                    <input type="hidden" name="inquiry_id" value="<?= $inquiry['id'] ?>">
                                                                    <input type="hidden" name="status" value="responded">
                                                                    <button type="submit" class="dropdown-item">
                                                                        <i data-lucide="mail-check" class="item-icon"></i> Mark Responded
                                                                    </button>
                                                                </form>
                                                            </li>
                                                            <li>
                                                                <form method="POST" action="inquiries.php">
                                                                    <input type="hidden" name="action" value="update_status">
                                                                    <input type="hidden" name="inquiry_id" value="<?= $inquiry['id'] ?>">
                                                                    <input type="hidden" name="status" value="scheduled">
                                                                    <button type="submit" class="dropdown-item">
                                                                        <i data-lucide="calendar-check" class="item-icon"></i> Mark Scheduled
                                                                    </button>
                                                                </form>
                                                            </li>
                                                            <li>
                                                                <form method="POST" action="inquiries.php">
                                                                    <input type="hidden" name="action" value="update_status">
                                                                    <input type="hidden" name="inquiry_id" value="<?= $inquiry['id'] ?>">
                                                                    <input type="hidden" name="status" value="completed">
                                                                    <button type="submit" class="dropdown-item">
                                                                        <i data-lucide="check-check" class="item-icon"></i> Mark Completed
                                                                    </button>
                                                                </form>
                                                            </li>
                                                            <li><hr class="dropdown-divider"></li>
                                                            <li>
                                                                <form method="POST" action="inquiries.php">
                                                                    <input type="hidden" name="action" value="update_status">
                                                                    <input type="hidden" name="inquiry_id" value="<?= $inquiry['id'] ?>">
                                                                    <input type="hidden" name="status" value="approved">
                                                                    <button type="submit" class="dropdown-item text-success">
                                                                        <i data-lucide="check-circle" class="item-icon"></i> Approve Inquiry
                                                                    </button>
                                                                </form>
                                                            </li>
                                                            <li>
                                                                <form method="POST" action="inquiries.php">
                                                                    <input type="hidden" name="action" value="update_status">
                                                                    <input type="hidden" name="inquiry_id" value="<?= $inquiry['id'] ?>">
                                                                    <input type="hidden" name="status" value="rejected">
                                                                    <button type="submit" class="dropdown-item text-danger">
                                                                        <i data-lucide="x-circle" class="item-icon"></i> Reject Inquiry
                                                                    </button>
                                                                </form>
                                                            </li>
                                                        </ul>
                                                    </div>

                                                    <!-- Direct Message Link -->
                                                    <a href="messages.php?user=<?= $inquiry['buyer_id'] ?>" class="btn-action-icon btn-action-icon--chat" title="Message Buyer">
                                                        <i data-lucide="message-square"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>

                                        <!-- Message Details Modal -->
                                        <div class="modal fade" id="messageModal-<?= $inquiry['id'] ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content modern-modal">
                                                    <div class="modal-header modern-modal__header">
                                                        <div class="d-flex align-items-center gap-2">
                                                            <div class="modal-icon-circle">
                                                                <i data-lucide="mail"></i>
                                                            </div>
                                                            <div>
                                                                <h5 class="modal-title modern-modal__title">Inquiry Details</h5>
                                                                <span class="modern-modal__sub">Received on <?= formatDateTime($inquiry['created_at']) ?></span>
                                                            </div>
                                                        </div>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body modern-modal__body">
                                                        <!-- Property mini-card -->
                                                        <div class="modal-property-card">
                                                            <div class="fw-bold text-dark mb-1"><?= htmlspecialchars($inquiry['property_title']) ?></div>
                                                            <div class="small text-muted mb-1"><?= htmlspecialchars($inquiry['address'] . ', ' . $inquiry['city'] . ', ' . $inquiry['state']) ?></div>
                                                            <div class="fw-bold text-primary"><?= formatCurrency($inquiry['price']) ?></div>
                                                        </div>

                                                        <!-- Buyer Info -->
                                                        <div class="modal-buyer-section">
                                                            <div class="buyer-avatar modal-avatar"><?= $buyerInitials ?></div>
                                                            <div>
                                                                <div class="fw-bold text-dark"><?= htmlspecialchars($inquiry['buyer_name']) ?></div>
                                                                <div class="small text-muted"><?= htmlspecialchars($inquiry['buyer_email']) ?></div>
                                                                <?php if (!empty($inquiry['buyer_phone'])): ?>
                                                                    <div class="small text-muted"><?= htmlspecialchars($inquiry['buyer_phone']) ?></div>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>

                                                        <!-- Message text -->
                                                        <div class="modal-message-box">
                                                            <div class="modal-message-label">Prospect Message:</div>
                                                            <div class="modal-message-content">
                                                                <?= nl2br(htmlspecialchars($inquiry['message'])) ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer modern-modal__footer">
                                                        <button type="button" class="btn-db-action btn-db-action--secondary" data-bs-dismiss="modal">Close</button>
                                                        <a href="messages.php?user=<?= $inquiry['buyer_id'] ?>" class="btn-db-action btn-db-action--primary">
                                                            <i data-lucide="message-square" style="width:16px;height:16px;"></i>
                                                            <span>Start Chat with Buyer</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

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

.inquiries-stat-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.8rem;
    font-weight: 600;
    padding: 6px 14px;
    border-radius: 100px;
    background: #F1F5F9;
    color: #334155;
}
.inquiries-stat-pill--pending {
    background: #FEF3C7;
    color: #D97706;
}

/* Actions buttons */
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
.modern-alert--success {
    background: #F0FDF4;
    border: 1px solid #86EFAC;
    color: #166534;
}

/* Inquiries Card */
.inquiries-card-header {
    padding: 20px 24px;
    border-bottom: 1px solid #F1F5F9;
}

.inquiries-card-title {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 1.05rem;
    font-weight: 700;
    color: #0F172A;
    margin: 0;
}

/* Empty State */
.inquiries-empty-state {
    padding: 60px 20px;
    text-align: center;
    max-width: 500px;
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
    width: 32px;
    height: 32px;
}

.inquiries-empty-state h3 {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 1.25rem;
    font-weight: 700;
    color: #0F172A;
    margin-bottom: 8px;
}

.inquiries-empty-state p {
    font-size: 0.88rem;
    color: #64748B;
    line-height: 1.5;
    margin-bottom: 20px;
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
.prop-lead-cell {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 200px;
}

.prop-lead-thumb {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    object-fit: cover;
    background: #E2E8F0;
    flex-shrink: 0;
}

.prop-lead-title {
    font-weight: 600;
    color: #0F172A;
    font-size: 0.88rem;
    max-width: 160px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.prop-lead-price {
    font-size: 0.8rem;
    font-weight: 600;
    color: #059669;
}

/* Buyer cell */
.buyer-lead-cell {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 180px;
}

.buyer-avatar {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: linear-gradient(135deg, #3B82F6, #6366F1);
    color: #FFFFFF;
    font-weight: 700;
    font-size: 0.95rem;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.buyer-name {
    font-weight: 600;
    color: #0F172A;
    font-size: 0.88rem;
}

.buyer-contact {
    font-size: 0.78rem;
    color: #64748B;
}

/* Message Snippet cell */
.message-snippet-box {
    max-width: 220px;
}

.message-snippet-text {
    margin: 0 0 4px;
    font-size: 0.82rem;
    color: #475569;
    line-height: 1.4;
}

.btn-view-msg {
    background: none;
    border: none;
    padding: 0;
    font-size: 0.75rem;
    font-weight: 600;
    color: #2563EB;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 2px;
}
.btn-view-msg:hover {
    text-decoration: underline;
}

/* Date cell */
.inquiry-date-cell {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.82rem;
    font-weight: 500;
    color: #334155;
    white-space: nowrap;
}

.inquiry-time-cell {
    font-size: 0.72rem;
    color: #94A3B8;
    padding-left: 20px;
}

/* Status Chips */
.status-chip {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 100px;
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: capitalize;
    letter-spacing: 0.02em;
    white-space: nowrap;
}

.status-chip--pending {
    background: #FEF3C7;
    color: #D97706;
}
.status-chip--blue {
    background: #EFF6FF;
    color: #2563EB;
}
.status-chip--purple {
    background: #F3E8FF;
    color: #7C3AED;
}
.status-chip--gray {
    background: #F1F5F9;
    color: #475569;
}
.status-chip--green {
    background: #ECFDF5;
    color: #059669;
}
.status-chip--red {
    background: #FEE2E2;
    color: #DC2626;
}

/* Action Icons */
.btn-action-icon {
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
.btn-action-icon:hover {
    background: #E2E8F0;
    color: #0F172A;
}
.btn-action-icon svg {
    width: 16px;
    height: 16px;
}

.btn-action-icon--chat {
    background: #EFF6FF;
    color: #2563EB;
}
.btn-action-icon--chat:hover {
    background: #DBEAFE;
    color: #1D4ED8;
}

/* Dropdown Menu */
.modern-dropdown {
    border-radius: 12px;
    border: 1px solid #E2E8F0;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
    padding: 8px;
    min-width: 190px;
}

.modern-dropdown .dropdown-header {
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #94A3B8;
    padding: 6px 12px 4px;
}

.modern-dropdown .dropdown-item {
    font-size: 0.82rem;
    padding: 8px 12px;
    border-radius: 8px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 8px;
}

.modern-dropdown .dropdown-item .item-icon {
    width: 15px;
    height: 15px;
}

.modern-dropdown .dropdown-item:hover {
    background: #F8FAFC;
}

/* Modern Modal */
.modern-modal {
    border-radius: 20px;
    border: 1px solid #E2E8F0;
    overflow: hidden;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}

.modern-modal__header {
    padding: 20px 24px;
    background: #FFFFFF;
    border-bottom: 1px solid #F1F5F9;
}

.modal-icon-circle {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: #EFF6FF;
    color: #2563EB;
    display: flex;
    align-items: center;
    justify-content: center;
}
.modal-icon-circle svg {
    width: 20px;
    height: 20px;
}

.modern-modal__title {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 1.1rem;
    font-weight: 700;
    color: #0F172A;
    margin: 0;
}

.modern-modal__sub {
    font-size: 0.78rem;
    color: #64748B;
}

.modern-modal__body {
    padding: 24px;
    background: #FFFFFF;
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.modal-property-card {
    background: #F8FAFC;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    padding: 14px 16px;
}

.modal-buyer-section {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 4px 0;
}

.modal-avatar {
    width: 44px;
    height: 44px;
    font-size: 1.1rem;
}

.modal-message-box {
    background: #F8FAFC;
    border-left: 3px solid #2563EB;
    padding: 14px 16px;
    border-radius: 0 12px 12px 0;
}

.modal-message-label {
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #64748B;
    margin-bottom: 6px;
}

.modal-message-content {
    font-size: 0.88rem;
    color: #1E293B;
    line-height: 1.6;
}

.modern-modal__footer {
    padding: 16px 24px;
    background: #F8FAFC;
    border-top: 1px solid #F1F5F9;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});
</script>

<?php include '../inc/footer.php'; ?>