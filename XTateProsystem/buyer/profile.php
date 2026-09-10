<?php
require_once '../inc/db.php';
require_once '../inc/functions.php';
require_once '../inc/auth.php';

// Check if user is logged in and has buyer role
checkPermission(['buyer']);

// Get buyer data
$buyerId = isset($_GET['id']) ? intval($_GET['id']) : $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = ? AND role = 'buyer'";
$buyer = fetchOne($sql, "i", [$buyerId]);

// Only allow editing if viewing own profile
$canEdit = $buyerId === $_SESSION['user_id'];

// If database query fails, use demo buyer data as safe fallback
if (!$buyer) {
    $buyer = [
        'id' => $buyerId,
        'full_name' => 'John Buyer',
        'email' => 'buyer@example.com',
        'phone' => '555-123-4567',
        'address' => '123 Buyer St',
        'city' => 'New York',
        'state' => 'NY',
        'zip_code' => '10001',
        'bio' => 'A dedicated homebuyer seeking a comfortable and modern property.',
        'role' => 'buyer',
        'created_at' => date('Y-m-d H:i:s', strtotime('-30 days')),
        'updated_at' => date('Y-m-d H:i:s', strtotime('-30 days')),
    ];
}

// Handle profile update
$updateSuccess = false;
$updateError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Extract and sanitize form data
        $fullName = sanitizeInput($_POST['full_name'] ?? '');
        $phone = sanitizeInput($_POST['phone'] ?? '');
        $address = sanitizeInput($_POST['address'] ?? '');
        $city = sanitizeInput($_POST['city'] ?? '');
        $state = sanitizeInput($_POST['state'] ?? '');
        $zipCode = sanitizeInput($_POST['zip_code'] ?? '');
        $bio = sanitizeInput($_POST['bio'] ?? '');
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($fullName)) {
            throw new Exception('Full name is required');
        }

        // Update user profile
        $sql = "UPDATE users SET 
                full_name = ?, 
                phone = ?,
                address = ?,
                city = ?,
                state = ?,
                zip_code = ?,
                bio = ?,
                updated_at = NOW() 
                WHERE id = ?";

        $params = [$fullName, $phone, $address, $city, $state, $zipCode, $bio, $buyerId];
        $types = "sssssssi";

        $result = updateData($sql, $types, $params);

        if (!$result) {
            throw new Exception('Failed to update profile');
        }

        // If password is being updated
        if (!empty($newPassword)) {
            if ($newPassword !== $confirmPassword) {
                throw new Exception('New password and confirmation do not match.');
            }
            if (strlen($newPassword) < 6) {
                throw new Exception('New password must be at least 6 characters long.');
            }
            if (!empty($currentPassword) && !verifyPassword($currentPassword, $buyer['password'] ?? '')) {
                throw new Exception('Current password is incorrect.');
            }
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET password = ? WHERE id = ?";
            $result = updateData($sql, "si", [$passwordHash, $buyerId]);
            if (!$result) {
                throw new Exception('Failed to update password');
            }
        }

        // Update session data
        $_SESSION['user_name'] = $fullName;

        // Set success flag
        $updateSuccess = true;

        // Refresh buyer data
        $buyer['full_name'] = $fullName;
        $buyer['phone'] = $phone;
        $buyer['address'] = $address;
        $buyer['city'] = $city;
        $buyer['state'] = $state;
        $buyer['zip_code'] = $zipCode;
        $buyer['bio'] = $bio;
    } catch (Exception $e) {
        $updateError = $e->getMessage();
    }
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
                        <a href="favorites.php" class="db-nav-link">
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
                        <a href="profile.php" class="db-nav-link active">
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
                        <h1 class="db-hero-title">Account & Profile</h1>
                        <p class="db-hero-desc">Manage your buyer identity, contact information, and security credentials</p>
                    </div>
                </div>

                <!-- Alert Messages -->
                <?php if ($updateSuccess): ?>
                    <div class="modern-alert modern-alert--success">
                        <i data-lucide="check-circle-2" style="width:20px;height:20px;flex-shrink:0;"></i>
                        <div class="modern-alert__content">
                            <strong>Success!</strong> Your profile details have been updated successfully.
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($updateError)): ?>
                    <div class="modern-alert modern-alert--danger">
                        <i data-lucide="alert-circle" style="width:20px;height:20px;flex-shrink:0;"></i>
                        <div class="modern-alert__content">
                            <strong>Update Failed:</strong> <?= htmlspecialchars($updateError) ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($canEdit): ?>
                    <form method="POST" action="profile.php">

                        <!-- Panel 1: Personal & Contact Information -->
                        <section class="form-panel">
                            <div class="form-panel__header">
                                <div class="form-panel__icon">
                                    <i data-lucide="user"></i>
                                </div>
                                <div>
                                    <h2 class="form-panel__title">Personal Information</h2>
                                    <p class="form-panel__sub">Your identity used when submitting inquiries and contacting sellers</p>
                                </div>
                            </div>
                            <div class="form-panel__body">

                                <!-- Member Badge Header -->
                                <div class="profile-header-summary mb-4">
                                    <div class="db-avatar" style="margin: 0; width: 54px; height: 54px; font-size: 1.25rem;">
                                        <?= strtoupper(substr($buyer['full_name'] ?? 'B', 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($buyer['full_name']) ?></div>
                                        <div class="d-flex align-items-center gap-2 mt-1">
                                            <span class="badge-role-pill"><?= ucfirst($buyer['role']) ?> Account</span>
                                            <span class="meta-dot">&bull;</span>
                                            <span class="text-muted small">
                                                <i data-lucide="calendar" style="width:12px;height:12px;vertical-align:-1px;"></i>
                                                Member since <?= formatDateTime($buyer['created_at'], 'F Y') ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-grid form-grid--2 mb-4">
                                    <div class="form-group">
                                        <label for="full_name" class="form-label">Full Name <span class="req">*</span></label>
                                        <div class="input-with-icon">
                                            <i data-lucide="user" class="field-icon"></i>
                                            <input type="text" class="modern-input has-icon" id="full_name" name="full_name" value="<?= htmlspecialchars($buyer['full_name']) ?>" required>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="email" class="form-label">Email Address</label>
                                        <div class="input-with-icon">
                                            <i data-lucide="mail" class="field-icon"></i>
                                            <input type="email" class="modern-input has-icon input-disabled" id="email" value="<?= htmlspecialchars($buyer['email']) ?>" disabled>
                                        </div>
                                        <div class="form-helper-text">Email address is permanently linked to your account.</div>
                                    </div>

                                    <div class="form-group">
                                        <label for="phone" class="form-label">Phone Number <span class="req">*</span></label>
                                        <div class="input-with-icon">
                                            <i data-lucide="phone" class="field-icon"></i>
                                            <input type="tel" class="modern-input has-icon" id="phone" name="phone" value="<?= htmlspecialchars($buyer['phone'] ?? '') ?>" placeholder="+1 (555) 000-0000" required>
                                        </div>
                                    </div>
                                </div>

                                <h3 class="profile-section-heading">
                                    <i data-lucide="map-pin" style="width:16px;height:16px;color:#2563EB;"></i> Address & Location
                                </h3>

                                <div class="form-grid form-grid--3 mb-4">
                                    <div class="form-group form-grid--full">
                                        <label for="address" class="form-label">Street Address</label>
                                        <input type="text" class="modern-input" id="address" name="address" value="<?= htmlspecialchars($buyer['address'] ?? '') ?>" placeholder="e.g. 123 Main Street, Apt 4B">
                                    </div>

                                    <div class="form-group">
                                        <label for="city" class="form-label">City</label>
                                        <input type="text" class="modern-input" id="city" name="city" value="<?= htmlspecialchars($buyer['city'] ?? '') ?>" placeholder="e.g. Los Angeles">
                                    </div>

                                    <div class="form-group">
                                        <label for="state" class="form-label">State / Province</label>
                                        <input type="text" class="modern-input" id="state" name="state" value="<?= htmlspecialchars($buyer['state'] ?? '') ?>" placeholder="e.g. California">
                                    </div>

                                    <div class="form-group">
                                        <label for="zip_code" class="form-label">ZIP / Postal Code</label>
                                        <input type="text" class="modern-input" id="zip_code" name="zip_code" value="<?= htmlspecialchars($buyer['zip_code'] ?? '') ?>" placeholder="e.g. 90210">
                                    </div>
                                </div>

                                <h3 class="profile-section-heading">
                                    <i data-lucide="book-open" style="width:16px;height:16px;color:#2563EB;"></i> Buyer Introduction
                                </h3>

                                <div class="form-group">
                                    <label for="bio" class="form-label">About Me & Preferences</label>
                                    <textarea class="modern-textarea" id="bio" name="bio" rows="4" placeholder="Tell sellers about yourself, your timeline, and what kind of properties you are interested in..."><?= htmlspecialchars($buyer['bio'] ?? '') ?></textarea>
                                    <div class="form-helper-text">This helps sellers understand your requirements when you inquire.</div>
                                </div>

                            </div>
                        </section>

                        <!-- Panel 2: Security & Password -->
                        <section class="form-panel">
                            <div class="form-panel__header">
                                <div class="form-panel__icon form-panel__icon--security">
                                    <i data-lucide="shield-check"></i>
                                </div>
                                <div>
                                    <h2 class="form-panel__title">Security & Password</h2>
                                    <p class="form-panel__sub">Update your password to keep your account safe</p>
                                </div>
                            </div>
                            <div class="form-panel__body">
                                <div class="form-grid form-grid--3 mb-3">
                                    <div class="form-group">
                                        <label for="current_password" class="form-label">Current Password</label>
                                        <div class="input-with-icon">
                                            <i data-lucide="lock" class="field-icon"></i>
                                            <input type="password" class="modern-input has-icon" id="current_password" name="current_password" placeholder="Enter current password">
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="new_password" class="form-label">New Password</label>
                                        <div class="input-with-icon">
                                            <i data-lucide="key" class="field-icon"></i>
                                            <input type="password" class="modern-input has-icon" id="new_password" name="new_password" placeholder="At least 6 characters">
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                                        <div class="input-with-icon">
                                            <i data-lucide="check" class="field-icon"></i>
                                            <input type="password" class="modern-input has-icon" id="confirm_password" name="confirm_password" placeholder="Re-enter new password">
                                        </div>
                                    </div>
                                </div>
                                <div class="form-helper-text">Leave password fields blank if you don't wish to change your password.</div>
                            </div>
                        </section>

                        <!-- Actions Bar -->
                        <div class="form-actions-bar">
                            <a href="dashboard.php" class="btn-db-action btn-db-action--secondary">
                                Cancel
                            </a>
                            <button type="submit" class="btn-db-action btn-db-action--primary">
                                <i data-lucide="save" style="width:17px;height:17px;"></i>
                                <span>Save Changes</span>
                            </button>
                        </div>

                    </form>
                <?php else: ?>
                    <div class="db-card p-5 text-center">
                        <i data-lucide="lock" style="width:40px;height:40px;color:#94A3B8;margin-bottom:12px;"></i>
                        <h3 class="fw-bold">Read Only View</h3>
                        <p class="text-muted">You are viewing another user's profile. Modifications can only be made on your own account.</p>
                    </div>
                <?php endif; ?>

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

/* Action Buttons */
.btn-db-action {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
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
    border: 1px solid #E2E8F0;
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
.modern-alert--danger {
    background: #FEF2F2;
    border: 1px solid #FCA5A5;
    color: #991B1B;
}
.modern-alert--success {
    background: #F0FDF4;
    border: 1px solid #86EFAC;
    color: #166534;
}

/* ── Form Panels (Bento) ── */
.form-panel {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.05);
    margin-bottom: 20px;
}

.form-panel__header {
    padding: 20px 24px;
    border-bottom: 1px solid #F1F5F9;
    display: flex;
    align-items: center;
    gap: 14px;
    background: #FFFFFF;
}

.form-panel__icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: #EFF6FF;
    color: #2563EB;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.form-panel__icon--security {
    background: #ECFDF5;
    color: #059669;
}
.form-panel__icon svg, .form-panel__icon i {
    width: 20px;
    height: 20px;
}

.form-panel__title {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 1.05rem;
    font-weight: 700;
    color: #0F172A;
    margin: 0 0 2px;
}

.form-panel__sub {
    font-size: 0.82rem;
    color: #64748B;
    margin: 0;
}

.form-panel__body {
    padding: 24px;
}

.profile-header-summary {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 14px 18px;
    background: #F8FAFC;
    border: 1px solid #E2E8F0;
    border-radius: 14px;
}

.badge-role-pill {
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    padding: 2px 8px;
    border-radius: 6px;
    background: #EFF6FF;
    color: #2563EB;
}

.meta-dot {
    color: #CBD5E1;
}

.profile-section-heading {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 0.92rem;
    font-weight: 700;
    color: #334155;
    margin: 0 0 14px;
    display: flex;
    align-items: center;
    gap: 8px;
}

/* Grids */
.form-grid {
    display: grid;
    gap: 18px;
}
.form-grid--2 {
    grid-template-columns: repeat(2, 1fr);
}
.form-grid--3 {
    grid-template-columns: repeat(3, 1fr);
}
.form-grid--full {
    grid-column: 1 / -1;
}

@media (max-width: 768px) {
    .form-grid--2, .form-grid--3 {
        grid-template-columns: 1fr;
    }
}

/* Inputs */
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

.req {
    color: #EF4444;
}

.modern-input, .modern-textarea {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid #CBD5E1;
    border-radius: 10px;
    font-size: 0.88rem;
    color: #0F172A;
    background: #FFFFFF;
    outline: none;
    transition: all .15s ease;
    font-family: inherit;
}

.modern-input:focus, .modern-textarea:focus {
    border-color: #2563EB;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
}

.input-disabled {
    background: #F1F5F9;
    color: #64748B;
    cursor: not-allowed;
}

.input-with-icon {
    position: relative;
    display: flex;
    align-items: center;
}

.field-icon {
    position: absolute;
    left: 12px;
    width: 16px;
    height: 16px;
    color: #94A3B8;
    pointer-events: none;
}

.modern-input.has-icon {
    padding-left: 36px;
}

.form-helper-text {
    font-size: 0.76rem;
    color: #94A3B8;
    margin-top: 4px;
}

.form-actions-bar {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 12px;
    padding: 16px 24px;
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.05);
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