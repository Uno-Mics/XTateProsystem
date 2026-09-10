<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'inc/db.php';
require_once 'inc/functions.php';
require_once 'inc/auth.php';

startSession();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('search.php');
}

$propertyId = (int)$_GET['id'];

$property = firestore_get_property_by_id($propertyId);
if (!$property) {
    $property = getPropertyById($propertyId);
}

if (!$property || $property['status'] !== 'active') {
    redirect('search.php');
}

$propertyImages = getPropertyImages($propertyId);

$isFavorited = false;
if (isLoggedIn() && $_SESSION['user_role'] === 'buyer') {
    try {
        $conn = connectDB();
        if ($conn) {
            $userId = $_SESSION['user_id'];
            $stmt = $conn->prepare("SELECT id FROM favorites WHERE property_id = ? AND buyer_id = ?");
            $stmt->bind_param("ii", $propertyId, $userId);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $isFavorited = true;
            } else {
                $stmt = $conn->prepare("SELECT id FROM buyer_favorites WHERE property_id = ? AND buyer_id = ?");
                $stmt->bind_param("ii", $propertyId, $userId);
                $stmt->execute();
                if ($stmt->get_result()->num_rows > 0) $isFavorited = true;
            }
            $stmt->close();
            closeDB($conn);
        }
    } catch (Exception $e) {
        error_log("Error checking favorites: " . $e->getMessage());
    }
}

$errors  = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['inquiry_submit'])) {
    if (!isLoggedIn()) {
        redirect('login.php?redirect=' . urlencode('property_details.php?id=' . $propertyId));
    } elseif ($_SESSION['user_role'] !== 'buyer') {
        $errors[] = 'Only buyers can submit inquiries';
    }
    $message = sanitizeInput($_POST['inquiry_message'] ?? '');
    if (empty($message)) { $errors[] = 'Please enter your message'; }
    if (empty($errors)) {
        $sql = "INSERT INTO inquiries (property_id, buyer_id, message, status, created_at) VALUES (?, ?, ?, 'pending', NOW())";
        $inquiryId = insertData($sql, "iis", [$propertyId, $_SESSION['user_id'], $message]);
        if ($inquiryId) {
            $success = 'Your inquiry has been submitted successfully. The seller will respond soon.';
        } else {
            $errors[] = 'Failed to submit inquiry. Please try again.';
        }
    }
}

// Normalize all image paths once in PHP
$galleryImages = [];
foreach ($propertyImages as $image) {
    $p = $image['image_path'];
    if (substr($p, 0, 1) === '/')  $p = substr($p, 1);
    if (substr($p, 0, 2) === '//') $p = 'https:' . $p;
    elseif (substr($p, 0, 4) !== 'http') {
        if (strpos($p, '../uploads/') === 0) $p = substr($p, 3);
        if (substr($p, 0, 1) !== '/') $p = '/' . $p;
        $p = ltrim($p, '/');
    }
    $galleryImages[] = $p;
}
if (empty($galleryImages)) {
    $galleryImages[] = 'XTate-Image.png';
}

include 'inc/header.php';
?>

<style>
/* ============================================================
   PROPERTY DETAILS — Complete Self-Contained Styles
   ============================================================ */

.pd-wrap {
    background: #F8FAFC;
    min-height: 100vh;
    width: 100%;
    max-width: 100%;
    overflow-x: hidden;
}

/* Breadcrumb bar */
.pd-breadbar {
    background: #fff;
    border-bottom: 1px solid #E2E8F0;
    padding: 12px 0;
}
.pd-breadbar .container,
.pd-body .container {
    max-width: 1200px;
    width: 100%;
    margin-left: auto;
    margin-right: auto;
    padding-left: 16px;
    padding-right: 16px;
}
.pd-breadcrumb {
    display: flex; align-items: center; gap: 6px;
    list-style: none; margin: 0; padding: 0;
    font-size: 0.82rem;
}
.pd-breadcrumb li     { display: flex; align-items: center; gap: 6px; color: #94A3B8; }
.pd-breadcrumb a      { color: #64748B; text-decoration: none; font-weight: 500; }
.pd-breadcrumb a:hover{ color: #2563EB; }
.pd-breadcrumb .cur   { color: #0F172A; font-weight: 600; max-width: 320px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

/* ── Two-column layout (Strictly Constrained) ── */
.pd-body { padding: 24px 0 60px; width: 100%; }
.pd-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 350px;
    gap: 24px;
    align-items: start;
    width: 100%;
    max-width: 100%;
}
.pd-main {
    min-width: 0;
    width: 100%;
    max-width: 100%;
}
.pd-sidebar {
    min-width: 0;
    width: 100%;
    max-width: 100%;
}

/* ── Section Card helper ── */
.pd-card {
    background: #fff;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 1px 4px rgba(15,23,42,.06);
    margin-bottom: 16px;
    width: 100%;
    max-width: 100%;
}
.pd-card-inner { padding: 22px; }
.pd-card-title {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 0.95rem; font-weight: 700;
    color: #0F172A; margin-bottom: 16px;
    display: flex; align-items: center; gap: 7px;
    padding-bottom: 12px;
    border-bottom: 1px solid #F1F5F9;
}

/* ── Gallery ── */
.pd-gallery {
    margin-bottom: 16px;
    width: 100%;
    max-width: 100%;
    border-radius: 16px;
    overflow: hidden;
    background: #fff;
    border: 1px solid #E2E8F0;
    box-shadow: 0 1px 4px rgba(15,23,42,.06);
}
.pd-gallery-main {
    border-radius: 16px 16px 0 0;
    overflow: hidden;
    position: relative;
    height: 460px;
    width: 100%;
    max-width: 100%;
    background: #0F172A;
}
.pd-main-swiper {
    width: 100% !important;
    max-width: 100% !important;
    height: 100% !important;
    overflow: hidden !important;
}
.pd-main-swiper .swiper-wrapper {
    width: 100% !important;
    height: 100% !important;
}
.pd-main-swiper .swiper-slide {
    width: 100% !important;
    height: 100% !important;
    overflow: hidden !important;
    background: #0F172A;
}
.pd-main-swiper .swiper-slide a {
    display: block;
    width: 100%;
    height: 100%;
}
.pd-main-swiper .swiper-slide img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}
.pd-gallery-main > a {
    display: block;
    width: 100%;
    height: 100%;
}
.pd-gallery-main > a img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}
.pd-swiper-btn {
    position: absolute; top: 50%; transform: translateY(-50%);
    z-index: 10; width: 40px; height: 40px; border-radius: 50%;
    background: rgba(255,255,255,.9); backdrop-filter: blur(6px);
    border: 1px solid rgba(255,255,255,.6);
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; color: #0F172A; font-size: 0;
    transition: background .2s, box-shadow .2s;
    box-shadow: 0 2px 8px rgba(0,0,0,.12);
}
.pd-swiper-btn:hover { background: #fff; box-shadow: 0 4px 16px rgba(0,0,0,.18); }
.pd-swiper-prev { left: 12px; }
.pd-swiper-next { right: 12px; }
.pd-gallery-count {
    position: absolute; bottom: 12px; right: 12px; z-index: 5;
    background: rgba(10,20,40,.75); backdrop-filter: blur(6px);
    color: rgba(255,255,255,.9);
    font-size: 0.72rem; font-weight: 600;
    padding: 3px 10px; border-radius: 100px;
    display: flex; align-items: center; gap: 4px;
    pointer-events: none;
}

/* Thumbnail strip */
.pd-thumbs {
    display: flex; gap: 8px;
    padding: 10px;
    background: #F8FAFC;
    border-top: 1px solid #E2E8F0;
    border-radius: 0 0 16px 16px;
    overflow-x: auto;
    width: 100%;
    max-width: 100%;
}
.pd-thumb {
    width: 72px; height: 54px; flex-shrink: 0;
    border-radius: 8px; overflow: hidden;
    cursor: pointer; opacity: .6;
    transition: opacity .2s, border-color .2s;
    border: 2px solid transparent;
}
.pd-thumb:hover { opacity: .9; }
.pd-thumb.active { opacity: 1; border-color: #2563EB; }
.pd-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }

/* ── Property Header Card ── */
.pd-header { padding: 24px; }
.pd-badges { display: flex; gap: 6px; margin-bottom: 10px; flex-wrap: wrap; }
.pd-badge {
    font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em;
    padding: 4px 12px; border-radius: 100px;
}
.pd-badge--type   { background: #EFF6FF; color: #2563EB; }
.pd-badge--status { background: #ECFDF5; color: #059669; }

.pd-prop-title {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 1.75rem; font-weight: 800;
    color: #0F172A; margin-bottom: 6px;
    line-height: 1.25; letter-spacing: -0.03em;
}
.pd-prop-addr {
    display: flex; align-items: flex-start; gap: 6px;
    font-size: 0.875rem; color: #64748B; margin-bottom: 16px;
}
.pd-prop-price {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 1.95rem; font-weight: 800;
    color: #2563EB; letter-spacing: -0.04em;
    margin-bottom: 18px;
}

/* Specs strip */
.pd-specs {
    display: flex; align-items: center; gap: 0;
    padding: 16px 0 0;
    border-top: 1px solid #F1F5F9;
    flex-wrap: wrap;
}
.pd-spec {
    display: flex; align-items: center; gap: 8px;
    padding: 0 20px 0 0; margin-right: 20px;
    border-right: 1px solid #E2E8F0;
    flex-shrink: 0;
    margin-bottom: 6px;
}
.pd-spec:last-child { border-right: none; padding-right: 0; margin-right: 0; }
.pd-spec-val   { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 1.05rem; font-weight: 700; color: #0F172A; display: block; line-height: 1.2; }
.pd-spec-label { font-size: 0.68rem; color: #94A3B8; text-transform: uppercase; letter-spacing: .05em; display: block; }

/* Description */
.pd-desc { font-size: 0.92rem; color: #475569; line-height: 1.85; }

/* Amenities */
.pd-amenities-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.pd-amenity {
    display: flex; align-items: center; gap: 8px;
    padding: 10px 14px;
    background: #F8FAFC; border: 1px solid #F1F5F9;
    border-radius: 10px;
    font-size: 0.85rem; color: #374151; font-weight: 500;
}
.pd-amenity-ico {
    width: 30px; height: 30px; border-radius: 8px;
    background: #EFF6FF; display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}

/* Map */
.pd-map { height: 340px; border-radius: 12px; overflow: hidden; border: 1px solid #E2E8F0; width: 100%; max-width: 100%; }

/* ── Sidebar ── */
.pd-sidebar { display: flex; flex-direction: column; gap: 16px; }

/* Seller card */
.pd-seller-top { display: flex; align-items: center; gap: 12px; margin-bottom: 14px; }
.pd-seller-av {
    width: 48px; height: 48px; border-radius: 50%;
    background: linear-gradient(135deg, #2563EB, #7C3AED);
    color: #fff; font-family: 'Plus Jakarta Sans', sans-serif;
    font-weight: 700; font-size: 1.1rem;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.pd-seller-name  { font-weight: 700; font-size: 0.95rem; color: #0F172A; margin: 0 0 1px; }
.pd-seller-role  { font-size: 0.72rem; color: #94A3B8; margin: 0; }
.pd-seller-vbadge{ margin-left: auto; display: flex; align-items: center; gap: 4px; font-size: 0.72rem; color: #059669; font-weight: 600; }
.pd-contacts { display: flex; flex-direction: column; gap: 6px; margin-bottom: 14px; }
.pd-contact-link {
    display: flex; align-items: center; gap: 8px;
    font-size: 0.82rem; color: #475569;
    text-decoration: none; padding: 7px 10px;
    background: #F8FAFC; border-radius: 8px;
    border: 1px solid #F1F5F9;
    transition: all .15s;
    overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.pd-contact-link:hover { background: #EFF6FF; color: #2563EB; border-color: #BFDBFE; }
.pd-seller-btns { display: flex; flex-direction: column; gap: 7px; margin-bottom: 10px; }
.pd-sbtn {
    display: flex; align-items: center; justify-content: center; gap: 6px;
    padding: 10px;
    border-radius: 8px; font-size: 0.875rem; font-weight: 600;
    font-family: 'Inter', sans-serif; text-decoration: none; cursor: pointer;
    border: none; transition: all .2s;
}
.pd-sbtn--primary { background: #2563EB; color: #fff; }
.pd-sbtn--primary:hover { background: #1D4ED8; transform: translateY(-1px); box-shadow: 0 4px 14px rgba(37,99,235,.35); color: #fff; }
.pd-sbtn--ghost  { background: #F8FAFC; color: #475569; border: 1px solid #E2E8F0; }
.pd-sbtn--ghost:hover { border-color: #DC2626; color: #DC2626; }
.pd-sbtn--ghost.favorited { color: #DC2626; border-color: #DC2626; background: #FFF1F2; }
.pd-report-row { display: flex; gap: 6px; }
.pd-report-btn {
    flex: 1; display: flex; align-items: center; justify-content: center; gap: 4px;
    padding: 6px; font-size: 0.7rem; font-weight: 600; color: #94A3B8;
    background: transparent; border: 1px solid #E2E8F0; border-radius: 7px;
    cursor: pointer; font-family: 'Inter', sans-serif; transition: all .15s;
}
.pd-report-btn:hover { border-color: #DC2626; color: #DC2626; background: #FFF1F2; }

/* Inquiry card */
.pd-inquiry-guest {
    text-align: center; padding: 16px;
    display: flex; flex-direction: column; align-items: center; gap: 8px;
    font-size: 0.875rem; color: #94A3B8; line-height: 1.65;
}
.pd-inquiry-guest a { color: #2563EB; font-weight: 600; }
.pd-form-label { font-size: 0.75rem; font-weight: 700; color: #64748B; text-transform: uppercase; letter-spacing: .04em; margin-bottom: 5px; display: block; }
.pd-textarea {
    width: 100%; padding: 10px 12px;
    border: 1px solid #CBD5E1; border-radius: 8px;
    font-size: 0.875rem; color: #0F172A;
    font-family: 'Inter', sans-serif;
    resize: vertical; min-height: 110px;
    transition: border-color .15s;
}
.pd-textarea:focus { outline: none; border-color: #2563EB; box-shadow: 0 0 0 3px rgba(37,99,235,.1); }
.pd-send-btn {
    display: flex; align-items: center; justify-content: center; gap: 6px;
    width: 100%; padding: 11px;
    background: #2563EB; color: #fff;
    border: none; border-radius: 8px;
    font-size: 0.875rem; font-weight: 600;
    cursor: pointer; font-family: 'Inter', sans-serif;
    transition: background .2s, transform .2s; margin-top: 10px;
}
.pd-send-btn:hover { background: #1D4ED8; transform: translateY(-1px); }

/* Share card */
.pd-share-row { display: flex; gap: 8px; flex-wrap: wrap; }
.pd-share-btn {
    width: 40px; height: 40px;
    display: flex; align-items: center; justify-content: center;
    border-radius: 50%; background: #F8FAFC;
    border: 1px solid #E2E8F0; color: #475569;
    font-size: 0.9rem; text-decoration: none; cursor: pointer;
    transition: all .18s; flex-shrink: 0;
}
.pd-share-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,.12); }
.pd-share-fb:hover  { background: #1877F2; color: #fff; border-color: #1877F2; }
.pd-share-tw:hover  { background: #000; color: #fff; border-color: #000; }
.pd-share-ig:hover  { background: #E1306C; color: #fff; border-color: #E1306C; }
.pd-share-tk:hover  { background: #010101; color: #fff; border-color: #010101; }
.pd-share-cp:hover  { background: #2563EB; color: #fff; border-color: #2563EB; }

/* ── Responsive Breakpoints ── */
@media (max-width: 991.98px) {
    .pd-layout {
        grid-template-columns: 1fr;
    }
    .pd-gallery-main {
        height: 360px;
    }
}
@media (max-width: 575.98px) {
    .pd-body {
        padding: 16px 0 40px;
    }
    .pd-gallery-main {
        height: 250px;
    }
    .pd-prop-title {
        font-size: 1.35rem;
    }
    .pd-prop-price {
        font-size: 1.55rem;
    }
    .pd-amenities-grid {
        grid-template-columns: 1fr;
    }
    .pd-spec {
        padding-right: 12px;
        margin-right: 12px;
    }
    .pd-thumb {
        width: 58px;
        height: 44px;
    }
}

/* Fade in animation */
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(18px); }
    to   { opacity: 1; transform: translateY(0); }
}
.pd-anim { animation: fadeInUp .4s ease both; }
</style>

<div class="pd-wrap">

    <!-- Breadcrumb -->
    <div class="pd-breadbar">
        <div class="container">
            <ol class="pd-breadcrumb">
                <li><a href="index.php">Home</a></li>
                <li><i data-lucide="chevron-right" style="width:12px;height:12px;"></i></li>
                <li><a href="search.php">Properties</a></li>
                <li><i data-lucide="chevron-right" style="width:12px;height:12px;"></i></li>
                <li class="cur"><?= htmlspecialchars($property['title']) ?></li>
            </ol>
        </div>
    </div>

    <div class="pd-body">
        <div class="container">
            <div class="pd-layout">

                <!-- ══════════ LEFT: MAIN CONTENT ══════════ -->
                <div class="pd-main pd-anim">

                    <!-- GALLERY -->
                    <div class="pd-gallery pd-card">
                        <div class="pd-gallery-main">
                            <?php if (count($galleryImages) > 1): ?>
                                <!-- Swiper with actual img tags -->
                                <div class="swiper pd-main-swiper" id="pdMainSwiper">
                                    <div class="swiper-wrapper">
                                        <?php foreach ($galleryImages as $idx => $gImg): ?>
                                        <div class="swiper-slide">
                                            <a href="<?= htmlspecialchars($gImg) ?>" class="glightbox"
                                               data-gallery="prop-gallery"
                                               data-glightbox="title: <?= htmlspecialchars($property['title']) ?>">
                                                <img src="<?= htmlspecialchars($gImg) ?>"
                                                     alt="<?= htmlspecialchars($property['title']) ?> image <?= $idx+1 ?>"
                                                     loading="<?= $idx===0?'eager':'lazy' ?>"
                                                     onerror="this.src='XTate-Image.png'">
                                            </a>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <button class="pd-swiper-btn pd-swiper-prev" id="pdPrev" aria-label="Previous">
                                    <i data-lucide="chevron-left" style="width:20px;height:20px;display:block;"></i>
                                </button>
                                <button class="pd-swiper-btn pd-swiper-next" id="pdNext" aria-label="Next">
                                    <i data-lucide="chevron-right" style="width:20px;height:20px;display:block;"></i>
                                </button>
                                <div class="pd-gallery-count">
                                    <i data-lucide="images" style="width:12px;height:12px;"></i>
                                    <span id="pdImgCount">1 / <?= count($galleryImages) ?></span>
                                </div>
                            <?php else: ?>
                                <!-- Single image -->
                                <a href="<?= htmlspecialchars($galleryImages[0]) ?>" class="glightbox" data-gallery="prop-gallery">
                                    <img src="<?= htmlspecialchars($galleryImages[0]) ?>"
                                         alt="<?= htmlspecialchars($property['title']) ?>"
                                         onerror="this.src='XTate-Image.png'">
                                </a>
                            <?php endif; ?>
                        </div>

                        <!-- Thumbnail strip -->
                        <?php if (count($galleryImages) > 1): ?>
                        <div class="pd-thumbs" id="pdThumbs">
                            <?php foreach ($galleryImages as $ti => $tImg): ?>
                            <div class="pd-thumb <?= $ti===0?'active':'' ?>" data-idx="<?= $ti ?>">
                                <img src="<?= htmlspecialchars($tImg) ?>" alt="Thumb <?= $ti+1 ?>"
                                     onerror="this.src='XTate-Image.png'">
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- PROPERTY HEADER -->
                    <div class="pd-card">
                        <div class="pd-header">
                            <div class="pd-badges">
                                <?php if (!empty($property['property_type_name']) || !empty($property['type_name'])): ?>
                                <span class="pd-badge pd-badge--type">
                                    <?= htmlspecialchars($property['property_type_name'] ?? $property['type_name']) ?>
                                </span>
                                <?php endif; ?>
                                <span class="pd-badge pd-badge--status">For Sale</span>
                            </div>

                            <h1 class="pd-prop-title"><?= htmlspecialchars($property['title']) ?></h1>

                            <div class="pd-prop-addr">
                                <i data-lucide="map-pin" style="width:15px;height:15px;color:#2563EB;flex-shrink:0;margin-top:2px;"></i>
                                <span>
                                    <?= htmlspecialchars($property['address']) ?>,
                                    <?= htmlspecialchars($property['city']) ?>,
                                    <?= htmlspecialchars($property['state']) ?>
                                    <?= htmlspecialchars($property['zip_code'] ?? '') ?>
                                </span>
                            </div>

                            <div class="pd-prop-price"><?= formatCurrency($property['price']) ?></div>

                            <!-- Specs -->
                            <div class="pd-specs">
                                <div class="pd-spec">
                                    <i data-lucide="bed-double" style="width:20px;height:20px;color:#2563EB;"></i>
                                    <div>
                                        <span class="pd-spec-val"><?= $property['bedrooms'] ?? 0 ?></span>
                                        <span class="pd-spec-label">Bedrooms</span>
                                    </div>
                                </div>
                                <div class="pd-spec">
                                    <i data-lucide="bath" style="width:20px;height:20px;color:#2563EB;"></i>
                                    <div>
                                        <span class="pd-spec-val"><?= $property['bathrooms'] ?? 0 ?></span>
                                        <span class="pd-spec-label">Bathrooms</span>
                                    </div>
                                </div>
                                <div class="pd-spec">
                                    <i data-lucide="maximize-2" style="width:20px;height:20px;color:#2563EB;"></i>
                                    <div>
                                        <span class="pd-spec-val"><?= number_format($property['area'] ?? ($property['area_sqft'] ?? 0)) ?></span>
                                        <span class="pd-spec-label">Sq. Ft.</span>
                                    </div>
                                </div>
                                <?php if (!empty($property['year_built']) && $property['year_built'] !== 'N/A'): ?>
                                <div class="pd-spec">
                                    <i data-lucide="calendar" style="width:20px;height:20px;color:#2563EB;"></i>
                                    <div>
                                        <span class="pd-spec-val"><?= htmlspecialchars($property['year_built']) ?></span>
                                        <span class="pd-spec-label">Year Built</span>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- DESCRIPTION -->
                    <div class="pd-card">
                        <div class="pd-card-inner">
                            <div class="pd-card-title">
                                <i data-lucide="file-text" style="width:16px;height:16px;color:#2563EB;"></i>
                                Description
                            </div>
                            <p class="pd-desc"><?= nl2br(htmlspecialchars($property['description'])) ?></p>
                        </div>
                    </div>

                    <!-- AMENITIES -->
                    <?php
                    $amenities = [
                        'garage'           => ['Garage',          'car'],
                        'air_conditioning' => ['Air Conditioning','wind'],
                        'swimming_pool'    => ['Swimming Pool',   'waves'],
                        'backyard'         => ['Backyard',        'trees'],
                        'gym'              => ['Gym',             'dumbbell'],
                        'fireplace'        => ['Fireplace',       'flame'],
                        'security_system'  => ['Security System', 'shield-check'],
                        'washer_dryer'     => ['Washer / Dryer',  'shirt'],
                    ];
                    $activeAmenities = [];
                    foreach ($amenities as $key => [$name, $icon]) {
                        if (isset($property[$key]) && $property[$key] == 1) {
                            $activeAmenities[] = ['name' => $name, 'icon' => $icon];
                        }
                    }
                    if (!empty($activeAmenities)): ?>
                    <div class="pd-card">
                        <div class="pd-card-inner">
                            <div class="pd-card-title">
                                <i data-lucide="sparkles" style="width:16px;height:16px;color:#2563EB;"></i>
                                Amenities
                            </div>
                            <div class="pd-amenities-grid">
                                <?php foreach ($activeAmenities as $am): ?>
                                <div class="pd-amenity">
                                    <div class="pd-amenity-ico">
                                        <i data-lucide="<?= $am['icon'] ?>" style="width:15px;height:15px;color:#2563EB;"></i>
                                    </div>
                                    <?= $am['name'] ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- MAP -->
                    <div class="pd-card">
                        <div class="pd-card-inner">
                            <div class="pd-card-title">
                                <i data-lucide="map" style="width:16px;height:16px;color:#2563EB;"></i>
                                Location
                            </div>
                            <div id="propertyMap" class="pd-map"></div>
                        </div>
                    </div>

                </div><!-- end pd-main -->

                <!-- ══════════ RIGHT: SIDEBAR ══════════ -->
                <div class="pd-sidebar pd-anim" style="animation-delay:.1s;">

                    <!-- SELLER CARD -->
                    <div class="pd-card" style="margin-bottom:0;">
                        <div class="pd-card-inner">
                            <div class="pd-card-title">
                                <i data-lucide="user-check" style="width:16px;height:16px;color:#2563EB;"></i>
                                Seller Information
                            </div>
                            <div class="pd-seller-top">
                                <div class="pd-seller-av">
                                    <?= strtoupper(substr($property['seller_name'] ?? 'S', 0, 1)) ?>
                                </div>
                                <div>
                                    <p class="pd-seller-name"><?= htmlspecialchars($property['seller_name']) ?></p>
                                    <p class="pd-seller-role">Property Seller</p>
                                </div>
                                <div class="pd-seller-vbadge">
                                    <i data-lucide="badge-check" style="width:15px;height:15px;color:#059669;"></i>
                                    Verified
                                </div>
                            </div>
                            <div class="pd-contacts">
                                <a href="mailto:<?= htmlspecialchars($property['seller_email']) ?>" class="pd-contact-link">
                                    <i data-lucide="mail" style="width:14px;height:14px;color:#2563EB;flex-shrink:0;"></i>
                                    <?= htmlspecialchars($property['seller_email']) ?>
                                </a>
                                <a href="tel:<?= htmlspecialchars($property['seller_phone']) ?>" class="pd-contact-link">
                                    <i data-lucide="phone" style="width:14px;height:14px;color:#2563EB;flex-shrink:0;"></i>
                                    <?= htmlspecialchars($property['seller_phone']) ?>
                                </a>
                            </div>
                            <?php if (isLoggedIn() && $_SESSION['user_role'] === 'buyer'): ?>
                            <div class="pd-seller-btns">
                                <a href="buyer/messages.php?user=<?= $property['seller_id'] ?>" class="pd-sbtn pd-sbtn--primary">
                                    <i data-lucide="message-circle" style="width:15px;height:15px;"></i>
                                    Message Seller
                                </a>
                                <button class="pd-sbtn pd-sbtn--ghost favorite-btn <?= $isFavorited?'favorited':'' ?>"
                                        data-property-id="<?= $property['id'] ?>">
                                    <i data-lucide="heart" style="width:15px;height:15px;"></i>
                                    <?= $isFavorited ? 'Remove from Favorites' : 'Save Property' ?>
                                </button>
                            </div>
                            <div class="pd-report-row">
                                <button class="pd-report-btn" data-bs-toggle="modal" data-bs-target="#reportSellerModal">
                                    <i data-lucide="flag" style="width:12px;height:12px;"></i> Report Seller
                                </button>
                                <button class="pd-report-btn" data-bs-toggle="modal" data-bs-target="#reportPropertyModal">
                                    <i data-lucide="flag" style="width:12px;height:12px;"></i> Report Property
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- INQUIRY CARD -->
                    <div class="pd-card" style="margin-bottom:0;">
                        <div class="pd-card-inner">
                            <div class="pd-card-title">
                                <i data-lucide="send" style="width:16px;height:16px;color:#2563EB;"></i>
                                Send an Inquiry
                            </div>

                            <?php if (!empty($errors)): ?>
                                <div class="alert alert-danger py-2 mb-3">
                                    <?php foreach ($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($success)): ?>
                                <div class="alert alert-success py-2 mb-3">
                                    <i data-lucide="check-circle" style="width:13px;height:13px;margin-right:4px;"></i>
                                    <?= htmlspecialchars($success) ?>
                                </div>
                            <?php endif; ?>

                            <?php if (isLoggedIn() && $_SESSION['user_role'] === 'buyer'): ?>
                                <form method="POST" action="<?= $_SERVER['PHP_SELF'] ?>?id=<?= $propertyId ?>" novalidate>
                                    <label class="pd-form-label" for="inquiry_message">Your Message</label>
                                    <textarea class="pd-textarea" id="inquiry_message" name="inquiry_message" required
                                              placeholder="I am interested in this property and would like to schedule a viewing…"></textarea>
                                    <button type="submit" name="inquiry_submit" class="pd-send-btn">
                                        <i data-lucide="send" style="width:15px;height:15px;"></i>
                                        Send Inquiry
                                    </button>
                                </form>
                            <?php elseif (isLoggedIn() && $_SESSION['user_role'] === 'seller'): ?>
                                <div class="alert alert-info py-2">
                                    As a seller, you cannot submit inquiries.
                                    <a href="seller/dashboard.php">Go to dashboard →</a>
                                </div>
                            <?php elseif (isLoggedIn() && $_SESSION['user_role'] === 'admin'): ?>
                                <div class="alert alert-info py-2">
                                    As an admin, you cannot submit inquiries.
                                    <a href="admin/dashboard.php">Go to dashboard →</a>
                                </div>
                            <?php else: ?>
                                <div class="pd-inquiry-guest">
                                    <i data-lucide="lock" style="width:28px;height:28px;color:#CBD5E1;"></i>
                                    <p>Please <a href="login.php?redirect=<?= urlencode('property_details.php?id='.$propertyId) ?>">login</a>
                                       or <a href="register.php">register</a> as a buyer to send an inquiry.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- SHARE CARD -->
                    <div class="pd-card" style="margin-bottom:0;">
                        <div class="pd-card-inner">
                            <div class="pd-card-title">
                                <i data-lucide="share-2" style="width:16px;height:16px;color:#2563EB;"></i>
                                Share This Property
                            </div>
                            <div class="pd-share-row">
                                <a href="https://www.facebook.com/" target="_blank" rel="noopener" class="pd-share-btn pd-share-fb" title="Facebook">
                                    <i class="fab fa-facebook-f"></i>
                                </a>
                                <a href="https://x.com/home" target="_blank" rel="noopener" class="pd-share-btn pd-share-tw" title="X / Twitter">
                                    <i class="fab fa-twitter"></i>
                                </a>
                                <a href="https://www.instagram.com/" target="_blank" rel="noopener" class="pd-share-btn pd-share-ig" title="Instagram">
                                    <i class="fab fa-instagram"></i>
                                </a>
                                <a href="https://www.tiktok.com/" target="_blank" rel="noopener" class="pd-share-btn pd-share-tk" title="TikTok">
                                    <i class="fa-brands fa-tiktok"></i>
                                </a>
                                <button class="pd-share-btn pd-share-cp" title="Copy link" id="copyLinkBtn"
                                        onclick="navigator.clipboard.writeText(window.location.href).then(function(){
                                            document.getElementById('copyLinkBtn').innerHTML='<i data-lucide=\'check\' style=\'width:14px;height:14px;display:block;\'></i>';
                                            if(typeof lucide!==\'undefined\') lucide.createIcons();
                                        });">
                                    <i data-lucide="link" style="width:14px;height:14px;display:block;"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                </div><!-- end pd-sidebar -->

            </div><!-- end pd-layout -->
        </div>
    </div>
</div>

<!-- ── Report Modals (PRESERVED) ── -->
<?php if (isLoggedIn() && $_SESSION['user_role'] === 'buyer'): ?>
<div class="modal fade" id="reportSellerModal" tabindex="-1" aria-labelledby="reportSellerModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reportSellerModalLabel">Report Seller</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="reportSellerForm" class="report-form">
                <div class="modal-body">
                    <input type="hidden" name="action" value="report_seller">
                    <input type="hidden" name="seller_id" value="<?= $property['seller_id'] ?>">
                    <div class="mb-3">
                        <label class="form-label">Reason for Report</label>
                        <textarea class="form-control" name="reason" rows="4" required placeholder="Describe the issue…"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Submit Report</button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="modal fade" id="reportPropertyModal" tabindex="-1" aria-labelledby="reportPropertyModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reportPropertyModalLabel">Report Property</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="reportPropertyForm" class="report-form">
                <div class="modal-body">
                    <input type="hidden" name="action" value="report_property">
                    <input type="hidden" name="property_id" value="<?= $property['id'] ?>">
                    <div class="mb-3">
                        <label class="form-label">Reason for Report</label>
                        <textarea class="form-control" name="reason" rows="4" required placeholder="Describe the issue…"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Submit Report</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    /* Lucide */
    if (typeof lucide !== 'undefined') lucide.createIcons();

    /* ── Swiper gallery (img-based, reliable) ── */
    var pdSwiper = null;
    if (typeof Swiper !== 'undefined' && document.getElementById('pdMainSwiper')) {
        pdSwiper = new Swiper('#pdMainSwiper', {
            loop: false,
            observer: true,
            observeParents: true,
            resizeObserver: true,
            navigation: { prevEl: '#pdPrev', nextEl: '#pdNext' },
            on: {
                slideChange: function () {
                    var idx = pdSwiper.activeIndex;
                    /* Update count badge */
                    var countEl = document.getElementById('pdImgCount');
                    if (countEl) countEl.textContent = (idx+1) + ' / ' + pdSwiper.slides.length;
                    /* Update thumbnails */
                    document.querySelectorAll('.pd-thumb').forEach(function (t, i) {
                        t.classList.toggle('active', i === idx);
                    });
                }
            }
        });
        /* Lucide re-init after swiper buttons render */
        if (typeof lucide !== 'undefined') lucide.createIcons();

        /* Thumbnail click */
        document.querySelectorAll('.pd-thumb').forEach(function (t) {
            t.addEventListener('click', function () {
                var idx = parseInt(this.dataset.idx, 10);
                if (pdSwiper) pdSwiper.slideTo(idx);
            });
        });
    }

    /* ── GLightbox ── */
    if (typeof GLightbox !== 'undefined') {
        GLightbox({ selector: '.glightbox', touchNavigation: true, loop: true });
    }

    /* ── Leaflet map with Nominatim geocoding (PRESERVED) ── */
    if (typeof L !== 'undefined' && document.getElementById('propertyMap')) {
        var map = L.map('propertyMap');
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(map);

        var fullAddr = <?= json_encode($property['address'] . ', ' . $property['city'] . ', ' . $property['state']) ?>;
        var cityAddr = <?= json_encode($property['city'] . ', ' . $property['state']) ?>;

        fetch('https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(fullAddr))
            .then(function(r){ return r.json(); })
            .then(function(data){
                if (data.length > 0) {
                    var lat = parseFloat(data[0].lat), lon = parseFloat(data[0].lon);
                    map.setView([lat,lon], 15);
                    L.marker([lat,lon]).addTo(map).bindPopup(fullAddr).openPopup();
                } else {
                    return fetch('https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(cityAddr))
                        .then(function(r){ return r.json(); })
                        .then(function(cd){
                            if (cd && cd.length > 0) {
                                var lat = parseFloat(cd[0].lat), lon = parseFloat(cd[0].lon);
                                map.setView([lat,lon], 13);
                                L.marker([lat,lon]).addTo(map).bindPopup(cityAddr).openPopup();
                            }
                        });
                }
            })
            .catch(function(e){ console.error('Map error:', e); });
    }

    /* ── Report form AJAX (PRESERVED) + Notyf ── */
    if (typeof Notyf !== 'undefined' && !window.xtNotyf) {
        window.xtNotyf = new Notyf({ duration: 3500, position: { x:'right', y:'top' }, ripple: false, dismissible: true });
    }
    document.querySelectorAll('.report-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            fetch('api/reports.php', { method: 'POST', body: new FormData(this) })
                .then(function(r){ return r.json(); })
                .then(function(data){
                    var modal = bootstrap.Modal.getInstance(form.closest('.modal'));
                    if (modal) modal.hide();
                    if (window.xtNotyf) window.xtNotyf.success(data.message || 'Report submitted.');
                    form.reset();
                })
                .catch(function(){ if (window.xtNotyf) window.xtNotyf.error('An error occurred. Please try again.'); });
        });
    });
});
</script>

<script src="js/property.js"></script>
<script src="js/favorites_fix.js"></script>

<?php include 'inc/footer.php'; ?>