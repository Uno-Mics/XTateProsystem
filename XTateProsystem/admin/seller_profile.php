
<?php
require_once '../inc/db.php';
require_once '../inc/functions.php';
require_once '../inc/auth.php';

// Check if user is admin
checkPermission(['admin']);

// Get seller data
$sellerId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$sql = "SELECT * FROM users WHERE id = ? AND role = 'seller'";
$seller = fetchOne($sql, "i", [$sellerId]);

if (!$seller) {
    header("Location: users.php");
    exit;
}

// Handle form submission
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
        $companyName = sanitizeInput($_POST['company_name'] ?? '');

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
                company_name = ?,
                updated_at = NOW() 
                WHERE id = ?";

        $params = [$fullName, $phone, $address, $city, $state, $zipCode, $bio, $companyName, $sellerId];
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

include '../inc/header.php';
?>

<div class="container py-5">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-3 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Admin Dashboard</h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="dashboard.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                    <a href="reports.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-flag me-2"></i> Reports
                    </a>
                    <a href="users.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-users me-2"></i> Users
                    </a>
                    <a href="properties.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-home me-2"></i> Properties
                    </a>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-lg-9">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Edit Seller Profile</h5>
                    <a href="users.php" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Back to Users
                    </a>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="avatar-circle me-3" style="background-color: <?= generateAvatarColor($seller['id']) ?>">
                                <span class="avatar-initials"><?= strtoupper(substr($seller['full_name'] ?? 'S', 0, 1)) ?></span>
                            </div>
                            <div>
                                <h5 class="mb-0"><?= htmlspecialchars($seller['full_name']) ?></h5>
                                <p class="text-muted mb-0"><?= ucfirst($seller['role']) ?> Account</p>
                            </div>
                        </div>
                        <div class="text-muted small">Member since <?= formatDateTime($seller['created_at'], 'F Y') ?></div>
                    </div>

                    <?php if (isset($_GET['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle me-2"></i> Profile updated successfully!
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-circle me-2"></i> <?= $error ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <h6 class="fw-bold mb-3">Profile Information</h6>
                        <div class="row mb-3">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label for="full_name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" value="<?= htmlspecialchars($seller['full_name']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" value="<?= htmlspecialchars($seller['email']) ?>" disabled>
                                <div class="form-text">Email address cannot be changed</div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="tel" class="form-control" id="phone" name="phone" value="<?= htmlspecialchars($seller['phone'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="company_name" class="form-label">Company Name</label>
                                <input type="text" class="form-control" id="company_name" name="company_name" value="<?= htmlspecialchars($seller['company_name'] ?? '') ?>">
                            </div>
                        </div>

                        <h6 class="fw-bold mt-4 mb-3">Address Information</h6>
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

                        <h6 class="fw-bold mt-4 mb-3">Bio</h6>
                        <div class="mb-3">
                            <label for="bio" class="form-label">About Me</label>
                            <textarea class="form-control" id="bio" name="bio" rows="4"><?= htmlspecialchars($seller['bio'] ?? '') ?></textarea>
                            <div class="form-text">Tell potential buyers about yourself, your experience, and your expertise.</div>
                        </div>

                        <div class="d-flex justify-content-end">
                            <a href="users.php" class="btn btn-outline-secondary me-2">Cancel</a>
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
