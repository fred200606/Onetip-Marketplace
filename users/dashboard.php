<?php
// ✅ Use role-specific session name
session_name('USER_SESSION');
session_start();
include '../config/db.php';

// ✅ Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    session_destroy();
    header("Location: ../loginreg/login.php");
    exit();
}

// ✅ Check session timeout (2 hours)
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > 7200)) {
    session_unset();
    session_destroy();
    header("Location: ../loginreg/login.php?timeout=1");
    exit();
}

$_SESSION['login_time'] = time();

$user_id = $_SESSION['user_id'];
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

// ✅ Check for profile update success
$profileUpdateMessage = '';
if (isset($_GET['profile_updated']) && $_GET['profile_updated'] == 1) {
    $profileUpdateMessage = '<div style="padding: 15px; margin-bottom: 20px; border-radius: 8px; background: #d4edda; color: #155724; text-align: center;">✅ Profile updated successfully!</div>';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ONE-TiP - Dashboard</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="../assets/dashboard.css">
</head>

<body>
    <?php if (!empty($profileUpdateMessage)) echo $profileUpdateMessage; ?>
    
    <!-- Navigation Header -->
    <header class="main-header">
        <div class="header-container">
            <div class="logo-section">
                <img src="../assets/Images/LOGO-LONG.png" alt="ONE-TiP" class="header-logo">
            </div>

            <!-- Global Search Bar -->
            <div class="search-section">
                <input type="text" id="globalSearch" placeholder="Search marketplace and services..."
                    class="search-input">
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
            <button class="nav-tab active" data-section="dashboard">Dashboard</button>
            <a href="marketplace.php" class="nav-tab">Marketplace</a>
            <a href="services.php" class="nav-tab">Services</a>
            <a href="chat.php" class="nav-tab">Messages</a>
        </nav>
    </header>

     <!-- User Profile Dropdown - Move this right after the header -->
    <div id="userProfileDropdown" class="profile-dropdown" style="display: none;">
        <div class="profile-header">
            <img src="<?= $profile_photo ?>" alt="User" class="dropdown-avatar">
            <div class="profile-details">
                <div class="profile-name" id="profileName"><?php echo $username; ?></div>
                <div class="profile-email" id="profileEmail"><?php echo $tip_email; ?></div>
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
    // filepath: c:\xampp\htdocs\0neTip\users\dashboard.php
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

            // Menu item handlers
            document.querySelectorAll('.menu-item').forEach(item => {
                item.addEventListener('click', function (ev) {
                    const action = this.dataset.action;
                    if (action === 'settings') {
                        ev.preventDefault();
                        window.location.href = 'settings.php';
                    }
                    if (action === 'help') {
                        ev.preventDefault();
                        window.location.href = 'help.php';
                    }
                    // Edit profile and logout have direct href links
                });
            });
        });
    })();
    </script>

    <!-- Dashboard Section -->
    <section id="dashboard" class="section-content active">
        <div class="dashboard-container">
            <!-- 
            Backend Integration: User Statistics
            SQL Query Example:
            SELECT 
                COUNT(CASE WHEN status = 'active' AND type = 'marketplace' THEN 1 END) as active_marketplace,
                COUNT(CASE WHEN status = 'active' AND type = 'service' THEN 1 END) as active_services,
                (SELECT COUNT(*) FROM vouches WHERE receiver_id = ?) as total_vouches,
                (SELECT COUNT(*) FROM likes WHERE post_user_id = ?) as total_likes
            FROM posts WHERE user_id = ?
            -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number" id="activeListings" data-user-id="">
                        <?php
                        // Get marketplace items count
                        $marketplaceQuery = "SELECT COUNT(*) as total FROM marketplace_items WHERE user_id = ? AND status = 'active'";
                        $stmt = $conn->prepare($marketplaceQuery);
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $marketplaceResult = $stmt->get_result()->fetch_assoc();
                        $marketplaceCount = $marketplaceResult['total'] ?? 0;
                        
                        // Get service offers count
                        $servicesQuery = "SELECT COUNT(*) as total FROM service_offers WHERE user_id = ? AND status = 'available'";
                        $stmt = $conn->prepare($servicesQuery);
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $servicesResult = $stmt->get_result()->fetch_assoc();
                        $servicesCount = $servicesResult['total'] ?? 0;
                        
                        // Display combined total
                        $totalListings = $marketplaceCount + $servicesCount;
                        echo $totalListings;
                        ?>
                    </div>
                    <div class="stat-label">Your Active Listings</div>
                    <div class="stat-sublabel" id="listingsBreakdown">
                        <?php echo "marketplace {$marketplaceCount} | services {$servicesCount}"; ?>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="totalVouches">
                        <?php
                        $vouchQuery = "SELECT COUNT(*) as total FROM vouches WHERE seller_id = ?";
                        $stmt = $conn->prepare($vouchQuery);
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $vouchResult = $stmt->get_result()->fetch_assoc();
                        echo $vouchResult['total'] ?? 0;
                        ?>
                    </div>
                    <div class="stat-label">Total Vouches</div>
                    <div class="stat-sublabel" id="vouchesThisMonth">from buyers</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="totalMarketplacePosts">
                        <?php
                        // Get total marketplace posts from ALL users (active only)
                        $marketplaceTotalQuery = "SELECT COUNT(*) as total FROM marketplace_items WHERE status = 'active'";
                        $stmt = $conn->prepare($marketplaceTotalQuery);
                        $stmt->execute();
                        $marketplaceTotalResult = $stmt->get_result()->fetch_assoc();
                        echo $marketplaceTotalResult['total'] ?? 0;
                        ?>
                    </div>
                    <div class="stat-label">Total Marketplace Posts</div>
                    <div class="stat-sublabel" id="marketplacePostsBreakdown">on platform</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="totalServicesPosts">
                        <?php
                        // Get total service posts from ALL users (available only)
                        $servicesTotalQuery = "SELECT COUNT(*) as total FROM service_offers WHERE status = 'available'";
                        $stmt = $conn->prepare($servicesTotalQuery);
                        $stmt->execute();
                        $servicesTotalResult = $stmt->get_result()->fetch_assoc();
                        echo $servicesTotalResult['total'] ?? 0;
                        ?>
                    </div>
                    <div class="stat-label">Total Services Posts</div>
                    <div class="stat-sublabel" id="servicesPostsBreakdown">on platform</div>
                </div>
            </div>

            <!-- Current Marketplace Listings -->
<!-- Marketplace Listings -->
<div class="dashboard-section">
    <h3>Your Active Marketplace Listings</h3>
    <div class="listings-container" id="marketplaceListings">
    <?php
    $queryMarket = "SELECT item_id, productName, productDescription, productPrice, productImg, posted_at 
                    FROM marketplace_items 
                    WHERE user_id = ? AND status = 'active' 
                    ORDER BY posted_at DESC 
                    LIMIT 5";
    $stmt = $conn->prepare($queryMarket);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $marketResults = $stmt->get_result();

    if ($marketResults->num_rows > 0) {
        while ($row = $marketResults->fetch_assoc()) {
            $imagePath = !empty($row['productImg']) ? $row['productImg'] : '../assets/Images/placeholder.png';
            echo '
            <div class="listing-card" data-listing-id="'.$row['item_id'].'">
                <img src="'.$imagePath.'" alt="'.htmlspecialchars($row['productName']).'" class="listing-image">
                <div class="listing-info">
                    <h4>'.htmlspecialchars($row['productName']).'</h4>
                    <p>'.htmlspecialchars($row['productDescription']).'</p>
                    <div class="listing-meta">
                        <span class="listing-price">₱'.number_format($row['productPrice'], 2).'</span>
                        <span class="listing-dates">
                            Posted: '.$row['posted_at'].'
                        </span>
                    </div>
                </div>
            </div>';
        }
    } else {
        echo '<div class="no-listings"><p>You don\'t have any active listings yet.</p></div>';
    }
    ?>
    </div>
</div>

<!-- Service Listings -->
<div class="dashboard-section">
    <h3>Your Active Service Listings</h3>
    <div class="services-container" id="serviceListings">
    <?php
    $queryService = "SELECT id, serviceTitle, serviceDescription, startingPrice, serviceImages, posted_at 
                     FROM service_offers 
                     WHERE user_id = ? AND status = 'available' 
                     ORDER BY posted_at DESC 
                     LIMIT 5";
    $stmt = $conn->prepare($queryService);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $serviceResults = $stmt->get_result();

    if ($serviceResults->num_rows > 0) {
        while ($row = $serviceResults->fetch_assoc()) {
            echo '
            <div class="service-card" data-service-id="'.$row['id'].'">
                <div class="service-icon">💰</div>
                <div class="service-info">
                    <h4>'.htmlspecialchars($row['serviceTitle']).'</h4>
                    <p>'.htmlspecialchars($row['serviceDescription']).'</p>
                    <div class="service-meta">
                        <span class="service-price">₱'.number_format($row['startingPrice'], 2).'/hr</span>
                        <span class="service-dates">
                            Posted: '.$row['posted_at'].'
                        </span>
                    </div>
                </div>
                <div class="service-actions">
                    <button class="btn-edit" data-action="edit" data-id="'.$row['id'].'">
                        <img src="../assets/Images/pencil-icon.png" alt="Edit" class="action-icon">
                    </button>
                    <button class="btn-delete" data-action="delete" data-id="'.$row['id'].'">
                        <img src="../assets/Images/trash-can-icon.png" alt="Delete" class="action-icon">
                    </button>
                </div>
            </div>';
        }
    } else {
        echo '<div class="no-services" id="noServiceListings">
                <p>You don\'t have any active service listings yet.</p>
                <button class="btn-secondary" onclick="openCreatePostModal(\'service\')">Create Your First Service</button>
            </div>';
    }
    ?>
    </div>
</div>


            <!-- Recent Activity Section -->
            <div class="dashboard-section">
                <h3>📬 Recent Notifications</h3>
                <div class="notification-list-dashboard" id="dashboardNotifications">
                    <?php
                    // ✅ Fetch user notifications
                    $notifQuery = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 10";
                    $stmt = $conn->prepare($notifQuery);
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $notifications = $stmt->get_result();
                    
                    if ($notifications->num_rows > 0):
                        while ($notif = $notifications->fetch_assoc()):
                            $icon = match($notif['type']) {
                                'vouch' => '👍',
                                'post_approved' => '✅',
                                'post_rejected' => '❌',
                                default => '🔔'
                            };
                            $bgColor = $notif['is_read'] ? '#f8f9fa' : '#fff9e6';
                    ?>
                        <div class="notification-item-dashboard" style="background: <?= $bgColor ?>;" data-notif-id="<?= $notif['id'] ?>">
                            <div class="notif-icon"><?= $icon ?></div>
                            <div class="notif-content">
                                <div class="notif-title"><?= htmlspecialchars($notif['title']) ?></div>
                                <div class="notif-message"><?= htmlspecialchars($notif['message']) ?></div>
                                <div class="notif-time"><?= date('M d, Y g:i A', strtotime($notif['created_at'])) ?></div>
                            </div>
                            <?php if (!$notif['is_read']): ?>
                                <button class="mark-read-btn-small" onclick="markAsRead(<?= $notif['id'] ?>)">✓</button>
                            <?php endif; ?>
                        </div>
                    <?php
                        endwhile;
                    else:
                    ?>
                        <div class="no-notifications-dashboard">
                            <p>🔕 No notifications yet</p>
                            <small>You'll be notified about vouches and post approvals here</small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section (Placeholder) -->
    <section id="services" class="section-content">
        <div class="services-container">
            <div class="coming-soon">
                <h2>Services Section</h2>
                <p>This section will be implemented with your services details.</p>
            </div>
        </div>
    </section>

    <!-- Create Post Modal -->
    <!-- 
    Backend: Form submission to /api/posts/create
    Required fields depend on post type (marketplace vs service)
    -->
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

    <!-- Notification Panel -->
    <!-- 
    Backend: Get notifications from database
    SQL: SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC
    -->
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
                            <button class="btn-contact btn-email" id="contactEmail">
                                <img src="../assets/Images/email-icon.svg" alt="Email" class="contact-icon">
                                Email
                            </button>
                            <button class="btn-contact btn-chat" id="contactChat">
                                <img src="../assets/Images/chat-icon.svg" alt="Chat" class="contact-icon">
                                Chat
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript Files -->
    <script src="assets/js/auth.js"></script>
    <script src="assets/js/dashboard.js"></script>
    <script src="assets/js/notifications.js"></script>
</body>

</html>