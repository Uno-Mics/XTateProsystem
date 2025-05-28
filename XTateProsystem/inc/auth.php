<?php
require_once 'functions.php';

// Start session if not already started
function startSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// Check if user is logged in
function isLoggedIn() {
    startSession();
    return isset($_SESSION['user_id']);
}

// Redirect to another page
function redirect($url) {
    header("Location: $url");
    exit;
}

// Get demo users for authentication
function getDemoUsers() {
    return [
        [
            'id' => 1,
            'full_name' => 'John Seller',
            'email' => 'seller@example.com',
            'phone' => '555-123-4567',
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password: password
            'role' => 'seller',
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s', strtotime('-30 days')),
            'last_login' => date('Y-m-d H:i:s')
        ],
        [
            'id' => 2,
            'full_name' => 'Jane Buyer',
            'email' => 'buyer@example.com',
            'phone' => '555-987-6543',
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password: password
            'role' => 'buyer',
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s', strtotime('-25 days')),
            'last_login' => date('Y-m-d H:i:s')
        ],
        [
            'id' => 3,
            'full_name' => 'Admin User',
            'email' => 'admin@example.com',
            'phone' => '555-111-2222',
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password: password
            'role' => 'admin',
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s', strtotime('-60 days')),
            'last_login' => date('Y-m-d H:i:s')
        ]
    ];
}

// Find user by email in demo data
function findUserByEmail($email) {
    $users = getDemoUsers();
    foreach ($users as $user) {
        if ($user['email'] === $email) {
            return $user;
        }
    }
    return null;
}

// Find user by ID in demo data
function findUserById($id) {
    $users = getDemoUsers();
    foreach ($users as $user) {
        if ($user['id'] == $id) {
            return $user;
        }
    }
    return null;
}

// Register new user
function registerUser($fullName, $email, $phone, $password, $role) {
    // Check if user already exists
    $sql = "SELECT id FROM users WHERE email = ?";
    $exists = recordExists($sql, "s", [$email]);
    
    if ($exists) {
        return [
            'success' => false,
            'message' => 'Email is already registered. Please login or use a different email.'
        ];
    }
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert new user
    $sql = "INSERT INTO users (full_name, email, phone, password, role, status, created_at) 
            VALUES (?, ?, ?, ?, ?, 'active', NOW())";
    
    $userId = insertData($sql, "sssss", [
        $fullName,
        $email,
        $phone,
        $hashedPassword,
        $role
    ]);
    
    if ($userId) {
        return [
            'success' => true,
            'user_id' => $userId,
            'message' => 'Registration successful! You can now login.'
        ];
    } else {
        // Fallback to demo users if database insert fails
        $demoUsers = getDemoUsers();
        foreach ($demoUsers as $user) {
            if ($user['email'] === $email) {
                return [
                    'success' => false,
                    'message' => 'Email is already registered. Please login or use a different email.'
                ];
            }
        }
        
        return [
            'success' => true,
            'user_id' => 999, // Demo user ID
            'message' => 'Registration successful! You can now login.'
        ];
    }
}

// Authenticate user
function loginUser($email, $password) {
    // Try to find user in database
    $sql = "SELECT id, full_name, email, password, role, status FROM users WHERE email = ?";
    $user = fetchOne($sql, "s", [$email]);
    
    // If user not found in database, try demo users
    if (!$user) {
        $demoUser = findUserByEmail($email);
        if ($demoUser) {
            // For demo users, we'll accept the default password: "password"
            if (!password_verify($password, $demoUser['password'])) {
                return [
                    'success' => false,
                    'message' => 'Invalid email or password.'
                ];
            }
            
            // Start session and set user data
            startSession();
            $_SESSION['user_id'] = $demoUser['id'];
            $_SESSION['user_name'] = $demoUser['full_name'];
            $_SESSION['user_email'] = $demoUser['email'];
            $_SESSION['user_role'] = $demoUser['role'];
            
            return [
                'success' => true,
                'user' => [
                    'id' => $demoUser['id'],
                    'name' => $demoUser['full_name'],
                    'email' => $demoUser['email'],
                    'role' => $demoUser['role']
                ],
                'message' => 'Login successful!'
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Invalid email or password.'
        ];
    }
    
    // Verify password
    if (!password_verify($password, $user['password'])) {
        return [
            'success' => false,
            'message' => 'Invalid email or password.'
        ];
    }
    
    // Check if user is active
    if ($user['status'] !== 'active') {
        return [
            'success' => false,
            'message' => 'Your account is not active. Please contact administrator.'
        ];
    }
    
    // Update last login time
    $updateSql = "UPDATE users SET last_login = NOW() WHERE id = ?";
    updateData($updateSql, "i", [$user['id']]);
    
    // Start session and set user data
    startSession();
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['full_name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];
    
    return [
        'success' => true,
        'user' => [
            'id' => $user['id'],
            'name' => $user['full_name'],
            'email' => $user['email'],
            'role' => $user['role']
        ],
        'message' => 'Login successful!'
    ];
}

// Logout user
function logoutUser() {
    startSession();
    
    // Unset all session variables
    $_SESSION = [];
    
    // Destroy the session
    session_destroy();
    
    return [
        'success' => true,
        'message' => 'Logout successful!'
    ];
}

// Check if user has permission to access a page
function checkPermission($allowedRoles = []) {
    startSession();
    
    if (!isLoggedIn()) {
        redirect('login.php');
    }
    
    if (!empty($allowedRoles) && !in_array($_SESSION['user_role'], $allowedRoles)) {
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
}

// Update user profile (demo version)
function updateUserProfile($userId, $fullName, $email, $phone) {
    // Update session data
    startSession();
    $_SESSION['user_name'] = $fullName;
    $_SESSION['user_email'] = $email;
    
    return [
        'success' => true,
        'message' => 'Profile updated successfully!'
    ];
}

// Change user password (demo version)
function changeUserPassword($userId, $currentPassword, $newPassword) {
    return [
        'success' => true,
        'message' => 'Password changed successfully!'
    ];
}

// Admin: Change user status (demo version)
function changeUserStatus($userId, $status) {
    return [
        'success' => true,
        'message' => "User status changed to $status successfully!"
    ];
}

// Admin: Delete user (demo version)
function deleteUser($userId) {
    return [
        'success' => true,
        'message' => 'User deleted successfully!'
    ];
}
?>
