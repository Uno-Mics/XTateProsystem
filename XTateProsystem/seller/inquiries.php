<?php
require_once '../inc/db.php';
require_once '../inc/functions.php';
require_once '../inc/auth.php';

// Check if user is logged in and has seller role
checkPermission(['seller']);

// Get seller data
$sellerId = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = ?";
$seller = fetchOne($sql, "i", [$sellerId]);

// Get inquiries for this seller's properties
$sql = "SELECT i.*, p.title as property_title, p.price, p.address, p.city, p.state,
         u.full_name as buyer_name, u.email as buyer_email, u.phone as buyer_phone,
         pi.image_path as property_image
         FROM inquiries i
         JOIN properties p ON i.property_id = p.id
         JOIN users u ON i.buyer_id = u.id
         LEFT JOIN property_images pi ON p.id = pi.property_id AND pi.is_primary = 1
         WHERE p.seller_id = ?
         ORDER BY i.created_at DESC";
$inquiries = fetchAll($sql, "i", [$sellerId]);

// Process status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $inquiryId = isset($_POST['inquiry_id']) ? intval($_POST['inquiry_id']) : 0;
    $status = isset($_POST['status']) ? sanitizeInput($_POST['status']) : '';

    if ($inquiryId > 0 && in_array($status, ['pending', 'responded', 'scheduled', 'completed', 'rejected', 'approved'])) {
        $sql = "UPDATE inquiries SET status = ?, updated_at = NOW() WHERE id = ?";
        $result = updateData($sql, "si", [$status, $inquiryId]);

        if ($result) {
            // Refresh inquiries
            header("Location: inquiries.php?success=1");
            exit;
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
                    <a href="inquiries.php" class="list-group-item list-group-item-action active">
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
                    <a href="profile.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-user-edit me-2"></i> Edit Profile
                    </a>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-lg-9">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Property Inquiries</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($_GET['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle me-2"></i> Inquiry status updated successfully!
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (empty($inquiries)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i> You haven't received any inquiries yet. List more properties to attract buyer interest.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Property</th>
                                        <th>Buyer</th>
                                        <th>Message</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($inquiries as $inquiry): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php
                                                    $imagePath = $inquiry['property_image'];
                                                    if (!empty($imagePath)) {
                                                        if (substr($imagePath, 0, 4) !== 'http') {
                                                            $imagePath = '../uploads/properties/' . basename($imagePath);
                                                            $realPath = dirname(__DIR__) . '/uploads/properties/' . basename($imagePath);
                                                            if (!file_exists($realPath)) {
                                                                $imagePath = '../uploads/properties/default.jpg';
                                                            }
                                                        }
                                                    } else {
                                                        $imagePath = '../uploads/properties/default.jpg';
                                                    }
                                                    ?>
                                                    <div class="flex-shrink-0" style="width: 50px; height: 50px; background-image: url('<?= $imagePath ?>'); background-size: cover; background-position: center;"></div>
                                                    <div class="ms-3">
                                                        <h6 class="mb-0"><?= $inquiry['property_title'] ?></h6>
                                                        <small class="text-muted"><?= formatCurrency($inquiry['price']) ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <div><?= $inquiry['buyer_name'] ?></div>
                                                    <small class="text-muted"><?= $inquiry['buyer_email'] ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-link p-0" data-bs-toggle="modal" data-bs-target="#messageModal-<?= $inquiry['id'] ?>">
                                                    View Message
                                                </button>
                                            </td>
                                            <td><?= formatDateTime($inquiry['created_at']) ?></td>
                                            <td><?= formatInquiryStatus($inquiry['status']) ?></td>
                                            <td>
                                                <div class="btn-group position-relative">
                                                    <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle action-dropdown-btn" data-bs-toggle="dropdown">
                                                        Action
                                                    </button>
                                                    <ul class="dropdown-menu" data-popper-placement="bottom-start">
                                                        <li>
                                                            <form method="POST" action="inquiries.php">
                                                                <input type="hidden" name="action" value="update_status">
                                                                <input type="hidden" name="inquiry_id" value="<?= $inquiry['id'] ?>">
                                                                <input type="hidden" name="status" value="responded">
                                                                <button type="submit" class="dropdown-item">Mark as Responded</button>
                                                            </form>
                                                        </li>
                                                        <li>
                                                            <form method="POST" action="inquiries.php">
                                                                <input type="hidden" name="action" value="update_status">
                                                                <input type="hidden" name="inquiry_id" value="<?= $inquiry['id'] ?>">
                                                                <input type="hidden" name="status" value="scheduled">
                                                                <button type="submit" class="dropdown-item">Mark as Scheduled</button>
                                                            </form>
                                                        </li>
                                                        <li>
                                                            <form method="POST" action="inquiries.php">
                                                                <input type="hidden" name="action" value="update_status">
                                                                <input type="hidden" name="inquiry_id" value="<?= $inquiry['id'] ?>">
                                                                <input type="hidden" name="status" value="completed">
                                                                <button type="submit" class="dropdown-item">Mark as Completed</button>
                                                            </form>
                                                        </li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <form method="POST" action="inquiries.php">
                                                                <input type="hidden" name="action" value="update_status">
                                                                <input type="hidden" name="inquiry_id" value="<?= $inquiry['id'] ?>">
                                                                <input type="hidden" name="status" value="approved">
                                                                <button type="submit" class="dropdown-item text-success">
                                                                    <i class="fas fa-check-circle me-2"></i>Approve Inquiry
                                                                </button>
                                                            </form>
                                                        </li>
                                                        <li>
                                                            <form method="POST" action="inquiries.php">
                                                                <input type="hidden" name="action" value="update_status">
                                                                <input type="hidden" name="inquiry_id" value="<?= $inquiry['id'] ?>">
                                                                <input type="hidden" name="status" value="rejected">
                                                                <button type="submit" class="dropdown-item text-danger">
                                                                    <i class="fas fa-times-circle me-2"></i>Reject Inquiry
                                                                </button>
                                                            </form>
                                                        </li>
                                                    </ul>
                                                </div>
                                                <a href="messages.php?user=<?= $inquiry['buyer_id'] ?>" class="btn btn-sm btn-outline-success ms-1" data-bs-toggle="tooltip" title="Message Buyer">
                                                    <i class="fas fa-comment"></i>
                                                </a>
                                            </td>
                                        </tr>

                                        <!-- Message Modal -->
                                        <div class="modal fade" id="messageModal-<?= $inquiry['id'] ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Inquiry Message</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <div class="fw-bold">Property:</div>
                                                            <div><?= $inquiry['property_title'] ?></div>
                                                            <div class="small text-muted"><?= $inquiry['address'] ?>, <?= $inquiry['city'] ?>, <?= $inquiry['state'] ?></div>
                                                        </div>

                                                        <div class="mb-3">
                                                            <div class="fw-bold">From:</div>
                                                            <div><?= $inquiry['buyer_name'] ?></div>
                                                            <div class="small"><?= $inquiry['buyer_email'] ?> | <?= $inquiry['buyer_phone'] ?></div>
                                                        </div>

                                                        <div class="mb-3">
                                                            <div class="fw-bold">Message:</div>
                                                            <div class="p-3 bg-light rounded">
                                                                <?= nl2br(htmlspecialchars($inquiry['message'])) ?>
                                                            </div>
                                                        </div>

                                                        <div>
                                                            <div class="fw-bold">Date:</div>
                                                            <div><?= formatDateTime($inquiry['created_at']) ?></div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <a href="messages.php?user=<?= $inquiry['buyer_id'] ?>" class="btn btn-primary">
                                                            <i class="fas fa-comment me-2"></i> Message Buyer
                                                        </a>
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>

<?php include '../inc/footer.php'; ?>