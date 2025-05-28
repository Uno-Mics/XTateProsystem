<?php
require_once 'inc/db.php';
require_once 'inc/functions.php';
require_once 'inc/auth.php';

// Start session
startSession();

// Get featured properties (using demo data instead of database query)
$featuredProperties = getDemoProperties();

// Get property types for search form
$propertyTypes = getPropertyTypes();

// Get locations for search form
$locations = getLocations();

include 'inc/header.php';
?>

<!-- Hero Section -->
<section class="hero">
    <div class="container">
        <div class="hero-content">
            <h1 class="hero-title">Find Your Dream Home</h1>
            <p class="hero-text">Browse our exclusive selection of properties and find the perfect home for you and your family.</p>
            
            <!-- Search Form -->
            <div class="search-form">
                <form action="search.php" method="GET">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="location" class="form-label">Search Location</label>
                            <input type="text" name="location" id="location" class="form-control" placeholder="Address, city, or country">
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="property_type" class="form-label">Property Type</label>
                            <select name="property_type" id="property_type" class="form-select">
                                <option value="">All Types</option>
                                <?php foreach ($propertyTypes as $type): ?>
                                    <option value="<?= $type['id'] ?>"><?= $type['name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="price_max" class="form-label">Max Price</label>
                            <select name="price_max" id="price_max" class="form-select">
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
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">Search Properties</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- Featured Properties Section -->
<section class="py-5">
    <div class="container">
        <h2 class="section-title">Featured Properties</h2>
        
        <?php if (empty($featuredProperties)): ?>
            <div class="alert alert-info">
                No properties available at the moment. Please check back later.
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($featuredProperties as $property): ?>
                    <div class="col-md-4 mb-4">
                        <div class="property-card">
                            <div class="property-image" style="background-image: url('<?= $property['primary_image'] ?? 'https://images.unsplash.com/photo-1560518883-ce09059eeffa' ?>');">
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
            
            <div class="text-center mt-4">
                <a href="search.php" class="btn btn-outline-primary">View All Properties</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Property Types Section -->
<section class="py-5 bg-light">
    <div class="container">
        <h2 class="section-title">Browse by Property Type</h2>
        
        <div class="row">
            <?php foreach ($propertyTypes as $index => $type): ?>
                <div class="col-md-4 mb-4">
                    <a href="search.php?property_type=<?= $type['id'] ?>" class="text-decoration-none">
                        <div class="card h-100">
                            <?php
                            // Alternate between different property images
                            $imageUrls = [
                                'https://images.unsplash.com/photo-1484154218962-a197022b5858',
                                'https://images.unsplash.com/photo-1510627489930-0c1b0bfb6785',
                                'https://images.unsplash.com/photo-1472224371017-08207f84aaae',
                                'https://images.unsplash.com/photo-1491357492920-d2979986a84e',
                                'https://images.unsplash.com/photo-1560518883-ce09059eeffa',
                                'https://images.unsplash.com/photo-1448630360428-65456885c650'
                            ];
                            $imageIndex = $index % count($imageUrls);
                            ?>
                            <div style="height: 200px; background-image: url('<?= $imageUrls[$imageIndex] ?>'); background-size: cover; background-position: center;"></div>
                            <div class="card-body text-center">
                                <h3 class="card-title"><?= $type['name'] ?></h3>
                                <p class="card-text text-muted">Browse all <?= $type['name'] ?> properties</p>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- How It Works Section -->
<section class="py-5">
    <div class="container">
        <h2 class="section-title text-center">How It Works</h2>
        
        <div class="row mt-5">
            <div class="col-md-4 text-center mb-4">
                <div class="bg-primary text-white rounded-circle mb-4 mx-auto" style="width: 80px; height: 80px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-search fa-2x"></i>
                </div>
                <h3>Search Properties</h3>
                <p>Browse our extensive catalog of properties using our advanced search tools to find exactly what you're looking for.</p>
            </div>
            
            <div class="col-md-4 text-center mb-4">
                <div class="bg-primary text-white rounded-circle mb-4 mx-auto" style="width: 80px; height: 80px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-comments fa-2x"></i>
                </div>
                <h3>Connect with Sellers</h3>
                <p>Use our real-time messaging system to communicate directly with property sellers and get all your questions answered.</p>
            </div>
            
            <div class="col-md-4 text-center mb-4">
                <div class="bg-primary text-white rounded-circle mb-4 mx-auto" style="width: 80px; height: 80px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-home fa-2x"></i>
                </div>
                <h3>Find Your Dream Home</h3>
                <p>Schedule visits, make offers, and find the perfect property that meets all your needs and preferences.</p>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials Section -->
<section class="py-5 bg-light">
    <div class="container">
        <h2 class="section-title text-center">What Our Clients Say</h2>
        
        <div class="row mt-5">
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex mb-3">
                            <div class="text-warning">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                            </div>
                        </div>
                        <p class="card-text">"I found my dream home thanks to this platform! The search features made it easy to filter properties based on my specific requirements."</p>
                        <div class="d-flex align-items-center mt-3">
                            <div class="flex-shrink-0">
                                <div class="bg-primary text-white rounded-circle" style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-user"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h5 class="mb-0">John Anderson</h5>
                                <small class="text-muted">Home Buyer</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex mb-3">
                            <div class="text-warning">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                            </div>
                        </div>
                        <p class="card-text">"As a seller, I appreciate how easy it is to list properties and communicate with potential buyers. I sold my apartment within just two weeks!"</p>
                        <div class="d-flex align-items-center mt-3">
                            <div class="flex-shrink-0">
                                <div class="bg-primary text-white rounded-circle" style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-user"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h5 class="mb-0">Sarah Johnson</h5>
                                <small class="text-muted">Property Seller</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex mb-3">
                            <div class="text-warning">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star-half-alt"></i>
                            </div>
                        </div>
                        <p class="card-text">"The real-time messaging feature made communication with the seller so convenient. I could ask questions and get immediate responses."</p>
                        <div class="d-flex align-items-center mt-3">
                            <div class="flex-shrink-0">
                                <div class="bg-primary text-white rounded-circle" style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-user"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h5 class="mb-0">Michael Carter</h5>
                                <small class="text-muted">Home Buyer</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action Section -->
<section class="py-5 bg-primary text-white">
    <div class="container text-center">
        <h2 class="mb-4">Ready to Find Your Dream Home?</h2>
        <p class="lead mb-4">Join thousands of satisfied users who have found their perfect property on our platform.</p>
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                    <a href="search.php" class="btn btn-light btn-lg px-4 me-md-2">Browse Properties</a>
                    <?php if (!isLoggedIn()): ?>
                        <a href="register.php" class="btn btn-outline-light btn-lg px-4">Register Now</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'inc/footer.php'; ?>
