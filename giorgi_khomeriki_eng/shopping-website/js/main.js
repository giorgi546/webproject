$(document).ready(function() {
    
    // Initialize shopping cart
    updateCartCount();
    
    // Smooth scrolling for hero scroll button
    $('.hero-scroll').click(function() {
        $('html, body').animate({
            scrollTop: $('.featured-products').offset().top - 100
        }, 800);
    });
    
    // Navbar scroll effect
    $(window).scroll(function() {
        if ($(this).scrollTop() > 50) {
            $('.navbar').addClass('scrolled');
        } else {
            $('.navbar').removeClass('scrolled');
        }
    });
    
    // Add to cart functionality
    $('.add-to-cart, .add-to-cart-btn, .add-cart-btn').click(function(e) {
        e.preventDefault();
        
        const productId = $(this).data('id');
        const quantity = $(this).closest('.product-card').find('.quantity-input').val() || 1;
        
        addToCart(productId, quantity);
    });
    
    // Quick view functionality
    $('.quick-view').click(function(e) {
        e.preventDefault();
        const productId = $(this).data('id');
        showQuickView(productId);
    });
    
    // Newsletter subscription
    $('#newsletterForm').submit(function(e) {
        e.preventDefault();
        
        const email = $(this).find('input[name="email"]').val();
        
        if (validateEmail(email)) {
            subscribeNewsletter(email);
        } else {
            showAlert('Please enter a valid email address', 'error');
        }
    });
    
    // Search functionality with live suggestions
    let searchTimeout;
    $('.search-input').on('input', function() {
        clearTimeout(searchTimeout);
        const query = $(this).val();
        
        if (query.length > 2) {
            searchTimeout = setTimeout(function() {
                searchProducts(query);
            }, 300);
        } else {
            hideSearchSuggestions();
        }
    });
    
    // Product filtering (for shop page)
    $('.filter-checkbox').change(function() {
        filterProducts();
    });
    
    // Price range slider
    if ($('#priceRange').length) {
        $('#priceRange').on('input', function() {
            const value = $(this).val();
            $('#priceValue').text('$' + value);
            filterProducts();
        });
    }
    
    // Mobile menu toggle
    $('.mobile-menu-btn').click(function() {
        $('.main-nav').slideToggle(300);
        $(this).find('i').toggleClass('fa-bars fa-times');
    });
    
    // Auto-hide alerts
    setTimeout(function() {
        $('.alert').fadeOut(300);
    }, 5000);
    
    // Image lazy loading for better performance
    $('img[data-src]').each(function() {
        const img = $(this);
        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    const src = img.data('src');
                    img.attr('src', src).removeAttr('data-src');
                    observer.unobserve(entry.target);
                }
            });
        });
        observer.observe(this);
    });
    
    // Product quantity controls
    $(document).on('click', '.qty-btn', function() {
        const input = $(this).siblings('.quantity-input');
        const currentVal = parseInt(input.val()) || 1;
        
        if ($(this).hasClass('qty-increase')) {
            input.val(currentVal + 1);
        } else if ($(this).hasClass('qty-decrease') && currentVal > 1) {
            input.val(currentVal - 1);
        }
    });
    
    // Form validation
    $('form').submit(function(e) {
        const form = $(this);
        let isValid = true;
        
        // Check required fields
        form.find('input[required], textarea[required], select[required]').each(function() {
            const field = $(this);
            if (!field.val().trim()) {
                field.addClass('error');
                isValid = false;
            } else {
                field.removeClass('error');
            }
        });
        
        // Email validation
        form.find('input[type="email"]').each(function() {
            const email = $(this);
            if (email.val() && !validateEmail(email.val())) {
                email.addClass('error');
                isValid = false;
            } else {
                email.removeClass('error');
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            showAlert('Please fill in all required fields correctly', 'error');
        }
    });
    
    // Clear form errors on input
    $('input, textarea, select').on('input change', function() {
        $(this).removeClass('error');
    });
});

// Add to cart function
function addToCart(productId, quantity = 1) {
    showLoading();
    
    $.ajax({
        url: 'ajax/add_to_cart.php',
        method: 'POST',
        data: {
            product_id: productId,
            quantity: quantity
        },
        dataType: 'json',
        success: function(response) {
            hideLoading();
            
            if (response.success) {
                updateCartCount();
                showAlert('Product added to cart!', 'success');
                
                // Add visual feedback
                $('.cart-link').addClass('bounce');
                setTimeout(function() {
                    $('.cart-link').removeClass('bounce');
                }, 600);
                
            } else {
                showAlert(response.message || 'Failed to add product to cart', 'error');
            }
        },
        error: function() {
            hideLoading();
            showAlert('An error occurred. Please try again.', 'error');
        }
    });
}

// Update cart count
function updateCartCount() {
    $.ajax({
        url: 'ajax/get_cart_count.php',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            const count = response.count || 0;
            $('#cartCount').text(count);
            
            if (count > 0) {
                $('#cartCount').addClass('show');
            } else {
                $('#cartCount').removeClass('show');
            }
        }
    });
}

// Quick view modal
function showQuickView(productId) {
    showLoading();
    
    $.ajax({
        url: 'ajax/get_product.php',
        method: 'GET',
        data: { id: productId },
        dataType: 'json',
        success: function(product) {
            hideLoading();
            
            if (product) {
                const modal = createQuickViewModal(product);
                $('body').append(modal);
                $('#quickViewModal').fadeIn(300);
            }
        },
        error: function() {
            hideLoading();
            showAlert('Failed to load product details', 'error');
        }
    });
}

// Create quick view modal
function createQuickViewModal(product) {
    const salePrice = product.sale_price ? 
        `<span class="price-sale">$${parseFloat(product.sale_price).toFixed(2)}</span>
         <span class="price-original">$${parseFloat(product.price).toFixed(2)}</span>` :
        `<span class="price-current">$${parseFloat(product.price).toFixed(2)}</span>`;
    
    return `
        <div id="quickViewModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <div class="modal-body">
                    <div class="product-image-modal">
                        <img src="${product.main_image ? 'uploads/products/' + product.main_image : 'images/placeholder.jpg'}" 
                             alt="${product.name}">
                    </div>
                    <div class="product-details-modal">
                        <h2>${product.name}</h2>
                        <div class="product-price-modal">${salePrice}</div>
                        <p class="product-description-modal">${product.description || ''}</p>
                        <div class="quantity-selector">
                            <label>Quantity:</label>
                            <div class="qty-controls">
                                <button type="button" class="qty-btn qty-decrease">-</button>
                                <input type="number" class="quantity-input" value="1" min="1" max="${product.stock_quantity}">
                                <button type="button" class="qty-btn qty-increase">+</button>
                            </div>
                        </div>
                        <button class="btn btn-primary add-to-cart-modal" data-id="${product.id}">
                            Add to Cart
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
}

// Close modal functionality
$(document).on('click', '.close, .modal', function(e) {
    if (e.target === this) {
        $('.modal').fadeOut(300, function() {
            $(this).remove();
        });
    }
});

// Add to cart from modal
$(document).on('click', '.add-to-cart-modal', function() {
    const productId = $(this).data('id');
    const quantity = $('.quantity-input').val()  || 1;
    
    addToCart(productId, quantity);
    
    $('.modal').fadeOut(300, function() {
        $(this).remove();
    });
});

// Newsletter subscription
function subscribeNewsletter(email) {
    $.ajax({
        url: 'ajax/newsletter_subscribe.php',
        method: 'POST',
        data: { email: email },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showAlert('Thank you for subscribing!', 'success');
                $('#newsletterForm')[0].reset();
            } else {
                showAlert(response.message || 'Subscription failed', 'error');
            }
        },
        error: function() {
            showAlert('An error occurred. Please try again.', 'error');
        }
    });
}

// Search products
function searchProducts(query) {
    $.ajax({
        url: 'ajax/search_products.php',
        method: 'GET',
        data: { q: query },
        dataType: 'json',
        success: function(results) {
            showSearchSuggestions(results);
        }
    });
}

// Show search suggestions
function showSearchSuggestions(results) {
    const suggestions = $('.search-suggestions');
    if (suggestions.length === 0) {
        $('.search-form').append('<div class="search-suggestions"></div>');
    }
    
    let html = '';
    results.forEach(function(product) {
        html += `
            <div class="suggestion-item" data-id="${product.id}">
                <img src="${product.main_image ? 'uploads/products/' + product.main_image : 'images/placeholder.jpg'}" alt="${product.name}">
                <div class="suggestion-info">
                    <div class="suggestion-name">${product.name}</div>
                    <div class="suggestion-price">$${parseFloat(product.price).toFixed(2)}</div>
                </div>
            </div>
        `;
    });
    
    $('.search-suggestions').html(html).show();
}

// Hide search suggestions
function hideSearchSuggestions() {
    $('.search-suggestions').hide();
}

// Handle suggestion clicks
$(document).on('click', '.suggestion-item', function() {
    const productId = $(this).data('id');
    window.location.href = `product.php?id=${productId}`;
});

// Product filtering (for shop page)
function filterProducts() {
    const filters = {};
    
    // Category filters
    $('.filter-checkbox:checked').each(function() {
        const filterType = $(this).data('filter');
        const filterValue = $(this).val();
        
        if (!filters[filterType]) {
            filters[filterType] = [];
        }
        filters[filterType].push(filterValue);
    });
    
    // Price filter
    if ($('#priceRange').length) {
        filters.max_price = $('#priceRange').val();
    }
    
    // Apply filters via AJAX
    $.ajax({
        url: 'ajax/filter_products.php',
        method: 'POST',
        data: filters,
        success: function(response) {
            $('.products-grid').html(response);
        }
    });
}

// Utility functions
function validateEmail(email) {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
}

function showAlert(message, type = 'info') {
    const alert = `
        <div class="alert alert-${type}">
            ${message}
        </div>
    `;
    
    // Remove existing alerts
    $('.alert').remove();
    
    // Add new alert
    $('body').append(alert);
    
    // Auto-hide after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut(300, function() {
            $(this).remove();
        });
    }, 5000);
}

function showLoading() {
    $('#loadingSpinner').addClass('show');
}

function hideLoading() {
    $('#loadingSpinner').removeClass('show');
}

// Smooth scroll for anchor links
$('a[href^="#"]').click(function(e) {
    e.preventDefault();
    
    const target = $(this.getAttribute('href'));
    if (target.length) {
        $('html, body').animate({
            scrollTop: target.offset().top - 100
        }, 800);
    }
});

// Add bounce animation class
$('<style>')
    .prop('type', 'text/css')
    .html(`
        .bounce {
            animation: bounceEffect 0.6s ease-in-out;
        }
        
        @keyframes bounceEffect {
            0%, 20%, 60%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-10px);
            }
            80% {
                transform: translateY(-5px);
            }
        }
        
        .modal {
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            display: none;
        }
        
        .modal-content {
            position: relative;
            background-color: #fefefe;
            margin: 5% auto;
            padding: 0;
            border-radius: 8px;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .close {
            position: absolute;
            top: 15px;
            right: 20px;
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            z-index: 1;
        }
        
        .close:hover {
            color: #000;
        }
        
        .modal-body {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            padding: 2rem;
        }
        
        .product-image-modal img {
            width: 100%;
            height: 300px;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .quantity-selector {
            margin: 1rem 0;
        }
        
        .qty-controls {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
        
        .qty-btn {
            width: 30px;
            height: 30px;
            border: 1px solid #ddd;
            background: white;
            cursor: pointer;
            border-radius: 4px;
        }
        
        .search-suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-top: none;
            border-radius: 0 0 8px 8px;
            max-height: 300px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
        }
        
        .suggestion-item {
            display: flex;
            align-items: center;
            padding: 10px;
            cursor: pointer;
            border-bottom: 1px solid #eee;
        }
        
        .suggestion-item:hover {
            background: #f5f5f5;
        }
        
        .suggestion-item img {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 4px;
            margin-right: 10px;
        }
        
        .suggestion-name {
            font-weight: 500;
        }
        
        .suggestion-price {
            color: #666;
            font-size: 0.9rem;
        }
        
        @media (max-width: 768px) {
            .modal-body {
                grid-template-columns: 1fr;
                padding: 1rem;
            }
        }
    `)
    .appendTo('head');