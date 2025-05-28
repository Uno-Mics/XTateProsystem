<?php
require_once '../inc/db.php';
require_once '../inc/functions.php';
require_once '../inc/auth.php';

// Get seller data
$sellerId = isset($_GET['id']) ? intval($_GET['id']) : $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = ? AND role = 'seller'";
$seller = fetchOne($sql, "i", [$sellerId]);

// Only allow editing if viewing own profile
$canEdit = $sellerId === $_SESSION['user_id'];

// Process profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    try {
        $fullName = isset($_POST['full_name']) ? sanitizeInput($_POST['full_name']) : '';
        $email = isset($_POST['email']) ? sanitizeInput($_POST['email']) : '';
        $phone = isset($_POST['phone']) ? sanitizeInput($_POST['phone']) : '';
        $companyName = isset($_POST['company_name']) ? sanitizeInput($_POST['company_name']) : '';
        $bio = isset($_POST['bio']) ? sanitizeInput($_POST['bio']) : '';

        // Validate fields
        $errors = [];

        if (empty($fullName)) {
            throw new Exception("Full name is required");
        }

        if (empty($email)) {
            throw new Exception("Email is required");
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Email is invalid");
        }

        // Check if email is already in use by another user
        if ($email !== $seller['email']) {
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

        if (!$result) {
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

        // Redirect with success message
        header("Location: profile.php?success=1");
        exit;

    } catch (Exception $e) {
        $errors[] = $e->getMessage();
    }
}

// Process password update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_password') {
    $currentPassword = isset($_POST['current_password']) ? $_POST['current_password'] : '';
    $newPassword = isset($_POST['new_password']) ? $_POST['new_password'] : '';
    $confirmPassword = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

    // Validate fields
    $passwordErrors = [];

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

<div class="container py-5">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-3 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Seller Dashboard</h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="dashboard.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                    <a href="properties.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-home me-2"></i> My Properties
                    </a>
                    <a href="inquiries.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-file-alt me-2"></i> Inquiries
                    </a>
                    <a href="messages.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-comments me-2"></i> Messages
                        <?php 
                        $unreadCount = getUnreadMessagesCount($sellerId);
                        if ($unreadCount > 0): 
                        ?>
                            <span class="badge bg-danger rounded-pill ms-1"><?= $unreadCount ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="profile.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-user-edit me-2"></i> Edit Profile
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-lg-9">
            <!-- Profile Info Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Profile Information</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($_GET['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle me-2"></i> Profile updated successfully!
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($errors) && !empty($errors)): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= $error ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($canEdit): ?>
                        <form method="POST" action="profile.php">
                            <input type="hidden" name="action" value="update_profile">
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="fullName" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="fullName" name="full_name" value="<?= htmlspecialchars($seller['full_name'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($seller['email'] ?? '') ?>" required>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="phone" class="form-label">Phone</label>
                                    <input type="text" class="form-control" id="phone" name="phone" value="<?= htmlspecialchars($seller['phone'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="companyName" class="form-label">Company Name</label>
                                    <input type="text" class="form-control" id="companyName" name="company_name" value="<?= htmlspecialchars($seller['company_name'] ?? '') ?>">
                                </div>
                            </div>

                            <h6 class="fw-bold mb-3">Address Information</h6>
                            <div class="mb-3">
                                <label for="address" class="form-label">Address</label>
                                <input type="text" class="form-control" id="address" name="address" value="<?= htmlspecialchars($seller['address'] ?? '') ?>">
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="city" class="form-label">City</label>
                                    <input type="text" class="form-control" id="city" name="city" value="<?= htmlspecialchars($seller['city'] ?? '') ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="state" class="form-label">State</label>
                                    <input type="text" class="form-control" id="state" name="state" value="<?= htmlspecialchars($seller['state'] ?? '') ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="zip_code" class="form-label">ZIP Code</label>
                                    <input type="text" class="form-control" id="zip_code" name="zip_code" value="<?= htmlspecialchars($seller['zip_code'] ?? '') ?>">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="bio" class="form-label">Bio</label>
                                <textarea class="form-control" id="bio" name="bio" rows="4"><?= htmlspecialchars($seller['bio'] ?? '') ?></textarea>
                                <div class="form-text">Tell potential buyers about yourself, your experience, and your expertise.</div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </form>
                    <?php else: ?>
                        <p>Viewing seller profile.  Changes cannot be made here.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Password Card -->
            <?php if ($canEdit): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Change Password</h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_GET['password_success'])): ?>
                            <div class="alert alert-success alert-dismissible fade show">
                                <i class="fas fa-check-circle me-2"></i> Password updated successfully!
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($passwordErrors) && !empty($passwordErrors)): ?>
                            <div class="alert alert-danger alert-dismissible fade show">
                                <ul class="mb-0">
                                    <?php foreach ($passwordErrors as $error): ?>
                                        <li><?= $error ?></li>
                                    <?php endforeach; ?>
                                </ul>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="profile.php">
                            <input type="hidden" name="action" value="update_password">
                            
                            <div class="mb-3">
                                <label for="currentPassword" class="form-label">Current Password</label>
                                <input type="password" class="form-control" id="currentPassword" name="current_password" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="newPassword" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="newPassword" name="new_password" required>
                                <div class="form-text">Password must be at least 6 characters long.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirmPassword" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirmPassword" name="confirm_password" required>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Update Password</button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../inc/footer.php'; ?>