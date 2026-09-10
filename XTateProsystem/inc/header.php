<?php
// Determine proper path to include files based on directory structure
$inc_dir = '';
if (strpos($_SERVER['PHP_SELF'], '/buyer/') !== false || 
    strpos($_SERVER['PHP_SELF'], '/seller/') !== false || 
    strpos($_SERVER['PHP_SELF'], '/admin/') !== false) {
    $inc_dir = '../';
}

require_once $inc_dir . 'inc/db.php';
require_once $inc_dir . 'inc/functions.php';
require_once $inc_dir . 'inc/auth.php';

// Start session
startSession();

// Get current page name
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>XTate Prosystem — Real Estate Platform</title>
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="<?= $inc_dir ?>XTate-Logo.png" type="image/x-icon">

    <!-- ═══════════════════════════════════════════════
         MODERN TYPOGRAPHY — Plus Jakarta Sans + Inter
         ═══════════════════════════════════════════════ -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- ═══════════════════════════════════════════════
         CORE CSS FRAMEWORKS
         ═══════════════════════════════════════════════ -->
    <!-- Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <!-- Font Awesome (kept as fallback icon library) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- ═══════════════════════════════════════════════
         MODERN UI LIBRARIES
         ═══════════════════════════════════════════════ -->
    <!-- Swiper.js — Property card image carousels -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
    <!-- GLightbox — Full-screen photo gallery viewer -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css">
    <!-- Notyf — Modern floating toast notifications -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.css">
    <!-- Leaflet.js — Interactive maps -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">

    <!-- ═══════════════════════════════════════════════
         PROJECT STYLESHEETS
         ═══════════════════════════════════════════════ -->
    <link rel="stylesheet" href="<?= $inc_dir ?>css/style.css">
    <link rel="stylesheet" href="<?= $inc_dir ?>css/responsive.css">

    <!-- ═══════════════════════════════════════════════
         JAVASCRIPT — Core Frameworks (defer for performance)
         ═══════════════════════════════════════════════ -->
    <!-- Bootstrap Bundle (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" defer></script>
    <!-- Chart.js (existing, preserved) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js" defer></script>
    <!-- Lucide Icons — Modern crisp SVG icon system -->
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js" defer></script>
    <!-- Swiper.js -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js" defer></script>
    <!-- GLightbox -->
    <script src="https://cdn.jsdelivr.net/npm/glightbox/dist/js/glightbox.min.js" defer></script>
    <!-- Notyf -->
    <script src="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.js" defer></script>
    <!-- CountUp.js — Animated number counters -->
    <script src="https://cdn.jsdelivr.net/npm/countup.js@2.8.0/dist/countUp.umd.js" defer></script>
    <!-- Leaflet.js -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" defer></script>
    <!-- Project Main JS -->
    <script src="<?= $inc_dir ?>js/main.js" defer></script>

    <!-- ═══════════════════════════════════════════════
         LUCIDE INIT — runs after all deferred scripts load
         ═══════════════════════════════════════════════ -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof lucide !== 'undefined') lucide.createIcons();
        });
    </script>
</head>
<body>
    <!-- ═══════════════════════════════════════════════════════════
         MODERN GLASSMORPHIC NAVIGATION BAR
         ═══════════════════════════════════════════════════════════ -->
    <header class="xt-header">
        <nav class="navbar navbar-expand-lg xt-navbar">
            <div class="container">
                <!-- Brand Logo -->
                <a class="navbar-brand xt-brand" href="<?= $inc_dir ?>index.php">
                    <img src="<?= $inc_dir ?>XTate-Logo.png" alt="XTate Prosystem" height="38" class="d-inline-block align-middle">
                </a>

                <!-- Mobile Toggle -->
                <button class="navbar-toggler xt-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <i data-lucide="menu" style="width:20px;height:20px;"></i>
                </button>

                <!-- Nav Links -->
                <div class="collapse navbar-collapse" id="navbarNav">
                    <!-- Left nav links -->
                    <ul class="navbar-nav me-auto xt-nav-links">
                        <li class="nav-item">
                            <a class="nav-link xt-nav-link <?= ($current_page == 'index.php') ? 'active' : '' ?>" href="<?= $inc_dir ?>index.php">
                                Home
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link xt-nav-link <?= ($current_page == 'search.php') ? 'active' : '' ?>" href="<?= $inc_dir ?>search.php">
                                Properties
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link xt-nav-link <?= ($current_page == 'contact.php') ? 'active' : '' ?>" href="<?= $inc_dir ?>contact.php">
                                Contact
                            </a>
                        </li>
                    </ul>

                    <!-- Right nav: auth-aware -->
                    <ul class="navbar-nav ms-auto align-items-center xt-nav-right">
                        <?php if (isLoggedIn()): ?>
                            <!-- Dashboard link based on role -->
                            <?php if ($_SESSION['user_role'] == 'admin'): ?>
                                <li class="nav-item">
                                    <a class="nav-link xt-nav-link" href="<?= $inc_dir ?>admin/dashboard.php">
                                        <i data-lucide="layout-dashboard" class="xt-nav-icon"></i> Admin
                                    </a>
                                </li>
                            <?php elseif ($_SESSION['user_role'] == 'seller'): ?>
                                <li class="nav-item">
                                    <a class="nav-link xt-nav-link" href="<?= $inc_dir ?>seller/dashboard.php">
                                        <i data-lucide="layout-dashboard" class="xt-nav-icon"></i> Dashboard
                                    </a>
                                </li>
                            <?php elseif ($_SESSION['user_role'] == 'buyer'): ?>
                                <li class="nav-item">
                                    <a class="nav-link xt-nav-link" href="<?= $inc_dir ?>buyer/dashboard.php">
                                        <i data-lucide="layout-dashboard" class="xt-nav-icon"></i> Dashboard
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php
                            // Get unread messages count if user is buyer or seller (PRESERVED from original)
                            $unreadCount = 0;
                            if ($_SESSION['user_role'] == 'buyer' || $_SESSION['user_role'] == 'seller') {
                                $unreadCount = getUnreadMessagesCount($_SESSION['user_id']);
                            }
                            ?>

                            <?php if ($_SESSION['user_role'] == 'buyer' || $_SESSION['user_role'] == 'seller'): ?>
                                <li class="nav-item">
                                    <a class="nav-link xt-nav-link position-relative" href="<?= $inc_dir . $_SESSION['user_role'] ?>/messages.php">
                                        <i data-lucide="message-circle" class="xt-nav-icon"></i>
                                        <?php if ($unreadCount > 0): ?>
                                            <span class="xt-badge-dot"><?= $unreadCount ?></span>
                                        <?php endif; ?>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <!-- User Dropdown -->
                            <li class="nav-item dropdown">
                                <a class="xt-user-pill dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <span class="xt-avatar"><?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?></span>
                                    <span class="xt-user-name d-none d-lg-inline"><?= htmlspecialchars($_SESSION['user_name']) ?></span>
                                    <i data-lucide="chevron-down" style="width:14px;height:14px;opacity:0.6;"></i>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end xt-dropdown" aria-labelledby="navbarDropdown">
                                    <li class="px-3 py-2 border-bottom mb-1" style="background:#F8FAFC; border-radius: 8px 8px 0 0;">
                                        <div class="fw-bold text-dark text-truncate" style="max-width: 180px; font-size: 0.85rem;"><?= htmlspecialchars($_SESSION['user_name']) ?></div>
                                        <div class="text-muted text-truncate" style="max-width: 180px; font-size: 0.75rem;"><?= htmlspecialchars($_SESSION['user_email'] ?? '') ?></div>
                                        <span class="badge bg-primary text-white text-uppercase mt-1" style="font-size: 0.65rem; font-weight: 700; letter-spacing: 0.04em;">
                                            <?= htmlspecialchars($_SESSION['user_role'] ?? 'user') ?>
                                        </span>
                                    </li>
                                    <?php if ($_SESSION['user_role'] == 'buyer'): ?>
                                        <li>
                                            <a class="dropdown-item xt-dropdown-item" href="<?= $inc_dir ?>buyer/favorites.php">
                                                <i data-lucide="heart" class="xt-dropdown-icon"></i> My Favorites
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item xt-dropdown-item" href="<?= $inc_dir ?>buyer/profile.php">
                                                <i data-lucide="user" class="xt-dropdown-icon"></i> My Profile
                                            </a>
                                        </li>
                                    <?php elseif ($_SESSION['user_role'] == 'seller'): ?>
                                        <li>
                                            <a class="dropdown-item xt-dropdown-item" href="<?= $inc_dir ?>seller/dashboard.php">
                                                <i data-lucide="layout-grid" class="xt-dropdown-icon"></i> Seller Dashboard
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item xt-dropdown-item" href="<?= $inc_dir ?>seller/profile.php">
                                                <i data-lucide="user" class="xt-dropdown-icon"></i> My Profile
                                            </a>
                                        </li>
                                    <?php elseif ($_SESSION['user_role'] == 'admin'): ?>
                                        <li>
                                            <a class="dropdown-item xt-dropdown-item" href="<?= $inc_dir ?>admin/dashboard.php">
                                                <i data-lucide="layout-grid" class="xt-dropdown-icon"></i> Admin Dashboard
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item xt-dropdown-item" href="<?= $inc_dir ?>admin/users.php">
                                                <i data-lucide="users" class="xt-dropdown-icon"></i> Manage Users
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    <li><hr class="dropdown-divider xt-divider my-1"></li>
                                    <li>
                                        <a class="dropdown-item xt-dropdown-item xt-logout" href="<?= $inc_dir ?>logout.php">
                                            <i data-lucide="log-out" class="xt-dropdown-icon"></i> Logout
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link xt-nav-link <?= ($current_page == 'login.php') ? 'active' : '' ?>" href="<?= $inc_dir ?>login.php">Login</a>
                            </li>
                            <li class="nav-item">
                                <a class="xt-btn-register" href="<?= $inc_dir ?>register.php">
                                    Get Started
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <!-- Main Content -->
    <main class="main-content">
