<?php
require_once '../inc/db.php';
require_once '../inc/functions.php';
require_once '../inc/auth.php';

// Check if user is admin
checkPermission(['admin']);

$adminId = $_SESSION['user_id'];
$adminName = $_SESSION['user_name'] ?? 'Admin';

// Get seller data
$sellerId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$sql = "SELECT * FROM users WHERE id = ? AND role = 'seller'";
$seller = fetchOne($sql, "i", [$sellerId]);

if (!$seller) {
    header("Location: users.php");
    exit;
}

// Handle form submission
$error = '';
$success = isset($_GET['success']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Extract and sanitize form data
        $fullName = sanitizeInput($_POST['full_name'] ?? '');
        $phone = sanitizeInput($_POST['phone'] ?? '');
        $companyName = sanitizeInput($_POST['company_name'] ?? '');
        $address = sanitizeInput($_POST['address'] ?? '');
        $city = sanitizeInput($_POST['city'] ?? '');
        $state = sanitizeInput($_POST['state'] ?? '');
        $zipCode = sanitizeInput($_POST['zip_code'] ?? '');
        $bio = sanitizeInput($_POST['bio'] ?? '');

        if (empty($fullName)) {
            throw new Exception('Full name is required');
        }

        // Update user profile
        $sql = "UPDATE users SET 
                full_name = ?, 
                phone = ?,
                company_name = ?,
                address = ?,
                city = ?,
                state = ?,
                zip_code = ?,
                bio = ?,
                updated_at = NOW() 
                WHERE id = ?";

        $params = [$fullName, $phone, $companyName, $address, $city, $state, $zipCode, $bio, $sellerId];
        $types = "ssssssssi";

        $result = updateData($sql, $types, $params);

        if (!$result) {
            throw new Exception('Failed to update profile');
        }

        // Refresh seller data
        $sql = "SELECT * FROM users WHERE id = ? AND role = 'seller'";
        $seller = fetchOne($sql, "i", [$sellerId]);

        // Redirect with success message
        header("Location: seller_profile.php?id=" . $sellerId . "&success=1");
        exit;

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Pending reports count for sidebar badge
$pendingReportsCount = 0;
try {
    $prResult = fetchOne("SELECT COUNT(*) as cnt FROM reports WHERE status = 'pending'");
    $pendingReportsCount = $prResult['cnt'] ?? 0;
} catch (Exception $e) {}

// Unread contacts count
$unreadContactsCount = 0;
try {
    $ucResult = fetchOne("SELECT COUNT(*) as cnt FROM contact_messages WHERE status = 'unread'");
    $unreadContactsCount = $ucResult['cnt'] ?? 0;
} catch (Exception $e) {}

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
                        <i data-lucide="shield-check" style="width:13px;height:13px;"></i>
                        Super Admin
                    </span>
                </div>

                <div class="db-card">
                    <nav class="db-nav-group">
                        <a href="dashboard.php" class="db-nav-link">
                            <i data-lucide="layout-grid" style="width:17px;height:17px;"></i>
                            <span>Dashboard</span>
                        </a>
                        <a href="reports.php" class="db-nav-link">
                            <i data-lucide="flag" style="width:17px;height:17px;"></i>
                            <span>Reports</span>
                            <?php if ($pendingReportsCount > 0): ?>
                                <span class="db-nav-badge"><?= $pendingReportsCount ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="contacts.php" class="db-nav-link">
                            <i data-lucide="mail" style="width:17px;height:17px;"></i>
                            <span>Contacts</span>
                            <?php if ($unreadContactsCount > 0): ?>
                                <span class="db-nav-badge"><?= $unreadContactsCount ?></span>
                            <?php endif; ?>
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
                        <h1 class="db-hero-title">Edit Seller Profile</h1>
                        <p class="db-hero-desc">Modify business, agency, and account details for this registered seller</p>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <a href="users.php" class="btn-top-link">
                            <i data-lucide="arrow-left" style="width:15px;height:15px;"></i>
                            <span>Back to Users</span>
                        </a>
                    </div>
                </div>

                <!-- Alert Messages -->
                <?php if (!empty($error)): ?>
                    <div class="modern-alert modern-alert--danger">
                        <i data-lucide="alert-circle" style="width:20px;height:20px;flex-shrink:0;"></i>
                        <div class="modern-alert__content">
                            <strong>Error!</strong> <?= htmlspecialchars($error) ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="modern-alert modern-alert--success">
                        <i data-lucide="check-circle-2" style="width:20px;height:20px;flex-shrink:0;"></i>
                        <div class="modern-alert__content">
                            <strong>Success!</strong> Seller profile updated successfully.
                        </div>
                    </div>
                <?php endif; ?>

                <!-- User Profile Bento Card -->
                <div class="db-card user-overview-card">
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <div class="user-large-avatar" style="background: linear-gradient(135deg, #8B5CF6, #6D28D9);">
                            <?= strtoupper(substr($seller['full_name'] ?? 'S', 0, 1)) ?>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                <h3 class="overview-name mb-0"><?= htmlspecialchars($seller['full_name']) ?></h3>
                                <span class="role-chip role-chip--seller">Seller</span>
                                <span class="status-chip <?= ($seller['status'] ?? 'active') === 'active' ? 'status-chip--green' : 'status-chip--red' ?>">
                                    <?= ucfirst($seller['status'] ?? 'active') ?>
                                </span>
                            </div>
                            <div class="overview-meta-row">
                                <span><i data-lucide="mail" style="width:13px;height:13px;"></i> <?= htmlspecialchars($seller['email']) ?></span>
                                <?php if (!empty($seller['phone'])): ?>
                                    <span>•</span>
                                    <span><i data-lucide="phone" style="width:13px;height:13px;"></i> <?= htmlspecialchars($seller['phone']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($seller['company_name'])): ?>
                                    <span>•</span>
                                    <span><i data-lucide="briefcase" style="width:13px;height:13px;"></i> <?= htmlspecialchars($seller['company_name']) ?></span>
                                <?php endif; ?>
                                <span>•</span>
                                <span><i data-lucide="calendar" style="width:13px;height:13px;"></i> Member since <?= date('F Y', strtotime($seller['created_at'])) ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Card -->
                <form method="POST" class="profile-form">
                    <!-- Business & Personal Info Panel -->
                    <div class="db-card form-panel">
                        <div class="form-panel-header">
                            <div class="panel-icon-wrap">
                                <i data-lucide="briefcase"></i>
                            </div>
                            <div>
                                <h4 class="panel-title">Business & Contact Information</h4>
                                <p class="panel-desc">Seller identity, agency name, and phone contact</p>
                            </div>
                        </div>
                        <div class="form-panel-body">
                            <div class="form-grid-2">
                                <div class="form-group">
                                    <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                    <input type="text" class="modern-input" name="full_name" value="<?= htmlspecialchars($seller['full_name']) ?>" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Email Address</label>
                                    <input type="email" class="modern-input" value="<?= htmlspecialchars($seller['email']) ?>" readonly style="background:#F8FAFC; color:#64748B;">
                                    <small class="text-muted">Email address cannot be modified directly</small>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Phone Number</label>
                                    <input type="tel" class="modern-input" name="phone" value="<?= htmlspecialchars($seller['phone'] ?? '') ?>" placeholder="e.g. +1 555-0199">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Company / Agency Name</label>
                                    <input type="text" class="modern-input" name="company_name" value="<?= htmlspecialchars($seller['company_name'] ?? '') ?>" placeholder="e.g. Prime Realty Group">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Address Information Panel -->
                    <div class="db-card form-panel">
                        <div class="form-panel-header">
                            <div class="panel-icon-wrap">
                                <i data-lucide="map-pin"></i>
                            </div>
                            <div>
                                <h4 class="panel-title">Address & Location</h4>
                                <p class="panel-desc">Seller's agency or physical office location</p>
                            </div>
                        </div>
                        <div class="form-panel-body">
                            <div class="form-group mb-3">
                                <label class="form-label">Street Address</label>
                                <input type="text" class="modern-input" name="address" value="<?= htmlspecialchars($seller['address'] ?? '') ?>" placeholder="e.g. 742 Evergreen Terrace">
                            </div>
                            <div class="form-grid-3">
                                <div class="form-group">
                                    <label class="form-label">City</label>
                                    <input type="text" class="modern-input" name="city" value="<?= htmlspecialchars($seller['city'] ?? '') ?>" placeholder="e.g. Springfield">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">State / Province</label>
                                    <input type="text" class="modern-input" name="state" value="<?= htmlspecialchars($seller['state'] ?? '') ?>" placeholder="e.g. Oregon">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Postal / ZIP Code</label>
                                    <input type="text" class="modern-input" name="zip_code" value="<?= htmlspecialchars($seller['zip_code'] ?? '') ?>" placeholder="e.g. 97477">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bio Panel -->
                    <div class="db-card form-panel">
                        <div class="form-panel-header">
                            <div class="panel-icon-wrap">
                                <i data-lucide="file-text"></i>
                            </div>
                            <div>
                                <h4 class="panel-title">Seller Bio & Experience</h4>
                                <p class="panel-desc">Agency reputation, background, and license credentials</p>
                            </div>
                        </div>
                        <div class="form-panel-body">
                            <div class="form-group">
                                <label class="form-label">About Seller</label>
                                <textarea class="modern-input" name="bio" rows="4" placeholder="Brief description of this seller, real estate experience, etc..."><?= htmlspecialchars($seller['bio'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Action Bar -->
                    <div class="db-card form-action-bar">
                        <a href="users.php" class="btn-cancel">Cancel</a>
                        <button type="submit" class="btn-save">
                            <i data-lucide="save" style="width:16px;height:16px;"></i>
                            <span>Save Profile Changes</span>
                        </button>
                    </div>
                </form>

            </main>
        </div>
    </div>
</div>

<style>
/* ── Layout & Aesthetics ── */
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

.btn-top-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.82rem;
    font-weight: 600;
    color: #475569;
    background: #F8FAFC;
    border: 1px solid #E2E8F0;
    padding: 8px 16px;
    border-radius: 8px;
    text-decoration: none;
    transition: all .15s ease;
}
.btn-top-link:hover {
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

/* Overview Card */
.user-overview-card {
    padding: 24px;
}
.user-large-avatar {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    color: #FFFFFF;
    font-size: 1.6rem;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.overview-name {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-weight: 800;
    font-size: 1.25rem;
    color: #0F172A;
}

.role-chip {
    padding: 2px 8px;
    border-radius: 100px;
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
}
.role-chip--seller {
    background: #F3E8FF;
    color: #7C3AED;
}

.status-chip {
    padding: 2px 8px;
    border-radius: 100px;
    font-size: 0.72rem;
    font-weight: 700;
}
.status-chip--green {
    background: #ECFDF5;
    color: #059669;
}
.status-chip--red {
    background: #FEF2F2;
    color: #DC2626;
}

.overview-meta-row {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.8rem;
    color: #64748B;
    flex-wrap: wrap;
}
.overview-meta-row span {
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

/* Panels */
.profile-form {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.form-panel {
    border-radius: 16px;
    overflow: hidden;
}

.form-panel-header {
    padding: 20px 24px;
    border-bottom: 1px solid #F1F5F9;
    display: flex;
    align-items: center;
    gap: 14px;
}

.panel-icon-wrap {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    background: #F3E8FF;
    color: #7C3AED;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.panel-icon-wrap svg {
    width: 18px;
    height: 18px;
}

.panel-title {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 1.05rem;
    font-weight: 700;
    color: #0F172A;
    margin: 0;
}

.panel-desc {
    font-size: 0.8rem;
    color: #64748B;
    margin: 2px 0 0;
}

.form-panel-body {
    padding: 24px;
}

.form-grid-2 {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px 20px;
}
.form-grid-3 {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px 20px;
}

@media (max-width: 768px) {
    .form-grid-2, .form-grid-3 {
        grid-template-columns: 1fr;
    }
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.form-label {
    font-size: 0.82rem;
    font-weight: 600;
    color: #334155;
    margin: 0;
}

.modern-input {
    width: 100%;
    padding: 10px 14px;
    border-radius: 10px;
    border: 1px solid #E2E8F0;
    font-size: 0.88rem;
    color: #0F172A;
    background: #FFFFFF;
    transition: all .2s ease;
}

.modern-input:focus {
    outline: none;
    border-color: #7C3AED;
    box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
}

/* Action bar */
.form-action-bar {
    padding: 18px 24px;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 12px;
}

.btn-cancel {
    font-size: 0.85rem;
    font-weight: 600;
    color: #64748B;
    background: #F1F5F9;
    padding: 9px 18px;
    border-radius: 8px;
    text-decoration: none;
    transition: all .15s ease;
}
.btn-cancel:hover {
    background: #E2E8F0;
    color: #0F172A;
}

.btn-save {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 0.88rem;
    font-weight: 600;
    color: #FFFFFF;
    background: #7C3AED;
    border: none;
    padding: 9px 22px;
    border-radius: 8px;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(124, 58, 237, 0.25);
    transition: all .2s ease;
}
.btn-save:hover {
    background: #6D28D9;
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
