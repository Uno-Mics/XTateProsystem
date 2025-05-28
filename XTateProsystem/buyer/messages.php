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

include '../inc/header.php';
?>

<div class="container py-5">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-3 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Buyer Dashboard</h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="dashboard.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                    <a href="favorites.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-heart me-2"></i> My Favorites
                    </a>
                    <a href="messages.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-comments me-2"></i> Messages
                        <?php 
                        $unreadCount = getUnreadMessagesCount($buyerId);
                        if ($unreadCount > 0): 
                        ?>
                            <span class="badge bg-danger rounded-pill ms-1"><?= $unreadCount ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="../search.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-search me-2"></i> Find Properties
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
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Messages</h5>
                </div>
                
                <div class="card-body p-0">
                    <div class="messaging-container">
                        <!-- Partners List -->
                        <div class="partners-list">
                            <div class="partners-header">
                                <h6 class="mb-0">Conversations</h6>
                            </div>
                            
                            <div class="partners-body">
                                <?php if (empty($partners)): ?>
                                    <div class="text-center py-5">
                                        <div class="mb-3">
                                            <i class="fas fa-comments text-muted fa-3x"></i>
                                        </div>
                                        <p class="text-muted mb-0">No conversations yet</p>
                                        <small class="text-muted">Start messaging sellers to see them here</small>
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
                                        $unreadCount = $result ? $result['count'] : 0;
                                        
                                        $isActive = $partnerId == $partner['partner_id'];
                                    ?>
                                    <a href="messages.php?user=<?= $partner['partner_id'] ?>" class="partner-item <?= $isActive ? 'active' : '' ?>">
                                        <div class="partner-avatar">
                                            <?php 
                                            $initial = strtoupper(substr($partnerInfo['full_name'], 0, 1));
                                            $bgColor = generateAvatarColor($partnerInfo['id']);
                                            ?>
                                            <div class="avatar-circle" style="background-color: <?= $bgColor ?>;">
                                                <?= $initial ?>
                                            </div>
                                        </div>
                                        <div class="partner-info">
                                            <div class="partner-name">
                                                <?= $partnerInfo['full_name'] ?>
                                                <small class="partner-role"><?= ucfirst($partnerInfo['role']) ?></small>
                                            </div>
                                            <?php if ($unreadCount > 0): ?>
                                                <span class="unread-badge"><?= $unreadCount ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </a>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Messages Area -->
                        <div class="messages-area">
                            <?php if (!$partnerData): ?>
                                <div class="message-placeholder">
                                    <div class="mb-3">
                                        <i class="fas fa-comments text-primary fa-4x"></i>
                                    </div>
                                    <h5>Select a conversation</h5>
                                    <p class="text-muted">Choose a conversation from the list to start messaging</p>
                                </div>
                            <?php else: ?>
                                <!-- Message Header -->
                                <div class="message-header">
                                    <div class="d-flex align-items-center">
                                        <?php 
                                        $initial = strtoupper(substr($partnerData['full_name'], 0, 1));
                                        $bgColor = generateAvatarColor($partnerData['id']);
                                        ?>
                                        <div class="avatar-circle me-2" style="background-color: <?= $bgColor ?>; width: 40px; height: 40px; font-size: 16px;">
                                            <?= $initial ?>
                                        </div>
                                        <div>
                                            <h6 class="mb-0"><?= $partnerData['full_name'] ?></h6>
                                            <small class="text-muted"><?= ucfirst($partnerData['role']) ?></small>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Message Body -->
                                <div class="message-body" id="messageContainer">
                                    <?php if (empty($messages)): ?>
                                        <div class="text-center py-5">
                                            <div class="mb-3">
                                                <i class="fas fa-comments text-muted fa-3x"></i>
                                            </div>
                                            <p class="text-muted mb-0">No messages yet</p>
                                            <small class="text-muted">Start the conversation by sending a message</small>
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
                                
                                <!-- Message Footer -->
                                <div class="message-footer">
                                    <form id="messageForm" method="POST" action="../api/messages.php">
                                        <input type="hidden" name="action" value="send_message">
                                        <input type="hidden" name="receiver_id" value="<?= $partnerData['id'] ?>">
                                        
                                        <div class="input-group">
                                            <textarea class="form-control" name="message" placeholder="Type your message..." rows="1" required></textarea>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-paper-plane"></i>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="../js/messaging.js"></script>

<style>
.messaging-container {
    display: flex;
    height: 600px;
    border: 1px solid #e0e0e0;
}

.partners-list {
    width: 300px;
    border-right: 1px solid #e0e0e0;
    overflow-y: auto;
}

.partners-header {
    padding: 15px;
    border-bottom: 1px solid #e0e0e0;
    background-color: #f8f9fa;
}

.partners-body {
    overflow-y: auto;
}

.partner-item {
    display: flex;
    align-items: center;
    padding: 12px 15px;
    border-bottom: 1px solid #e0e0e0;
    text-decoration: none;
    color: #333;
    transition: background-color 0.2s;
}

.partner-item:hover, .partner-item.active {
    background-color: #f0f7ff;
    text-decoration: none;
}

.partner-avatar {
    margin-right: 12px;
}

.avatar-circle {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    background-color: #007bff;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 18px;
}

.partner-info {
    flex-grow: 1;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.partner-name {
    display: flex;
    flex-direction: column;
}

.partner-role {
    color: #6c757d;
    font-size: 12px;
}

.unread-badge {
    background-color: #ff4757;
    color: white;
    border-radius: 50%;
    min-width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: bold;
}

.messages-area {
    flex-grow: 1;
    display: flex;
    flex-direction: column;
}

.message-header {
    padding: 12px 15px;
    border-bottom: 1px solid #e0e0e0;
    background-color: #f8f9fa;
    margin: 0px 0px 0px;
}

.message-body {
    flex-grow: 1;
    padding: 15px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
}

.message-item {
    margin-bottom: 15px;
    max-width: 75%;
}

.message-sent {
    align-self: flex-end;
}

.message-received {
    align-self: flex-start;
}

.message-content {
    padding: 10px 15px;
    border-radius: 18px;
    position: relative;
}

.message-sent .message-content {
    background-color: #007bff;
    color: white;
    border-bottom-right-radius: 4px;
}

.message-received .message-content {
    background-color: #f0f2f5;
    color: #333;
    border-bottom-left-radius: 4px;
}

.message-content p {
    margin-bottom: 5px;
}

.message-time {
    font-size: 11px;
    opacity: 0.8;
    display: block;
    text-align: right;
}

.message-footer {
    padding: 12px 15px;
    border-top: 1px solid #e0e0e0;
}

.message-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    padding: 20px;
    text-align: center;
    color: #6c757d;
}
</style>

<script src="../js/websocket.js"></script>
<script src="../js/messaging.js"></script>

<?php include '../inc/footer.php'; ?>