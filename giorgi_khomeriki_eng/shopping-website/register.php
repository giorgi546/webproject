<?php
require_once 'includes/config.php';

// Redirect if already logged in
if (User::isLoggedIn()) {
    header('Location: account.php');
    exit;
}

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = new User($db);
    
    $data = [
        'email' => trim($_POST['email']),
        'password' => $_POST['password'],
        'first_name' => trim($_POST['first_name']),
        'last_name' => trim($_POST['last_name']),
        'phone' => trim($_POST['phone']) ?: null
    ];
    
    // Confirm password validation
    if ($_POST['password'] !== $_POST['confirm_password']) {
        $_SESSION['error'] = "Passwords do not match";
    } else if ($user->register($data)) {
        header('Location: login.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - <?php echo SITE_NAME; ?></title>
    <meta name="description" content="Create your account and start shopping today">
    
    <link rel="stylesheet" href="css/register.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
  
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="loading-overlay" id="loadingOverlay">
                <div class="loading-spinner"></div>
            </div>
            
            <div class="auth-header">
                <div class="auth-logo">
                    <i class="fas fa-user-plus"></i>
                </div>
                <h1 class="auth-title">Create Account</h1>
                <p class="auth-subtitle">Join us today and start your shopping journey</p>
            </div>
            
            <form method="POST" class="auth-form" id="registerForm">
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name" class="form-label">
                            First Name <span class="required">*</span>
                        </label>
                        <div class="input-wrapper">
                            <input 
                                type="text" 
                                id="first_name" 
                                name="first_name" 
                                class="form-input with-icon" 
                                placeholder="Enter your first name"
                                value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>"
                                required
                                maxlength="100"
                                autocomplete="given-name"
                            >
                            <i class="fas fa-user input-icon"></i>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="last_name" class="form-label">
                            Last Name <span class="required">*</span>
                        </label>
                        <div class="input-wrapper">
                            <input 
                                type="text" 
                                id="last_name" 
                                name="last_name" 
                                class="form-input with-icon" 
                                placeholder="Enter your last name"
                                value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>"
                                required
                                maxlength="100"
                                autocomplete="family-name"
                            >
                            <i class="fas fa-user input-icon"></i>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="email" class="form-label">
                        Email Address <span class="required">*</span>
                    </label>
                    <div class="input-wrapper">
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            class="form-input with-icon" 
                            placeholder="Enter your email address"
                            value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                            required
                            maxlength="255"
                            autocomplete="email"
                        >
                        <i class="fas fa-envelope input-icon"></i>
                    </div>
                    <div class="field-help">We'll send you a verification email</div>
                </div>
                
                <div class="form-group">
                    <label for="phone" class="form-label">Phone Number</label>
                    <div class="input-wrapper">
                        <input 
                            type="tel" 
                            id="phone" 
                            name="phone" 
                            class="form-input with-icon" 
                            placeholder="Enter your phone number"
                            value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                            pattern="[0-9\-\+\(\)\s]+"
                            maxlength="20"
                            autocomplete="tel"
                        >
                        <i class="fas fa-phone input-icon"></i>
                    </div>
                    <div class="field-help">Optional - for order updates and notifications</div>
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">
                        Password <span class="required">*</span>
                    </label>
                    <div class="input-wrapper">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-input with-icon" 
                            placeholder="Create a strong password"
                            minlength="<?php echo PASSWORD_MIN_LENGTH; ?>"
                            required
                            autocomplete="new-password"
                        >
                        <i class="fas fa-lock input-icon"></i>
                    </div>
                    <div class="password-strength" id="passwordStrength">
                        <div class="strength-text">Password strength: <span id="strengthText">Too short</span></div>
                        <div class="strength-bar">
                            <div class="strength-fill" id="strengthFill"></div>
                        </div>
                    </div>
                    <div class="field-help">
                        Minimum <?php echo PASSWORD_MIN_LENGTH; ?> characters. Use letters, numbers, and symbols for better security.
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password" class="form-label">
                        Confirm Password <span class="required">*</span>
                    </label>
                    <div class="input-wrapper">
                        <input 
                            type="password" 
                            id="confirm_password" 
                            name="confirm_password" 
                            class="form-input with-icon" 
                            placeholder="Confirm your password"
                            minlength="<?php echo PASSWORD_MIN_LENGTH; ?>"
                            required
                            autocomplete="new-password"
                        >
                        <i class="fas fa-lock input-icon"></i>
                    </div>
                    <div id="passwordMatch" class="field-help"></div>
                </div>
                
                <div class="checkbox-group">
                    <input type="checkbox" id="terms" name="terms" class="checkbox" required>
                    <label for="terms" class="checkbox-label">
                        I agree to the <a href="terms.php" target="_blank">Terms of Service</a> 
                        and <a href="privacy.php" target="_blank">Privacy Policy</a>
                    </label>
                </div>
                
                <div class="checkbox-group">
                    <input type="checkbox" id="newsletter" name="newsletter" class="checkbox">
                    <label for="newsletter" class="checkbox-label">
                        Send me promotional emails about special offers and new products
                    </label>
                </div>
                
                <button type="submit" class="auth-btn" id="submitBtn" disabled>
                    <i class="fas fa-user-plus"></i>
                    Create My Account
                </button>
            </form>
            
            <div class="social-divider">
                <span>or sign up with</span>
            </div>
            
            <div class="social-login">
                <button type="button" class="social-btn" onclick="signUpWithGoogle()">
                    <i class="fab fa-google" style="color: #DB4437;"></i>
                    Continue with Google
                </button>
                <button type="button" class="social-btn" onclick="signUpWithFacebook()">
                    <i class="fab fa-facebook" style="color: #4267B2;"></i>
                    Continue with Facebook
                </button>
            </div>
            
            <div class="auth-footer">
                <p>Already have an account? <a href="login.php" class="auth-link">Sign in here</a></p>
                <p><a href="index.php" class="auth-link">← Back to Home</a></p>
            </div>
        </div>
    </div>

    <!-- Display Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success" style="position: fixed; top: 20px; right: 20px; z-index: 10000; padding: 12px 20px; border-radius: 8px; max-width: 400px; background: #D1FAE5; border: 1px solid #10B981; color: #065F46;">
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error" style="position: fixed; top: 20px; right: 20px; z-index: 10000; padding: 12px 20px; border-radius: 8px; max-width: 400px; background: #FEE2E2; border: 1px solid #EF4444; color: #991B1B;">
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="js/register.js"></script>


    <script>
        function checkPasswordStrength(password) {
            const strengthBar = $('#passwordStrength');
            const strengthText = $('#strengthText');
            const strengthFill = $('#strengthFill');
            
            let strength = 0;
            let text = 'Too short';
            let className = '';
            
            if (password.length >= <?php echo PASSWORD_MIN_LENGTH; ?>) {
                strength = 1;
                text = 'Weak';
                className = 'strength-weak';
                
                // Check for mixed case, numbers, and symbols
                const hasLower = /[a-z]/.test(password);
                const hasUpper = /[A-Z]/.test(password);
                const hasNumbers = /[0-9]/.test(password);
                const hasSymbols = /[!@#$%^&*(),.?":{}|<>]/.test(password);
                
                if (password.length >= 10 && hasUpper && hasNumbers) {
                    strength = 2;
                    text = 'Medium';
                    className = 'strength-medium';
                    
                    if (password.length >= 12 && hasSymbols && hasLower) {
                        strength = 3;
                        text = 'Strong';
                        className = 'strength-strong';
                    }
                }
            }
            
            strengthText.text(text);
            strengthBar.removeClass('strength-weak strength-medium strength-strong').addClass(className);
        }

        function updateSubmitButton() {
            const password = $('#password').val();
            const confirmPassword = $('#confirm_password').val();
            const termsChecked = $('#terms').is(':checked');
            const firstName = $('#first_name').val().trim();
            const lastName = $('#last_name').val().trim();
            const email = $('#email').val().trim();
            
            const isValid = password.length >= <?php echo PASSWORD_MIN_LENGTH; ?> && 
                           password === confirmPassword && 
                           termsChecked &&
                           firstName &&
                           lastName &&
                           validateEmail(email);
            
            $('#submitBtn').prop('disabled', !isValid);
            
            if (isValid) {
                $('#submitBtn').html('<i class="fas fa-user-plus"></i> Create My Account');
            } else {
                $('#submitBtn').html('<i class="fas fa-user-plus"></i> Please Complete Form');
            }
        }

        function validateField(field) {
            const value = field.val().trim();
            const fieldName = field.attr('name');
            let isValid = true;
            
            if (field.prop('required') && !value) {
                isValid = false;
            } else if (fieldName === 'email' && !validateEmail(value)) {
                isValid = false;
            } else if (fieldName === 'password' && value.length < <?php echo PASSWORD_MIN_LENGTH; ?>) {
                isValid = false;
            }
            
            if (isValid) {
                field.removeClass('error');
            } else {
                field.addClass('error');
            }
            
            return isValid;
        }

        function validateForm() {
            let isValid = true;
            
            // Required fields validation
            const requiredFields = ['first_name', 'last_name', 'email', 'password', 'confirm_password'];
            requiredFields.forEach(function(field) {
                const input = $('#' + field);
                if (!input.val().trim()) {
                    input.addClass('error');
                    isValid = false;
                } else {
                    input.removeClass('error');
                }
            });
            
            // Email validation
            const email = $('#email').val();
            if (!validateEmail(email)) {
                $('#email').addClass('error');
                isValid = false;
            }
            
            // Password validation
            const password = $('#password').val();
            const confirmPassword = $('#confirm_password').val();
            
            if (password.length < <?php echo PASSWORD_MIN_LENGTH; ?>) {
                $('#password').addClass('error');
                isValid = false;
            }
            
            if (password !== confirmPassword) {
                $('#confirm_password').addClass('error');
                isValid = false;
            }
            
            // Terms checkbox
            if (!$('#terms').is(':checked')) {
                showAlert('You must agree to the Terms of Service', 'error');
                isValid = false;
            }
            
            if (!isValid) {
                showAlert('Please correct the errors in the form', 'error');
            }
            
            return isValid;
        }

    </script>
</body>
</html>