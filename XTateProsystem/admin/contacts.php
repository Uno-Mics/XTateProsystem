<?php
require_once '../inc/db.php';
require_once '../inc/functions.php';
require_once '../inc/auth.php';

// Check if user is admin
checkPermission(['admin']);

$adminId = $_SESSION['user_id'];
$adminName = $_SESSION['user_name'] ?? 'Admin';
$contactId = isset($_GET['contact']) ? intval($_GET['contact']) : 0;

// Get all contacts
$sql = "SELECT * FROM contacts ORDER BY created_at DESC";
$contacts = fetchAll($sql);

// Count unread contacts
$unreadContactsCount = 0;
foreach ($contacts as $c) {
    if (($c['status'] ?? 'unread') === 'unread') {
        $unreadContactsCount++;
    }
}

// Pending reports count for sidebar badge
$sql = "SELECT COUNT(*) as count FROM reports WHERE status = 'pending'";
$pendingReportsResult = fetchOne($sql);
$pendingReports = $pendingReportsResult ? $pendingReportsResult['count'] : 0;

include '../inc/header.php';
?>

<div class="db-wrap">
    <div class="db-container">
        <div class="db-layout">

            <!-- ══════════ LEFT: ADMIN SIDEBAR ══════════ -->
            <aside class="db-sidebar">
                <div class="db-card db-profile-card">
                    <div class="db-avatar">
                        <?= strtoupper(substr($adminName, 0, 1)) ?>
                    </div>
                    <h3 class="db-user-name"><?= htmlspecialchars($adminName) ?></h3>
                    <span class="db-user-badge">
                        <i data-lucide="shield" style="width:13px;height:13px;"></i> Super Admin
                    </span>
                </div>

                <div class="db-card">
                    <nav class="db-nav-group">
                        <a href="dashboard.php" class="db-nav-link">
                            <i data-lucide="layout-dashboard" style="width:17px;height:17px;"></i>
                            <span>Dashboard</span>
                        </a>
                        <a href="reports.php" class="db-nav-link">
                            <i data-lucide="flag" style="width:17px;height:17px;"></i>
                            <span>Reports</span>
                            <?php if ($pendingReports > 0): ?>
                                <span class="db-nav-badge"><?= $pendingReports ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="contacts.php" class="db-nav-link active">
                            <i data-lucide="mail" style="width:17px;height:17px;"></i>
                            <span>Contacts</span>
                            <?php if ($unreadContactsCount > 0): ?>
                                <span class="db-nav-badge" style="background:#EFF6FF;color:#2563EB;"><?= $unreadContactsCount ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="users.php" class="db-nav-link">
                            <i data-lucide="users" style="width:17px;height:17px;"></i>
                            <span>Users</span>
                        </a>
                        <a href="properties.php" class="db-nav-link">
                            <i data-lucide="home" style="width:17px;height:17px;"></i>
                            <span>Properties</span>
                        </a>
                    </nav>
                </div>
            </aside>

            <!-- ══════════ RIGHT: MAIN WORKSPACE ══════════ -->
            <main class="db-main">

                <!-- Header Banner -->
                <div class="db-hero-banner">
                    <div>
                        <h1 class="db-hero-title">Contact Messages</h1>
                        <p class="db-hero-desc">Manage public inquiries, consultation requests, and customer communications</p>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="stat-pill stat-pill--total">
                            <i data-lucide="mail" style="width:14px;height:14px;"></i>
                            <span><?= count($contacts) ?> Total Messages</span>
                        </span>
                        <?php if ($unreadContactsCount > 0): ?>
                            <span class="stat-pill stat-pill--unread">
                                <i data-lucide="bell" style="width:14px;height:14px;"></i>
                                <span><?= $unreadContactsCount ?> Unread</span>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Bento Split-Screen Card -->
                <div class="db-card contacts-card-wrapper">
                    <div class="contacts-container">
                        <!-- Contacts List Pane -->
                        <div class="contacts-list">
                            <div class="contacts-header">
                                <div class="contacts-search-box">
                                    <i data-lucide="search" class="search-icon"></i>
                                    <input type="text" class="contacts-search-input" id="searchContacts" placeholder="Search contacts by name, email, subject...">
                                </div>
                            </div>
                            <div class="contacts-body">
                                <?php if (empty($contacts)): ?>
                                    <div class="p-4 text-center text-muted">
                                        <i data-lucide="inbox" style="width:36px;height:36px;margin:0 auto 10px;color:#CBD5E1;display:block;"></i>
                                        <p class="small mb-0">No contact messages received yet.</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($contacts as $contact): ?>
                                    <div class="contact-item <?= ($contact['status'] ?? 'unread') === 'unread' ? 'unread' : '' ?>" 
                                         data-contact-id="<?= $contact['id'] ?>"
                                         data-search-text="<?= strtolower($contact['name'] . ' ' . $contact['email'] . ' ' . $contact['subject']) ?>">
                                        <div class="contact-avatar">
                                            <?php 
                                            $initial = strtoupper(substr($contact['name'] ?? 'U', 0, 1));
                                            $avatarGradients = [
                                                'linear-gradient(135deg, #3B82F6, #1D4ED8)',
                                                'linear-gradient(135deg, #10B981, #059669)',
                                                'linear-gradient(135deg, #8B5CF6, #6D28D9)',
                                                'linear-gradient(135deg, #F59E0B, #D97706)',
                                                'linear-gradient(135deg, #EC4899, #BE185D)'
                                            ];
                                            $grad = $avatarGradients[crc32($contact['name'] ?? 'A') % count($avatarGradients)];
                                            ?>
                                            <div class="avatar-circle" style="background: <?= $grad ?>;">
                                                <?= $initial ?>
                                            </div>
                                        </div>
                                        <div class="contact-info">
                                            <div class="contact-header">
                                                <span class="contact-name"><?= htmlspecialchars($contact['name']) ?></span>
                                                <span class="contact-time"><?= date('M d', strtotime($contact['created_at'])) ?></span>
                                            </div>
                                            <div class="contact-preview">
                                                <div class="contact-subject"><?= htmlspecialchars($contact['subject']) ?></div>
                                                <div class="contact-snippet"><?= htmlspecialchars(substr($contact['message'] ?? '', 0, 60)) ?>...</div>
                                            </div>
                                            <?php if (($contact['status'] ?? 'unread') === 'unread'): ?>
                                                <span class="unread-badge"></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Message View Pane -->
                        <div class="message-view">
                            <!-- Empty State Placeholder -->
                            <div class="message-placeholder">
                                <div class="empty-icon-circle">
                                    <i data-lucide="mail-open"></i>
                                </div>
                                <h4>Select a message</h4>
                                <p>Choose an inquiry from the left to read details, view customer contact information, or send a reply.</p>
                            </div>

                            <!-- Active Content View -->
                            <div class="message-content" style="display: none;">
                                <div class="msg-header-bar">
                                    <div class="msg-title-wrap">
                                        <span class="msg-type-badge contact-type">General</span>
                                        <h2 class="message-subject">Inquiry Subject</h2>
                                    </div>
                                    <div class="message-date-badge">
                                        <i data-lucide="calendar" style="width:13px;height:13px;"></i>
                                        <span class="message-date"></span>
                                    </div>
                                </div>

                                <!-- Sender Info Bento Card -->
                                <div class="sender-info-box">
                                    <div class="sender-top">
                                        <div class="sender-avatar-wrap">
                                            <i data-lucide="user" style="width:20px;height:20px;color:#2563EB;"></i>
                                        </div>
                                        <div>
                                            <div class="sender-name message-sender">Sender Name</div>
                                            <div class="sender-meta-row">
                                                <span class="meta-item"><i data-lucide="mail" style="width:13px;height:13px;"></i> <span class="contact-email"></span></span>
                                                <span class="meta-dot">•</span>
                                                <span class="meta-item"><i data-lucide="phone" style="width:13px;height:13px;"></i> <span class="contact-phone"></span></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Body -->
                                <div class="message-body-wrap">
                                    <h5 class="body-heading">Message Content</h5>
                                    <div class="message-text"></div>
                                </div>

                                <!-- Actions Bar -->
                                <div class="message-actions-bar">
                                    <button type="button" class="btn-action-primary reply-btn">
                                        <i data-lucide="reply" style="width:15px;height:15px;"></i>
                                        <span>Reply</span>
                                    </button>
                                    <button type="button" class="btn-action-danger btn-delete-contact">
                                        <i data-lucide="trash-2" style="width:15px;height:15px;"></i>
                                        <span>Delete Message</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>
</div>

<!-- Reply Modal -->
<div class="modal fade" id="replyModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modern-modal">
            <div class="modal-header">
                <div class="d-flex align-items-center gap-2">
                    <div class="modal-header-icon">
                        <i data-lucide="reply" style="width:18px;height:18px;color:#2563EB;"></i>
                    </div>
                    <h5 class="modal-title">Reply to Contact</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="replyForm">
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold text-uppercase">Recipient</label>
                        <div class="reply-recipient-badge">
                            <i data-lucide="mail" style="width:14px;height:14px;color:#2563EB;"></i>
                            <span class="reply-to fw-semibold"></span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold text-uppercase">Your Message</label>
                        <textarea class="form-control modern-input" rows="6" placeholder="Type your response message here..." required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-modal-cancel" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn-modal-primary send-reply">
                    <i data-lucide="send" style="width:15px;height:15px;"></i>
                    <span>Send Reply</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Contact Confirmation Modal -->
<div class="modal fade" id="deleteContactModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modern-modal">
            <div class="modal-header" style="border-bottom: 1px solid #FEE2E2; background:#FEF2F2;">
                <div class="d-flex align-items-center gap-2">
                    <div class="modal-header-icon" style="background:#FEE2E2;">
                        <i data-lucide="alert-triangle" style="width:18px;height:18px;color:#DC2626;"></i>
                    </div>
                    <h5 class="modal-title" style="color:#991B1B;">Delete Contact Message</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-4 text-center">
                <div class="mb-3">
                    <div style="width:56px;height:56px;border-radius:50%;background:#FEE2E2;color:#DC2626;display:inline-flex;align-items:center;justify-content:center;">
                        <i data-lucide="trash-2" style="width:28px;height:28px;"></i>
                    </div>
                </div>
                <h5 style="color:#0F172A;font-weight:700;margin-bottom:8px;">Delete this message?</h5>
                <p class="text-muted mb-1">Are you sure you want to permanently remove this message?</p>
                <p class="small text-danger mb-0">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-modal-cancel" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn-modal-danger confirm-delete-contact">
                    <i data-lucide="trash-2" style="width:15px;height:15px;"></i>
                    <span>Delete Message</span>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* ── Global layout overrides for Admin ── */
.db-wrap {
    background-color: #F8FAFC;
    min-height: calc(100vh - 70px);
    padding: 32px 0 64px;
    font-family: 'Inter', sans-serif;
    color: #334155;
    overflow-x: hidden;
}

.db-container {
    max-width: 1240px;
    margin: 0 auto;
    padding: 0 16px;
    width: 100%;
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
    background: linear-gradient(135deg, #6366F1, #2563EB);
    color: #fff;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 1.4rem;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 14px rgba(99, 102, 241, 0.25);
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
    color: #2563EB;
    background: #EFF6FF;
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
    background: #FEE2E2;
    color: #DC2626;
    padding: 2px 8px;
    border-radius: 100px;
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

.stat-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.8rem;
    font-weight: 600;
    padding: 6px 14px;
    border-radius: 100px;
}
.stat-pill--total {
    background: #F1F5F9;
    color: #334155;
}
.stat-pill--unread {
    background: #EFF6FF;
    color: #2563EB;
}

/* ── Split-Screen Contacts Layout ── */
.contacts-card-wrapper {
    height: 700px;
}

.contacts-container {
    display: grid;
    grid-template-columns: 340px 1fr;
    height: 100%;
}

@media (max-width: 768px) {
    .contacts-container {
        grid-template-columns: 1fr;
        height: auto;
    }
}

/* Left: Contacts List */
.contacts-list {
    border-right: 1px solid #E2E8F0;
    display: flex;
    flex-direction: column;
    background: #FFFFFF;
    height: 100%;
}

.contacts-header {
    padding: 16px;
    border-bottom: 1px solid #F1F5F9;
}

.contacts-search-box {
    position: relative;
    display: flex;
    align-items: center;
}

.contacts-search-box .search-icon {
    position: absolute;
    left: 12px;
    width: 16px;
    height: 16px;
    color: #94A3B8;
    pointer-events: none;
}

.contacts-search-input {
    width: 100%;
    padding: 9px 12px 9px 36px;
    border-radius: 10px;
    border: 1px solid #E2E8F0;
    font-size: 0.85rem;
    background: #F8FAFC;
    color: #0F172A;
    transition: all .2s ease;
}

.contacts-search-input:focus {
    outline: none;
    border-color: #2563EB;
    background: #FFFFFF;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.contacts-body {
    flex: 1;
    overflow-y: auto;
}

.contact-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 14px 16px;
    border-bottom: 1px solid #F1F5F9;
    cursor: pointer;
    position: relative;
    transition: all .15s ease;
}

.contact-item:hover {
    background: #F8FAFC;
}

.contact-item.active {
    background: #EFF6FF;
    border-left: 3px solid #2563EB;
}

.contact-avatar .avatar-circle {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #FFFFFF;
    font-weight: 700;
    font-size: 0.85rem;
    flex-shrink: 0;
}

.contact-info {
    flex: 1;
    min-width: 0;
}

.contact-header {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    margin-bottom: 2px;
}

.contact-name {
    font-weight: 600;
    font-size: 0.88rem;
    color: #0F172A;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.contact-time {
    font-size: 0.72rem;
    color: #94A3B8;
    white-space: nowrap;
}

.contact-preview .contact-subject {
    font-size: 0.82rem;
    font-weight: 500;
    color: #334155;
    margin-bottom: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.contact-item.unread .contact-subject {
    font-weight: 700;
    color: #0F172A;
}

.contact-preview .contact-snippet {
    font-size: 0.76rem;
    color: #64748B;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.unread-badge {
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #2563EB;
}

/* Right: Message View Pane */
.message-view {
    height: 100%;
    overflow-y: auto;
    background: #FAFAFA;
    display: flex;
    flex-direction: column;
}

.message-placeholder {
    margin: auto;
    padding: 40px 20px;
    text-align: center;
    max-width: 420px;
}

.empty-icon-circle {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: #EFF6FF;
    color: #2563EB;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 16px;
}
.empty-icon-circle svg {
    width: 28px;
    height: 28px;
}

.message-placeholder h4 {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 1.15rem;
    font-weight: 700;
    color: #0F172A;
    margin-bottom: 8px;
}

.message-placeholder p {
    font-size: 0.85rem;
    color: #64748B;
    line-height: 1.5;
    margin: 0;
}

/* Active Message Content */
.message-content {
    padding: 28px;
    display: flex;
    flex-direction: column;
    gap: 20px;
    background: #FFFFFF;
    min-height: 100%;
}

.msg-header-bar {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    padding-bottom: 18px;
    border-bottom: 1px solid #F1F5F9;
}

.msg-type-badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 100px;
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    background: #EFF6FF;
    color: #2563EB;
    margin-bottom: 8px;
}

.message-subject {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 1.35rem;
    font-weight: 800;
    color: #0F172A;
    margin: 0;
    letter-spacing: -0.02em;
}

.message-date-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.78rem;
    color: #64748B;
    background: #F8FAFC;
    border: 1px solid #E2E8F0;
    padding: 5px 12px;
    border-radius: 8px;
    white-space: nowrap;
}

/* Sender Info Box */
.sender-info-box {
    background: #F8FAFC;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    padding: 16px 20px;
}

.sender-top {
    display: flex;
    align-items: center;
    gap: 14px;
}

.sender-avatar-wrap {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    background: #EFF6FF;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.sender-name {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 1rem;
    font-weight: 700;
    color: #0F172A;
    margin-bottom: 3px;
}

.sender-meta-row {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.8rem;
    color: #64748B;
    flex-wrap: wrap;
}

.meta-item {
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.meta-dot {
    color: #CBD5E1;
}

/* Body wrap */
.message-body-wrap {
    background: #FAFAFA;
    border: 1px solid #F1F5F9;
    border-radius: 12px;
    padding: 24px;
    flex: 1;
}

.body-heading {
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #94A3B8;
    margin-bottom: 12px;
}

.message-text {
    font-size: 0.92rem;
    line-height: 1.7;
    color: #334155;
    white-space: pre-line;
}

/* Action bar */
.message-actions-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding-top: 16px;
    border-top: 1px solid #F1F5F9;
}

.btn-action-primary {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #2563EB;
    color: #FFFFFF;
    font-weight: 600;
    font-size: 0.88rem;
    padding: 9px 20px;
    border-radius: 10px;
    border: none;
    cursor: pointer;
    transition: all .2s ease;
    box-shadow: 0 2px 8px rgba(37, 99, 235, 0.25);
}

.btn-action-primary:hover {
    background: #1D4ED8;
    color: #FFFFFF;
}

.btn-action-danger {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #FEE2E2;
    color: #DC2626;
    font-weight: 600;
    font-size: 0.88rem;
    padding: 9px 18px;
    border-radius: 10px;
    border: none;
    cursor: pointer;
    transition: all .2s ease;
}

.btn-action-danger:hover {
    background: #FCA5A5;
    color: #B91C1C;
}

/* Modal styling */
.modern-modal {
    border-radius: 16px;
    border: 1px solid #E2E8F0;
    box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04);
}

.modal-header-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: #EFF6FF;
    display: flex;
    align-items: center;
    justify-content: center;
}

.reply-recipient-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #F1F5F9;
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 0.85rem;
    color: #0F172A;
}

.modern-input {
    border-radius: 10px;
    border: 1px solid #E2E8F0;
    padding: 10px 14px;
    font-size: 0.88rem;
}

.modern-input:focus {
    border-color: #2563EB;
    box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
}

.btn-modal-cancel {
    background: #F1F5F9;
    color: #475569;
    font-weight: 600;
    font-size: 0.85rem;
    padding: 8px 16px;
    border-radius: 8px;
    border: none;
}

.btn-modal-primary {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #2563EB;
    color: #FFFFFF;
    font-weight: 600;
    font-size: 0.85rem;
    padding: 8px 18px;
    border-radius: 8px;
    border: none;
    box-shadow: 0 2px 8px rgba(37,99,235,0.25);
}

.btn-modal-danger {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #DC2626;
    color: #FFFFFF;
    font-weight: 600;
    font-size: 0.85rem;
    padding: 8px 18px;
    border-radius: 8px;
    border: none;
    box-shadow: 0 2px 8px rgba(220,38,38,0.25);
    cursor: pointer;
}
.btn-modal-danger:hover {
    background: #B91C1C;
}
</style>

<script src="../js/admin-contacts.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});
</script>

<?php include '../inc/footer.php'; ?>
