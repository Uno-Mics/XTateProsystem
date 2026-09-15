<?php
require_once '../inc/db.php';
require_once '../inc/functions.php';
require_once '../inc/auth.php';

// Check if user is admin
checkPermission(['admin']);

$adminId = $_SESSION['user_id'];
$adminName = $_SESSION['user_name'] ?? 'Admin';

// Get conversation partner
$partnerId = isset($_GET['user']) ? intval($_GET['user']) : 0;
$contactEmail = isset($_GET['contact_email']) ? sanitizeInput($_GET['contact_email']) : '';
$partnerData = null;

if ($partnerId > 0) {
    $sql = "SELECT id, full_name, email, role FROM users WHERE id = ?";
    $partnerData = fetchOne($sql, "i", [$partnerId]);
} else if (!empty($contactEmail)) {
    // First check if user exists
    $sql = "SELECT id, full_name, email, role FROM users WHERE email = ?";
    $partnerData = fetchOne($sql, "s", [$contactEmail]);

    if ($partnerData) {
        $partnerId = $partnerData['id'];
    } else {
        // Create a new user account for the contact
        $sql = "INSERT INTO users (email, full_name, role, status, created_at) VALUES (?, ?, 'buyer', 'active', NOW())";
        $fullName = explode('@', $contactEmail)[0]; // Use email username as full name
        $newUserId = insertData($sql, "ss", [$contactEmail, $fullName]);

        if ($newUserId) {
            $sql = "SELECT id, full_name, email, role FROM users WHERE id = ?";
            $partnerData = fetchOne($sql, "i", [$newUserId]);
            $partnerId = $newUserId;
        }
    }
}

// Get all messages with this partner if exists
$messages = [];
if ($partnerData) {
    $sql = "SELECT m.*, 
            s.full_name as sender_name, 
            r.full_name as receiver_name 
            FROM messages m
            JOIN users s ON m.sender_id = s.id
            JOIN users r ON m.receiver_id = r.id
            WHERE (m.sender_id = ? AND m.receiver_id = ?) 
               OR (m.sender_id = ? AND m.receiver_id = ?)
            ORDER BY m.created_at ASC";

    $messages = fetchAll($sql, "iiii", [$adminId, $partnerId, $partnerId, $adminId]);
}

// Get unique conversation partners (combining reports and messages)
$sql = "SELECT DISTINCT u.id, u.full_name, u.email, u.role, COALESCE(r.created_at, m.created_at) as last_activity
        FROM users u
        LEFT JOIN reports r ON r.reporter_id = u.id
        LEFT JOIN messages m ON (m.sender_id = u.id AND m.receiver_id = ?) 
                            OR (m.receiver_id = u.id AND m.sender_id = ?)
        WHERE u.id != ? 
        AND (r.reporter_id IS NOT NULL OR m.id IS NOT NULL)
        GROUP BY u.id
        ORDER BY last_activity DESC";

$partners = fetchAll($sql, "iii", [$adminId, $adminId, $adminId]);

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
                        <i data-lucide="shield-check" style="width:13px;height:13px;"></i> Super Admin
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
                        <a href="contacts.php" class="db-nav-link">
                            <i data-lucide="mail" style="width:17px;height:17px;"></i>
                            <span>Contacts</span>
                        </a>
                        <a href="messages.php" class="db-nav-link active">
                            <i data-lucide="message-square" style="width:17px;height:17px;"></i>
                            <span>Messages</span>
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
                        <h1 class="db-hero-title">Direct Messages &amp; Inquiries</h1>
                        <p class="db-hero-sub">Communicate directly with reporters, customer inquiries, and registered users</p>
                    </div>
                    <div class="db-hero-actions">
                        <a href="contacts.php" class="db-btn db-btn-secondary">
                            <i data-lucide="arrow-left" style="width:15px;height:15px;"></i>
                            <span>Back to Contacts</span>
                        </a>
                    </div>
                </div>

                <!-- Bento Chat Workspace Card -->
                <div class="db-card chat-card-wrapper">
                    <div class="messaging-container">

                        <!-- Left: Partners / Threads List -->
                        <div class="partners-list">
                            <div class="partners-header">
                                <div class="d-flex align-items-center justify-content-between">
                                    <h6 class="partners-title">Conversations</h6>
                                    <span class="badge-count"><?= count($partners) ?></span>
                                </div>
                            </div>
                            <div class="partners-body">
                                <?php if (empty($partners) && !$partnerData): ?>
                                    <div class="p-4 text-center text-muted">
                                        <i data-lucide="message-square-dashed" style="width:36px;height:36px;margin:0 auto 8px;color:#CBD5E1;display:block;"></i>
                                        <p class="small mb-0">No active threads yet</p>
                                    </div>
                                <?php else: ?>
                                    <?php 
                                    // If partnerData is not in partners list (new contact), show it at top
                                    $inList = false;
                                    foreach ($partners as $p) {
                                        if ($partnerData && $p['id'] == $partnerData['id']) {
                                            $inList = true;
                                            break;
                                        }
                                    }
                                    if ($partnerData && !$inList): 
                                        $initial = strtoupper(substr($partnerData['full_name'] ?? 'U', 0, 1));
                                    ?>
                                        <a href="?user=<?= $partnerData['id'] ?>" class="partner-item active">
                                            <div class="avatar-circle" style="background: linear-gradient(135deg, #2563EB, #1D4ED8);">
                                                <?= $initial ?>
                                            </div>
                                            <div class="partner-info">
                                                <div class="partner-name-row">
                                                    <span class="partner-name"><?= htmlspecialchars($partnerData['full_name']) ?></span>
                                                </div>
                                                <span class="partner-role-pill"><?= ucfirst($partnerData['role'] ?? 'Contact') ?></span>
                                            </div>
                                        </a>
                                    <?php endif; ?>

                                    <?php foreach ($partners as $partner): 
                                        $initial = strtoupper(substr($partner['full_name'] ?? 'U', 0, 1));
                                        $isActive = ($partnerId == $partner['id']);
                                        $avatarColors = [
                                            'linear-gradient(135deg, #3B82F6, #1D4ED8)',
                                            'linear-gradient(135deg, #10B981, #059669)',
                                            'linear-gradient(135deg, #8B5CF6, #6D28D9)',
                                            'linear-gradient(135deg, #F59E0B, #D97706)'
                                        ];
                                        $grad = $avatarColors[$partner['id'] % count($avatarColors)];
                                    ?>
                                    <a href="?user=<?= $partner['id'] ?>" class="partner-item <?= $isActive ? 'active' : '' ?>">
                                        <div class="avatar-circle" style="background: <?= $grad ?>;">
                                            <?= $initial ?>
                                        </div>
                                        <div class="partner-info">
                                            <div class="partner-name-row">
                                                <span class="partner-name"><?= htmlspecialchars($partner['full_name']) ?></span>
                                            </div>
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="partner-role-pill"><?= ucfirst($partner['role'] ?? 'User') ?></span>
                                                <span class="partner-email-sub"><?= htmlspecialchars($partner['email']) ?></span>
                                            </div>
                                        </div>
                                    </a>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Right: Messages Area -->
                        <div class="messages-area">
                            <?php if (!$partnerData): ?>
                                <div class="message-placeholder">
                                    <div class="empty-icon-circle">
                                        <i data-lucide="message-square"></i>
                                    </div>
                                    <h4>Select a conversation</h4>
                                    <p>Choose a contact or reporter from the list on the left to review messages and send a reply.</p>
                                </div>
                            <?php else: ?>
                                <!-- Message Header -->
                                <div class="message-header">
                                    <div class="d-flex align-items-center gap-3">
                                        <?php 
                                        $initial = strtoupper(substr($partnerData['full_name'] ?? 'U', 0, 1));
                                        ?>
                                        <div class="avatar-circle header-avatar">
                                            <?= $initial ?>
                                        </div>
                                        <div>
                                            <div class="d-flex align-items-center gap-2">
                                                <h5 class="partner-title mb-0"><?= htmlspecialchars($partnerData['full_name']) ?></h5>
                                                <span class="partner-role-pill"><?= ucfirst($partnerData['role'] ?? 'User') ?></span>
                                            </div>
                                            <div class="partner-subtitle"><?= htmlspecialchars($partnerData['email']) ?></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Message Body -->
                                <div class="message-body" id="messageContainer">
                                    <?php if (empty($messages)): ?>
                                        <div class="empty-chat-state">
                                            <div class="empty-icon-circle" style="width:48px;height:48px;">
                                                <i data-lucide="messages-square" style="width:24px;height:24px;"></i>
                                            </div>
                                            <h5>No message history yet</h5>
                                            <p>Start the conversation by typing your response below.</p>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($messages as $message): 
                                            $isSender = ($message['sender_id'] == $adminId);
                                            $messageClass = $isSender ? 'message-sent' : 'message-received';
                                        ?>
                                        <div class="message-item <?= $messageClass ?>">
                                            <div class="message-content">
                                                <p><?= nl2br(htmlspecialchars($message['message'])) ?></p>
                                                <small class="message-time"><?= date('h:i A', strtotime($message['created_at'])) ?></small>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>

                                <!-- Message Footer Input Bar -->
                                <div class="message-footer">
                                    <form id="messageForm" method="POST" action="../api/messages.php" class="message-form-wrap">
                                        <input type="hidden" name="action" value="send_message">
                                        <input type="hidden" name="receiver_id" value="<?= $partnerData['id'] ?>">
                                        <div class="message-input-bar">
                                            <textarea class="modern-chat-input" name="message" placeholder="Type your reply message..." rows="1" required></textarea>
                                            <button type="submit" class="btn-send-msg" id="sendMessageBtn">
                                                <i data-lucide="send" style="width:16px;height:16px;"></i>
                                            </button>
                                        </div>
                                    </form>
                                    <div id="messageStatus" class="text-danger mt-2 small" style="display: none;"></div>
                                </div>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>

            </main>
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

.btn-top-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.82rem;
    font-weight: 600;
    color: #475569;
    background: #F8FAFC;
    border: 1px solid #E2E8F0;
    padding: 8px 16px;
    border-radius: 8px;
    text-decoration: none;
    transition: all .15s ease;
}
.btn-top-link:hover {
    background: #F1F5F9;
    color: #0F172A;
}

/* ── Chat Container ── */
.chat-card-wrapper {
    height: 700px;
}

.messaging-container {
    display: grid;
    grid-template-columns: 320px 1fr;
    height: 100%;
}

@media (max-width: 768px) {
    .messaging-container {
        grid-template-columns: 1fr;
        height: auto;
    }
}

/* Left: Partners List */
.partners-list {
    border-right: 1px solid #E2E8F0;
    background: #FFFFFF;
    display: flex;
    flex-direction: column;
    height: 100%;
}

.partners-header {
    padding: 18px 20px;
    border-bottom: 1px solid #F1F5F9;
}

.partners-title {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-weight: 700;
    font-size: 0.95rem;
    color: #0F172A;
    margin: 0;
}

.badge-count {
    background: #F1F5F9;
    color: #64748B;
    font-size: 0.72rem;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 100px;
}

.partners-body {
    flex: 1;
    overflow-y: auto;
}

.partner-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 18px;
    border-bottom: 1px solid #F8FAFC;
    text-decoration: none;
    color: inherit;
    transition: all .15s ease;
}

.partner-item:hover {
    background: #F8FAFC;
}

.partner-item.active {
    background: #EFF6FF;
    border-left: 3px solid #2563EB;
}

.avatar-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    color: #FFFFFF;
    font-weight: 700;
    font-size: 0.88rem;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.header-avatar {
    background: linear-gradient(135deg, #2563EB, #1D4ED8);
}

.partner-info {
    flex: 1;
    min-width: 0;
}

.partner-name-row {
    margin-bottom: 2px;
}

.partner-name {
    font-weight: 600;
    font-size: 0.88rem;
    color: #0F172A;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.partner-role-pill {
    display: inline-block;
    padding: 1px 7px;
    border-radius: 100px;
    font-size: 0.68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    background: #F1F5F9;
    color: #475569;
}

.partner-email-sub {
    font-size: 0.72rem;
    color: #94A3B8;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Right: Messages Area */
.messages-area {
    display: flex;
    flex-direction: column;
    height: 100%;
    background: #FAFAFA;
}

.message-placeholder {
    margin: auto;
    padding: 40px 20px;
    text-align: center;
    max-width: 400px;
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

/* Message Header */
.message-header {
    padding: 16px 24px;
    background: #FFFFFF;
    border-bottom: 1px solid #E2E8F0;
}

.partner-title {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-weight: 700;
    font-size: 1.05rem;
    color: #0F172A;
}

.partner-subtitle {
    font-size: 0.78rem;
    color: #64748B;
}

/* Message Body */
.message-body {
    flex: 1;
    overflow-y: auto;
    padding: 24px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.empty-chat-state {
    margin: auto;
    text-align: center;
    color: #94A3B8;
}
.empty-chat-state h5 {
    color: #334155;
    font-weight: 700;
    margin-top: 10px;
}

.message-item {
    display: flex;
    max-width: 75%;
}

.message-sent {
    align-self: flex-end;
}

.message-received {
    align-self: flex-start;
}

.message-content {
    padding: 12px 18px;
    border-radius: 16px;
    word-break: break-word;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
}

.message-sent .message-content {
    background: #2563EB;
    color: #FFFFFF;
    border-bottom-right-radius: 4px;
}

.message-received .message-content {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    color: #0F172A;
    border-bottom-left-radius: 4px;
}

.message-content p {
    margin: 0;
    font-size: 0.9rem;
    line-height: 1.5;
}

.message-time {
    font-size: 0.68rem;
    margin-top: 4px;
    display: block;
    opacity: 0.75;
}
.message-sent .message-time {
    text-align: right;
    color: rgba(255, 255, 255, 0.85);
}
.message-received .message-time {
    color: #94A3B8;
}

/* Footer / Input Bar */
.message-footer {
    padding: 16px 24px;
    background: #FFFFFF;
    border-top: 1px solid #E2E8F0;
}

.message-input-bar {
    display: flex;
    align-items: center;
    gap: 10px;
    background: #F8FAFC;
    border: 1px solid #E2E8F0;
    border-radius: 14px;
    padding: 6px 8px 6px 16px;
    transition: all .2s ease;
}

.message-input-bar:focus-within {
    border-color: #2563EB;
    background: #FFFFFF;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.modern-chat-input {
    flex: 1;
    border: none;
    background: transparent;
    font-size: 0.88rem;
    color: #0F172A;
    resize: none;
    outline: none;
    max-height: 100px;
    padding: 6px 0;
}

.btn-send-msg {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    background: #2563EB;
    color: #FFFFFF;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all .15s ease;
    flex-shrink: 0;
}
.btn-send-msg:hover {
    background: #1D4ED8;
    transform: scale(1.04);
}
</style>

<script src="../js/admin-messages.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});
</script>

<?php include '../inc/footer.php'; ?>