<?php
require_once '../inc/db.php';
require_once '../inc/functions.php';
require_once '../inc/auth.php';

// Check if user is logged in and has seller role
checkPermission(['seller']);

// Get seller data
$sellerId = $_SESSION['user_id'];
$sellerName = $_SESSION['user_name'] ?? 'Seller';
$sql = "SELECT * FROM users WHERE id = ?";
$seller = fetchOne($sql, "i", [$sellerId]);

// Get conversation partner
$partnerId = isset($_GET['user']) ? intval($_GET['user']) : 0;
$partnerData = null;

if ($partnerId > 0) {
    $sql = "SELECT id, full_name, email, role FROM users WHERE id = ?";
    $partnerData = fetchOne($sql, "i", [$partnerId]);
}

// Get conversation partners
$partners = getConversationPartners($sellerId);

// Load messages for selected partner
$messages = [];
if ($partnerId > 0) {
    $sql = "SELECT m.*, 
            s.full_name as sender_name, 
            r.full_name as receiver_name 
            FROM messages m
            JOIN users s ON m.sender_id = s.id
            JOIN users r ON m.receiver_id = r.id
            WHERE (m.sender_id = ? AND m.receiver_id = ?) 
               OR (m.sender_id = ? AND m.receiver_id = ?)
            ORDER BY m.created_at ASC";
    
    $messages = fetchAll($sql, "iiii", [$sellerId, $partnerId, $partnerId, $sellerId]);
    
    // Mark messages as read
    if (!empty($messages)) {
        markMessagesAsRead($partnerId);
    }
}

// Badges count
$pendingCount = getPendingInquiriesCount($sellerId);
$unreadCount = getUnreadMessagesCount($sellerId);

include '../inc/header.php';
?>

<div class="db-wrap">
    <div class="db-container">
        <div class="db-layout">

            <!-- Sidebar -->
            <aside class="db-sidebar">
                <!-- Profile Card -->
                <div class="db-card db-profile-card">
                    <div class="db-avatar"><?= strtoupper(substr($sellerName, 0, 1)) ?></div>
                    <div class="db-user-name"><?= htmlspecialchars($sellerName) ?></div>
                    <span class="db-user-badge">
                        <i data-lucide="badge-check" style="width:13px;height:13px;"></i> Verified Seller
                    </span>
                </div>

                <!-- Navigation -->
                <div class="db-card db-nav-group">
                    <a href="dashboard.php" class="db-nav-link">
                        <i data-lucide="layout-dashboard"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="properties.php" class="db-nav-link">
                        <i data-lucide="home"></i>
                        <span>My Properties</span>
                    </a>
                    <a href="add_property.php" class="db-nav-link">
                        <i data-lucide="plus-circle"></i>
                        <span>Add Property</span>
                    </a>
                    <a href="inquiries.php" class="db-nav-link">
                        <i data-lucide="inbox"></i>
                        <span>Inquiries</span>
                        <?php if ($pendingCount > 0): ?>
                            <span class="db-nav-badge"><?= $pendingCount ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="messages.php" class="db-nav-link active">
                        <i data-lucide="message-square"></i>
                        <span>Messages</span>
                        <?php if ($unreadCount > 0): ?>
                            <span class="db-nav-badge db-nav-badge--danger"><?= $unreadCount ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="profile.php" class="db-nav-link">
                        <i data-lucide="user-cog"></i>
                        <span>Edit Profile</span>
                    </a>
                </div>
            </aside>

            <!-- Main Workspace -->
            <main class="db-main">

                <!-- Header Banner -->
                <div class="db-hero-banner">
                    <div>
                        <h1 class="db-hero-title">Direct Messages</h1>
                        <p class="db-hero-desc">Real-time chat with interested buyers and property clients</p>
                    </div>
                    <?php if ($unreadCount > 0): ?>
                        <span class="unread-hero-pill">
                            <i data-lucide="bell" style="width:14px;height:14px;"></i>
                            <span><?= $unreadCount ?> Unread <?= $unreadCount === 1 ? 'Message' : 'Messages' ?></span>
                        </span>
                    <?php endif; ?>
                </div>

                <!-- Chat Bento Container -->
                <div class="db-card chat-card-wrapper">
                    <div class="messaging-container">

                        <!-- Left Column: Partners List -->
                        <div class="partners-list">
                            <div class="partners-header">
                                <div class="d-flex align-items-center justify-content-between">
                                    <h2 class="partners-title">Conversations</h2>
                                    <span class="partners-count-badge"><?= count($partners) ?></span>
                                </div>
                            </div>

                            <div class="partners-body">
                                <?php if (empty($partners)): ?>
                                    <div class="partners-empty">
                                        <div class="empty-icon-sm">
                                            <i data-lucide="messages-square"></i>
                                        </div>
                                        <div class="empty-title">No chats yet</div>
                                        <div class="empty-desc">When buyers inquire about your properties, conversations will appear here.</div>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($partners as $partner): 
                                        $sql = "SELECT * FROM users WHERE id = ?";
                                        $partnerInfo = fetchOne($sql, "i", [$partner['partner_id']]);
                                        
                                        if (!$partnerInfo) continue;
                                        
                                        // Get unread count
                                        $sql = "SELECT COUNT(*) as count FROM messages 
                                                WHERE sender_id = ? AND receiver_id = ? AND is_read = 0";
                                        $result = fetchOne($sql, "ii", [$partner['partner_id'], $sellerId]);
                                        $partnerUnreadCount = $result ? $result['count'] : 0;
                                        
                                        $isActive = $partnerId == $partner['partner_id'];
                                        $initial = strtoupper(substr($partnerInfo['full_name'], 0, 1));
                                    ?>
                                        <a href="messages.php?user=<?= $partner['partner_id'] ?>" class="partner-item <?= $isActive ? 'active' : '' ?>">
                                            <div class="partner-avatar">
                                                <div class="avatar-circle">
                                                    <?= $initial ?>
                                                </div>
                                            </div>
                                            <div class="partner-info">
                                                <div class="partner-name-row">
                                                    <span class="partner-name"><?= htmlspecialchars($partnerInfo['full_name']) ?></span>
                                                    <span class="partner-role-pill"><?= ucfirst(htmlspecialchars($partnerInfo['role'])) ?></span>
                                                </div>
                                                <div class="partner-sub-row">
                                                    <span class="partner-email-snippet"><?= htmlspecialchars($partnerInfo['email']) ?></span>
                                                    <?php if ($partnerUnreadCount > 0): ?>
                                                        <span class="unread-badge"><?= $partnerUnreadCount ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Right Column: Messages Area -->
                        <div class="messages-area">
                            <?php if (!$partnerData): ?>
                                <div class="message-placeholder">
                                    <div class="placeholder-icon-circle">
                                        <i data-lucide="message-square-dashed"></i>
                                    </div>
                                    <h3>Select a conversation</h3>
                                    <p>Choose a contact from the left list to view your chat history and reply.</p>
                                </div>
                            <?php else: ?>
                                <!-- Message Header -->
                                <div class="message-header">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="avatar-circle avatar-circle--active">
                                            <?= strtoupper(substr($partnerData['full_name'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <h3 class="active-chat-name"><?= htmlspecialchars($partnerData['full_name']) ?></h3>
                                            <div class="active-chat-meta">
                                                <span class="online-indicator"></span>
                                                <span><?= ucfirst(htmlspecialchars($partnerData['role'])) ?></span>
                                                <span class="meta-dot">&bull;</span>
                                                <span><?= htmlspecialchars($partnerData['email']) ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Message Body Stream -->
                                <div class="message-body" id="messageContainer">
                                    <?php if (empty($messages)): ?>
                                        <div class="message-stream-empty">
                                            <div class="empty-icon-sm">
                                                <i data-lucide="message-circle"></i>
                                            </div>
                                            <div class="empty-title">No messages yet</div>
                                            <div class="empty-desc">Send a greeting below to begin your conversation.</div>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($messages as $message): 
                                            $isSender = $message['sender_id'] == $sellerId;
                                            $messageClass = $isSender ? 'message-sent' : 'message-received';
                                        ?>
                                            <div class="message-item <?= $messageClass ?>" data-message-id="<?= $message['id'] ?>">
                                                <div class="message-content">
                                                    <p><?= nl2br(htmlspecialchars($message['message'])) ?></p>
                                                    <small class="message-time"><?= formatMessageTime($message['created_at']) ?></small>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>

                                <!-- Message Footer Composer -->
                                <div class="message-footer">
                                    <form id="messageForm" method="POST" action="../api/messages.php">
                                        <input type="hidden" name="action" value="send_message">
                                        <input type="hidden" name="receiver_id" value="<?= $partnerData['id'] ?>">

                                        <div class="composer-bar">
                                            <textarea class="composer-input" name="message" placeholder="Type a message..." rows="1" required></textarea>
                                            <button type="submit" class="composer-send-btn" title="Send Message">
                                                <i data-lucide="send"></i>
                                            </button>
                                        </div>
                                    </form>
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
/* ── Design Tokens & Base Layout ── */
.db-wrap {
    background: #F8FAFC;
    min-height: 100vh;
    padding: 30px 0 70px;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    color: #0F172A;
    overflow-x: hidden;
}

.db-container {
    max-width: 1240px;
    margin: 0 auto;
    padding: 0 16px;
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
    background: linear-gradient(135deg, #2563EB, #7C3AED);
    color: #fff;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 1.4rem;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 14px rgba(37, 99, 235, 0.25);
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
    color: #059669;
    background: #ECFDF5;
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

.db-nav-link i, .db-nav-link svg {
    width: 18px;
    height: 18px;
    flex-shrink: 0;
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
    background: #EFF6FF;
    color: #2563EB;
    padding: 2px 8px;
    border-radius: 100px;
}
.db-nav-badge--danger {
    background: #FEE2E2;
    color: #DC2626;
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

.unread-hero-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.82rem;
    font-weight: 700;
    padding: 6px 14px;
    border-radius: 100px;
    background: #FEE2E2;
    color: #DC2626;
}

/* ── Chat Container ── */
.chat-card-wrapper {
    height: 640px;
    display: flex;
    flex-direction: column;
}

.messaging-container {
    display: flex;
    height: 100%;
    width: 100%;
}

@media (max-width: 768px) {
    .messaging-container {
        flex-direction: column;
    }
    .partners-list {
        width: 100% !important;
        height: 200px !important;
        border-right: none !important;
        border-bottom: 1px solid #E2E8F0;
    }
}

/* Left Column: Partners List */
.partners-list {
    width: 320px;
    flex-shrink: 0;
    border-right: 1px solid #F1F5F9;
    display: flex;
    flex-direction: column;
    background: #FAFAFA;
}

.partners-header {
    padding: 18px 20px;
    background: #FFFFFF;
    border-bottom: 1px solid #F1F5F9;
}

.partners-title {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 1rem;
    font-weight: 700;
    color: #0F172A;
    margin: 0;
}

.partners-count-badge {
    font-size: 0.72rem;
    font-weight: 700;
    background: #EFF6FF;
    color: #2563EB;
    padding: 2px 8px;
    border-radius: 100px;
}

.partners-body {
    flex: 1;
    overflow-y: auto;
}

.partners-empty {
    padding: 40px 20px;
    text-align: center;
}

.empty-icon-sm {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: #EFF6FF;
    color: #2563EB;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 10px;
}
.empty-icon-sm svg {
    width: 22px;
    height: 22px;
}

.empty-title {
    font-weight: 700;
    font-size: 0.9rem;
    color: #0F172A;
    margin-bottom: 4px;
}

.empty-desc {
    font-size: 0.78rem;
    color: #64748B;
    line-height: 1.4;
}

/* Partner Item */
.partner-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 18px;
    border-bottom: 1px solid #F1F5F9;
    text-decoration: none;
    color: inherit;
    transition: all .15s ease;
    border-left: 3px solid transparent;
    background: #FFFFFF;
}

.partner-item:hover {
    background: #F8FAFC;
    color: inherit;
}

.partner-item.active {
    background: #EFF6FF;
    border-left-color: #2563EB;
}

.avatar-circle {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background: linear-gradient(135deg, #3B82F6, #6366F1);
    color: #FFFFFF;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-weight: 700;
    font-size: 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.avatar-circle--active {
    background: linear-gradient(135deg, #2563EB, #7C3AED);
    box-shadow: 0 2px 8px rgba(37, 99, 235, 0.25);
}

.partner-info {
    flex: 1;
    min-width: 0;
}

.partner-name-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 6px;
    margin-bottom: 3px;
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
    font-size: 0.68rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    padding: 1px 6px;
    border-radius: 4px;
    background: #F1F5F9;
    color: #64748B;
    flex-shrink: 0;
}

.partner-sub-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 6px;
}

.partner-email-snippet {
    font-size: 0.76rem;
    color: #64748B;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.unread-badge {
    background: #EF4444;
    color: #FFFFFF;
    font-size: 0.68rem;
    font-weight: 800;
    min-width: 18px;
    height: 18px;
    border-radius: 100px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0 5px;
    flex-shrink: 0;
}

/* Right Column: Messages Area */
.messages-area {
    flex: 1;
    display: flex;
    flex-direction: column;
    min-width: 0;
    background: #FFFFFF;
}

/* Placeholder state */
.message-placeholder {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
    text-align: center;
    background: #FAFAFA;
}

.placeholder-icon-circle {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: #EFF6FF;
    color: #2563EB;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 16px;
}
.placeholder-icon-circle svg {
    width: 32px;
    height: 32px;
}

.message-placeholder h3 {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 1.2rem;
    font-weight: 700;
    color: #0F172A;
    margin-bottom: 6px;
}

.message-placeholder p {
    font-size: 0.85rem;
    color: #64748B;
    max-width: 340px;
    margin: 0;
}

/* Chat Header */
.message-header {
    padding: 16px 24px;
    border-bottom: 1px solid #F1F5F9;
    background: #FFFFFF;
}

.active-chat-name {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 1.05rem;
    font-weight: 700;
    color: #0F172A;
    margin: 0 0 2px;
}

.active-chat-meta {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.78rem;
    color: #64748B;
}

.online-indicator {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #10B981;
}

.meta-dot {
    color: #CBD5E1;
}

/* Message Stream */
.message-body {
    flex: 1;
    padding: 20px 24px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 12px;
    background: #FAFAFA;
}

.message-stream-empty {
    margin: auto;
    text-align: center;
}

.message-item {
    display: flex;
    flex-direction: column;
    max-width: 70%;
}

.message-sent {
    align-self: flex-end;
}

.message-received {
    align-self: flex-start;
}

.message-content {
    padding: 12px 16px;
    border-radius: 16px;
    position: relative;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
}

.message-sent .message-content {
    background: #2563EB;
    color: #FFFFFF;
    border-bottom-right-radius: 4px;
}

.message-received .message-content {
    background: #FFFFFF;
    color: #0F172A;
    border: 1px solid #E2E8F0;
    border-bottom-left-radius: 4px;
}

.message-content p {
    margin: 0 0 4px;
    font-size: 0.88rem;
    line-height: 1.5;
    word-break: break-word;
}

.message-time {
    font-size: 0.68rem;
    display: block;
    text-align: right;
    opacity: 0.75;
}

/* Composer Bar */
.message-footer {
    padding: 16px 20px;
    border-top: 1px solid #F1F5F9;
    background: #FFFFFF;
}

.composer-bar {
    display: flex;
    align-items: center;
    gap: 10px;
    background: #F8FAFC;
    border: 1px solid #CBD5E1;
    border-radius: 14px;
    padding: 6px 8px 6px 16px;
    transition: all .15s ease;
}

.composer-bar:focus-within {
    border-color: #2563EB;
    background: #FFFFFF;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.composer-input {
    flex: 1;
    border: none;
    background: transparent;
    outline: none;
    font-size: 0.88rem;
    color: #0F172A;
    resize: none;
    max-height: 120px;
    font-family: inherit;
    line-height: 1.4;
    padding: 6px 0;
}

.composer-send-btn {
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
    flex-shrink: 0;
    transition: all .15s ease;
}

.composer-send-btn:hover {
    background: #1D4ED8;
    transform: scale(1.04);
}

.composer-send-btn svg {
    width: 17px;
    height: 17px;
}
</style>

<script src="../js/websocket.js"></script>
<script src="../js/messaging.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});
</script>

<?php include '../inc/footer.php'; ?>