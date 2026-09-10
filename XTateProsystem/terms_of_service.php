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
                <span class="active-crumb">Terms of Service</span>
            </div>
        </div>

        <!-- Hero Card -->
        <div class="legal-hero-card">
            <div class="legal-hero-content">
                <span class="legal-hero-tag">
                    <i data-lucide="file-check-2" style="width:14px;height:14px;"></i>
                    Legal Agreement
                </span>
                <h1 class="legal-hero-title">Terms of Service</h1>
                <p class="legal-hero-desc">
                    These terms govern your access to and use of XTateProsystem. Please read them thoroughly to understand your legal rights and obligations.
                </p>
                <div class="legal-meta-row">
                    <span><i data-lucide="calendar" style="width:14px;height:14px;"></i> Last Revised: <?= date('F d, Y') ?></span>
                    <span>•</span>
                    <span><i data-lucide="globe" style="width:14px;height:14px;"></i> Applicable Worldwide</span>
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
                        <a href="#section-1" class="nav-item-link">1. Agreement to Terms</a>
                        <a href="#section-2" class="nav-item-link">2. User Accounts & Security</a>
                        <a href="#section-3" class="nav-item-link">3. Property Listings & Accuracy</a>
                        <a href="#section-4" class="nav-item-link">4. User Conduct & Inquiries</a>
                        <a href="#section-5" class="nav-item-link">5. Limitation of Liability</a>
                        <a href="#section-6" class="nav-item-link">6. Amendments & Contact</a>
                    </nav>
                </div>
            </aside>

            <!-- Main Legal Content -->
            <main class="legal-content-col">

                <div class="legal-section-card" id="section-1">
                    <div class="section-icon-wrap icon--blue">
                        <i data-lucide="check-square"></i>
                    </div>
                    <div class="section-card-body">
                        <h3 class="section-title">1. Agreement to Terms</h3>
                        <p>
                            By creating an account, browsing listings, inquiring about real estate, or otherwise accessing <strong>XTateProsystem</strong>, you explicitly confirm that you have read, understood, and agreed to be bound by these Terms of Service, along with our Privacy Policy.
                        </p>
                        <p class="mb-0">
                            If you do not agree with any provision contained herein, you must immediately discontinue your use of our platform and services.
                        </p>
                    </div>
                </div>

                <div class="legal-section-card" id="section-2">
                    <div class="section-icon-wrap icon--purple">
                        <i data-lucide="user-check"></i>
                    </div>
                    <div class="section-card-body">
                        <h3 class="section-title">2. User Accounts & Responsibilities</h3>
                        <p>
                            When registering an account as either a <strong>Buyer</strong> or a <strong>Seller</strong>, you agree to provide truthful, accurate, and up-to-date credentials.
                        </p>
                        <ul class="legal-list">
                            <li>You are solely responsible for maintaining the confidentiality of your login credentials and authentication credentials.</li>
                            <li>You agree to promptly notify our administrative support team of any unauthorized use or potential security breach involving your account.</li>
                            <li>Accounts found using deceptive identity representations or falsified contact information are subject to immediate suspension.</li>
                        </ul>
                    </div>
                </div>

                <div class="legal-section-card" id="section-3">
                    <div class="section-icon-wrap icon--emerald">
                        <i data-lucide="home"></i>
                    </div>
                    <div class="section-card-body">
                        <h3 class="section-title">3. Property Listings & Accuracy</h3>
                        <p>
                            Sellers and verified agents retain primary legal responsibility for the veracity, legality, and accuracy of all property details published on XTateProsystem:
                        </p>
                        <ul class="legal-list">
                            <li><strong>Authentic Media:</strong> All uploaded photos, floor plans, and media assets must portray the authentic current physical condition of the property and must not infringe on third-party intellectual property rights.</li>
                            <li><strong>Transparent Pricing:</strong> Asking prices, square footage, property type categories, and HOA dues must reflect genuine commercial figures.</li>
                            <li><strong>Moderation Rights:</strong> XTateProsystem reserves the right to review, update, or remove any listing that breaches platform guidelines or relevant real estate legislation.</li>
                        </ul>
                    </div>
                </div>

                <div class="legal-section-card" id="section-4">
                    <div class="section-icon-wrap icon--amber">
                        <i data-lucide="message-square-x"></i>
                    </div>
                    <div class="section-card-body">
                        <h3 class="section-title">4. User Conduct & Inquiries</h3>
                        <p>
                            XTateProsystem strives to maintain an ethical, trustworthy community for property transactions. The following actions are strictly prohibited:
                        </p>
                        <ul class="legal-list">
                            <li>Transmitting unsolicited spam, mass marketing schemes, or phishing attempts through the messaging system.</li>
                            <li>Harassing, threatening, or defrauding buyers, sellers, or platform administrators.</li>
                            <li>Submitting false or malicious moderation reports against competing sellers or authentic listings.</li>
                        </ul>
                    </div>
                </div>

                <div class="legal-section-card" id="section-5">
                    <div class="section-icon-wrap icon--red">
                        <i data-lucide="scale"></i>
                    </div>
                    <div class="section-card-body">
                        <h3 class="section-title">5. Limitation of Liability</h3>
                        <p>
                            XTateProsystem operates as a modern digital marketplace connecting independent buyers with property owners and agents. XTateProsystem does not own, physically inspect, or warrant the construction integrity, title deeds, or physical safety of listed properties.
                        </p>
                        <p class="mb-0">
                            Users are strongly advised to perform professional due diligence, property title verification, and licensed home inspections prior to finalizing any financial transactions.
                        </p>
                    </div>
                </div>

                <div class="legal-section-card" id="section-6">
                    <div class="section-icon-wrap icon--blue">
                        <i data-lucide="help-circle"></i>
                    </div>
                    <div class="section-card-body">
                        <h3 class="section-title">6. Amendments & Contact</h3>
                        <p>
                            We reserve the right to periodically update these Terms of Service. Continued use of the platform after updates are published constitutes acceptance of the modified terms.
                        </p>
                        <div class="d-flex align-items-center gap-3 flex-wrap mt-3">
                            <a href="contact.php" class="btn-legal-contact">
                                <i data-lucide="mail" style="width:15px;height:15px;"></i>
                                <span>Contact Legal Support</span>
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
.icon--red     { background: #FEF2F2; color: #DC2626; }

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
