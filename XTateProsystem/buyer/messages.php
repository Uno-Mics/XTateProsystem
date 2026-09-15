<?php
require_once '../inc/db.php';
require_once '../inc/functions.php';
require_once '../inc/auth.php';

// Check if user is logged in and has buyer role
checkPermission(['buyer']);

// Get buyer data
$buyerId = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = ?";
$buyer = fetchOne($sql, "i", [$buyerId]);

// Get conversation partner
$partnerId = isset($_GET['user']) ? intval($_GET['user']) : 0;
$partnerData = null;

if ($partnerId > 0) {
    $sql = "SELECT id, full_name, email, role FROM users WHERE id = ?";
    $partnerData = fetchOne($sql, "i", [$partnerId]);
}

// Get conversation partners
$partners = getConversationPartners($buyerId);

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
    
    $messages = fetchAll($sql, "iiii", [$buyerId, $partnerId, $partnerId, $buyerId]);
    
    // Mark messages as read
    if (!empty($messages)) {
        markMessagesAsRead($partnerId);
    }
}

$unviewedFavoritesCount = getUnviewedFavoritesCount($buyerId);
$unreadCount = getUnreadMessagesCount($buyerId);

$pageTitle = "Direct Messages";
$metaDescription = "Real-time direct messaging with verified property sellers and listing agents on XTate.";

include '../inc/header.php';
?>

<style>
/* ── Messages-Page–Specific Styles ──
   Shared tokens (db-wrap, db-layout, db-sidebar, db-card, etc.)
   are served by css/buyer-module.css loaded via inc/header.php */
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

/* â”€â”€ Chat Container â”€â”€ */
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

.btn-explore-link {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 0.8rem;
    font-weight: 600;
    color: #2563EB;
    text-decoration: none;
}
.btn-explore-link:hover {
    text-decoration: underline;
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
    background: linear-gradient(135deg, #0284C7, #2563EB);
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


<div class="db-wrap">
    <div class="db-container">
        <div class="db-layout">

            <!-- â•â•â•â•â•â•â•â•â•â• LEFT: BUYER SIDEBAR â•â•â•â•â•â•â•â•â•â• -->
            <aside class="db-sidebar">
                <div class="db-card db-profile-card">
                    <div class="db-avatar">
                        <?= strtoupper(substr($buyer['full_name'] ?? 'B', 0, 1)) ?>
                    </div>
                    <h3 class="db-user-name"><?= htmlspecialchars($buyer['full_name']) ?></h3>
                    <span class="db-user-badge">
                        <i data-lucide="shield-check" style="width:13px;height:13px;"></i> Verified Buyer
                    </span>
                </div>

                <div class="db-card">
                    <nav class="db-nav-group">
                        <a href="dashboard.php" class="db-nav-link">
                            <i data-lucide="layout-dashboard" style="width:17px;height:17px;"></i>
                            <span>Dashboard</span>
                        </a>
                        <a href="favorites.php" class="db-nav-link">
                            <i data-lucide="heart" style="width:17px;height:17px;"></i>
                            <span>My Favorites</span>
                            <?php if ($unviewedFavoritesCount > 0): ?>
                                <span class="db-nav-badge"><?= $unviewedFavoritesCount ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="messages.php" class="db-nav-link active">
                            <i data-lucide="message-square" style="width:17px;height:17px;"></i>
                            <span>Messages</span>
                            <?php if ($unreadCount > 0): ?>
                                <span class="db-nav-badge db-nav-badge--danger"><?= $unreadCount ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="../search.php" class="db-nav-link">
                            <i data-lucide="search" style="width:17px;height:17px;"></i>
                            <span>Find Properties</span>
                        </a>
                        <a href="profile.php" class="db-nav-link">
                            <i data-lucide="user-cog" style="width:17px;height:17px;"></i>
                            <span>Edit Profile</span>
                        </a>
                    </nav>
                </div>
            </aside>

            <!-- â•â•â•â•â•â•â•â•â•â• RIGHT: MAIN WORKSPACE â•â•â•â•â•â•â•â•â•â• -->
            <main class="db-main">

                <!-- Header Banner -->
                <div class="db-hero-banner">
                    <div>
                        <h1 class="db-hero-title">Direct Messages</h1>
                        <p class="db-hero-sub">Real-time conversations with verified property sellers and listing agents</p>
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
                                        <div class="empty-title">No conversations yet</div>
                                        <div class="empty-desc">Reach out to sellers from property details pages to start a conversation.</div>
                                        <a href="../search.php" class="btn-explore-link mt-2">
                                            Find Properties <i data-lucide="arrow-right" style="width:13px;height:13px;"></i>
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($partners as $partner): 
                                        $sql = "SELECT * FROM users WHERE id = ?";
                                        $partnerInfo = fetchOne($sql, "i", [$partner['partner_id']]);
                                        
                                        if (!$partnerInfo) continue;
                                        
                                        // Get unread count
                                        $sql = "SELECT COUNT(*) as count FROM messages 
                                                WHERE sender_id = ? AND receiver_id = ? AND is_read = 0";
                                        $result = fetchOne($sql, "ii", [$partner['partner_id'], $buyerId]);
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
                                    <p>Choose a seller from the left list to view your chat history and reply in real-time.</p>
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
                                            <div class="empty-desc">Send a message below to start your conversation with <?= htmlspecialchars($partnerData['full_name']) ?>.</div>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($messages as $message): 
                                            $isSender = $message['sender_id'] == $buyerId;
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


<script src="../js/messaging.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});
</script>

<?php include '../inc/footer.php'; ?>
