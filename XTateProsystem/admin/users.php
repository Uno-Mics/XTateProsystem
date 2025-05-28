<?php
require_once '../inc/db.php';
require_once '../inc/functions.php';
require_once '../inc/auth.php';

// Check if user is admin
checkPermission(['admin']);

// Get all users
$sql = "SELECT * FROM users WHERE role != 'admin' ORDER BY role, created_at DESC";
$users = fetchAll($sql);

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
                <div class="card-header">
                    <h5 class="card-title mb-0">User Management</h5>
                </div>
                <div class="card-body">
                    <ul class="nav nav-tabs mb-4">
                        <li class="nav-item">
                            <a class="nav-link active" href="#buyers" data-bs-toggle="tab">Buyers</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#sellers" data-bs-toggle="tab">Sellers</a>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane active" id="buyers">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Phone</th>
                                            <th>Joined Date</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $user): ?>
                                            <?php if ($user['role'] === 'buyer'): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($user['full_name']) ?></td>
                                                <td><?= htmlspecialchars($user['email']) ?></td>
                                                <td><?= htmlspecialchars($user['phone'] ?? 'N/A') ?></td>
                                                <td><?= formatDateTime($user['created_at']) ?></td>
                                                <td><span class="badge <?= $user['status'] === 'active' ? 'bg-success' : 'bg-danger' ?>"><?= ucfirst($user['status'] ?? 'active') ?></span></td>
                                                <td>
                                                    <div class="dropdown">
                                                        <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                            Options
                                                        </button>
                                                        <ul class="dropdown-menu">
                                                            <li>
                                                                <button class="dropdown-item edit-user-btn"
                                                                        data-user-id="<?= $user['id'] ?>"
                                                                        data-name="<?= htmlspecialchars($user['full_name']) ?>"
                                                                        data-email="<?= htmlspecialchars($user['email']) ?>"
                                                                        data-phone="<?= htmlspecialchars($user['phone'] ?? '') ?>"
                                                                        data-role="<?= $user['role'] ?>">
                                                                    <i class="fas fa-edit me-2"></i> Edit Account
                                                                </button>
                                                            </li>
                                                            <li>
                                                                <button class="dropdown-item status-toggle-btn" 
                                                                        data-user-id="<?= $user['id'] ?>" 
                                                                        data-current-status="<?= $user['status'] ?>">
                                                                    <i class="fas fa-power-off me-2"></i>
                                                                    <?= $user['status'] === 'active' ? 'Deactivate Account' : 'Activate Account' ?>
                                                                </button>
                                                            </li>
                                                            <li><hr class="dropdown-divider"></li>
                                                            <li>
                                                                <button class="dropdown-item text-danger delete-user-btn"
                                                                        data-user-id="<?= $user['id'] ?>"
                                                                        data-name="<?= htmlspecialchars($user['full_name']) ?>">
                                                                    <i class="fas fa-trash-alt me-2"></i> Delete Account
                                                                </button>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="tab-pane" id="sellers">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Phone</th>
                                            <th>Joined Date</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $user): ?>
                                            <?php if ($user['role'] === 'seller'): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($user['full_name']) ?></td>
                                                <td><?= htmlspecialchars($user['email']) ?></td>
                                                <td><?= htmlspecialchars($user['phone'] ?? 'N/A') ?></td>
                                                <td><?= formatDateTime($user['created_at']) ?></td>
                                                <td><span class="badge <?= $user['status'] === 'active' ? 'bg-success' : 'bg-danger' ?>"><?= ucfirst($user['status'] ?? 'active') ?></span></td>
                                                <td>
                                                    <div class="dropdown">
                                                        <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                            Options
                                                        </button>
                                                        <ul class="dropdown-menu">
                                                            <li>
                                                                <button class="dropdown-item edit-user-btn"
                                                                        data-user-id="<?= $user['id'] ?>"
                                                                        data-name="<?= htmlspecialchars($user['full_name']) ?>"
                                                                        data-email="<?= htmlspecialchars($user['email']) ?>"
                                                                        data-phone="<?= htmlspecialchars($user['phone'] ?? '') ?>"
                                                                        data-role="<?= $user['role'] ?>">
                                                                    <i class="fas fa-edit me-2"></i> Edit Account
                                                                </button>
                                                            </li>
                                                            <li>
                                                                <button class="dropdown-item status-toggle-btn" 
                                                                        data-user-id="<?= $user['id'] ?>" 
                                                                        data-current-status="<?= $user['status'] ?>">
                                                                    <i class="fas fa-power-off me-2"></i>
                                                                    <?= $user['status'] === 'active' ? 'Deactivate Account' : 'Activate Account' ?>
                                                                </button>
                                                            </li>
                                                            <li><hr class="dropdown-divider"></li>
                                                            <li>
                                                                <button class="dropdown-item text-danger delete-user-btn"
                                                                        data-user-id="<?= $user['id'] ?>"
                                                                        data-name="<?= htmlspecialchars($user['full_name']) ?>">
                                                                    <i class="fas fa-trash-alt me-2"></i> Delete Account
                                                                </button>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit User Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editUserForm">
                    <input type="hidden" name="user_id" id="editUserId">
                    <div class="mb-3">
                        <label for="editFullName" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="editFullName" name="full_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="editEmail" class="form-label">Email</label>
                        <input type="email" class="form-control" id="editEmail" name="email" required readonly>
                    </div>
                    <div class="mb-3">
                        <label for="editPhone" class="form-label">Phone</label>
                        <input type="tel" class="form-control" id="editPhone" name="phone">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveUserChanges">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete User Modal -->
<div class="modal fade" id="deleteUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Delete User Account</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <i class="fas fa-exclamation-triangle text-danger" style="font-size: 4rem;"></i>
                </div>
                <p class="text-center">Are you sure you want to delete the account for <strong id="deleteUserName"></strong>?</p>
                <p class="text-center text-danger">This action cannot be undone!</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteUser">Delete Account</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize modals
    const editModal = new bootstrap.Modal(document.getElementById('editUserModal'));
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteUserModal'));
    let currentUserId = null;

    // Edit user functionality
    document.querySelectorAll('.edit-user-btn').forEach(button => {
        button.addEventListener('click', function() {
            const userId = this.getAttribute('data-user-id');
            const name = this.getAttribute('data-name');
            const email = this.getAttribute('data-email');
            const phone = this.getAttribute('data-phone');
            const role = this.getAttribute('data-role');

            document.getElementById('editUserId').value = userId;
            document.getElementById('editFullName').value = name;
            document.getElementById('editEmail').value = email;
            document.getElementById('editPhone').value = phone;

            // Store current role for redirection
            const currentRole = role;
            
            // Handle edit click based on user role
            if (currentRole === 'buyer') {
                window.location.href = 'buyer_profile.php?id=' + userId;
            } else if (currentRole === 'seller') {
                window.location.href = 'seller_profile.php?id=' + userId;
            }
        });
    });

    // Save user changes
    document.getElementById('saveUserChanges').addEventListener('click', function() {
        const form = document.getElementById('editUserForm');
        const formData = new FormData(form);
        formData.append('action', 'update_user');

        fetch('../api/users.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success notification
                const notification = document.createElement('div');
                notification.className = 'alert alert-success position-fixed top-0 start-50 translate-middle-x mt-3';
                notification.style.zIndex = '9999';
                notification.innerHTML = `
                    <div class="d-flex align-items-center">
                        <i class="fas fa-check-circle me-2"></i>
                        <span>User account updated successfully!</span>
                    </div>
                `;
                document.body.appendChild(notification);
                setTimeout(() => notification.remove(), 3000);

                // Close modal and reload page
                editModal.hide();
                setTimeout(() => location.reload(), 1000);
            }
        })
        .catch(error => console.error('Error:', error));
    });

    // Delete user functionality
    document.querySelectorAll('.delete-user-btn').forEach(button => {
        button.addEventListener('click', function() {
            currentUserId = this.getAttribute('data-user-id');
            const userName = this.getAttribute('data-name');
            document.getElementById('deleteUserName').textContent = userName;
            deleteModal.show();
        });
    });

    // Confirm delete user
    document.getElementById('confirmDeleteUser').addEventListener('click', function() {
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
                // Show success notification
                const notification = document.createElement('div');
                notification.className = 'alert alert-success position-fixed top-0 start-50 translate-middle-x mt-3';
                notification.style.zIndex = '9999';
                notification.innerHTML = `
                    <div class="d-flex align-items-center">
                        <i class="fas fa-check-circle me-2"></i>
                        <span>User account deleted successfully!</span>
                    </div>
                `;
                document.body.appendChild(notification);
                setTimeout(() => notification.remove(), 3000);

                // Close modal and reload page
                deleteModal.hide();
                setTimeout(() => location.reload(), 1000);
            }
        })
        .catch(error => console.error('Error:', error));
    });
});
</script>

<?php include '../inc/footer.php'; ?>