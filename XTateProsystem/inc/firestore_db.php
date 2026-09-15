<?php
/**
 * Real Estate Listing System
 * Cloud Firestore Data Access Layer for XTateProsystem
 */

require_once __DIR__ . '/firebase.php';

/**
 * Fetch all properties from Firestore
 */
function firestore_get_properties($filters = []) {
    $firestore = getFirestore();
    $collection = $firestore->collection('properties');
    
    $query = $collection;
    
    // Status filter (pass null, '' or 'all' to fetch all statuses)
    $statusFilter = $filters['status'] ?? 'active';
    if (!empty($statusFilter) && $statusFilter !== 'all') {
        $query = $query->where('status', '=', $statusFilter);
    }

    if (!empty($filters['property_type_id'])) {
        $query = $query->where('property_type_id', '=', (int)$filters['property_type_id']);
    }

    if (!empty($filters['seller_id'])) {
        $query = $query->where('seller_id', '=', (int)$filters['seller_id']);
    }

    $documents = $query->documents();
    $results = [];

    foreach ($documents as $doc) {
        if ($doc->exists()) {
            $data = $doc->data();
            $data['id'] = (int)($data['id'] ?? $doc->id());
            
            // Client-side filtering for search keywords/locations/prices if needed
            if (!empty($filters['location'])) {
                $loc = strtolower($filters['location']);
                $city = strtolower($data['city'] ?? '');
                $state = strtolower($data['state'] ?? '');
                $address = strtolower($data['address'] ?? '');
                if (strpos($city, $loc) === false && strpos($state, $loc) === false && strpos($address, $loc) === false) {
                    continue;
                }
            }

            if (!empty($filters['price_min']) && ($data['price'] ?? 0) < $filters['price_min']) {
                continue;
            }

            if (!empty($filters['price_max']) && ($data['price'] ?? 0) > $filters['price_max']) {
                continue;
            }

            if (!empty($filters['bedrooms']) && ($data['bedrooms'] ?? 0) < $filters['bedrooms']) {
                continue;
            }

            if (!empty($filters['bathrooms']) && ($data['bathrooms'] ?? 0) < $filters['bathrooms']) {
                continue;
            }

            if (!empty($filters['keyword'])) {
                $kw = strtolower($filters['keyword']);
                $title = strtolower($data['title'] ?? '');
                $desc = strtolower($data['description'] ?? '');
                if (strpos($title, $kw) === false && strpos($desc, $kw) === false) {
                    continue;
                }
            }

            // Normalize property data
            $data = firestore_normalize_property($data);

            // Auto-enrich from MySQL if area is 0 or missing
            if ($data['area'] <= 0 && function_exists('connectDB')) {
                $synced = firestore_sync_property($data['id']);
                if ($synced && is_array($synced)) {
                    $data = firestore_normalize_property($synced);
                }
            }

            $results[] = $data;
        }
    }

    // Sort results
    $sort = $filters['sort'] ?? 'newest';
    usort($results, function($a, $b) use ($sort) {
        if ($sort === 'price_low') {
            return ($a['price'] ?? 0) <=> ($b['price'] ?? 0);
        } elseif ($sort === 'price_high') {
            return ($b['price'] ?? 0) <=> ($a['price'] ?? 0);
        }
        return strtotime($b['created_at'] ?? 'now') <=> strtotime($a['created_at'] ?? 'now');
    });

    return $results;
}

/**
 * Normalize property data structure ensuring area, specs, and amenities consistency
 */
function firestore_normalize_property($data) {
    if (!is_array($data)) {
        return $data;
    }

    // Standardize area resolution
    $area = 0;
    if (isset($data['area']) && (float)$data['area'] > 0) {
        $area = (float)$data['area'];
    } elseif (isset($data['area_sqft']) && (float)$data['area_sqft'] > 0) {
        $area = (float)$data['area_sqft'];
    } elseif (isset($data['sqft']) && (float)$data['sqft'] > 0) {
        $area = (float)$data['sqft'];
    }
    $data['area'] = $area;
    $data['area_sqft'] = $area;
    $data['sqft'] = $area;

    // Normalize Year Built
    if (!isset($data['year_built']) || empty($data['year_built']) || $data['year_built'] === 0 || $data['year_built'] === '0') {
        $data['year_built'] = 'N/A';
    }

    // Normalize Amenities
    $amenityKeys = [
        'garage', 'air_conditioning', 'swimming_pool', 'backyard',
        'gym', 'fireplace', 'security_system', 'washer_dryer'
    ];
    $amenitiesMap = is_array($data['amenities'] ?? null) ? $data['amenities'] : [];
    foreach ($amenityKeys as $key) {
        if (isset($data[$key])) {
            $val = (int)$data[$key];
        } elseif (isset($amenitiesMap[$key])) {
            $val = (int)$amenitiesMap[$key];
        } else {
            $val = 0;
        }
        $data[$key] = $val;
        $amenitiesMap[$key] = $val;
    }
    $data['amenities'] = $amenitiesMap;

    // Ensure primary_image compatibility
    if (empty($data['primary_image'])) {
        if (!empty($data['featured_image'])) {
            $data['primary_image'] = $data['featured_image'];
        } elseif (!empty($data['images']) && is_array($data['images'])) {
            $data['primary_image'] = $data['images'][0]['image_path'] ?? $data['images'][0]['image_url'] ?? null;
        }
    }

    // Ensure property_type_name
    if (empty($data['property_type_name'])) {
        $data['property_type_name'] = $data['type_name'] ?? $data['property_type'] ?? 'Residential';
    }

    return $data;
}

/**
 * Sync or create a property in Cloud Firestore from MySQL data or provided array
 */
function firestore_sync_property($propertyId, $propertyData = null) {
    try {
        $firestore = getFirestore();
        $pId = (int)$propertyId;
        if ($pId <= 0) return false;

        $p = $propertyData;
        if (empty($p) && function_exists('connectDB')) {
            $conn = connectDB();
            if ($conn) {
                $stmt = $conn->prepare("SELECT p.*, pt.name as property_type_name 
                                       FROM properties p 
                                       LEFT JOIN property_types pt ON p.property_type_id = pt.id 
                                       WHERE p.id = ?");
                if ($stmt) {
                    $stmt->bind_param("i", $pId);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $p = $result->fetch_assoc();
                    $stmt->close();
                }

                if ($p) {
                    // Fetch images from MySQL
                    $imgStmt = $conn->prepare("SELECT id, image_path, is_primary FROM property_images WHERE property_id = ? ORDER BY is_primary DESC, id ASC");
                    if ($imgStmt) {
                        $imgStmt->bind_param("i", $pId);
                        $imgStmt->execute();
                        $imgRes = $imgStmt->get_result();
                        $images = [];
                        while ($img = $imgRes->fetch_assoc()) {
                            $images[] = [
                                'id' => (int)$img['id'],
                                'image_path' => $img['image_path'],
                                'image_url' => $img['image_path'],
                                'is_primary' => (bool)$img['is_primary']
                            ];
                        }
                        $p['images'] = $images;
                        $imgStmt->close();
                    }
                }
            }
        }

        if (empty($p)) {
            return false;
        }

        $areaVal = (float)($p['area'] ?? $p['area_sqft'] ?? $p['sqft'] ?? 0);
        $typeName = $p['property_type_name'] ?? $p['type_name'] ?? $p['property_type'] ?? 'Residential';
        $images = $p['images'] ?? [];
        $primaryImage = null;
        if (!empty($images) && is_array($images)) {
            $primaryImage = $images[0]['image_path'] ?? $images[0]['image_url'] ?? null;
        } elseif (!empty($p['primary_image'])) {
            $primaryImage = $p['primary_image'];
        } elseif (!empty($p['featured_image'])) {
            $primaryImage = $p['featured_image'];
        }

        // Clean description of nested HTML entities if present
        $cleanDescription = $p['description'] ?? '';
        if (!empty($cleanDescription)) {
            while (strpos($cleanDescription, '&amp;') !== false || strpos($cleanDescription, '&#') !== false) {
                $cleanDescription = html_entity_decode($cleanDescription, ENT_QUOTES | ENT_HTML5, 'UTF-8');
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

        $docData = [
            'id'               => $pId,
            'seller_id'        => (int)($p['seller_id'] ?? 0),
            'property_type_id' => (int)($p['property_type_id'] ?? 1),
            'type_name'        => $typeName,
            'property_type'    => $typeName,
            'property_type_name' => $typeName,
            'title'            => $p['title'] ?? '',
            'description'      => $cleanDescription,
            'price'            => (float)($p['price'] ?? 0),
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
            'status'           => $p['status'] ?? 'active',
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
            'featured_image'   => $primaryImage,
            'primary_image'    => $primaryImage,
            'images'           => $images,
            'created_at'       => $p['created_at'] ?? date('Y-m-d H:i:s'),
            'updated_at'       => $p['updated_at'] ?? date('Y-m-d H:i:s')
        ];

        $firestore->collection('properties')->document((string)$pId)->set($docData, ['merge' => true]);
        return $docData;
    } catch (Exception $e) {
        error_log("Error syncing property to Firestore #{$propertyId}: " . $e->getMessage());
        return false;
    }
}

/**
 * Fetch a single property by ID from Firestore
 */
function firestore_get_property_by_id($id) {
    $firestore = getFirestore();
    $docRef = $firestore->collection('properties')->document((string)$id);
    $snapshot = $docRef->snapshot();

    if (!$snapshot->exists()) {
        // Fallback to sync from MySQL if available
        if (function_exists('connectDB')) {
            $synced = firestore_sync_property($id);
            if ($synced && is_array($synced)) {
                return firestore_normalize_property($synced);
            }
        }
        return null;
    }

    $data = $snapshot->data();
    $data['id'] = (int)($data['id'] ?? $snapshot->id());

    // If area is 0 or amenities are missing in Firestore, auto-sync from MySQL
    $hasAmenities = isset($data['garage']) || isset($data['amenities']);
    $hasArea = (isset($data['area']) && (float)$data['area'] > 0) || 
               (isset($data['area_sqft']) && (float)$data['area_sqft'] > 0);
    if ((!$hasArea || !$hasAmenities || empty($data['year_built']) || $data['year_built'] === 'N/A') && function_exists('connectDB')) {
        $synced = firestore_sync_property($id);
        if ($synced && is_array($synced)) {
            $data = $synced;
        }
    }

    $data = firestore_normalize_property($data);

    // Populate seller information
    if (!empty($data['seller_id'])) {
        $sellerDoc = $firestore->collection('users')->document((string)$data['seller_id'])->snapshot();
        if ($sellerDoc->exists()) {
            $seller = $sellerDoc->data();
            $data['seller_name'] = $seller['full_name'] ?? '';
            $data['seller_email'] = $seller['email'] ?? '';
            $data['seller_phone'] = $seller['phone'] ?? '';
        }
    }

    if (empty($data['primary_image'])) {
        if (!empty($data['featured_image'])) {
            $data['primary_image'] = $data['featured_image'];
        } elseif (!empty($data['images']) && is_array($data['images'])) {
            $data['primary_image'] = $data['images'][0]['image_path'] ?? $data['images'][0]['image_url'] ?? null;
        }
    }

    // Populate property_type_name if not already present
    if (empty($data['property_type_name'])) {
        if (!empty($data['type_name'])) {
            $data['property_type_name'] = $data['type_name'];
        } elseif (!empty($data['property_type'])) {
            $data['property_type_name'] = $data['property_type'];
        } elseif (!empty($data['property_type_id'])) {
            $typeDoc = $firestore->collection('property_types')->document((string)$data['property_type_id'])->snapshot();
            if ($typeDoc->exists()) {
                $data['property_type_name'] = $typeDoc->get('name');
            } else {
                $data['property_type_name'] = 'Residential';
            }
        } else {
            $data['property_type_name'] = 'Residential';
        }
    }

    return $data;
}

/**
 * Fetch images for a property from Firestore
 */
function firestore_get_property_images($propertyId) {
    $property = firestore_get_property_by_id($propertyId);
    if ($property && !empty($property['images'])) {
        return $property['images'];
    }
    return [];
}

/**
 * Fetch user by email from Firestore
 */
function firestore_get_user_by_email($email) {
    $firestore = getFirestore();
    $users = $firestore->collection('users')->where('email', '=', trim($email))->documents();
    foreach ($users as $user) {
        if ($user->exists()) {
            $data = $user->data();
            $data['id'] = (int)($data['id'] ?? $user->id());
            return $data;
        }
    }
    return null;
}

/**
 * Fetch user by ID from Firestore
 */
function firestore_get_user_by_id($id) {
    $firestore = getFirestore();
    $userDoc = $firestore->collection('users')->document((string)$id)->snapshot();
    if ($userDoc->exists()) {
        $data = $userDoc->data();
        $data['id'] = (int)($data['id'] ?? $userDoc->id());
        return $data;
    }
    return null;
}

/**
 * Fetch property types from Firestore
 */
function firestore_get_property_types() {
    $firestore = getFirestore();
    $docs = $firestore->collection('property_types')->documents();
    $types = [];
    foreach ($docs as $doc) {
        if ($doc->exists()) {
            $types[] = $doc->data();
        }
    }
    if (empty($types)) {
        return [
            ['id' => 1, 'name' => 'Single Family Home'],
            ['id' => 2, 'name' => 'Apartment'],
            ['id' => 3, 'name' => 'Condo'],
            ['id' => 4, 'name' => 'Townhouse'],
            ['id' => 5, 'name' => 'Land'],
            ['id' => 6, 'name' => 'Commercial']
        ];
    }
    return $types;
}

/**
 * Sync or create a user in Firebase Auth and Cloud Firestore
 */
function firestore_sync_user($userId, $userData, $rawPassword = null) {
    try {
        $firestore = getFirestore();
        $auth = getFirebaseAuth();
        $email = trim($userData['email'] ?? '');
        $fullName = $userData['full_name'] ?? 'User';
        $firebaseUid = null;

        if (!empty($email)) {
            try {
                $userRecord = $auth->getUserByEmail($email);
                $firebaseUid = $userRecord->uid;
            } catch (\Kreait\Firebase\Exception\Auth\UserNotFound $e) {
                try {
                    $createdUser = $auth->createUser([
                        'email' => $email,
                        'emailVerified' => true,
                        'displayName' => $fullName,
                        'password' => !empty($rawPassword) ? $rawPassword : 'password123',
                        'disabled' => ($userData['status'] ?? 'active') === 'inactive'
                    ]);
                    $firebaseUid = $createdUser->uid;
                } catch (Exception $ce) {
                    error_log("Failed creating Firebase Auth user for {$email}: " . $ce->getMessage());
                }
            } catch (Exception $e) {
                error_log("Firebase Auth lookup error: " . $e->getMessage());
            }
        }

        $docData = [
            'id'           => (int)$userId,
            'firebase_uid' => $firebaseUid,
            'full_name'    => $fullName,
            'email'        => $email,
            'phone'        => $userData['phone'] ?? '',
            'role'         => $userData['role'] ?? 'buyer',
            'status'       => $userData['status'] ?? 'active',
            'company_name' => $userData['company_name'] ?? '',
            'bio'          => $userData['bio'] ?? '',
            'address'      => $userData['address'] ?? '',
            'city'         => $userData['city'] ?? '',
            'state'        => $userData['state'] ?? '',
            'zip_code'     => $userData['zip_code'] ?? '',
            'created_at'   => $userData['created_at'] ?? date('Y-m-d H:i:s'),
            'updated_at'   => $userData['updated_at'] ?? date('Y-m-d H:i:s')
        ];

        $firestore->collection('users')->document((string)$userId)->set($docData);
        if ($firebaseUid) {
            $firestore->collection('users_by_uid')->document($firebaseUid)->set($docData);
        }
        return true;
    } catch (Exception $e) {
        error_log("Error syncing user to Firestore: " . $e->getMessage());
        return false;
    }
}

/**
 * Fetch all users (or exclude admin) from Firestore
 */
function firestore_get_users($excludeAdmin = true) {
    $firestore = getFirestore();
    $docs = $firestore->collection('users')->documents();
    $users = [];
    $existingIds = [];
    foreach ($docs as $doc) {
        if ($doc->exists()) {
            $data = $doc->data();
            $data['id'] = (int)($data['id'] ?? $doc->id());
            $existingIds[] = $data['id'];
            if ($excludeAdmin && ($data['role'] ?? '') === 'admin') {
                continue;
            }
            $users[] = $data;
        }
    }

    // Auto-sync any MySQL users missing from Firestore if function fetchAll exists
    if (function_exists('fetchAll')) {
        try {
            $mysqlUsers = fetchAll("SELECT * FROM users");
            foreach ($mysqlUsers as $mu) {
                if (!in_array((int)$mu['id'], $existingIds)) {
                    firestore_sync_user($mu['id'], $mu);
                    if (!$excludeAdmin || ($mu['role'] ?? '') !== 'admin') {
                        $users[] = [
                            'id'           => (int)$mu['id'],
                            'firebase_uid' => null,
                            'full_name'    => $mu['full_name'],
                            'email'        => $mu['email'],
                            'phone'        => $mu['phone'] ?? '',
                            'role'         => $mu['role'] ?? 'buyer',
                            'status'       => $mu['status'] ?? 'active',
                            'company_name' => $mu['company_name'] ?? '',
                            'bio'          => $mu['bio'] ?? '',
                            'address'      => $mu['address'] ?? '',
                            'city'         => $mu['city'] ?? '',
                            'state'        => $mu['state'] ?? '',
                            'zip_code'     => $mu['zip_code'] ?? '',
                            'created_at'   => $mu['created_at'] ?? date('Y-m-d H:i:s'),
                            'updated_at'   => $mu['updated_at'] ?? date('Y-m-d H:i:s')
                        ];
                    }
                }
            }
        } catch (Exception $e) {}
    }

    // Sort by role then created_at desc
    usort($users, function($a, $b) {
        if (($a['role'] ?? '') !== ($b['role'] ?? '')) {
            return strcmp($a['role'] ?? '', $b['role'] ?? '');
        }
        return strtotime($b['created_at'] ?? 'now') <=> strtotime($a['created_at'] ?? 'now');
    });

    return $users;
}

/**
 * Delete a user from Firestore and Firebase Auth
 */
function firestore_delete_user($userId) {
    $firestore = getFirestore();
    $auth = getFirebaseAuth();

    $userDocRef = $firestore->collection('users')->document((string)$userId);
    $snapshot = $userDocRef->snapshot();

    if ($snapshot->exists()) {
        $userData = $snapshot->data();
        $firebaseUid = $userData['firebase_uid'] ?? null;

        // Delete from Firebase Auth if UID is present or lookup by email
        if (!empty($firebaseUid)) {
            try {
                $auth->deleteUser($firebaseUid);
            } catch (Exception $e) {
                error_log("Failed to delete user from Firebase Auth by UID: " . $e->getMessage());
            }
            try {
                $firestore->collection('users_by_uid')->document($firebaseUid)->delete();
            } catch (Exception $e) {}
        } elseif (!empty($userData['email'])) {
            try {
                $uRecord = $auth->getUserByEmail($userData['email']);
                if ($uRecord) {
                    $auth->deleteUser($uRecord->uid);
                }
            } catch (Exception $e) {
                error_log("Failed to delete user from Firebase Auth by Email: " . $e->getMessage());
            }
        }

        // Delete user's inquiries, messages, and favorites in Firestore
        try {
            $inqs = $firestore->collection('inquiries')->where('buyer_id', '=', (int)$userId)->documents();
            foreach ($inqs as $inq) {
                if ($inq->exists()) $inq->reference()->delete();
            }
            $favs = $firestore->collection('favorites')->where('buyer_id', '=', (int)$userId)->documents();
            foreach ($favs as $fav) {
                if ($fav->exists()) $fav->reference()->delete();
            }
            $sentMsgs = $firestore->collection('messages')->where('sender_id', '=', (int)$userId)->documents();
            foreach ($sentMsgs as $m) {
                if ($m->exists()) $m->reference()->delete();
            }
            $recvMsgs = $firestore->collection('messages')->where('receiver_id', '=', (int)$userId)->documents();
            foreach ($recvMsgs as $m) {
                if ($m->exists()) $m->reference()->delete();
            }
        } catch (Exception $e) {
            error_log("Error deleting related user records: " . $e->getMessage());
        }

        // Delete the main user document
        $userDocRef->delete();
        return true;
    }

    return false;
}

/**
 * Update user status in Firestore
 */
function firestore_update_user_status($userId, $status) {
    $firestore = getFirestore();
    $userDocRef = $firestore->collection('users')->document((string)$userId);
    $snapshot = $userDocRef->snapshot();
    if ($snapshot->exists()) {
        $userDocRef->update([
            ['path' => 'status', 'value' => $status],
            ['path' => 'updated_at', 'value' => date('Y-m-d H:i:s')]
        ]);
        return true;
    }
    return false;
}

/**
 * Delete a property from Firestore
 */
function firestore_delete_property($propertyId) {
    $firestore = getFirestore();
    $propRef = $firestore->collection('properties')->document((string)$propertyId);
    $snapshot = $propRef->snapshot();
    if ($snapshot->exists()) {
        // Delete related inquiries and favorites in Firestore
        try {
            $inqs = $firestore->collection('inquiries')->where('property_id', '=', (int)$propertyId)->documents();
            foreach ($inqs as $inq) {
                if ($inq->exists()) $inq->reference()->delete();
            }
            $favs = $firestore->collection('favorites')->where('property_id', '=', (int)$propertyId)->documents();
            foreach ($favs as $fav) {
                if ($fav->exists()) $fav->reference()->delete();
            }
        } catch (Exception $e) {
            error_log("Error deleting property related records in Firestore: " . $e->getMessage());
        }

        $propRef->delete();
        return true;
    }
    return false;
}
