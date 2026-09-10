<?php
/**
 * Firebase Client Wrapper
 * Initializes Firebase Auth and Firestore for XTateProsystem
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Kreait\Firebase\Factory;
use Google\Cloud\Firestore\FirestoreClient;

class FirebaseManager {
    private static $instance = null;
    private $factory;
    private $auth;
    private $firestore;

    private function __construct() {
        $credentialsPath = realpath(__DIR__ . '/../credentials/firebase_service_account.json');
        
        if (!$credentialsPath || !file_exists($credentialsPath)) {
            throw new Exception("Firebase service account credentials not found.");
        }

        // Set environment variable so all Google client libraries (GAPIC & GAX) automatically find it
        putenv('GOOGLE_APPLICATION_CREDENTIALS=' . $credentialsPath);
        $_ENV['GOOGLE_APPLICATION_CREDENTIALS'] = $credentialsPath;

        // Initialize Kreait Factory for Auth
        $this->factory = (new Factory)->withServiceAccount($credentialsPath);
        $this->auth = $this->factory->createAuth();

        // Initialize Google Cloud Firestore Client
        $this->firestore = new FirestoreClient([
            'projectId'   => 'xtate-prosystem',
            'keyFilePath' => $credentialsPath
        ]);
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getAuth() {
        return $this->auth;
    }

    public function getFirestore() {
        return $this->firestore;
    }
}

/**
 * Global helper function to get Firestore client
 */
function getFirestore() {
    return FirebaseManager::getInstance()->getFirestore();
}

/**
 * Global helper function to get Firebase Auth client
 */
function getFirebaseAuth() {
    return FirebaseManager::getInstance()->getAuth();
}
