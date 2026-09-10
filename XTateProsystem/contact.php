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

// If user is logged in, pre-fill their info
if (isLoggedIn()) {
    $formData['name'] = $_SESSION['user_name'] ?? '';
    $formData['email'] = $_SESSION['user_email'] ?? '';
}

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
    $userType = $user ? $user['role'] : (isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'guest');
    
    // Validate form data
    if (empty($formData['name'])) {
        $errors[] = 'Your full name is required.';
    }
    
    if (empty($formData['email'])) {
        $errors[] = 'Email address is required.';
    } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please provide a valid email format.';
    }
    
    if (empty($formData['subject'])) {
        $errors[] = 'Please choose or provide an inquiry subject.';
    }
    
    if (empty($formData['message'])) {
        $errors[] = 'Message content cannot be empty.';
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
            // Sync to Cloud Firestore
            try {
                $firestore = getFirestore();
                $firestore->collection('contacts')->document((string)$messageId)->set([
                    'id'         => (int)$messageId,
                    'name'       => $formData['name'],
                    'email'      => $formData['email'],
                    'phone'      => $formData['phone'],
                    'subject'    => $formData['subject'],
                    'message'    => $formData['message'],
                    'user_type'  => $userType,
                    'status'     => 'unread',
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            } catch (Exception $fe) {
                error_log("Firestore contact sync warning: " . $fe->getMessage());
            }

            $success = 'Thank you! Your message has been received. A dedicated real estate advisor will respond within 24 hours.';
            // Clear form data after successful submission
            $formData = [
                'name' => '',
                'email' => '',
                'phone' => '',
                'subject' => '',
                'message' => ''
            ];
        } else {
            $errors[] = 'We encountered an error transmitting your message. Please try again.';
        }
    }
}

include 'inc/header.php';
?>

<div class="contact-page-wrap">
    <div class="contact-page-container">

        <!-- Top Navigation / Go Back Bar -->
        <div class="top-nav-bar">
            <a href="javascript:history.length > 1 ? history.back() : window.location.href='index.php'" class="btn-back-nav" id="goBackBtn">
                <i data-lucide="arrow-left" style="width:16px;height:16px;"></i>
                <span>Back to Previous Page</span>
            </a>
            <div class="breadcrumb-trail">
                <a href="index.php">Home</a>
                <i data-lucide="chevron-right" style="width:13px;height:13px;opacity:0.5;"></i>
                <span class="active-crumb">Contact Support</span>
            </div>
        </div>

        <!-- Hero Banner -->
        <div class="contact-hero-card">
            <div class="contact-hero-content">
                <span class="contact-hero-tag">
                    <i data-lucide="sparkles" style="width:14px;height:14px;"></i>
                    Client Concierge & Inquiries
                </span>
                <h1 class="contact-hero-title">How Can We Help You Today?</h1>
                <p class="contact-hero-desc">
                    Whether you are searching for your dream home, need assistance listing an upscale property, or have platform inquiries, our professional real estate team is here for you.
                </p>
            </div>
        </div>

        <!-- Main Bento Grid -->
        <div class="contact-main-grid">

            <!-- Left: Contact Information Cards -->
            <div class="contact-info-col">
                <h3 class="info-section-heading">
                    <i data-lucide="headset" style="width:18px;height:18px;color:#2563EB;"></i>
                    Direct Channels
                </h3>

                <div class="info-bento-card">
                    <div class="info-card-icon info-icon--blue">
                        <i data-lucide="map-pin"></i>
                    </div>
                    <div class="info-card-body">
                        <h4 class="info-card-title">Corporate Headquarters</h4>
                        <p class="info-card-text">
                            Barangay 10-B Kingfisher Ariston Sison Rd., Seabreeze Subd., Cavite City, Philippines 4100
                        </p>
                        <span class="info-card-badge">Cavite City, PH</span>
                    </div>
                </div>

                <div class="info-bento-card">
                    <div class="info-card-icon info-icon--emerald">
                        <i data-lucide="phone-call"></i>
                    </div>
                    <div class="info-card-body">
                        <h4 class="info-card-title">Direct Phone Line</h4>
                        <p class="info-card-text">
                            Available during business hours for quick consultation and schedule assistance.
                        </p>
                        <a href="tel:+63968547501" class="info-card-link">
                            <span>+63 96 854 7501</span>
                            <i data-lucide="arrow-up-right" style="width:14px;height:14px;"></i>
                        </a>
                    </div>
                </div>

                <div class="info-bento-card">
                    <div class="info-card-icon info-icon--purple">
                        <i data-lucide="mail"></i>
                    </div>
                    <div class="info-card-body">
                        <h4 class="info-card-title">Email Inquiries</h4>
                        <p class="info-card-text">
                            Send us listing dossiers, customer service questions, or partnership inquiries.
                        </p>
                        <a href="mailto:xtateprosystem@gmail.com" class="info-card-link">
                            <span>xtateprosystem@gmail.com</span>
                            <i data-lucide="arrow-up-right" style="width:14px;height:14px;"></i>
                        </a>
                    </div>
                </div>

                <div class="info-bento-card">
                    <div class="info-card-icon info-icon--amber">
                        <i data-lucide="clock"></i>
                    </div>
                    <div class="info-card-body">
                        <h4 class="info-card-title">Hours of Operation</h4>
                        <ul class="hours-list">
                            <li><span>Monday – Friday</span> <strong>9:00 AM – 6:00 PM</strong></li>
                            <li><span>Saturday</span> <strong>9:00 AM – 3:00 PM</strong></li>
                            <li><span>Sunday</span> <span class="text-muted">Online Queue Active</span></li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Right: Interactive Contact Form -->
            <div class="contact-form-col">
                <div class="form-bento-card">
                    <div class="form-card-head">
                        <div class="d-flex align-items-center gap-2">
                            <div class="form-icon-wrap">
                                <i data-lucide="send"></i>
                            </div>
                            <div>
                                <h3 class="form-card-title">Send a Direct Message</h3>
                                <p class="form-card-desc">Fill out the fields below and we will connect you to the appropriate specialist.</p>
                            </div>
                        </div>
                    </div>

                    <div class="form-card-body">
                        <?php if (!empty($errors)): ?>
                            <div class="contact-alert contact-alert--danger">
                                <i data-lucide="alert-circle"></i>
                                <div>
                                    <strong>Please review the following:</strong>
                                    <ul class="mb-0 mt-1 ps-3">
                                        <?php foreach ($errors as $error): ?>
                                            <li><?= htmlspecialchars($error) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($success)): ?>
                            <div class="contact-alert contact-alert--success">
                                <i data-lucide="check-circle-2"></i>
                                <div>
                                    <strong>Message Sent!</strong> <?= htmlspecialchars($success) ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="contact.php" novalidate class="modern-contact-form">
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="input-label" for="c_name">Full Name <span class="text-danger">*</span></label>
                                    <div class="input-with-icon">
                                        <i data-lucide="user"></i>
                                        <input type="text" class="input-field" id="c_name" name="name" value="<?= htmlspecialchars($formData['name']) ?>" placeholder="e.g. Maria Santos" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="input-label" for="c_email">Email Address <span class="text-danger">*</span></label>
                                    <div class="input-with-icon">
                                        <i data-lucide="mail"></i>
                                        <input type="email" class="input-field" id="c_email" name="email" value="<?= htmlspecialchars($formData['email']) ?>" placeholder="name@example.com" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="input-label" for="c_phone">Phone Number (Optional)</label>
                                    <div class="input-with-icon">
                                        <i data-lucide="phone"></i>
                                        <input type="tel" class="input-field" id="c_phone" name="phone" value="<?= htmlspecialchars($formData['phone']) ?>" placeholder="e.g. +63 917 123 4567">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="input-label" for="c_subject">Inquiry Subject <span class="text-danger">*</span></label>
                                    <div class="input-with-icon">
                                        <i data-lucide="tag"></i>
                                        <input type="text" class="input-field" id="c_subject" name="subject" value="<?= htmlspecialchars($formData['subject']) ?>" placeholder="e.g. Property Viewing Request" required>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="input-label" for="c_message">Your Detailed Message <span class="text-danger">*</span></label>
                                <textarea class="textarea-field" id="c_message" name="message" rows="5" placeholder="Please describe your property questions or requirements in detail..." required><?= htmlspecialchars($formData['message']) ?></textarea>
                            </div>

                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                                <span class="privacy-note">
                                    <i data-lucide="shield-check" style="width:14px;height:14px;color:#10B981;"></i>
                                    Your information is kept confidential & secure.
                                </span>
                                <button type="submit" class="btn-submit-contact">
                                    <span>Send Message</span>
                                    <i data-lucide="arrow-right" style="width:16px;height:16px;"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </div>

        <!-- FAQ Bento Section -->
        <div class="contact-faq-section">
            <div class="faq-head-center">
                <span class="faq-badge">Instant Answers</span>
                <h2 class="faq-title">Frequently Asked Questions</h2>
                <p class="faq-subtitle">Quick guidance on listings, scheduling tours, and account verification.</p>
            </div>

            <div class="accordion modern-faq-accordion" id="faqAccordion">
                <div class="accordion-item modern-accordion-item">
                    <h2 class="accordion-header" id="faqOne">
                        <button class="accordion-button modern-accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                            <i data-lucide="plus-circle" class="faq-accord-icon"></i>
                            <span>How do I list my property for sale on XTate?</span>
                        </button>
                    </h2>
                    <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="faqOne" data-bs-parent="#faqAccordion">
                        <div class="accordion-body modern-accordion-body">
                            Simply register or sign in as a <strong>Seller</strong>. From your Seller Dashboard, navigate to "Add Property" to enter specifications, upload high-resolution photography, set your asking price, and publish.
                        </div>
                    </div>
                </div>

                <div class="accordion-item modern-accordion-item">
                    <h2 class="accordion-header" id="faqTwo">
                        <button class="accordion-button modern-accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                            <i data-lucide="message-square" class="faq-accord-icon"></i>
                            <span>How can I contact a property seller directly?</span>
                        </button>
                    </h2>
                    <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="faqTwo" data-bs-parent="#faqAccordion">
                        <div class="accordion-body modern-accordion-body">
                            When browsing any property details page, buyers can click "Send Inquiry" or initiate real-time messaging directly with the verified agent or homeowner.
                        </div>
                    </div>
                </div>

                <div class="accordion-item modern-accordion-item">
                    <h2 class="accordion-header" id="faqThree">
                        <button class="accordion-button modern-accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                            <i data-lucide="badge-percent" class="faq-accord-icon"></i>
                            <span>Is there any charge or subscription fee for buyers?</span>
                        </button>
                    </h2>
                    <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="faqThree" data-bs-parent="#faqAccordion">
                        <div class="accordion-body modern-accordion-body">
                            No. Browsing listings, saving favorite properties, communicating with sellers, and submitting inquiries is 100% free for all prospective buyers.
                        </div>
                    </div>
                </div>

                <div class="accordion-item modern-accordion-item">
                    <h2 class="accordion-header" id="faqFour">
                        <button class="accordion-button modern-accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                            <i data-lucide="flag" class="faq-accord-icon"></i>
                            <span>How do I report an inaccurate or suspicious listing?</span>
                        </button>
                    </h2>
                    <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="faqFour" data-bs-parent="#faqAccordion">
                        <div class="accordion-body modern-accordion-body">
                            On every property listing page, use the "Report Listing" button to notify our administration team. Our security team verifies all claims promptly.
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<style>
/* ── Modern Bento Page Styles ── */
.contact-page-wrap {
    background-color: #F8FAFC;
    min-height: calc(100vh - 70px);
    padding: 32px 0 64px;
    font-family: 'Inter', sans-serif;
    color: #334155;
}

.contact-page-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

/* Top Nav Bar */
.top-nav-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 24px;
}

.btn-back-nav {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    padding: 8px 16px;
    border-radius: 10px;
    font-size: 0.85rem;
    font-weight: 600;
    color: #475569;
    text-decoration: none;
    box-shadow: 0 1px 2px rgba(0,0,0,0.04);
    transition: all .2s ease;
}
.btn-back-nav:hover {
    background: #F1F5F9;
    color: #0F172A;
    transform: translateX(-2px);
}

.breadcrumb-trail {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.82rem;
    color: #64748B;
}
.breadcrumb-trail a {
    color: #64748B;
    text-decoration: none;
    transition: color .15s ease;
}
.breadcrumb-trail a:hover {
    color: #2563EB;
}
.breadcrumb-trail .active-crumb {
    color: #0F172A;
    font-weight: 600;
}

/* Hero Card */
.contact-hero-card {
    background: linear-gradient(135deg, #1E293B 0%, #0F172A 100%);
    border-radius: 20px;
    padding: 36px 40px;
    color: #FFFFFF;
    margin-bottom: 32px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.15);
}

.contact-hero-tag {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    background: rgba(37, 99, 235, 0.25);
    color: #93C5FD;
    padding: 4px 12px;
    border-radius: 100px;
    border: 1px solid rgba(147, 197, 253, 0.2);
    margin-bottom: 12px;
}

.contact-hero-title {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 1.85rem;
    font-weight: 800;
    color: #FFFFFF;
    margin: 0 0 10px;
    letter-spacing: -0.02em;
}

.contact-hero-desc {
    font-size: 0.95rem;
    color: #94A3B8;
    max-width: 680px;
    margin: 0;
    line-height: 1.5;
}

/* Main Grid */
.contact-main-grid {
    display: grid;
    grid-template-columns: 380px minmax(0, 1fr);
    gap: 28px;
    align-items: start;
}

@media (max-width: 991px) {
    .contact-main-grid {
        grid-template-columns: 1fr;
    }
}

/* Info Column */
.info-section-heading {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 1.1rem;
    font-weight: 700;
    color: #0F172A;
    margin: 0 0 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.info-bento-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    padding: 20px;
    margin-bottom: 16px;
    display: flex;
    gap: 16px;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
    transition: all .2s ease;
}
.info-bento-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(15, 23, 42, 0.06);
    border-color: #CBD5E1;
}

.info-card-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.info-card-icon svg {
    width: 20px;
    height: 20px;
}
.info-icon--blue { background: #EFF6FF; color: #2563EB; }
.info-icon--emerald { background: #ECFDF5; color: #059669; }
.info-icon--purple { background: #FAF5FF; color: #7C3AED; }
.info-icon--amber { background: #FFFBEB; color: #D97706; }

.info-card-body {
    flex-grow: 1;
}

.info-card-title {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 0.95rem;
    font-weight: 700;
    color: #0F172A;
    margin: 0 0 4px;
}

.info-card-text {
    font-size: 0.82rem;
    color: #64748B;
    line-height: 1.45;
    margin: 0 0 8px;
}

.info-card-badge {
    display: inline-block;
    font-size: 0.72rem;
    font-weight: 600;
    color: #2563EB;
    background: #EFF6FF;
    padding: 2px 8px;
    border-radius: 6px;
}

.info-card-link {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 0.85rem;
    font-weight: 600;
    color: #2563EB;
    text-decoration: none;
    transition: color .15s ease;
}
.info-card-link:hover {
    color: #1D4ED8;
    text-decoration: underline;
}

.hours-list {
    list-group: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.hours-list li {
    display: flex;
    justify-content: space-between;
    font-size: 0.8rem;
    color: #64748B;
}

/* Form Bento Card */
.form-bento-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.05);
}

.form-card-head {
    padding: 24px 28px;
    border-bottom: 1px solid #F1F5F9;
}

.form-icon-wrap {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    background: #EFF6FF;
    color: #2563EB;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.form-icon-wrap svg {
    width: 20px;
    height: 20px;
}

.form-card-title {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 1.15rem;
    font-weight: 800;
    color: #0F172A;
    margin: 0 0 2px;
}

.form-card-desc {
    font-size: 0.82rem;
    color: #64748B;
    margin: 0;
}

.form-card-body {
    padding: 28px;
}

.contact-alert {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 14px 18px;
    border-radius: 12px;
    font-size: 0.88rem;
    margin-bottom: 20px;
}
.contact-alert svg {
    width: 20px;
    height: 20px;
    flex-shrink: 0;
}
.contact-alert--danger {
    background: #FEF2F2;
    border: 1px solid #FECACA;
    color: #991B1B;
}
.contact-alert--success {
    background: #F0FDF4;
    border: 1px solid #BBF7D0;
    color: #166534;
}

.input-label {
    display: block;
    font-size: 0.82rem;
    font-weight: 600;
    color: #334155;
    margin-bottom: 6px;
}

.input-with-icon {
    position: relative;
    display: flex;
    align-items: center;
}
.input-with-icon svg {
    position: absolute;
    left: 14px;
    width: 17px;
    height: 17px;
    color: #94A3B8;
    pointer-events: none;
}

.input-field {
    width: 100%;
    padding: 11px 14px 11px 40px;
    border-radius: 10px;
    border: 1px solid #E2E8F0;
    font-size: 0.88rem;
    color: #0F172A;
    background: #FFFFFF;
    transition: all .2s ease;
}
.input-field:focus {
    outline: none;
    border-color: #2563EB;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.textarea-field {
    width: 100%;
    padding: 12px 14px;
    border-radius: 10px;
    border: 1px solid #E2E8F0;
    font-size: 0.88rem;
    color: #0F172A;
    background: #FFFFFF;
    transition: all .2s ease;
}
.textarea-field:focus {
    outline: none;
    border-color: #2563EB;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.privacy-note {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.78rem;
    color: #64748B;
}

.btn-submit-contact {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #2563EB;
    color: #FFFFFF;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 0.9rem;
    font-weight: 700;
    padding: 12px 28px;
    border-radius: 10px;
    border: none;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
    transition: all .2s ease;
}
.btn-submit-contact:hover {
    background: #1D4ED8;
    transform: translateY(-1px);
    box-shadow: 0 6px 18px rgba(37, 99, 235, 0.35);
}

/* FAQ Section */
.contact-faq-section {
    margin-top: 56px;
    padding-top: 40px;
    border-top: 1px solid #E2E8F0;
}

.faq-head-center {
    text-align: center;
    margin-bottom: 32px;
}

.faq-badge {
    display: inline-block;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    background: #EFF6FF;
    color: #2563EB;
    padding: 4px 12px;
    border-radius: 100px;
    margin-bottom: 8px;
}

.faq-title {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 1.6rem;
    font-weight: 800;
    color: #0F172A;
    margin: 0 0 6px;
}

.faq-subtitle {
    font-size: 0.9rem;
    color: #64748B;
    margin: 0;
}

.modern-faq-accordion {
    max-width: 860px;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.modern-accordion-item {
    background: #FFFFFF;
    border: 1px solid #E2E8F0 !important;
    border-radius: 14px !important;
    overflow: hidden;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
}

.modern-accordion-button {
    background: #FFFFFF !important;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 0.95rem;
    font-weight: 700;
    color: #0F172A !important;
    padding: 18px 22px;
    display: flex;
    align-items: center;
    gap: 10px;
    border: none !important;
    box-shadow: none !important;
}

.faq-accord-icon {
    width: 18px;
    height: 18px;
    color: #2563EB;
    flex-shrink: 0;
}

.modern-accordion-body {
    padding: 0 22px 20px;
    color: #475569;
    font-size: 0.88rem;
    line-height: 1.6;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});
</script>

<?php include 'inc/footer.php'; ?>
