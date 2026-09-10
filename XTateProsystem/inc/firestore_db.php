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

            // Ensure primary_image compatibility
            if (empty($data['primary_image'])) {
                if (!empty($data['featured_image'])) {
                    $data['primary_image'] = $data['featured_image'];
                } elseif (!empty($data['images']) && is_array($data['images'])) {
                    $data['primary_image'] = $data['images'][0]['image_path'] ?? $data['images'][0]['image_url'] ?? null;
                }
            }
            if (empty($data['area']) && isset($data['area_sqft'])) {
                $data['area'] = $data['area_sqft'];
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
 * Fetch a single property by ID from Firestore
 */
function firestore_get_property_by_id($id) {
    $firestore = getFirestore();
    $docRef = $firestore->collection('properties')->document((string)$id);
    $snapshot = $docRef->snapshot();

    if (!$snapshot->exists()) {
        return null;
    }

    $data = $snapshot->data();
    $data['id'] = (int)($data['id'] ?? $snapshot->id());

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
    if (empty($data['area']) && isset($data['area_sqft'])) {
        $data['area'] = $data['area_sqft'];
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

    // Provide default year_built and area if not set
    if (!isset($data['year_built'])) {
        $data['year_built'] = 'N/A';
    }
    if (!isset($data['area'])) {
        $data['area'] = 0;
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
