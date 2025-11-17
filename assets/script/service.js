/*
Services functionality for ONE-TiP
Handles service loading, filtering, modal display, and interactions
*/

document.addEventListener('DOMContentLoaded', function () {
    let currentServices = [];
    let filteredServices = [];
    let currentFilter = {
        category: 'all',
        sort: 'newest',
        priceRange: 'all',
        deliveryTime: 'all'
    };

    // Initialize services
    initializeServices();

    function initializeServices() {
        console.log('Initializing services page...');
        loadUserProfile();
        setupEventListeners();
        loadServices();
    }

    function setupEventListeners() {
        console.log('Setting up services event listeners...');

        // Filter dropdowns
        const sortBy = document.getElementById('servicesSortBy');
        const category = document.getElementById('servicesCategory');
        const priceRange = document.getElementById('servicesPriceRange');
        const deliveryTime = document.getElementById('servicesDeliveryTime');

        if (sortBy) sortBy.addEventListener('change', handleFilterChange);
        if (category) category.addEventListener('change', handleFilterChange);
        if (priceRange) priceRange.addEventListener('change', handleFilterChange);
        if (deliveryTime) deliveryTime.addEventListener('change', handleFilterChange);

        // Load more button
        const loadMoreBtn = document.getElementById('loadMoreServices');
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

        // Close dropdown when clicking outside
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

        setupServiceModal();

        // Create Post Button
        const createPostBtn = document.getElementById('createPostBtn');
        if (createPostBtn) {
            createPostBtn.addEventListener('click', () => {
                openCreatePostModal('service');
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

        console.log('Services event listeners set up successfully');
    }

    function handleFilterChange() {
        currentFilter.sort = document.getElementById('servicesSortBy')?.value || 'newest';
        currentFilter.category = document.getElementById('servicesCategory')?.value || 'all';
        currentFilter.priceRange = document.getElementById('servicesPriceRange')?.value || 'all';
        currentFilter.deliveryTime = document.getElementById('servicesDeliveryTime')?.value || 'all';

        filterAndDisplayServices();
    }

    function handleLoadMore() {
        alert('Load more functionality - would load additional services in a real implementation');
    }

    function loadServices() {
        console.log('Loading services...');
        
        // Sample services data with portfolio links
        currentServices = [
            {
                id: 1,
                title: 'Math Tutoring Service',
                price: 100,
                provider_name: 'Maria Santos',
                provider_department: 'Engineering',
                provider_rating: 4.9,
                provider_reviews: 25,
                created_at: new Date(Date.now() - 3 * 24 * 60 * 60 * 1000),
                description: 'Professional mathematics tutoring for all levels. Specializing in Calculus, Algebra, and Statistics. 3+ years experience helping students achieve their academic goals.',
                category: 'tutoring',
                delivery_time: '1_week',
                portfolio: [
                    { 
                        title: 'Calculus Study Guide', 
                        type: 'document',
                        link: 'https://drive.google.com/file/d/1ABC123/view'
                    },
                    { 
                        title: 'Statistics Tutorial Videos', 
                        type: 'video',
                        link: 'https://drive.google.com/drive/folders/1XYZ789'
                    },
                    { 
                        title: 'Student Success Stories', 
                        type: 'document',
                        link: 'https://docs.google.com/document/d/1DEF456/edit'
                    }
                ],
                contact_info: {
                    chat_availability: '9:00 am - 8:00 pm',
                    meetup_availability: 'Weekdays - Casal / Arlegui Campus',
                    facebook: 'maria.santos.tip',
                    email: 'maria.santos@tip.edu.ph'
                }
            },
            {
                id: 2,
                title: 'Logo Design & Branding',
                price: 200,
                provider_name: 'Alex Chen',
                provider_department: 'Arts',
                provider_rating: 4.8,
                provider_reviews: 18,
                created_at: new Date(Date.now() - 1 * 24 * 60 * 60 * 1000),
                description: 'Professional logo design and brand identity creation. Modern, clean designs that represent your business perfectly. Fast turnaround and unlimited revisions.',
                category: 'design',
                delivery_time: '3_days',
                portfolio: [
                    { 
                        title: 'Restaurant Logo Portfolio', 
                        type: 'image',
                        link: 'https://drive.google.com/drive/folders/1LOGO123'
                    },
                    { 
                        title: 'Tech Startup Branding', 
                        type: 'image',
                        link: 'https://behance.net/gallery/12345678/Tech-Startup-Brand'
                    },
                    { 
                        title: 'Fashion Brand Identity', 
                        type: 'image',
                        link: 'https://drive.google.com/drive/folders/1FASHION456'
                    },
                    { 
                        title: 'Process & Sketches', 
                        type: 'document',
                        link: 'https://docs.google.com/presentation/d/1PROCESS789/edit'
                    }
                ],
                contact_info: {
                    chat_availability: '10:00 am - 10:00 pm',
                    meetup_availability: 'Flexible - Casal / Arlegui Campus',
                    facebook: 'alex.chen.design',
                    email: 'alex.chen@tip.edu.ph'
                }
            },
            {
                id: 3,
                title: 'Programming & Web Development',
                price: 150,
                provider_name: 'John Reyes',
                provider_department: 'Computer Science',
                provider_rating: 4.7,
                provider_reviews: 32,
                created_at: new Date(Date.now() - 5 * 24 * 60 * 60 * 1000),
                description: 'Full-stack web development and programming tutoring. Python, JavaScript, PHP, React, and more. Build your first website or debug your code with expert help.',
                category: 'programming',
                delivery_time: '1_week',
                portfolio: [
                    { title: 'E-commerce Website', type: 'website' },
                    { title: 'Mobile App Backend', type: 'document' },
                    { title: 'Portfolio Website', type: 'website' }
                ],
                contact_info: {
                    chat_availability: '7:00 am - 11:00 pm',
                    meetup_availability: 'Anytime - Casal Campus',
                    facebook: 'john.reyes.dev',
                    email: 'john.reyes@tip.edu.ph'
                }
            },
            {
                id: 4,
                title: 'Photography & Photo Editing',
                price: 80,
                provider_name: 'Sarah Kim',
                provider_department: 'Arts',
                provider_rating: 4.9,
                provider_reviews: 28,
                created_at: new Date(Date.now() - 2 * 24 * 60 * 60 * 1000),
                description: 'Professional photography for events, portraits, and products. Also offering photo editing and retouching services using Photoshop and Lightroom.',
                category: 'photography',
                delivery_time: '2_days',
                portfolio: [
                    { title: 'Event Photography', type: 'image' },
                    { title: 'Portrait Session', type: 'image' },
                    { title: 'Product Photography', type: 'image' }
                ],
                contact_info: {
                    chat_availability: '8:00 am - 6:00 pm',
                    meetup_availability: 'Weekdays - Arlegui Campus',
                    facebook: 'sarah.kim.photo',
                    email: 'sarah.kim@tip.edu.ph'
                }
            },
            {
                id: 5,
                title: 'Music Lessons & Audio Production',
                price: 120,
                provider_name: 'Mike Torres',
                provider_department: 'Arts',
                provider_rating: 4.6,
                provider_reviews: 15,
                created_at: new Date(Date.now() - 7 * 24 * 60 * 60 * 1000),
                description: 'Guitar, piano, and music theory lessons. Also offering audio production services including mixing, mastering, and podcast editing.',
                category: 'music',
                delivery_time: '1_week',
                portfolio: [
                    { title: 'Original Song Production', type: 'audio' },
                    { title: 'Podcast Editing', type: 'audio' },
                    { title: 'Live Performance', type: 'video' }
                ],
                contact_info: {
                    chat_availability: '2:00 pm - 9:00 pm',
                    meetup_availability: 'Weekends - Music Studio',
                    facebook: 'mike.torres.music',
                    email: 'mike.torres@tip.edu.ph'
                }
            },
            {
                id: 6,
                title: 'Business Consulting & Marketing',
                price: 180,
                provider_name: 'Lisa Rodriguez',
                provider_department: 'Business',
                provider_rating: 4.8,
                provider_reviews: 22,
                created_at: new Date(Date.now() - 4 * 24 * 60 * 60 * 1000),
                description: 'Business plan development, market research, and digital marketing strategies. Help your startup grow with proven business techniques.',
                category: 'business',
                delivery_time: '2_weeks',
                portfolio: [
                    { title: 'Startup Business Plan', type: 'document' },
                    { title: 'Social Media Strategy', type: 'document' },
                    { title: 'Market Analysis Report', type: 'document' }
                ],
                contact_info: {
                    chat_availability: '9:00 am - 5:00 pm',
                    meetup_availability: 'Business Hours - Casal Campus',
                    facebook: 'lisa.rodriguez.biz',
                    email: 'lisa.rodriguez@tip.edu.ph'
                }
            }
        ];

        filterAndDisplayServices();
    }

    function filterAndDisplayServices() {
        // Apply filters
        filteredServices = currentServices.filter(service => {
            if (currentFilter.category !== 'all' && service.category !== currentFilter.category) {
                return false;
            }
            if (currentFilter.priceRange !== 'all') {
                if (!matchesPriceRange(service.price, currentFilter.priceRange)) {
                    return false;
                }
            }
            if (currentFilter.deliveryTime !== 'all' && service.delivery_time !== currentFilter.deliveryTime) {
                return false;
            }
            return true;
        });

        // Apply sorting
        filteredServices.sort((a, b) => {
            switch (currentFilter.sort) {
                case 'price_low':
                    return a.price - b.price;
                case 'price_high':
                    return b.price - a.price;
                case 'rating':
                    return b.provider_rating - a.provider_rating;
                case 'popular':
                    return b.provider_reviews - a.provider_reviews;
                case 'oldest':
                    return new Date(a.created_at) - new Date(b.created_at);
                case 'newest':
                default:
                    return new Date(b.created_at) - new Date(a.created_at);
            }
        });

        displayServices(filteredServices);
    }

    function matchesPriceRange(price, range) {
        switch (range) {
            case '0-50':
                return price >= 0 && price <= 50;
            case '50-100':
                return price >= 50 && price <= 100;
            case '100-200':
                return price >= 100 && price <= 200;
            case '200-500':
                return price >= 200 && price <= 500;
            case '500+':
                return price >= 500;
            default:
                return true;
        }
    }

    function displayServices(services) {
        const servicesGrid = document.getElementById('servicesGrid');
        if (!servicesGrid) {
            console.error('Services grid not found');
            return;
        }

        if (services.length === 0) {
            servicesGrid.innerHTML = `
                <div class="no-services">
                    <p>No services found matching your criteria.</p>
                    <button class="btn-secondary" onclick="clearServiceFilters()">Clear Filters</button>
                </div>
            `;
            return;
        }

        servicesGrid.innerHTML = services.map(service => `
            <div class="service-card" data-service-id="${service.id}" onclick="openServiceModal(${service.id})">
                <div class="service-card-content">
                    <div class="service-card-header">
                        <h3 class="service-card-title">${service.title}</h3>
                        <p class="service-card-provider">by ${service.provider_name}</p>
                    </div>
                    <div class="service-card-description">
                        <p>${service.description.substring(0, 120)}...</p>
                    </div>
                    <div class="service-card-meta">
                        <div class="service-card-price">Starting at ₱${service.price}/hr</div>
                        <div class="service-card-rating">
                            <span class="stars">★</span>
                            <span class="rating">${service.provider_rating}</span>
                            <span class="reviews">(${service.provider_reviews})</span>
                        </div>
                    </div>
                    <div class="service-card-footer">
                        <div class="service-card-tags">
                            <span class="service-tag">${getCategoryDisplayName(service.category)}</span>
                            <span class="service-tag">${getDeliveryDisplayName(service.delivery_time)}</span>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');
    }

    function getCategoryDisplayName(category) {
        const categories = {
            'tutoring': 'Tutoring',
            'design': 'Design',
            'writing': 'Writing',
            'programming': 'Programming',
            'photography': 'Photography',
            'music': 'Music',
            'business': 'Business',
            'lifestyle': 'Lifestyle',
            'crafts': 'Crafts',
            'other': 'Other'
        };
        return categories[category] || category;
    }

    function getDeliveryDisplayName(deliveryTime) {
        const deliveryTimes = {
            '1_day': '1 Day',
            '2_days': '2 Days',
            '3_days': '3 Days',
            '1_week': '1 Week',
            '2_weeks': '2 Weeks',
            '1_month': '1 Month',
            'custom': 'Custom'
        };
        return deliveryTimes[deliveryTime] || deliveryTime;
    }

    function setupServiceModal() {
        const modal = document.getElementById('serviceModal');
        const closeBtn = document.getElementById('closeServiceModal');

        if (closeBtn) {
            closeBtn.addEventListener('click', closeServiceModal);
        }

        if (modal) {
            modal.addEventListener('click', function (e) {
                if (e.target === modal) {
                    closeServiceModal();
                }
            });
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && modal && modal.style.display === 'block') {
                closeServiceModal();
            }
        });
    }

    function openServiceModal(serviceId) {
        const service = currentServices.find(s => s.id === serviceId);
        if (!service) {
            console.error('Service not found:', serviceId);
            return;
        }

        populateServiceModal(service);
        const modal = document.getElementById('serviceModal');
        if (modal) {
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
    }

    function populateServiceModal(service) {
        // Safely update modal elements
        const serviceTitle = document.getElementById('serviceTitle');
        const servicePrice = document.getElementById('servicePrice');
        const serviceDeliveryTime = document.getElementById('serviceDeliveryTime');
        const servicePriceType = document.getElementById('servicePriceType');
        const serviceDescription = document.getElementById('serviceDescription');
        const serviceDate = document.getElementById('serviceDate');

        if (serviceTitle) serviceTitle.textContent = service.title;
        if (servicePrice) servicePrice.textContent = `Starting at ₱${service.price}/hr`;
        if (serviceDeliveryTime) serviceDeliveryTime.textContent = `Delivery: ${getDeliveryDisplayName(service.delivery_time)}`;
        if (servicePriceType) servicePriceType.textContent = 'Negotiable - Cash on meetup';
        if (serviceDescription) serviceDescription.textContent = service.description;
        if (serviceDate) serviceDate.textContent = `Listed ${formatTimeAgo(service.created_at)}`;

        // Provider information
        const serviceProviderName = document.getElementById('serviceProviderName');
        const serviceProviderDepartment = document.getElementById('serviceProviderDepartment');
        const serviceProviderRating = document.getElementById('serviceProviderRating');
        const serviceRatingCount = document.getElementById('serviceRatingCount');

        if (serviceProviderName) serviceProviderName.textContent = service.provider_name;
        if (serviceProviderDepartment) serviceProviderDepartment.textContent = `${service.provider_department} Department`;
        if (serviceProviderRating) serviceProviderRating.textContent = '★'.repeat(Math.floor(service.provider_rating)) + '☆'.repeat(5 - Math.floor(service.provider_rating));
        if (serviceRatingCount) serviceRatingCount.textContent = `(${service.provider_reviews} reviews)`;

        // Contact availability
        if (service.contact_info) {
            const serviceChatAvailability = document.getElementById('serviceChatAvailability');
            const serviceMeetupAvailability = document.getElementById('serviceMeetupAvailability');
            
            if (serviceChatAvailability) serviceChatAvailability.textContent = service.contact_info.chat_availability || '8:00 am - 9:00pm';
            if (serviceMeetupAvailability) serviceMeetupAvailability.textContent = service.contact_info.meetup_availability || 'Weekdays - Casal / Arlegui Campus';
        }

        // Portfolio gallery with links
        const portfolioGallery = document.getElementById('portfolioGallery');
        if (portfolioGallery) {
            if (service.portfolio && service.portfolio.length > 0) {
                portfolioGallery.innerHTML = service.portfolio.map(item => `
                    <div class="portfolio-item ${item.link ? 'clickable' : ''}" 
                         ${item.link ? `onclick="openPortfolioLink('${item.link}')"` : ''}>
                        <div class="portfolio-image">
                            ${getPortfolioIcon(item.type)}
                        </div>
                        <div class="portfolio-title">${item.title}</div>
                        ${item.link ? `
                            <a href="${item.link}" target="_blank" class="portfolio-link" onclick="event.stopPropagation()">
                                <i class="fas fa-external-link-alt"></i>
                                View ${getPortfolioLinkText(item.link)}
                            </a>
                        ` : ''}
                    </div>
                `).join('');
            } else {
                portfolioGallery.innerHTML = `
                    <div class="portfolio-item">
                        <div class="portfolio-image">📋</div>
                        <div class="portfolio-title">Sample Work Available</div>
                        <div style="font-size: 0.8rem; color: #666; margin-top: 0.5rem;">
                            Contact for examples
                        </div>
                    </div>
                `;
            }
        }

        setupServiceContactButtons(service);
    }

    function getPortfolioIcon(type) {
        const icons = {
            'image': '🎨',
            'document': '📄',
            'website': '💻',
            'audio': '🎵',
            'video': '🎬'
        };
        return icons[type] || '📁';
    }

    function getPortfolioLinkText(link) {
        if (link.includes('drive.google.com')) return 'on Google Drive';
        if (link.includes('docs.google.com')) return 'Google Doc';
        if (link.includes('behance.net')) return 'on Behance';
        if (link.includes('github.com')) return 'on GitHub';
        if (link.includes('youtube.com') || link.includes('youtu.be')) return 'on YouTube';
        return 'External Link';
    }

    function openPortfolioLink(link) {
        if (link && link.startsWith('http')) {
            window.open(link, '_blank', 'noopener,noreferrer');
        }
    }

    function setupServiceContactButtons(service) {
        const facebookBtn = document.getElementById('serviceContactFacebook');
        const emailBtn = document.getElementById('serviceContactEmail');

        if (facebookBtn) {
            facebookBtn.onclick = (e) => {
                e.preventDefault();
                if (service.contact_info && service.contact_info.facebook) {
                    window.open(`https://facebook.com/${service.contact_info.facebook}`, '_blank');
                } else {
                    alert('Facebook contact not available');
                }
            };
        }

        if (emailBtn) {
            emailBtn.onclick = (e) => {
                e.preventDefault();
                const email = (service.contact_info && service.contact_info.email) 
                    ? service.contact_info.email 
                    : `${service.provider_name.toLowerCase().replace(' ', '.')}@tip.edu.ph`;
                const subject = `Interested in: ${service.title}`;
                const body = `Hi ${service.provider_name},\n\nI'm interested in your ${service.title} service starting at ₱${service.price}/hr.\n\nCan we discuss the details?\n\nThanks!`;
                window.location.href = `mailto:${email}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
            };
        }
    }

    function closeServiceModal() {
        const modal = document.getElementById('serviceModal');
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }
    }

    function clearServiceFilters() {
        currentFilter = {
            category: 'all',
            sort: 'newest',
            priceRange: 'all',
            deliveryTime: 'all'
        };

        const sortBy = document.getElementById('servicesSortBy');
        const category = document.getElementById('servicesCategory');
        const priceRange = document.getElementById('servicesPriceRange');
        const deliveryTime = document.getElementById('servicesDeliveryTime');

        if (sortBy) sortBy.value = 'newest';
        if (category) category.value = 'all';
        if (priceRange) priceRange.value = 'all';
        if (deliveryTime) deliveryTime.value = 'all';

        filterAndDisplayServices();
    }

    function loadUserProfile() {
        // Get stored user data from login
        const username = sessionStorage.getItem('username') || 'user';
        const email = sessionStorage.getItem('email') || 'user@tip.edu.ph';

        // Update UI with user data
        const displayUsername = document.getElementById('displayUsername');
        const profileName = document.getElementById('profileName');
        const profileEmail = document.getElementById('profileEmail');

        if (displayUsername) displayUsername.textContent = '@' + username;
        if (profileName) profileName.textContent = capitalizeWords(username.replace(/[._]/g, ' '));
        if (profileEmail) profileEmail.textContent = email;
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

    function capitalizeWords(str) {
        if (!str) return '';
        return str.split(' ')
            .map(word => {
                if (!word) return '';
                return word.charAt(0).toUpperCase() + word.slice(1).toLowerCase();
            })
            .join(' ');
    }

    function performGlobalSearch() {
        const globalSearch = document.getElementById('globalSearch');
        const query = globalSearch.value.trim();
        if (query.length < 2) {
            alert('Please enter at least 2 characters to search');
            return;
        }

        window.location.href = `search-results.html?q=${encodeURIComponent(query)}`;
    }

    // Export functions to global scope
    window.openServiceModal = openServiceModal;
    window.clearServiceFilters = clearServiceFilters;
    window.openPortfolioLink = openPortfolioLink;
    window.openCreatePostModal = function (type = 'service', editId = null) {
        const modal = document.getElementById('createPostModal');
        if (modal) {
            modal.style.display = 'block';
            if (typeof loadCreatePostForm === 'function') {
                loadCreatePostForm(type, editId);
            }
        }
    };

    console.log('Services page loaded successfully');
});