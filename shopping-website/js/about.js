
$(document).ready(function() {
    // Animate stats numbers when they come into view
    function animateStats() {
        $('.stat-number').each(function() {
            const $this = $(this);
            const target = parseInt($this.text().replace(/[^0-9]/g, ''));
            const duration = 2000;
            const step = target / (duration / 50);
            let current = 0;
            
            const timer = setInterval(function() {
                current += step;
                if (current >= target) {
                    current = target;
                    clearInterval(timer);
                }
                $this.text(Math.floor(current).toLocaleString() + '+');
            }, 50);
        });
    }
    
    // Trigger animation when stats section is in view
    const statsSection = $('.stats-section');
    let statsAnimated = false;
    
    $(window).scroll(function() {
        if (!statsAnimated && isInViewport(statsSection[0])) {
            animateStats();
            statsAnimated = true;
        }
    });
    
    // Check if element is in viewport
    function isInViewport(element) {
        const rect = element.getBoundingClientRect();
        return (
            rect.top >= 0 &&
            rect.left >= 0 &&
            rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
            rect.right <= (window.innerWidth || document.documentElement.clientWidth)
        );
    }
    
    // Add scroll animations to value cards
    $(window).scroll(function() {
        $('.value-card').each(function() {
            const elementTop = $(this).offset().top;
            const elementBottom = elementTop + $(this).outerHeight();
            const viewportTop = $(window).scrollTop();
            const viewportBottom = viewportTop + $(window).height();
            
            if (elementBottom > viewportTop && elementTop < viewportBottom) {
                $(this).addClass('animate-in');
            }
        });
    });
    
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
    
    // Add parallax effect to hero section
    $(window).scroll(function() {
        const scrolled = $(this).scrollTop();
        const parallax = $('.about-hero');
        const speed = scrolled * 0.5;
        
        parallax.css('transform', 'translateY(' + speed + 'px)');
    });
    
    // Add hover effects to team cards
    $('.team-card').hover(
        function() {
            $(this).find('.social-links').fadeIn(300);
        },
        function() {
            $(this).find('.social-links').fadeOut(300);
        }
    );
    
    // Auto-hide alerts
    setTimeout(function() {
        $('.alert').fadeOut(300);
    }, 5000);
    
    // Add CSS for animated elements
    $('<style>')
        .prop('type', 'text/css')
        .html(`
            .animate-in {
                animation: slideInUp 0.6s ease-out;
            }
            
            .social-links {
                display: none;
            }
            
            .team-card:hover .social-links {
                display: flex !important;
            }
        `)
        .appendTo('head');
});
