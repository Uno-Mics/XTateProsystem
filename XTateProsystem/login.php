<?php
require_once 'inc/db.php';
require_once 'inc/functions.php';
require_once 'inc/auth.php';

// Start session
startSession();

// Redirect if already logged in
if (isLoggedIn()) {
    // Redirect based on user role
    switch ($_SESSION['user_role']) {
        case 'admin':
            redirect('admin/dashboard.php');
            break;
        case 'seller':
            redirect('seller/dashboard.php');
            break;
        case 'buyer':
            redirect('buyer/dashboard.php');
            break;
        default:
            redirect('index.php');
            break;
    }
}

$errors = [];
$success = '';
$email = '';

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    // Validate form data
    if (empty($email)) {
        $errors[] = 'Email is required';
    }
    
    if (empty($password)) {
        $errors[] = 'Password is required';
    }
    
    // If no errors, attempt to login
    if (empty($errors)) {
        $result = loginUser($email, $password);
        
        if ($result['success']) {
            // Set remember me cookie if selected
            if ($remember) {
                $token = generateRandomString();
                $userId = $result['user']['id'];
                
                // Just set the cookie without database update
                // since we don't have remember_token column yet
                setcookie('remember_token', $token, time() + (86400 * 30), '/');
                
                // Note: In a production environment, you would store this token in the database
                // $sql = "UPDATE users SET remember_token = ? WHERE id = ?";
                // updateData($sql, "si", [$token, $userId]);
            }
            
            // Redirect based on user role
            switch ($result['user']['role']) {
                case 'admin':
                    redirect('admin/dashboard.php');
                    break;
                case 'seller':
                    redirect('seller/dashboard.php');
                    break;
                case 'buyer':
                    redirect('buyer/dashboard.php');
                    break;
                default:
                    redirect('index.php');
                    break;
            }
        } else {
            $errors[] = $result['message'];
        }
    }
}

include 'inc/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="auth-form">
                <h2 class="auth-title">Login to Your Account</h2>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?= $error ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form id="loginForm" method="POST" action="<?= $_SERVER['PHP_SELF'] ?>" novalidate>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?= $email ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                        <label class="form-check-label" for="remember">Remember me</label>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">Login</button>
                    </div>
                </form>
                
                <div class="auth-footer">
                    Don't have an account? <a href="register.php">Register</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="js/auth.js"></script>

<?php include 'inc/footer.php'; ?>
