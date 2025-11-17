<?php
// ✅ Use role-specific session name
session_name('USER_SESSION');
session_start();
require '../config/db.php';

// ✅ STRICT User Authentication Check
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in'] || 
    !isset($_SESSION['role']) || $_SESSION['role'] !== 'student' || 
    !isset($_SESSION['user_id'])) {
    session_destroy();
    header("Location: ../loginreg/login.php");
    exit();
}

// ✅ Check session timeout
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > 7200)) {
    session_unset();
    session_destroy();
    header("Location: ../loginreg/login.php?timeout=1");
    exit();
}

$_SESSION['login_time'] = time();

$user_id = $_SESSION['user_id'];

// ✅ Fetch full user data including profile photo
$queryUser = "SELECT * FROM userdata WHERE id = ?";
$stmt = $conn->prepare($queryUser);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

$username = htmlspecialchars($user['username']);
$tip_email = htmlspecialchars($user['tip_email']);
$profile_photo = !empty($user['profile_photo']) && file_exists($user['profile_photo']) 
    ? htmlspecialchars($user['profile_photo']) 
    : '../assets/Images/profile-icon.png';
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

                <!-- User Profile Dropdown -->
                <div class="user-profile" id="userProfile">
                    <img src="<?= $profile_photo ?>" alt="User" class="profile-img" id="userAvatar">
                    <span class="username" id="displayUsername">@<?php echo $username; ?></span>
                </div>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <nav class="nav-tabs">
            <a href="dashboard.php" class="nav-tab">Dashboard</a>
            <a href="marketplace.php" class="nav-tab active">Marketplace</a>
            <a href="services.php" class="nav-tab">Services</a>
        </nav>
    </header>

    <!-- User Profile Dropdown - Move this right after the header -->
    <div id="userProfileDropdown" class="profile-dropdown" style="display: none;">
        <div class="profile-header">
            <img src="<?= $profile_photo ?>" alt="User" class="dropdown-avatar">
            <div class="profile-details">
                <div class="profile-name" id="profileName"><?= htmlspecialchars($username ?? 'user'); ?></div>
                <div class="profile-email" id="profileEmail"><?= htmlspecialchars($tip_email ?? 'user@tip.edu.ph'); ?></div>
            </div>
        </div>
        <div class="profile-menu">
            <a href="edit-profile.php" class="menu-item">
                <img src="../assets/Images/profile-icon.svg" alt="Edit Profile" class="menu-icon-img">
                Edit Profile
            </a>
            <hr>
            <a href="../loginreg/logout.php" class="menu-item">
                <img src="../assets/Images/exit-icon.svg" alt="Logout" class="menu-icon-img">
                Logout
            </a>
        </div>
    </div>
    
    <!-- Add dropdown toggle script -->
    <script>
    // filepath: c:\xampp\htdocs\0neTip\users\marketplace.php
    (function () {
        document.addEventListener('DOMContentLoaded', function () {
            const userProfile = document.getElementById('userProfile');
            const dropdown = document.getElementById('userProfileDropdown');
            if (!userProfile || !dropdown) return;

            dropdown.style.display = 'none';

            userProfile.addEventListener('click', function (e) {
                e.stopPropagation();
                dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
            });

            document.addEventListener('click', function (e) {
                if (!userProfile.contains(e.target) && !dropdown.contains(e.target)) {
                    dropdown.style.display = 'none';
                }
            });

            // ✅ FIX: Menu item handlers - remove preventDefault and add proper navigation
            document.querySelectorAll('.menu-item').forEach(item => {
                item.addEventListener('click', function (ev) {
                    const href = this.getAttribute('href');
                    
                    // If it's a direct link (edit-profile.php or logout.php), let it navigate normally
                    if (href && href !== '#') {
                        return; // Don't prevent default, allow navigation
                    }
                    
                    // For action-based items, prevent default and handle
                    ev.preventDefault();
                    const action = this.dataset.action;
                    
                    if (action === 'settings') {
                        window.location.href = 'settings.php';
                    }
                    if (action === 'help') {
                        window.location.href = 'help.php';
                    }
                });
            });
        });
    })();
    </script>

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
                        <span>Books & Textbooks</span>
                    </div>
                    <div class="category-item" data-category="clothing">
                        <div class="category-icon">
                            <img src="../assets/Images/bag-icon.svg" alt="Clothing" class="category-icon-img">
                        </div>
                        <span>Clothing & Accessories</span>
                    </div>
                    <div class="category-item" data-category="furniture">
                        <div class="category-icon">
                            <img src="../assets/Images/misc-icon.svg" alt="Furniture" class="category-icon-img">
                        </div>
                        <span>Furniture</span>
                    </div>
                    <div class="category-item" data-category="sports">
                        <div class="category-icon">
                            <img src="../assets/Images/sports-icon.svg" alt="Sports" class="category-icon-img">
                        </div>
                        <span>Sports & Recreation</span>
                    </div>
                    <div class="category-item" data-category="musical_instruments">
                        <div class="category-icon">
                            <img src="../assets/Images/music-icon.svg" alt="Musical Instruments" class="category-icon-img">
                        </div>
                        <span>Musical Instruments</span>
                    </div>
                    <div class="category-item" data-category="automotive">
                        <div class="category-icon">
                            <img src="../assets/Images/gear-icon.svg" alt="Automotive" class="category-icon-img">
                        </div>
                        <span>Automotive</span>
                    </div>
                    <div class="category-item" data-category="home_garden">
                        <div class="category-icon">
                            <img src="../assets/Images/misc-icon.svg" alt="Home & Garden" class="category-icon-img">
                        </div>
                        <span>Home & Garden</span>
                    </div>
                    <div class="category-item" data-category="art_crafts">
                        <div class="category-icon">
                            <img src="../assets/Images/art-icon.svg" alt="Art & Crafts" class="category-icon-img">
                        </div>
                        <span>Art & Crafts</span>
                    </div>
                    <div class="category-item" data-category="other">
                        <div class="category-icon">
                            <img src="../assets/Images/misc-icon.svg" alt="Other" class="category-icon-img">
                        </div>
                        <span>Other</span>
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
                            <option value="poor">Poor</option>
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
<!-- Products Grid -->
<div class="products-section">
    <div class="products-grid" id="productsGrid">
        <?php
        include "../config/db.php";

        // Fetch approved marketplace items with user info AND total vouches
        $query = "
            SELECT 
                m.item_id,
                m.productName,
                m.productPrice,
                m.productCategory,
                m.productCondition,
                m.productImg,
                m.productDescription,
                m.sellerFacebook,
                m.sellerEmail,
                m.sellerChat,
                m.sellerMeetup,
                m.total_vouches,
                m.average_rating,
                m.posted_at,
                m.user_id,
                u.first_name,
                u.last_name,
                (SELECT COUNT(*) FROM vouches WHERE seller_id = m.user_id) as seller_vouches
            FROM marketplace_items m
            JOIN userdata u ON m.user_id = u.id
            WHERE m.status = 'active'
            ORDER BY m.posted_at DESC
            LIMIT 12
        ";

        $result = mysqli_query($conn, $query);

        if (!$result) {
            echo "<div style='color:red; text-align:center;'>❌ Database Error: " . htmlspecialchars(mysqli_error($conn)) . "</div>";
        } else {
            if (mysqli_num_rows($result) > 0):
                while ($product = mysqli_fetch_assoc($result)):
                    $imgPath = htmlspecialchars($product['productImg']);
                    $sellerName = htmlspecialchars($product['first_name'] . ' ' . $product['last_name']);
        ?>
                    <div class="product-card" data-product='<?= json_encode($product) ?>'>
                        <div class="product-image">
                            <?php if (!empty($imgPath) && (file_exists($imgPath) || str_starts_with($imgPath, 'http'))): ?>
                                <img src="<?= $imgPath ?>" alt="<?= htmlspecialchars($product['productName']) ?>">
                            <?php else: ?>
                                <img src="../assets/Images/placeholder.png" alt="No image available">
                            <?php endif; ?>
                        </div>

                        <div class="product-info">
                            <h3 class="product-title"><?= $product['productName'] ?></h3>
                            <p class="product-price">₱<?= number_format($product['productPrice'], 2) ?></p>
                            <p class="product-seller">Seller: <?= $sellerName ?></p>
                            <button class="btn-primary view-details-btn">View Details</button>
                        </div>
                    </div>
        <?php
                endwhile;
            else:
                echo "<div style='text-align:center; padding: 20px;'>⚠️ No approved products available in the database.</div>";
            endif;
        }

        mysqli_free_result($result);
        ?>
    </div>

    <div class="load-more-section">
        <button class="btn-secondary" id="loadMoreProducts">Load More Products</button>
    </div>
</div>

<!-- Product Details Modal -->
<div id="productModal" class="modal product-modal">
    <div class="modal-content product-modal-content">
        <span class="close product-modal-close" id="closeProductModal">&times;</span>

        <div class="product-modal-body">
            <div class="product-image-section">
                <div class="product-main-image" id="productMainImage"></div>
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
                            <img id="sellerAvatar" src="../assets/Images/profile-icon.png" alt="Seller" class="seller-profile-img">
                        </div>
                        <div class="seller-details">
                            <div class="seller-name" id="sellerName">John Doe</div>
                            <div class="seller-department" id="sellerDepartment">CCS Department</div>
                            <div class="seller-rating">
                                <span class="rating-stars" id="sellerRating">★★★★★</span>
                                <span class="rating-count" id="ratingCount">(0 vouches)</span>
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
                        <button class="btn-contact btn-gmail" id="openGmailModal">
                            <img src="../assets/Images/email-icon.svg" alt="Email" class="contact-icon">
                            View Gmail
                        </button>
                        
                        <button class="btn-contact btn-message" id="messageSellerBtn" style="background: #007bff; color: white;">
                            <img src="../assets/Images/chat-icon.svg" alt="Chat" class="contact-icon">
                            Message Seller
                        </button>
                        
                        <button class="btn-contact btn-vouch" id="vouchSellerBtn" style="background: #28a745;">
                            👍 Vouch Seller
                        </button>
                        <button class="btn-contact btn-rate" id="rateItemBtn" style="background: #ffc107;">
                            ⭐ Rate Item
                        </button>
                        <button class="btn-contact btn-report" id="reportItemBtn" style="background: #dc3545; color: white;">
                            🚩 Report Item
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ADD: Rating Modal -->
<div id="ratingModal" class="modal gmail-modal" style="display:none;">
  <div class="gmail-modal-content" style="max-width: 450px;">
    <span class="close" id="closeRatingModal">&times;</span>
    <h2>Rate this Item</h2>
    <div style="text-align: center; margin: 20px 0;">
        <div id="starRating" style="font-size: 2.5rem; cursor: pointer;">
            <span class="star" data-rating="1">☆</span>
            <span class="star" data-rating="2">☆</span>
            <span class="star" data-rating="3">☆</span>
            <span class="star" data-rating="4">☆</span>
            <span class="star" data-rating="5">☆</span>
        </div>
        <p id="ratingText" style="margin-top: 10px; color: #666;">Select a rating</p>
    </div>
    <textarea id="ratingReview" placeholder="Write a review (optional)" style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ddd; margin-bottom: 15px;" rows="3"></textarea>
    <button id="submitRatingBtn" class="btn-primary" style="width: 100%;">Submit Rating</button>
  </div>
</div>

<!-- Gmail Modal -->
<div id="gmailModal" class="modal gmail-modal">
  <div class="gmail-modal-content">
    <span class="close" id="closeGmailModal">&times;</span>
    <h2>Seller Gmail</h2>
    <div class="gmail-display">
      <span id="sellerGmail">example@gmail.com</span>
      <button id="copyGmailBtn" class="btn-copy">
        <i class="fa-regular fa-copy"></i> Copy
      </button>
    </div>
  </div>
</div>

<!-- ADD: Report Modal -->
<div id="reportModal" class="modal gmail-modal" style="display:none;">
  <div class="gmail-modal-content" style="max-width: 500px;">
    <span class="close" id="closeReportModal">&times;</span>
    <h2>Report Item</h2>
    <div style="text-align: left; margin: 20px 0;">
        <label style="display: block; margin-bottom: 10px; font-weight: 600;">Reason for Report *</label>
        <select id="reportReason" style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ddd; margin-bottom: 15px;">
            <option value="">Select a reason</option>
            <option value="scam">Suspected Scam/Fraud</option>
            <option value="fake">Fake/Counterfeit Item</option>
            <option value="inappropriate">Inappropriate Content</option>
            <option value="spam">Spam/Misleading</option>
            <option value="overpriced">Extremely Overpriced</option>
            <option value="stolen">Suspected Stolen Item</option>
            <option value="other">Other</option>
        </select>
        
        <label style="display: block; margin-bottom: 10px; font-weight: 600;">Additional Details</label>
        <textarea id="reportDescription" placeholder="Please provide more details about your report..." style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ddd; margin-bottom: 15px; min-height: 100px;"></textarea>
    </div>
    <button id="submitReportBtn" class="btn-primary" style="width: 100%; background: #dc3545;">Submit Report</button>
  </div>
</div>

<!-- JS for Modal & Gmail -->
<script>
const productModal = document.getElementById('productModal');
const closeModal = document.getElementById('closeProductModal');
const gmailModal = document.getElementById('gmailModal');
const openGmailModal = document.getElementById('openGmailModal');
const closeGmailModal = document.getElementById('closeGmailModal');
const sellerGmailSpan = document.getElementById('sellerGmail');
const copyGmailBtn = document.getElementById('copyGmailBtn');


// ADD: Rating modal elements
const ratingModal = document.getElementById('ratingModal');
const closeRatingModal = document.getElementById('closeRatingModal');
const rateItemBtn = document.getElementById('rateItemBtn');
const vouchSellerBtn = document.getElementById('vouchSellerBtn');
const starRating = document.getElementById('starRating');
const ratingText = document.getElementById('ratingText');
const submitRatingBtn = document.getElementById('submitRatingBtn');

// ADD: Report modal elements
const reportItemBtn = document.getElementById('reportItemBtn');
const reportModal = document.getElementById('reportModal');
const closeReportModal = document.getElementById('closeReportModal');
const submitReportBtn = document.getElementById('submitReportBtn');

let currentProduct = null;
let selectedRating = 0;

document.querySelectorAll('.view-details-btn').forEach(btn => {
    btn.addEventListener('click', (e) => {
        const card = e.target.closest('.product-card');
        const product = JSON.parse(card.getAttribute('data-product'));
        currentProduct = product;

        // Fill modal data
        document.getElementById('productTitle').innerText = product.productName;
        document.getElementById('productPrice').innerText = '₱' + parseFloat(product.productPrice).toFixed(2);
        document.getElementById('productDescription').innerText = product.productDescription || 'No description.';
        document.getElementById('productDate').innerText = 'Listed on ' + new Date(product.posted_at).toLocaleDateString();
        document.getElementById('sellerName').innerText = product.first_name + ' ' + product.last_name;
        document.getElementById('chatAvailability').innerText = product.sellerChat || 'Not available';
        document.getElementById('meetupAvailability').innerText = product.sellerMeetup || 'Not available';
        document.getElementById('ratingCount').innerText = `(${product.seller_vouches || 0} vouches)`;

        const mainImageContainer = document.getElementById('productMainImage');
        const imgPath = product.productImg && (product.productImg.startsWith('http') || product.productImg.trim() !== '')
            ? product.productImg
            : '../assets/Images/placeholder.png';
        mainImageContainer.innerHTML = `<img src="${imgPath}" alt="${product.productName}" class="modal-product-image" style="max-width: 100%; max-height: 100%; width: auto; height: auto; object-fit: contain;">`;

        // Show product modal
        productModal.style.display = 'flex';
    });
});

// ADD: Vouch functionality
vouchSellerBtn.addEventListener('click', async () => {
    if (!currentProduct) return;
    
    const formData = new FormData();
    formData.append('item_type', 'marketplace');
    formData.append('item_id', currentProduct.item_id);
    formData.append('seller_id', currentProduct.user_id);
    
    try {
        const response = await fetch('handle_vouch.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        alert(result.message);
        
        if (result.status === 'success') {
            // ✅ Update seller's total vouch count
            document.getElementById('ratingCount').innerText = `(${result.total_vouches} vouches)`;
            currentProduct.seller_vouches = result.total_vouches;
        }
    } catch (error) {
        alert('Error: ' + error.message);
    }
});

// ADD: Rating modal functionality
rateItemBtn.addEventListener('click', () => {
    if (!currentProduct) return;
    selectedRating = 0;
    document.getElementById('ratingReview').value = '';
    updateStars(0);
    ratingModal.style.display = 'flex';
});

closeRatingModal.addEventListener('click', () => {
    ratingModal.style.display = 'none';
});

// Star rating interaction
document.querySelectorAll('.star').forEach(star => {
    star.addEventListener('click', function() {
        selectedRating = parseInt(this.dataset.rating);
        updateStars(selectedRating);
    });
    
    star.addEventListener('mouseenter', function() {
        const hoverRating = parseInt(this.dataset.rating);
        updateStars(hoverRating, true);
    });
});

starRating.addEventListener('mouseleave', () => {
    updateStars(selectedRating);
});

function updateStars(rating, isHover = false) {
    document.querySelectorAll('.star').forEach((star, index) => {
        star.textContent = index < rating ? '★' : '☆';
        star.style.color = index < rating ? '#ffc107' : '#ddd';
    });
    
    const texts = ['', 'Poor', 'Fair', 'Good', 'Very Good', 'Excellent'];
    ratingText.textContent = rating > 0 ? texts[rating] : 'Select a rating';
}

submitRatingBtn.addEventListener('click', async () => {
    if (selectedRating === 0) {
        alert('Please select a rating');
        return;
    }
    
    const formData = new FormData();
    formData.append('item_type', 'marketplace');
    formData.append('item_id', currentProduct.item_id);
    formData.append('rating', selectedRating);
    formData.append('review', document.getElementById('ratingReview').value);
    
    try {
        const response = await fetch('handle_rating.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        alert(result.message);
        
        if (result.status === 'success') {
            ratingModal.style.display = 'none';
        }
    } catch (error) {
        alert('Error: ' + error.message);
    }
});

// ADD: Report functionality
reportItemBtn.addEventListener('click', () => {
    if (!currentProduct) return;
    document.getElementById('reportReason').value = '';
    document.getElementById('reportDescription').value = '';
    reportModal.style.display = 'flex';
});

closeReportModal.addEventListener('click', () => {
    reportModal.style.display = 'none';
});

submitReportBtn.addEventListener('click', async () => {
    const reason = document.getElementById('reportReason').value;
    const description = document.getElementById('reportDescription').value;
    
    if (!reason) {
        alert('Please select a reason for reporting');
        return;
    }
    
    const formData = new FormData();
    formData.append('item_id', currentProduct.item_id);
    formData.append('item_type', 'marketplace');
    formData.append('reason', reason);
    formData.append('description', description);
    
    try {
        const response = await fetch('handle_report.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        alert(result.message);
        
        if (result.status === 'success') {
            reportModal.style.display = 'none';
        }
    } catch (error) {
        alert('Error: ' + error.message);
    }
});

// Close product modal
closeModal.addEventListener('click', () => {
    productModal.style.display = 'none';
});

// Gmail modal behavior
openGmailModal.addEventListener('click', () => {
    if (currentProduct && currentProduct.sellerEmail) {
        sellerGmailSpan.textContent = currentProduct.sellerEmail;
    } else {
        sellerGmailSpan.textContent = 'No email available';
    }
    gmailModal.style.display = 'flex';
});

closeGmailModal.addEventListener('click', () => {
    gmailModal.style.display = 'none';
});

copyGmailBtn.addEventListener('click', () => {
    const gmail = sellerGmailSpan.textContent;
    navigator.clipboard.writeText(gmail);
    copyGmailBtn.innerHTML = '<i class="fa-solid fa-check"></i> Copied!';
    setTimeout(() => {
        copyGmailBtn.innerHTML = '<i class="fa-regular fa-copy"></i> Copy';
    }, 2000);
});

// ADD: Message Seller functionality
const messageSellerBtn = document.getElementById('messageSellerBtn');

if (messageSellerBtn) {
    messageSellerBtn.addEventListener('click', async () => {
        if (!currentProduct) return;
        
        // Don't allow messaging yourself
        const currentUserId = <?= $user_id ?>;
        if (currentProduct.user_id == currentUserId) {
            alert('You cannot message yourself!');
            return;
        }
        
        // Create or get chat room
        const formData = new FormData();
        formData.append('action', 'create_chat');
        formData.append('seller_id', currentProduct.user_id);
        formData.append('item_id', currentProduct.item_id);
        formData.append('item_type', 'marketplace');
        
        try {
            const response = await fetch('chat_api.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });
            const result = await response.json();
            
            if (result.status === 'success') {
                // Redirect to chat page
                window.location.href = `chat.php?chat_id=${result.chat_id}`;
            } else {
                alert(result.message || 'Failed to create chat');
            }
        } catch (error) {
            alert('Error: ' + error.message);
        }
    });
}

// Close any modal when clicking outside
window.addEventListener('click', (e) => {
    if (e.target === productModal) productModal.style.display = 'none';
    if (e.target === gmailModal) gmailModal.style.display = 'none';
    if (e.target === reportModal) reportModal.style.display = 'none';
});
</script>

<style>
/* Gmail Modal Styling */
.gmail-modal {
  display: none;
  position: fixed;
  z-index: 9999; /* Higher than product modal */
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  backdrop-filter: blur(4px);
  background-color: rgba(0, 0, 0, 0.5);
  justify-content: center;
  align-items: center;
}

.gmail-modal-content {
  background: #fff;
  padding: 20px 25px;
  border-radius: 10px;
  width: 90%;
  max-width: 380px;
  text-align: center;
  box-shadow: 0 8px 20px rgba(0,0,0,0.2);
  animation: fadeIn 0.25s ease;
  position: relative;
}

.gmail-modal-content h2 {
  margin-bottom: 15px;
  font-size: 1.3rem;
  color: #333;
}

.gmail-display {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 10px;
  background: #f7f7f7;
  padding: 10px 14px;
  border-radius: 8px;
  font-size: 0.95rem;
  word-break: break-all;
}

.btn-copy {
  background-color: #007bff;
  color: #fff;
  border: none;
  padding: 6px 12px;
  border-radius: 6px;
  cursor: pointer;
  font-size: 0.9rem;
}

.btn-copy:hover {
  background-color: #0056b3;
}

.gmail-modal .close {
  position: absolute;
  top: 8px;
  right: 12px;
  font-size: 1.5rem;
  color: #555;
  cursor: pointer;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(-10px); }
  to { opacity: 1; transform: translateY(0); }
}

/* Report Modal Styling */
.modal.gmail-modal {
  display: none;
  position: fixed;
  z-index: 10000; /* Higher than other modals */
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  backdrop-filter: blur(4px);
  background-color: rgba(0, 0, 0, 0.7);
  justify-content: center;
  align-items: center;
}

.gmail-modal-content {
  background: #fff;
  padding: 25px 30px;
  border-radius: 10px;
  width: 90%;
  max-width: 500px;
  text-align: left;
  box-shadow: 0 8px 30px rgba(0,0,0,0.3);
  animation: slideIn 0.3s ease;
  position: relative;
}

.gmail-modal-content h2 {
  margin-bottom: 20px;
  font-size: 1.5rem;
  color: #333;
}

label {
  font-weight: 500;
  margin-bottom: 8px;
  display: block;
}

input, select, textarea {
  width: 100%;
  padding: 10px;
  border-radius: 5px;
  border: 1px solid #ddd;
  margin-bottom: 15px;
  font-size: 0.95rem;
}

textarea {
  resize: vertical;
}

button.btn-primary {
  width: 100%;
    padding: 12px;
    background-color: #ffc107;
    color: #333;
    border: none;
    border-radius: 15px;
    font-size: 1rem;
    font-weight: 600;
    font-family: 'Poppins', sans-serif;
    cursor: pointer;
    transition: background-color 0.3s;
    text-decoration: none;
    display: inline-block;
    text-align: center;
}

button.btn-primary:hover {
  background: #0056b3;
}

.close {
  position: absolute;
  top: 15px;
  right: 20px;
  font-size: 1.5rem;
  color: #aaa;
  cursor: pointer;
}

.close:hover {
  color: #333;
}

@keyframes slideIn {
  from { opacity: 0; transform: translateY(-10px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>

    <!-- Create Post Modal -->


<!-- CREATE POST MODAL -->
<div id="createPostModal" class="modal" style="display:none;">
  <div class="modal-content">
    <div class="modal-header">
      <h2>Create New Post</h2>
      <span class="close" onclick="document.getElementById('createPostModal').style.display='none';">&times;</span>
    </div>

    <div class="modal-body">
      <p>Share your items or services with the ONE-TiP community</p>

      <!-- Post Type Selection -->
      <div class="post-type-selection">
        <button class="post-type-btn active" id="marketplaceBtn">Marketplace Item</button>
        <button class="post-type-btn" id="serviceBtn">Service Offer</button>
      </div>

      <!-- Marketplace Form -->
      <form id="marketplaceForm" class="create-post-form" enctype="multipart/form-data" style="display:block;">

        <div class="input-group">
          <label for="productName">Product Name *</label>
          <input type="text" id="productName" name="productName" placeholder="Enter product name" required maxlength="100">
        </div>

        <div class="form-row">
          <div class="input-group half">
            <label for="productPrice">Price (₱) *</label>
            <input type="number" id="productPrice" name="productPrice" placeholder="0.00" min="0" step="0.01" required>
          </div>

          <div class="input-group half">
            <label for="productCondition">Condition *</label>
            <select id="productCondition" name="productCondition" required>
              <option value="">Select condition</option>
              <option value="new">Brand New</option>
              <option value="like_new">Like New</option>
              <option value="excellent">Excellent</option>
              <option value="good">Good</option>
              <option value="fair">Fair</option>
              <option value="poor">Poor</option>
            </select>
          </div>
        </div>

        <div class="input-group">
          <label for="productCategory">Category *</label>
          <select id="productCategory" name="productCategory" required>
            <option value="">Select category</option>
            <option value="electronics">Electronics</option>
            <option value="books">Books & Textbooks</option>
            <option value="clothing">Clothing & Accessories</option>
            <option value="furniture">Furniture</option>
            <option value="sports">Sports & Recreation</option>
            <option value="musical_instruments">Musical Instruments</option>
            <option value="automotive">Automotive</option>
            <option value="home_garden">Home & Garden</option>
            <option value="art_crafts">Art & Crafts</option>
            <option value="other">Other</option>
          </select>
        </div>

        <div class="input-group">
          <label for="productDescription">Description *</label>
          <textarea id="productDescription" name="productDescription" placeholder="Describe your item in detail" required rows="4" maxlength="1000"></textarea>
        </div>

        <!-- Image Upload -->
        <div class="input-group">
            <label for="images">Upload Images</label>
            <input type="file" id="images" name="productImg[]" multiple accept="image/*" style="display: none;">
            <div class="upload-area" id="uploadArea">
                <div class="upload-icon">
                    <img src="../assets/Images/folder-icon.svg" alt="Upload"
                        style="width: 48px; height: 48px; filter: brightness(0) saturate(100%) invert(50%);">
                </div>
                <p>Click to upload or drag and drop</p>
                <small>PNG, JPG, GIF up to 10MB (Max 5 images)</small>
            </div>
        </div>


        <!-- Contact Info -->
        <div class="contact-section">
          <h4>Contact Information</h4>

          <div class="input-group">
            <label for="sellerBio">Your Bio</label>
            <textarea id="sellerBio" name="sellerBio" placeholder="Tell buyers about yourself" rows="3" maxlength="200"></textarea>
          </div>

          <div class="form-row">
            <div class="input-group half">
              <label for="sellerEmail">Email Address</label>
              <input type="email" id="sellerEmail" name="sellerEmail" placeholder="your.email@tip.edu.ph">
            </div>
            <div class="input-group half">
              <label for="sellerFacebook">Facebook</label>
              <input type="text" id="sellerFacebook" name="sellerFacebook" placeholder="Profile name">
            </div>
          </div>

          <div class="form-row">
            <div class="input-group half">
              <label for="sellerChat">Chat Availability</label>
              <input type="text" id="sellerChat" name="sellerChat" placeholder="e.g., 9 AM - 6 PM">
            </div>
            <div class="input-group half">
              <label for="sellerMeetup">Meetup Availability</label>
              <input type="text" id="sellerMeetup" name="sellerMeetup" placeholder="e.g., Weekends only">
            </div>
          </div>
        </div>

        <div class="form-actions">
          <button type="button" class="btn-secondary" onclick="document.getElementById('createPostModal').style.display='none';">Cancel</button>
          <button type="submit" class="btn-primary">Create Post</button>
        </div>
      </form>

      <!-- SERVICE OFFER FORM -->
        <form id="serviceForm" class="create-post-form" action="serviceOffer.php" method="POST" enctype="multipart/form-data" style="display:none;">
        <input type="hidden" name="post_type" value="service">

        <div class="input-group">
            <label for="serviceTitle">Service Title *</label>
            <input type="text" id="serviceTitle" name="serviceTitle" placeholder="Enter service title" required maxlength="100">
        </div>
        
        <div class="form-row">
            <div class="input-group half">
                <label for="startingPrice">Starting Price (₱) *</label>
                <input type="number" id="startingPrice" name="startingPrice" placeholder="0.00" min="0" step="0.01" required>
            </div>
            <div class="input-group half">
                <label for="serviceCategory">Category *</label>
                <select id="serviceCategory" name="serviceCategory" required>
                    <option value="">Select category</option>
                    <option value="tutoring">Tutoring & Education</option>
                    <option value="design">Graphic Design</option>
                    <option value="writing">Writing & Translation</option>
                    <option value="programming">Programming & Tech</option>
                    <option value="photography">Photography & Video</option>
                    <option value="music">Music & Audio</option>
                    <option value="business">Business & Marketing</option>
                    <option value="lifestyle">Lifestyle Services</option>
                    <option value="crafts">Arts & Crafts</option>
                    <option value="other">Other</option>
                </select>
            </div>
        </div>
        
        <div class="input-group">
            <label for="deliveryTime">Delivery Time *</label>
            <select id="deliveryTime" name="serviceDuration" required>
                <option value="">Select delivery time</option>
                <option value="1_day">1 Day</option>
                <option value="2_days">2 Days</option>
                <option value="3_days">3 Days</option>
                <option value="1_week">1 Week</option>
                <option value="2_weeks">2 Weeks</option>
                <option value="1_month">1 Month</option>
                <option value="custom">Custom Timeline</option>
            </select>
        </div>

        <div class="portfolio-section">
            <h4>Portfolio <button type="button" class="btn-add-item" id="addPortfolioItem">+ Add Item</button></h4>
            <p>Showcase your previous work to attract more clients</p>
            <div class="portfolio-items" id="portfolioItems"></div>
        </div>
        
        <div class="input-group">
            <label for="serviceDescription">Description *</label>
            <textarea id="serviceDescription" name="serviceDescription" placeholder="Describe your item or service in detail" required rows="5" maxlength="1000"></textarea>
        </div>

        <div class="input-group">
            <label for="serviceImages">Upload Images</label>
            <input type="file" id="serviceImages" name="serviceImages[]" multiple accept="image/*" style="display: none;">
            <div class="upload-area" id="serviceUploadArea">
                <div class="upload-icon">
                    <img src="../assets/Images/folder-icon.svg" alt="Upload"
                        style="width: 48px; height: 48px; filter: brightness(0) saturate(100%) invert(50%);">
                </div>
                <p>Click to upload or drag and drop</p>
                <small>PNG, JPG, GIF up to 10MB (Max 5 images)</small>
            </div>
        </div>

        <div class="contact-section">
            <h4>Contact Information</h4>
            <div class="input-group">
                <label for="serviceBio">Your Bio</label>
                <textarea id="serviceBio" name="providerBio" placeholder="Tell buyers/clients about yourself" rows="3" maxlength="200"></textarea>
            </div>
            <div class="form-row">
                <div class="input-group half">
                    <label for="serviceEmail">Email Address</label>
                    <input type="email" id="serviceEmail" name="contactEmail" placeholder="your.email@tip.edu.ph">
                </div>
                <div class="input-group half">
                    <label for="serviceFacebook">Facebook</label>
                    <input type="text" id="serviceFacebook" name="contactFacebook" placeholder="Profile name">
                </div>
            </div>
            <div class="form-row">
                <div class="input-group half">
                    <label for="serviceChatAvailability">Chat Availability</label>
                    <input type="text" id="serviceChatAvailability" name="contactChat" placeholder="e.g., 9 AM - 6 PM">
                </div>
                <div class="input-group half">
                    <label for="serviceMeetupAvailability">Meetup Availability</label>
                    <input type="text" id="serviceMeetupAvailability" name="contactMeetup" placeholder="e.g., Weekends only">
                </div>
            </div>
        </div>

        <div class="form-actions">
            <button type="button" class="btn-secondary" id="cancelServiceBtn">Cancel</button>
            <button type="submit" class="btn-primary" id="createServiceBtn">Create Post</button>
        </div>
    </form>
    </div>
  </div>
</div>

<script>
// ====== Modal Control ======
document.getElementById('createPostBtn')?.addEventListener('click', () => {
  document.getElementById('createPostModal').style.display = 'block';
});
window.onclick = e => {
  const modal = document.getElementById('createPostModal');
  if (e.target === modal) modal.style.display = 'none';
};

<!-- REMOVE: Facebook button from product modal contact section -->
// ====== Post Type Switching ======
const marketBtn = document.getElementById('marketplaceBtn');
const serviceBtn = document.getElementById('serviceBtn');
const marketForm = document.getElementById('marketplaceForm');
const serviceForm = document.getElementById('serviceForm');

marketBtn.addEventListener('click', () => {
  marketBtn.classList.add('active');
  serviceBtn.classList.remove('active');
  marketForm.style.display = 'block';
  serviceForm.style.display = 'none';
});

serviceBtn.addEventListener('click', () => {
  serviceBtn.classList.add('active');
  marketBtn.classList.remove('active');
  serviceForm.style.display = 'block';
  marketForm.style.display = 'none';
});

// ====== Marketplace Image Upload + Preview ======
const uploadArea = document.getElementById('uploadArea');
const imageInput = document.getElementById('images');

if (uploadArea && imageInput) {
    // ✅ FIX: Make the entire upload area clickable
    uploadArea.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        imageInput.click();
    });

    // Add visual feedback on dragover
    uploadArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        e.stopPropagation();
        uploadArea.style.borderColor = '#ffc107';
        uploadArea.style.backgroundColor = '#fff9e6';
    });

    uploadArea.addEventListener('dragleave', function(e) {
        e.preventDefault();
        e.stopPropagation();
        uploadArea.style.borderColor = '#ddd';
        uploadArea.style.backgroundColor = '#f8f9fa';
    });

    uploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        uploadArea.style.borderColor = '#ddd';
        uploadArea.style.backgroundColor = '#f8f9fa';
        
        if (e.dataTransfer.files.length > 0) {
            imageInput.files = e.dataTransfer.files;
            updateImagePreview();
        }
    });

    imageInput.addEventListener('change', updateImagePreview);
}

function updateImagePreview() {
  const files = imageInput.files;
  
  if (files.length === 0) {
      uploadArea.innerHTML = `
          <div class="upload-icon">
              <img src="../assets/Images/folder-icon.svg" alt="Upload"
                  style="width: 48px; height: 48px; filter: brightness(0) saturate(100%) invert(50%);">
          </div>
          <p>Click to upload or drag and drop</p>
          <small>PNG, JPG, GIF up to 10MB (Max 5 images)</small>
      `;
      return;
  }
  
  if (files.length > 5) {
    alert('❌ Maximum of 5 images only.');
    imageInput.value = '';
    uploadArea.innerHTML = `
        <div class="upload-icon">
            <img src="../assets/Images/folder-icon.svg" alt="Upload"
                style="width: 48px; height: 48px; filter: brightness(0) saturate(100%) invert(50%);">
        </div>
        <p>Click to upload or drag and drop</p>
        <small>PNG, JPG, GIF up to 10MB (Max 5 images)</small>
    `;
    return;
  }
  
  // ✅ FIX: Clear and rebuild with proper click handler
  uploadArea.innerHTML = '';
  
  const previewContainer = document.createElement('div');
  previewContainer.style.cssText = 'display: flex; flex-direction: column; align-items: center; justify-content: center; width: 100%;';
  
  const previewGrid = document.createElement('div');
  previewGrid.style.cssText = 'display: grid; grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); gap: 8px; padding: 10px; max-width: fit-content;';
  
  Array.from(files).forEach(file => {
    if (file.type.startsWith('image/')) {
      const reader = new FileReader();
      reader.onload = function(e) {
        const imgWrapper = document.createElement('div');
        imgWrapper.style.cssText = 'position: relative; width: 80px; height: 80px; overflow: hidden; border-radius: 8px; border: 2px solid #ddd;';
        
        const img = document.createElement('img');
        img.src = e.target.result;
        img.style.cssText = 'width: 100%; height: 100%; object-fit: cover;';
        
        imgWrapper.appendChild(img);
        previewGrid.appendChild(imgWrapper);
      };
      reader.readAsDataURL(file);
    }
  });
  
  const changeText = document.createElement('p');
  changeText.textContent = `${files.length} image(s) selected - Click to change`;
  changeText.style.cssText = 'text-align: center; margin-top: 10px; color: #666; font-size: 0.9rem; cursor: pointer;';
  
  previewContainer.appendChild(previewGrid);
  previewContainer.appendChild(changeText);
  uploadArea.appendChild(previewContainer);
  
  // ✅ Re-attach click handler to new content
  uploadArea.onclick = function(e) {
    e.preventDefault();
    imageInput.click();
  };
}

// ====== AJAX Submission for Marketplace ======
document.getElementById('marketplaceForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);

    fetch('itemoffer.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {  // ✅ FIX: Added parentheses around data
        alert(data.message);
        if (data.status === 'success') {
            form.reset();
            document.getElementById('createPostModal').style.display = 'none';
        }
    })
    .catch(err => {
        alert('❌ Something went wrong.');
        console.error(err);
    });
});

// ====== Portfolio Section: Add Item ======
const addPortfolioBtn = document.getElementById("addPortfolioItem");
const portfolioItems = document.getElementById("portfolioItems");

let portfolioCount = 0;
addPortfolioBtn.addEventListener("click", () => {
    portfolioCount++;
    const item = document.createElement("div");
    item.classList.add("portfolio-item");
    item.innerHTML = `
        <h5>Portfolio Item ${portfolioCount}</h5>
        <button type="button" class="btn-remove-item">Remove</button>
        <div class="input-group">
            <label>Project Title</label>
            <input type="text" name="portfolio_title[]" placeholder="Project Title Name" maxlength="100">
        </div>
        <div class="input-group">
            <label>Project Description</label>
            <textarea name="portfolio_description[]" rows="2" maxlength="300" placeholder="Brief description of the project and your role"></textarea>
        </div>
        <div class="input-group">
            <label>Image URL (Optional)</label>
            <input type="url" name="portfolio_image[]" placeholder="https://example.com/image.jpg">
            <small>Paste a link to showcase your work (from Google Drive, Imgur, etc.)</small>
        </div>
        <div class="input-group">
            <label>Detailed Description *</label>
            <textarea name="portfolio_item_description[]" required rows="3" maxlength="500" placeholder="Describe your item or service in detail"></textarea>
        </div>
    `;
    portfolioItems.appendChild(item);

    // Remove button logic
    item.querySelector(".btn-remove-item").addEventListener("click", () => {
        item.remove();
    });
});

// ====== Service Image Upload + Preview ======
const serviceUploadArea = document.getElementById("serviceUploadArea");
const serviceImageInput = document.getElementById("serviceImages");

if (serviceUploadArea && serviceImageInput) {
    serviceUploadArea.addEventListener("click", (e) => {
        e.preventDefault();
        e.stopPropagation();
        serviceImageInput.click();
    });

    serviceUploadArea.addEventListener("dragover", (e) => {
        e.preventDefault();
        e.stopPropagation();
        serviceUploadArea.classList.add("dragover");
    });

    serviceUploadArea.addEventListener("dragleave", (e) => {
        e.preventDefault();
        e.stopPropagation();
        serviceUploadArea.classList.remove("dragover");
    });

    serviceUploadArea.addEventListener("drop", (e) => {
        e.preventDefault();
        e.stopPropagation();
        serviceUploadArea.classList.remove("dragover");
        
        if (e.dataTransfer.files.length > 0) {
            serviceImageInput.files = e.dataTransfer.files;
            updateServicePreview();
        }
    });

    serviceImageInput.addEventListener("change", updateServicePreview);
}

function updateServicePreview() {
  const files = serviceImageInput.files;
  
  if (files.length === 0) {
      serviceUploadArea.innerHTML = `
          <div class="upload-icon">
              <img src="../assets/Images/folder-icon.svg" alt="Upload"
                  style="width: 48px; height: 48px; filter: brightness(0) saturate(100%) invert(50%);">
          </div>
          <p>Click to upload or drag and drop</p>
          <small>PNG, JPG, GIF up to 10MB (Max 5 images)</small>
      `;
      return;
  }
  
  if (files.length > 5) {
    alert("❌ Maximum of 5 images only.");
    serviceImageInput.value = "";
    serviceUploadArea.innerHTML = `
        <div class="upload-icon">
            <img src="../assets/Images/folder-icon.svg" alt="Upload"
                style="width: 48px; height: 48px; filter: brightness(0) saturate(100%) invert(50%);">
        </div>
        <p>Click to upload or drag and drop</p>
        <small>PNG, JPG, GIF up to 10MB (Max 5 images)</small>
    `;
    return;
  }
  
  serviceUploadArea.innerHTML = `
      <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; width: 100%;">
          <div class="upload-preview-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); gap: 8px; padding: 10px; max-width: fit-content;"></div>
          <p style="text-align: center; margin-top: 10px; color: #666; font-size: 0.9rem; cursor: pointer;">
              ${files.length} image(s) selected - Click to change
          </p>
      </div>
  `;
  
  const previewGrid = serviceUploadArea.querySelector('.upload-preview-grid');
  
  Array.from(files).forEach(file => {
    if (file.type.startsWith("image/")) {
      const reader = new FileReader();
      reader.onload = e => {
        const imgWrapper = document.createElement('div');
        imgWrapper.style.cssText = 'position: relative; width: 80px; height: 80px; overflow: hidden; border-radius: 8px; border: 2px solid #ddd; cursor: pointer;';
        
        const img = document.createElement("img");
        img.src = e.target.result;
        img.style.cssText = 'width: 100%; height: 100%; object-fit: cover;';
        
        imgWrapper.appendChild(img);
        previewGrid.appendChild(imgWrapper);
      };
      reader.readAsDataURL(file);
    }
  });
}

// ====== Service Form AJAX Submission ======
document.getElementById("serviceForm").addEventListener("submit", async function(e) {
    e.preventDefault();
    const formData = new FormData(this);

    const response = await fetch("serviceOffer.php", {
        method: "POST",
        body: formData
    });

    const result = await response.json();
    alert(result.message);

    if (result.status === "success") {
        this.reset();
        document.getElementById("serviceImagePreview").innerHTML = "";
        document.getElementById("portfolioItems").innerHTML = "";
        document.getElementById("createPostModal").style.display = "none";
    }
});
</script>

<!-- Category, Sort, and Filter functionality -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    let allProducts = [];
    
    // Store all product data on page load
    document.querySelectorAll('.product-card').forEach(card => {
        const productData = JSON.parse(card.getAttribute('data-product'));
        allProducts.push({
            element: card,
            data: productData
        });
    });

    // Category filter
    document.querySelectorAll('.category-item').forEach(item => {
        item.addEventListener('click', function() {
            document.querySelectorAll('.category-item').forEach(i => i.classList.remove('active'));
            this.classList.add('active');
            filterAndSort();
        });
    });

    // Sort and filter dropdowns
    document.getElementById('sortBy').addEventListener('change', filterAndSort);
    document.getElementById('conditionFilter').addEventListener('change', filterAndSort);
    document.getElementById('priceRange').addEventListener('change', filterAndSort);

    function filterAndSort() {
        const activeCategory = document.querySelector('.category-item.active').getAttribute('data-category');
        const sortBy = document.getElementById('sortBy').value;
        const conditionFilter = document.getElementById('conditionFilter').value;
        const priceRange = document.getElementById('priceRange').value;

        // Filter products
        let filteredProducts = allProducts.filter(product => {
            const data = product.data;
            
            // Category filter
            if (activeCategory !== 'all' && data.productCategory !== activeCategory) {
                return false;
            }
            
            // Condition filter
            if (conditionFilter !== 'all' && data.productCondition && data.productCondition !== conditionFilter) {
                return false;
            }
            
            // Price range filter
            if (priceRange !== 'all') {
                const price = parseFloat(data.productPrice);
                switch(priceRange) {
                    case '0-100':
                        if (price < 0 || price > 100) return false;
                        break;
                    case '100-500':
                        if (price < 100 || price > 500) return false;
                        break;
                    case '500-1000':
                        if (price < 500 || price > 1000) return false;
                        break;
                    case '1000-5000':
                        if (price < 1000 || price > 5000) return false;
                        break;
                    case '5000+':
                        if (price < 5000) return false;
                        break;
                }
            }
            
            return true;
        });

        // Sort products
        filteredProducts.sort((a, b) => {
            switch(sortBy) {
                case 'price_low':
                    return parseFloat(a.data.productPrice) - parseFloat(b.data.productPrice);
                case 'price_high':
                    return parseFloat(b.data.productPrice) - parseFloat(a.data.productPrice);
                case 'oldest':
                    return new Date(a.data.posted_at) - new Date(b.data.posted_at);
                case 'popular':
                    return (b.data.total_vouches || 0) - (a.data.total_vouches || 0);
                case 'newest':
                default:
                    return new Date(b.data.posted_at) - new Date(a.data.posted_at);
            }
        });

        // Update grid display
        const productsGrid = document.getElementById('productsGrid');
        productsGrid.innerHTML = '';
        
        if (filteredProducts.length === 0) {
            productsGrid.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 40px;"><p>No products found matching your criteria.</p></div>';
        } else {
            filteredProducts.forEach(product => {
                const clonedCard = product.element.cloneNode(true);
                productsGrid.appendChild(clonedCard);
            });
            
            // Re-attach click handlers to cloned elements
            attachProductCardListeners();
        }
    }
    
    // Function to attach event listeners to product cards
    function attachProductCardListeners() {
        document.querySelectorAll('.view-details-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const card = e.target.closest('.product-card');
                const product = JSON.parse(card.getAttribute('data-product'));
                currentProduct = product;

                // Fill modal data
                document.getElementById('productTitle').innerText = product.productName;
                document.getElementById('productPrice').innerText = '₱' + parseFloat(product.productPrice).toFixed(2);
                document.getElementById('productDescription').innerText = product.productDescription || 'No description.';
                document.getElementById('productDate').innerText = 'Listed on ' + new Date(product.posted_at).toLocaleDateString();
                document.getElementById('sellerName').innerText = product.first_name + ' ' + product.last_name;
                document.getElementById('chatAvailability').innerText = product.sellerChat || 'Not available';
                document.getElementById('meetupAvailability').innerText = product.sellerMeetup || 'Not available';
                document.getElementById('ratingCount').innerText = `(${product.seller_vouches || 0} vouches)`;

                const mainImageContainer = document.getElementById('productMainImage');
                const imgPath = product.productImg && (product.productImg.startsWith('http') || product.productImg.trim() !== '')
                    ? product.productImg
                    : '../assets/Images/placeholder.png';
                mainImageContainer.innerHTML = `<img src="${imgPath}" alt="${product.productName}" class="modal-product-image" style="max-width: 100%; max-height: 100%; width: auto; height: auto; object-fit: contain;">`;

                // Show product modal
                productModal.style.display = 'flex';
            });
        });
    }
});
</script>

</body>
</html>