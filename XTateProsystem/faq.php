<?php
$inc_dir = '';
require_once 'inc/db.php';
require_once 'inc/functions.php';
require_once 'inc/auth.php';

startSession();
include 'inc/header.php';
?>

<div class="faq-page-wrap">
    <div class="faq-page-container">

        <!-- Top Navigation / Go Back Bar -->
        <div class="top-nav-bar">
            <a href="javascript:history.length > 1 ? history.back() : window.location.href='index.php'" class="btn-back-nav" id="goBackBtn">
                <i data-lucide="arrow-left" style="width:16px;height:16px;"></i>
                <span>Back to Previous Page</span>
            </a>
            <div class="breadcrumb-trail">
                <a href="index.php">Home</a>
                <i data-lucide="chevron-right" style="width:13px;height:13px;opacity:0.5;"></i>
                <span class="active-crumb">Help & FAQ</span>
            </div>
        </div>

        <!-- Hero Card -->
        <div class="faq-hero-card">
            <div class="faq-hero-content text-center">
                <span class="faq-hero-tag">
                    <i data-lucide="help-circle" style="width:14px;height:14px;"></i>
                    Help Center & Knowledge Base
                </span>
                <h1 class="faq-hero-title">Frequently Asked Questions</h1>
                <p class="faq-hero-desc">
                    Find quick, clear answers on finding homes, connecting with verified sellers, publishing listings, and navigating your real estate deals on XTateProsystem.
                </p>

                <!-- Interactive Filter Category Pills -->
                <div class="faq-filter-pills">
                    <button type="button" class="faq-pill-btn active" data-category="all">
                        <i data-lucide="layout-grid"></i>
                        <span>All Questions</span>
                    </button>
                    <button type="button" class="faq-pill-btn" data-category="buying">
                        <i data-lucide="shopping-bag"></i>
                        <span>For Buyers</span>
                    </button>
                    <button type="button" class="faq-pill-btn" data-category="selling">
                        <i data-lucide="building-2"></i>
                        <span>For Sellers</span>
                    </button>
                    <button type="button" class="faq-pill-btn" data-category="account">
                        <i data-lucide="shield-check"></i>
                        <span>Security & Account</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- FAQ Content Grid -->
        <div class="faq-accordion-container">
            <div class="accordion modern-faq-list" id="faqAccordionList">

                <!-- Item 1: Selling -->
                <div class="accordion-item faq-item" data-category="selling">
                    <h2 class="accordion-header" id="headingOne">
                        <button class="accordion-button faq-accord-btn" type="button" data-bs-toggle="collapse" data-bs-target="#faqColOne" aria-expanded="true" aria-controls="faqColOne">
                            <div class="faq-icon-badge badge--blue">
                                <i data-lucide="home"></i>
                            </div>
                            <span class="faq-question-text">How do I list a property for sale as a Seller?</span>
                        </button>
                    </h2>
                    <div id="faqColOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#faqAccordionList">
                        <div class="accordion-body faq-answer-text">
                            To publish a property listing, register or log in with a <strong>Seller Account</strong>. From your Seller Dashboard, select <em>"Add Property"</em> to specify your property type, pricing, bedrooms, bathrooms, and square footage. You can upload multiple high-resolution photos and choose a primary featured image. Once submitted, your listing is immediately indexed and visible in buyer search results.
                        </div>
                    </div>
                </div>

                <!-- Item 2: Buying -->
                <div class="accordion-item faq-item" data-category="buying">
                    <h2 class="accordion-header" id="headingTwo">
                        <button class="accordion-button faq-accord-btn collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqColTwo" aria-expanded="false" aria-controls="faqColTwo">
                            <div class="faq-icon-badge badge--emerald">
                                <i data-lucide="message-circle"></i>
                            </div>
                            <span class="faq-question-text">How do Buyers contact and communicate with Sellers?</span>
                        </button>
                    </h2>
                    <div id="faqColTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#faqAccordionList">
                        <div class="accordion-body faq-answer-text">
                            When viewing any listing page, prospective buyers can click the <em>"Send Inquiry"</em> form or initiate a real-time message with the property owner. You can inquire about scheduling private tours, price negotiations, or title documents. Sellers receive instant notifications and reply directly via their dashboard.
                        </div>
                    </div>
                </div>

                <!-- Item 3: Buying -->
                <div class="accordion-item faq-item" data-category="buying">
                    <h2 class="accordion-header" id="headingThree">
                        <button class="accordion-button faq-accord-btn collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqColThree" aria-expanded="false" aria-controls="faqColThree">
                            <div class="faq-icon-badge badge--purple">
                                <i data-lucide="heart"></i>
                            </div>
                            <span class="faq-question-text">How do I save and favorite properties to review later?</span>
                        </button>
                    </h2>
                    <div id="faqColThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#faqAccordionList">
                        <div class="accordion-body faq-answer-text">
                            Whenever you encounter a property you love, click the heart-shaped <em>"Favorite"</em> button on the property card or details view. All your saved listings are neatly cataloged in your Buyer Dashboard under <strong>My Favorites</strong>, allowing you to compare valuations and monitor status changes.
                        </div>
                    </div>
                </div>

                <!-- Item 4: Account & Security -->
                <div class="accordion-item faq-item" data-category="account">
                    <h2 class="accordion-header" id="headingFour">
                        <button class="accordion-button faq-accord-btn collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqColFour" aria-expanded="false" aria-controls="faqColFour">
                            <div class="faq-icon-badge badge--amber">
                                <i data-lucide="wallet"></i>
                            </div>
                            <span class="faq-question-text">Is XTateProsystem completely free to use?</span>
                        </button>
                    </h2>
                    <div id="faqColFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#faqAccordionList">
                        <div class="accordion-body faq-answer-text">
                            Yes! Exploring listings, running filtered searches, registering buyer accounts, sending messages, and submitting inquiries is 100% free of charge. Sellers can also publish properties with standard listing privileges at no upfront cost.
                        </div>
                    </div>
                </div>

                <!-- Item 5: Account & Security -->
                <div class="accordion-item faq-item" data-category="account">
                    <h2 class="accordion-header" id="headingFive">
                        <button class="accordion-button faq-accord-btn collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqColFive" aria-expanded="false" aria-controls="faqColFive">
                            <div class="faq-icon-badge badge--red">
                                <i data-lucide="flag"></i>
                            </div>
                            <span class="faq-question-text">How do I report a suspicious or fraudulent property listing?</span>
                        </button>
                    </h2>
                    <div id="faqColFive" class="accordion-collapse collapse" aria-labelledby="headingFive" data-bs-parent="#faqAccordionList">
                        <div class="accordion-body faq-answer-text">
                            If you suspect incorrect specifications, copyright infringement on images, or suspicious pricing, click the <em>"Report Listing"</em> link on the property details page. You can specify a reason and brief explanation. Our moderation team reviews all flagged items within 24 hours.
                        </div>
                    </div>
                </div>

                <!-- Item 6: Selling -->
                <div class="accordion-item faq-item" data-category="selling">
                    <h2 class="accordion-header" id="headingSix">
                        <button class="accordion-button faq-accord-btn collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqColSix" aria-expanded="false" aria-controls="faqColSix">
                            <div class="faq-icon-badge badge--blue">
                                <i data-lucide="refresh-cw"></i>
                            </div>
                            <span class="faq-question-text">How do I mark a listing as 'Sold' or 'Pending'?</span>
                        </button>
                    </h2>
                    <div id="faqColSix" class="accordion-collapse collapse" aria-labelledby="headingSix" data-bs-parent="#faqAccordionList">
                        <div class="accordion-body faq-answer-text">
                            In your Seller Dashboard, navigate to <strong>My Properties</strong> and select the <em>"Edit"</em> action on the target listing. Use the <strong>Status</strong> dropdown to update it to <em>Active</em>, <em>Pending</em>, or <em>Sold</em>, and save your changes.
                        </div>
                    </div>
                </div>

            </div>

            <!-- Still Have Questions Bento Card -->
            <div class="faq-help-card">
                <div class="faq-help-content">
                    <div class="faq-help-icon">
                        <i data-lucide="life-buoy"></i>
                    </div>
                    <div>
                        <h3 class="faq-help-title">Still Need Assistance?</h3>
                        <p class="faq-help-desc">Our client support team is available Monday through Saturday to assist with inquiries, account verification, and technical support.</p>
                    </div>
                </div>
                <div class="faq-help-actions">
                    <a href="contact.php" class="btn-help-contact">
                        <i data-lucide="mail" style="width:16px;height:16px;"></i>
                        <span>Contact Support</span>
                    </a>
                </div>
            </div>

        </div>

    </div>
</div>

<style>
/* ── FAQ Bento Styles ── */
.faq-page-wrap {
    background-color: #F8FAFC;
    min-height: calc(100vh - 70px);
    padding: 32px 0 64px;
    font-family: 'Inter', sans-serif;
    color: #334155;
}

.faq-page-container {
    max-width: 980px;
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
.faq-hero-card {
    background: linear-gradient(135deg, #1E293B 0%, #0F172A 100%);
    border-radius: 20px;
    padding: 40px 30px;
    color: #FFFFFF;
    margin-bottom: 36px;
    box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.15);
}

.faq-hero-tag {
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

.faq-hero-title {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 2rem;
    font-weight: 800;
    color: #FFFFFF;
    margin: 0 0 10px;
    letter-spacing: -0.02em;
}

.faq-hero-desc {
    font-size: 0.95rem;
    color: #94A3B8;
    max-width: 640px;
    margin: 0 auto 28px;
    line-height: 1.55;
}

/* Category Filter Pills */
.faq-filter-pills {
    display: inline-flex;
    background: rgba(255, 255, 255, 0.08);
    padding: 5px;
    border-radius: 14px;
    gap: 6px;
    flex-wrap: wrap;
    justify-content: center;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.faq-pill-btn {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    background: transparent;
    border: none;
    color: #CBD5E1;
    font-size: 0.82rem;
    font-weight: 600;
    padding: 8px 16px;
    border-radius: 10px;
    cursor: pointer;
    transition: all .2s ease;
}
.faq-pill-btn svg {
    width: 15px;
    height: 15px;
}
.faq-pill-btn:hover {
    color: #FFFFFF;
    background: rgba(255, 255, 255, 0.1);
}
.faq-pill-btn.active {
    background: #2563EB;
    color: #FFFFFF;
    box-shadow: 0 2px 8px rgba(37, 99, 235, 0.4);
}

/* Accordion List */
.modern-faq-list {
    display: flex;
    flex-direction: column;
    gap: 14px;
    margin-bottom: 40px;
}

.faq-item {
    background: #FFFFFF;
    border: 1px solid #E2E8F0 !important;
    border-radius: 16px !important;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
    transition: all .2s ease;
}
.faq-item:hover {
    border-color: #CBD5E1 !important;
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.06);
}

.faq-accord-btn {
    background: #FFFFFF !important;
    padding: 20px 24px;
    display: flex;
    align-items: center;
    gap: 14px;
    border: none !important;
    box-shadow: none !important;
}

.faq-icon-badge {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.faq-icon-badge svg {
    width: 18px;
    height: 18px;
}
.badge--blue    { background: #EFF6FF; color: #2563EB; }
.badge--emerald { background: #ECFDF5; color: #059669; }
.badge--purple  { background: #FAF5FF; color: #7C3AED; }
.badge--amber   { background: #FFFBEB; color: #D97706; }
.badge--red     { background: #FEF2F2; color: #DC2626; }

.faq-question-text {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 1rem;
    font-weight: 700;
    color: #0F172A;
    margin: 0;
    text-align: left;
}

.faq-answer-text {
    padding: 0 24px 22px 76px;
    color: #475569;
    font-size: 0.9rem;
    line-height: 1.65;
}

@media (max-width: 768px) {
    .faq-answer-text {
        padding-left: 24px;
    }
}

/* Help Card */
.faq-help-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 18px;
    padding: 28px 32px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 20px;
    box-shadow: 0 2px 6px rgba(15, 23, 42, 0.05);
}

.faq-help-content {
    display: flex;
    align-items: center;
    gap: 18px;
    max-width: 600px;
}

.faq-help-icon {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    background: #EFF6FF;
    color: #2563EB;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.faq-help-icon svg {
    width: 26px;
    height: 26px;
}

.faq-help-title {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 1.15rem;
    font-weight: 800;
    color: #0F172A;
    margin: 0 0 4px;
}

.faq-help-desc {
    font-size: 0.85rem;
    color: #64748B;
    margin: 0;
    line-height: 1.45;
}

.btn-help-contact {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #2563EB;
    color: #FFFFFF;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 0.88rem;
    font-weight: 700;
    padding: 11px 22px;
    border-radius: 10px;
    text-decoration: none;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
    transition: all .2s ease;
}
.btn-help-contact:hover {
    background: #1D4ED8;
    color: #FFFFFF;
    transform: translateY(-1px);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    // Category filter pills
    const pillButtons = document.querySelectorAll('.faq-pill-btn');
    const faqItems = document.querySelectorAll('.faq-item');

    pillButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            pillButtons.forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            const selectedCategory = this.getAttribute('data-category');

            faqItems.forEach(item => {
                const itemCat = item.getAttribute('data-category');
                if (selectedCategory === 'all' || itemCat === selectedCategory) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    });
});
</script>

<?php include 'inc/footer.php'; ?>
