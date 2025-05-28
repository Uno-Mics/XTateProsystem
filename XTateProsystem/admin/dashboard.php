<?php
require_once '../inc/db.php';
require_once '../inc/functions.php';
require_once '../inc/auth.php';

// Check if user is admin
checkPermission(['admin']);

// Get admin name from database
$sql = "SELECT full_name FROM users WHERE id = 1 AND email = 'admin@example.com' AND role = 'admin'";
$admin = fetchOne($sql, "", []);
$adminName = $admin ? $admin['full_name'] : 'Admin User';

// Get statistics
$totalProperties = fetchOne("SELECT COUNT(*) as count FROM properties", "", [])['count'] ?? 0;
$activeProperties = fetchOne("SELECT COUNT(*) as count FROM properties WHERE status = 'active'", "", [])['count'] ?? 0;
$totalUsers = fetchOne("SELECT COUNT(*) as count FROM users WHERE role != 'admin'", "", [])['count'] ?? 0;
$totalReports = fetchOne("SELECT COUNT(*) as count FROM reports", "", [])['count'] ?? 0;
$pendingReports = fetchOne("SELECT COUNT(*) as count FROM reports WHERE status = 'pending'", "", [])['count'] ?? 0;
$totalPropertyValue = fetchOne("SELECT SUM(price) as total FROM properties WHERE status = 'active'", "", [])['total'] ?? 0;

// Get monthly registration stats
$monthlyUsers = fetchAll("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count 
                         FROM users WHERE role != 'admin'
                         GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                         ORDER BY month DESC LIMIT 6");

// Get property type distribution
$propertyTypes = fetchAll("SELECT property_types.name, COUNT(*) as count 
                          FROM properties 
                          JOIN property_types ON properties.property_type_id = property_types.id 
                          GROUP BY property_types.id");

$_SESSION['user_name'] = $adminName;
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
                    <a href="dashboard.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                    <a href="reports.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-flag me-2"></i> Reports
                    </a>
                    <a href="contacts.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-envelope me-2"></i> Contacts
                    </a>
                    <a href="users.php" class="list-group-item list-group-item-action">
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
            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card stat-card blue">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-uppercase mb-2">TOTAL REPORTS</h6>
                                    <h2 class="mb-0"><?= $totalReports ?></h2>
                                </div>
                                <div class="stat-icon">
                                    <i class="fas fa-flag"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card stat-card orange">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-uppercase mb-2">PENDING REPORTS</h6>
                                    <h2 class="mb-0"><?= $pendingReports ?></h2>
                                </div>
                                <div class="stat-icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card stat-card green">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-uppercase mb-2">TOTAL PROPERTIES</h6>
                                    <h2 class="mb-0"><?= $totalProperties ?></h2>
                                </div>
                                <div class="stat-icon">
                                    <i class="fas fa-home"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card stat-card lightblue">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-uppercase mb-2">TOTAL USERS</h6>
                                    <h2 class="mb-0"><?= $totalUsers ?></h2>
                                </div>
                                <div class="stat-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts and Property Value -->
            <div class="row">
                <div class="col-xl-6 mb-4">
                    <div class="card shadow">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Monthly User Registrations</h6>
                        </div>
                        <div class="card-body">
                            <canvas id="userRegistrationChart"></canvas>
                        </div>
                    </div>
                    
                    <!-- Total Property Value Card -->
                    <div class="card shadow mt-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Total Property Value</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-center">
                                <h2 class="display-4 mb-0 text-primary">$<?= number_format($totalPropertyValue, 2) ?></h2>
                                <i class="fas fa-dollar-sign ms-3 text-primary" style="font-size: 2.5rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-6 mb-4">
                    <div class="card shadow">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Property Type Distribution</h6>
                        </div>
                        <div class="card-body">
                            <canvas id="propertyTypeChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // User Registration Chart
    const userCtx = document.getElementById('userRegistrationChart').getContext('2d');
    new Chart(userCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode(array_column(array_reverse($monthlyUsers), 'month')) ?>,
            datasets: [{
                label: 'New Users',
                data: <?= json_encode(array_column(array_reverse($monthlyUsers), 'count')) ?>,
                borderColor: 'rgb(75, 192, 192)',
                tension: 0.1
            }]
        }
    });

    // Property Type Chart
    const propertyCtx = document.getElementById('propertyTypeChart').getContext('2d');
    new Chart(propertyCtx, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_column($propertyTypes, 'name')) ?>,
            datasets: [{
                data: <?= json_encode(array_column($propertyTypes, 'count')) ?>,
                backgroundColor: [
                    '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796'
                ]
            }]
        }
    });
});
</script>

<script src="../js/auth.js"></script>
<script src="../js/admin-dashboard.js"></script>
<?php include '../inc/footer.php'; ?>