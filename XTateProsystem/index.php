<?php
require_once 'inc/db.php';
require_once 'inc/functions.php';
require_once 'inc/auth.php';

// Start session
startSession();

// Get featured properties from Cloud Firestore
$featuredProperties = firestore_get_properties(['status' => 'active']);

// Get property types from Firestore
$propertyTypes = firestore_get_property_types();

// Get locations for search form
$locations = getLocations();

include 'inc/header.php';
?>

<!-- ============================================================
     PHASE 2: BENTO HERO SECTION
     ============================================================ -->
<section class="xt-hero-section">
    <div class="container">

        <!-- ── BENTO HERO GRID ── -->
        <div class="xt-hero-bento">

            <!-- Card 1 · Main Narrative + Frosted Search (Large Left) -->
            <div class="xt-hero-card xt-hero-main reveal-on-scroll">
                <div class="xt-hero-tag">
                    <i data-lucide="shield-check" style="width:13px;height:13px;"></i>
                    Verified Listings Only
                </div>
                <h1 class="xt-hero-headline">
                    Find Your<br>
                    <span class="xt-hero-accent">Dream&nbsp;Home</span>
                </h1>
                <p class="xt-hero-sub">
                    Browse our exclusive selection of verified properties and connect directly with trusted sellers across the Philippines.
                </p>

                <!-- Glassmorphic Search Form (action preserved) -->
                <form action="search.php" method="GET" class="xt-search-glass" id="heroSearchForm">
                    <div class="xt-search-row">
                        <!-- Location with Nominatim Autocomplete -->
                        <div class="xt-search-field xt-search-field--wide" style="position:relative;">
                            <label for="location" class="xt-search-label">
                                <i data-lucide="map-pin" style="width:12px;height:12px;"></i> Location
                            </label>
                            <input
                                type="text"
                                name="location"
                                id="location"
                                class="xt-search-input"
                                placeholder="City, barangay, or province…"
                                autocomplete="off"
                            >
                            <!-- Autocomplete dropdown -->
                            <ul id="locationSuggestions" class="xt-autocomplete" hidden></ul>
                        </div>

                        <!-- Property Type -->
                        <div class="xt-search-field">
                            <label for="property_type" class="xt-search-label">
                                <i data-lucide="home" style="width:12px;height:12px;"></i> Type
                            </label>
                            <select name="property_type" id="property_type" class="xt-search-select">
                                <option value="">All Types</option>
                                <?php foreach ($propertyTypes as $type): ?>
                                    <option value="<?= $type['id'] ?>"><?= htmlspecialchars($type['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Max Price -->
                        <div class="xt-search-field">
                            <label for="price_max" class="xt-search-label">
                                <i data-lucide="tag" style="width:12px;height:12px;"></i> Max Price
                            </label>
                            <select name="price_max" id="price_max" class="xt-search-select">
                                <option value="">No Limit</option>
                                <option value="100000">$100,000</option>
                                <option value="200000">$200,000</option>
                                <option value="300000">$300,000</option>
                                <option value="500000">$500,000</option>
                                <option value="750000">$750,000</option>
                                <option value="1000000">$1,000,000</option>
                                <option value="2000000">$2,000,000+</option>
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="xt-search-btn" id="heroSearchBtn">
                        <i data-lucide="search" style="width:16px;height:16px;"></i>
                        Search Properties
                    </button>
                </form>

                <!-- Quick category pills -->
                <div class="xt-hero-pills">
                    <a href="search.php?property_type=1" class="xt-pill">🏠 Residential</a>
                    <a href="search.php?property_type=6" class="xt-pill">🏢 Commercial</a>
                    <a href="search.php?property_type=5" class="xt-pill">🌿 Land</a>
                    <a href="search.php" class="xt-pill">✨ All Listings</a>
                </div>
            </div>

            <!-- Card 2 · Market Pulse Stats -->
            <div class="xt-hero-card xt-hero-stats reveal-on-scroll" style="animation-delay:0.1s;">
                <p class="xt-bento-label">Market Pulse</p>
                <div class="xt-stats-grid">
                    <div class="xt-stat-item">
                        <span class="xt-stat-num" id="stat-listings" data-target="<?= count($featuredProperties) ?: 32 ?>">0</span>
                        <span class="xt-stat-desc">Active Listings</span>
                    </div>
                    <div class="xt-stat-item">
                        <span class="xt-stat-num" id="stat-buyers" data-target="250">0</span>
                        <span class="xt-stat-desc">Happy Buyers</span>
                    </div>
                    <div class="xt-stat-item">
                        <span class="xt-stat-num" id="stat-sellers" data-target="48">0</span>
                        <span class="xt-stat-desc">Verified Sellers</span>
                    </div>
                    <div class="xt-stat-item">
                        <span class="xt-stat-num" id="stat-rate">98%</span>
                        <span class="xt-stat-desc">Satisfaction Rate</span>
                    </div>
                </div>
                <div class="xt-stats-badge">
                    <i data-lucide="trending-up" style="width:14px;height:14px;color:#10B981;"></i>
                    <span>Growing every month</span>
                </div>
            </div>

            <!-- Card 3 · Category Explorer -->
            <div class="xt-hero-card xt-hero-categories reveal-on-scroll" style="animation-delay:0.2s;">
                <p class="xt-bento-label">Browse by Type</p>
                <div class="xt-cat-list">
                    <?php
                    $catIcons = ['home','building-2','building','trees','landmark','warehouse'];
                    $catColors = ['#2563EB','#7C3AED','#059669','#D97706','#DC2626','#0284C7'];
                    foreach ($propertyTypes as $i => $type):
                        $icon = $catIcons[$i % count($catIcons)];
                        $color = $catColors[$i % count($catColors)];
                    ?>
                    <a href="search.php?property_type=<?= $type['id'] ?>" class="xt-cat-item">
                        <span class="xt-cat-icon" style="background:<?= $color ?>18;color:<?= $color ?>;">
                            <i data-lucide="<?= $icon ?>" style="width:18px;height:18px;"></i>
                        </span>
                        <span class="xt-cat-name"><?= htmlspecialchars($type['name']) ?></span>
                        <i data-lucide="chevron-right" class="xt-cat-arrow" style="width:14px;height:14px;"></i>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Card 4 · Quick Actions / CTA -->
            <div class="xt-hero-card xt-hero-cta reveal-on-scroll" style="animation-delay:0.3s;">
                <div class="xt-cta-icon">
                    <i data-lucide="zap" style="width:24px;height:24px;color:#2563EB;"></i>
                </div>
                <h3 class="xt-cta-title">List Your Property</h3>
                <p class="xt-cta-body">Reach thousands of serious buyers. Get your property listed in minutes.</p>
                <a href="<?= isLoggedIn() && $_SESSION['user_role'] === 'seller' ? 'seller/add_property.php' : 'register.php' ?>"
                   class="xt-cta-btn">
                    Get Started
                    <i data-lucide="arrow-right" style="width:15px;height:15px;"></i>
                </a>
            </div>

        </div>
        <!-- end .xt-hero-bento -->

    </div>
</section>


<!-- ============================================================
     FEATURED PROPERTIES — Modern Bento Cards
     ============================================================ -->
<section class="xt-section">
    <div class="container">
        <div class="xt-section-header">
            <div>
                <h2 class="section-title">Featured Properties</h2>
                <p style="color:var(--text-muted);font-size:0.9rem;margin-top:-1rem;">Hand-picked listings from verified sellers</p>
            </div>
            <a href="search.php" class="xt-view-all">
                View All <i data-lucide="arrow-right" style="width:15px;height:15px;"></i>
            </a>
        </div>

        <?php if (empty($featuredProperties)): ?>
            <div class="alert alert-info">
                <i data-lucide="info" style="width:16px;height:16px;margin-right:6px;"></i>
                No properties available at the moment. Please check back later.
            </div>
        <?php else: ?>
            <div class="xt-prop-grid">
                <?php foreach ($featuredProperties as $i => $property): ?>
                <div class="xt-prop-card reveal-on-scroll" style="animation-delay:<?= ($i * 0.07) ?>s;">
                    <!-- Image with hover zoom -->
                    <a href="property_details.php?id=<?= $property['id'] ?>" class="xt-prop-img-wrap">
                        <div class="property-image xt-prop-img"
                             style="background-image: url('<?= htmlspecialchars($property['primary_image'] ?? 'https://images.unsplash.com/photo-1560518883-ce09059eeffa?w=600') ?>');"></div>
                        <!-- Price pill -->
                        <div class="xt-prop-price-pill"><?= formatCurrency($property['price']) ?></div>
                        <!-- Type badge -->
                        <div class="xt-prop-type-badge"><?= htmlspecialchars($property['property_type'] ?? 'Property') ?></div>
                    </a>

                    <!-- Card Content -->
                    <div class="xt-prop-body">
                        <h3 class="xt-prop-title">
                            <a href="property_details.php?id=<?= $property['id'] ?>"><?= htmlspecialchars($property['title']) ?></a>
                        </h3>
                        <div class="xt-prop-location">
                            <i data-lucide="map-pin" style="width:12px;height:12px;flex-shrink:0;"></i>
                            <?= htmlspecialchars($property['city']) ?>, <?= htmlspecialchars($property['state']) ?>
                        </div>

                        <!-- Spec pills -->
                        <div class="property-features">
                            <div class="feature">
                                <i data-lucide="bed-double" style="width:13px;height:13px;"></i>
                                <?= $property['bedrooms'] ?> Beds
                            </div>
                            <div class="feature">
                                <i data-lucide="bath" style="width:13px;height:13px;"></i>
                                <?= $property['bathrooms'] ?> Baths
                            </div>
                            <div class="feature">
                                <i data-lucide="maximize-2" style="width:13px;height:13px;"></i>
                                <?php $idxArea = (float)($property['area'] ?? $property['area_sqft'] ?? $property['sqft'] ?? 0); ?>
                                <?= number_format($idxArea) ?> sqft
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="text-center mt-5">
                <a href="search.php" class="btn btn-outline-primary px-5">
                    View All Properties
                    <i data-lucide="arrow-right" style="width:15px;height:15px;margin-left:6px;vertical-align:-2px;"></i>
                </a>
            </div>
        <?php endif; ?>
    </div>
</section>


<!-- ============================================================
     PROPERTY TYPES — Bento Category Cards
     ============================================================ -->
<section class="xt-section xt-section--tinted">
    <div class="container">
        <div class="xt-section-header">
            <div>
                <h2 class="section-title">Browse by Type</h2>
                <p style="color:var(--text-muted);font-size:0.9rem;margin-top:-1rem;">Explore our curated property categories</p>
            </div>
        </div>
        <div class="xt-type-grid">
            <?php
            $typeImages = [
                'https://images.unsplash.com/photo-1564013799919-ab600027ffc6?w=600',
                'https://images.unsplash.com/photo-1510627489930-0c1b0bfb6785?w=600',
                'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=600',
                'https://images.unsplash.com/photo-1491357492920-d2979986a84e?w=600',
                'https://images.unsplash.com/photo-1472224371017-08207f84aaae?w=600',
                'https://images.unsplash.com/photo-1448630360428-65456885c650?w=600',
            ];
            foreach ($propertyTypes as $i => $type):
                $img = $typeImages[$i % count($typeImages)];
            ?>
            <a href="search.php?property_type=<?= $type['id'] ?>" class="xt-type-card reveal-on-scroll" style="animation-delay:<?= ($i * 0.08) ?>s;">
                <div class="xt-type-img" style="background-image:url('<?= $img ?>');"></div>
                <div class="xt-type-overlay">
                    <span class="xt-type-name"><?= htmlspecialchars($type['name']) ?></span>
                    <span class="xt-type-cta">Browse <i data-lucide="arrow-up-right" style="width:13px;height:13px;"></i></span>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>


<!-- ============================================================
     HOW IT WORKS — Modern 3-Step Bento
     ============================================================ -->
<section class="xt-section">
    <div class="container">
        <h2 class="section-title text-center" style="margin-bottom:0.5rem;">How It Works</h2>
        <p class="text-center" style="color:var(--text-muted);margin-bottom:3rem;">Simple steps to find and secure your perfect property.</p>

        <div class="xt-how-grid">
            <div class="xt-how-card bento-card reveal-on-scroll">
                <div class="xt-how-step">01</div>
                <div class="bento-icon">
                    <i data-lucide="search" style="width:20px;height:20px;"></i>
                </div>
                <h3 class="xt-how-title">Search Properties</h3>
                <p class="xt-how-body">Browse our extensive catalog using advanced filters — location, type, price range, and more. Find exactly what you need.</p>
            </div>

            <div class="xt-how-card bento-card reveal-on-scroll" style="animation-delay:0.1s;">
                <div class="xt-how-step">02</div>
                <div class="bento-icon bento-icon--accent">
                    <i data-lucide="message-circle" style="width:20px;height:20px;"></i>
                </div>
                <h3 class="xt-how-title">Connect with Sellers</h3>
                <p class="xt-how-body">Use our real-time messaging system to communicate directly with sellers, ask questions, and schedule viewings.</p>
            </div>

            <div class="xt-how-card bento-card reveal-on-scroll" style="animation-delay:0.2s;">
                <div class="xt-how-step">03</div>
                <div class="bento-icon bento-icon--warning">
                    <i data-lucide="key" style="width:20px;height:20px;"></i>
                </div>
                <h3 class="xt-how-title">Close the Deal</h3>
                <p class="xt-how-body">Schedule site visits, make offers, and finalize your purchase — all managed through the platform with full transparency.</p>
            </div>
        </div>
    </div>
</section>


<!-- ============================================================
     TESTIMONIALS — Refined Bento Cards
     ============================================================ -->
<section class="xt-section xt-section--tinted">
    <div class="container">
        <h2 class="section-title text-center" style="margin-bottom:0.5rem;">What Our Clients Say</h2>
        <p class="text-center" style="color:var(--text-muted);margin-bottom:3rem;">Real stories from real buyers and sellers.</p>

        <div class="xt-testi-grid">
            <!-- Testimonial 1 -->
            <div class="xt-testi-card bento-card reveal-on-scroll">
                <div class="xt-testi-stars">
                    <?php for($s=0;$s<5;$s++): ?><i data-lucide="star" class="xt-star-filled" style="width:14px;height:14px;"></i><?php endfor; ?>
                </div>
                <p class="xt-testi-text">"I found my dream home thanks to this platform! The search features made it easy to filter properties based on my specific requirements."</p>
                <div class="xt-testi-author">
                    <div class="xt-testi-avatar" style="background:linear-gradient(135deg,#2563EB,#7C3AED);">JA</div>
                    <div>
                        <p class="xt-testi-name">John Anderson</p>
                        <p class="xt-testi-role">Home Buyer</p>
                    </div>
                </div>
            </div>

            <!-- Testimonial 2 -->
            <div class="xt-testi-card bento-card reveal-on-scroll" style="animation-delay:0.1s;">
                <div class="xt-testi-stars">
                    <?php for($s=0;$s<5;$s++): ?><i data-lucide="star" class="xt-star-filled" style="width:14px;height:14px;"></i><?php endfor; ?>
                </div>
                <p class="xt-testi-text">"As a seller, I appreciate how easy it is to list properties and communicate with potential buyers. I sold my apartment within just two weeks!"</p>
                <div class="xt-testi-author">
                    <div class="xt-testi-avatar" style="background:linear-gradient(135deg,#059669,#0284C7);">SJ</div>
                    <div>
                        <p class="xt-testi-name">Sarah Johnson</p>
                        <p class="xt-testi-role">Property Seller</p>
                    </div>
                </div>
            </div>

            <!-- Testimonial 3 -->
            <div class="xt-testi-card bento-card reveal-on-scroll" style="animation-delay:0.2s;">
                <div class="xt-testi-stars">
                    <?php for($s=0;$s<4;$s++): ?><i data-lucide="star" class="xt-star-filled" style="width:14px;height:14px;"></i><?php endfor; ?>
                    <i data-lucide="star" class="xt-star-half" style="width:14px;height:14px;"></i>
                </div>
                <p class="xt-testi-text">"The real-time messaging feature made communication with the seller so convenient. I could ask questions and get immediate responses."</p>
                <div class="xt-testi-author">
                    <div class="xt-testi-avatar" style="background:linear-gradient(135deg,#D97706,#DC2626);">MC</div>
                    <div>
                        <p class="xt-testi-name">Michael Carter</p>
                        <p class="xt-testi-role">Home Buyer</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>


<!-- ============================================================
     CALL TO ACTION — Dark Bento Banner
     ============================================================ -->
<section class="xt-section">
    <div class="container">
        <div class="xt-cta-banner">
            <div class="xt-cta-banner-glow"></div>
            <div class="xt-cta-banner-content">
                <h2 class="xt-cta-banner-title">Ready to Find Your Dream Home?</h2>
                <p class="xt-cta-banner-sub">Join thousands of satisfied users who found their perfect property on our platform.</p>
                <div class="xt-cta-banner-actions">
                    <a href="search.php" class="xt-cta-banner-btn xt-cta-banner-btn--primary">
                        <i data-lucide="search" style="width:16px;height:16px;"></i>
                        Browse Properties
                    </a>
                    <?php if (!isLoggedIn()): ?>
                    <a href="register.php" class="xt-cta-banner-btn xt-cta-banner-btn--ghost">
                        Get Started — It's Free
                        <i data-lucide="arrow-right" style="width:16px;height:16px;"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>


<!-- ============================================================
     PHASE 2 INLINE STYLES
     ============================================================ -->
<style>
/* ── Hero Section ── */
.xt-hero-section {
    background: linear-gradient(160deg, #0F172A 0%, #1E293B 60%, #0F2252 100%);
    padding: 5rem 0 4rem;
    position: relative;
    overflow: hidden;
}
.xt-hero-section::before {
    content: '';
    position: absolute;
    width: 700px; height: 700px;
    background: radial-gradient(circle, rgba(37,99,235,0.18) 0%, transparent 70%);
    top: -200px; right: -100px;
    pointer-events: none;
}
.xt-hero-section::after {
    content: '';
    position: absolute;
    width: 500px; height: 500px;
    background: radial-gradient(circle, rgba(124,58,237,0.12) 0%, transparent 70%);
    bottom: -150px; left: -100px;
    pointer-events: none;
}

/* ── Bento Grid Layout ── */
.xt-hero-bento {
    display: grid;
    grid-template-columns: 1fr 1fr;
    grid-template-rows: auto auto;
    gap: 1rem;
    position: relative;
    z-index: 1;
}
.xt-hero-card {
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.10);
    border-radius: 20px;
    padding: 2rem;
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    transition: all 0.3s ease;
}
.xt-hero-card:hover { border-color: rgba(255,255,255,0.18); background: rgba(255,255,255,0.07); }

/* Card grid positions */
.xt-hero-main       { grid-column: 1; grid-row: 1 / span 2; }
.xt-hero-stats      { grid-column: 2; grid-row: 1; }
.xt-hero-categories { display: none; } /* shown on wider screens */
.xt-hero-cta        { grid-column: 2; grid-row: 2; }

/* ── Hero Typography ── */
.xt-hero-tag {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.72rem;
    font-weight: 600;
    color: #10B981;
    background: rgba(16,185,129,0.12);
    border: 1px solid rgba(16,185,129,0.25);
    padding: 4px 12px;
    border-radius: 100px;
    margin-bottom: 1.25rem;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}
.xt-hero-headline {
    font-family: var(--font-display);
    font-size: clamp(2rem, 4.5vw, 3.2rem);
    font-weight: 800;
    line-height: 1.1;
    letter-spacing: -0.04em;
    color: white;
    margin-bottom: 1rem;
}
.xt-hero-accent {
    background: linear-gradient(135deg, #60A5FA, #818CF8);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
.xt-hero-sub {
    font-size: 0.95rem;
    color: rgba(255,255,255,0.65);
    margin-bottom: 1.75rem;
    line-height: 1.7;
    max-width: 480px;
}

/* ── Glass Search Form ── */
.xt-search-glass {
    background: rgba(255,255,255,0.07);
    border: 1px solid rgba(255,255,255,0.14);
    border-radius: 16px;
    padding: 1.25rem;
    margin-bottom: 1.25rem;
}
.xt-search-row {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr;
    gap: 0.75rem;
    margin-bottom: 0.75rem;
}
.xt-search-label {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: rgba(255,255,255,0.55);
    margin-bottom: 0.35rem;
}
.xt-search-input, .xt-search-select {
    width: 100%;
    background: rgba(255,255,255,0.09);
    border: 1px solid rgba(255,255,255,0.14);
    border-radius: 10px;
    padding: 0.55rem 0.9rem;
    font-size: 0.875rem;
    color: white;
    font-family: var(--font-body);
    transition: all 0.2s ease;
    -webkit-appearance: none;
}
.xt-search-input::placeholder { color: rgba(255,255,255,0.35); }
.xt-search-input:focus, .xt-search-select:focus {
    outline: none;
    border-color: #60A5FA;
    background: rgba(255,255,255,0.13);
    box-shadow: 0 0 0 3px rgba(96,165,250,0.15);
}
.xt-search-select option { background: #1E293B; color: white; }
.xt-search-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
    padding: 0.7rem 1.5rem;
    background: var(--brand-primary);
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 0.9rem;
    font-weight: 600;
    font-family: var(--font-body);
    cursor: pointer;
    transition: all 0.25s ease;
}
.xt-search-btn:hover {
    background: var(--brand-primary-h);
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(37,99,235,0.35);
}

/* Nominatim autocomplete dropdown */
.xt-autocomplete {
    position: absolute;
    top: calc(100% + 4px);
    left: 0; right: 0;
    background: #1E2D45;
    border: 1px solid rgba(255,255,255,0.15);
    border-radius: 12px;
    z-index: 200;
    list-style: none;
    margin: 0; padding: 0.4rem;
    max-height: 220px;
    overflow-y: auto;
    box-shadow: 0 16px 40px rgba(0,0,0,0.4);
}
.xt-autocomplete li {
    padding: 0.5rem 0.75rem;
    font-size: 0.82rem;
    color: rgba(255,255,255,0.8);
    border-radius: 8px;
    cursor: pointer;
    transition: background 0.15s;
}
.xt-autocomplete li:hover { background: rgba(255,255,255,0.1); color: white; }

/* ── Quick Category Pills ── */
.xt-hero-pills {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}
.xt-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 0.78rem;
    font-weight: 500;
    color: rgba(255,255,255,0.65);
    background: rgba(255,255,255,0.07);
    border: 1px solid rgba(255,255,255,0.12);
    padding: 0.3rem 0.85rem;
    border-radius: 100px;
    text-decoration: none;
    transition: all 0.2s ease;
}
.xt-pill:hover {
    color: white;
    background: rgba(255,255,255,0.14);
    border-color: rgba(255,255,255,0.25);
}

/* ── Stats Card ── */
.xt-bento-label {
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: rgba(255,255,255,0.4);
    margin-bottom: 1rem;
}
.xt-stats-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1.25rem;
}
.xt-stat-item { display: flex; flex-direction: column; gap: 2px; }
.xt-stat-num {
    font-family: var(--font-display);
    font-size: 1.85rem;
    font-weight: 800;
    color: white;
    line-height: 1;
    letter-spacing: -0.03em;
}
.xt-stat-desc {
    font-size: 0.72rem;
    color: rgba(255,255,255,0.45);
    font-weight: 500;
}
.xt-stats-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.78rem;
    color: rgba(255,255,255,0.55);
    background: rgba(16,185,129,0.1);
    border: 1px solid rgba(16,185,129,0.2);
    padding: 0.3rem 0.75rem;
    border-radius: 100px;
}

/* ── Category List Card ── */
.xt-cat-list { display: flex; flex-direction: column; gap: 0.4rem; }
.xt-cat-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.5rem 0.6rem;
    border-radius: 10px;
    text-decoration: none;
    color: rgba(255,255,255,0.75);
    transition: all 0.18s ease;
    font-size: 0.875rem;
    font-weight: 500;
}
.xt-cat-item:hover { background: rgba(255,255,255,0.08); color: white; }
.xt-cat-icon {
    width: 34px; height: 34px;
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.xt-cat-name { flex: 1; }
.xt-cat-arrow { opacity: 0.3; transition: opacity 0.18s; }
.xt-cat-item:hover .xt-cat-arrow { opacity: 0.7; }

/* ── CTA Card ── */
.xt-cta-icon {
    width: 48px; height: 48px;
    background: rgba(37,99,235,0.15);
    border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    margin-bottom: 1rem;
}
.xt-cta-title {
    font-family: var(--font-display);
    font-size: 1.1rem;
    font-weight: 700;
    color: white;
    margin-bottom: 0.5rem;
}
.xt-cta-body { font-size: 0.82rem; color: rgba(255,255,255,0.5); margin-bottom: 1.25rem; line-height: 1.6; }
.xt-cta-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 0.6rem 1.25rem;
    background: var(--brand-primary);
    color: white;
    border-radius: 10px;
    font-size: 0.875rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.25s ease;
}
.xt-cta-btn:hover { background: var(--brand-primary-h); transform: translateY(-1px); color: white; box-shadow: 0 6px 20px rgba(37,99,235,0.3); }

/* ── Shared Section Wrappers ── */
.xt-section { padding: 5rem 0; }
.xt-section--tinted { background: var(--surface-raised); }
.xt-section-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    margin-bottom: 2rem;
}
.xt-view-all {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--brand-primary);
    text-decoration: none;
    transition: gap 0.2s ease;
}
.xt-view-all:hover { gap: 10px; color: var(--brand-primary-h); }

/* ── Property Grid ── */
.xt-prop-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1.25rem;
}
.xt-prop-card {
    background: var(--surface-card);
    border: 1px solid var(--border-subtle);
    border-radius: var(--radius-xl);
    overflow: hidden;
    transition: var(--transition-smooth);
}
.xt-prop-card:hover { transform: translateY(-6px); box-shadow: var(--shadow-hover); border-color: var(--border-soft); }

.xt-prop-img-wrap { display: block; position: relative; overflow: hidden; text-decoration: none; }
.xt-prop-img {
    height: 210px;
    background-size: cover;
    background-position: center;
    transition: transform 0.45s cubic-bezier(0.4,0,0.2,1);
}
.xt-prop-card:hover .xt-prop-img { transform: scale(1.06); }

.xt-prop-price-pill {
    position: absolute;
    bottom: 12px; right: 12px;
    background: rgba(10,20,40,0.82);
    backdrop-filter: blur(8px);
    color: white;
    font-family: var(--font-display);
    font-size: 0.875rem;
    font-weight: 700;
    padding: 0.3rem 0.75rem;
    border-radius: 100px;
    border: 1px solid rgba(255,255,255,0.15);
}
.xt-prop-type-badge {
    position: absolute;
    top: 12px; left: 12px;
    background: rgba(37,99,235,0.88);
    color: white;
    font-size: 0.68rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    padding: 0.2rem 0.6rem;
    border-radius: 100px;
}

.xt-prop-body { padding: 1.25rem; }
.xt-prop-title {
    font-family: var(--font-display);
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--text-main);
    margin-bottom: 0.35rem;
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.xt-prop-title a { color: inherit; text-decoration: none; }
.xt-prop-title a:hover { color: var(--brand-primary); }
.xt-prop-location {
    display: flex; align-items: center; gap: 4px;
    font-size: 0.78rem;
    color: var(--text-muted);
    font-weight: 500;
    margin-bottom: 0.875rem;
}
.xt-prop-location svg { color: var(--brand-primary); }

/* ── Property Types Grid ── */
.xt-type-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 1rem;
}
.xt-type-card {
    position: relative;
    border-radius: var(--radius-xl);
    overflow: hidden;
    text-decoration: none;
    display: block;
    aspect-ratio: 4/3;
    transition: var(--transition-smooth);
}
.xt-type-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-hover); }
.xt-type-img {
    position: absolute; inset: 0;
    background-size: cover; background-position: center;
    transition: transform 0.45s ease;
}
.xt-type-card:hover .xt-type-img { transform: scale(1.06); }
.xt-type-overlay {
    position: absolute; inset: 0;
    background: linear-gradient(to top, rgba(10,20,40,0.85) 0%, rgba(10,20,40,0.2) 50%, transparent 100%);
    display: flex; flex-direction: column;
    justify-content: flex-end;
    padding: 1.25rem;
    gap: 4px;
}
.xt-type-name { font-family: var(--font-display); font-weight: 700; font-size: 1rem; color: white; }
.xt-type-cta {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: 0.75rem; font-weight: 600;
    color: rgba(255,255,255,0.65);
    transition: color 0.2s;
}
.xt-type-card:hover .xt-type-cta { color: white; }

/* ── How It Works ── */
.xt-how-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.25rem;
}
.xt-how-card { padding: 2rem; position: relative; }
.xt-how-step {
    font-family: var(--font-display);
    font-size: 3.5rem;
    font-weight: 800;
    color: var(--border-subtle);
    line-height: 1;
    position: absolute;
    top: 1.25rem; right: 1.5rem;
    letter-spacing: -0.04em;
}
.xt-how-title {
    font-family: var(--font-display);
    font-size: 1rem;
    font-weight: 700;
    color: var(--text-main);
    margin: 0.75rem 0 0.5rem;
}
.xt-how-body { font-size: 0.875rem; color: var(--text-secondary); line-height: 1.7; margin: 0; }

/* ── Testimonials ── */
.xt-testi-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.25rem;
}
.xt-testi-card { padding: 1.75rem; }
.xt-testi-stars { display: flex; gap: 3px; margin-bottom: 1rem; }
.xt-star-filled { color: #F59E0B; fill: #F59E0B; }
.xt-star-half   { color: #F59E0B; }
.xt-testi-text  { font-size: 0.875rem; color: var(--text-secondary); line-height: 1.75; margin-bottom: 1.25rem; font-style: italic; }
.xt-testi-author { display: flex; align-items: center; gap: 0.75rem; }
.xt-testi-avatar {
    width: 40px; height: 40px;
    border-radius: 50%;
    color: white;
    font-family: var(--font-display);
    font-weight: 700; font-size: 0.8rem;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.xt-testi-name { font-weight: 700; font-size: 0.875rem; color: var(--text-main); margin: 0; }
.xt-testi-role { font-size: 0.75rem; color: var(--text-muted); margin: 0; }

/* ── CTA Banner ── */
.xt-cta-banner {
    background: var(--text-main);
    border-radius: var(--radius-2xl);
    padding: 4rem 3rem;
    text-align: center;
    position: relative;
    overflow: hidden;
}
.xt-cta-banner-glow {
    position: absolute;
    width: 600px; height: 600px;
    background: radial-gradient(circle, rgba(37,99,235,0.25) 0%, transparent 65%);
    top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    pointer-events: none;
}
.xt-cta-banner-content { position: relative; z-index: 1; }
.xt-cta-banner-title {
    font-family: var(--font-display);
    font-size: clamp(1.5rem, 3vw, 2.25rem);
    font-weight: 800;
    color: white;
    margin-bottom: 0.75rem;
    letter-spacing: -0.03em;
}
.xt-cta-banner-sub { font-size: 1rem; color: rgba(255,255,255,0.6); margin-bottom: 2rem; }
.xt-cta-banner-actions { display: flex; justify-content: center; gap: 1rem; flex-wrap: wrap; }
.xt-cta-banner-btn {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 0.75rem 1.75rem;
    border-radius: var(--radius-pill);
    font-size: 0.9rem;
    font-weight: 600;
    font-family: var(--font-body);
    text-decoration: none;
    transition: all 0.25s ease;
}
.xt-cta-banner-btn--primary {
    background: var(--brand-primary);
    color: white;
}
.xt-cta-banner-btn--primary:hover { background: var(--brand-primary-h); transform: translateY(-2px); box-shadow: 0 8px 24px rgba(37,99,235,0.4); color: white; }
.xt-cta-banner-btn--ghost {
    background: rgba(255,255,255,0.08);
    color: rgba(255,255,255,0.85);
    border: 1px solid rgba(255,255,255,0.18);
}
.xt-cta-banner-btn--ghost:hover { background: rgba(255,255,255,0.15); color: white; }

/* ── Responsive Overrides ── */
@media (max-width: 991.98px) {
    .xt-hero-bento { grid-template-columns: 1fr; }
    .xt-hero-main  { grid-column: 1; grid-row: 1; }
    .xt-hero-stats { grid-column: 1; grid-row: 2; }
    .xt-hero-cta   { grid-column: 1; grid-row: 3; }
    .xt-how-grid   { grid-template-columns: 1fr; }
    .xt-testi-grid { grid-template-columns: 1fr; }
    .xt-search-row { grid-template-columns: 1fr; }
    .xt-cta-banner { padding: 2.5rem 1.5rem; }
    .xt-hero-section { padding: 3rem 0; }
}
@media (max-width: 767.98px) {
    .xt-testi-grid  { grid-template-columns: 1fr; }
    .xt-type-grid   { grid-template-columns: 1fr 1fr; }
    .xt-section-header { flex-direction: column; align-items: flex-start; gap: 0.75rem; }
}
@media (max-width: 575.98px) {
    .xt-type-grid   { grid-template-columns: 1fr; }
    .xt-stats-grid  { grid-template-columns: 1fr 1fr; }
    .xt-hero-headline { font-size: 1.75rem; }
}
</style>


<!-- ============================================================
     PHASE 2 INLINE JAVASCRIPT
     (CountUp stats, Nominatim autocomplete, Scroll reveal)
     ============================================================ -->
<script>
document.addEventListener('DOMContentLoaded', function () {

    /* ── Re-init Lucide icons for dynamically rendered content ── */
    if (typeof lucide !== 'undefined') lucide.createIcons();

    /* ── 1. COUNTUP.JS — Animated stat counters ── */
    function startCountUps() {
        const statEls = document.querySelectorAll('.xt-stat-num[data-target]');
        statEls.forEach(function (el) {
            const target = parseInt(el.getAttribute('data-target'), 10);
            if (isNaN(target)) return;
            const suffix = el.getAttribute('data-suffix') || '';
            if (typeof CountUp !== 'undefined') {
                const cu = new CountUp.CountUp(el, target, {
                    duration: 2.2,
                    useEasing: true,
                    suffix: suffix,
                });
                if (!cu.error) cu.start();
            } else {
                el.textContent = target.toLocaleString() + suffix;
            }
        });
    }

    /* Trigger counter only when stats card is in viewport */
    const statsCard = document.querySelector('.xt-hero-stats');
    if (statsCard && 'IntersectionObserver' in window) {
        const io = new IntersectionObserver(function (entries) {
            if (entries[0].isIntersecting) {
                startCountUps();
                io.disconnect();
            }
        }, { threshold: 0.3 });
        io.observe(statsCard);
    } else {
        startCountUps();
    }


    /* ── 2. SCROLL REVEAL — Fade-in cards on scroll ── */
    const revealEls = document.querySelectorAll('.reveal-on-scroll');
    if ('IntersectionObserver' in window) {
        const revealIO = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    revealIO.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });
        revealEls.forEach(function (el) { revealIO.observe(el); });
    } else {
        revealEls.forEach(function (el) { el.classList.add('visible'); });
    }


    /* ── 3. NOMINATIM GEOCODING — Live location autocomplete ── */
    const locationInput  = document.getElementById('location');
    const suggestionList = document.getElementById('locationSuggestions');

    if (locationInput && suggestionList) {
        let debounceTimer;

        locationInput.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            const query = this.value.trim();

            if (query.length < 3) {
                suggestionList.hidden = true;
                return;
            }

            debounceTimer = setTimeout(function () {
                fetch('https://nominatim.openstreetmap.org/search?format=json&q=' +
                      encodeURIComponent(query) +
                      '&countrycodes=ph&limit=6&addressdetails=0', {
                    headers: { 'Accept-Language': 'en' }
                })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    suggestionList.innerHTML = '';
                    if (!data || data.length === 0) {
                        suggestionList.hidden = true;
                        return;
                    }
                    data.forEach(function (item) {
                        const li = document.createElement('li');
                        /* Trim long display names to stay readable */
                        const parts = item.display_name.split(',');
                        li.textContent = parts.slice(0, 3).join(',').trim();
                        li.addEventListener('click', function () {
                            locationInput.value = li.textContent;
                            suggestionList.hidden = true;
                        });
                        suggestionList.appendChild(li);
                    });
                    suggestionList.hidden = false;
                })
                .catch(function () { suggestionList.hidden = true; });
            }, 320); /* 320 ms debounce */
        });

        /* Hide suggestions when clicking outside */
        document.addEventListener('click', function (e) {
            if (!locationInput.contains(e.target) && !suggestionList.contains(e.target)) {
                suggestionList.hidden = true;
            }
        });

        /* Keyboard navigation */
        locationInput.addEventListener('keydown', function (e) {
            const items = suggestionList.querySelectorAll('li');
            const active = suggestionList.querySelector('li.xt-ac-active');
            let idx = Array.from(items).indexOf(active);
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (active) active.classList.remove('xt-ac-active');
                idx = (idx + 1) % items.length;
                items[idx] && items[idx].classList.add('xt-ac-active');
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                if (active) active.classList.remove('xt-ac-active');
                idx = (idx - 1 + items.length) % items.length;
                items[idx] && items[idx].classList.add('xt-ac-active');
            } else if (e.key === 'Enter' && active) {
                locationInput.value = active.textContent;
                suggestionList.hidden = true;
            } else if (e.key === 'Escape') {
                suggestionList.hidden = true;
            }
        });
    }


    /* ── 4. NOTYF — Initialize global toast instance ── */
    if (typeof Notyf !== 'undefined') {
        window.xtNotyf = new Notyf({
            duration: 3500,
            position: { x: 'right', y: 'top' },
            ripple: false,
            dismissible: true,
        });
    }

});
</script>

<?php include 'inc/footer.php'; ?>
