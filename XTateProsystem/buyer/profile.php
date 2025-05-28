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

// If database query fails, use demo buyer data (This is a temporary fallback, a proper error handling mechanism is needed)
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
            throw new Exception('Name is required');
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

        // Refresh buyer data  (This section needs improvement for efficiency)
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

include '../inc/header.php';
?>

<div class="container py-5">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-3 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Buyer Dashboard</h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="dashboard.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                    <a href="favorites.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-heart me-2"></i> My Favorites
                    </a>
                    <a href="messages.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-comments me-2"></i> Messages
                        <?php 
                        $unreadCount = getUnreadMessagesCount($buyerId);
                        if ($unreadCount > 0): 
                        ?>
                            <span class="badge bg-danger rounded-pill ms-1"><?= $unreadCount ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="../search.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-search me-2"></i> Find Properties
                    </a>
                    <a href="profile.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-user-edit me-2"></i> Edit Profile
                    </a>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-lg-9">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Edit Profile</h5>
                </div>
                <div class="card-body">
                    <?php if ($updateSuccess): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle me-2"></i> Your profile has been updated successfully.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($updateError)): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle me-2"></i> <?= $updateError ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <form method="POST" action="profile.php">
                        <div class="mb-4">
                            <div class="d-flex align-items-center mb-3">
                                <div class="avatar-circle me-3" style="background-color: <?= generateAvatarColor($buyerId) ?>">
                                    <span class="avatar-initials"><?= strtoupper(substr($buyer['full_name'] ?? 'JB', 0, 1)) ?></span>
                                </div>
                                <div>
                                    <h5 class="mb-0"><?= $buyer['full_name'] ?></h5>
                                    <p class="text-muted mb-0"><?= ucfirst($buyer['role']) ?> Account</p>
                                </div>
                            </div>
                            <div class="text-muted small">Member since <?= formatDateTime($buyer['created_at'], 'F Y') ?></div>
                        </div>

                        <h6 class="fw-bold mb-3">Personal Information</h6>
                        <div class="row mb-3">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label for="full_name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" value="<?= $buyer['full_name'] ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" value="<?= $buyer['email'] ?>" disabled>
                                <div class="form-text">Email address cannot be changed</div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone" value="<?= $buyer['phone'] ?? '' ?>" required>
                            </div>
                        </div>

                        <h6 class="fw-bold mt-4 mb-3">Address Information</h6>
                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <input type="text" class="form-control" id="address" name="address" value="<?= $buyer['address'] ?? '' ?>">
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4 mb-3 mb-md-0">
                                <label for="city" class="form-label">City</label>
                                <input type="text" class="form-control" id="city" name="city" value="<?= $buyer['city'] ?? '' ?>">
                            </div>
                            <div class="col-md-4 mb-3 mb-md-0">
                                <label for="state" class="form-label">State</label>
                                <input type="text" class="form-control" id="state" name="state" value="<?= $buyer['state'] ?? '' ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="zip_code" class="form-label">ZIP Code</label>
                                <input type="text" class="form-control" id="zip_code" name="zip_code" value="<?= $buyer['zip_code'] ?? '' ?>">
                            </div>
                        </div>

                        <h6 class="fw-bold mt-4 mb-3">Bio</h6>
                        <div class="mb-3">
                            <label for="bio" class="form-label">About Me</label>
                            <textarea class="form-control" id="bio" name="bio" rows="4"><?= htmlspecialchars($buyer['bio'] ?? '') ?></textarea>
                            <div class="form-text">Tell us about yourself and what you're looking for in a property.</div>
                        </div>

                        <h6 class="fw-bold mt-4 mb-3">Change Password</h6>
                        <div class="row mb-3">
                            <div class="col-md-4 mb-3 mb-md-0">
                                <label for="current_password" class="form-label">Current Password</label>
                                <input type="password" class="form-control" id="current_password" name="current_password">
                            </div>
                            <div class="col-md-4 mb-3 mb-md-0">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password">
                            </div>
                            <div class="col-md-4">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                            </div>
                        </div>
                        <div class="form-text mb-4">Leave password fields empty if you don't want to change your password.</div>

                        <div class="d-flex justify-content-end">
                            <a href="dashboard.php" class="btn btn-outline-secondary me-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .avatar-circle {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .avatar-initials {
        color: white;
        font-size: 24px;
        font-weight: bold;
    }
</style>

<?php include '../inc/footer.php'; ?>