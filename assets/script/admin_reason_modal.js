let currentAction = '';
let currentUserId = 0;
let currentUsername = '';
let currentUserEmail = '';
let currentPage = 1;

function showReasonModal(action, userId, username, page) {
    currentAction = action;
    currentUserId = userId;
    currentUsername = username;
    currentPage = page;
    
    // Get user email from the table row
    const userRow = document.querySelector(`button[onclick*="showReasonModal('${action}', ${userId}"]`).closest('tr');
    if (userRow) {
        currentUserEmail = userRow.cells[2].textContent.trim();
    }
    
    // Update modal title
    const actionText = action.charAt(0).toUpperCase() + action.slice(1);
    document.getElementById('reasonModalTitle').textContent = `${actionText} User - Provide Reason`;
    
    // Update modal text
    let durationText = action === 'suspend' ? ' (7 days)' : '';
    document.getElementById('reasonModalText').textContent = 
        `Please provide a reason for ${action}ing this user${durationText}:`;
    
    // Update user info
    document.getElementById('modalUsername').textContent = username;
    document.getElementById('modalUserEmail').textContent = currentUserEmail;
    
    // Clear previous input
    document.getElementById('reasonInput').value = '';
    
    // Show modal
    const modal = document.getElementById('reasonModal');
    if (modal) {
        modal.style.display = 'flex';
    }
}

function closeReasonModal() {
    const modal = document.getElementById('reasonModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

function submitReason() {
    const reason = document.getElementById('reasonInput').value.trim();
    if (!reason) {
        alert('Please enter a reason');
        return;
    }
    
    const url = `?useraction=${currentAction}&userid=${currentUserId}&page=${currentPage}&reason=${encodeURIComponent(reason)}`;
    window.location.href = url;
}

// Close modal on outside click
window.addEventListener('click', function(event) {
    const modal = document.getElementById('reasonModal');
    if (modal && event.target === modal) {
        closeReasonModal();
    }
});

// Close modal on ESC key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const modal = document.getElementById('reasonModal');
        if (modal && modal.style.display === 'flex') {
            closeReasonModal();
        }
    }
});
