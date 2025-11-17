// Mark notification as read
function markAsRead(notifId) {
    const formData = new FormData();
    formData.append('notif_id', notifId);
    
    fetch('mark_notification_read.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            // Update UI
            const notifElement = document.querySelector(`[data-notif-id="${notifId}"]`);
            if (notifElement) {
                notifElement.style.background = '#f8f9fa';
                const btn = notifElement.querySelector('.mark-read-btn-small');
                if (btn) btn.remove();
            }
            
            // Update notification badge
            const badge = document.getElementById('notificationBadge');
            if (badge && data.unread_count > 0) {
                badge.textContent = data.unread_count;
            } else if (badge) {
                badge.style.display = 'none';
            }
        }
    })
    .catch(err => console.error('Error:', err));
}
