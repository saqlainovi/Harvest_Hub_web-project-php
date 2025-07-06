<?php
// Set page title
$page_title = 'Home';

// Include header
require_once 'includes/header.php';
?>

<!-- Hero Section with Better Background Handling -->
<section class="hero" style="background-image: url('img/hero-bg.jpg'); background-size: cover; background-position: center; padding: 60px 0; text-align: center; color: white; position: relative;">
    <!-- Add overlay for better text visibility -->
    <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background-color: rgba(0,0,0,0.4);"></div>
    <div class="container" style="position: relative; z-index: 1;">
        <h2>Fresh Fruits, Directly From Farmers</h2>
        <p>Discover seasonal fruits and connect with local farmers</p>
        <a href="pages/fruits.php" class="btn" style="display: inline-block; background-color: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-top: 15px; font-weight: bold;">Explore Fruits</a>
    </div>
</section>

<!-- Seasonal Highlights with Robust Image Handling -->
<section class="seasonal-highlights" style="padding: 40px 0; background-color: #f9f9f9;">
    <div class="container">
        <h2 style="text-align: center; color: #4CAF50; margin-bottom: 30px;">Seasonal Highlights</h2>
        <div class="fruit-grid" style="display: flex; flex-wrap: wrap; justify-content: center; gap: 20px;">
            
            <!-- Mango Card with CSS Fallback -->
            <div class="fruit-card" style="flex: 1; min-width: 250px; max-width: 350px; background-color: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
                <div style="height: 200px; overflow: hidden; position: relative; background-color: #FFD700;">
                    <!-- Primary image with inline CSS fallback -->
                    <img src="img/alphonso_mango.jpg" alt="Mangoes" 
                         style="width: 100%; height: 100%; object-fit: cover;"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <!-- CSS Fallback that shows when image fails -->
                    <div style="display: none; width: 100%; height: 100%; justify-content: center; align-items: center; color: white; font-weight: bold; text-align: center; padding: 20px; box-sizing: border-box;">
                        Alphonso Mango
                    </div>
                </div>
                <div style="padding: 15px;">
                    <h3 style="margin-top: 0; color: #333;">Mangoes</h3>
                    <p style="color: #666;">Now in season! Fresh and juicy mangoes available.</p>
                    <a href="pages/fruit_details.php?id=1" class="btn" style="display: inline-block; background-color: #4CAF50; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px; font-size: 14px;">View Details</a>
                </div>
            </div>
            
            <!-- Lychee Card with CSS Fallback -->
            <div class="fruit-card" style="flex: 1; min-width: 250px; max-width: 350px; background-color: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
                <div style="height: 200px; overflow: hidden; position: relative; background-color: #8BC34A;">
                    <!-- Primary image with inline CSS fallback -->
                    <img src="img/litchi.jpg" alt="Lychee" 
                         style="width: 100%; height: 100%; object-fit: cover;"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <!-- CSS Fallback that shows when image fails -->
                    <div style="display: none; width: 100%; height: 100%; justify-content: center; align-items: center; color: white; font-weight: bold; text-align: center; padding: 20px; box-sizing: border-box;">
                        Litchi
                    </div>
                </div>
                <div style="padding: 15px;">
                    <h3 style="margin-top: 0; color: #333;">Lychee</h3>
                    <p style="color: #666;">Sweet and aromatic lychees freshly harvested.</p>
                    <a href="pages/fruit_details.php?id=2" class="btn" style="display: inline-block; background-color: #4CAF50; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px; font-size: 14px;">View Details</a>
                </div>
            </div>
            
            <!-- Pineapple Card with CSS Fallback -->
            <div class="fruit-card" style="flex: 1; min-width: 250px; max-width: 350px; background-color: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
                <div style="height: 200px; overflow: hidden; position: relative; background-color: #2E7D32;">
                    <!-- Primary image with inline CSS fallback -->
                    <img src="img/organic_pineapple.jpg" alt="Pineapple" 
                         style="width: 100%; height: 100%; object-fit: cover;"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <!-- CSS Fallback that shows when image fails -->
                    <div style="display: none; width: 100%; height: 100%; justify-content: center; align-items: center; color: white; font-weight: bold; text-align: center; padding: 20px; box-sizing: border-box;">
                        Organic Pineapple
                    </div>
                </div>
                <div style="padding: 15px;">
                    <h3 style="margin-top: 0; color: #333;">Pineapple</h3>
                    <p style="color: #666;">Juicy pineapples at their peak freshness.</p>
                    <a href="pages/fruit_details.php?id=3" class="btn" style="display: inline-block; background-color: #4CAF50; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px; font-size: 14px;">View Details</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Harvesting Tips Section with Icons -->
<section class="harvesting-tips" style="padding: 40px 0; background-color: #fff;">
    <div class="container">
        <h2 style="text-align: center; color: #4CAF50; margin-bottom: 30px;">Harvesting Tips</h2>
        <div class="tips-container" style="display: flex; flex-wrap: wrap; justify-content: center; gap: 30px; margin-bottom: 30px;">
            <div class="tip" style="flex: 1; min-width: 200px; max-width: 300px; text-align: center; padding: 20px; border-radius: 8px; background-color: #f9f9f9; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                <div style="font-size: 40px; color: #4CAF50; margin-bottom: 15px;">
                    <i class="fas fa-seedling"></i>
                </div>
                <h3 style="margin-top: 0; color: #333;">Seasonal Guide</h3>
                <p style="color: #666;">Learn when different fruits are in season and ready for harvest.</p>
            </div>
            <div class="tip" style="flex: 1; min-width: 200px; max-width: 300px; text-align: center; padding: 20px; border-radius: 8px; background-color: #f9f9f9; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                <div style="font-size: 40px; color: #4CAF50; margin-bottom: 15px;">
                    <i class="fas fa-hands"></i>
                </div>
                <h3 style="margin-top: 0; color: #333;">Picking Techniques</h3>
                <p style="color: #666;">Tips on how to properly pick fruits without damaging them.</p>
            </div>
            <div class="tip" style="flex: 1; min-width: 200px; max-width: 300px; text-align: center; padding: 20px; border-radius: 8px; background-color: #f9f9f9; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                <div style="font-size: 40px; color: #4CAF50; margin-bottom: 15px;">
                    <i class="fas fa-apple-alt"></i>
                </div>
                <h3 style="margin-top: 0; color: #333;">Storage Tips</h3>
                <p style="color: #666;">How to store your harvested fruits for maximum freshness.</p>
            </div>
        </div>
        <div style="text-align: center;">
            <a href="pages/harvesting_tips.php" class="btn" style="display: inline-block; background-color: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;">More Tips</a>
        </div>
    </div>
</section>

<!-- Featured Farmers with Robust Fallback -->
<section class="featured-farmers" style="padding: 40px 0; background-color: #f9f9f9; border-top: 1px solid #eee; border-bottom: 1px solid #eee;">
    <div class="container">
        <h2 style="text-align: center; color: #4CAF50; margin-bottom: 30px;">Our Featured Farmers</h2>
        <div class="farmers-grid" style="display: flex; flex-wrap: wrap; justify-content: center; gap: 30px;">
            
            <!-- Ahmed Farms Card -->
            <div class="farmer-card" style="flex: 1; min-width: 280px; max-width: 400px; background-color: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
                <div style="height: 220px; background-color: #eee; position: relative; display: flex; justify-content: center; align-items: center;">
                    <!-- Inline SVG as fallback in case image fails -->
                    <svg width="80" height="80" viewBox="0 0 24 24" style="position: absolute; fill: #ccc;">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/>
                    </svg>
                    <img src="img/placeholder.jpg" alt="Ahmed Farms" style="width: 100%; height: 100%; object-fit: cover; position: relative; z-index: 1;">
                </div>
                <div style="padding: 20px;">
                    <h3 style="margin-top: 0; color: #333; margin-bottom: 10px;">Ahmed Farms</h3>
                    <div class="rating" style="color: #FFD700; margin-bottom: 10px;">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star-half-alt"></i>
                        <span style="color: #666; margin-left: 5px; font-size: 14px;">4.5/5</span>
                    </div>
                    <p style="color: #666; margin-bottom: 15px;">Specializing in organic mangoes and jackfruits.</p>
                    <a href="#" class="btn" style="display: inline-block; background-color: #4CAF50; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px; font-size: 14px;">View Profile</a>
                </div>
            </div>
            
            <!-- Green Valley Orchard Card -->
            <div class="farmer-card" style="flex: 1; min-width: 280px; max-width: 400px; background-color: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
                <div style="height: 220px; background-color: #eee; position: relative; display: flex; justify-content: center; align-items: center;">
                    <!-- Inline SVG as fallback in case image fails -->
                    <svg width="80" height="80" viewBox="0 0 24 24" style="position: absolute; fill: #ccc;">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/>
                    </svg>
                    <img src="img/placeholder.jpg" alt="Green Valley Orchard" style="width: 100%; height: 100%; object-fit: cover; position: relative; z-index: 1;">
                </div>
                <div style="padding: 20px;">
                    <h3 style="margin-top: 0; color: #333; margin-bottom: 10px;">Green Valley Orchard</h3>
                    <div class="rating" style="color: #FFD700; margin-bottom: 10px;">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="far fa-star"></i>
                        <span style="color: #666; margin-left: 5px; font-size: 14px;">4.0/5</span>
                    </div>
                    <p style="color: #666; margin-bottom: 15px;">Family-owned farm with a variety of citrus fruits.</p>
                    <a href="#" class="btn" style="display: inline-block; background-color: #4CAF50; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px; font-size: 14px;">View Profile</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action Section -->
<section style="padding: 60px 0; background-color: #4CAF50; color: white; text-align: center;">
    <div class="container">
        <h2 style="margin-bottom: 20px;">Ready to experience farm-fresh fruits?</h2>
        <p style="margin-bottom: 30px; max-width: 600px; margin-left: auto; margin-right: auto;">Join our community of fruit lovers and connect directly with local farmers. Get the freshest produce delivered to your doorstep.</p>
        <div>
            <a href="pages/register.php" class="btn" style="display: inline-block; background-color: white; color: #4CAF50; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold; margin-right: 15px;">Sign Up</a>
            <a href="pages/fruits.php" class="btn" style="display: inline-block; background-color: rgba(255,255,255,0.2); color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold; border: 2px solid white;">Browse Fruits</a>
        </div>
    </div>
</section>

<?php
// Extra JS for home page animations
$extra_js = '<script>
    // Check all images on load and handle any failures
    window.addEventListener("load", function() {
        // Handle fruit images
        document.querySelectorAll(".fruit-card img").forEach(function(img) {
            if (!img.complete || img.naturalHeight === 0) {
                img.style.display = "none";
                img.nextElementSibling.style.display = "flex";
            }
        });
    });
</script>';

// Include footer
require_once 'includes/footer.php';
?> 