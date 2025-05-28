<?php
require_once '../inc/db.php';
require_once '../inc/functions.php';
require_once '../inc/auth.php';

// Check if user is admin
checkPermission(['admin']);

// Handle report status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $reportId = isset($_POST['report_id']) ? intval($_POST['report_id']) : 0;
    $status = isset($_POST['status']) ? sanitizeInput($_POST['status']) : '';
    
    if ($reportId && in_array($status, ['pending', 'reviewed', 'resolved', 'dismissed'])) {
        if ($status === 'dismissed') {
            // Delete the report if dismissed
            $sql = "DELETE FROM reports WHERE id = ?";
            deleteData($sql, "i", [$reportId]);
        } else {
            // Update status for other cases
            $sql = "UPDATE reports SET status = ?, updated_at = NOW() WHERE id = ?";
            updateData($sql, "si", [$status, $reportId]);
        }
    }
}

// Get all reports with related information
$sql = "SELECT r.*, 
        p.title as property_title,
        u1.full_name as reporter_name,
        u2.full_name as seller_name
        FROM reports r
        LEFT JOIN properties p ON r.property_id = p.id
        LEFT JOIN users u1 ON r.reporter_id = u1.id
        LEFT JOIN users u2 ON r.seller_id = u2.id
        ORDER BY r.created_at DESC";

$reports = fetchAll($sql, "", []);

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
                    <a href="reports.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-flag me-2"></i> Reports
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
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Reported Listings & Sellers</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Reporter</th>
                                    <th>Type</th>
                                    <th>Subject</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                    <th>Message</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reports as $report): ?>
                                <tr>
                                    <td><?= formatDateTime($report['created_at']) ?></td>
                                    <td><?= htmlspecialchars($report['reporter_name']) ?></td>
                                    <td><?= $report['property_id'] ? 'Property' : 'Seller' ?></td>
                                    <td>
                                        <?php if ($report['property_id']): ?>
                                            <a href="../property_details.php?id=<?= $report['property_id'] ?>" target="_blank">
                                                <?= htmlspecialchars($report['property_title']) ?>
                                            </a>
                                        <?php else: ?>
                                            <?= htmlspecialchars($report['seller_name']) ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($report['reason']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= getStatusBadgeClass($report['status']) ?>">
                                            <?= ucfirst($report['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="../admin/messages.php?user=<?= $report['reporter_id'] ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-comments"></i> Message
                                        </a>
                                    </td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                Update Status
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li>
                                                    <form method="POST">
                                                        <input type="hidden" name="action" value="update_status">
                                                        <input type="hidden" name="report_id" value="<?= $report['id'] ?>">
                                                        <button type="submit" name="status" value="reviewed" class="dropdown-item">Mark as Reviewed</button>
                                                        <button type="submit" name="status" value="resolved" class="dropdown-item">Mark as Resolved</button>
                                                        <button type="submit" name="status" value="dismissed" class="dropdown-item">Dismiss Report</button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'pending':
            return 'warning';
        case 'reviewed':
            return 'info';
        case 'resolved':
            return 'success';
        case 'dismissed':
            return 'secondary';
        default:
            return 'primary';
    }
}

include '../inc/footer.php'; 
?>