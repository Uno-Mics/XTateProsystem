<?php
require_once 'inc/db.php';
require_once 'inc/functions.php';
require_once 'inc/auth.php';

startSession();

$location       = sanitizeInput($_GET['location'] ?? '');
$propertyTypeId = (int)($_GET['property_type'] ?? 0);
$priceMin       = (int)($_GET['price_min'] ?? 0);
$priceMax       = (int)($_GET['price_max'] ?? 0);
$bedrooms       = (int)($_GET['bedrooms'] ?? 0);
$bathrooms      = (int)($_GET['bathrooms'] ?? 0);
$keyword        = sanitizeInput($_GET['keyword'] ?? '');
$sort           = sanitizeInput($_GET['sort'] ?? 'newest');

$searchFilters = [
    'status'           => 'active',
    'location'         => $location,
    'property_type_id' => $propertyTypeId,
    'price_min'        => $priceMin,
    'price_max'        => $priceMax,
    'bedrooms'         => $bedrooms,
    'bathrooms'        => $bathrooms,
    'keyword'          => $keyword,
    'sort'             => $sort
];

$properties    = firestore_get_properties($searchFilters);
$propertyTypes = getPropertyTypes();
$locations     = getLocations();

include 'inc/header.php';
?>

<style>
/* ============================================================
   SEARCH PAGE — Complete Styles (self-contained, no conflicts)
   ============================================================ */

/* Page wrapper */
.sp-wrap { background: #F1F5F9; min-height: 100vh; }

/* ── Top bar ── */
.sp-topbar {
    background: #fff;
    border-bottom: 1px solid #E2E8F0;
    padding: 14px 0;
}
.sp-topbar-inner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
}
.sp-page-title {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 1.2rem;
    font-weight: 800;
    color: #0F172A;
    margin: 0 0 2px;
    letter-spacing: -0.02em;
}
.sp-page-sub { font-size: 0.8rem; color: #94A3B8; margin: 0; }
.sp-sort-wrap { display: flex; align-items: center; gap: 8px; }
.sp-sort-label {
    font-size: 0.78rem; font-weight: 600; color: #64748B;
    display: flex; align-items: center; gap: 4px;
    white-space: nowrap;
}
.sp-sort-select {
    padding: 7px 12px;
    border: 1px solid #CBD5E1;
    border-radius: 8px;
    font-size: 0.82rem;
    color: #0F172A;
    background: #fff;
    cursor: pointer;
    font-family: 'Inter', sans-serif;
}
.sp-sort-select:focus { outline: none; border-color: #2563EB; box-shadow: 0 0 0 3px rgba(37,99,235,.1); }

/* ── Layout ── */
.sp-body { display: flex; gap: 20px; padding: 24px 0 48px; align-items: flex-start; }

/* ── Filter Sidebar ── */
.sp-sidebar { width: 270px; flex-shrink: 0; }
.sp-filter-card {
    background: #fff;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 1px 4px rgba(15,23,42,.06);
    position: sticky;
    top: 80px;
}
.sp-filter-head {
    display: flex; align-items: center; gap: 8px;
    padding: 14px 18px;
    background: #F8FAFC;
    border-bottom: 1px solid #E2E8F0;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-weight: 700; font-size: 0.9rem; color: #0F172A;
}
.sp-filter-body { padding: 16px 18px; display: flex; flex-direction: column; gap: 14px; }
.sp-fg { display: flex; flex-direction: column; gap: 5px; }
.sp-label {
    font-size: 0.7rem; font-weight: 700; color: #64748B;
    text-transform: uppercase; letter-spacing: .06em;
}
.sp-input-wrap { position: relative; }
.sp-input-icon { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #94A3B8; pointer-events: none; }
.sp-input {
    width: 100%;
    padding: 8px 10px 8px 30px;
    border: 1px solid #CBD5E1;
    border-radius: 8px;
    font-size: 0.84rem;
    color: #0F172A;
    background: #fff;
    font-family: 'Inter', sans-serif;
    transition: border-color .15s;
}
.sp-input.no-icon { padding-left: 10px; }
.sp-input:focus { outline: none; border-color: #2563EB; box-shadow: 0 0 0 3px rgba(37,99,235,.1); }
.sp-select {
    width: 100%;
    padding: 8px 10px;
    border: 1px solid #CBD5E1;
    border-radius: 8px;
    font-size: 0.84rem;
    color: #0F172A;
    background: #fff;
    cursor: pointer;
    font-family: 'Inter', sans-serif;
}
.sp-select:focus { outline: none; border-color: #2563EB; box-shadow: 0 0 0 3px rgba(37,99,235,.1); }
.sp-price-row { display: flex; align-items: center; gap: 6px; }
.sp-price-sep { color: #94A3B8; font-size: 0.82rem; flex-shrink: 0; }
.sp-chips { display: flex; flex-wrap: wrap; gap: 5px; }
.sp-chip {
    padding: 4px 10px;
    border: 1px solid #CBD5E1;
    border-radius: 100px;
    font-size: 0.75rem; font-weight: 600;
    color: #475569; background: #fff;
    cursor: pointer; transition: all .15s;
    font-family: 'Inter', sans-serif;
}
.sp-chip:hover  { border-color: #2563EB; color: #2563EB; }
.sp-chip.active { background: #2563EB; border-color: #2563EB; color: #fff; }
.sp-submit {
    display: flex; align-items: center; justify-content: center; gap: 6px;
    width: 100%; padding: 10px;
    background: #2563EB; color: #fff;
    border: none; border-radius: 8px;
    font-size: 0.875rem; font-weight: 600;
    cursor: pointer; font-family: 'Inter', sans-serif;
    transition: background .2s, transform .2s;
}
.sp-submit:hover { background: #1D4ED8; transform: translateY(-1px); }
.sp-clear {
    display: flex; align-items: center; justify-content: center; gap: 4px;
    font-size: 0.78rem; color: #94A3B8;
    text-decoration: none; margin-top: 2px;
    transition: color .15s;
}
.sp-clear:hover { color: #DC2626; }

/* ── Results Area ── */
.sp-results { flex: 1; min-width: 0; }
.sp-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }

/* ── Property Card ── */
.sp-card {
    background: #fff;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    overflow: hidden;
    transition: transform .3s cubic-bezier(.4,0,.2,1), box-shadow .3s;
    display: flex; flex-direction: column;
}
.sp-card:hover { transform: translateY(-6px); box-shadow: 0 16px 40px rgba(15,23,42,.13); border-color: #CBD5E1; }

/* Image area — KEY FIX: use position relative on this wrapper */
.sp-card-img-box {
    position: relative;
    overflow: hidden;
    height: 200px;
    background: #E2E8F0;
    flex-shrink: 0;
}
.sp-card-img-box img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    transition: transform .45s cubic-bezier(.4,0,.2,1);
}
.sp-card:hover .sp-card-img-box img { transform: scale(1.07); }

/* Price pill — overlaid on image */
.sp-card-price {
    position: absolute;
    bottom: 12px;
    right: 12px;
    background: rgba(10,20,40,.82);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    color: #fff;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 0.82rem;
    font-weight: 700;
    padding: 4px 12px;
    border-radius: 100px;
    border: 1px solid rgba(255,255,255,.18);
    pointer-events: none;
    z-index: 2;
}
/* Type badge — top left */
.sp-card-badge {
    position: absolute;
    top: 12px;
    left: 12px;
    background: rgba(37,99,235,.9);
    backdrop-filter: blur(4px);
    color: #fff;
    font-size: 0.65rem;
    font-weight: 700;
    letter-spacing: .04em;
    text-transform: uppercase;
    padding: 3px 8px;
    border-radius: 100px;
    z-index: 2;
}

/* Card body */
.sp-card-body { padding: 14px 16px 16px; display: flex; flex-direction: column; flex: 1; }
.sp-card-title {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 0.95rem; font-weight: 700;
    color: #0F172A; margin: 0 0 5px;
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.sp-card-title a { color: inherit; text-decoration: none; }
.sp-card-title a:hover { color: #2563EB; }
.sp-card-location {
    display: flex; align-items: center; gap: 4px;
    font-size: 0.78rem; color: #94A3B8; font-weight: 500;
    margin-bottom: 8px;
}
.sp-card-desc {
    font-size: 0.8rem; color: #64748B; line-height: 1.55;
    margin-bottom: 12px; flex: 1;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.sp-card-specs {
    display: flex; gap: 6px;
    padding-top: 10px;
    border-top: 1px solid #F1F5F9;
    flex-wrap: wrap;
}
.sp-spec {
    display: flex; align-items: center; gap: 4px;
    font-size: 0.75rem; font-weight: 600; color: #475569;
    background: #F8FAFC;
    padding: 3px 8px;
    border-radius: 100px;
    border: 1px solid #E2E8F0;
}

/* ── Empty state ── */
.sp-empty {
    text-align: center; padding: 60px 24px;
    background: #fff;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
}
.sp-empty h3 { font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 700; font-size: 1.1rem; color: #0F172A; margin: 12px 0 6px; }
.sp-empty p  { font-size: 0.875rem; color: #94A3B8; margin-bottom: 16px; }

/* ── Responsive ── */
@media (max-width: 1100px) { .sp-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 900px)  {
    .sp-body { flex-direction: column; }
    .sp-sidebar { width: 100%; }
    .sp-filter-card { position: static; }
}
@media (max-width: 600px)  { .sp-grid { grid-template-columns: 1fr; } }
</style>

<div class="sp-wrap">

    <!-- ── Top Bar ── -->
    <div class="sp-topbar">
        <div class="container">
            <div class="sp-topbar-inner">
                <div>
                    <h1 class="sp-page-title">Property Search</h1>
                    <p class="sp-page-sub">
                        <?= count($properties) ?> properties found
                        <?php
                        $parts = [];
                        if (!empty($location)) $parts[] = 'in <strong>' . htmlspecialchars($location) . '</strong>';
                        if ($propertyTypeId > 0) {
                            foreach ($propertyTypes as $t) {
                                if ((int)$t['id'] === $propertyTypeId) { $parts[] = '· ' . htmlspecialchars($t['name']); break; }
                            }
                        }
                        if (!empty($keyword)) $parts[] = '· "' . htmlspecialchars($keyword) . '"';
                        if (!empty($parts)) echo ' ' . implode(' ', $parts);
                        ?>
                    </p>
                </div>
                <div class="sp-sort-wrap">
                    <span class="sp-sort-label">
                        <i data-lucide="arrow-up-down" style="width:13px;height:13px;"></i> Sort by
                    </span>
                    <select class="sp-sort-select" onchange="window.location.href=this.value">
                        <option value="search.php?<?= http_build_query(array_merge($_GET, ['sort'=>'newest'])) ?>" <?= $sort==='newest' ? 'selected':'' ?>>Newest First</option>
                        <option value="search.php?<?= http_build_query(array_merge($_GET, ['sort'=>'price_low'])) ?>" <?= $sort==='price_low' ? 'selected':'' ?>>Price: Low → High</option>
                        <option value="search.php?<?= http_build_query(array_merge($_GET, ['sort'=>'price_high'])) ?>" <?= $sort==='price_high' ? 'selected':'' ?>>Price: High → Low</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Body ── -->
    <div class="container">
        <div class="sp-body">

            <!-- FILTER SIDEBAR -->
            <aside class="sp-sidebar">
                <div class="sp-filter-card">
                    <div class="sp-filter-head">
                        <i data-lucide="sliders-horizontal" style="width:15px;height:15px;color:#2563EB;"></i>
                        Filter Properties
                    </div>
                    <form action="search.php" method="GET" class="sp-filter-body" id="filterForm">

                        <!-- Keyword -->
                        <div class="sp-fg">
                            <label class="sp-label">Keyword</label>
                            <div class="sp-input-wrap">
                                <i data-lucide="search" class="sp-input-icon" style="width:13px;height:13px;"></i>
                                <input type="text" class="sp-input" name="keyword" placeholder="Title, description…" value="<?= htmlspecialchars($keyword) ?>">
                            </div>
                        </div>

                        <!-- Location -->
                        <div class="sp-fg">
                            <label class="sp-label">Location</label>
                            <div class="sp-input-wrap">
                                <i data-lucide="map-pin" class="sp-input-icon" style="width:13px;height:13px;"></i>
                                <input type="text" class="sp-input" name="location" placeholder="City, barangay…" value="<?= htmlspecialchars($location) ?>">
                            </div>
                        </div>

                        <!-- Property Type -->
                        <div class="sp-fg">
                            <label class="sp-label">Property Type</label>
                            <select class="sp-select" name="property_type">
                                <option value="">All Types</option>
                                <?php foreach ($propertyTypes as $t): ?>
                                <option value="<?= $t['id'] ?>" <?= $propertyTypeId===(int)$t['id'] ? 'selected':'' ?>>
                                    <?= htmlspecialchars($t['name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Price Range -->
                        <div class="sp-fg">
                            <label class="sp-label">Price Range</label>
                            <div class="sp-price-row">
                                <input type="number" class="sp-input no-icon" name="price_min" placeholder="Min $" value="<?= $priceMin>0?$priceMin:'' ?>">
                                <span class="sp-price-sep">–</span>
                                <input type="number" class="sp-input no-icon" name="price_max" placeholder="Max $" value="<?= $priceMax>0?$priceMax:'' ?>">
                            </div>
                        </div>

                        <!-- Bedrooms -->
                        <div class="sp-fg">
                            <label class="sp-label">Bedrooms</label>
                            <input type="hidden" name="bedrooms" id="bdHidden" value="<?= $bedrooms ?>">
                            <div class="sp-chips">
                                <?php foreach (['Any'=>'','1+'=>1,'2+'=>2,'3+'=>3,'4+'=>4,'5+'=>5] as $lbl=>$v): ?>
                                <button type="button" class="sp-chip <?= $bedrooms==$v?'active':'' ?>" data-h="bdHidden" data-v="<?= $v ?>"><?= $lbl ?></button>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Bathrooms -->
                        <div class="sp-fg">
                            <label class="sp-label">Bathrooms</label>
                            <input type="hidden" name="bathrooms" id="btHidden" value="<?= $bathrooms ?>">
                            <div class="sp-chips">
                                <?php foreach (['Any'=>'','1+'=>1,'2+'=>2,'3+'=>3,'4+'=>4] as $lbl=>$v): ?>
                                <button type="button" class="sp-chip <?= $bathrooms==$v?'active':'' ?>" data-h="btHidden" data-v="<?= $v ?>"><?= $lbl ?></button>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">

                        <button type="submit" class="sp-submit">
                            <i data-lucide="search" style="width:15px;height:15px;"></i>
                            Apply Filters
                        </button>
                        <a href="search.php" class="sp-clear">
                            <i data-lucide="x" style="width:12px;height:12px;"></i> Clear All
                        </a>
                    </form>
                </div>
            </aside>

            <!-- RESULTS -->
            <div class="sp-results">
                <?php if (empty($properties)): ?>
                    <div class="sp-empty">
                        <i data-lucide="search-x" style="width:36px;height:36px;color:#94A3B8;"></i>
                        <h3>No Properties Found</h3>
                        <p>Try adjusting your filters or broadening your search criteria.</p>
                        <a href="search.php" class="btn btn-outline-primary">Clear Filters</a>
                    </div>
                <?php else: ?>
                    <div class="sp-grid">
                        <?php foreach ($properties as $i => $p):
                            // Image path resolution (PRESERVED)
                            $img = $p['primary_image'] ?? 'https://images.unsplash.com/photo-1560518883-ce09059eeffa?w=600';
                            if (substr($img, 0, 4) !== 'http') {
                                $img = 'uploads/properties/' . basename($img);
                            }
                        ?>
                        <div class="sp-card" style="animation: fadeInUp .4s ease both; animation-delay: <?= ($i%6)*.07 ?>s;">
                            <!-- Image Box -->
                            <div class="sp-card-img-box">
                                <img src="<?= htmlspecialchars($img) ?>"
                                     alt="<?= htmlspecialchars($p['title']) ?>"
                                     loading="lazy"
                                     onerror="this.src='https://images.unsplash.com/photo-1560518883-ce09059eeffa?w=600'">
                                <div class="sp-card-price"><?= formatCurrency($p['price']) ?></div>
                                <?php if (!empty($p['property_type'])): ?>
                                <div class="sp-card-badge"><?= htmlspecialchars($p['property_type']) ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- Body -->
                            <div class="sp-card-body">
                                <h3 class="sp-card-title">
                                    <a href="property_details.php?id=<?= $p['id'] ?>"><?= htmlspecialchars($p['title']) ?></a>
                                </h3>
                                <div class="sp-card-location">
                                    <i data-lucide="map-pin" style="width:12px;height:12px;color:#2563EB;flex-shrink:0;"></i>
                                    <?= htmlspecialchars($p['city']) ?>, <?= htmlspecialchars($p['state']) ?>
                                </div>
                                <p class="sp-card-desc"><?= htmlspecialchars(substr($p['description'] ?? '', 0, 110)) ?>…</p>
                                <div class="sp-card-specs">
                                    <div class="sp-spec">
                                        <i data-lucide="bed-double" style="width:12px;height:12px;color:#2563EB;"></i>
                                        <?= $p['bedrooms'] ?> Beds
                                    </div>
                                    <div class="sp-spec">
                                        <i data-lucide="bath" style="width:12px;height:12px;color:#2563EB;"></i>
                                        <?= $p['bathrooms'] ?> Baths
                                    </div>
                                    <div class="sp-spec">
                                        <i data-lucide="maximize-2" style="width:12px;height:12px;color:#2563EB;"></i>
                                        <?= number_format($p['area']) ?> sqft
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof lucide !== 'undefined') lucide.createIcons();
    // Chip selectors
    document.querySelectorAll('.sp-chip').forEach(function (btn) {
        btn.addEventListener('click', function () {
            this.closest('.sp-chips').querySelectorAll('.sp-chip').forEach(function (b) { b.classList.remove('active'); });
            this.classList.add('active');
            document.getElementById(this.dataset.h).value = this.dataset.v;
        });
    });
});
</script>

<?php include 'inc/footer.php'; ?>
