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
    <title>Real Estate Listing System</title>
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="<?= $inc_dir ?>XTate-Logo.png" type="image/x-icon">
    
    <!-- CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= $inc_dir ?>css/style.css">
    <link rel="stylesheet" href="<?= $inc_dir ?>css/responsive.css">
    
    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js" defer></script>
    <script src="<?= $inc_dir ?>js/main.js" defer></script>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
            <div class="container">
                <a class="navbar-brand" href="<?= $inc_dir ?>index.php">
                    <img src="<?= $inc_dir ?>XTate-Logo.png" alt="XTate Logo" height="40" class="d-inline-block align-middle">
                </a>
                
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link <?= ($current_page == 'index.php') ? 'active' : '' ?>" href="<?= $inc_dir ?>index.php">Home</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= ($current_page == 'search.php') ? 'active' : '' ?>" href="<?= $inc_dir ?>search.php">Properties</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= ($current_page == 'contact.php') ? 'active' : '' ?>" href="<?= $inc_dir ?>contact.php">Contact</a>
                        </li>
                    </ul>
                    
                    <ul class="navbar-nav ms-auto">
                        <?php if (isLoggedIn()): ?>
                            <?php if ($_SESSION['user_role'] == 'admin'): ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="<?= $inc_dir ?>admin/dashboard.php">Admin Dashboard</a>
                                </li>
                            <?php elseif ($_SESSION['user_role'] == 'seller'): ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="<?= $inc_dir ?>seller/dashboard.php">Seller Dashboard</a>
                                </li>
                            <?php elseif ($_SESSION['user_role'] == 'buyer'): ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="<?= $inc_dir ?>buyer/dashboard.php">Buyer Dashboard</a>
                                </li>
                            <?php endif; ?>
                            
                            <?php 
                            // Get unread messages count if user is buyer or seller
                            $unreadCount = 0;
                            if ($_SESSION['user_role'] == 'buyer' || $_SESSION['user_role'] == 'seller') {
                                $unreadCount = getUnreadMessagesCount($_SESSION['user_id']);
                            }
                            ?>
                            
                            <?php if ($_SESSION['user_role'] == 'buyer' || $_SESSION['user_role'] == 'seller'): ?>
                                <li class="nav-item">
                                    <a class="nav-link position-relative" href="<?= $inc_dir . $_SESSION['user_role'] ?>/messages.php">
                                        <i class="fas fa-envelope"></i> Messages
                                        <?php if ($unreadCount > 0): ?>
                                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                                <?= $unreadCount ?>
                                            </span>
                                        <?php endif; ?>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-user-circle"></i> <?= $_SESSION['user_name'] ?>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <?php if ($_SESSION['user_role'] == 'buyer'): ?>
                                        <li><a class="dropdown-item" href="<?= $inc_dir ?>buyer/favorites.php">My Favorites</a></li>
                                    <?php endif; ?>
                                    <li><a class="dropdown-item" href="<?= $inc_dir ?>logout.php">Logout</a></li>
                                </ul>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link <?= ($current_page == 'login.php') ? 'active' : '' ?>" href="<?= $inc_dir ?>login.php">Login</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= ($current_page == 'register.php') ? 'active' : '' ?>" href="<?= $inc_dir ?>register.php">Register</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <!-- Main Content -->
    <main class="main-content">
