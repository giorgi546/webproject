
$(document).ready(function() {
    // Handle sidebar menu clicks
    $('.menu-link').click(function(e) {
        e.preventDefault();
        
        const section = $(this).data('section');
        
        // Update active menu item
        $('.menu-link').removeClass('active');
        $(this).addClass('active');
        
        // Show corresponding section
        $('.account-section').removeClass('active');
        $('#' + section).addClass('active');
        
        // Update URL hash
        window.location.hash = section;
    });
    
    // Handle browser back/forward
    $(window).on('hashchange', function() {
        const hash = window.location.hash.substring(1);
        if (hash) {
            $('.menu-link').removeClass('active');
            $(`.menu-link[data-section="${hash}"]`).addClass('active');
            
            $('.account-section').removeClass('active');
            $('#' + hash).addClass('active');
        }
    });
    
    // Set initial section from URL hash
    const initialHash = window.location.hash.substring(1);
    if (initialHash && $('#' + initialHash).length) {
        $('.menu-link').removeClass('active');
        $(`.menu-link[data-section="${initialHash}"]`).addClass('active');
        
        $('.account-section').removeClass('active');
        $('#' + initialHash).addClass('active');
    }
    
    // Password confirmation validation
    $('#confirm_password').on('input', function() {
        const newPassword = $('#new_password').val();
        const confirmPassword = $(this).val();
        
        if (confirmPassword && newPassword !== confirmPassword) {
            $(this).css('border-color', '#EF4444');
        } else {
            $(this).css('border-color', '#E5E7EB');
        }
    });
    
    // Auto-hide alerts
    setTimeout(function() {
        $('.alert').fadeOut(300);
    }, 5000);
});
 