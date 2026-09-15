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

// Get statistics from Cloud Firestore
$firestoreProps = firestore_get_properties(['status' => 'all']);
$firestoreUsers = firestore_get_users(true);

$totalProperties = count($firestoreProps);
$activeProperties = 0;
$totalPropertyValue = 0;
foreach ($firestoreProps as $fp) {
    if (($fp['status'] ?? '') === 'active') {
        $activeProperties++;
        $totalPropertyValue += ($fp['price'] ?? 0);
    }
}
$totalUsers = count($firestoreUsers);

// Fallbacks if empty
if ($totalProperties === 0) {
    $totalProperties = fetchOne("SELECT COUNT(*) as count FROM properties", "", [])['count'] ?? 0;
    $activeProperties = fetchOne("SELECT COUNT(*) as count FROM properties WHERE status = 'active'", "", [])['count'] ?? 0;
    $totalPropertyValue = fetchOne("SELECT SUM(price) as total FROM properties WHERE status = 'active'", "", [])['total'] ?? 0;
}
if ($totalUsers === 0) {
    $totalUsers = fetchOne("SELECT COUNT(*) as count FROM users WHERE role != 'admin'", "", [])['count'] ?? 0;
}

$totalReports = fetchOne("SELECT COUNT(*) as count FROM reports", "", [])['count'] ?? 0;
$pendingReports = fetchOne("SELECT COUNT(*) as count FROM reports WHERE status = 'pending'", "", [])['count'] ?? 0;

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

<style>
/* ============================================================
   ADMIN DASHBOARD — Modern Minimalist + Bento Grid
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
    transition: box-shadow .2s;
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

.db-nav-link i {
    flex-shrink: 0;
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

.db-hero-sub {
    font-size: 0.88rem;
    color: #64748B;
    margin: 0;
}

.db-hero-actions {
    display: flex;
    gap: 10px;
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

.db-btn-secondary {
    background: #F8FAFC;
    color: #475569;
    border: 1px solid #E2E8F0;
}

.db-btn-secondary:hover {
    background: #F1F5F9;
    color: #0F172A;
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

.db-metric-blue   { background: #EFF6FF; color: #2563EB; }
.db-metric-amber  { background: #FFFBEB; color: #D97706; }
.db-metric-emerald{ background: #ECFDF5; color: #059669; }
.db-metric-purple { background: #FAF5FF; color: #7C3AED; }

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
    font-size: 1.6rem;
    font-weight: 800;
    color: #0F172A;
    line-height: 1.15;
}

/* ── Bento Content Grid ── */
.db-bento-grid {
    display: grid;
    grid-template-columns: repeat(12, 1fr);
    gap: 20px;
}

.db-bento-span-7 { grid-column: span 7; }
.db-bento-span-5 { grid-column: span 5; }
.db-bento-span-6 { grid-column: span 6; }
.db-bento-span-12{ grid-column: span 12; }

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
    font-size: 0.95rem;
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

/* Valuation card highlight */
.db-value-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    color: #0F172A;
    padding: 24px;
    border-radius: 16px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.05);
}

.db-value-sub {
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #64748B;
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.db-value-num {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 2rem;
    font-weight: 800;
    color: #2563EB;
    letter-spacing: -0.03em;
    margin: 0;
}

/* ── Responsive ── */
@media (max-width: 991.98px) {
    .db-layout {
        grid-template-columns: 1fr;
    }
    .db-metrics-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .db-bento-span-7,
    .db-bento-span-5,
    .db-bento-span-6 {
        grid-column: span 12;
    }
}

@media (max-width: 575.98px) {
    .db-wrap { padding: 18px 0 40px; }
    .db-metrics-grid {
        grid-template-columns: 1fr;
    }
    .db-hero-banner {
        flex-direction: column;
        align-items: flex-start;
    }
    .db-hero-actions {
        width: 100%;
    }
    .db-hero-actions .db-btn {
        flex: 1;
        justify-content: center;
    }
}
</style>

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
                        <a href="dashboard.php" class="db-nav-link active">
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
                        <a href="messages.php" class="db-nav-link">
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

            <!-- ══════════ RIGHT: BENTO WORKSPACE ══════════ -->
            <main class="db-main">

                <!-- Welcome Hero Banner -->
                <div class="db-hero-banner">
                    <div>
                        <h1 class="db-hero-title">Welcome back, <?= htmlspecialchars($adminName) ?> 👋</h1>
                        <p class="db-hero-sub">Here is the real-time operational overview of the XTate platform.</p>
                    </div>
                    <div class="db-hero-actions">
                        <a href="reports.php" class="db-btn db-btn-secondary">
                            <i data-lucide="alert-circle" style="width:15px;height:15px;"></i> Review Reports
                        </a>
                        <a href="properties.php" class="db-btn db-btn-primary">
                            <i data-lucide="home" style="width:15px;height:15px;"></i> All Properties
                        </a>
                    </div>
                </div>

                <!-- Bento Metrics -->
                <div class="db-metrics-grid">
                    <div class="db-metric-card">
                        <div class="db-metric-icon db-metric-blue">
                            <i data-lucide="flag" style="width:22px;height:22px;"></i>
                        </div>
                        <div class="db-metric-info">
                            <div class="db-metric-label">Total Reports</div>
                            <div class="db-metric-value"><?= $totalReports ?></div>
                        </div>
                    </div>

                    <div class="db-metric-card">
                        <div class="db-metric-icon db-metric-amber">
                            <i data-lucide="clock" style="width:22px;height:22px;"></i>
                        </div>
                        <div class="db-metric-info">
                            <div class="db-metric-label">Pending Reports</div>
                            <div class="db-metric-value"><?= $pendingReports ?></div>
                        </div>
                    </div>

                    <div class="db-metric-card">
                        <div class="db-metric-icon db-metric-emerald">
                            <i data-lucide="home" style="width:22px;height:22px;"></i>
                        </div>
                        <div class="db-metric-info">
                            <div class="db-metric-label">Active Properties</div>
                            <div class="db-metric-value"><?= $activeProperties ?></div>
                        </div>
                    </div>

                    <div class="db-metric-card">
                        <div class="db-metric-icon db-metric-purple">
                            <i data-lucide="users" style="width:22px;height:22px;"></i>
                        </div>
                        <div class="db-metric-info">
                            <div class="db-metric-label">Total Users</div>
                            <div class="db-metric-value"><?= $totalUsers ?></div>
                        </div>
                    </div>
                </div>

                <!-- Bento Row: Charts & Highlight -->
                <div class="db-bento-grid">
                    
                    <!-- Monthly User Registrations Chart -->
                    <div class="db-panel db-bento-span-7">
                        <div class="db-panel-head">
                            <h3 class="db-panel-title">
                                <i data-lucide="trending-up" style="width:17px;height:17px;color:#2563EB;"></i>
                                Monthly User Registrations
                            </h3>
                            <span style="font-size: 0.75rem; color: #94A3B8; font-weight: 600;">Last 6 Months</span>
                        </div>
                        <div class="db-panel-body">
                            <div style="position: relative; height: 260px; width: 100%;">
                                <canvas id="userRegistrationChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Total Valuation + Quick Info -->
                    <div class="db-bento-span-5" style="display: flex; flex-direction: column; gap: 20px;">
                        <div class="db-value-card">
                            <div class="db-value-sub">
                                <i data-lucide="wallet" style="width:15px;height:15px;color:#2563EB;"></i>
                                <span>Total Active Portfolio Value</span>
                            </div>
                            <div class="db-value-num">$<?= number_format($totalPropertyValue, 2) ?></div>
                            <div style="margin-top: 12px; font-size: 0.8rem; color: #64748B; display: flex; align-items: center; gap: 6px;">
                                <i data-lucide="check-circle-2" style="width:14px;height:14px;color:#10B981;"></i>
                                <span><?= $activeProperties ?> active listings calculated</span>
                            </div>
                        </div>

                        <!-- Property Type Distribution -->
                        <div class="db-panel" style="flex: 1;">
                            <div class="db-panel-head">
                                <h3 class="db-panel-title">
                                    <i data-lucide="pie-chart" style="width:17px;height:17px;color:#2563EB;"></i>
                                    Property Types
                                </h3>
                            </div>
                            <div class="db-panel-body">
                                <div style="position: relative; height: 180px; width: 100%;">
                                    <canvas id="propertyTypeChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

            </main>

        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') lucide.createIcons();

    // User Registration Chart
    const userCanvas = document.getElementById('userRegistrationChart');
    if (userCanvas && typeof Chart !== 'undefined') {
        const userCtx = userCanvas.getContext('2d');
        const blueGrad = userCtx.createLinearGradient(0, 0, 0, 260);
        blueGrad.addColorStop(0, 'rgba(37, 99, 235, 0.2)');
        blueGrad.addColorStop(1, 'rgba(37, 99, 235, 0.0)');

        new Chart(userCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column(array_reverse($monthlyUsers), 'month')) ?>,
                datasets: [{
                    label: 'New Registrations',
                    data: <?= json_encode(array_column(array_reverse($monthlyUsers), 'count')) ?>,
                    borderColor: '#2563EB',
                    backgroundColor: blueGrad,
                    borderWidth: 2.5,
                    fill: true,
                    tension: 0.35,
                    pointBackgroundColor: '#2563EB',
                    pointBorderColor: '#FFFFFF',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#0F172A',
                        padding: 10,
                        titleFont: { family: 'Plus Jakarta Sans', size: 12 },
                        bodyFont: { family: 'Inter', size: 12 },
                        cornerRadius: 8
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: '#94A3B8', font: { family: 'Inter', size: 11 } }
                    },
                    y: {
                        grid: { color: '#F1F5F9' },
                        ticks: { color: '#94A3B8', font: { family: 'Inter', size: 11 }, precision: 0 }
                    }
                }
            }
        });
    }

    // Property Type Distribution Chart
    const propCanvas = document.getElementById('propertyTypeChart');
    if (propCanvas && typeof Chart !== 'undefined') {
        const propertyCtx = propCanvas.getContext('2d');
        new Chart(propertyCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_column($propertyTypes, 'name')) ?>,
                datasets: [{
                    data: <?= json_encode(array_column($propertyTypes, 'count')) ?>,
                    backgroundColor: [
                        '#2563EB', '#10B981', '#F59E0B', '#8B5CF6', '#EC4899', '#64748B'
                    ],
                    borderWidth: 2,
                    borderColor: '#FFFFFF'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            color: '#475569',
                            font: { family: 'Inter', size: 11 },
                            boxWidth: 12,
                            padding: 10
                        }
                    }
                },
                cutout: '68%'
            }
        });
    }
});
</script>

<script src="../js/auth.js"></script>
<script src="../js/admin-dashboard.js"></script>
<?php include '../inc/footer.php'; ?>