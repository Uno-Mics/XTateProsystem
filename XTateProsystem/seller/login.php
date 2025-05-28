<?php
require_once '../inc/db.php';
require_once '../inc/functions.php';

// Check if user is already logged in and redirect to dashboard if they are
session_start();
if (isset($_SESSION['user_id']) && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'seller') {
    header('Location: dashboard.php');
    exit;
}

// Process login form
$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validate inputs
    if (empty($email) || empty($password)) {
        $error = 'Email and password are required';
    } else {
        // Attempt to login
        $conn = connectDB();
        if ($conn) {
            $tableCheck = $conn->query("SHOW TABLES LIKE 'users'");
            if ($tableCheck && $tableCheck->num_rows > 0) {
                // Use prepared statement to prevent SQL injection
                $stmt = $conn->prepare("SELECT id, email, password, first_name, last_name, role FROM users WHERE email = ? AND role = 'seller' LIMIT 1");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 1) {
                    $user = $result->fetch_assoc();
                    
                    // Verify password
                    if (verifyPassword($password, $user['password'])) {
                        // Set session variables
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_email'] = $user['email'];
                        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                        $_SESSION['user_role'] = 'seller';
                        
                        // Redirect to dashboard
                        header('Location: dashboard.php');
                        exit;
                    } else {
                        $error = 'Invalid email or password';
                    }
                } else {
                    $error = 'Invalid email or password';
                }
                
                $stmt->close();
            } else {
                // Demo login for testing purposes when database tables don't exist
                if ($email === 'seller@example.com' && $password === 'password') {
                    // Set session variables for demo seller
                    $_SESSION['user_id'] = 2;
                    $_SESSION['user_email'] = 'seller@example.com';
                    $_SESSION['user_name'] = 'John Seller';
                    $_SESSION['user_role'] = 'seller';
                    
                    // Redirect to dashboard
                    header('Location: dashboard.php');
                    exit;
                } else {
                    $error = 'Invalid email or password';
                }
            }
            
            $conn->close();
        } else {
            // Demo login for testing purposes when database connection fails
            if ($email === 'seller@example.com' && $password === 'password') {
                // Set session variables for demo seller
                $_SESSION['user_id'] = 2;
                $_SESSION['user_email'] = 'seller@example.com';
                $_SESSION['user_name'] = 'John Seller';
                $_SESSION['user_role'] = 'seller';
                
                // Redirect to dashboard
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Invalid email or password';
            }
        }
    }
}

include '../inc/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="card-title mb-0">Seller Login</h4>
                </div>
                <div class="card-body p-4">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger">
                            <?= $error ?>
                        </div>
                    <?php endif; ?>
                    
                    <form id="loginForm" method="POST" action="<?= $_SERVER['PHP_SELF'] ?>">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= $email ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Login</button>
                        </div>
                    </form>
                    
                    <div class="mt-3 text-center">
                        <p>Don't have an account? <a href="../register.php?role=seller">Register as a Seller</a></p>
                        <p><a href="../login.php">Login as Buyer or Admin</a></p>
                    </div>
                </div>
                
                <div class="card-footer bg-light p-3">
                    <div class="demo-login-info">
                        <p class="mb-0"><strong>Demo Login:</strong> seller@example.com / password</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include '../inc/footer.php';
?>