<?php
require_once 'includes/config.php';

// Redirect if already logged in
if (User::isLoggedIn()) {
    header('Location: account.php');
    exit;
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = new User($db);
    
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);
    
    if ($user->login($email, $password, $remember)) {
        $redirect = $_GET['redirect'] ?? 'account.php';
        header('Location: ' . $redirect);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_NAME; ?></title>

    <link rel="stylesheet" href="css/login.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
   
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-logo">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <h1 class="auth-title">Welcome Back</h1>
                <p class="auth-subtitle">Sign in to your account to continue shopping</p>
            </div>
            
            <form method="POST" class="auth-form" id="loginForm">
                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope input-icon"></i>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            class="form-input with-icon" 
                            placeholder="Enter your email"
                            value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                            required
                        >
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-input with-icon" 
                            placeholder="Enter your password"
                            minlength="<?php echo PASSWORD_MIN_LENGTH; ?>"
                            required
                        >
                    </div>
                    <div class="forgot-password">
                        <a href="forgot-password.php">Forgot your password?</a>
                    </div>
                </div>
                
                <div class="checkbox-group">
                    <input type="checkbox" id="remember" name="remember" class="checkbox">
                    <label for="remember" class="checkbox-label">Remember me for 30 days</label>
                </div>
                
                <button type="submit" class="auth-btn">
                    <i class="fas fa-sign-in-alt"></i>
                    Sign In
                </button>
            </form>
            
            <div class="social-divider">
                <span>or continue with</span>
            </div>
            
            <div class="social-login">
                <button type="button" class="social-btn">
                    <i class="fab fa-google"></i>
                    Continue with Google
                </button>
                <button type="button" class="social-btn">
                    <i class="fab fa-facebook"></i>
                    Continue with Facebook
                </button>
            </div>
            
            <div class="auth-footer">
                <p>Don't have an account? <a href="register.php" class="auth-link">Sign up for free</a></p>
                <p><a href="index.php" class="auth-link">← Back to Home</a></p>
            </div>
        </div>
    </div>

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
    

    <script>
        $(document).ready(function() {
            // Form validation
            $('#loginForm').submit(function(e) {
                let isValid = true;
                
                // Email validation
                const email = $('#email').val();
                if (!email || !validateEmail(email)) {
                    $('#email').addClass('error');
                    isValid = false;
                } else {
                    $('#email').removeClass('error');
                }
                
                // Password validation
                const password = $('#password').val();
                if (!password || password.length < <?php echo PASSWORD_MIN_LENGTH; ?>) {
                    $('#password').addClass('error');
                    isValid = false;
                } else {
                    $('#password').removeClass('error');
                }
                
                if (!isValid) {
                    e.preventDefault();
                    showAlert('Please fill in all fields correctly', 'error');
                }
            });
            
            // Clear errors on input
            $('.form-input').on('input', function() {
                $(this).removeClass('error');
            });
            
            // Auto-hide alerts
            setTimeout(function() {
                $('.alert').fadeOut(300);
            }, 5000);
        });
        
        function validateEmail(email) {
            const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return regex.test(email);
        }
        
        function showAlert(message, type = 'info') {
            const alert = `
                <div class="alert alert-${type}" style="position: fixed; top: 20px; right: 20px; z-index: 10000; padding: 12px 20px; border-radius: 8px; max-width: 400px;">
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
    </script>
</body>
</html>