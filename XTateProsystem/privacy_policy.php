<?php
$inc_dir = '';
require_once 'inc/db.php';
require_once 'inc/functions.php';
require_once 'inc/auth.php';

startSession();
include 'inc/header.php';
?>

<div class="legal-page-wrap">
    <div class="legal-page-container">

        <!-- Top Navigation / Go Back Bar -->
        <div class="top-nav-bar">
            <a href="javascript:history.length > 1 ? history.back() : window.location.href='index.php'" class="btn-back-nav" id="goBackBtn">
                <i data-lucide="arrow-left" style="width:16px;height:16px;"></i>
                <span>Back to Previous Page</span>
            </a>
            <div class="breadcrumb-trail">
                <a href="index.php">Home</a>
                <i data-lucide="chevron-right" style="width:13px;height:13px;opacity:0.5;"></i>
                <span class="active-crumb">Privacy Policy</span>
            </div>
        </div>

        <!-- Hero Card -->
        <div class="legal-hero-card">
            <div class="legal-hero-content">
                <span class="legal-hero-tag">
                    <i data-lucide="shield-check" style="width:14px;height:14px;"></i>
                    Data Governance & Privacy
                </span>
                <h1 class="legal-hero-title">Privacy Policy</h1>
                <p class="legal-hero-desc">
                    Learn how XTateProsystem collects, uses, encrypts, and protects your personal credentials, contact inquiries, and real estate data.
                </p>
                <div class="legal-meta-row">
                    <span><i data-lucide="calendar" style="width:14px;height:14px;"></i> Last Revised: <?= date('F d, Y') ?></span>
                    <span>•</span>
                    <span><i data-lucide="lock" style="width:14px;height:14px;"></i> SSL & Firebase Protected</span>
                </div>
            </div>
        </div>

        <!-- Bento Sections Grid -->
        <div class="legal-layout-grid">

            <!-- Sticky Quick Nav -->
            <aside class="legal-sidebar">
                <div class="quick-nav-card">
                    <h4 class="quick-nav-title">
                        <i data-lucide="list" style="width:16px;height:16px;color:#2563EB;"></i>
                        Table of Contents
                    </h4>
                    <nav class="quick-nav-links">
                        <a href="#privacy-1" class="nav-item-link">1. Information We Collect</a>
                        <a href="#privacy-2" class="nav-item-link">2. How We Use Information</a>
                        <a href="#privacy-3" class="nav-item-link">3. Information Sharing</a>
                        <a href="#privacy-4" class="nav-item-link">4. Cloud Security Safeguards</a>
                        <a href="#privacy-5" class="nav-item-link">5. Data Subject Rights & Contact</a>
                    </nav>
                </div>
            </aside>

            <!-- Main Content -->
            <main class="legal-content-col">

                <div class="legal-section-card" id="privacy-1">
                    <div class="section-icon-wrap icon--blue">
                        <i data-lucide="database"></i>
                    </div>
                    <div class="section-card-body">
                        <h3 class="section-title">1. Information We Collect</h3>
                        <p>
                            At <strong>XTateProsystem</strong>, we collect personal information you provide when registering an account, publishing property listings, scheduling tours, or submitting buyer inquiries:
                        </p>
                        <ul class="legal-list">
                            <li><strong>Personal Identity:</strong> Your full name, email address, phone number, and physical office/residence location.</li>
                            <li><strong>Real Estate Data:</strong> Property descriptions, prices, geo-coordinates, square footage, and photographic assets uploaded by sellers.</li>
                            <li><strong>Communication Records:</strong> Direct buyer-to-seller chat messages and contact support inquiries.</li>
                        </ul>
                    </div>
                </div>

                <div class="legal-section-card" id="privacy-2">
                    <div class="section-icon-wrap icon--emerald">
                        <i data-lucide="sparkles"></i>
                    </div>
                    <div class="section-card-body">
                        <h3 class="section-title">2. How We Use Your Information</h3>
                        <p>We process your data exclusively to deliver authentic, high-quality real estate marketplace services:</p>
                        <ul class="legal-list">
                            <li>Connecting prospective home buyers with verified real estate sellers and agents.</li>
                            <li>Transmitting instant notifications on status changes, price updates, and messages.</li>
                            <li>Authenticating sessions and safeguarding against unauthorized access or fraud.</li>
                            <li>Optimizing search filters and property recommendations.</li>
                        </ul>
                    </div>
                </div>

                <div class="legal-section-card" id="privacy-3">
                    <div class="section-icon-wrap icon--amber">
                        <i data-lucide="share-2"></i>
                    </div>
                    <div class="section-card-body">
                        <h3 class="section-title">3. Information Sharing and Disclosure</h3>
                        <p>
                            <strong>We never sell, rent, or monetize your personal credentials to third-party data brokers.</strong>
                        </p>
                        <p class="mb-0">
                            Information is only shared between buyers and sellers when an inquiry or communication is initiated by the user for bona fide real estate transactions.
                        </p>
                    </div>
                </div>

                <div class="legal-section-card" id="privacy-4">
                    <div class="section-icon-wrap icon--purple">
                        <i data-lucide="shield-check"></i>
                    </div>
                    <div class="section-card-body">
                        <h3 class="section-title">4. Cloud Security Safeguards</h3>
                        <p>
                            We employ modern industry standards for data protection:
                        </p>
                        <ul class="legal-list">
                            <li><strong>Encrypted Authentication:</strong> Passwords are protected using secure bcrypt salt hashing.</li>
                            <li><strong>Google Firebase Integration:</strong> Cloud Firestore stores documents behind strict IAM security rules and encrypted transport.</li>
                            <li><strong>Session Protection:</strong> Cookie sessions are tokenized and protected against cross-site scripting (XSS).</li>
                        </ul>
                    </div>
                </div>

                <div class="legal-section-card" id="privacy-5">
                    <div class="section-icon-wrap icon--blue">
                        <i data-lucide="user-cog"></i>
                    </div>
                    <div class="section-card-body">
                        <h3 class="section-title">5. Your Rights & Data Inquiries</h3>
                        <p>
                            You retain full rights to review, update, or request permanent deletion of your profile and data records at any time.
                        </p>
                        <div class="d-flex align-items-center gap-3 flex-wrap mt-3">
                            <a href="contact.php" class="btn-legal-contact">
                                <i data-lucide="mail" style="width:15px;height:15px;"></i>
                                <span>Contact Privacy Officer</span>
                            </a>
                            <a href="javascript:history.length > 1 ? history.back() : window.location.href='index.php'" class="btn-legal-back">
                                <i data-lucide="arrow-left" style="width:15px;height:15px;"></i>
                                <span>Return to Previous Page</span>
                            </a>
                        </div>
                    </div>
                </div>

            </main>

        </div>

    </div>
</div>

<style>
/* ── Legal Bento Page Styles ── */
.legal-page-wrap {
    background-color: #F8FAFC;
    min-height: calc(100vh - 70px);
    padding: 32px 0 64px;
    font-family: 'Inter', sans-serif;
    color: #334155;
}

.legal-page-container {
    max-width: 1140px;
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
.legal-hero-card {
    background: linear-gradient(135deg, #1E293B 0%, #0F172A 100%);
    border-radius: 20px;
    padding: 36px 40px;
    color: #FFFFFF;
    margin-bottom: 32px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.15);
}

.legal-hero-tag {
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

.legal-hero-title {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 1.85rem;
    font-weight: 800;
    color: #FFFFFF;
    margin: 0 0 10px;
    letter-spacing: -0.02em;
}

.legal-hero-desc {
    font-size: 0.95rem;
    color: #94A3B8;
    max-width: 680px;
    margin: 0 0 16px;
    line-height: 1.5;
}

.legal-meta-row {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 0.8rem;
    color: #94A3B8;
}
.legal-meta-row span {
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

/* Layout Grid */
.legal-layout-grid {
    display: grid;
    grid-template-columns: 280px minmax(0, 1fr);
    gap: 28px;
    align-items: start;
}

@media (max-width: 991px) {
    .legal-layout-grid {
        grid-template-columns: 1fr;
    }
}

/* Quick Nav Sidebar */
.quick-nav-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    padding: 22px;
    position: sticky;
    top: 90px;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.05);
}

.quick-nav-title {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 0.92rem;
    font-weight: 700;
    color: #0F172A;
    margin: 0 0 14px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.quick-nav-links {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.nav-item-link {
    font-size: 0.82rem;
    color: #64748B;
    text-decoration: none;
    padding: 7px 10px;
    border-radius: 8px;
    transition: all .15s ease;
}
.nav-item-link:hover {
    background: #F1F5F9;
    color: #2563EB;
}

/* Legal Cards */
.legal-content-col {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.legal-section-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    padding: 26px 28px;
    display: flex;
    gap: 20px;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
    scroll-margin-top: 100px;
}

.section-icon-wrap {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.section-icon-wrap svg {
    width: 20px;
    height: 20px;
}
.icon--blue    { background: #EFF6FF; color: #2563EB; }
.icon--purple  { background: #FAF5FF; color: #7C3AED; }
.icon--emerald { background: #ECFDF5; color: #059669; }
.icon--amber   { background: #FFFBEB; color: #D97706; }

.section-card-body {
    flex-grow: 1;
}

.section-title {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 1.15rem;
    font-weight: 800;
    color: #0F172A;
    margin: 0 0 10px;
    letter-spacing: -0.01em;
}

.section-card-body p {
    font-size: 0.9rem;
    color: #475569;
    line-height: 1.6;
    margin-bottom: 12px;
}

.legal-list {
    margin: 0 0 12px;
    padding-left: 18px;
    color: #475569;
    font-size: 0.88rem;
    line-height: 1.6;
}
.legal-list li {
    margin-bottom: 6px;
}

.btn-legal-contact {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    background: #2563EB;
    color: #FFFFFF;
    font-weight: 600;
    font-size: 0.85rem;
    padding: 9px 18px;
    border-radius: 8px;
    text-decoration: none;
    box-shadow: 0 2px 8px rgba(37, 99, 235, 0.25);
    transition: all .2s ease;
}
.btn-legal-contact:hover {
    background: #1D4ED8;
    color: #FFFFFF;
    transform: translateY(-1px);
}

.btn-legal-back {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    background: #F1F5F9;
    color: #475569;
    font-weight: 600;
    font-size: 0.85rem;
    padding: 9px 18px;
    border-radius: 8px;
    text-decoration: none;
    transition: all .2s ease;
}
.btn-legal-back:hover {
    background: #E2E8F0;
    color: #0F172A;
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
