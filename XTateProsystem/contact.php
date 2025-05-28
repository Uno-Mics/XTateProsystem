<?php
require_once 'inc/db.php';
require_once 'inc/functions.php';
require_once 'inc/auth.php';

// Start session
startSession();

$errors = [];
$success = '';
$formData = [
    'name' => '',
    'email' => '',
    'phone' => '',
    'subject' => '',
    'message' => ''
];

// Process contact form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $formData = [
        'name' => sanitizeInput($_POST['name'] ?? ''),
        'email' => sanitizeInput($_POST['email'] ?? ''),
        'phone' => sanitizeInput($_POST['phone'] ?? ''),
        'subject' => sanitizeInput($_POST['subject'] ?? ''),
        'message' => sanitizeInput($_POST['message'] ?? '')
    ];

    // Determine user type based on email
    $sql = "SELECT role FROM users WHERE email = ?";
    $user = fetchOne($sql, "s", [$formData['email']]);
    $userType = $user ? $user['role'] : 'guest';
    
    // Validate form data
    if (empty($formData['name'])) {
        $errors[] = 'Name is required';
    }
    
    if (empty($formData['email'])) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }
    
    if (empty($formData['subject'])) {
        $errors[] = 'Subject is required';
    }
    
    if (empty($formData['message'])) {
        $errors[] = 'Message is required';
    }
    
    // If no errors, save contact message
    if (empty($errors)) {
        $sql = "INSERT INTO contacts (name, email, phone, subject, message, user_type, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
        
        $messageId = insertData($sql, "ssssss", [
            $formData['name'],
            $formData['email'],
            $formData['phone'],
            $formData['subject'],
            $formData['message'],
            $userType
        ]);
        
        if ($messageId) {
            $success = 'Your message has been sent successfully. We will get back to you soon.';
            // Clear form data after successful submission
            $formData = [
                'name' => '',
                'email' => '',
                'phone' => '',
                'subject' => '',
                'message' => ''
            ];
        } else {
            $errors[] = 'Failed to send message. Please try again.';
        }
    }
}

include 'inc/header.php';
?>

<div class="container py-5">
    <h1 class="section-title text-center mb-5">Contact Us</h1>
    
    <div class="row">
        <div class="col-lg-6 mb-4 mb-lg-0">
            <div class="contact-info">
                <h2 class="mb-4">Get in Touch</h2>
                <p class="mb-4">Have questions about our properties or services? Reach out to our team and we'll be happy to assist you.</p>
                
                <div class="mb-4">
                    <i class="fas fa-map-marker-alt"></i>
                    <div>
                        <h5>Our Office</h5>
                        <p>123 Real Estate St, City, State 12345</p>
                    </div>
                </div>
                
                <div class="mb-4">
                    <i class="fas fa-phone"></i>
                    <div>
                        <h5>Phone</h5>
                        <p> +63 96 854 7501</p>
                    </div>
                </div>
                
                <div class="mb-4">
                    <i class="fas fa-envelope"></i>
                    <div>
                        <h5>Email</h5>
                        <p>xtateprosystem@gmail.com</p>
                    </div>
                </div>
                
                <div>
                    <i class="fas fa-clock"></i>
                    <div>
                        <h5>Office Hours</h5>
                        <p>Monday - Friday: 9:00 AM - 5:00 PM<br>Saturday: 10:00 AM - 2:00 PM<br>Sunday: Closed</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6">
            <div class="contact-form">
                <h2 class="mb-4">Send a Message</h2>
                
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
                    </div>
                <?php endif; ?>
                
                <form id="contactForm" method="POST" action="<?= $_SERVER['PHP_SELF'] ?>" novalidate>
                    <div class="mb-3">
                        <label for="name" class="form-label">Your Name</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?= $formData['name'] ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?= $formData['email'] ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number (Optional)</label>
                        <input type="tel" class="form-control" id="phone" name="phone" value="<?= $formData['phone'] ?>" placeholder="123-456-7890">
                    </div>
                    
                    <div class="mb-3">
                        <label for="subject" class="form-label">Subject</label>
                        <input type="text" class="form-control" id="subject" name="subject" value="<?= $formData['subject'] ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="message" class="form-label">Message</label>
                        <textarea class="form-control" id="message" name="message" rows="5" required><?= $formData['message'] ?></textarea>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">Send Message</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- FAQ Section -->
    <div class="mt-5">
        <h2 class="section-title text-center mb-4">Frequently Asked Questions</h2>
        
        <div class="accordion" id="faqAccordion">
            <div class="accordion-item">
                <h2 class="accordion-header" id="faqOne">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                        How do I list my property for sale?
                    </button>
                </h2>
                <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="faqOne" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        To list your property, you need to register as a seller on our platform. After registration, you can access your seller dashboard where you can add new properties with details, photos, and pricing information.
                    </div>
                </div>
            </div>
            
            <div class="accordion-item">
                <h2 class="accordion-header" id="faqTwo">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                        How can I contact a property seller?
                    </button>
                </h2>
                <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="faqTwo" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        You can contact property sellers in multiple ways. When you're viewing a property listing, you can either send a direct message to the seller using our messaging system, or submit an inquiry directly from the property page. You'll need to be registered and logged in as a buyer to use these features.
                    </div>
                </div>
            </div>
            
            <div class="accordion-item">
                <h2 class="accordion-header" id="faqThree">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                        Is there a fee for using this platform?
                    </button>
                </h2>
                <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="faqThree" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        Basic registration and property browsing is completely free for all users. Sellers can list a limited number of properties for free, with premium options available for additional listings and enhanced visibility.
                    </div>
                </div>
            </div>
            
            <div class="accordion-item">
                <h2 class="accordion-header" id="faqFour">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                        How do I save properties to view later?
                    </button>
                </h2>
                <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="faqFour" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        When you're logged in as a buyer, you can add properties to your favorites by clicking the "Add to Favorites" button on any property listing. You can then access all your saved properties in your buyer dashboard under the "Favorites" section.
                    </div>
                </div>
            </div>
            
            <div class="accordion-item">
                <h2 class="accordion-header" id="faqFive">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive">
                        How can I report an issue with a property listing?
                    </button>
                </h2>
                <div id="collapseFive" class="accordion-collapse collapse" aria-labelledby="faqFive" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        If you notice any issues with a property listing, such as incorrect information or inappropriate content, you can report it by using the "Report Listing" option on the property page. Our admin team will review all reports and take appropriate action.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="js/main.js"></script>

<?php include 'inc/footer.php'; ?>
