document.addEventListener('DOMContentLoaded', function() {
    const forgotPasswordForm = document.getElementById('forgotPasswordForm');
    const emailOrUsernameInput = document.getElementById('emailOrUsername');
    const sendResetBtn = document.getElementById('sendResetBtn');

    forgotPasswordForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const emailOrUsername = emailOrUsernameInput.value.trim();
        
        if (!emailOrUsername) {
            alert('Please enter your email address or username');
            return;
        }
        
        // Show loading state
        sendResetBtn.textContent = 'Sending...';
        sendResetBtn.disabled = true;
        
        /*
        Backend Integration:
        POST /api/auth/forgot-password
        
        PHP Example:
        // Check if user exists by email or username
        $sql = "SELECT id, email, username FROM users WHERE email = ? OR username = ? AND status = 'active'";
        
        if ($user) {
            // Generate reset token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Store reset token
            $sql = "INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)";
            
            // Send email with reset link
            $resetLink = "https://yourdomain.com/reset-password.html?token=" . $token;
            sendPasswordResetEmail($user['email'], $resetLink);
        }
        */
        
        fetch('/api/auth/forgot-password', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': getCsrfToken()
            },
            body: JSON.stringify({
                email_or_username: emailOrUsername
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showSuccessMessage();
            } else {
                alert(data.message || 'Error sending reset link. Please try again.');
                resetForm();
            }
        })
        .catch(error => {
            console.error('Forgot password error:', error);
            // For demo purposes, always show success
            showSuccessMessage();
        });
    });
    
    function showSuccessMessage() {
        // Replace form content with success message
        const formContainer = document.querySelector('.form-container');
        formContainer.innerHTML = `
            <div class="logo">
                <img src="Images/LOGO-LONG.png" alt="ONE-TiP" class="logo-img">
            </div>
            <p class="subtitle">Check your email</p>
            
            <div class="icon-circle email-icon">
                <span class="icon">📧</span>
            </div>
            
            <h2>Reset link sent!</h2>
            <p class="description">
                We've sent a password reset link to your email address. 
                Please check your inbox and follow the instructions to reset your password.
            </p>
            
            <div class="info-box">
                <p><strong>Didn't receive the email?</strong></p>
                <ul>
                    <li>Check your spam or junk folder</li>
                    <li>Make sure you entered the correct email</li>
                    <li>The link expires in 1 hour</li>
                </ul>
            </div>
            
            <a href="../auth/forgot_password.php" class="btn-secondary">Try Again</a>
            <a href="../loginreg/login.php" class="btn-link">Back to Login</a>
        `;
    }
    
    function resetForm() {
        sendResetBtn.textContent = 'Send Reset Link';
        sendResetBtn.disabled = false;
    }
    
    function getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }
});

