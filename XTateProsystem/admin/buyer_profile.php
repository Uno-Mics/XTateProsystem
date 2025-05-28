
<?php
require_once '../inc/db.php';
require_once '../inc/functions.php';
require_once '../inc/auth.php';

// Check if user is admin
checkPermission(['admin']);

// Get buyer data
$buyerId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$sql = "SELECT * FROM users WHERE id = ? AND role = 'buyer'";
$buyer = fetchOne($sql, "i", [$buyerId]);

if (!$buyer) {
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

        // Refresh buyer data
        $sql = "SELECT * FROM users WHERE id = ? AND role = 'buyer'";
        $buyer = fetchOne($sql, "i", [$buyerId]);

        // Redirect with success message
        header("Location: buyer_profile.php?id=" . $buyerId . "&success=1");
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
                    <h5 class="card-title mb-0">Edit Buyer Profile</h5>
                    <a href="users.php" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Back to Users
                    </a>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="avatar-circle me-3" style="background-color: <?= generateAvatarColor($buyer['id']) ?>">
                                <span class="avatar-initials"><?= strtoupper(substr($buyer['full_name'] ?? 'B', 0, 1)) ?></span>
                            </div>
                            <div>
                                <h5 class="mb-0"><?= htmlspecialchars($buyer['full_name']) ?></h5>
                                <p class="text-muted mb-0"><?= ucfirst($buyer['role']) ?> Account</p>
                            </div>
                        </div>
                        <div class="text-muted small">Member since <?= formatDateTime($buyer['created_at'], 'F Y') ?></div>
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
                        <h6 class="fw-bold mb-3">Personal Information</h6>
                        <div class="row mb-3">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label for="full_name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" value="<?= htmlspecialchars($buyer['full_name']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" value="<?= htmlspecialchars($buyer['email']) ?>" disabled>
                                <div class="form-text">Email address cannot be changed</div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone" value="<?= htmlspecialchars($buyer['phone'] ?? '') ?>">
                            </div>
                        </div>

                        <h6 class="fw-bold mt-4 mb-3">Address Information</h6>
                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <input type="text" class="form-control" id="address" name="address" value="<?= htmlspecialchars($buyer['address'] ?? '') ?>">
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="city" class="form-label">City</label>
                                <input type="text" class="form-control" id="city" name="city" value="<?= htmlspecialchars($buyer['city'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="state" class="form-label">State</label>
                                <input type="text" class="form-control" id="state" name="state" value="<?= htmlspecialchars($buyer['state'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="zip_code" class="form-label">ZIP Code</label>
                                <input type="text" class="form-control" id="zip_code" name="zip_code" value="<?= htmlspecialchars($buyer['zip_code'] ?? '') ?>">
                            </div>
                        </div>

                        <h6 class="fw-bold mt-4 mb-3">Bio</h6>
                        <div class="mb-3">
                            <label for="bio" class="form-label">About Me</label>
                            <textarea class="form-control" id="bio" name="bio" rows="4"><?= htmlspecialchars($buyer['bio'] ?? '') ?></textarea>
                            <div class="form-text">Tell us about yourself and what you're looking for in a property.</div>
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
