
$(document).ready(function() {
    // Quantity controls
    $('.qty-increase').click(function() {
        const input = $('#quantityInput');
        const max = parseInt(input.attr('max'));
        const current = parseInt(input.val());
        
        if (current < max) {
            input.val(current + 1);
        }
    });
    
    $('.qty-decrease').click(function() {
        const input = $('#quantityInput');
        const current = parseInt(input.val());
        
        if (current > 1) {
            input.val(current - 1);
        }
    });
    
    // Validate quantity input
    $('#quantityInput').on('change', function() {
        const min = parseInt($(this).attr('min'));
        const max = parseInt($(this).attr('max'));
        let value = parseInt($(this).val());
        
        if (value < min) {
            $(this).val(min);
        } else if (value > max) {
            $(this).val(max);
            showAlert(`Maximum quantity available: ${max}`, 'warning');
        }
    });
    
    // Add to cart with quantity
    // $('.add-to-cart-btn').click(function() {
    //     const productId = $(this).data('id');
    //     const quantity = $('#quantityInput').val();
        
    //     if (!productId) {
    //         showAlert('Invalid product ID', 'error');
    //         return;
    //     }
        
    //     addToCart(productId, quantity);
    // });
    
    // Wishlist functionality
    $('.wishlist-btn').click(function() {
        const productId = $(this).data('id');
        
        if (!productId) {
            showAlert('Invalid product ID', 'error');
            return;
        }
        
        toggleWishlist(productId);
    });
    
    // Image gallery functionality
    $('.thumbnail').click(function() {
        $('.thumbnail').removeClass('active');
        $(this).addClass('active');
    });
});

// Image gallery functions
function changeMainImage(imageSrc) {
    $('#mainImage').attr('src', imageSrc);
    
    // Update active thumbnail
    $('.thumbnail').removeClass('active');
    event.currentTarget.classList.add('active');
}

function openZoom(imageSrc) {
    $('#zoomImage').attr('src', imageSrc);
    $('#zoomOverlay').fadeIn(300);
    $('body').css('overflow', 'hidden');
}

function closeZoom() {
    $('#zoomOverlay').fadeOut(300);
    $('body').css('overflow', 'auto');
}

// Close zoom with Escape key
$(document).keyup(function(e) {
    if (e.keyCode === 27) { // Escape key
        closeZoom();
    }
});

// Add to cart function
// function addToCart(productId, quantity = 1) {
//     if (!productId) {
//         showAlert('Invalid product ID', 'error');
//         return;
//     }
//     // Show loading state
//     const button = $('.add-to-cart-btn');
//     const originalText = button.html();
//     button.html('<i class="fas fa-spinner fa-spin"></i> Adding...').prop('disabled', true);
    
//     $.ajax({
//         url: 'ajax/add_to_cart.php',
//         method: 'POST',
//         data: {
//             product_id: productId,
//             quantity: quantity
//         },
//         dataType: 'json',
//         success: function(response) {
//             button.html(originalText).prop('disabled', false);
            
//             if (response.success) {
//                 updateCartCount();
//                 showAlert(response.message || 'Product added to cart successfully!', 'success');
                
//                 // Add visual feedback
//                 $('.cart-link').addClass('bounce');
//                 setTimeout(function() {
//                     $('.cart-link').removeClass('bounce');
//                 }, 600);
                
//             } else {
//                 showAlert(response.message || 'Failed to add product to cart', 'error');
//             }
//         },
//         error: function(xhr, status, error) {
//             button.html(originalText).prop('disabled', false);
//             console.error('AJAX Error:', error);
            
//             if (xhr.status === 404) {
//                 showAlert('Cart system not available. Please try again later.', 'error');
//             } else {
//                 showAlert('An error occurred. Please try again.', 'error');
//             }
//         }
//     });
// }

// Wishlist function
function toggleWishlist(productId) {
    const button = $('.wishlist-btn');
    const icon = button.find('i');
    
    // Toggle heart icon
    if (icon.hasClass('far')) {
        icon.removeClass('far').addClass('fas');
        button.css('color', '#EF4444');
        showAlert('Added to wishlist!', 'success');
    } else {
        icon.removeClass('fas').addClass('far');
        button.css('color', '#6B7280');
        showAlert('Removed from wishlist', 'info');
    }
    
    // Placeholder for actual wishlist functionality
    // In a real app, you'd make an AJAX call here
}

// Update cart count
function updateCartCount() {
    $.ajax({
        url: 'ajax/get_cart_count.php',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const count = response.count || 0;
                $('#cartCount').text(count);
                
                if (count > 0) {
                    $('#cartCount').addClass('show');
                } else {
                    $('#cartCount').removeClass('show');
                }
            }
        },
        error: function() {
            console.log('Failed to update cart count');
        }
    });
}

// Show alert messages
function showAlert(message, type = 'info') {
    const alertColors = {
        success: { bg: '#D1FAE5', border: '#10B981', text: '#065F46' },
        error: { bg: '#FEE2E2', border: '#EF4444', text: '#991B1B' },
        warning: { bg: '#FEF3C7', border: '#F59E0B', text: '#92400E' },
        info: { bg: '#DBEAFE', border: '#3B82F6', text: '#1E40AF' }
    };
    
    const colors = alertColors[type] || alertColors.info;
    
    const alert = `
        <div class="alert alert-${type}" style="position: fixed; top: 20px; right: 20px; z-index: 10000; padding: 12px 20px; border-radius: 8px; max-width: 400px; background: ${colors.bg}; border: 1px solid ${colors.border}; color: ${colors.text}; box-shadow: 0 4px 6px rgba(0,0,0,0.1); font-weight: 500;">
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : type === 'warning' ? 'exclamation-triangle' : 'info-circle'}"></i>
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

// Add bounce animation to cart icon
$('<style>.bounce { animation: bounce 0.6s ease; } @keyframes bounce { 0%, 20%, 60%, 100% { transform: translateY(0); } 40% { transform: translateY(-10px); } 80% { transform: translateY(-5px); } }</style>').appendTo('head');

// Smooth scroll to top when clicking breadcrumbs
$('.breadcrumb a').click(function(e) {
    if ($(this).attr('href') === '#') {
        e.preventDefault();
        $('html, body').animate({scrollTop: 0}, 500);
    }
});

// Add loading spinner styles
$('<style>.fa-spinner { animation: spin 1s linear infinite; } @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }</style>').appendTo('head');

// Initialize page
$(document).ready(function() {
    // Update cart count on page load
    updateCartCount();
    
    // Add fade-in animation to product details
    $('.product-info').css('opacity', '0').animate({opacity: 1}, 800);
    $('.product-gallery').css('opacity', '0').animate({opacity: 1}, 600);
});