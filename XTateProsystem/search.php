<?php
require_once 'inc/db.php';
require_once 'inc/functions.php';
require_once 'inc/auth.php';

// Start session
startSession();

// Get filter parameters from query string
$location = sanitizeInput($_GET['location'] ?? '');
$propertyTypeId = (int)($_GET['property_type'] ?? 0);
$priceMin = (int)($_GET['price_min'] ?? 0);
$priceMax = (int)($_GET['price_max'] ?? 0);
$bedrooms = (int)($_GET['bedrooms'] ?? 0);
$bathrooms = (int)($_GET['bathrooms'] ?? 0);
$keyword = sanitizeInput($_GET['keyword'] ?? '');

// Build SQL query
$sql = "SELECT p.*, pt.name as property_type, 
        (SELECT image_path FROM property_images WHERE property_id = p.id AND is_primary = 1 LIMIT 1) as primary_image 
        FROM properties p 
        JOIN property_types pt ON p.property_type_id = pt.id 
        WHERE p.status = 'active'";

$params = [];
$types = "";

// Location filter
if (!empty($location)) {
    $searchTerm = "%$location%";
    $sql .= " AND (p.address LIKE ? OR p.city LIKE ? OR p.state LIKE ?)";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "sss";
}

// Property type filter
if ($propertyTypeId > 0) {
    $sql .= " AND p.property_type_id = ?";
    $params[] = $propertyTypeId;
    $types .= "i";
}

// Price range filter
if ($priceMin > 0) {
    $sql .= " AND p.price >= ?";
    $params[] = $priceMin;
    $types .= "i";
}

if ($priceMax > 0) {
    $sql .= " AND p.price <= ?";
    $params[] = $priceMax;
    $types .= "i";
}

// Bedrooms filter
if ($bedrooms > 0) {
    $sql .= " AND p.bedrooms >= ?";
    $params[] = $bedrooms;
    $types .= "i";
}

// Bathrooms filter
if ($bathrooms > 0) {
    $sql .= " AND p.bathrooms >= ?";
    $params[] = $bathrooms;
    $types .= "i";
}

// Keyword search
if (!empty($keyword)) {
    $sql .= " AND (p.title LIKE ? OR p.description LIKE ? OR p.address LIKE ? OR p.city LIKE ? OR p.state LIKE ? OR pt.name LIKE ?)";
    $keywordParam = "%$keyword%";
    $params[] = $keywordParam;
    $params[] = $keywordParam;
    $params[] = $keywordParam;
    $params[] = $keywordParam;
    $params[] = $keywordParam;
    $params[] = $keywordParam;
    $types .= "ssssss";
}

// Sort order
$sort = sanitizeInput($_GET['sort'] ?? 'newest');
switch ($sort) {
    case 'price_low':
        $sql .= " ORDER BY p.price ASC";
        break;
    case 'price_high':
        $sql .= " ORDER BY p.price DESC";
        break;
    case 'newest':
    default:
        $sql .= " ORDER BY p.created_at DESC";
        break;
}

// Execute query
$properties = empty($params) ? fetchAll($sql) : fetchAll($sql, $types, $params);

// Get property types for filter form
$propertyTypes = getPropertyTypes();

// Get locations for filter form
$locations = getLocations();

include 'inc/header.php';
?>

<div class="container py-5">
    <h1 class="mb-4">Property Search</h1>
    
    <div class="row">
        <!-- Filters Sidebar -->
        <div class="col-lg-3 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h3 class="card-title h5 mb-0">Filter Properties</h3>
                </div>
                <div class="card-body">
                    <form action="search.php" method="GET" id="filterForm">
                        <!-- Keyword Search -->
                        <div class="mb-3">
                            <label for="keyword" class="form-label">Keyword</label>
                            <input type="text" class="form-control" id="keyword" name="keyword" placeholder="Enter keyword" value="<?= $keyword ?>">
                        </div>
                        
                        <!-- Location Filter -->
                        <div class="mb-3">
                            <label for="location" class="form-label">Location</label>
                            <input type="text" class="form-control" id="location" name="location" 
                                   value="<?= htmlspecialchars($location) ?>" 
                                   placeholder="Enter address, city, or state">
                        </div>
                        
                        <!-- Property Type Filter -->
                        <div class="mb-3">
                            <label for="property_type" class="form-label">Property Type</label>
                            <select class="form-select" id="property_type" name="property_type">
                                <option value="">All Types</option>
                                <?php foreach ($propertyTypes as $type): ?>
                                    <option value="<?= $type['id'] ?>" <?= $propertyTypeId === (int)$type['id'] ? 'selected' : '' ?>>
                                        <?= $type['name'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Price Range Filter -->
                        <div class="mb-3">
                            <label class="form-label">Price Range</label>
                            <div class="row gx-2">
                                <div class="col">
                                    <input type="number" class="form-control" id="price_min" name="price_min" placeholder="Min" value="<?= $priceMin > 0 ? $priceMin : '' ?>">
                                </div>
                                <div class="col">
                                    <input type="number" class="form-control" id="price_max" name="price_max" placeholder="Max" value="<?= $priceMax > 0 ? $priceMax : '' ?>">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Bedrooms Filter -->
                        <div class="mb-3">
                            <label for="bedrooms" class="form-label">Bedrooms</label>
                            <select class="form-select" id="bedrooms" name="bedrooms">
                                <option value="">Any</option>
                                <option value="1" <?= $bedrooms === 1 ? 'selected' : '' ?>>1+</option>
                                <option value="2" <?= $bedrooms === 2 ? 'selected' : '' ?>>2+</option>
                                <option value="3" <?= $bedrooms === 3 ? 'selected' : '' ?>>3+</option>
                                <option value="4" <?= $bedrooms === 4 ? 'selected' : '' ?>>4+</option>
                                <option value="5" <?= $bedrooms === 5 ? 'selected' : '' ?>>5+</option>
                            </select>
                        </div>
                        
                        <!-- Bathrooms Filter -->
                        <div class="mb-3">
                            <label for="bathrooms" class="form-label">Bathrooms</label>
                            <select class="form-select" id="bathrooms" name="bathrooms">
                                <option value="">Any</option>
                                <option value="1" <?= $bathrooms === 1 ? 'selected' : '' ?>>1+</option>
                                <option value="2" <?= $bathrooms === 2 ? 'selected' : '' ?>>2+</option>
                                <option value="3" <?= $bathrooms === 3 ? 'selected' : '' ?>>3+</option>
                                <option value="4" <?= $bathrooms === 4 ? 'selected' : '' ?>>4+</option>
                            </select>
                        </div>
                        
                        <!-- Sort Order -->
                        <div class="mb-3">
                            <label for="sort" class="form-label">Sort By</label>
                            <select class="form-select" id="sort" name="sort">
                                <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest First</option>
                                <option value="price_low" <?= $sort === 'price_low' ? 'selected' : '' ?>>Price: Low to High</option>
                                <option value="price_high" <?= $sort === 'price_high' ? 'selected' : '' ?>>Price: High to Low</option>
                            </select>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Apply Filters</button>
                        </div>
                        
                        <div class="mt-2 text-center">
                            <a href="search.php" class="text-decoration-none">Clear All Filters</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Properties Grid -->
        <div class="col-lg-9">
            <!-- Search Results Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="h4 mb-0">
                    <?= count($properties) ?> Properties Found
                    <?php
                    $filters = [];
                    if (!empty($location)) $filters[] = 'in ' . $location;
                    if ($propertyTypeId > 0) {
                        foreach ($propertyTypes as $type) {
                            if ((int)$type['id'] === $propertyTypeId) {
                                $filters[] = $type['name'];
                                break;
                            }
                        }
                    }
                    if (!empty($filters)) echo ' (' . implode(', ', $filters) . ')';
                    ?>
                </h2>
                
                <div class="d-flex align-items-center">
                    <label for="sort-mobile" class="form-label me-2 mb-0 d-none d-sm-block">Sort By:</label>
                    <select class="form-select form-select-sm" id="sort-mobile" onchange="window.location.href = this.value">
                        <option value="search.php?<?= http_build_query(array_merge($_GET, ['sort' => 'newest'])) ?>" <?= $sort === 'newest' ? 'selected' : '' ?>>
                            Newest First
                        </option>
                        <option value="search.php?<?= http_build_query(array_merge($_GET, ['sort' => 'price_low'])) ?>" <?= $sort === 'price_low' ? 'selected' : '' ?>>
                            Price: Low to High
                        </option>
                        <option value="search.php?<?= http_build_query(array_merge($_GET, ['sort' => 'price_high'])) ?>" <?= $sort === 'price_high' ? 'selected' : '' ?>>
                            Price: High to Low
                        </option>
                    </select>
                </div>
            </div>
            
            <?php if (empty($properties)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i> No properties found matching your search criteria. Try adjusting your filters.
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($properties as $property): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="property-card">
                                <?php
                                // Fix image path to ensure it works
                                $imagePath = $property['primary_image'] ?? 'https://images.unsplash.com/photo-1560518883-ce09059eeffa';
                                echo "<!-- DEBUG: Original Image Path: " . $imagePath . " -->";
                                
                                if (substr($imagePath, 0, 4) !== 'http') {
                                    // For local paths, get just the filename
                                    $filename = basename($imagePath);
                                    
                                    // Ensure path is relative to web root and has correct format
                                    $imagePath = 'uploads/properties/' . $filename;
                                }
                                
                                echo "<!-- DEBUG: Final Image Path: " . $imagePath . " -->";
                            ?>
                            <div class="property-image" style="background-image: url('<?= $imagePath ?>');">
                                    <div class="property-price"><?= formatCurrency($property['price']) ?></div>
                                </div>
                                <div class="property-content">
                                    <h3 class="property-title">
                                        <a href="property_details.php?id=<?= $property['id'] ?>"><?= $property['title'] ?></a>
                                    </h3>
                                    <div class="property-location">
                                        <i class="fas fa-map-marker-alt"></i> <?= $property['city'] ?>, <?= $property['state'] ?>
                                    </div>
                                    <p class="property-description">
                                        <?= substr($property['description'], 0, 100) ?>...
                                    </p>
                                    <div class="property-features">
                                        <div class="feature">
                                            <i class="fas fa-bed"></i> <?= $property['bedrooms'] ?> Beds
                                        </div>
                                        <div class="feature">
                                            <i class="fas fa-bath"></i> <?= $property['bathrooms'] ?> Baths
                                        </div>
                                        <div class="feature">
                                            <i class="fas fa-ruler-combined"></i> <?= number_format($property['area']) ?> sqft
                                        </div>
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

<?php include 'inc/footer.php'; ?>
