
$(document).ready(function() {
    // Quantity controls
    $('.qty-increase').click(function() {
        const productId = $(this).data('id');
        const input = $(this).siblings('.qty-input');
        const max = parseInt(input.attr('max'));
        const current = parseInt(input.val());
        
        if (current < max) {
            const newValue = current + 1;
            input.val(newValue);
            updateQuantity(productId, newValue);
        }
    });
    
    $('.qty-decrease').click(function() {
        const productId = $(this).data('id');
        const input = $(this).siblings('.qty-input');
        const current = parseInt(input.val());
        
        if (current > 1) {
            const newValue = current - 1;
            input.val(newValue);
            updateQuantity(productId, newValue);
        }
    });
    
    // Remove item
    $('.remove-btn').click(function() {
        const productId = $(this).data('id');
        const itemName = $(this).closest('.cart-item').find('h3 a').text().trim();
        
        if (confirm(`Remove "${itemName}" from your cart?`)) {
            removeItem(productId);
        }
    });
    
    // Add visual feedback for interactions
    $('.cart-item').hover(
        function() { $(this).addClass('hover'); },
        function() { $(this).removeClass('hover'); }
    );
});

function updateQuantity(productId, quantity) {
    const cartItem = $(`.cart-item[data-product-id="${productId}"]`);
    cartItem.addClass('updating');
    
    $.ajax({
        url: 'cart.php',
        method: 'POST',
        data: {
            action: 'update_quantity',
            product_id: productId,
            quantity: quantity
        },
        success: function() {
            location.reload();
        },
        error: function() {
            cartItem.removeClass('updating');
            showAlert('Failed to update quantity', 'error');
        }
    });
}

function removeItem(productId) {
    const cartItem = $(`.cart-item[data-product-id="${productId}"]`);
    cartItem.addClass('loading');
    
    $.ajax({
        url: 'cart.php',
        method: 'POST',
        data: {
            action: 'remove_item',
            product_id: productId
        },
        success: function() {
            cartItem.fadeOut(300, function() {
                location.reload();
            });
        },
        error: function() {
            cartItem.removeClass('loading');
            showAlert('Failed to remove item', 'error');
        }
    });
}

function clearCart() {
    if (confirm('Are you sure you want to clear your entire cart?')) {
        $('.cart-items-section').addClass('loading');
        
        $.ajax({
            url: 'cart.php',
            method: 'POST',
            data: {
                action: 'clear_cart'
            },
            success: function() {
                location.reload();
            },
            error: function() {
                $('.cart-items-section').removeClass('loading');
                showAlert('Failed to clear cart', 'error');
            }
        });
    }
}

function applyCoupon() {
    const code = $('#couponCode').val().trim();
    
    if (!code) {
        showAlert('Please enter a coupon code', 'error');
        return;
    }
    
    $('.apply-coupon-btn').addClass('loading');
    
    // Placeholder for coupon functionality
    setTimeout(function() {
        $('.apply-coupon-btn').removeClass('loading');
        showAlert('Coupon system coming soon!', 'info');
    }, 1000);
}

function proceedToCheckout() {
    // Add loading state
    $('.checkout-btn').addClass('loading').text('Processing...');
    
    // Simulate checkout process
    setTimeout(function() {
        window.location.href = 'checkout.php';
    }, 1000);
}

function showAlert(message, type = 'info') {
    const alertClass = type === 'error' ? 'alert-error' : (type === 'success' ? 'alert-success' : 'alert-info');
    const alert = `
        <div class="alert ${alertClass}" style="position: fixed; top: 20px; right: 20px; z-index: 10000; padding: 12px 20px; border-radius: 8px; max-width: 400px;">
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
