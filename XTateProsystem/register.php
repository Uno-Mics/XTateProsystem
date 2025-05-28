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
$formData = [
    'full_name' => '',
    'email' => '',
    'phone' => '',
    'role' => ''
];

// Process registration form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $formData = [
        'full_name' => sanitizeInput($_POST['full_name'] ?? ''),
        'email' => sanitizeInput($_POST['email'] ?? ''),
        'phone' => sanitizeInput($_POST['phone'] ?? ''),
        'role' => sanitizeInput($_POST['role'] ?? '')
    ];
    
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $terms = isset($_POST['terms']);
    
    // Validate form data
    if (empty($formData['full_name'])) {
        $errors[] = 'Full name is required';
    }
    
    if (empty($formData['email'])) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }
    
    if (empty($formData['phone'])) {
        $errors[] = 'Phone number is required';
    }
    
    if (empty($formData['role'])) {
        $errors[] = 'Role is required';
    } elseif (!in_array($formData['role'], ['buyer', 'seller'])) {
        $errors[] = 'Invalid role selected';
    }
    
    if (empty($password)) {
        $errors[] = 'Password is required';
    } elseif (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long';
    }
    
    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match';
    }
    
    if (!$terms) {
        $errors[] = 'You must agree to the terms and conditions';
    }
    
    // If no errors, register the user
    if (empty($errors)) {
        $result = registerUser(
            $formData['full_name'],
            $formData['email'],
            $formData['phone'],
            $password,
            $formData['role']
        );
        
        if ($result['success']) {
            $success = $result['message'];
            // Clear form data after successful registration
            $formData = [
                'full_name' => '',
                'email' => '',
                'phone' => '',
                'role' => ''
            ];
        } else {
            $errors[] = $result['message'];
        }
    }
}

include 'inc/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="auth-form">
                <h2 class="auth-title">Create an Account</h2>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?= $error ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success">
                        <?= $success ?>
                        <p class="mt-3 mb-0">
                            <a href="login.php" class="btn btn-primary">Login Now</a>
                        </p>
                    </div>
                <?php else: ?>
                    <form id="registerForm" method="POST" action="<?= $_SERVER['PHP_SELF'] ?>" novalidate>
                        <div class="mb-3">
                            <label for="full_name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" value="<?= $formData['full_name'] ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= $formData['email'] ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone" value="<?= $formData['phone'] ?>" placeholder="123-456-7890" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="role" class="form-label">I want to</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="" <?= empty($formData['role']) ? 'selected' : '' ?>>Select a role</option>
                                <option value="buyer" <?= $formData['role'] === 'buyer' ? 'selected' : '' ?>>Buy a property</option>
                                <option value="seller" <?= $formData['role'] === 'seller' ? 'selected' : '' ?>>Sell a property</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <div class="form-text">Password must be at least 8 characters long.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                            <label class="form-check-label" for="terms">I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a></label>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">Register</button>
                        </div>
                    </form>
                    
                    <div class="auth-footer">
                        Already have an account? <a href="login.php">Login</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="js/auth.js"></script>

<?php include 'inc/footer.php'; ?>
