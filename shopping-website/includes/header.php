<!-- Navigation Header -->
<header class="main-header">
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-brand">
                <a href="<?php echo SITE_URL; ?>/index.php" class="brand-link">
                    <i class="fas fa-shopping-bag"></i>
                    <span><?php echo SITE_NAME; ?></span>
                </a>
            </div>
            
            <div class="nav-search">
                <form class="search-form" action="<?php echo SITE_URL; ?>/shop.php" method="GET">
                    <input type="search" name="search" placeholder="Search products..." 
                           class="search-input" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>
            
            <div class="nav-actions">
                <?php if (User::isLoggedIn()): ?>
                    <div class="user-menu">
                        <span class="user-greeting">Hi, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                        <div class="dropdown">
                            <button class="dropdown-btn">
                                <i class="fas fa-user"></i>
                            </button>
                            <div class="dropdown-content">
                                <a href="<?php echo SITE_URL; ?>/account.php"><i class="fas fa-user-circle"></i> My Account</a>
                                <?php if (User::isAdmin()): ?>
                                    <a href="<?php echo SITE_URL; ?>/admin/"><i class="fas fa-cog"></i> Admin Panel</a>
                                <?php endif; ?>
                                <a href="<?php echo SITE_URL; ?>/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="<?php echo SITE_URL; ?>/login.php" class="nav-link">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </a>
                    <a href="<?php echo SITE_URL; ?>/register.php" class="nav-link signup-btn">
                        <i class="fas fa-user-plus"></i> Sign Up
                    </a>
                <?php endif; ?>
                
                <a href="<?php echo SITE_URL; ?>/cart.php" class="cart-link">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-count" id="cartCount"><?php echo get_cart_count(); ?></span>
                </a>
            </div>
            
            <button class="mobile-menu-btn">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </nav>
    
    <!-- Main Navigation Menu -->
    <div class="main-nav">
        <div class="nav-container">
            <ul class="nav-menu">
                <li><a href="<?php echo SITE_URL; ?>/index.php" <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'class="active"' : ''; ?>>Home</a></li>
                <li><a href="<?php echo SITE_URL; ?>/shop.php" <?php echo basename($_SERVER['PHP_SELF']) == 'shop.php' ? 'class="active"' : ''; ?>>Shop</a></li>
                <li class="dropdown-nav">
                    <a href="#">Categories <i class="fas fa-chevron-down"></i></a>
                    <ul class="dropdown-menu">
                        <li><a href="<?php echo SITE_URL; ?>/shop.php?category=electronics">Electronics</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/shop.php?category=clothing">Clothing</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/shop.php?category=books">Books</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/shop.php?category=home-garden">Home & Garden</a></li>
                    </ul>
                </li>
                <li><a href="<?php echo SITE_URL; ?>/about.php">About</a></li>
                <li><a href="<?php echo SITE_URL; ?>/contact.php">Contact</a></li>
            </ul>
        </div>
    </div>
</header>