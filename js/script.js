// Mobile menu toggle
document.addEventListener('DOMContentLoaded', function() {
    // Add any initialization code here
    console.log('Website loaded successfully!');
    
    // Responsive navigation for mobile (will be implemented later)
    
    // Scroll animations
    window.addEventListener('scroll', function() {
        const header = document.querySelector('header');
        if (window.scrollY > 50) {
            header.style.boxShadow = '0 2px 10px rgba(0, 0, 0, 0.1)';
        } else {
            header.style.boxShadow = '0 2px 5px rgba(0, 0, 0, 0.1)';
        }
    });

    // Preload images 
    function preloadImages() {
        const images = document.querySelectorAll('img');
        images.forEach(img => {
            const src = img.getAttribute('src');
            if (src && src !== '') {
                const newImg = new Image();
                newImg.src = src;
            }
        });
    }
    
    preloadImages();
    
    // Initialize filter functionality
    initializeFilters();
    
    // Initialize card interactions
    initializeCardInteractions();
});

// Dynamic date for copyright in footer
document.addEventListener('DOMContentLoaded', function() {
    const year = new Date().getFullYear();
    const copyrightElement = document.querySelector('.copyright p');
    if (copyrightElement) {
        copyrightElement.textContent = `© ${year} Fresh Harvest. All rights reserved.`;
    }
});

// Filter functionality
function initializeFilters() {
    // Auto-submit filters when changed
    const filterSelects = document.querySelectorAll('.filter-form select, .filter-form input[type="checkbox"]');
    filterSelects.forEach(select => {
        select.addEventListener('change', function() {
            const form = this.closest('form');
            if (form) {
                form.submit();
            }
        });
    });
    
    // Clear filters button
    const filterForm = document.querySelector('.filter-form');
    if (filterForm) {
        const clearBtn = document.createElement('button');
        clearBtn.type = 'button';
        clearBtn.className = 'btn btn-secondary clear-filters-btn';
        clearBtn.textContent = 'Clear Filters';
        clearBtn.addEventListener('click', function() {
            // Reset all form inputs
            const selects = filterForm.querySelectorAll('select');
            selects.forEach(select => {
                select.value = '0';
            });
            
            const checkboxes = filterForm.querySelectorAll('input[type="checkbox"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
            
            const searchInput = filterForm.querySelector('input[name="search"]');
            if (searchInput) {
                searchInput.value = '';
            }
            
            // Submit the form
            filterForm.submit();
        });
        
        const filterOptions = filterForm.querySelector('.filter-options');
        if (filterOptions) {
            filterOptions.appendChild(clearBtn);
        }
    }
}

// Card interactions
function initializeCardInteractions() {
    // Add hover effects to fruit cards
    const fruitCards = document.querySelectorAll('.fruit-card');
    fruitCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.classList.add('hover');
        });
        
        card.addEventListener('mouseleave', function() {
            this.classList.remove('hover');
        });
    });
    
    // Add to cart animation
    const addToCartForms = document.querySelectorAll('.add-to-cart-form');
    addToCartForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const btn = this.querySelector('.add-cart-btn');
            if (btn) {
                btn.textContent = 'Adding...';
                btn.disabled = true;
                
                // Re-enable after submission
                setTimeout(() => {
                    btn.textContent = 'Add to Cart';
                    btn.disabled = false;
                }, 1000);
            }
        });
    });
}

// Harvest Hub JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Animate elements when they come into view
    const animateOnScroll = function() {
        const elements = document.querySelectorAll('.fruit-card, .farmer-card, .tip');
        
        elements.forEach(element => {
            const elementPosition = element.getBoundingClientRect().top;
            const screenPosition = window.innerHeight;
            
            if(elementPosition < screenPosition) {
                element.classList.add('animate');
                element.style.opacity = 1;
                element.style.transform = 'translateY(0)';
            }
        });
    };
    
    // Initially set elements to be invisible
    const elementsToAnimate = document.querySelectorAll('.fruit-card, .farmer-card, .tip');
    elementsToAnimate.forEach(element => {
        element.style.opacity = 0;
        element.style.transform = 'translateY(20px)';
        element.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
    });
    
    // Run animation on load and scroll
    window.addEventListener('scroll', animateOnScroll);
    animateOnScroll(); // Run once on load
    
    // Add hover effect to hero button
    const heroBtn = document.querySelector('.hero .btn');
    if(heroBtn) {
        heroBtn.addEventListener('mouseover', function() {
            this.style.transform = 'translateY(-3px)';
            this.style.boxShadow = '0 6px 15px rgba(0,0,0,0.4)';
        });
        
        heroBtn.addEventListener('mouseout', function() {
            this.style.transform = '';
            this.style.boxShadow = '';
        });
    }
    
    // Simple counter for demo purposes (could be replaced with actual data)
    const startCounters = function() {
        const counters = document.querySelectorAll('.counter-value');
        
        counters.forEach(counter => {
            const target = +counter.getAttribute('data-target');
            const count = +counter.innerText;
            const increment = target / 100;
            
            if(count < target) {
                counter.innerText = Math.ceil(count + increment);
                setTimeout(startCounters, 10);
            } else {
                counter.innerText = target;
            }
        });
    };
    
    // Add a statistics section dynamically
    const addStatisticsSection = function() {
        const harverstingTips = document.querySelector('.harvesting-tips');
        
        if(harverstingTips) {
            const statsSection = document.createElement('section');
            statsSection.className = 'statistics';
            
            statsSection.innerHTML = `
                <div class="container">
                    <div class="stats-container">
                        <div class="stat">
                            <i class="fas fa-users"></i>
                            <h3 class="counter-value" data-target="1500">0</h3>
                            <p>Happy Customers</p>
                        </div>
                        <div class="stat">
                            <i class="fas fa-store"></i>
                            <h3 class="counter-value" data-target="120">0</h3>
                            <p>Partner Farmers</p>
                        </div>
                        <div class="stat">
                            <i class="fas fa-apple-alt"></i>
                            <h3 class="counter-value" data-target="50">0</h3>
                            <p>Fruit Varieties</p>
                        </div>
                        <div class="stat">
                            <i class="fas fa-truck"></i>
                            <h3 class="counter-value" data-target="5000">0</h3>
                            <p>Deliveries</p>
                        </div>
                    </div>
                </div>
            `;
            
            // Insert after harvesting tips
            harverstingTips.parentNode.insertBefore(statsSection, harverstingTips.nextSibling);
            
            // Add styles for statistics section
            const style = document.createElement('style');
            style.textContent = `
                .statistics {
                    background-color: #2E7D32;
                    color: white;
                    padding: 60px 0;
                    text-align: center;
                }
                
                .stats-container {
                    display: flex;
                    justify-content: space-around;
                    flex-wrap: wrap;
                    gap: 30px;
                }
                
                .stat {
                    flex: 1;
                    min-width: 200px;
                }
                
                .stat i {
                    font-size: 3rem;
                    margin-bottom: 15px;
                    color: #8BC34A;
                }
                
                .stat h3 {
                    font-size: 2.5rem;
                    margin-bottom: 10px;
                    color: white;
                }
                
                @media (max-width: 768px) {
                    .stats-container {
                        flex-direction: column;
                        max-width: 400px;
                        margin: 0 auto;
                    }
                }
            `;
            
            document.head.appendChild(style);
            
            // Start the counters after a short delay
            setTimeout(startCounters, 500);
        }
    };
    
    // Call the function to add statistics
    addStatisticsSection();
}); 