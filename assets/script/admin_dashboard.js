    /*
    Admin Dashboard JavaScript
    Handles navigation tab switching and admin profile dropdown only
    */

    // Initialize basic admin dashboard UI
    initializeAdminDashboard();

    // Track current section
    let currentSection = 'overview';

    function initializeAdminDashboard() {
        setupEventListeners();
    }

    function setupEventListeners() {
        // Navigation tabs
        document.querySelectorAll('.nav-tab').forEach(tab => {
            tab.addEventListener('click', handleTabClick);
        });

        // Profile dropdown
        setupProfileDropdown();
    }

    function handleTabClick(event) {
        const targetSection = event.target.dataset.section;

        // Remove active class from all tabs and sections
        document.querySelectorAll('.nav-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.section-content').forEach(s => s.classList.remove('active'));

        // Add active class to clicked tab and corresponding section
        event.target.classList.add('active');
        document.getElementById(targetSection).classList.add('active');

        currentSection = targetSection;
    }
function setupProfileDropdown() {
        const userProfile = document.getElementById('userProfile');
        const userProfileDropdown = document.getElementById('userProfileDropdown');

        if (userProfile && userProfileDropdown) {
            userProfile.addEventListener('click', function() {
                const isVisible = userProfileDropdown.style.display === 'block';
                userProfileDropdown.style.display = isVisible ? 'none' : 'block';
            });

            document.querySelectorAll('.menu-item').forEach(item => {
                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    const action = this.dataset.action;
                    handleProfileAction(action);
                });
            });
        }
    }

    function handleProfileAction(action) {
        switch (action) {
            case 'admin-settings':
                currentSection = 'settings';
                const settingsTab = document.querySelector('[data-section="settings"]');
                if (settingsTab) {
                    settingsTab.click();
                }
                break;
            case 'system-logs':
                console.log('Opening system logs...');
                break;
            case 'backup':
                console.log('Starting data backup...');
                break;
            case 'logout':
                if (confirm('Are you sure you want to logout?')) {
                    sessionStorage.clear();
                    localStorage.clear();
                    window.location.href = 'loginreg/login.php';
                }
                break;
            default:
                console.log('Admin action:', action);
        }

        const userProfileDropdown = document.getElementById('userProfileDropdown');
        if (userProfileDropdown) {
            userProfileDropdown.style.display = 'none';
        }
    }


