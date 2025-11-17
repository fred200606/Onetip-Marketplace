document.addEventListener('DOMContentLoaded', function() {
    const usernameForm = document.getElementById('usernameForm');
    const usernameInput = document.getElementById('username');
    const usernameStatus = document.getElementById('usernameStatus');
    const completeSetupBtn = document.getElementById('completeSetupBtn');
    
    let debounceTimer;
    
    // Live validation
    usernameInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            validateUsername(this.value);
        }, 500);
    });
    
    usernameForm.addEventListener('submit', function(e) {
        const username = usernameInput.value.trim();
        if (!isValidUsername(username)) {
            e.preventDefault();
            usernameStatus.textContent = 'Invalid username format.';
            usernameStatus.className = 'status-message error';
            return false;
        }

        // ✅ Let PHP handle form submission
        completeSetupBtn.textContent = 'Saving...';
        completeSetupBtn.disabled = true;
    });
    
    function validateUsername(username) {
        const usernameRegex = /^[a-zA-Z0-9_]{3,20}$/;
        
        if (!username) {
            usernameStatus.textContent = '';
            usernameStatus.className = 'status-message';
            return;
        }
        
        if (!usernameRegex.test(username)) {
            usernameStatus.textContent = 'Username must be 3-20 characters (letters, numbers, underscores only)';
            usernameStatus.className = 'status-message error';
            return false;
        }
        
        usernameStatus.textContent = '✓ Username format valid';
        usernameStatus.className = 'status-message success';
        completeSetupBtn.textContent = `Complete setup with @${username}`;
        return true;
    }
    
    function isValidUsername(username) {
        const usernameRegex = /^[a-zA-Z0-9_]{3,20}$/;
        return usernameRegex.test(username);
    }
});
