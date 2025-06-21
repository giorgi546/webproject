<?php
require_once 'includes/config.php';

// Get some statistics for the about page
$stats = [];

// Total products
$stmt = $db->prepare("SELECT COUNT(*) as total FROM products WHERE status = 'active'");
$stmt->execute();
$stats['products'] = $stmt->fetch()['total'];

// Total customers
$stmt = $db->prepare("SELECT COUNT(*) as total FROM users WHERE role = 'customer'");
$stmt->execute();
$stats['customers'] = $stmt->fetch()['total'];

// Total orders
$stmt = $db->prepare("SELECT COUNT(*) as total FROM orders");
$stmt->execute();
$stats['orders'] = $stmt->fetch()['total'];

// Years in business (since the site was created)
$stats['years'] = date('Y') - 2020; // Assuming business started in 2020
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - <?php echo SITE_NAME; ?></title>
    <meta name="description" content="Learn about <?php echo SITE_NAME; ?> - your trusted partner for quality products and exceptional shopping experience.">
    
    <link rel="stylesheet" href="css/about.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    
   
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <!-- Hero Section -->
    <section class="about-hero">
        <div class="floating-shapes">
            <div class="shape shape-1"></div>
            <div class="shape shape-2"></div>
            <div class="shape shape-3"></div>
        </div>
        <div class="container">
            <div class="about-hero-content">
                <h1>About <?php echo SITE_NAME; ?></h1>
                <p>We're passionate about bringing you the best products with exceptional service and unbeatable value.</p>
            </div>
        </div>
    </section>

    <!-- Story Section -->
    <section class="about-section">
        <div class="container">
            <div class="about-content">
                <div class="about-text">
                    <h2>Our Story</h2>
                    <p>Founded with a simple mission: to make online shopping <span class="highlight">easy, affordable, and enjoyable</span> for everyone. What started as a small idea has grown into a trusted platform serving thousands of customers worldwide.</p>
                    <p>We believe that great products shouldn't be complicated to find or expensive to buy. That's why we've carefully curated our selection to include only the <span class="highlight">highest quality items</span> at prices that won't break the bank.</p>
                    <p>Every day, we work hard to improve your shopping experience, from our user-friendly website to our lightning-fast shipping and responsive customer service.</p>
                </div>
                <div class="about-image">
                    <img src="https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="Our team working">
                </div>
            </div>

            <div class="about-content reverse">
                <div class="about-text">
                    <h2>Our Mission</h2>
                    <p>To revolutionize online shopping by providing a <span class="highlight">seamless, secure, and satisfying</span> experience that exceeds customer expectations every single time.</p>
                    <p>We're committed to building lasting relationships with our customers through transparency, reliability, and genuine care for their needs.</p>
                    <p>Our goal isn't just to sell products – it's to become your <span class="highlight">trusted shopping partner</span> for life.</p>
                </div>
                <div class="about-image">
                    <img src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="Customer service">
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-item">
                    <span class="stat-number"><?php echo number_format($stats['products']); ?>+</span>
                    <span class="stat-label">Products</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?php echo number_format($stats['customers']); ?>+</span>
                    <span class="stat-label">Happy Customers</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?php echo number_format($stats['orders']); ?>+</span>
                    <span class="stat-label">Orders Delivered</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?php echo $stats['years']; ?>+</span>
                    <span class="stat-label">Years Experience</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Values Section -->
    <section class="values-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Our Values</h2>
                <p class="section-subtitle">The principles that guide everything we do and help us deliver exceptional experiences to our customers.</p>
            </div>
            
            <div class="values-grid">
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-heart"></i>
                    </div>
                    <h3>Customer First</h3>
                    <p>Every decision we make starts with our customers. Your satisfaction, security, and success are our top priorities in everything we do.</p>
                </div>
                
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3>Trust & Security</h3>
                    <p>We protect your personal information and ensure secure transactions with industry-leading encryption and privacy measures.</p>
                </div>
                
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <h3>Quality Excellence</h3>
                    <p>We carefully curate every product in our catalog to ensure you receive only the highest quality items that exceed your expectations.</p>
                </div>
                
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-rocket"></i>
                    </div>
                    <h3>Innovation</h3>
                    <p>We continuously improve our platform, services, and processes to provide you with the most modern and efficient shopping experience.</p>
                </div>
                
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-handshake"></i>
                    </div>
                    <h3>Integrity</h3>
                    <p>Honest pricing, transparent policies, and reliable service. We believe in doing business the right way, every single time.</p>
                </div>
                
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-globe"></i>
                    </div>
                    <h3>Community</h3>
                    <p>We're committed to supporting our local communities and making a positive impact through responsible business practices.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Team Section -->
    <section class="team-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Meet Our Team</h2>
                <p class="section-subtitle">The passionate people behind <?php echo SITE_NAME; ?> who work tirelessly to bring you the best shopping experience.</p>
            </div>
            
            <div class="team-grid">
                <div class="team-card">
                    <div class="team-image">
                        <img src="./uploads/products/images/c8394846-342a-4045-8653-947b4f9ffd4c.jpg" alt="Giorgi Khomeriki">
                    </div>
                    <div class="team-info">
                        <h3 class="team-name">Giorgi Khomeriki</h3>
                        <div class="team-role">Founder & CEO</div>
                        <p class="team-bio">Passionate about e-commerce and customer experience. Girogi founded <?php echo SITE_NAME; ?> with the vision of making online shopping simple and enjoyable for everyone.</p>
                        <div class="social-links">
                            <a href="#" class="social-link"><i class="fab fa-linkedin"></i></a>
                            <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                        </div>
                    </div>
                </div>
                
                <div class="team-card">
                    <div class="team-image">
                        <img src="./uploads/products/images/c8394846-342a-4045-8653-947b4f9ffd4c.jpg" alt="Giorgi Khomeriki">
                    </div>
                    <div class="team-info">
                        <h3 class="team-name">Giorgi Khomeriki</h3>
                        <div class="team-role">LEAD OF CYBERSECURITY</div>
                        <p class="team-bio">Protects our digital world from threats. Giorgi oversees all cybersecurity measures, ensuring our systems, data, and users stay safe and secure day and night.</p>
                        <div class="social-links">
                            <a href="#" class="social-link"><i class="fab fa-linkedin"></i></a>
                            <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                        </div>
                    </div>
                </div>
                
                <div class="team-card">
                    <div class="team-image">
                        <img src="./uploads/products/images/c8394846-342a-4045-8653-947b4f9ffd4c.jpg" alt="Giorgi Khomeriki">
                    </div>
                    <div class="team-info">
                        <h3 class="team-name">Giorgi Khomeriki</h3>
                        <div class="team-role">Lead Developer</div>
                        <p class="team-bio">The tech wizard who keeps our website running perfectly. Giorgi constantly improves our platform to make your shopping experience better and more secure.</p>
                        <div class="social-links">
                            <a href="#" class="social-link"><i class="fab fa-github"></i></a>
                            <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <div class="cta-content">
                <h2>Ready to Start Shopping?</h2>
                <p>Join thousands of satisfied customers who trust <?php echo SITE_NAME; ?> for their shopping needs.</p>
                <div class="cta-buttons">
                    <a href="shop.php" class="btn-cta">
                        <i class="fas fa-shopping-bag"></i>
                        Browse Products
                    </a>
                    <a href="contact.php" class="btn-cta btn-outline">
                        <i class="fas fa-envelope"></i>
                        Contact Us
                    </a>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <!-- Display Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error">
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="js/about.js"></script>
    
</body>
</html>