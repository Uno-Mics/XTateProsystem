
<?php
require_once '../inc/db.php';
require_once '../inc/functions.php';
require_once '../inc/auth.php';

// Check if user is admin
checkPermission(['admin']);

$adminId = $_SESSION['user_id'];
$contactId = isset($_GET['contact']) ? intval($_GET['contact']) : 0;

// Get all contacts
$sql = "SELECT * FROM contacts ORDER BY created_at DESC";
$contacts = fetchAll($sql);

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
                    <a href="contacts.php" class="list-group-item list-group-item-action active">
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
            <div class="contacts-container">
                <!-- Contacts List -->
                <div class="contacts-list">
                    <div class="contacts-header">
                        <h5 class="mb-0">Contact Messages</h5>
                        <div class="contacts-actions">
                            <div class="input-group">
                                <input type="text" class="form-control" id="searchContacts" placeholder="Search contacts...">
                                <button class="btn btn-outline-secondary" type="button">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="contacts-body">
                        <?php foreach ($contacts as $contact): ?>
                        <div class="contact-item <?= $contact['status'] === 'unread' ? 'unread' : '' ?>" 
                             data-contact-id="<?= $contact['id'] ?>"
                             data-search-text="<?= strtolower($contact['name'] . ' ' . $contact['email'] . ' ' . $contact['subject']) ?>">
                            <div class="contact-avatar">
                                <?php 
                                $initial = strtoupper(substr($contact['name'], 0, 1));
                                $bgColor = sprintf('#%06X', mt_rand(0, 0xFFFFFF));
                                ?>
                                <div class="avatar-circle" style="background-color: <?= $bgColor ?>;">
                                    <?= $initial ?>
                                </div>
                            </div>
                            <div class="contact-info">
                                <div class="contact-header">
                                    <span class="contact-name"><?= htmlspecialchars($contact['name']) ?></span>
                                    <span class="contact-time"><?= formatDateTime($contact['created_at']) ?></span>
                                </div>
                                <div class="contact-preview">
                                    <div class="contact-subject"><?= htmlspecialchars($contact['subject']) ?></div>
                                    <div class="contact-snippet"><?= htmlspecialchars(substr($contact['message'], 0, 50)) ?>...</div>
                                </div>
                                <?php if ($contact['status'] === 'unread'): ?>
                                <span class="unread-badge"></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Message View -->
                <div class="message-view">
                    <div class="message-placeholder">
                        <div class="text-center">
                            <i class="fas fa-envelope-open fa-3x text-muted mb-3"></i>
                            <h5>Select a message</h5>
                            <p class="text-muted">Choose a contact message from the list to view details</p>
                        </div>
                    </div>
                    <div class="message-content" style="display: none;">
                        <div class="message-header">
                            <h5 class="message-subject"></h5>
                            <div class="message-meta">
                                <span class="message-sender"></span>
                                <span class="message-date"></span>
                            </div>
                        </div>
                        <div class="message-body">
                            <div class="message-text"></div>
                            <div class="contact-details mt-4">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Email:</strong> <span class="contact-email"></span></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Phone:</strong> <span class="contact-phone"></span></p>
                                    </div>
                                </div>
                                <p><strong>User Type:</strong> <span class="contact-type"></span></p>
                            </div>
                        </div>
                        <div class="message-actions mt-4">
                            <button class="btn btn-primary reply-btn">
                                <i class="fas fa-reply me-2"></i>Reply
                            </button>
                            <button class="btn btn-danger delete-btn">
                                <i class="fas fa-trash me-2"></i>Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reply Modal -->
<div class="modal fade" id="replyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reply to Message</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="replyForm">
                    <div class="mb-3">
                        <label class="form-label">To: <span class="reply-to"></span></label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Message</label>
                        <textarea class="form-control" rows="5" required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary send-reply">Send Reply</button>
            </div>
        </div>
    </div>
</div>

<script src="../js/admin-contacts.js"></script>
<?php include '../inc/footer.php'; ?>
