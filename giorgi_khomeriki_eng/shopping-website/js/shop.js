$(document).ready(function() {
    // Filter functionality
    $('.category-filter, .featured-filter').change(function() {
        applyFilters();
    });
    // Price filter with debounce
    let priceTimeout;
    $('#minPrice, #maxPrice').on('input', function() {
        clearTimeout(priceTimeout);
        priceTimeout = setTimeout(applyFilters, 1000);
    });
   
    // Wishlist functionality
    $('.wishlist-btn').click(function(e) {
        e.preventDefault();
        const productId = $(this).data('id');
        
        if (!productId) {
            showAlert('Invalid product ID', 'error');
            return;
        }
        
        toggleWishlist(productId);
    });
});
function applyFilters() {
    const url = new URL(window.location);
    
    // Clear existing filter params
    url.searchParams.delete('category');
    url.searchParams.delete('featured');
    url.searchParams.delete('min_price');
    url.searchParams.delete('max_price');
    url.searchParams.delete('page');
    
    // Apply category filters
    const selectedCategory = $('.category-filter:checked').val();
    if (selectedCategory) {
        url.searchParams.set('category', selectedCategory);
    }
    
    // Apply featured filter
    if ($('.featured-filter:checked').length) {
        url.searchParams.set('featured', '1');
    }
    
    // Apply price filters
    const minPrice = $('#minPrice').val();
    const maxPrice = $('#maxPrice').val();
    if (minPrice && minPrice > 0) {
        url.searchParams.set('min_price', minPrice);
    }
    if (maxPrice && maxPrice > 0) {
        url.searchParams.set('max_price', maxPrice);
    }
    
    // Show loading state
    $('.products-grid').css('opacity', '0.6');
    
    window.location.href = url.toString();
}
function clearFilters() {
    const url = new URL(window.location);
    const search = url.searchParams.get('search');
    url.search = '';
    if (search) {
        url.searchParams.set('search', search);
    }
    window.location.href = url.toString();
}
function sortProducts(sortValue) {
    const url = new URL(window.location);
    url.searchParams.set('sort', sortValue);
    url.searchParams.delete('page');
    window.location.href = url.toString();
}
function toggleFilters() {
    const sidebar = $('#filtersSidebar');
    const button = $('.filter-mobile-toggle');
    
    sidebar.toggleClass('show');
    
    if (sidebar.hasClass('show')) {
        button.html('<i class="fas fa-times"></i> Hide Filters');
        sidebar.slideDown(300);
    } else {
        button.html('<i class="fas fa-filter"></i> Show Filters');
        sidebar.slideUp(300);
    }
}

// Create quick view modal with proper image mapping
function createQuickViewModal(product) {
    const imageMap = {
        'Smartphone Pro': 's24-ultra.webp',
        'Laptop Ultra': 'asus-ROG.jpg', 
        'Cotton T-Shirt': 't-shirt.jpeg',
        'Programming Book': 'hacking-book.jpg'
    };
    
    let imageSrc = 'images/asus-ROG.jpg'; // Default fallback
    
    if (product.main_image) {
        imageSrc = 'uploads/products/' + product.main_image;
    } else if (imageMap[product.name]) {
        imageSrc = 'images/' + imageMap[product.name];
    }
    
    const salePrice = product.sale_price ? 
        `<span class="price-sale">${parseFloat(product.sale_price).toFixed(2)}</span>
         <span class="price-original">${parseFloat(product.price).toFixed(2)}</span>` :
        `<span class="price-current">${parseFloat(product.price).toFixed(2)}</span>`;
    
    const modal = `
        <div id="quickViewModal" class="modal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 10000; display: flex; align-items: center; justify-content: center;">
            <div class="modal-content" style="background: white; border-radius: 16px; max-width: 800px; width: 90%; max-height: 90%; overflow-y: auto;">
                <div class="modal-header" style="padding: 2rem; border-bottom: 1px solid #E5E7EB; display: flex; justify-content: space-between; align-items: center;">
                    <h2>${product.name}</h2>
                    <button class="close-modal" style="background: none; border: none; font-size: 2rem; cursor: pointer;">&times;</button>
                </div>
                <div class="modal-body" style="padding: 2rem; display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                    <div class="product-image">
                        <img src="${imageSrc}" 
                             alt="${product.name}" 
                             style="width: 100%; height: 300px; object-fit: cover; border-radius: 12px;"
                             onerror="this.src='images/asus-ROG.jpg'">
                    </div>
                    <div class="product-details">
                        <div class="product-price" style="margin: 1rem 0; font-size: 1.5rem; font-weight: 700;">${salePrice}</div>
                        <p style="color: #6B7280; margin-bottom: 1.5rem;">${product.description || 'No description available'}</p>
                        <div class="quantity-selector" style="margin: 1rem 0;">
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Quantity:</label>
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <button class="qty-decrease" style="width: 40px; height: 40px; border: 1px solid #D1D5DB; background: white; cursor: pointer;">-</button>
                                <input type="number" class="quantity-input" value="1" min="1" max="${product.stock_quantity || 99}" style="width: 60px; height: 40px; text-align: center; border: 1px solid #D1D5DB;">
                                <button class="qty-increase" style="width: 40px; height: 40px; border: 1px solid #D1D5DB; background: white; cursor: pointer;">+</button>
                            </div>
                        </div>
                        <button class="add-to-cart-modal" data-id="${product.id}" style="width: 100%; background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; padding: 1rem; border-radius: 10px; font-weight: 600; cursor: pointer; margin-top: 1rem;">
                            Add to Cart
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    $('body').append(modal);
    
    // Modal event handlers
    $('.close-modal, #quickViewModal').click(function(e) {
        if (e.target === this) {
            $('#quickViewModal').remove();
        }
    });
    
    $('.qty-increase').click(function() {
        const input = $('.quantity-input');
        const max = parseInt(input.attr('max'));
        const current = parseInt(input.val());
        if (current < max) {
            input.val(current + 1);
        }
    });
    
    $('.qty-decrease').click(function() {
        const input = $('.quantity-input');
        const current = parseInt(input.val());
        if (current > 1) {
            input.val(current - 1);
        }
    });
    

}
// Wishlist function
function toggleWishlist(productId) {
    // Placeholder for wishlist functionality
    showAlert('Wishlist feature coming soon!', 'info');
}
// Utility functions
function showLoading() {
    if (!$('#loadingSpinner').length) {
        $('body').append('<div id="loadingSpinner" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; display: flex; align-items: center; justify-content: center;"><div style="width: 50px; height: 50px; border: 4px solid rgba(255,255,255,0.3); border-top: 4px solid white; border-radius: 50%; animation: spin 1s linear infinite;"></div></div>');
        $('<style>@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }</style>').appendTo('head');
    }
    $('#loadingSpinner').show();
}
function hideLoading() {
    $('#loadingSpinner').hide();
}
function updateCartCount() {
    $.ajax({
        url: 'ajax/get_cart_count.php',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#cartCount').text(response.count || 0);
                if (response.count > 0) {
                    $('#cartCount').addClass('show');
                }
            }
        },
        error: function() {
            console.log('Failed to update cart count');
        }
    });
}
function showAlert(message, type = 'info') {
    const alertColors = {
        success: { bg: '#D1FAE5', border: '#10B981', text: '#065F46' },
        error: { bg: '#FEE2E2', border: '#EF4444', text: '#991B1B' },
        info: { bg: '#DBEAFE', border: '#3B82F6', text: '#1E40AF' }
    };
    
    const colors = alertColors[type] || alertColors.info;
    
    const alert = `
        <div class="alert alert-${type}" style="position: fixed; top: 20px; right: 20px; z-index: 10000; padding: 12px 20px; border-radius: 8px; max-width: 400px; background: ${colors.bg}; border: 1px solid ${colors.border}; color: ${colors.text}; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            ${message}
        </div>
    `;
    
    $('.alert').remove();
    $('body').append(alert);
    
    setTimeout(function() {
        $('.alert').fadeOut(300, function() {
            $(this).remove();
        });
    }, 5000);
}
