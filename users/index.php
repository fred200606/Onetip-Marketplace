<?php
session_start();
require '../config/db.php';

$message = "";
$toastColor = "";

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ONE-TiP - Marketplace</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="../assets/dashboard.css">
</head>

<body>
    
    <!-- Navigation Header -->
    <header class="main-header">
        <div class="header-container">
            <div class="logo-section">
                <img src="../assets/Images/LOGO-LONG.png" alt="ONE-TiP" class="header-logo">
            </div>

            <!-- Global Search Bar -->
            <div class="search-section">
                <input type="text" id="globalSearch" placeholder="Search marketplace..." class="search-input">
                <button type="button" class="search-btn" id="searchBtn">
                    <img src="../assets/Images/grey-search-bar.svg" alt="Search" class="search-icon">
                </button>
            </div>

            <!-- User Controls -->
            <div class="user-section">
                <button type="button" class="btn-primary create-post-btn" id="createPostBtn">+ Create Post</button>

                <!-- Notification Icon with Badge -->
                <div class="notification-icon" id="notificationIcon">
                    <span class="notification-badge" id="notificationBadge">3</span>
                    <img src="../assets/Images/notification-icon.svg" alt="Notifications" class="notification-img">
                </div>

                <!-- User Profile Dropdown -->
                <div class="user-profile" id="userProfile">
                    <img src="../assets/Images/profile-icon.png" alt="User" class="profile-img" id="userAvatar">
                    <span class="username" id="displayUsername">@username</span>
                </div>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <nav class="nav-tabs">
            <a href="../users/user-dashboard.html" class="nav-tab">Dashboard</a>
            <a href="../users/index.php" class="nav-tab active">Marketplace</a>
            <a href="../users/services.php" class="nav-tab">Services</a>
        </nav>
    </header>

    <!-- User Profile Dropdown - Move this right after the header -->
    <div id="userProfileDropdown" class="profile-dropdown" style="display: none;">
        <div class="profile-header">
            <img src="../assets/Images/profile-icon.png" alt="User" class="dropdown-avatar">
            <div class="profile-details">
            <div class="profile-name" id="profileName"><?= htmlspecialchars($_SESSION['username'] ?? 'user'); ?></div>
            <div class="profile-email" id="profileEmail"><?= htmlspecialchars($_SESSION['tip_email'] ?? 'user@tip.edu.ph'); ?></div>
            </div>
        </div>
        <div class="profile-menu">
            <a href="#" class="menu-item" data-action="edit-profile">
                <img src="../assets/Images/profile-icon.svg" alt="Edit Profile" class="menu-icon-img">
                Edit Profile
            </a>
            <a href="#" class="menu-item" data-action="settings">
                <img src="../assets/Images/gear-icon.svg" alt="Settings" class="menu-icon-img">
                Settings
            </a>
            <a href="#" class="menu-item" data-action="help">
                <img src="../assets/Images/question-mark-icon.svg" alt="Help" class="menu-icon-img">
                Help & Support
            </a>
            <hr>
            <a href="#" class="menu-item" data-action="logout">
                <img src="../assets/Images/exit-icon.svg" alt="Logout" class="menu-icon-img">
                Logout
            </a>
        </div>
    </div>

    <!-- Marketplace Section -->
    <main class="marketplace-main" style="padding-top: 0; max-width: 1400px; margin: 0 auto;">
        <div class="marketplace-container">
            <!-- Marketplace Header -->
            <div class="marketplace-header">
                <div class="marketplace-banner">
                    <img src="../assets/Images/LOGO-LONG.png" alt="ONE-TiP Marketplace" class="marketplace-logo">
                    <h1>Marketplace</h1>
                    <p>Find great deals from fellow TiP students!</p>
                </div>
            </div>

            <!-- Shop by Category -->
            <div class="category-section">
                <h2>Shop by Category!</h2>
                <div class="category-grid">
                    <div class="category-item active" data-category="all">
                        <div class="category-icon">
                            <img src="../assets/Images/all-items-icon.svg" alt="All Items" class="category-icon-img">
                        </div>
                        <span>All Items</span>
                    </div>
                    <div class="category-item" data-category="electronics">
                        <div class="category-icon">
                            <img src="../assets/Images/electronics-icon.svg" alt="Electronics" class="category-icon-img">
                        </div>
                        <span>Electronics</span>
                    </div>
                    <div class="category-item" data-category="books">
                        <div class="category-icon">
                            <img src="../assets/Images/book-icon.svg" alt="Books" class="category-icon-img">
                        </div>
                        <span>Books & Supplies</span>
                    </div>
                    <div class="category-item" data-category="calculators">
                        <div class="category-icon">
                            <img src="../assets/Images/calculators-icon.svg" alt="Calculators" class="category-icon-img">
                        </div>
                        <span>Calculators</span>
                    </div>
                    <div class="category-item" data-category="art">
                        <div class="category-icon">
                            <img src="../assets/Images/art-icon.svg" alt="Art Supplies" class="category-icon-img">
                        </div>
                        <span>Art Supplies</span>
                    </div>
                    <div class="category-item" data-category="musical">
                        <div class="category-icon">
                            <img src="../assets/Images/music-icon.svg" alt="Musical Instruments" class="category-icon-img">
                        </div>
                        <span>Musical Instruments</span>
                    </div>
                    <div class="category-item" data-category="sports">
                        <div class="category-icon">
                            <img src="../assets/Images/sports-icon.svg" alt="Sports Equipment" class="category-icon-img">
                        </div>
                        <span>Sports Equipment</span>
                    </div>
                    <div class="category-item" data-category="food">
                        <div class="category-icon">
                            <img src="../assets/Images/food-icon.svg" alt="Food & Snacks" class="category-icon-img">
                        </div>
                        <span>Food & Snacks</span>
                    </div>
                    <div class="category-item" data-category="bags">
                        <div class="category-icon">
                            <img src="../assets/Images/bag-icon.svg" alt="Bags & Accessories" class="category-icon-img">
                        </div>
                        <span>Bags & Accessories</span>
                    </div>
                    <div class="category-item" data-category="misc">
                        <div class="category-icon">
                            <img src="../assets/Images/misc-icon.svg" alt="Miscellaneous" class="category-icon-img">
                        </div>
                        <span>Miscellaneous</span>
                    </div>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="filter-section">
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="sortBy">Sort by:</label>
                        <select id="sortBy" class="filter-select">
                            <option value="newest">Newest First</option>
                            <option value="oldest">Oldest First</option>
                            <option value="price_low">Price: Low to High</option>
                            <option value="price_high">Price: High to Low</option>
                            <option value="popular">Most Popular</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="conditionFilter">Condition:</label>
                        <select id="conditionFilter" class="filter-select">
                            <option value="all">All Conditions</option>
                            <option value="new">Brand New</option>
                            <option value="like_new">Like New</option>
                            <option value="excellent">Excellent</option>
                            <option value="good">Good</option>
                            <option value="fair">Fair</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="priceRange">Price Range:</label>
                        <select id="priceRange" class="filter-select">
                            <option value="all">All Prices</option>
                            <option value="0-100">₱0 - ₱100</option>
                            <option value="100-500">₱100 - ₱500</option>
                            <option value="500-1000">₱500 - ₱1,000</option>
                            <option value="1000-5000">₱1,000 - ₱5,000</option>
                            <option value="5000+">₱5,000+</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Products Grid -->
            <div class="products-section">
                <div class="products-grid" id="productsGrid">
                    <!-- Products will be loaded here -->
                </div>

                <div class="load-more-section">
                    <button class="btn-secondary" id="loadMoreProducts">Load More Products</button>
                </div>
            </div>
        </div>
    </main>

    <!-- Product Details Modal -->
    <div id="productModal" class="modal product-modal">
        <div class="modal-content product-modal-content">
            <span class="close product-modal-close" id="closeProductModal">&times;</span>

            <div class="product-modal-body">
                <div class="product-image-section">
                    <div class="product-main-image" id="productMainImage">
                        <!-- Main product image will be loaded here -->
                    </div>
                </div>

                <div class="product-details-section">
                    <div class="product-header">
                        <h2 id="productTitle">Product Name</h2>
                        <p class="product-listing-date" id="productDate">Listed 9 days ago</p>
                    </div>

                    <div class="product-price-section">
                        <div class="price-badge">
                            <span class="product-price" id="productPrice">₱9,999</span>
                            <span class="price-type" id="priceType">Negotiable - Cash on meetup</span>
                        </div>
                    </div>

                    <div class="product-description">
                        <h4>Description</h4>
                        <p id="productDescription">Product description will be displayed here...</p>
                    </div>

                    <div class="seller-info-section">
                        <h4>Seller Information</h4>
                        <div class="seller-card">
                            <div class="seller-avatar">
                                <img id="sellerAvatar" src="../assets/Images/profile-icon.png" alt="Seller"
                                    class="seller-profile-img">
                            </div>
                            <div class="seller-details">
                                <div class="seller-name" id="sellerName">John Doe</div>
                                <div class="seller-department" id="sellerDepartment">CCS Department</div>
                                <div class="seller-rating">
                                    <span class="rating-stars" id="sellerRating">★★★★★</span>
                                    <span class="rating-count" id="ratingCount">(15 vouches)</span>
                                </div>
                            </div>
                        </div>

                        <div class="contact-availability">
                            <div class="availability-item">
                                <strong>Chat availability:</strong>
                                <span id="chatAvailability">8:00 am - 9:00 pm</span>
                            </div>
                            <div class="availability-item">
                                <strong>Meetup:</strong>
                                <span id="meetupAvailability">Weekdays - Cash / Arlegui Campus</span>
                            </div>
                        </div>

                        <div class="contact-buttons">
                            <button class="btn-contact btn-facebook" id="contactFacebook">
                                <img src="../assets/Images/facebook-icon.svg" alt="Facebook" class="contact-icon">
                                Facebook
                            </button>
                            <button class="btn-contact btn-email" id="contactEmail">
                                <img src="../assets/Images/email-icon.svg" alt="Email" class="contact-icon">
                                Email
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Post Modal -->
    <div id="createPostModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Create New Post</h2>
                <span class="close" id="closeModal">&times;</span>
            </div>
            <div class="modal-body">
                <p>Share your items or services with the ONE-TiP community</p>

                <!-- Post Type Selection -->
                <div class="post-type-selection">
                    <button class="post-type-btn active" data-type="marketplace" id="marketplaceBtn">Marketplace Item
                    </button>
                    <button class="post-type-btn" data-type="service" id="serviceBtn">Service Offer</button>
                </div>

                <!-- Dynamic Form Container -->
                <div id="createPostForm">
                    <!-- Forms will be loaded here via JavaScript -->
                </div>
            </div>
        </div>
    </div>

    <!-- Notification Panel -->
    <div id="notificationPanel" class="notification-panel">
        <div class="notification-header">
            <h3>Notifications</h3>
            <span class="notification-count" id="notificationCount">3 unread notifications</span>
            <div class="notification-actions">
                <button class="notification-btn active" data-filter="all">All</button>
                <button class="notification-btn" data-filter="unread">Unread (3)</button>
                <button class="notification-btn" id="markAllRead">Mark all read</button>
            </div>
        </div>
        <div class="notification-list" id="notificationList">
            <!-- Notifications will be loaded here via JavaScript -->
        </div>
    </div>

    <!-- JavaScript Files -->
    <script src="assets/js/auth.js"></script>
    <script src="../assets/script/marketplace.js"></script>
    <script src="../assets/script/create-post.js"></script>
    <script src="../assets/script/notifications.js"></script>
</body>

</html>