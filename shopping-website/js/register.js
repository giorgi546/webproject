$(document).ready(function() {
            // Password strength checker
            $('#password').on('input', function() {
                checkPasswordStrength($(this).val());
                checkPasswordMatch();
                updateSubmitButton();
            });
            
            // Confirm password checker
            $('#confirm_password').on('input', function() {
                checkPasswordMatch();
                updateSubmitButton();
            });
            
            // Form validation
            $('#registerForm').submit(function(e) {
                if (!validateForm()) {
                    e.preventDefault();
                    return false;
                }
                
                // Show loading overlay
                $('#loadingOverlay').show();
            });
            
            // Clear errors on input
            $('.form-input').on('input', function() {
                $(this).removeClass('error');
            });
            
            // Terms checkbox
            $('#terms').on('change', function() {
                updateSubmitButton();
            });
            
            // Real-time form validation
            $('.form-input[required]').on('blur', function() {
                validateField($(this));
            });
            
            // Auto-hide alerts
            setTimeout(function() {
                $('.alert').fadeOut(300);
            }, 5000);
        });
        
        
        
        function checkPasswordMatch() {
            const password = $('#password').val();
            const confirmPassword = $('#confirm_password').val();
            const matchDiv = $('#passwordMatch');
            
            if (confirmPassword.length > 0) {
                if (password === confirmPassword) {
                    matchDiv.html('<span style="color: #10B981;"><i class="fas fa-check"></i> Passwords match</span>');
                    $('#confirm_password').removeClass('error');
                } else {
                    matchDiv.html('<span style="color: #EF4444;"><i class="fas fa-times"></i> Passwords do not match</span>');
                    $('#confirm_password').addClass('error');
                }
            } else {
                matchDiv.html('');
                $('#confirm_password').removeClass('error');
            }
        }
        
        
        function validateEmail(email) {
            const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return regex.test(email);
        }
        
        function showAlert(message, type = 'info') {
            const alertClass = type === 'error' ? 'alert-error' : 'alert-success';
            const bgColor = type === 'error' ? '#FEE2E2' : '#D1FAE5';
            const borderColor = type === 'error' ? '#EF4444' : '#10B981';
            const textColor = type === 'error' ? '#991B1B' : '#065F46';
            
            const alert = `
                <div class="alert ${alertClass}" style="position: fixed; top: 20px; right: 20px; z-index: 10000; padding: 12px 20px; border-radius: 8px; max-width: 400px; background: ${bgColor}; border: 1px solid ${borderColor}; color: ${textColor};">
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
        
        // Social login functions (placeholders)
        function signUpWithGoogle() {
            showAlert('Google registration coming soon!', 'info');
        }
        
        function signUpWithFacebook() {
            showAlert('Facebook registration coming soon!', 'info');
        }
        
        // Auto-fill form validation on page load
        updateSubmitButton();