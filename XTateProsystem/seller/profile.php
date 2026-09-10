<?php
require_once '../inc/db.php';
require_once '../inc/functions.php';
require_once '../inc/auth.php';

// Check if user is logged in and has seller role
checkPermission(['seller']);

// Get seller data
$sellerId = isset($_GET['id']) ? intval($_GET['id']) : ($_SESSION['user_id'] ?? 0);
$sql = "SELECT * FROM users WHERE id = ? AND role = 'seller'";
$seller = fetchOne($sql, "i", [$sellerId]);

if (!$seller) {
    // Fallback to Firestore
    $seller = firestore_get_user_by_id($sellerId);
}

// Only allow editing if viewing own profile
$canEdit = ($sellerId === ($_SESSION['user_id'] ?? 0));
$sellerName = $seller['full_name'] ?? ($_SESSION['user_name'] ?? 'Seller');

// Badges count
$pendingCount = getPendingInquiriesCount($sellerId);
$unreadCount = getUnreadMessagesCount($sellerId);

// Process profile update
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    try {
        $fullName = isset($_POST['full_name']) ? sanitizeInput($_POST['full_name']) : '';
        $email = isset($_POST['email']) ? sanitizeInput($_POST['email']) : '';
        $phone = isset($_POST['phone']) ? sanitizeInput($_POST['phone']) : '';
        $companyName = isset($_POST['company_name']) ? sanitizeInput($_POST['company_name']) : '';
        $bio = isset($_POST['bio']) ? sanitizeInput($_POST['bio']) : '';

        // Validate fields
        if (empty($fullName)) {
            throw new Exception("Full name is required");
        }

        if (empty($email)) {
            throw new Exception("Email is required");
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Email is invalid");
        }

        // Check if email is already in use by another user
        if ($email !== ($seller['email'] ?? '')) {
            $sql = "SELECT id FROM users WHERE email = ? AND id != ?";
            $existingUser = fetchOne($sql, "si", [$email, $sellerId]);

            if ($existingUser) {
                throw new Exception("Email is already in use");
            }
        }

        // Update profile
        $address = isset($_POST['address']) ? sanitizeInput($_POST['address']) : '';
        $city = isset($_POST['city']) ? sanitizeInput($_POST['city']) : '';
        $state = isset($_POST['state']) ? sanitizeInput($_POST['state']) : '';
        $zipCode = isset($_POST['zip_code']) ? sanitizeInput($_POST['zip_code']) : '';

        $sql = "UPDATE users SET full_name = ?, email = ?, phone = ?, company_name = ?, bio = ?, address = ?, city = ?, state = ?, zip_code = ?, updated_at = NOW() WHERE id = ?";
        $result = updateData($sql, "sssssssssi", [$fullName, $email, $phone, $companyName, $bio, $address, $city, $state, $zipCode, $sellerId]);

        // Sync to Firestore
        try {
            $firestore = getFirestore();
            $firestore->collection('users')->document((string)$sellerId)->set([
                'id' => (int)$sellerId,
                'full_name' => $fullName,
                'email' => $email,
                'phone' => $phone,
                'company_name' => $companyName,
                'bio' => $bio,
                'address' => $address,
                'city' => $city,
                'state' => $state,
                'zip_code' => $zipCode,
                'updated_at' => date('Y-m-d H:i:s')
            ], ['merge' => true]);
        } catch (Exception $fe) {
            error_log("Firestore profile update error: " . $fe->getMessage());
        }

        if (!$result && empty($seller)) {
            throw new Exception("Failed to update profile");
        }

        // Update session user name
        $_SESSION['user_name'] = $fullName;

        // Refresh seller data
        $seller['full_name'] = $fullName;
        $seller['email'] = $email;
        $seller['phone'] = $phone;
        $seller['company_name'] = $companyName;
        $seller['bio'] = $bio;
        $seller['address'] = $address;
        $seller['city'] = $city;
        $seller['state'] = $state;
        $seller['zip_code'] = $zipCode;
        $sellerName = $fullName;

        // Redirect with success message
        header("Location: profile.php?success=1");
        exit;

    } catch (Exception $e) {
        $errors[] = $e->getMessage();
    }
}

// Process password update
$passwordErrors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_password') {
    $currentPassword = isset($_POST['current_password']) ? $_POST['current_password'] : '';
    $newPassword = isset($_POST['new_password']) ? $_POST['new_password'] : '';
    $confirmPassword = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

    if (empty($currentPassword)) {
        $passwordErrors[] = "Current password is required";
    } elseif (!verifyPassword($currentPassword, $seller['password'] ?? '')) {
        $passwordErrors[] = "Current password is incorrect";
    }

    if (empty($newPassword)) {
        $passwordErrors[] = "New password is required";
    } elseif (strlen($newPassword) < 6) {
        $passwordErrors[] = "New password must be at least 6 characters long";
    }

    if (empty($confirmPassword)) {
        $passwordErrors[] = "Confirm password is required";
    } elseif ($newPassword !== $confirmPassword) {
        $passwordErrors[] = "Passwords do not match";
    }

    // If no errors, update password
    if (empty($passwordErrors)) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        $sql = "UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?";
        $result = updateData($sql, "si", [$hashedPassword, $sellerId]);

        if ($result) {
            header("Location: profile.php?password_success=1");
            exit;
        } else {
            $passwordErrors[] = "Failed to update password";
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
                    <a href="inquiries.php" class="db-nav-link">
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
                    <a href="profile.php" class="db-nav-link active">
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
                        <h1 class="db-hero-title">Account & Profile</h1>
                        <p class="db-hero-desc">Manage your public seller identity, contact information, and account security</p>
                    </div>
                </div>

                <!-- Alert Messages -->
                <?php if (isset($_GET['success'])): ?>
                    <div class="modern-alert modern-alert--success">
                        <i data-lucide="check-circle-2" style="width:20px;height:20px;flex-shrink:0;"></i>
                        <div class="modern-alert__content">
                            <strong>Success!</strong> Your profile details have been updated and synchronized.
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="modern-alert modern-alert--danger">
                        <i data-lucide="alert-circle" style="width:20px;height:20px;flex-shrink:0;"></i>
                        <div class="modern-alert__content">
                            <div class="fw-bold mb-1">Could not update profile:</div>
                            <ul class="mb-0 ps-3">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($canEdit): ?>
                    <!-- Panel 1: Profile Information -->
                    <section class="form-panel">
                        <div class="form-panel__header">
                            <div class="form-panel__icon">
                                <i data-lucide="user"></i>
                            </div>
                            <div>
                                <h2 class="form-panel__title">Personal & Contact Details</h2>
                                <p class="form-panel__sub">Information displayed on your public property listings and seller bio</p>
                            </div>
                        </div>
                        <div class="form-panel__body">
                            <form method="POST" action="profile.php">
                                <input type="hidden" name="action" value="update_profile">

                                <div class="form-grid form-grid--2 mb-4">
                                    <div class="form-group">
                                        <label for="fullName" class="form-label">Full Name <span class="req">*</span></label>
                                        <div class="input-with-icon">
                                            <i data-lucide="user" class="field-icon"></i>
                                            <input type="text" class="modern-input has-icon" id="fullName" name="full_name" value="<?= htmlspecialchars($seller['full_name'] ?? '') ?>" required>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="email" class="form-label">Email Address <span class="req">*</span></label>
                                        <div class="input-with-icon">
                                            <i data-lucide="mail" class="field-icon"></i>
                                            <input type="email" class="modern-input has-icon" id="email" name="email" value="<?= htmlspecialchars($seller['email'] ?? '') ?>" required>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="phone" class="form-label">Phone Number</label>
                                        <div class="input-with-icon">
                                            <i data-lucide="phone" class="field-icon"></i>
                                            <input type="text" class="modern-input has-icon" id="phone" name="phone" value="<?= htmlspecialchars($seller['phone'] ?? '') ?>" placeholder="+1 (555) 000-0000">
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="companyName" class="form-label">Agency / Company Name</label>
                                        <div class="input-with-icon">
                                            <i data-lucide="building-2" class="field-icon"></i>
                                            <input type="text" class="modern-input has-icon" id="companyName" name="company_name" value="<?= htmlspecialchars($seller['company_name'] ?? '') ?>" placeholder="e.g. Skyline Premier Realty">
                                        </div>
                                    </div>
                                </div>

                                <h3 class="profile-section-heading">
                                    <i data-lucide="map-pin" style="width:16px;height:16px;color:#2563EB;"></i> Office / Business Address
                                </h3>

                                <div class="form-grid form-grid--3 mb-4">
                                    <div class="form-group form-grid--full">
                                        <label for="address" class="form-label">Street Address</label>
                                        <input type="text" class="modern-input" id="address" name="address" value="<?= htmlspecialchars($seller['address'] ?? '') ?>" placeholder="e.g. 100 Financial District Blvd, Suite 1200">
                                    </div>

                                    <div class="form-group">
                                        <label for="city" class="form-label">City</label>
                                        <input type="text" class="modern-input" id="city" name="city" value="<?= htmlspecialchars($seller['city'] ?? '') ?>" placeholder="e.g. New York">
                                    </div>

                                    <div class="form-group">
                                        <label for="state" class="form-label">State / Province</label>
                                        <input type="text" class="modern-input" id="state" name="state" value="<?= htmlspecialchars($seller['state'] ?? '') ?>" placeholder="e.g. NY">
                                    </div>

                                    <div class="form-group">
                                        <label for="zip_code" class="form-label">ZIP / Postal Code</label>
                                        <input type="text" class="modern-input" id="zip_code" name="zip_code" value="<?= htmlspecialchars($seller['zip_code'] ?? '') ?>" placeholder="e.g. 10005">
                                    </div>
                                </div>

                                <h3 class="profile-section-heading">
                                    <i data-lucide="book-open" style="width:16px;height:16px;color:#2563EB;"></i> Professional Biography
                                </h3>

                                <div class="form-group mb-4">
                                    <label for="bio" class="form-label">Bio & Track Record</label>
                                    <textarea class="modern-textarea" id="bio" name="bio" rows="4" placeholder="Introduce yourself to prospective buyers, highlighting your real estate experience, specializations, and client commitment..."><?= htmlspecialchars($seller['bio'] ?? '') ?></textarea>
                                    <div class="form-helper-text">This will be showcased on your seller profile and linked properties.</div>
                                </div>

                                <div class="d-flex justify-content-end">
                                    <button type="submit" class="btn-db-action btn-db-action--primary">
                                        <i data-lucide="save" style="width:17px;height:17px;"></i>
                                        <span>Save Profile</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </section>

                    <!-- Panel 2: Security & Password -->
                    <section class="form-panel">
                        <div class="form-panel__header">
                            <div class="form-panel__icon form-panel__icon--security">
                                <i data-lucide="shield-check"></i>
                            </div>
                            <div>
                                <h2 class="form-panel__title">Security & Credentials</h2>
                                <p class="form-panel__sub">Keep your seller account secure with a robust password</p>
                            </div>
                        </div>
                        <div class="form-panel__body">
                            <?php if (isset($_GET['password_success'])): ?>
                                <div class="modern-alert modern-alert--success mb-4">
                                    <i data-lucide="check-circle-2" style="width:20px;height:20px;flex-shrink:0;"></i>
                                    <div class="modern-alert__content">
                                        <strong>Success!</strong> Your password has been updated successfully.
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($passwordErrors)): ?>
                                <div class="modern-alert modern-alert--danger mb-4">
                                    <i data-lucide="alert-circle" style="width:20px;height:20px;flex-shrink:0;"></i>
                                    <div class="modern-alert__content">
                                        <div class="fw-bold mb-1">Could not change password:</div>
                                        <ul class="mb-0 ps-3">
                                            <?php foreach ($passwordErrors as $error): ?>
                                                <li><?= htmlspecialchars($error) ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <form method="POST" action="profile.php">
                                <input type="hidden" name="action" value="update_password">

                                <div class="form-grid form-grid--3 mb-4">
                                    <div class="form-group">
                                        <label for="currentPassword" class="form-label">Current Password <span class="req">*</span></label>
                                        <div class="input-with-icon">
                                            <i data-lucide="lock" class="field-icon"></i>
                                            <input type="password" class="modern-input has-icon" id="currentPassword" name="current_password" required>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="newPassword" class="form-label">New Password <span class="req">*</span></label>
                                        <div class="input-with-icon">
                                            <i data-lucide="key" class="field-icon"></i>
                                            <input type="password" class="modern-input has-icon" id="newPassword" name="new_password" placeholder="At least 6 characters" required>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="confirmPassword" class="form-label">Confirm New Password <span class="req">*</span></label>
                                        <div class="input-with-icon">
                                            <i data-lucide="check" class="field-icon"></i>
                                            <input type="password" class="modern-input has-icon" id="confirmPassword" name="confirm_password" placeholder="Re-enter new password" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end">
                                    <button type="submit" class="btn-db-action btn-db-action--secondary">
                                        <i data-lucide="key-round" style="width:16px;height:16px;"></i>
                                        <span>Update Password</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </section>
                <?php else: ?>
                    <div class="db-card p-5 text-center">
                        <i data-lucide="lock" style="width:40px;height:40px;color:#94A3B8;margin-bottom:12px;"></i>
                        <h3 class="fw-bold">Read Only View</h3>
                        <p class="text-muted">You are viewing another seller's profile. Modifications can only be made on your own account.</p>
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

/* Actions buttons */
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
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});
</script>

<?php include '../inc/footer.php'; ?>