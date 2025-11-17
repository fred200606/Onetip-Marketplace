document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const loginBtn = document.getElementById('loginBtn');

    loginForm.addEventListener('submit', function(e) {
        const email = emailInput.value.trim();
        const password = passwordInput.value.trim();

        if (!email || !password) {
            e.preventDefault();
            alert('Please fill in all fields');
            return;
        }

        if (!isValidEmail(email)) {
            e.preventDefault();
            alert('Please enter a valid TiP email address');
            return;
        }

        // Show loading state (optional)
        loginBtn.textContent = 'Logging in...';
        loginBtn.disabled = true;
    });
    
    function isValidEmail(email) {
        const emailRegex = /^[a-zA-Z0-9._%+-]+@tip\.edu\.ph$/;
        return emailRegex.test(email);
    }
});
