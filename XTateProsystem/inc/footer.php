</main>

<!-- Footer -->
<footer class="footer bg-dark text-white py-5 mt-5">
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-4 mb-md-0">
                <div class="mb-4">
                    <img src="<?= $inc_dir ?>XTate-Logo.png" alt="XTate Logo" height="50" class="mb-3">
                    <h5 class="text-primary">Real Estate Listing System</h5>
                </div>
                <p>Find your dream home with our comprehensive real estate listing platform. Browse properties, connect with sellers, and make informed decisions.</p>
                <div class="social-icons mt-3">
                    <a href="https://www.facebook.com/ProXTate" class="text-white me-3"><i class="fab fa-facebook-f"></i></a>
                    <a href="https://x.com/XtateP82466" class="text-white me-3"><i class="fab fa-twitter"></i></a>
                    <a href="https://www.instagram.com/proxtate/" class="text-white me-3"><i class="fab fa-instagram"></i></a>
                    <a href="https://www.tiktok.com/@xtateprosystem?is_from_webapp=1&sender_device=pc" class="text-white"><i class="fa-brands fa-tiktok"></i></i></a>
                </div>
            </div>
            
            <div class="col-md-2 mb-4 mb-md-0">
                <h5 class="text-white mb-4">Quick Links</h5>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="/RealEstateConnect/index.php" class="text-white text-decoration-none">Home</a></li>
                    <li class="mb-2"><a href="/RealEstateConnect/search.php" class="text-white text-decoration-none">Properties</a></li>
                    <li class="mb-2"><a href="/RealEstateConnect/contact.php" class="text-white text-decoration-none">Contact</a></li>
                    <?php if (!isLoggedIn()): ?>
                        <li class="mb-2"><a href="/RealEstateConnect/login.php" class="text-white text-decoration-none">Login</a></li>
                        <li class="mb-2"><a href="/RealEstateConnect/register.php" class="text-white text-decoration-none">Register</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            
            <div class="col-md-3 mb-4 mb-md-0">
                <h5 class="text-white mb-4">Property Types</h5>
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <a href="/RealEstateConnect/search.php?property_type=1" class="text-white text-decoration-none">Single Family Home</a>
                    </li>
                    <li class="mb-2">
                        <a href="/RealEstateConnect/search.php?property_type=2" class="text-white text-decoration-none">Apartment</a>
                    </li>
                    <li class="mb-2">
                        <a href="/RealEstateConnect/search.php?property_type=3" class="text-white text-decoration-none">Condo</a>
                    </li>
                    <li class="mb-2">
                        <a href="/RealEstateConnect/search.php?property_type=4" class="text-white text-decoration-none">Townhouse</a>
                    </li>
                    <li class="mb-2">
                        <a href="/RealEstateConnect/search.php?property_type=5" class="text-white text-decoration-none">Land</a>
                    </li>
                    <li class="mb-2">
                        <a href="/RealEstateConnect/search.php?property_type=6" class="text-white text-decoration-none">Commercial</a>
                    </li>
                </ul>
            </div>
            
            <div class="col-md-3">
                <h5 class="text-white mb-4">Contact Us</h5>
                <ul class="list-unstyled">
                    <li class="mb-2"><i class="fas fa-map-marker-alt me-2"></i> 123 Real Estate St, City </li>
                    <li class="mb-2"><i class="fas fa-phone-alt me-2"></i> +63 96 854 7501 </li>
                    <li class="mb-2"><i class="fas fa-envelope me-2"></i> xtateprosystem@gmail.com </li>
                </ul>
            </div>
        </div>
        
        <hr class="bg-light my-4">
        
        <div class="row align-items-center">
            <div class="col-md-6 text-center text-md-start">
                <p class="mb-0">&copy; <?= date('Y') ?> Real Estate Listing System. All rights reserved.</p>
            </div>
            <div class="col-md-6 text-center text-md-end mt-3 mt-md-0">
                <p class="mb-0">
                    <a href="#" class="text-white text-decoration-none me-3">Privacy Policy</a>
                    <a href="#" class="text-white text-decoration-none me-3">Terms of Service</a>
                    <a href="#" class="text-white text-decoration-none">FAQ</a>
                </p>
            </div>
        </div>
    </div>
</footer>
</body>
</html>
