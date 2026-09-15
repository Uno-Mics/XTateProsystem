<?php
/**
 * Migration Script: MySQL / SQL Dump -> Cloud Firestore & Firebase Auth
 * XTateProsystem
 */

require_once __DIR__ . '/inc/firebase.php';
require_once __DIR__ . '/inc/db.php';

echo "====================================================\n";
echo "Starting Migration to Cloud Firestore & Firebase...\n";
echo "====================================================\n\n";

$firestore = getFirestore();
$auth = getFirebaseAuth();
$conn = connectDB();

if (!$conn) {
    die("Error: Could not connect to local MySQL. Please ensure MySQL is running.\n");
}

// ----------------------------------------------------
// 1. Migrate Property Types
// ----------------------------------------------------
echo "Migrating Property Types...\n";
$ptResult = $conn->query("SELECT * FROM property_types");
$propertyTypesMap = [];
if ($ptResult) {
    while ($row = $ptResult->fetch_assoc()) {
        $id = (string)$row['id'];
        $propertyTypesMap[$id] = $row['name'];
        $firestore->collection('property_types')->document($id)->set([
            'id'          => (int)$row['id'],
            'name'        => $row['name'],
            'description' => $row['description'] ?? '',
            'created_at'  => $row['created_at'] ?? date('Y-m-d H:i:s')
        ]);
        echo "  [Property Type] ID {$id}: {$row['name']}\n";
    }
}

// ----------------------------------------------------
// 2. Migrate Users (Firestore & Firebase Auth)
// ----------------------------------------------------
echo "\nMigrating Users to Firestore and Firebase Auth...\n";
$usersResult = $conn->query("SELECT * FROM users");
$userUidMap = []; // Maps numeric MySQL user ID -> Firebase UID

if ($usersResult) {
    while ($row = $usersResult->fetch_assoc()) {
        $id = (string)$row['id'];
        $email = trim($row['email']);
        $fullName = $row['full_name'] ?? 'User';
        $role = $row['role'] ?? 'buyer';

        // 1. Sync or create Firebase Auth account
        $firebaseUid = null;
        try {
            $userRecord = $auth->getUserByEmail($email);
            $firebaseUid = $userRecord->uid;
            echo "  [Auth] Existing Firebase account found for {$email} (UID: {$firebaseUid})\n";
        } catch (\Kreait\Firebase\Exception\Auth\UserNotFound $e) {
            // Create Firebase Auth user with a default password (e.g. password123)
            try {
                $userProperties = [
                    'email'        => $email,
                    'emailVerified'=> true,
                    'displayName'  => $fullName,
                    'password'     => 'password123',
                    'disabled'     => ($row['status'] ?? 'active') === 'inactive'
                ];
                $createdUser = $auth->createUser($userProperties);
                $firebaseUid = $createdUser->uid;
                echo "  [Auth] Created new Firebase Auth user for {$email} (Password: password123, UID: {$firebaseUid})\n";
            } catch (Exception $ce) {
                echo "  [Auth Warning] Could not create Firebase Auth user for {$email}: " . $ce->getMessage() . "\n";
            }
        }

        $userUidMap[$id] = $firebaseUid ?? $id;

        // 2. Store in Firestore users collection
        $userData = [
            'id'           => (int)$row['id'],
            'firebase_uid' => $firebaseUid,
            'full_name'    => $fullName,
            'email'        => $email,
            'phone'        => $row['phone'] ?? '',
            'role'         => $role,
            'status'       => $row['status'] ?? 'active',
            'company_name' => $row['company_name'] ?? '',
            'bio'          => $row['bio'] ?? '',
            'address'      => $row['address'] ?? '',
            'city'         => $row['city'] ?? '',
            'state'        => $row['state'] ?? '',
            'zip_code'     => $row['zip_code'] ?? '',
            'created_at'   => $row['created_at'] ?? date('Y-m-d H:i:s'),
            'updated_at'   => $row['updated_at'] ?? date('Y-m-d H:i:s')
        ];

        // Store by numeric ID so existing lookup references work directly
        $firestore->collection('users')->document($id)->set($userData);
        if ($firebaseUid) {
            // Also store/link by firebase UID for client auth
            $firestore->collection('users_by_uid')->document($firebaseUid)->set($userData);
        }
        echo "  [Firestore] Synced User ID {$id}: {$fullName} ({$email})\n";
    }
}

// ----------------------------------------------------
// 3. Migrate Properties & Property Images
// ----------------------------------------------------
echo "\nMigrating Properties & Images...\n";
$propsResult = $conn->query("SELECT * FROM properties");

if ($propsResult) {
    while ($p = $propsResult->fetch_assoc()) {
        $pId = (string)$p['id'];

        // Get images for this property
        $images = [];
        $imgResult = $conn->query("SELECT * FROM property_images WHERE property_id = " . (int)$pId . " ORDER BY is_primary DESC");
        if ($imgResult) {
            while ($img = $imgResult->fetch_assoc()) {
                $images[] = [
                    'id'         => (int)$img['id'],
                    'image_path' => $img['image_path'],
                    'image_url'  => $img['image_path'],
                    'is_primary' => (bool)$img['is_primary']
                ];
            }
        }

        $propertyType = $propertyTypesMap[(string)$p['property_type_id']] ?? 'Residential';

        $areaVal = (float)($p['area'] ?? $p['area_sqft'] ?? 0);
        $cleanDesc = $p['description'] ?? '';
        if (!empty($cleanDesc)) {
            while (strpos($cleanDesc, '&amp;') !== false || strpos($cleanDesc, '&#') !== false) {
                $cleanDesc = html_entity_decode($cleanDesc, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
        }

        $amenities = [
            'garage'           => (int)($p['garage'] ?? 0),
            'air_conditioning' => (int)($p['air_conditioning'] ?? 0),
            'swimming_pool'    => (int)($p['swimming_pool'] ?? 0),
            'backyard'         => (int)($p['backyard'] ?? 0),
            'gym'              => (int)($p['gym'] ?? 0),
            'fireplace'        => (int)($p['fireplace'] ?? 0),
            'security_system'  => (int)($p['security_system'] ?? 0),
            'washer_dryer'     => (int)($p['washer_dryer'] ?? 0),
        ];

        $propData = [
            'id'               => (int)$p['id'],
            'seller_id'        => (int)$p['seller_id'],
            'property_type_id' => (int)$p['property_type_id'],
            'type_name'        => $propertyType,
            'property_type'    => $propertyType,
            'property_type_name' => $propertyType,
            'title'            => $p['title'],
            'description'      => $cleanDesc,
            'price'            => (float)$p['price'],
            'address'          => $p['address'] ?? '',
            'city'             => $p['city'] ?? '',
            'state'            => $p['state'] ?? '',
            'zip_code'         => $p['zip_code'] ?? '',
            'bedrooms'         => (int)($p['bedrooms'] ?? 0),
            'bathrooms'        => (float)($p['bathrooms'] ?? 0),
            'area'             => $areaVal,
            'area_sqft'        => $areaVal,
            'sqft'             => $areaVal,
            'year_built'       => (!empty($p['year_built']) && $p['year_built'] !== 'N/A') ? (int)$p['year_built'] : 'N/A',
            'status'           => $p['status'] ?? 'pending',
            'is_featured'      => (bool)($p['is_featured'] ?? 0),
            'garage'           => $amenities['garage'],
            'air_conditioning' => $amenities['air_conditioning'],
            'swimming_pool'    => $amenities['swimming_pool'],
            'backyard'         => $amenities['backyard'],
            'gym'              => $amenities['gym'],
            'fireplace'        => $amenities['fireplace'],
            'security_system'  => $amenities['security_system'],
            'washer_dryer'     => $amenities['washer_dryer'],
            'amenities'        => $amenities,
            'featured_image'   => !empty($images) ? $images[0]['image_url'] : null,
            'primary_image'    => !empty($images) ? $images[0]['image_url'] : null,
            'images'           => $images,
            'created_at'       => $p['created_at'] ?? date('Y-m-d H:i:s'),
            'updated_at'       => $p['updated_at'] ?? date('Y-m-d H:i:s')
        ];

        $firestore->collection('properties')->document($pId)->set($propData);
        echo "  [Property] Synced #{$pId}: {$p['title']} ({$p['city']}) with " . count($images) . " images\n";
    }
}

// ----------------------------------------------------
// 4. Migrate Inquiries, Favorites & Messages
// ----------------------------------------------------
echo "\nMigrating Inquiries...\n";
$inqResult = $conn->query("SELECT * FROM inquiries");
if ($inqResult) {
    while ($inq = $inqResult->fetch_assoc()) {
        $firestore->collection('inquiries')->document((string)$inq['id'])->set([
            'id'          => (int)$inq['id'],
            'property_id' => (int)$inq['property_id'],
            'buyer_id'    => (int)$inq['buyer_id'],
            'message'     => $inq['message'] ?? '',
            'status'      => $inq['status'] ?? 'pending',
            'created_at'  => $inq['created_at'] ?? date('Y-m-d H:i:s'),
            'updated_at'  => $inq['updated_at'] ?? date('Y-m-d H:i:s')
        ]);
    }
    echo "  Synced inquiries.\n";
}

echo "Migrating Favorites...\n";
$favResult = $conn->query("SELECT * FROM favorites");
if ($favResult) {
    while ($fav = $favResult->fetch_assoc()) {
        $firestore->collection('favorites')->document((string)$fav['id'])->set([
            'id'          => (int)$fav['id'],
            'buyer_id'    => (int)$fav['buyer_id'],
            'property_id' => (int)$fav['property_id'],
            'created_at'  => $fav['created_at'] ?? date('Y-m-d H:i:s')
        ]);
    }
    echo "  Synced favorites.\n";
}

echo "Migrating Messages...\n";
$msgResult = $conn->query("SELECT * FROM messages");
if ($msgResult) {
    while ($msg = $msgResult->fetch_assoc()) {
        $firestore->collection('messages')->document((string)$msg['id'])->set([
            'id'          => (int)$msg['id'],
            'sender_id'   => (int)$msg['sender_id'],
            'receiver_id' => (int)$msg['receiver_id'],
            'property_id' => isset($msg['property_id']) ? (int)$msg['property_id'] : null,
            'message'     => $msg['message'],
            'is_read'     => (bool)$msg['is_read'],
            'created_at'  => $msg['created_at'] ?? date('Y-m-d H:i:s')
        ]);
    }
    echo "  Synced messages.\n";
}

closeDB($conn);

echo "\n====================================================\n";
echo ">>> All MySQL data successfully migrated to Firebase! <<<\n";
echo "====================================================\n";
