<?php
require_once '../inc/db.php';
require_once '../inc/functions.php';
require_once '../inc/auth.php';

// Check if user is admin
checkPermission(['admin']);

$adminId = $_SESSION['user_id'];
$adminName = $_SESSION['user_name'] ?? 'Admin';

// Fetch users from Cloud Firestore (with MySQL fallback)
$users = firestore_get_users(true);
if (empty($users)) {
    $sql = "SELECT * FROM users WHERE role != 'admin' ORDER BY role, created_at DESC";
    $users = fetchAll($sql);
}

// Filter buyers and sellers
$buyers = array_filter($users, function($u) { return ($u['role'] ?? '') === 'buyer'; });
$sellers = array_filter($users, function($u) { return ($u['role'] ?? '') === 'seller'; });

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
                        <a href="users.php" class="db-nav-link active">
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
                        <h1 class="db-hero-title">User Management</h1>
                        <p class="db-hero-desc">Directory of registered platform buyers and verified sellers</p>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="stat-pill stat-pill--total">
                            <i data-lucide="users" style="width:14px;height:14px;"></i>
                            <span><?= count($users) ?> Total Accounts</span>
                        </span>
                        <span class="stat-pill stat-pill--buyers">
                            <i data-lucide="shopping-bag" style="width:14px;height:14px;"></i>
                            <span><?= count($buyers) ?> Buyers</span>
                        </span>
                        <span class="stat-pill stat-pill--sellers">
                            <i data-lucide="building-2" style="width:14px;height:14px;"></i>
                            <span><?= count($sellers) ?> Sellers</span>
                        </span>
                    </div>
                </div>

                <!-- Bento User Table Card with Custom Tabs -->
                <div class="db-card table-card">
                    <div class="card-head-bar d-flex align-items-center justify-content-between flex-wrap gap-3">
                        <div class="modern-tabs-pill">
                            <button type="button" class="tab-pill-btn active" data-bs-target="#buyersPane" data-bs-toggle="tab">
                                <i data-lucide="shopping-bag" style="width:15px;height:15px;"></i>
                                <span>Buyers</span>
                                <span class="tab-count"><?= count($buyers) ?></span>
                            </button>
                            <button type="button" class="tab-pill-btn" data-bs-target="#sellersPane" data-bs-toggle="tab">
                                <i data-lucide="building-2" style="width:15px;height:15px;"></i>
                                <span>Sellers</span>
                                <span class="tab-count"><?= count($sellers) ?></span>
                            </button>
                        </div>
                    </div>

                    <div class="tab-content">
                        <!-- Buyers Tab Pane -->
                        <div class="tab-pane fade show active" id="buyersPane">
                            <?php if (empty($buyers)): ?>
                                <div class="empty-state-box">
                                    <div class="empty-icon-circle">
                                        <i data-lucide="users"></i>
                                    </div>
                                    <h3>No buyers registered</h3>
                                    <p>When buyers create an account, they will be listed here.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="modern-table">
                                        <thead>
                                            <tr>
                                                <th>User</th>
                                                <th>Contact</th>
                                                <th>Joined Date</th>
                                                <th>Status</th>
                                                <th style="text-align: right;">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($buyers as $user): 
                                                $initial = strtoupper(substr($user['full_name'] ?? 'B', 0, 1));
                                                $status = strtolower($user['status'] ?? 'active');
                                            ?>
                                            <tr>
                                                <!-- User Name & Avatar -->
                                                <td>
                                                    <div class="user-meta-cell">
                                                        <div class="user-avatar-circle" style="background: linear-gradient(135deg, #3B82F6, #1D4ED8);">
                                                            <?= $initial ?>
                                                        </div>
                                                        <div>
                                                            <div class="user-name-title"><?= htmlspecialchars($user['full_name']) ?></div>
                                                            <span class="user-id-sub">ID #<?= htmlspecialchars($user['id']) ?></span>
                                                        </div>
                                                    </div>
                                                </td>

                                                <!-- Contact Info -->
                                                <td>
                                                    <div class="contact-meta-group">
                                                        <div class="contact-row">
                                                            <i data-lucide="mail" style="width:13px;height:13px;color:#94A3B8;"></i>
                                                            <span><?= htmlspecialchars($user['email']) ?></span>
                                                        </div>
                                                        <?php if (!empty($user['phone'])): ?>
                                                            <div class="contact-row phone-sub">
                                                                <i data-lucide="phone" style="width:13px;height:13px;color:#94A3B8;"></i>
                                                                <span><?= htmlspecialchars($user['phone']) ?></span>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>

                                                <!-- Joined Date -->
                                                <td>
                                                    <div class="date-cell">
                                                        <i data-lucide="calendar" style="width:13px;height:13px;color:#94A3B8;"></i>
                                                        <span><?= date('M d, Y', strtotime($user['created_at'])) ?></span>
                                                    </div>
                                                    <div class="time-sub"><?= date('h:i A', strtotime($user['created_at'])) ?></div>
                                                </td>

                                                <!-- Status -->
                                                <td>
                                                    <span class="status-chip badge <?= $status === 'active' ? 'status-chip--green' : 'status-chip--red' ?>">
                                                        <?= ucfirst($status) ?>
                                                    </span>
                                                </td>

                                                <!-- Actions -->
                                                <td style="text-align: right;">
                                                    <div class="dropdown">
                                                        <button class="btn-table-action" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Account Options">
                                                            <i data-lucide="more-horizontal"></i>
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-end modern-dropdown">
                                                            <li class="dropdown-header">Manage User</li>
                                                            <li>
                                                                <button class="dropdown-item edit-user-btn"
                                                                        data-user-id="<?= $user['id'] ?>"
                                                                        data-name="<?= htmlspecialchars($user['full_name']) ?>"
                                                                        data-email="<?= htmlspecialchars($user['email']) ?>"
                                                                        data-phone="<?= htmlspecialchars($user['phone'] ?? '') ?>"
                                                                        data-role="<?= $user['role'] ?>">
                                                                    <i data-lucide="edit-3" class="item-icon"></i> Edit Account
                                                                </button>
                                                            </li>
                                                            <li>
                                                                <button class="dropdown-item status-toggle-btn" 
                                                                        data-user-id="<?= $user['id'] ?>" 
                                                                        data-current-status="<?= $status ?>">
                                                                    <i data-lucide="power" class="item-icon"></i>
                                                                    <?= $status === 'active' ? 'Deactivate Account' : 'Activate Account' ?>
                                                                </button>
                                                            </li>
                                                            <li><hr class="dropdown-divider"></li>
                                                            <li>
                                                                <button class="dropdown-item text-danger delete-user-btn"
                                                                        data-user-id="<?= $user['id'] ?>"
                                                                        data-name="<?= htmlspecialchars($user['full_name']) ?>">
                                                                    <i data-lucide="trash-2" class="item-icon"></i> Delete Account
                                                                </button>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Sellers Tab Pane -->
                        <div class="tab-pane fade" id="sellersPane">
                            <?php if (empty($sellers)): ?>
                                <div class="empty-state-box">
                                    <div class="empty-icon-circle">
                                        <i data-lucide="building-2"></i>
                                    </div>
                                    <h3>No sellers registered</h3>
                                    <p>When property sellers register, they will be displayed here.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="modern-table">
                                        <thead>
                                            <tr>
                                                <th>Seller</th>
                                                <th>Contact</th>
                                                <th>Joined Date</th>
                                                <th>Status</th>
                                                <th style="text-align: right;">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($sellers as $user): 
                                                $initial = strtoupper(substr($user['full_name'] ?? 'S', 0, 1));
                                                $status = strtolower($user['status'] ?? 'active');
                                            ?>
                                            <tr>
                                                <!-- User Name & Avatar -->
                                                <td>
                                                    <div class="user-meta-cell">
                                                        <div class="user-avatar-circle" style="background: linear-gradient(135deg, #8B5CF6, #6D28D9);">
                                                            <?= $initial ?>
                                                        </div>
                                                        <div>
                                                            <div class="user-name-title"><?= htmlspecialchars($user['full_name']) ?></div>
                                                            <span class="user-id-sub">Seller ID #<?= htmlspecialchars($user['id']) ?></span>
                                                        </div>
                                                    </div>
                                                </td>

                                                <!-- Contact Info -->
                                                <td>
                                                    <div class="contact-meta-group">
                                                        <div class="contact-row">
                                                            <i data-lucide="mail" style="width:13px;height:13px;color:#94A3B8;"></i>
                                                            <span><?= htmlspecialchars($user['email']) ?></span>
                                                        </div>
                                                        <?php if (!empty($user['phone'])): ?>
                                                            <div class="contact-row phone-sub">
                                                                <i data-lucide="phone" style="width:13px;height:13px;color:#94A3B8;"></i>
                                                                <span><?= htmlspecialchars($user['phone']) ?></span>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>

                                                <!-- Joined Date -->
                                                <td>
                                                    <div class="date-cell">
                                                        <i data-lucide="calendar" style="width:13px;height:13px;color:#94A3B8;"></i>
                                                        <span><?= date('M d, Y', strtotime($user['created_at'])) ?></span>
                                                    </div>
                                                    <div class="time-sub"><?= date('h:i A', strtotime($user['created_at'])) ?></div>
                                                </td>

                                                <!-- Status -->
                                                <td>
                                                    <span class="status-chip badge <?= $status === 'active' ? 'status-chip--green' : 'status-chip--red' ?>">
                                                        <?= ucfirst($status) ?>
                                                    </span>
                                                </td>

                                                <!-- Actions -->
                                                <td style="text-align: right;">
                                                    <div class="dropdown">
                                                        <button class="btn-table-action" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Account Options">
                                                            <i data-lucide="more-horizontal"></i>
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-end modern-dropdown">
                                                            <li class="dropdown-header">Manage Seller</li>
                                                            <li>
                                                                <button class="dropdown-item edit-user-btn"
                                                                        data-user-id="<?= $user['id'] ?>"
                                                                        data-name="<?= htmlspecialchars($user['full_name']) ?>"
                                                                        data-email="<?= htmlspecialchars($user['email']) ?>"
                                                                        data-phone="<?= htmlspecialchars($user['phone'] ?? '') ?>"
                                                                        data-role="<?= $user['role'] ?>">
                                                                    <i data-lucide="edit-3" class="item-icon"></i> Edit Account
                                                                </button>
                                                            </li>
                                                            <li>
                                                                <button class="dropdown-item status-toggle-btn" 
                                                                        data-user-id="<?= $user['id'] ?>" 
                                                                        data-current-status="<?= $status ?>">
                                                                    <i data-lucide="power" class="item-icon"></i>
                                                                    <?= $status === 'active' ? 'Deactivate Account' : 'Activate Account' ?>
                                                                </button>
                                                            </li>
                                                            <li><hr class="dropdown-divider"></li>
                                                            <li>
                                                                <button class="dropdown-item text-danger delete-user-btn"
                                                                        data-user-id="<?= $user['id'] ?>"
                                                                        data-name="<?= htmlspecialchars($user['full_name']) ?>">
                                                                    <i data-lucide="trash-2" class="item-icon"></i> Delete Account
                                                                </button>
                                                            </li>
                                                        </ul>
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
                </div>

            </main>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modern-modal">
            <div class="modal-header">
                <div class="d-flex align-items-center gap-2">
                    <div class="modal-header-icon">
                        <i data-lucide="user-cog" style="width:18px;height:18px;color:#2563EB;"></i>
                    </div>
                    <h5 class="modal-title">Edit User Account</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editUserForm">
                    <input type="hidden" name="user_id" id="editUserId">
                    <div class="mb-3">
                        <label for="editFullName" class="form-label text-muted small fw-bold text-uppercase">Full Name</label>
                        <input type="text" class="form-control modern-input" id="editFullName" name="full_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="editEmail" class="form-label text-muted small fw-bold text-uppercase">Email Address</label>
                        <input type="email" class="form-control modern-input" id="editEmail" name="email" required readonly style="background:#F8FAFC;">
                    </div>
                    <div class="mb-3">
                        <label for="editPhone" class="form-label text-muted small fw-bold text-uppercase">Phone Number</label>
                        <input type="tel" class="form-control modern-input" id="editPhone" name="phone">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-modal-cancel" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn-modal-primary" id="saveUserChanges">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete User Modal -->
<div class="modal fade" id="deleteUserModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modern-modal">
            <div class="modal-header" style="border-bottom: 1px solid #FEE2E2; background:#FEF2F2;">
                <div class="d-flex align-items-center gap-2">
                    <div class="modal-header-icon" style="background:#FEE2E2;">
                        <i data-lucide="alert-triangle" style="width:18px;height:18px;color:#DC2626;"></i>
                    </div>
                    <h5 class="modal-title" style="color:#991B1B;">Delete User Account</h5>
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
                <p class="text-muted mb-1">You are about to delete the account for <strong id="deleteUserName" style="color:#0F172A;"></strong>.</p>
                <p class="small text-danger mb-0">This action cannot be reversed and all associated user data may be lost.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-modal-cancel" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn-modal-danger" id="confirmDeleteUser">
                    <i data-lucide="trash-2" style="width:15px;height:15px;"></i>
                    <span>Confirm Delete</span>
                </button>
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
.stat-pill--buyers {
    background: #EFF6FF;
    color: #2563EB;
}
.stat-pill--sellers {
    background: #F5F3FF;
    color: #7C3AED;
}

/* Tab Pill Switchers */
.card-head-bar {
    padding: 16px 20px;
    border-bottom: 1px solid #F1F5F9;
}

.modern-tabs-pill {
    display: inline-flex;
    background: #F1F5F9;
    padding: 4px;
    border-radius: 12px;
    gap: 4px;
}

.tab-pill-btn {
    border: none;
    background: transparent;
    padding: 8px 18px;
    border-radius: 8px;
    font-size: 0.85rem;
    font-weight: 600;
    color: #64748B;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all .2s ease;
    cursor: pointer;
}

.tab-pill-btn.active {
    background: #FFFFFF;
    color: #0F172A;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
}

.tab-count {
    background: #E2E8F0;
    color: #475569;
    font-size: 0.72rem;
    font-weight: 700;
    padding: 1px 7px;
    border-radius: 100px;
    transition: all .2s ease;
}

.tab-pill-btn.active .tab-count {
    background: #EFF6FF;
    color: #2563EB;
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

/* User cell */
.user-meta-cell {
    display: flex;
    align-items: center;
    gap: 12px;
}

.user-avatar-circle {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    color: #FFFFFF;
    font-weight: 700;
    font-size: 0.88rem;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.user-name-title {
    font-weight: 600;
    color: #0F172A;
    font-size: 0.92rem;
}

.user-id-sub {
    font-size: 0.72rem;
    color: #94A3B8;
}

/* Contact meta */
.contact-meta-group {
    display: flex;
    flex-direction: column;
    gap: 3px;
}

.contact-row {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.82rem;
    color: #334155;
}

.phone-sub {
    font-size: 0.76rem;
    color: #64748B;
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
.status-chip--red {
    background: #FEE2E2;
    color: #DC2626;
}

/* Action button & dropdown */
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
    border: none;
    background: transparent;
    width: 100%;
    text-align: left;
}
.modern-dropdown .dropdown-item .item-icon {
    width: 15px;
    height: 15px;
}
.modern-dropdown .dropdown-item:hover {
    background: #F8FAFC;
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
    background: #EFF6FF;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modern-input {
    border-radius: 10px;
    border: 1px solid #E2E8F0;
    padding: 10px 14px;
    font-size: 0.88rem;
}

.modern-input:focus {
    border-color: #2563EB;
    box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
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
    box-shadow: 0 2px 8px rgba(37,99,235,0.25);
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

    // Tab switching for Buyers vs Sellers
    const tabButtons = document.querySelectorAll('.tab-pill-btn');
    tabButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const targetSelector = this.getAttribute('data-bs-target');
            if (!targetSelector) return;

            tabButtons.forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            document.querySelectorAll('.tab-pane').forEach(pane => {
                pane.classList.remove('show', 'active');
            });
            const targetPane = document.querySelector(targetSelector);
            if (targetPane) {
                targetPane.classList.add('show', 'active');
            }
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });
    });

    // Initialize modals
    const editModalEl = document.getElementById('editUserModal');
    const deleteModalEl = document.getElementById('deleteUserModal');
    const editModal = editModalEl ? new bootstrap.Modal(editModalEl) : null;
    const deleteModal = deleteModalEl ? new bootstrap.Modal(deleteModalEl) : null;
    let currentUserId = null;

    // Edit user functionality
    document.querySelectorAll('.edit-user-btn').forEach(button => {
        button.addEventListener('click', function() {
            const userId = this.getAttribute('data-user-id');
            const name = this.getAttribute('data-name');
            const email = this.getAttribute('data-email');
            const phone = this.getAttribute('data-phone');
            const role = this.getAttribute('data-role');

            const editUserIdEl = document.getElementById('editUserId');
            if (editUserIdEl) editUserIdEl.value = userId;
            const editFullNameEl = document.getElementById('editFullName');
            if (editFullNameEl) editFullNameEl.value = name;
            const editEmailEl = document.getElementById('editEmail');
            if (editEmailEl) editEmailEl.value = email;
            const editPhoneEl = document.getElementById('editPhone');
            if (editPhoneEl) editPhoneEl.value = phone;

            // Handle edit click based on user role
            if (role === 'buyer') {
                window.location.href = 'buyer_profile.php?id=' + userId;
            } else if (role === 'seller') {
                window.location.href = 'seller_profile.php?id=' + userId;
            } else if (editModal) {
                editModal.show();
            }
        });
    });

    // Save user changes
    const saveUserBtn = document.getElementById('saveUserChanges');
    if (saveUserBtn) {
        saveUserBtn.addEventListener('click', function() {
            const form = document.getElementById('editUserForm');
            if (!form) return;
            const formData = new FormData(form);
            formData.append('action', 'update_user');

            fetch('../api/users.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (typeof Notyf !== 'undefined') {
                        new Notyf().success('User account updated successfully!');
                    } else {
                        alert('User account updated successfully!');
                    }
                    if (editModal) editModal.hide();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    alert(data.message || 'Failed to update user');
                }
            })
            .catch(error => console.error('Error:', error));
        });
    }

    // Delete user functionality
    document.querySelectorAll('.delete-user-btn').forEach(button => {
        button.addEventListener('click', function() {
            currentUserId = this.getAttribute('data-user-id');
            const userName = this.getAttribute('data-name');
            const deleteNameEl = document.getElementById('deleteUserName');
            if (deleteNameEl) deleteNameEl.textContent = userName;
            if (deleteModal) deleteModal.show();
        });
    });

    // Confirm delete user
    const confirmDeleteBtn = document.getElementById('confirmDeleteUser');
    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', function() {
            if (!currentUserId) return;

            const formData = new FormData();
            formData.append('action', 'delete_user');
            formData.append('user_id', currentUserId);

            fetch('../api/users.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (typeof Notyf !== 'undefined') {
                        new Notyf().success('User account deleted successfully!');
                    } else {
                        alert('User account deleted successfully!');
                    }
                    if (deleteModal) deleteModal.hide();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    alert(data.message || 'Failed to delete user');
                }
            })
            .catch(error => console.error('Error:', error));
        });
    }
});
</script>

<?php include '../inc/footer.php'; ?>