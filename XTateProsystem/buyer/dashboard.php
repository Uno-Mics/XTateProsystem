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

// Get favorite properties count
$sql = "SELECT COUNT(*) as count FROM favorites WHERE buyer_id = ?";
$favoritesResult = fetchOne($sql, "i", [$buyerId]);
$favoritesCount = $favoritesResult ? $favoritesResult['count'] : 0;
$unviewedFavoritesCount = getUnviewedFavoritesCount($buyerId);

// Get inquiries count
$sql = "SELECT COUNT(*) as count FROM inquiries WHERE buyer_id = ?";
$inquiriesResult = fetchOne($sql, "i", [$buyerId]);
$inquiriesCount = $inquiriesResult ? $inquiriesResult['count'] : 0;
$unreadCount = getUnreadMessagesCount($buyerId);

// Get recent properties
$sql = "SELECT p.*, pt.name as property_type, 
        (SELECT image_path FROM property_images WHERE property_id = p.id AND is_primary = 1 LIMIT 1) as primary_image
        FROM properties p 
        JOIN property_types pt ON p.property_type_id = pt.id 
        WHERE p.status = 'active' 
        ORDER BY p.created_at DESC LIMIT 6";
$recentProperties = fetchAll($sql);

// Get recent inquiries
$sql = "SELECT i.*, p.title as property_title, p.price, p.seller_id,
        (SELECT image_path FROM property_images WHERE property_id = p.id AND is_primary = 1 LIMIT 1) as property_image,
        u.full_name as seller_name, u.email as seller_email
        FROM inquiries i
        JOIN properties p ON i.property_id = p.id
        JOIN users u ON p.seller_id = u.id
        WHERE i.buyer_id = ?
        ORDER BY i.created_at DESC LIMIT 5";
$recentInquiries = fetchAll($sql, "i", [$buyerId]);

$pageTitle = "Buyer Dashboard";
$metaDescription = "Overview of your saved properties, inquiries, and messages on XTate.";

include '../inc/header.php';
?>

<style>
/* ============================================================
   BUYER DASHBOARD — Modern Minimalist + Bento Grid
   ============================================================ */

.db-wrap {
    background: #F8FAFC;
    min-height: 100vh;
    padding: 30px 0 70px;
    width: 100%;
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
    background: linear-gradient(135deg, #2563EB, #06B6D4);
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

.db-hero-sub {
    font-size: 0.88rem;
    color: #64748B;
    margin: 0;
}

.db-btn {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 9px 16px;
    border-radius: 9px;
    font-size: 0.85rem;
    font-weight: 600;
    text-decoration: none;
    transition: all .2s;
    cursor: pointer;
    border: none;
}

.db-btn-primary {
    background: #2563EB;
    color: #FFFFFF;
}

.db-btn-primary:hover {
    background: #1D4ED8;
    color: #FFFFFF;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
    transform: translateY(-1px);
}

/* ── Bento Metrics ── */
.db-metrics-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
}

.db-metric-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.05);
    transition: transform .2s, box-shadow .2s;
}

.db-metric-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(15, 23, 42, 0.08);
}

.db-metric-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.db-metric-rose   { background: #FFE4E6; color: #E11D48; }
.db-metric-blue   { background: #EFF6FF; color: #2563EB; }
.db-metric-purple { background: #FAF5FF; color: #7C3AED; }
.db-metric-emerald{ background: #ECFDF5; color: #059669; }

.db-metric-info {
    min-width: 0;
}

.db-metric-label {
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #64748B;
    margin-bottom: 3px;
}

.db-metric-value {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 1.55rem;
    font-weight: 800;
    color: #0F172A;
    line-height: 1.15;
}

/* ── Panels ── */
.db-panel {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.05);
}

.db-panel-head {
    padding: 18px 22px;
    border-bottom: 1px solid #F1F5F9;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.db-panel-title {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 1rem;
    font-weight: 700;
    color: #0F172A;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.db-panel-body {
    padding: 22px;
}

/* ── Modern Table ── */
.db-table-wrap {
    overflow-x: auto;
}

.db-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.88rem;
}

.db-table th {
    text-align: left;
    padding: 12px 16px;
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #64748B;
    background: #F8FAFC;
    border-bottom: 1px solid #E2E8F0;
}

.db-table td {
    padding: 14px 16px;
    border-bottom: 1px solid #F1F5F9;
    color: #334155;
    vertical-align: middle;
}

.db-table tr:hover td {
    background: #F8FAFC;
}

/* Property cell */
.db-prop-cell {
    display: flex;
    align-items: center;
    gap: 12px;
}

.db-prop-img {
    width: 50px;
    height: 50px;
    border-radius: 8px;
    object-fit: cover;
    flex-shrink: 0;
    background: #E2E8F0;
}

.db-prop-title {
    font-weight: 600;
    color: #0F172A;
    margin: 0 0 2px;
    max-width: 240px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.db-prop-seller {
    font-size: 0.76rem;
    color: #64748B;
    margin: 0;
}

.db-price-pill {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-weight: 700;
    color: #2563EB;
}

/* Status Chips */
.db-status-chip {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 10px;
    border-radius: 100px;
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.db-status-pending  { background: #FEF3C7; color: #D97706; }
.db-status-approved { background: #ECFDF5; color: #059669; }
.db-status-closed   { background: #F1F5F9; color: #64748B; }

/* Table Action Buttons */
.db-action-btn {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #E2E8F0;
    background: #FFFFFF;
    color: #64748B;
    text-decoration: none;
    transition: all .15s;
    cursor: pointer;
    font-size: 0;
}

.db-action-btn:hover {
    color: #2563EB;
    border-color: #BFDBFE;
    background: #EFF6FF;
}

.db-action-btn--delete:hover {
    color: #DC2626;
    border-color: #FECACA;
    background: #FEF2F2;
}

/* ── Property Card Mini-Grid ── */
.db-props-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
}

.db-pcard {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 14px;
    overflow: hidden;
    transition: transform .2s, box-shadow .2s;
    display: flex;
    flex-direction: column;
}

.db-pcard:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
}

.db-pcard-img-wrap {
    height: 160px;
    width: 100%;
    position: relative;
    overflow: hidden;
    background: #E2E8F0;
}

.db-pcard-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.db-pcard-price {
    position: absolute;
    bottom: 10px;
    left: 10px;
    background: rgba(15, 23, 42, 0.85);
    backdrop-filter: blur(6px);
    color: #FFFFFF;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-weight: 700;
    font-size: 0.88rem;
    padding: 3px 10px;
    border-radius: 8px;
}

.db-pcard-body {
    padding: 14px;
    display: flex;
    flex-direction: column;
    flex: 1;
}

.db-pcard-title {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 0.95rem;
    font-weight: 700;
    margin: 0 0 4px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.db-pcard-title a {
    color: #0F172A;
    text-decoration: none;
}
.db-pcard-title a:hover { color: #2563EB; }

.db-pcard-loc {
    font-size: 0.78rem;
    color: #64748B;
    display: flex;
    align-items: center;
    gap: 4px;
    margin-bottom: 12px;
}

.db-pcard-specs {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-top: auto;
    padding-top: 10px;
    border-top: 1px solid #F1F5F9;
    font-size: 0.75rem;
    color: #64748B;
}

.db-pcard-spec-item {
    display: flex;
    align-items: center;
    gap: 4px;
}

/* ── Responsive ── */
@media (max-width: 991.98px) {
    .db-layout {
        grid-template-columns: 1fr;
    }
    .db-metrics-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .db-props-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 575.98px) {
    .db-wrap { padding: 18px 0 40px; }
    .db-metrics-grid {
        grid-template-columns: 1fr;
    }
    .db-props-grid {
        grid-template-columns: 1fr;
    }
    .db-hero-banner {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>

<div class="db-wrap">
    <div class="db-container">
        <div class="db-layout">

            <!-- ══════════ LEFT: BUYER SIDEBAR ══════════ -->
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
                        <a href="dashboard.php" class="db-nav-link active">
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
                        <a href="messages.php" class="db-nav-link">
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

            <!-- ══════════ RIGHT: BENTO WORKSPACE ══════════ -->
            <main class="db-main">

                <!-- Welcome Banner -->
                <div class="db-hero-banner">
                    <div>
                        <h1 class="db-hero-title">Welcome back, <?= htmlspecialchars($buyer['full_name']) ?> 👋</h1>
                        <p class="db-hero-sub">Track your saved properties, inquiries, and conversations with sellers.</p>
                    </div>
                    <div>
                        <a href="../search.php" class="db-btn db-btn-primary">
                            <i data-lucide="search" style="width:15px;height:15px;"></i> Find Properties
                        </a>
                    </div>
                </div>

                <!-- Bento Metrics -->
                <div class="db-metrics-grid">
                    <div class="db-metric-card">
                        <div class="db-metric-icon db-metric-rose">
                            <i data-lucide="heart" style="width:22px;height:22px;"></i>
                        </div>
                        <div class="db-metric-info">
                            <div class="db-metric-label">Favorites</div>
                            <div class="db-metric-value"><?= $favoritesCount ?></div>
                        </div>
                    </div>

                    <div class="db-metric-card">
                        <div class="db-metric-icon db-metric-blue">
                            <i data-lucide="send" style="width:22px;height:22px;"></i>
                        </div>
                        <div class="db-metric-info">
                            <div class="db-metric-label">Inquiries Sent</div>
                            <div class="db-metric-value"><?= $inquiriesCount ?></div>
                        </div>
                    </div>

                    <div class="db-metric-card">
                        <div class="db-metric-icon db-metric-purple">
                            <i data-lucide="message-square" style="width:22px;height:22px;"></i>
                        </div>
                        <div class="db-metric-info">
                            <div class="db-metric-label">Unread Messages</div>
                            <div class="db-metric-value"><?= $unreadCount ?></div>
                        </div>
                    </div>

                    <div class="db-metric-card">
                        <div class="db-metric-icon db-metric-emerald">
                            <i data-lucide="calendar" style="width:22px;height:22px;"></i>
                        </div>
                        <div class="db-metric-info">
                            <div class="db-metric-label">Member Since</div>
                            <div class="db-metric-value" style="font-size: 1.15rem;"><?= formatDateTime($buyer['created_at'], 'M Y') ?></div>
                        </div>
                    </div>
                </div>

                <!-- Recent Inquiries Panel -->
                <div class="db-panel">
                    <div class="db-panel-head">
                        <h3 class="db-panel-title">
                            <i data-lucide="inbox" style="width:17px;height:17px;color:#2563EB;"></i>
                            Recent Inquiries
                        </h3>
                        <?php if (count($recentInquiries) > 0): ?>
                            <span style="font-size: 0.78rem; color: #64748B; font-weight: 600;"><?= count($recentInquiries) ?> Latest</span>
                        <?php endif; ?>
                    </div>
                    <div class="db-panel-body" style="padding: 0;">
                        <?php if (empty($recentInquiries)): ?>
                            <div style="padding: 32px; text-align: center; color: #94A3B8;">
                                <i data-lucide="send" style="width:36px;height:36px;margin-bottom:8px;color:#CBD5E1;"></i>
                                <p style="margin: 0; font-size: 0.9rem;">You haven't made any inquiries yet. Browse properties to get in touch with sellers!</p>
                            </div>
                        <?php else: ?>
                            <div class="db-table-wrap">
                                <table class="db-table">
                                    <thead>
                                        <tr>
                                            <th>Property</th>
                                            <th>Price</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                            <th style="text-align: right;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentInquiries as $inquiry): ?>
                                            <?php
                                                $imagePath = $inquiry['property_image'];
                                                if (!empty($imagePath) && substr($imagePath, 0, 4) !== 'http') {
                                                    $imagePath = '../' . ltrim($imagePath, '/');
                                                } elseif (empty($imagePath)) {
                                                    $imagePath = '../XTate-Image.png';
                                                }
                                                $st = strtolower($inquiry['status'] ?? 'pending');
                                                $chipClass = ($st === 'approved' || $st === 'responded') ? 'db-status-approved' : ($st === 'closed' ? 'db-status-closed' : 'db-status-pending');
                                            ?>
                                            <tr>
                                                <td>
                                                    <div class="db-prop-cell">
                                                        <img src="<?= htmlspecialchars($imagePath) ?>" alt="Property" class="db-prop-img" onerror="this.src='../XTate-Image.png'">
                                                        <div>
                                                            <div class="db-prop-title"><?= htmlspecialchars($inquiry['property_title']) ?></div>
                                                            <div class="db-prop-seller">Seller: <?= htmlspecialchars($inquiry['seller_name']) ?></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><span class="db-price-pill"><?= formatCurrency($inquiry['price']) ?></span></td>
                                                <td>
                                                    <span class="db-status-chip <?= $chipClass ?>">
                                                        <?= htmlspecialchars(ucfirst($st)) ?>
                                                    </span>
                                                </td>
                                                <td style="color: #64748B; font-size: 0.8rem;"><?= formatDateTime($inquiry['created_at'], 'd M Y') ?></td>
                                                <td style="text-align: right;">
                                                    <div style="display: inline-flex; gap: 6px;">
                                                        <a href="../property_details.php?id=<?= $inquiry['property_id'] ?>" class="db-action-btn" title="View Property" data-bs-toggle="tooltip">
                                                            <i data-lucide="eye" style="width:14px;height:14px;"></i>
                                                        </a>
                                                        <a href="messages.php?user=<?= $inquiry['seller_id'] ?>" class="db-action-btn" title="Message Seller" data-bs-toggle="tooltip">
                                                            <i data-lucide="message-square" style="width:14px;height:14px;"></i>
                                                        </a>
                                                        <button class="db-action-btn db-action-btn--delete delete-inquiry" data-inquiry-id="<?= $inquiry['id'] ?>" title="Delete Inquiry" data-bs-toggle="tooltip">
                                                            <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recently Added Properties Feed -->
                <div class="db-panel">
                    <div class="db-panel-head">
                        <h3 class="db-panel-title">
                            <i data-lucide="sparkles" style="width:17px;height:17px;color:#2563EB;"></i>
                            Recently Added Properties
                        </h3>
                        <a href="../search.php" style="font-size: 0.82rem; font-weight: 600; color: #2563EB; text-decoration: none;">
                            Browse All →
                        </a>
                    </div>
                    <div class="db-panel-body">
                        <div class="db-props-grid">
                            <?php foreach ($recentProperties as $property): ?>
                                <?php
                                    $pImg = $property['primary_image'];
                                    if (!empty($pImg) && substr($pImg, 0, 4) !== 'http') {
                                        $pImg = '../' . ltrim($pImg, '/');
                                    } elseif (empty($pImg)) {
                                        $pImg = '../XTate-Image.png';
                                    }
                                ?>
                                <div class="db-pcard">
                                    <div class="db-pcard-img-wrap">
                                        <img src="<?= htmlspecialchars($pImg) ?>" alt="<?= htmlspecialchars($property['title']) ?>" class="db-pcard-img" onerror="this.src='../XTate-Image.png'">
                                        <div class="db-pcard-price"><?= formatCurrency($property['price']) ?></div>
                                    </div>
                                    <div class="db-pcard-body">
                                        <h4 class="db-pcard-title">
                                            <a href="../property_details.php?id=<?= $property['id'] ?>"><?= htmlspecialchars($property['title']) ?></a>
                                        </h4>
                                        <div class="db-pcard-loc">
                                            <i data-lucide="map-pin" style="width:12px;height:12px;color:#2563EB;"></i>
                                            <span><?= htmlspecialchars($property['city']) ?>, <?= htmlspecialchars($property['state']) ?></span>
                                        </div>
                                        <div class="db-pcard-specs">
                                            <span class="db-pcard-spec-item"><i data-lucide="bed" style="width:13px;height:13px;"></i> <?= $property['bedrooms'] ?> Beds</span>
                                            <span class="db-pcard-spec-item"><i data-lucide="bath" style="width:13px;height:13px;"></i> <?= $property['bathrooms'] ?> Baths</span>
                                            <?php $dashArea = (float)($property['area'] ?? $property['area_sqft'] ?? $property['sqft'] ?? 0); ?>
                                            <span class="db-pcard-spec-item"><i data-lucide="maximize-2" style="width:13px;height:13px;"></i> <?= number_format($dashArea) ?> sqft</span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

            </main>

        </div>
    </div>
</div>

<!-- Delete Confirmation Modal (PRESERVED) -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 16px; border: 1px solid #E2E8F0; overflow: hidden;">
            <div class="modal-header" style="background: #F8FAFC; border-bottom: 1px solid #E2E8F0;">
                <h5 class="modal-title font-weight-bold" id="confirmDeleteModalLabel" style="font-family: 'Plus Jakarta Sans', sans-serif; font-size: 1rem; font-weight: 700;">
                    Delete Inquiry
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="padding: 20px; color: #475569; font-size: 0.9rem;">
                Are you sure you want to delete this inquiry? This action cannot be undone.
            </div>
            <div class="modal-footer" style="border-top: 1px solid #F1F5F9; padding: 14px 20px;">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal" style="border-radius: 8px;">Cancel</button>
                <button type="button" class="btn btn-sm btn-danger" id="confirmDelete" style="border-radius: 8px;">Delete Inquiry</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') lucide.createIcons();

    // Initialize tooltips
    const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltips.forEach(tooltip => new bootstrap.Tooltip(tooltip));

    let currentButton = null;
    const deleteModal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));

    // Handle delete inquiry
    document.querySelectorAll('.delete-inquiry').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            currentButton = this;
            deleteModal.show();
        });
    });

    // Handle confirmation
    document.getElementById('confirmDelete').addEventListener('click', function() {
        if (!currentButton) return;

        const inquiryId = currentButton.getAttribute('data-inquiry-id');
        const formData = new FormData();
        formData.append('action', 'delete_inquiry');
        formData.append('inquiry_id', inquiryId);

        fetch('../api/inquiries.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            deleteModal.hide();
            if (data.success) {
                currentButton.closest('tr').remove();
                if (typeof Notyf !== 'undefined') {
                    new Notyf().success('Inquiry deleted successfully!');
                }
                setTimeout(() => window.location.reload(), 1500);
            } else {
                if (typeof Notyf !== 'undefined') {
                    new Notyf().error(data.message || 'Failed to delete inquiry');
                } else {
                    alert(data.message || 'Failed to delete inquiry');
                }
            }
        })
        .catch(error => {
            deleteModal.hide();
            console.error('Error:', error);
            if (typeof Notyf !== 'undefined') {
                new Notyf().error('An error occurred while deleting the inquiry');
            } else {
                alert('An error occurred while deleting the inquiry');
            }
        });
    });
});
</script>

<script src="../js/auth.js"></script>
<?php include '../inc/footer.php'; ?>