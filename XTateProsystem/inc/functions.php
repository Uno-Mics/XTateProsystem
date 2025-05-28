<?php
/**
 * Real Estate Listing System
 * Helper Functions
 */

// Format currency
function formatCurrency($amount) {
    return '$' . number_format($amount, 2);
}

// Format date and time
function formatDateTime($dateTime, $format = 'M d, Y h:i A') {
    return date($format, strtotime($dateTime));
}

// Get property types
function getPropertyTypes() {
    // Demo property types
    return [
        ['id' => 1, 'name' => 'Single Family Home'],
        ['id' => 2, 'name' => 'Apartment'],
        ['id' => 3, 'name' => 'Condo'],
        ['id' => 4, 'name' => 'Townhouse'],
        ['id' => 5, 'name' => 'Land'],
        ['id' => 6, 'name' => 'Commercial']
    ];
}

// Get locations (cities and states)
function getLocations() {
    // Demo locations
    return [
        ['city' => 'New York', 'state' => 'NY'],
        ['city' => 'Los Angeles', 'state' => 'CA'],
        ['city' => 'Chicago', 'state' => 'IL'],
        ['city' => 'Houston', 'state' => 'TX'],
        ['city' => 'Miami', 'state' => 'FL'],
        ['city' => 'San Francisco', 'state' => 'CA']
    ];
}

// Format inquiry status
function formatInquiryStatus($status) {
    $statusClass = '';
    $statusText = ucfirst($status);
    
    switch ($status) {
        case 'pending':
            $statusClass = 'bg-warning';
            break;
        case 'approved':
            $statusClass = 'bg-success';
            break;
        case 'rejected':
            $statusClass = 'bg-danger';
            break;
        default:
            $statusClass = 'bg-secondary';
    }
    
    return '<span class="badge ' . $statusClass . '">' . $statusText . '</span>';
}

// Get all inquiries for a seller
function getSellerInquiries($sellerId) {
    // Demo inquiries for seller
    return [
        [
            'id' => 1,
            'property_id' => 1,
            'buyer_id' => 2,
            'message' => 'I\'m interested in this property. Is it still available?',
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
            'updated_at' => date('Y-m-d H:i:s', strtotime('-2 days'))
        ],
        [
            'id' => 2,
            'property_id' => 3,
            'buyer_id' => 3,
            'message' => 'Can I schedule a viewing this weekend?',
            'status' => 'approved',
            'created_at' => date('Y-m-d H:i:s', strtotime('-5 days')),
            'updated_at' => date('Y-m-d H:i:s', strtotime('-4 days'))
        ]
    ];
}

// Get property by ID
function getPropertyById($id) {
    // Demo properties and additional data as fallback
    $properties = getDemoProperties();
    $demoProperty = null;
    
    // Find the matching property in the demo data
    foreach ($properties as $property) {
        if ($property['id'] == $id) {
            $demoProperty = $property;
            // Add seller information
            $demoProperty['seller_name'] = 'John Seller';
            $demoProperty['seller_email'] = 'seller@example.com';
            $demoProperty['seller_phone'] = '555-123-4567';
            $demoProperty['property_type_name'] = $property['property_type'];
            break;
        }
    }
    
    // Check if database and tables exist
    try {
        $conn = connectDB();
        if (!$conn) {
            return $demoProperty; // Database connection failed, use demo data
        }
        
        // Check if tables exist
        $tableCheck = $conn->query("SHOW TABLES LIKE 'properties'");
        if (!$tableCheck || $tableCheck->num_rows == 0) {
            closeDB($conn);
            return $demoProperty; // Table doesn't exist yet, use demo data
        }
        closeDB($conn);
    } catch (Exception $e) {
        return $demoProperty; // Any error, use demo data
    }
    
    // Try to get the property from the database using fetchOne
    $sql = "SELECT p.*, pt.name as property_type_name, 
            u.full_name as seller_name, u.email as seller_email, u.phone as seller_phone,
            (SELECT image_path FROM property_images WHERE property_id = p.id AND is_primary = 1 LIMIT 1) as primary_image
            FROM properties p
            JOIN property_types pt ON p.property_type_id = pt.id
            JOIN users u ON p.seller_id = u.id
            WHERE p.id = ?";
    
    $property = fetchOne($sql, "i", [$id]);
    
    // If found in database, return it; otherwise return demo property
    return $property ? $property : $demoProperty;
}

// Get property images
function getPropertyImages($propertyId) {
    // Demo images fallback
    $demoImages = [
        [
            'id' => 1,
            'property_id' => $propertyId,
            'image_path' => 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267',
            'is_primary' => 1
        ],
        [
            'id' => 2,
            'property_id' => $propertyId,
            'image_path' => 'https://images.unsplash.com/photo-1574362848149-11496d93a7c7',
            'is_primary' => 0
        ]
    ];
    
    // Check if database and tables exist
    try {
        $conn = connectDB();
        if (!$conn) {
            return $demoImages; // Database connection failed, use demo images
        }
        
        // Check if table exists
        $tableCheck = $conn->query("SHOW TABLES LIKE 'property_images'");
        if (!$tableCheck || $tableCheck->num_rows == 0) {
            closeDB($conn);
            return $demoImages; // Table doesn't exist yet, use demo images
        }
        closeDB($conn);
    } catch (Exception $e) {
        return $demoImages; // Any error, use demo images
    }
    
    // Get all images for a property using fetchAll - query directly with SQL to see raw data
    $sql = "SELECT * FROM property_images WHERE property_id = ? ORDER BY is_primary DESC";
    
    // ***IMPORTANT: DIRECT SQL QUERY FOR DEBUGGING - NO FORMATTING***
    try {
        $conn = connectDB();
        if ($conn) {
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("i", $propertyId);
                $stmt->execute();
                $result = $stmt->get_result();
                $rawImages = [];
                
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $rawImages[] = $row;
                    }
                }
                
                $stmt->close();
            }
            closeDB($conn);
            
            // If found raw images, use these with minimal modification
            if (!empty($rawImages)) {
                // Just ensure correct URL format
                foreach ($rawImages as &$img) {
                    // Convert from database format to browser accessible URL
                    if (isset($img['image_path']) && substr($img['image_path'], 0, 4) !== 'http') {
                        // First, save the original path for debugging
                        $img['original_path'] = $img['image_path'];
                        
                        // Get just the filename from the path
                        $filename = basename($img['image_path']);
                        
                        // Web-accessible path
                        $img['image_path'] = 'uploads/properties/' . $filename;
                    }
                }
                return $rawImages;
            }
        }
    } catch (Exception $e) {
        // Will fall back to normal fetchAll below
    }
    
    // Regular fetchAll as backup method
    $images = fetchAll($sql, "i", [$propertyId]);
    
    // If no images found in database, use demo images
    if (empty($images)) {
        return $demoImages;
    }
    
    // Fix image paths to ensure they're web-accessible
    foreach ($images as &$image) {
        if (isset($image['image_path'])) {
            // Save original path for reference
            $image['original_path'] = $image['image_path'];
            
            // Get just the filename from the path
            $filename = basename($image['image_path']);
            
            // Use direct web path that browsers can access
            $image['image_path'] = 'uploads/properties/' . $filename;
        }
    }
    
    return $images;
}

// Generate a random string
function generateRandomString($length = 32) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

// Verify password against stored hash
function verifyPassword($password, $hashedPassword) {
    // If this is demo data, just check against "password"
    if (empty($hashedPassword)) {
        return $password === 'password';
    }
    return password_verify($password, $hashedPassword);
}

// Generate a consistent color for user avatars
function generateAvatarColor($userId) {
    $colors = [
        '#3498db', // Blue
        '#e74c3c', // Red
        '#2ecc71', // Green
        '#f39c12', // Yellow
        '#9b59b6', // Purple
        '#1abc9c', // Turquoise
        '#d35400', // Orange
        '#34495e'  // Dark Blue
    ];
    
    // Ensure the same user always gets the same color
    $colorIndex = $userId % count($colors);
    return $colors[$colorIndex];
}

// Get inquiry by ID
function getInquiryById($inquiryId) {
    // Check if inquiries table exists first
    try {
        $conn = connectDB();
        if (!$conn) {
            return null; // Database connection failed
        }
        
        $tableCheck = $conn->query("SHOW TABLES LIKE 'inquiries'");
        if (!$tableCheck || $tableCheck->num_rows == 0) {
            closeDB($conn);
            return null; // Table doesn't exist yet
        }
        closeDB($conn);
    } catch (Exception $e) {
        return null; // Any error, just return null
    }
    
    // Get inquiry using fetchOne function
    $sql = "SELECT i.*, p.title as property_title
            FROM inquiries i
            JOIN properties p ON i.property_id = p.id
            WHERE i.id = ?";
    
    $inquiry = fetchOne($sql, "i", [$inquiryId]);
    
    // If not found in database, use demo data
    if (!$inquiry) {
        // Create a demo inquiry object
        $demoInquiries = [
            1 => [
                'id' => 1,
                'property_id' => 1,
                'buyer_id' => 3,
                'message' => 'I am interested in viewing this property. Is it still available?',
                'status' => 'pending',
                'property_title' => 'Modern Apartment with City View',
                'buyer_name' => 'Jane Buyer',
                'buyer_email' => 'buyer@example.com',
                'created_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
                'updated_at' => date('Y-m-d H:i:s', strtotime('-2 days'))
            ],
            2 => [
                'id' => 2,
                'property_id' => 1,
                'buyer_id' => 4,
                'message' => 'Can I schedule a viewing for this weekend?',
                'status' => 'rejected',
                'property_title' => 'Modern Apartment with City View',
                'buyer_name' => 'Sara Williams',
                'buyer_email' => 'sara@example.com',
                'created_at' => date('Y-m-d H:i:s', strtotime('-3 days')),
                'updated_at' => date('Y-m-d H:i:s', strtotime('-1 day'))
            ],
            3 => [
                'id' => 3,
                'property_id' => 2,
                'buyer_id' => 3,
                'message' => 'Is this property still on the market? I would like to make an offer.',
                'status' => 'pending',
                'property_title' => 'Spacious Family Home with Garden',
                'buyer_name' => 'Jane Buyer',
                'buyer_email' => 'buyer@example.com',
                'created_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
                'updated_at' => date('Y-m-d H:i:s', strtotime('-1 day'))
            ]
        ];
        
        return isset($demoInquiries[$inquiryId]) ? $demoInquiries[$inquiryId] : null;
    }
    
    return $inquiry;
}

// Get unread messages count
function getUnreadMessagesCount($userId) {
    // Use the database functions from db.php instead of global $conn
    // This ensures connection is properly handled
    
    // First check if the messages table exists
    try {
        $conn = connectDB();
        if (!$conn) {
            return 0; // Database connection failed, return 0 as a safe default
        }
        
        $tableCheck = $conn->query("SHOW TABLES LIKE 'messages'");
        if (!$tableCheck || $tableCheck->num_rows == 0) {
            closeDB($conn);
            return 0; // Table doesn't exist yet
        }
        closeDB($conn);
    } catch (Exception $e) {
        return 0; // Any error, just return 0
    }
    
    // Now use the fetchOne function to get the count
    $sql = "SELECT COUNT(*) as unread_count FROM messages WHERE receiver_id = ? AND is_read = 0";
    $result = fetchOne($sql, "i", [$userId]);
    
    if ($result && isset($result['unread_count'])) {
        return $result['unread_count'];
    }
    
    return 0;
}

// Format message time
function formatMessageTime($dateTime) {
    $timestamp = strtotime($dateTime);
    $now = time();
    $diff = $now - $timestamp;
    
    // Less than 1 minute
    if ($diff < 60) {
        return "Just now";
    }
    
    // Less than 1 hour
    if ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . " min" . ($minutes > 1 ? "s" : "") . " ago";
    }
    
    // Less than 24 hours
    if ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . " hour" . ($hours > 1 ? "s" : "") . " ago";
    }
    
    // Less than 7 days
    if ($diff < 604800) {
        $days = floor($diff / 86400);
        if ($days == 1) {
            return "Yesterday at " . date("g:i A", $timestamp);
        }
        return date("l", $timestamp) . " at " . date("g:i A", $timestamp);
    }
    
    // More than 7 days
    return date("M j, Y", $timestamp) . " at " . date("g:i A", $timestamp);
}

/**
 * Check if a property is favorited by a user
 * 
 * @param int $propertyId The property ID
 * @param int $userId The user ID
 * @return bool True if favorited, false otherwise
 */
function isPropertyFavorited($propertyId, $userId) {
    // Check both possible table names to ensure compatibility
    try {
        $conn = connectDB();
        if (!$conn) {
            return false; // Database connection failed
        }
        
        // First prioritize checking the favorites table (the one we see in phpMyAdmin)
        $tableCheck = $conn->query("SHOW TABLES LIKE 'favorites'");
        if ($tableCheck && $tableCheck->num_rows > 0) {
            // Try favorites table (the actual table name we see in database)
            try {
                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM favorites WHERE property_id = ? AND buyer_id = ?");
                $stmt->bind_param("ii", $propertyId, $userId);
                $stmt->execute();
                $result = $stmt->get_result();
                $data = $result->fetch_assoc();
                $count = $data['count'];
                $stmt->close();
                
                if ($count > 0) {
                    closeDB($conn);
                    error_log("Property $propertyId is favorited by user $userId in 'favorites' table");
                    return true;
                }
            } catch (Exception $e) {
                error_log("Error checking favorites table: " . $e->getMessage());
                // Continue to check other table
            }
        }

        // Then check if buyer_favorites table exists as a fallback
        $tableCheck = $conn->query("SHOW TABLES LIKE 'buyer_favorites'");
        if ($tableCheck && $tableCheck->num_rows > 0) {
            // Try buyer_favorites table
            try {
                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM buyer_favorites WHERE property_id = ? AND buyer_id = ?");
                $stmt->bind_param("ii", $propertyId, $userId);
                $stmt->execute();
                $result = $stmt->get_result();
                $data = $result->fetch_assoc();
                $count = $data['count'];
                $stmt->close();
                
                if ($count > 0) {
                    closeDB($conn);
                    error_log("Property $propertyId is favorited by user $userId in 'buyer_favorites' table");
                    return true;
                }
            } catch (Exception $e) {
                error_log("Error checking buyer_favorites table: " . $e->getMessage());
            }
        }
        
        closeDB($conn);
        return false;
    } catch (Exception $e) {
        // For debugging
        error_log("Error checking favorites: " . $e->getMessage());
        return false;
    }
}

// Mark messages as read
function markMessagesAsRead($partnerId) {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    $userId = $_SESSION['user_id'];
    
    // Check if messages table exists first
    try {
        $conn = connectDB();
        if (!$conn) {
            return false; // Database connection failed
        }
        
        $tableCheck = $conn->query("SHOW TABLES LIKE 'messages'");
        if (!$tableCheck || $tableCheck->num_rows == 0) {
            closeDB($conn);
            return false; // Table doesn't exist yet
        }
        closeDB($conn);
    } catch (Exception $e) {
        return false; // Any error, just return false
    }
    
    // Update messages using updateData function
    $sql = "UPDATE messages SET is_read = 1 
            WHERE sender_id = ? AND receiver_id = ? AND is_read = 0";
    
    $result = updateData($sql, "ii", [$partnerId, $userId]);
    return $result > 0;
}

// This is a placeholder comment since formatDateTime is already declared at the top of the file

// Get conversation partners
function getConversationPartners($userId) {
    // Check if messages table exists first
    try {
        $conn = connectDB();
        if (!$conn) {
            return []; // Database connection failed
        }
        
        $tableCheck = $conn->query("SHOW TABLES LIKE 'messages'");
        if (!$tableCheck || $tableCheck->num_rows == 0) {
            closeDB($conn);
            return []; // Table doesn't exist yet
        }
        closeDB($conn);
    } catch (Exception $e) {
        return []; // Any error, just return empty array
    }
    
    // Get distinct conversation partners using fetchAll
    $sql = "SELECT DISTINCT 
            CASE 
                WHEN m.sender_id = ? THEN m.receiver_id 
                ELSE m.sender_id 
            END as partner_id
            FROM messages m
            WHERE m.sender_id = ? OR m.receiver_id = ?
            ORDER BY (SELECT MAX(created_at) FROM messages 
                    WHERE (sender_id = ? AND receiver_id = partner_id) 
                    OR (sender_id = partner_id AND receiver_id = ?)) DESC";
    
    $partners = fetchAll($sql, "iiiii", [$userId, $userId, $userId, $userId, $userId]);
    return $partners;
}

// Get favorited properties for a buyer
function getBuyerFavorites($buyerId) {
    // Demo favorites as fallback
    $demoFavorites = [1, 3, 5];
    $tableToUse = null;
    
    // First check which table exists - favorites or buyer_favorites
    try {
        $conn = connectDB();
        if (!$conn) {
            return $demoFavorites; // Database connection failed
        }
        
        // First check if favorites table exists (the one we see in phpMyAdmin)
        $tableCheck = $conn->query("SHOW TABLES LIKE 'favorites'");
        if ($tableCheck && $tableCheck->num_rows > 0) {
            $tableToUse = 'favorites';
            error_log("Using 'favorites' table to get buyer favorites");
        } else {
            // Then check if buyer_favorites exists as backup
            $tableCheck = $conn->query("SHOW TABLES LIKE 'buyer_favorites'");
            if ($tableCheck && $tableCheck->num_rows > 0) {
                $tableToUse = 'buyer_favorites';
                error_log("Using 'buyer_favorites' table to get buyer favorites");
            }
        }
        
        if (!$tableToUse) {
            closeDB($conn);
            return $demoFavorites; // No table exists yet
        }
        
        closeDB($conn);
    } catch (Exception $e) {
        error_log("Error checking favorites tables: " . $e->getMessage());
        return $demoFavorites; // Any error, return demo favorites
    }
    
    // Get favorited property IDs using fetchAll
    $sql = "SELECT property_id FROM {$tableToUse} WHERE buyer_id = ?";
    try {
        $result = fetchAll($sql, "i", [$buyerId]);
        
        if (empty($result)) {
            return $demoFavorites;
        }
        
        // Extract just the property IDs
        $favorites = [];
        foreach ($result as $row) {
            $favorites[] = $row['property_id'];
        }
        
        return $favorites;
    } catch (Exception $e) {
        error_log("Error fetching favorites: " . $e->getMessage());
        return $demoFavorites;
    }
}

// This function was replaced by a more comprehensive version above

// Get demo favorite properties for buyer
function getDemoFavoriteProperties($buyerId) {
    // Get all demo properties
    $demoProperties = getDemoProperties();
    
    // Get buyer's favorite property IDs (demo data)
    $demoFavorites = [1, 3, 5]; // Default demo favorites
    
    // Filter properties that are in favorites
    $favorites = [];
    foreach ($demoProperties as $property) {
        if (in_array($property['id'], $demoFavorites)) {
            // Add favorited_at field
            $property['favorited_at'] = date('Y-m-d H:i:s', strtotime('-' . rand(1, 30) . ' days'));
            // Add seller information
            $property['seller_name'] = 'John Seller';
            $property['seller_email'] = 'seller@example.com';
            $property['seller_phone'] = '555-123-4567';
            $favorites[] = $property;
        }
    }
    
    // Sort by favorited_at in descending order
    usort($favorites, function($a, $b) {
        return strtotime($b['favorited_at']) - strtotime($a['favorited_at']);
    });
    
    return $favorites;
}

// Generate demo property data
function getDemoProperties() {
    return [
        [
            'id' => 1,
            'seller_id' => 1,
            'title' => 'Modern Apartment with City View',
            'description' => 'Stunning modern apartment with panoramic city views. Features include hardwood floors, stainless steel appliances, and a spacious balcony.',
            'price' => 450000,
            'property_type_id' => 2,
            'property_type' => 'Apartment',
            'bedrooms' => 2,
            'bathrooms' => 2,
            'area' => 1200,
            'address' => '123 Main St, Apt 7B',
            'city' => 'New York',
            'state' => 'NY',
            'zip_code' => '10001',
            'year_built' => 2015,
            'garage' => 1,
            'air_conditioning' => 1,
            'swimming_pool' => 0,
            'backyard' => 0,
            'gym' => 1,
            'fireplace' => 0,
            'security_system' => 1,
            'washer_dryer' => 1,
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s', strtotime('-10 days')),
            'updated_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
            'primary_image' => 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267'
        ],
        [
            'id' => 2,
            'seller_id' => 1,
            'title' => 'Spacious Family Home with Garden',
            'description' => 'Beautiful family home with a large garden, perfect for entertaining. Features 4 bedrooms, renovated kitchen, and a two-car garage.',
            'price' => 750000,
            'property_type_id' => 1,
            'property_type' => 'Single Family Home',
            'bedrooms' => 4,
            'bathrooms' => 3,
            'area' => 2500,
            'address' => '456 Oak Avenue',
            'city' => 'Los Angeles',
            'state' => 'CA',
            'zip_code' => '90001',
            'year_built' => 2005,
            'garage' => 2,
            'air_conditioning' => 1,
            'swimming_pool' => 1,
            'backyard' => 1,
            'gym' => 0,
            'fireplace' => 1,
            'security_system' => 1,
            'washer_dryer' => 1,
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s', strtotime('-15 days')),
            'updated_at' => date('Y-m-d H:i:s', strtotime('-3 days')),
            'primary_image' => 'https://images.unsplash.com/photo-1583608205776-bfd35f0d9f83'
        ],
        [
            'id' => 3,
            'seller_id' => 2,
            'title' => 'Luxury Condo in Downtown',
            'description' => 'Luxury condo in the heart of downtown. Walking distance to restaurants, shops, and entertainment. Features high-end finishes and amenities.',
            'price' => 550000,
            'property_type_id' => 3,
            'property_type' => 'Condo',
            'bedrooms' => 2,
            'bathrooms' => 2,
            'area' => 1500,
            'address' => '789 Market Street, Unit 12D',
            'city' => 'San Francisco',
            'state' => 'CA',
            'zip_code' => '94103',
            'year_built' => 2018,
            'garage' => 1,
            'air_conditioning' => 1,
            'swimming_pool' => 1,
            'backyard' => 0,
            'gym' => 1,
            'fireplace' => 0,
            'security_system' => 1,
            'washer_dryer' => 1,
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s', strtotime('-8 days')),
            'updated_at' => date('Y-m-d H:i:s', strtotime('-1 days')),
            'primary_image' => 'https://images.unsplash.com/photo-1512917774080-9991f1c4c750'
        ],
        [
            'id' => 4,
            'seller_id' => 2,
            'title' => 'Charming Townhouse Near Park',
            'description' => 'Charming townhouse located near a beautiful park. Features include an updated kitchen, hardwood floors, and a private patio.',
            'price' => 410000,
            'property_type_id' => 4,
            'property_type' => 'Townhouse',
            'bedrooms' => 3,
            'bathrooms' => 2,
            'area' => 1800,
            'address' => '321 Park Lane',
            'city' => 'Chicago',
            'state' => 'IL',
            'zip_code' => '60601',
            'year_built' => 2009,
            'garage' => 1,
            'air_conditioning' => 1,
            'swimming_pool' => 0,
            'backyard' => 1,
            'gym' => 0,
            'fireplace' => 1,
            'security_system' => 1,
            'washer_dryer' => 1,
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s', strtotime('-20 days')),
            'updated_at' => date('Y-m-d H:i:s', strtotime('-5 days')),
            'primary_image' => 'https://images.unsplash.com/photo-1576941089067-2de3c901e126'
        ],
        [
            'id' => 5,
            'seller_id' => 3,
            'title' => 'Waterfront Home with Private Dock',
            'description' => 'Stunning waterfront home with a private dock. Enjoy breathtaking views and direct water access for boating and fishing enthusiasts.',
            'price' => 1200000,
            'property_type_id' => 1,
            'property_type' => 'Single Family Home',
            'bedrooms' => 5,
            'bathrooms' => 4,
            'area' => 3500,
            'address' => '555 Ocean Drive',
            'city' => 'Miami',
            'state' => 'FL',
            'zip_code' => '33101',
            'year_built' => 2012,
            'garage' => 2,
            'air_conditioning' => 1,
            'swimming_pool' => 1,
            'backyard' => 1,
            'gym' => 1,
            'fireplace' => 1,
            'security_system' => 1,
            'washer_dryer' => 1,
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s', strtotime('-12 days')),
            'updated_at' => date('Y-m-d H:i:s', strtotime('-1 days')),
            'primary_image' => 'https://images.unsplash.com/photo-1523217582562-09d0def993a6'
        ],
        [
            'id' => 6,
            'seller_id' => 3,
            'title' => 'Modern Loft in Art District',
            'description' => 'Stylish modern loft in the vibrant Art District. Features high ceilings, exposed brick walls, and large windows that flood the space with natural light.',
            'price' => 385000,
            'property_type_id' => 2,
            'property_type' => 'Apartment',
            'bedrooms' => 1,
            'bathrooms' => 1,
            'area' => 1100,
            'address' => '888 Gallery Way, Loft 3C',
            'city' => 'Los Angeles',
            'state' => 'CA',
            'zip_code' => '90013',
            'year_built' => 2010,
            'garage' => 1,
            'air_conditioning' => 1,
            'swimming_pool' => 0,
            'backyard' => 0,
            'gym' => 0,
            'fireplace' => 0,
            'security_system' => 1,
            'washer_dryer' => 1,
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s', strtotime('-18 days')),
            'updated_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
            'primary_image' => 'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688'
        ]
    ];
}