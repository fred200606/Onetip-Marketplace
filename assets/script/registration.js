document.addEventListener('DOMContentLoaded', function() {
    const registerForm = document.getElementById('registerForm');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirmPassword');
    const signupBtn = document.getElementById('signupBtn');

    registerForm.addEventListener('submit', function(e) {
        // ✅ Run validation first
        if (!validateForm()) {
            e.preventDefault(); // stop submission only if invalid
            return;
        }

        // ✅ Optional: small UI feedback
        signupBtn.textContent = 'Creating account...';
        signupBtn.disabled = true;
    });

    function validateForm() {
        const password = passwordInput.value.trim();
        const confirmPassword = confirmPasswordInput.value.trim();

        if (password !== confirmPassword) {
            alert('Passwords do not match');
            return false;
        }

        if (password.length < 8) {
            alert('Password must be at least 8 characters long');
            return false;
        }

        return true;
    }
});
