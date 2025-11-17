/*
Standalone Marketplace functionality for ONE-TiP
Handles product loading, filtering, modal display, and interactions
*/

document.addEventListener('DOMContentLoaded', function () {
    let currentProducts = [];
    let filteredProducts = [];
    let currentFilter = {
        category: 'all',
        sort: 'newest',
        condition: 'all',
        priceRange: 'all'
    };

    // Initialize marketplace
    initializeMarketplace();

    function initializeMarketplace() {
        console.log('Initializing standalone marketplace...');
        loadUserProfile();
        setupEventListeners();
        loadProducts();
    }

    function setupEventListeners() {
        console.log('Setting up marketplace event listeners...');

        // Category selection
        const categoryItems = document.querySelectorAll('.category-item');
        categoryItems.forEach(item => {
            item.addEventListener('click', handleCategoryClick);
        });

        // Filter dropdowns
        const sortBy = document.getElementById('sortBy');
        const conditionFilter = document.getElementById('conditionFilter');
        const priceRange = document.getElementById('priceRange');

        if (sortBy) sortBy.addEventListener('change', handleFilterChange);
        if (conditionFilter) conditionFilter.addEventListener('change', handleFilterChange);
        if (priceRange) priceRange.addEventListener('change', handleFilterChange);

        // Load more button
        const loadMoreBtn = document.getElementById('loadMoreProducts');
        if (loadMoreBtn) {
            loadMoreBtn.addEventListener('click', handleLoadMore);
        }

        // User profile dropdown
        const userProfile = document.getElementById('userProfile');
        const userProfileDropdown = document.getElementById('userProfileDropdown');

        if (userProfile && userProfileDropdown) {
            userProfileDropdown.style.display = 'none';
            
            userProfile.addEventListener('click', function (e) {
                e.stopPropagation();
                const isVisible = userProfileDropdown.style.display === 'block';
                userProfileDropdown.style.display = isVisible ? 'none' : 'block';
            });
        }

        // Notification functionality
        const notificationIcon = document.getElementById('notificationIcon');
        const notificationPanel = document.getElementById('notificationPanel');

        if (notificationIcon && notificationPanel) {
            notificationIcon.addEventListener('click', function(e) {
                e.stopPropagation();
                toggleNotificationPanel();
            });
        }

        // Close dropdowns when clicking outside
        document.addEventListener('click', function (event) {
            if (userProfile && userProfileDropdown &&
                !userProfile.contains(event.target) &&
                !userProfileDropdown.contains(event.target)) {
                userProfileDropdown.style.display = 'none';
            }

            // Close notification panel when clicking outside
            if (notificationIcon && notificationPanel &&
                !notificationIcon.contains(event.target) &&
                !notificationPanel.contains(event.target)) {
                notificationPanel.style.display = 'none';
            }
        });

        // Profile menu actions
        document.querySelectorAll('.menu-item').forEach(item => {
            item.addEventListener('click', function (e) {
                e.preventDefault();
                const action = this.dataset.action;
                handleProfileAction(action);
            });
        });

        setupProductModal();

        // Create Post Button
        const createPostBtn = document.getElementById('createPostBtn');
        if (createPostBtn) {
            createPostBtn.addEventListener('click', () => {
                openCreatePostModal('marketplace');
            });
        }

        // Global search functionality
        const globalSearch = document.getElementById('globalSearch');
        const searchBtn = document.getElementById('searchBtn');

        if (globalSearch) {
            globalSearch.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    performGlobalSearch();
                }
            });
        }

        if (searchBtn) {
            searchBtn.addEventListener('click', performGlobalSearch);
        }

        console.log('Marketplace event listeners set up successfully');
    }

    function handleCategoryClick(event) {
        // Remove active class from all categories
        document.querySelectorAll('.category-item').forEach(item => {
            item.classList.remove('active');
        });

        // Add active class to clicked category
        event.currentTarget.classList.add('active');

        // Update filter and reload products
        currentFilter.category = event.currentTarget.dataset.category;
        filterAndDisplayProducts();
    }

    function handleFilterChange() {
        currentFilter.sort = document.getElementById('sortBy')?.value || 'newest';
        currentFilter.condition = document.getElementById('conditionFilter')?.value || 'all';
        currentFilter.priceRange = document.getElementById('priceRange')?.value || 'all';

        filterAndDisplayProducts();
    }

    function handleLoadMore() {
        alert('Load more functionality - would load additional products in a real implementation');
    }

    function loadProducts() {
        console.log('Loading marketplace products...');
        
        // Sample products data
        currentProducts = [
            {
                id: 1,
                title: 'MacBook Pro 13" 2022',
                price: 35000,
                condition: 'like_new',
                seller_name: 'John Doe',
                seller_department: 'CCS',
                campus: 'Arlegui',
                seller_vouches: 15,
                created_at: new Date(Date.now() - 9 * 24 * 60 * 60 * 1000),
                description: 'Lorem ipsum dolor sit amet consectetur. Enim donec mauris risus purus volutpat hac nec. Felis eleifend consectetur mattis tellus iaculis ornare sem elit. Proin ullamcorper facilisis tellus venenatis nisl. Elementum sed nisl donec venenatis ac mi sed. Non egestas morbi auctor elementum. Fames sit ut purus elementum in.',
                category: 'electronics',
                images: [],
                contact_info: {
                    chat_availability: '8:00 am - 9:00pm',
                    meetup_availability: 'Weekdays - Casal / Arlegui Campus',
                    facebook: 'john.doe.tip',
                    email: 'john.doe@tip.edu.ph'
                }
            },
            {
                id: 2,
                title: 'Calculus Textbook',
                price: 800,
                condition: 'good',
                seller_name: 'Maria Santos',
                seller_department: 'Engineering',
                campus: 'Casal',
                seller_vouches: 8,
                created_at: new Date(Date.now() - 5 * 24 * 60 * 60 * 1000),
                description: 'Well-maintained calculus textbook. Some highlighting but all pages intact.',
                category: 'books',
                images: [],
                contact_info: {
                    chat_availability: '9:00 am - 8:00 pm',
                    meetup_availability: 'Weekends - Casal / Arlegui Campus',
                    facebook: 'maria.santos.tip',
                    email: 'maria.santos@tip.edu.ph'
                }
            },
            {
                id: 3,
                title: 'Scientific Calculator',
                price: 450,
                condition: 'excellent',
                seller_name: 'Alex Chen',
                seller_department: 'CCS',
                campus: 'Arlegui',
                seller_vouches: 23,
                created_at: new Date(Date.now() - 2 * 24 * 60 * 60 * 1000),
                description: 'Excellent condition scientific calculator. Perfect for engineering students.',
                category: 'calculators',
                images: [],
                contact_info: {
                    chat_availability: '8:00 am - 10:00 pm',
                    meetup_availability: 'Anytime - Casal / Arlegui Campus',
                    facebook: 'alex.chen.tip',
                    email: 'alex.chen@tip.edu.ph'
                }
            },
            {
                id: 4,
                title: 'Art Supply Set',
                price: 1200,
                condition: 'new',
                seller_name: 'Lisa Park',
                seller_department: 'Arts',
                campus: 'Casal',
                seller_vouches: 12,
                created_at: new Date(Date.now() - 1 * 24 * 60 * 60 * 1000),
                description: 'Brand new art supply set with pencils, brushes, paints, and canvas.',
                category: 'art',
                images: [],
                contact_info: {
                    chat_availability: '10:00 am - 6:00 pm',
                    meetup_availability: 'Weekdays - Casal / Arlegui Campus',
                    facebook: 'lisa.park.tip',
                    email: 'lisa.park@tip.edu.ph'
                }
            },
            {
                id: 5,
                title: 'Acoustic Guitar',
                price: 3500,
                condition: 'good',
                seller_name: 'Mike Rodriguez',
                seller_department: 'Business',
                campus: 'Arlegui',
                seller_vouches: 6,
                created_at: new Date(Date.now() - 7 * 24 * 60 * 60 * 1000),
                description: 'Good condition acoustic guitar. Great for beginners and intermediate players.',
                category: 'musical',
                images: [],
                contact_info: {
                    chat_availability: '7:00 am - 11:00 pm',
                    meetup_availability: 'Flexible schedule - Casal / Arlegui Campus',
                    facebook: 'mike.rodriguez.tip',
                    email: 'mike.rodriguez@tip.edu.ph'
                }
            },
            {
                id: 6,
                title: 'Basketball',
                price: 150,
                condition: 'excellent',
                seller_name: 'David Kim',
                seller_department: 'Engineering',
                campus: 'Casal',
                seller_vouches: 19,
                created_at: new Date(Date.now() - 3 * 24 * 60 * 60 * 1000),
                description: 'Official size basketball in excellent condition. Perfect for games.',
                category: 'sports',
                images: [],
                contact_info: {
                    chat_availability: '6:00 am - 9:00 pm',
                    meetup_availability: 'Daily - Casal Campus (Sports Complex)',
                    facebook: 'david.kim.tip',
                    email: 'david.kim@tip.edu.ph'
                }
            },
            {
                id: 7,
                title: 'Programming Book',
                price: 600,
                condition: 'good',
                seller_name: 'Sarah Wilson',
                seller_department: 'CCS',
                campus: 'Arlegui',
                seller_vouches: 11,
                created_at: new Date(Date.now() - 4 * 24 * 60 * 60 * 1000),
                description: 'Clean Code textbook in good condition.',
                category: 'books',
                images: [],
                contact_info: {
                    chat_availability: '9:00 am - 7:00 pm',
                    meetup_availability: 'Weekdays - Casal Campus',
                    facebook: 'sarah.wilson.tip',
                    email: 'sarah.wilson@tip.edu.ph'
                }
            },
            {
                id: 8,
                title: 'Laptop Stand',
                price: 800,
                condition: 'like_new',
                seller_name: 'Tom Brown',
                seller_department: 'Engineering',
                campus: 'Arlegui',
                seller_vouches: 7,
                created_at: new Date(Date.now() - 6 * 24 * 60 * 60 * 1000),
                description: 'Adjustable laptop stand, barely used.',
                category: 'electronics',
                images: [],
                contact_info: {
                    chat_availability: '8:00 am - 8:00 pm',
                    meetup_availability: 'Weekdays - Arlegui Campus',
                    facebook: 'tom.brown.tip',
                    email: 'tom.brown@tip.edu.ph'
                }
            },
            {
                id: 9,
                title: 'Food Container Set',
                price: 250,
                condition: 'new',
                seller_name: 'Emma Davis',
                seller_department: 'Business',
                campus: 'Casal',
                seller_vouches: 5,
                created_at: new Date(Date.now() - 1 * 24 * 60 * 60 * 1000),
                description: 'Brand new food container set, perfect for students.',
                category: 'food',
                images: [],
                contact_info: {
                    chat_availability: '10:00 am - 6:00 pm',
                    meetup_availability: 'Weekdays - Casal Campus',
                    facebook: 'emma.davis.tip',
                    email: 'emma.davis@tip.edu.ph'
                }
            }
        ];

        filterAndDisplayProducts();
    }

    function filterAndDisplayProducts() {
        // Apply filters
        filteredProducts = currentProducts.filter(product => {
            // Category filter
            if (currentFilter.category !== 'all' && product.category !== currentFilter.category) {
                return false;
            }

            // Condition filter
            if (currentFilter.condition !== 'all' && product.condition !== currentFilter.condition) {
                return false;
            }

            // Price range filter
            if (currentFilter.priceRange !== 'all') {
                if (!matchesPriceRange(product.price, currentFilter.priceRange)) {
                    return false;
                }
            }

            return true;
        });

        // Apply sorting
        filteredProducts.sort((a, b) => {
            switch (currentFilter.sort) {
                case 'price_low':
                    return a.price - b.price;
                case 'price_high':
                    return b.price - a.price;
                case 'oldest':
                    return new Date(a.created_at) - new Date(b.created_at);
                case 'newest':
                default:
                    return new Date(b.created_at) - new Date(a.created_at);
            }
        });

        displayProducts(filteredProducts);
    }

    function matchesPriceRange(price, range) {
        switch (range) {
            case '0-100':
                return price >= 0 && price <= 100;
            case '100-500':
                return price >= 100 && price <= 500;
            case '500-1000':
                return price >= 500 && price <= 1000;
            case '1000-5000':
                return price >= 1000 && price <= 5000;
            case '5000+':
                return price >= 5000;
            default:
                return true;
        }
    }

    function displayProducts(products) {
        const productsGrid = document.getElementById('productsGrid');
        if (!productsGrid) {
            console.error('Products grid not found');
            return;
        }

        console.log('Displaying products:', products.length);

        if (products.length === 0) {
            productsGrid.innerHTML = `
                <div class="no-products">
                    <p>No products found matching your criteria.</p>
                    <button class="btn-secondary" onclick="clearFilters()">Clear Filters</button>
                </div>
            `;
            return;
        }

        productsGrid.innerHTML = products.map(product => `
            <div class="product-card" data-product-id="${product.id}" onclick="openProductModal(${product.id})">
                <div class="product-image">
                    ${product.images && product.images.length > 0
                ? `<img src="${product.images[0]}" alt="${product.title}">`
                : ''
            }
                </div>
                <div class="product-info">
                    <div class="product-card-title">${product.title}</div>
                    <div class="product-card-price">₱${product.price.toLocaleString()}</div>
                    <div class="product-card-seller">Seller: ${product.seller_name}</div>
                    <div class="product-card-condition">Campus: ${product.campus || 'Arlegui'}</div>
                </div>
            </div>
        `).join('');
    }

    function setupProductModal() {
        const modal = document.getElementById('productModal');
        const closeBtn = document.getElementById('closeProductModal');

        if (closeBtn) {
            closeBtn.addEventListener('click', closeProductModal);
        }

        if (modal) {
            modal.addEventListener('click', function (e) {
                if (e.target === modal) {
                    closeProductModal();
                }
            });
        }

        // Close modal with Escape key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && modal && modal.style.display === 'block') {
                closeProductModal();
            }
        });
    }

    function openProductModal(productId) {
        const product = currentProducts.find(p => p.id === productId);
        if (!product) {
            console.error('Product not found:', productId);
            return;
        }

        populateProductModal(product);
        const modal = document.getElementById('productModal');
        if (modal) {
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
    }

    function populateProductModal(product) {
        // Update product details
        document.getElementById('productTitle').textContent = product.title;
        document.getElementById('productPrice').textContent = `₱${product.price.toLocaleString()}`;
        document.getElementById('priceType').textContent = 'Negotiable - Cash on meetup';
        document.getElementById('productDescription').textContent = product.description;
        document.getElementById('productDate').textContent = `Listed ${formatTimeAgo(product.created_at)}`;

        // Update seller information
        document.getElementById('sellerName').textContent = product.seller_name;
        document.getElementById('sellerDepartment').textContent = `${product.seller_department} Department`;
        document.getElementById('ratingCount').textContent = `(${product.seller_vouches} vouches)`;

        // Update contact availability
        if (product.contact_info) {
            document.getElementById('chatAvailability').textContent = product.contact_info.chat_availability || '8:00 am - 9:00pm';
            document.getElementById('meetupAvailability').textContent = product.contact_info.meetup_availability || 'Weekdays - Casal / Arlegui Campus';
        }

        // Update main image
        const mainImage = document.getElementById('productMainImage');
        if (product.images && product.images.length > 0) {
            mainImage.innerHTML = `<img src="${product.images[0]}" alt="${product.title}" class="main-product-img">`;
        } else {
            mainImage.innerHTML = '<div style="font-size: 4rem; color: #999; z-index: 3; position: relative;">📦</div>';
        }

        // Setup contact buttons
        setupContactButtons(product);
    }

    function setupContactButtons(product) {
        const facebookBtn = document.getElementById('contactFacebook');
        const emailBtn = document.getElementById('contactEmail');

        if (facebookBtn) {
            facebookBtn.onclick = (e) => {
                e.preventDefault();
                if (product.contact_info?.facebook) {
                    window.open(`https://facebook.com/${product.contact_info.facebook}`, '_blank');
                } else {
                    alert('Facebook contact not available');
                }
            };
        }

        if (emailBtn) {
            emailBtn.onclick = (e) => {
                e.preventDefault();
                const email = product.contact_info?.email || `${product.seller_name.toLowerCase().replace(' ', '.')}@tip.edu.ph`;
                const subject = `Interested in: ${product.title}`;
                const body = `Hi ${product.seller_name},\n\nI'm interested in your ${product.title} listed for ₱${product.price.toLocaleString()}.\n\nCan we discuss the details?\n\nThanks!`;
                window.location.href = `mailto:${email}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
            };
        }
    }

    function closeProductModal() {
        const modal = document.getElementById('productModal');
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }
    }

    function formatTimeAgo(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffInSeconds = Math.floor((now - date) / 1000);

        if (diffInSeconds < 60) return 'Just now';
        if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)} minutes ago`;
        if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)} hours ago`;
        if (diffInSeconds < 604800) return `${Math.floor(diffInSeconds / 86400)} days ago`;

        return date.toLocaleDateString();
    }

    function clearFilters() {
        // Reset filters
        currentFilter = {
            category: 'all',
            sort: 'newest',
            condition: 'all',
            priceRange: 'all'
        };

        // Reset UI elements
        document.getElementById('sortBy').value = 'newest';
        document.getElementById('conditionFilter').value = 'all';
        document.getElementById('priceRange').value = 'all';

        document.querySelectorAll('.category-item').forEach(item => item.classList.remove('active'));
        document.querySelector('.category-item[data-category="all"]').classList.add('active');

        filterAndDisplayProducts();
    }

    function loadUserProfile() {
        // Get stored user data from login
        const username = sessionStorage.getItem('username') || 'user';
        const email = sessionStorage.getItem('email') || 'user@tip.edu.ph';

        // Update UI with user data
        document.getElementById('displayUsername').textContent = '@' + username;
        document.getElementById('profileName').textContent = capitalizeWords(username.replace(/[._]/g, ' '));
        document.getElementById('profileEmail').textContent = email;
    }

    function handleProfileAction(action) {
        switch (action) {
            case 'edit-profile':
                window.location.href = 'edit-profile.html';
                break;
            case 'settings':
                console.log('Opening settings...');
                break;
            case 'help':
                console.log('Opening help...');
                break;
            case 'logout':
                if (confirm('Are you sure you want to logout?')) {
                    sessionStorage.clear();
                    localStorage.clear();
                    window.location.href = 'index.html';
                }
                break;
        }

        // Close dropdown
        const userProfileDropdown = document.getElementById('userProfileDropdown');
        if (userProfileDropdown) {
            userProfileDropdown.style.display = 'none';
        }
    }

    function toggleNotificationPanel() {
        const notificationPanel = document.getElementById('notificationPanel');
        const userProfileDropdown = document.getElementById('userProfileDropdown');
        
        if (!notificationPanel) return;

        const isVisible = notificationPanel.style.display === 'block';

        // Close profile dropdown if open
        if (userProfileDropdown) {
            userProfileDropdown.style.display = 'none';
        }

        notificationPanel.style.display = isVisible ? 'none' : 'block';

        // Load notifications when opening panel
        if (!isVisible && typeof window.notificationManager !== 'undefined') {
            window.notificationManager.loadNotifications();
        }
    }

    function capitalizeWords(str) {
        return str.split(' ').map(word =>
            word.charAt(0).toUpperCase() + word.slice(1).toLowerCase()
        ).join(' ');
    }

    // Export functions to global scope
    window.openProductModal = openProductModal;
    window.clearFilters = clearFilters;

    // Global function for opening create post modal
    window.openCreatePostModal = function (type = 'marketplace', editId = null) {
        const modal = document.getElementById('createPostModal');
        if (modal) {
            modal.style.display = 'block';
            if (typeof loadCreatePostForm === 'function') {
                loadCreatePostForm(type, editId);
            }
        }
    };

    function performGlobalSearch() {
        const query = globalSearch.value.trim();
        if (query.length < 2) {
            alert('Please enter at least 2 characters to search');
            return;
        }

        window.location.href = `search-results.html?q=${encodeURIComponent(query)}`;
    }

    console.log('Standalone marketplace loaded successfully');
});
