<?php
require_once '../inc/db.php';
require_once '../inc/functions.php';
require_once '../inc/auth.php';

// Check if user is admin
checkPermission(['admin']);

$adminId = $_SESSION['user_id'];
$adminName = $_SESSION['user_name'] ?? 'Admin';

// Handle report status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $reportId = isset($_POST['report_id']) ? intval($_POST['report_id']) : 0;
    $status = isset($_POST['status']) ? sanitizeInput($_POST['status']) : '';
    
    if ($reportId && in_array($status, ['pending', 'reviewed', 'resolved', 'dismissed'])) {
        if ($status === 'dismissed') {
            $sql = "DELETE FROM reports WHERE id = ?";
            deleteData($sql, "i", [$reportId]);
        } else {
            $sql = "UPDATE reports SET status = ?, updated_at = NOW() WHERE id = ?";
            updateData($sql, "si", [$status, $reportId]);
        }
        header("Location: reports.php?status_updated=1");
        exit;
    }
}

// Get all reports with related information
$sql = "SELECT r.*, 
        p.title as property_title,
        u1.full_name as reporter_name,
        u2.full_name as seller_name
        FROM reports r
        LEFT JOIN properties p ON r.property_id = p.id
        LEFT JOIN users u1 ON r.reporter_id = u1.id
        LEFT JOIN users u2 ON r.seller_id = u2.id
        ORDER BY r.created_at DESC";

$reports = fetchAll($sql, "", []);

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
                        <a href="reports.php" class="db-nav-link active">
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
                        <a href="properties.php" class="db-nav-link">
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
                        <h1 class="db-hero-title">Reported Listings &amp; Sellers</h1>
                        <p class="db-hero-sub">Monitor user grievances, inappropriate listings, and moderation flags</p>
                    </div>
                    <div class="db-hero-actions">
                        <span class="stat-pill stat-pill--total">
                            <i data-lucide="flag" style="width:14px;height:14px;"></i>
                            <span><?= count($reports) ?> Total Reports</span>
                        </span>
                        <?php if ($pendingReports > 0): ?>
                            <span class="stat-pill stat-pill--pending">
                                <i data-lucide="clock" style="width:14px;height:14px;"></i>
                                <span><?= $pendingReports ?> Pending Action</span>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Alert Messages -->
                <?php if (isset($_GET['status_updated'])): ?>
                    <div class="modern-alert modern-alert--success">
                        <i data-lucide="check-circle-2" style="width:20px;height:20px;flex-shrink:0;"></i>
                        <div class="modern-alert__content">
                            <strong>Success!</strong> Report status updated successfully.
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Bento Table Card -->
                <div class="db-card table-card">
                    <div class="card-head-bar">
                        <div class="d-flex align-items-center gap-2">
                            <i data-lucide="shield-alert" style="width:18px;height:18px;color:#2563EB;"></i>
                            <h2 class="card-head-title">Moderation Queue</h2>
                        </div>
                    </div>

                    <?php if (empty($reports)): ?>
                        <div class="empty-state-box">
                            <div class="empty-icon-circle">
                                <i data-lucide="check-check"></i>
                            </div>
                            <h3>Queue is clear!</h3>
                            <p>There are no reported listings or seller accounts requiring moderation at this time.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="modern-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Reporter</th>
                                        <th>Target</th>
                                        <th>Subject</th>
                                        <th>Reason</th>
                                        <th>Status</th>
                                        <th style="text-align: right;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reports as $report): 
                                        $statusKey = strtolower($report['status'] ?? 'pending');
                                        $statusClass = 'status-chip--amber';
                                        if ($statusKey === 'reviewed') $statusClass = 'status-chip--blue';
                                        if ($statusKey === 'resolved') $statusClass = 'status-chip--green';
                                        if ($statusKey === 'dismissed') $statusClass = 'status-chip--gray';

                                        $isProperty = !empty($report['property_id']);
                                    ?>
                                        <tr>
                                            <!-- Date -->
                                            <td>
                                                <div class="date-cell">
                                                    <i data-lucide="calendar" style="width:13px;height:13px;color:#94A3B8;"></i>
                                                    <span><?= date('M d, Y', strtotime($report['created_at'])) ?></span>
                                                </div>
                                                <div class="time-sub"><?= date('h:i A', strtotime($report['created_at'])) ?></div>
                                            </td>

                                            <!-- Reporter -->
                                            <td>
                                                <div class="reporter-cell">
                                                    <div class="user-avatar-sm">
                                                        <?= strtoupper(substr($report['reporter_name'] ?? 'U', 0, 1)) ?>
                                                    </div>
                                                    <div>
                                                        <div class="reporter-name"><?= htmlspecialchars($report['reporter_name'] ?? 'Anonymous') ?></div>
                                                        <small class="text-muted">User #<?= (int)$report['reporter_id'] ?></small>
                                                    </div>
                                                </div>
                                            </td>

                                            <!-- Type Badge -->
                                            <td>
                                                <span class="target-type-badge <?= $isProperty ? 'target-type-badge--prop' : 'target-type-badge--seller' ?>">
                                                    <i data-lucide="<?= $isProperty ? 'home' : 'user' ?>" style="width:12px;height:12px;"></i>
                                                    <?= $isProperty ? 'Property' : 'Seller' ?>
                                                </span>
                                            </td>

                                            <!-- Subject -->
                                            <td>
                                                <?php if ($isProperty): ?>
                                                    <a href="../property_details.php?id=<?= $report['property_id'] ?>" target="_blank" class="subject-link">
                                                        <span><?= htmlspecialchars($report['property_title'] ?? 'View Property') ?></span>
                                                        <i data-lucide="external-link" style="width:12px;height:12px;"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="subject-text">
                                                        <?= htmlspecialchars($report['seller_name'] ?? 'Seller #' . $report['seller_id']) ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>

                                            <!-- Reason -->
                                            <td>
                                                <span class="reason-tag">
                                                    <?= htmlspecialchars($report['reason']) ?>
                                                </span>
                                            </td>

                                            <!-- Status -->
                                            <td>
                                                <span class="status-chip <?= $statusClass ?>">
                                                    <?= ucfirst(htmlspecialchars($report['status'])) ?>
                                                </span>
                                            </td>

                                            <!-- Actions -->
                                            <td style="text-align: right;">
                                                <div class="d-inline-flex align-items-center gap-1">
                                                    <?php if (!empty($report['reporter_id'])): ?>
                                                        <a href="messages.php?user=<?= $report['reporter_id'] ?>" class="btn-table-action btn-table-action--chat" title="Message Reporter">
                                                            <i data-lucide="message-square"></i>
                                                        </a>
                                                    <?php endif; ?>

                                                    <div class="dropdown">
                                                        <button class="btn-table-action" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Update Status">
                                                            <i data-lucide="more-horizontal"></i>
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-end modern-dropdown">
                                                            <li class="dropdown-header">Moderation Action</li>
                                                            <li>
                                                                <form method="POST" action="reports.php">
                                                                    <input type="hidden" name="action" value="update_status">
                                                                    <input type="hidden" name="report_id" value="<?= $report['id'] ?>">
                                                                    <input type="hidden" name="status" value="reviewed">
                                                                    <button type="submit" class="dropdown-item">
                                                                        <i data-lucide="check" class="item-icon"></i> Mark Reviewed
                                                                    </button>
                                                                </form>
                                                            </li>
                                                            <li>
                                                                <form method="POST" action="reports.php">
                                                                    <input type="hidden" name="action" value="update_status">
                                                                    <input type="hidden" name="report_id" value="<?= $report['id'] ?>">
                                                                    <input type="hidden" name="status" value="resolved">
                                                                    <button type="submit" class="dropdown-item text-success">
                                                                        <i data-lucide="check-check" class="item-icon"></i> Mark Resolved
                                                                    </button>
                                                                </form>
                                                            </li>
                                                            <li><hr class="dropdown-divider"></li>
                                                            <li>
                                                                <form method="POST" action="reports.php">
                                                                    <input type="hidden" name="action" value="update_status">
                                                                    <input type="hidden" name="report_id" value="<?= $report['id'] ?>">
                                                                    <input type="hidden" name="status" value="dismissed">
                                                                    <button type="submit" class="dropdown-item text-danger">
                                                                        <i data-lucide="trash-2" class="item-icon"></i> Dismiss & Delete
                                                                    </button>
                                                                </form>
                                                            </li>
                                                        </ul>
                                                    </div>
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
.stat-pill--pending {
    background: #FEF3C7;
    color: #D97706;
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
    width: 32px;
    height: 32px;
}

.empty-state-box h3 {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 1.25rem;
    font-weight: 700;
    color: #0F172A;
    margin-bottom: 8px;
}

.empty-state-box p {
    font-size: 0.88rem;
    color: #64748B;
    line-height: 1.5;
    margin: 0;
}

/* Table */
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

/* Date */
.date-cell {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.82rem;
    font-weight: 500;
    color: #334155;
    white-space: nowrap;
}
.time-sub {
    font-size: 0.72rem;
    color: #94A3B8;
    padding-left: 19px;
}

/* Reporter */
.reporter-cell {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 160px;
}

.user-avatar-sm {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: linear-gradient(135deg, #3B82F6, #6366F1);
    color: #FFFFFF;
    font-weight: 700;
    font-size: 0.85rem;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.reporter-name {
    font-weight: 600;
    color: #0F172A;
    font-size: 0.88rem;
}

/* Target */
.target-type-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    padding: 3px 8px;
    border-radius: 6px;
}
.target-type-badge--prop {
    background: #EFF6FF;
    color: #2563EB;
}
.target-type-badge--seller {
    background: #F3E8FF;
    color: #7C3AED;
}

.subject-link {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    color: #0F172A;
    font-weight: 600;
    text-decoration: none;
    max-width: 180px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.subject-link:hover {
    color: #2563EB;
}

.subject-text {
    font-weight: 600;
    color: #0F172A;
}

.reason-tag {
    font-size: 0.82rem;
    color: #475569;
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
.status-chip--amber {
    background: #FEF3C7;
    color: #D97706;
}
.status-chip--blue {
    background: #EFF6FF;
    color: #2563EB;
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
    width: 16px;
    height: 16px;
}

.btn-table-action--chat {
    background: #EFF6FF;
    color: #2563EB;
}
.btn-table-action--chat:hover {
    background: #DBEAFE;
    color: #1D4ED8;
}

.modern-table td .dropdown,
.dropdown {
    position: relative;
    display: inline-block;
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

/* Modern dropdown */
.modern-dropdown {
    border-radius: 12px;
    border: 1px solid #E2E8F0;
    box-shadow: 0 16px 40px -4px rgba(15, 23, 42, 0.16), 0 4px 12px -2px rgba(15, 23, 42, 0.08);
    padding: 8px;
    min-width: 195px;
    z-index: 1060;
    background: #FFFFFF;
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
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});
</script>

<?php include '../inc/footer.php'; ?>