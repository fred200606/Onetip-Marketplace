<?php
// Force USER_SESSION namespace for consistency across the app
if (session_status() === PHP_SESSION_NONE) {
    session_name('USER_SESSION');
    session_start();
}

include '../config/db.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}
$user_id = $_SESSION['user_id'];
$queryUser = "SELECT username, tip_email FROM userdata WHERE id = ?";
$stmt = $conn->prepare($queryUser);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

$username = htmlspecialchars($user['username']);
$tip_email = htmlspecialchars($user['tip_email']);
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ONE-TiP - Services</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="../assets/dashboard.css">
    <link rel="stylesheet" href="../assets/services.css">
</head>

<body>

    <!-- Navigation Header -->
    <header class="main-header">
        <div class="header-container">
            <div class="logo-section">
                <img src="../assets/Images/LOGO-LONG.png" alt="ONE-TiP" class="header-logo">
            </div>

            <div class="search-section">
                <input type="text" id="globalSearch" placeholder="Search services..." class="search-input">
                <button type="button" class="search-btn" id="searchBtn">
                    <img src="../assets/Images/grey-search-bar.svg" alt="Search" class="search-icon">
                </button>
            </div>

            <div class="user-section">
                <button type="button" class="btn-primary create-post-btn" id="createPostBtn">+ Create Post</button>

                <div class="user-profile" id="userProfile">
                    <img src="../assets/Images/profile-icon.png" alt="User" class="profile-img" id="userAvatar">
                    <span class="username" id="displayUsername">@<?php echo $username; ?></span>
                </div>
            </div>
        </div>

        <nav class="nav-tabs">
            <a href="dashboard.php" class="nav-tab">Dashboard</a>
            <a href="marketplace.php" class="nav-tab">Marketplace</a>
            <a href="services.php" class="nav-tab active">Services</a>
        </nav>
    </header>
     <!-- User Profile Dropdown - Move this right after the header -->
    <div id="userProfileDropdown" class="profile-dropdown" style="display: none;">
        <div class="profile-header">
            <img src="../assets/Images/profile-icon.png" alt="User" class="dropdown-avatar">
            <div class="profile-details">
                <div class="profile-name" id="profileName"><?php echo $username; ?></div>
                <div class="profile-email" id="profileEmail"><?php echo $tip_email; ?></div>
            </div>
        </div>
        <div class="profile-menu">
            <a href="#" class="menu-item" data-action="edit-profile">
                <img src="../assets/Images/profile-icon.svg" alt="Edit Profile" class="menu-icon-img">
                Edit Profile
            </a>
           
            <hr>
            <a href="#" class="menu-item" data-action="logout">
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

            // Simple handlers for menu actions (adjust targets as needed)
            document.querySelectorAll('.menu-item').forEach(item => {
                item.addEventListener('click', function (ev) {
                    ev.preventDefault();
                    const action = this.dataset.action;
                    if (action === 'logout') {
                        // redirect to your logout endpoint
                        window.location.href = '../loginreg/logout.php';
                        return;
                    }
                    if (action === 'edit-profile') window.location.href = 'edit-profile.php';
                    if (action === 'settings') window.location.href = 'settings.php';
                    if (action === 'help') window.location.href = 'help.php';
                });
            });
        });
    })();
    </script>

    <!-- Services Main Content -->
    <main class="services-main">
        <div class="services-container">
            <!-- Services Header -->
            <div class="services-header">
                <div class="services-banner">
                    <img src="../assets/Images/LOGO-LONG.png" alt="ONE-TiP Services" class="services-logo">
                    <h1>Services</h1>
                    <p>Get help from talented TiP students!</p>
                </div>
            </div>

            <!-- Services Filter Section -->
            <div class="services-filter-section">
                <div class="services-filter-row">
                    <div class="services-filter-group">
                        <label for="servicesSortBy">Sort by:</label>
                        <select id="servicesSortBy" class="services-filter-select">
                            <option value="newest">Newest First</option>
                            <option value="oldest">Oldest First</option>
                            <option value="price_low">Price: Low to High</option>
                            <option value="price_high">Price: High to Low</option>
                            <option value="rating">Highest Rated</option>
                            <option value="popular">Most Popular</option>
                        </select>
                    </div>

                    <div class="services-filter-group">
                        <label for="servicesCategory">Categories:</label>
                        <select id="servicesCategory" class="services-filter-select">
                            <option value="all">All Categories</option>
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

                    <div class="services-filter-group">
                        <label for="servicesPriceRange">Price Range:</label>
                        <select id="servicesPriceRange" class="services-filter-select">
                            <option value="all">All Prices</option>
                            <option value="0-50">₱0 - ₱50/hr</option>
                            <option value="50-100">₱50 - ₱100/hr</option>
                            <option value="100-200">₱100 - ₱200/hr</option>
                            <option value="200-500">₱200 - ₱500/hr</option>
                            <option value="500+">₱500+/hr</option>
                        </select>
                    </div>

                    <div class="services-filter-group">
                        <label for="servicesDeliveryTime">Delivery Time:</label>
                        <select id="servicesDeliveryTime" class="services-filter-select">
                            <option value="all">All Delivery Times</option>
                            <option value="1_day">1 Day</option>
                            <option value="2_days">2 Days</option>
                            <option value="3_days">3 Days</option>
                            <option value="1_week">1 Week</option>
                            <option value="2_weeks">2 Weeks</option>
                            <option value="1_month">1 Month</option>
                            <option value="custom">Custom Timeline</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Services Grid -->
<div class="services-section">
    <div class="services-grid" id="servicesGrid">
        <?php
        include "../config/db.php";

        // Add helper to detect avatar column
function column_exists($conn, $table, $column) {
    $dbRes = mysqli_query($conn, "SELECT DATABASE() as db");
    $dbRow = $dbRes ? mysqli_fetch_assoc($dbRes) : null;
    $db = $dbRow['db'] ?? '';
    $sql = "SELECT COUNT(*) AS cnt FROM information_schema.columns WHERE table_schema = ? AND table_name = ? AND column_name = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("sss", $db, $table, $column);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();
        return intval($row['cnt'] ?? 0) > 0;
    }
    return false;
}

// Detect avatar column in userdata
$avatarCol = '';
foreach (['profile_image', 'avatar', 'profile_pic', 'picture', 'photo', 'img'] as $c) {
    if (column_exists($conn, 'userdata', $c)) { 
        $avatarCol = $c; 
        break; 
    }
}
$avatarSelect = $avatarCol ? "COALESCE(u.`{$avatarCol}`, '') AS profile_image," : "'' AS profile_image,";

        // Fetch only available services with provider info AND total vouches
        $query = "
            SELECT 
                s.id AS service_id,
                s.serviceTitle,
                s.startingPrice,
                s.serviceCategory,
                s.serviceDescription,
                s.serviceDuration,
                s.serviceImages,
                s.contactFacebook,
                s.contactEmail,
                s.contactChat,
                s.contactMeetup,
                s.posted_at,
                s.user_id,
                u.first_name,
                u.last_name,
                u.department,
                {$avatarSelect}
                (SELECT COUNT(*) FROM vouches WHERE seller_id = s.user_id) as seller_vouches,
                (SELECT AVG(rating) FROM ratings WHERE item_id = s.id AND item_type = 'service') as avg_rating,
                (SELECT COUNT(*) FROM ratings WHERE item_id = s.id AND item_type = 'service') as rating_count
            FROM service_offers s
            JOIN userdata u ON s.user_id = u.id
            WHERE s.status = 'available'
            ORDER BY s.posted_at DESC
            LIMIT 12
        ";

        $result = mysqli_query($conn, $query);

        if (!$result) {
            echo "<div style='color:red; text-align:center;'>❌ Database Error: " . htmlspecialchars(mysqli_error($conn)) . "</div>";
        } else {
            if (mysqli_num_rows($result) > 0):
                while ($srv = mysqli_fetch_assoc($result)):
                    $providerName = htmlspecialchars($srv['first_name'] . ' ' . $srv['last_name']);
                    $avgRating = $srv['avg_rating'] ? round($srv['avg_rating'], 1) : 0;
                    $ratingCount = $srv['rating_count'] ?: 0;
                    $categoryDisplay = ucwords(str_replace('_', ' ', $srv['serviceCategory']));
                    
                    // Format delivery time for display
                    $deliveryDisplay = str_replace('_', ' ', $srv['serviceDuration']);
                    $deliveryDisplay = ucwords($deliveryDisplay);
        ?>
                    <div class="service-card" data-service='<?= json_encode($srv) ?>'>
                        <div class="service-card-content">
                            <h3 class="service-card-title"><?= htmlspecialchars($srv['serviceTitle']) ?></h3>
                            <p class="service-card-provider">by <?= $providerName ?></p>
                            
                            <p class="service-card-description">
                                <?= htmlspecialchars(substr($srv['serviceDescription'], 0, 120)) . (strlen($srv['serviceDescription']) > 120 ? '...' : '') ?>
                            </p>
                            
                            <div class="service-card-footer">
                                <div class="service-card-price">
                                    <span class="service-price-label">Starting at</span>
                                    <span class="service-price-amount">₱<?= number_format($srv['startingPrice'], 0) ?>/hr</span>
                                </div>
                                
                                <div class="service-card-meta">
                                    <div class="service-card-rating">
                                        <span class="rating-star">★</span>
                                        <span class="rating-value"><?= $avgRating ?></span>
                                        <span class="rating-count">(<?= $ratingCount ?>)</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="service-card-badges">
                                <span class="service-badge service-badge-category"><?= $categoryDisplay ?></span>
                                <span class="service-badge service-badge-delivery"><?= $deliveryDisplay ?></span>
                            </div>
                            
                            <button class="btn-primary view-service-btn service-view-btn">View Details</button>
                        </div>
                    </div>
        <?php
                endwhile;
            else:
                echo "<div style='text-align:center; padding: 20px;'>⚠️ No available services in the database.</div>";
            endif;
        }

        mysqli_free_result($result);
        ?>
    </div>

    <div class="services-load-more-section">
        <button class="btn-secondary" id="loadMoreServices">Load More Services</button>
    </div>
</div>

<!-- Service Details Modal -->
<div id="serviceModal" class="modal service-modal">
    <div class="modal-content service-modal-content">
        <button class="service-modal-close" id="closeServiceModal">&times;</button>

        <div class="service-modal-body">
            <div class="service-portfolio-section">
                <div class="service-portfolio-header">
                    <h3>Previous Work</h3>
                    <p>Portfolio & Examples</p>
                </div>
                <div class="portfolio-gallery" id="portfolioGallery">
                    <!-- Portfolio items will be loaded here -->
                </div>
            </div>

            <div class="service-details-section">
                <div class="service-header">
                    <h2 id="serviceTitle">Service Name</h2>
                    <p class="service-listing-date" id="serviceDate">Listed 3 days ago</p>
                </div>

                <div class="service-price-section">
                    <div class="service-price-badge">
                        <span class="service-price" id="servicePrice">Starting at ₱9/hr</span>
                        <div class="service-delivery-time" id="serviceDeliveryTime">Delivery: 5 days to 1 week</div>
                        <div class="service-price-type" id="servicePriceType">Negotiable - Cash on meetup</div>
                    </div>
                </div>

                <div class="service-description">
                    <h4>Service Description</h4>
                    <p id="serviceDescription">Service description will be displayed here...</p>
                </div>

                <div class="service-provider-section">
                    <h4>Service Provider Information</h4>
                    <div class="service-provider-card">
                        <div class="service-provider-avatar">
                            <img id="serviceProviderAvatar" src="../assets/Images/profile-icon.png" alt="Provider" class="service-provider-img">
                        </div>
                        <div class="service-provider-details">
                            <div class="service-provider-name" id="serviceProviderName">John Doe</div>
                            <div class="service-provider-department" id="serviceProviderDepartment">CCS Department</div>
                            <div class="service-provider-rating">
                                <span class="service-rating-stars" id="serviceProviderRating">★★★★★</span>
                                <span class="service-rating-count" id="serviceRatingCount">(15 vouches)</span>
                            </div>
                        </div>
                    </div>

                    <div class="service-contact-availability">
                        <div class="service-availability-item">
                            <strong>Chat availability:</strong>
                            <span id="serviceChatAvailability">8:00 am - 9:00 pm</span>
                        </div>
                        <div class="service-availability-item">
                            <strong>Meetup:</strong>
                            <span id="serviceMeetupAvailability">Weekdays - Cash / Arlegui Campus</span>
                        </div>
                    </div>

                    <div class="service-contact-buttons">
                        <button class="service-btn-contact service-btn-email" id="serviceContactEmail">
                            <img src="../assets/Images/email-icon.svg" alt="Email" class="service-contact-icon">
                            Email
                        </button>
                        
                        <button class="service-btn-contact service-btn-message" id="messageProviderBtn" style="background: #007bff; color: white;">
                            <img src="../assets/Images/chat-icon.svg" alt="Chat" class="service-contact-icon">
                            Message Provider
                        </button>
                        
                        <button class="service-btn-contact service-btn-vouch" id="serviceVouchBtn" style="background: #28a745; color: white;">
                            👍 Vouch Provider
                        </button>
                        <button class="service-btn-contact service-btn-rate" id="serviceRateBtn" style="background: #ffc107; color: #333;">
                            ⭐ Rate Service
                        </button>
                        <button class="service-btn-contact service-btn-report" id="reportServiceBtn" style="background: #dc3545; color: white;">
                            🚩 Report Service
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ADD: Rating Modal (reuse same design) -->
<div id="serviceRatingModal" class="modal gmail-modal" style="display:none;">
  <div class="gmail-modal-content" style="max-width: 450px;">
    <span class="close" id="closeServiceRatingModal">&times;</span>
    <h2>Rate this Service</h2>
    <div style="text-align: center; margin: 20px 0;">
        <div id="serviceStarRating" style="font-size: 2.5rem; cursor: pointer;">
            <span class="star" data-rating="1">☆</span>
            <span class="star" data-rating="2">☆</span>
            <span class="star" data-rating="3">☆</span>
            <span class="star" data-rating="4">☆</span>
            <span class="star" data-rating="5">☆</span>
        </div>
        <p id="serviceRatingText" style="margin-top: 10px; color: #666;">Select a rating</p>
    </div>
    <textarea id="serviceRatingReview" placeholder="Write a review (optional)" style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ddd; margin-bottom: 15px;" rows="3"></textarea>
    <button id="submitServiceRatingBtn" class="btn-primary" style="width: 100%;">Submit Rating</button>
  </div>
</div>

<!-- ADD: Email Modal (matches IDs used by the JS) -->
<div id="emailModal" class="modal gmail-modal" style="display:none;">
  <div class="gmail-modal-content">
    <span class="close" id="closeEmailModal">&times;</span>
    <h2>Provider Email</h2>
    <div class="gmail-display">
      <span id="providerEmailSpan">example@tip.edu.ph</span>
      <button id="copyEmailBtn" class="btn-copy">
        <i class="fa-regular fa-copy"></i> Copy
      </button>
    </div>
  </div>
</div>

<!-- ADD: Report Modal for Services -->
<div id="serviceReportModal" class="modal gmail-modal" style="display:none;">
  <div class="gmail-modal-content" style="max-width: 500px;">
    <span class="close" id="closeServiceReportModal">&times;</span>
    <h2>Report Service</h2>
    <div style="text-align: left; margin: 20px 0;">
        <label style="display: block; margin-bottom: 10px; font-weight: 600;">Reason for Report *</label>
        <select id="serviceReportReason" style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ddd; margin-bottom: 15px;">
            <option value="">Select a reason</option>
            <option value="scam">Suspected Scam/Fraud</option>
            <option value="fake">Fake Credentials/Portfolio</option>
            <option value="inappropriate">Inappropriate Content</option>
            <option value="spam">Spam/Misleading</option>
            <option value="overpriced">Extremely Overpriced</option>
            <option value="undelivered">Service Not Delivered</option>
            <option value="other">Other</option>
        </select>
        
        <label style="display: block; margin-bottom: 10px; font-weight: 600;">Additional Details</label>
        <textarea id="serviceReportDescription" placeholder="Please provide more details about your report..." style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ddd; margin-bottom: 15px; min-height: 100px;"></textarea>
    </div>
    <button id="submitServiceReportBtn" class="btn-primary" style="width: 100%; background: #dc3545;">Submit Report</button>
  </div>
</div>

<!-- ADD: minimal Gmail-modal CSS (keeps look consistent with marketplace) -->
<style>
/* gmail modal for services */
.gmail-modal {
  display: none;
  position: fixed;
  z-index: 9999;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  backdrop-filter: blur(4px);
  background-color: rgba(0,0,0,0.5);
  justify-content: center;
  align-items: center;
}
.gmail-modal .gmail-modal-content {
  background: #fff;
  padding: 18px 20px;
  border-radius: 10px;
  width: 92%;
  max-width: 380px;
  text-align: center;
  box-shadow: 0 8px 20px rgba(0,0,0,0.15);
  position: relative;
}
.gmail-display { display:flex; justify-content:space-between; gap:10px; background:#f7f7f7; padding:10px 14px; border-radius:8px; word-break:break-all; }
.btn-copy { background:#007bff; color:#fff; border:none; padding:6px 12px; border-radius:6px; cursor:pointer; }
.gmail-modal .close { position:absolute; top:8px; right:12px; cursor:pointer; font-size:1.4rem; color:#555; }
</style>

<!-- CSS -->
<style>
/* Service Cards Redesign */
.services-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 24px;
    padding: 20px 0;
}

.service-card {
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    overflow: hidden;
    border: 1px solid #e8e8e8;
    position: relative;
}

.service-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
}

.service-card-content {
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.service-card-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1a1a1a;
    margin: 0;
    line-height: 1.4;
    min-height: 50px;
}

.service-card-provider {
    font-size: 0.875rem;
    color: #666;
    margin: 0;
}

.service-card-description {
    font-size: 0.9rem;
    color: #555;
    line-height: 1.5;
    margin: 8px 0;
    min-height: 60px;
}

.service-card-footer {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    margin-top: auto;
    padding-top: 12px;
    border-top: 1px solid #f0f0f0;
}

.service-card-price {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.service-price-label {
    font-size: 0.75rem;
    color: #888;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.service-price-amount {
    font-size: 1.25rem;
    font-weight: 700;
    color: #007bff;
}

.service-card-meta {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 4px;
}

.service-card-rating {
    display: flex;
    align-items: center;
    gap: 4px;
}

.rating-star {
    color: #ffc107;
    font-size: 1.1rem;
}

.rating-value {
    font-weight: 600;
    color: #1a1a1a;
    font-size: 0.95rem;
}

.rating-count {
    color: #888;
    font-size: 0.85rem;
}

.service-card-badges {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-top: 8px;
}

.service-badge {
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 500;
    text-transform: capitalize;
}

.service-badge-category {
    background-color: #e3f2fd;
    color: #1976d2;
}

.service-badge-delivery {
    background-color: #f3e5f5;
    color: #7b1fa2;
}

.service-view-btn {
    width: 100%;
    margin-top: 16px;
    padding: 10px;
    border-radius: 8px;
    font-weight: 500;
}

@media screen and (max-width: 768px) {
    .services-grid {
        grid-template-columns: 1fr;
        gap: 16px;
    }
}
</style>

<!-- JS -->
<script>
// Add helper: safe JSON parsing for fetch responses (prevents "Unexpected token '<'")
async function safeJsonResponse(response) {
    const text = await response.text();
    try {
        // Try parse JSON
        const json = JSON.parse(text);
        // Include HTTP status to allow caller to check response.ok if needed
        json.__http_ok = response.ok;
        return json;
    } catch (err) {
        // Not JSON (probably HTML like login redirect) — return a standardized error object
        return {
            status: 'error',
            message: text ? text.substring(0, 1000) : 'Invalid server response',
            __http_ok: response.ok
        };
    }
}

// Utility to parse the card's data-service safely and normalize id
function parseServiceFromCard(card) {
    try {
        const raw = card.getAttribute('data-service') || '{}';
        // Some serialized strings use &apos; to avoid quote issues; handle that
        const payload = raw.replace(/&apos;/g, "'");
        const svc = JSON.parse(payload);
        // Normalize id fields so code can reliably use service.id
        if (!svc.id) {
            svc.id = svc.service_id || svc.id || svc.serviceId || svc.item_id || null;
        }
        return svc;
    } catch (err) {
        return null;
    }
}

// ✅ OPTIMIZED: Cache DOM elements and use event delegation
const serviceModal = document.getElementById('serviceModal');
const closeServiceModal = document.getElementById('closeServiceModal');
const serviceEmailBtn = document.getElementById('serviceContactEmail');
const emailModal = document.getElementById('emailModal');
const closeEmailModal = document.getElementById('closeEmailModal');
const providerEmailSpan = document.getElementById('providerEmailSpan');
const copyEmailBtn = document.getElementById('copyEmailBtn');
const serviceRatingModal = document.getElementById('serviceRatingModal');
const closeServiceRatingModal = document.getElementById('closeServiceRatingModal');
const serviceRateBtn = document.getElementById('serviceRateBtn');
const serviceVouchBtn = document.getElementById('serviceVouchBtn');
const serviceStarRating = document.getElementById('serviceStarRating');
const serviceRatingText = document.getElementById('serviceRatingText');
const submitServiceRatingBtn = document.getElementById('submitServiceRatingBtn');
const reportServiceBtn = document.getElementById('reportServiceBtn');
const serviceReportModal = document.getElementById('serviceReportModal');
const closeServiceReportModal = document.getElementById('closeServiceReportModal');
const submitServiceReportBtn = document.getElementById('submitServiceReportBtn');

let currentService = null;
let serviceSelectedRating = 0;

// ✅ FIX: Use event delegation for better performance - SINGLE LISTENER
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('view-service-btn') || e.target.closest('.view-service-btn')) {
        e.preventDefault();
        e.stopPropagation();
        
        const btn = e.target.classList.contains('view-service-btn') ? e.target : e.target.closest('.view-service-btn');
        const card = btn.closest('.service-card');
        const service = parseServiceFromCard(card);
        if (!service) return;
        currentService = service;

        // ✅ Use requestAnimationFrame for smooth rendering
        requestAnimationFrame(() => {
            // Batch all DOM updates together
            document.getElementById('serviceTitle').textContent = service.serviceTitle;
            document.getElementById('servicePrice').textContent = 'Starting at ₱' + parseFloat(service.startingPrice || 0).toFixed(2);
            document.getElementById('serviceDescription').textContent = service.serviceDescription || 'No description.';
            document.getElementById('serviceDate').textContent = 'Listed on ' + new Date(service.posted_at).toLocaleDateString();
            document.getElementById('serviceProviderName').textContent = service.first_name + ' ' + service.last_name;
            document.getElementById('serviceProviderDepartment').textContent = service.department || 'N/A';
            document.getElementById('serviceChatAvailability').textContent = service.contactChat || 'Not available';
            document.getElementById('serviceMeetupAvailability').textContent = service.contactMeetup || 'Not available';
            document.getElementById('serviceDeliveryTime').textContent = 'Delivery: ' + (service.serviceDuration || 'Not specified');
            document.getElementById('serviceRatingCount').textContent = `(${service.seller_vouches || 0} vouches)`;

            // Set provider avatar
            const avatarEl = document.getElementById('serviceProviderAvatar');
            if (avatarEl) {
                let imgSrc = service.profile_image || '';
                imgSrc = imgSrc.trim();
                if (imgSrc && !/^https?:\/\//i.test(imgSrc)) {
                    // Relative path - prefix with ../
                    if (!imgSrc.startsWith('/') && !imgSrc.startsWith('../')) {
                        imgSrc = '../' + imgSrc;
                    }
                }
                avatarEl.src = imgSrc || '../assets/Images/profile-icon.png';
            }

            // ✅ Portfolio gallery - use DocumentFragment for batch DOM insertion
            const gallery = document.getElementById('portfolioGallery');
            gallery.innerHTML = '';
            
            if (service.serviceImages) {
                const fragment = document.createDocumentFragment();
                const images = service.serviceImages.split(',');
                
                images.forEach(img => {
                    const imgPath = img.trim();
                    if (imgPath) {
                        const imgEl = document.createElement('img');
                        imgEl.src = imgPath.startsWith('http') || imgPath !== '' ? imgPath : '../assets/Images/placeholder.png';
                        imgEl.alt = service.serviceTitle;
                        imgEl.style.cssText = 'width: 100%; height: 200px; object-fit: contain; border-radius: 8px; background: white; padding: 10px;';
                        imgEl.loading = 'lazy'; // ✅ Lazy load images
                        fragment.appendChild(imgEl);
                    }
                });
                
                gallery.appendChild(fragment);
            } else {
                gallery.innerHTML = '<p style="text-align: center; color: #666;">No portfolio images available</p>';
            }

            // Set provider avatar
            const providerAvatar = document.getElementById('serviceProviderAvatar');
            providerAvatar.src = service.profile_image || '../assets/Images/profile-icon.png';

            // ✅ Show modal with proper display and animation
            serviceModal.style.display = 'flex';
            // Force reflow before adding animation class
            void serviceModal.offsetWidth;
            serviceModal.classList.add('modal-show');
        });
    }
});

// ✅ Close modal with fade-out animation
if (closeServiceModal) {
    closeServiceModal.addEventListener('click', () => {
        serviceModal.classList.remove('modal-show');
        setTimeout(() => {
            serviceModal.style.display = 'none';
        }, 300); // Match CSS transition duration
    });
}

// Vouch functionality
if (serviceVouchBtn) {
    serviceVouchBtn.addEventListener('click', async () => {
        if (!currentService) return;
        
        const formData = new FormData();
        formData.append('item_type', 'service');
        // Use normalized id
        formData.append('item_id', currentService.id || currentService.service_id || 0);
        formData.append('seller_id', currentService.user_id || currentService.userId || 0);
        
        try {
            const response = await fetch('handle_vouch.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });
            const result = await safeJsonResponse(response);
            alert(result.message || 'No message from server');

            if (result.status === 'success' && typeof result.total_vouches !== 'undefined') {
                document.getElementById('serviceRatingCount').textContent = `(${result.total_vouches} vouches)`;
                // keep in-memory sync
                currentService.seller_vouches = result.total_vouches;
            }
        } catch (error) {
            alert('Error: ' + (error.message || error));
        }
    });
}

// Rating modal
if (serviceRateBtn) {
    serviceRateBtn.addEventListener('click', () => {
        if (!currentService) return;
        serviceSelectedRating = 0;
        document.getElementById('serviceRatingReview').value = '';
        updateServiceStars(0);
        serviceRatingModal.style.display = 'flex';
    });
}

if (closeServiceRatingModal) {
    closeServiceRatingModal.addEventListener('click', () => {
        serviceRatingModal.style.display = 'none';
    });
}

// Star rating
if (serviceStarRating) {
    document.querySelectorAll('#serviceStarRating .star').forEach(star => {
        star.addEventListener('click', function() {
            serviceSelectedRating = parseInt(this.dataset.rating);
            updateServiceStars(serviceSelectedRating);
        });
        
        star.addEventListener('mouseenter', function() {
            const hoverRating = parseInt(this.dataset.rating);
            updateServiceStars(hoverRating, true);
        });
    });

    serviceStarRating.addEventListener('mouseleave', () => {
        updateServiceStars(serviceSelectedRating);
    });
}

function updateServiceStars(rating, isHover = false) {
    document.querySelectorAll('#serviceStarRating .star').forEach((star, index) => {
        star.textContent = index < rating ? '★' : '☆';
        star.style.color = index < rating ? '#ffc107' : '#ddd';
    });
    
    const texts = ['', 'Poor', 'Fair', 'Good', 'Very Good', 'Excellent'];
    if (serviceRatingText) {
        serviceRatingText.textContent = rating > 0 ? texts[rating] : 'Select a rating';
    }
}

if (submitServiceRatingBtn) {
    submitServiceRatingBtn.addEventListener('click', async () => {
        if (serviceSelectedRating === 0) {
            alert('Please select a rating');
            return;
        }
        
        const formData = new FormData();
        formData.append('item_type', 'service');
        formData.append('item_id', currentService.id || currentService.service_id || 0);
        formData.append('rating', serviceSelectedRating);
        formData.append('review', document.getElementById('serviceRatingReview').value);
        
        try {
            const response = await fetch('handle_rating.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });
            const result = await safeJsonResponse(response);
            alert(result.message || 'No message from server');
            
            if (result.status === 'success') {
                // Hide modal
                serviceRatingModal.style.display = 'none';

                // Update in-memory values and UI
                if (typeof result.avg_rating !== 'undefined') currentService.avg_rating = result.avg_rating;
                if (typeof result.rating_count !== 'undefined') currentService.rating_count = result.rating_count;

                const avg = currentService.avg_rating ? parseFloat(currentService.avg_rating).toFixed(1) : '0.0';
                const ratingCount = currentService.rating_count || 0;
                const providerRatingEl = document.getElementById('serviceProviderRating');
                const providerRatingCountEl = document.getElementById('serviceRatingCount');
                if (providerRatingEl) providerRatingEl.textContent = `${avg} ★`;
                if (providerRatingCountEl) providerRatingCountEl.textContent = `(${ratingCount} ratings)`;

                // Update the service card in the grid (if present)
                document.querySelectorAll('.service-card').forEach(card => {
                    try {
                        const svc = parseServiceFromCard(card);
                        if (!svc) return;
                        if (String(svc.id || svc.service_id) === String(currentService.id)) {
                            const ratingValueEl = card.querySelector('.rating-value');
                            const ratingCountEl = card.querySelector('.rating-count');
                            if (ratingValueEl) ratingValueEl.textContent = avg;
                            if (ratingCountEl) ratingCountEl.textContent = `(${ratingCount})`;
                            // update serialized data-service
                            svc.avg_rating = currentService.avg_rating;
                            svc.rating_count = currentService.rating_count;
                            card.setAttribute('data-service', JSON.stringify(svc));
                        }
                    } catch (err) {
                        // ignore parse errors
                    }
                });
            }
        } catch (error) {
            alert('Error: ' + (error.message || error));
        }
    });
}

// Email modal
if (serviceEmailBtn) {
    serviceEmailBtn.addEventListener('click', () => {
        if (currentService && currentService.contactEmail) {
            providerEmailSpan.textContent = currentService.contactEmail;
        } else {
            providerEmailSpan.textContent = 'No email available';
        }
        emailModal.style.display = 'flex';
    });
}

if (closeEmailModal) {
    closeEmailModal.addEventListener('click', () => {
        emailModal.style.display = 'none';
    });
}

if (copyEmailBtn) {
    copyEmailBtn.addEventListener('click', () => {
        const email = providerEmailSpan.textContent;
        if (email) {
            navigator.clipboard.writeText(email);
            copyEmailBtn.textContent = 'Copied!';
            setTimeout(() => {
                copyEmailBtn.textContent = 'Copy';
            }, 2000);
        }
    });
}

// ADD: Message Provider functionality
const messageProviderBtn = document.getElementById('messageProviderBtn');

if (messageProviderBtn) {
    messageProviderBtn.addEventListener('click', async () => {
        if (!currentService) return;
        
        // Don't allow messaging yourself
        const currentUserId = <?= $user_id ?>;
        if (currentService.user_id == currentUserId) {
            alert('You cannot message yourself!');
            return;
        }
        
        // Create or get chat room
        const formData = new FormData();
        formData.append('action', 'create_chat');
        formData.append('seller_id', currentService.user_id);
        formData.append('item_id', currentService.id || currentService.service_id);
        formData.append('item_type', 'service');
        
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

// ADD: Service Report functionality
if (reportServiceBtn) {
    reportServiceBtn.addEventListener('click', () => {
        if (!currentService) return;
        document.getElementById('serviceReportReason').value = '';
        document.getElementById('serviceReportDescription').value = '';
        serviceReportModal.style.display = 'flex';
    });
}

if (closeServiceReportModal) {
    closeServiceReportModal.addEventListener('click', () => {
        serviceReportModal.style.display = 'none';
    });
}

if (submitServiceReportBtn) {
    submitServiceReportBtn.addEventListener('click', async () => {
        const reason = document.getElementById('serviceReportReason').value;
        const description = document.getElementById('serviceReportDescription').value;
        
        if (!reason) {
            alert('Please select a reason for reporting');
            return;
        }
        
        const formData = new FormData();
        formData.append('item_id', currentService.id || currentService.service_id);
        formData.append('item_type', 'service');
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
                serviceReportModal.style.display = 'none';
            }
        } catch (error) {
            alert('Error: ' + error.message);
        }
    });
}

// Close modals on outside click
window.addEventListener('click', e => {
    if (e.target === serviceModal) {
        serviceModal.classList.remove('modal-show');
        setTimeout(() => {
            serviceModal.style.display = 'none';
        }, 300);
    }
    if (e.target === emailModal) emailModal.style.display = 'none';
    if (e.target === serviceRatingModal) serviceRatingModal.style.display = 'none';
    if (e.target === serviceReportModal) serviceReportModal.style.display = 'none';
});
</script>



    <!-- Create Post Modal -->
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
    uploadArea.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        imageInput.click();
    });

    uploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        e.stopPropagation();
        uploadArea.classList.add('dragover');
    });

    uploadArea.addEventListener('dragleave', (e) => {
        e.preventDefault();
        e.stopPropagation();
        uploadArea.classList.remove('dragover');
    });

    uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        e.stopPropagation();
        uploadArea.classList.remove('dragover');
        
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
  
  uploadArea.innerHTML = `
      <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; width: 100%;">
          <div class="upload-preview-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); gap: 8px; padding: 10px; max-width: fit-content;"></div>
          <p style="text-align: center; margin-top: 10px; color: #666; font-size: 0.9rem; cursor: pointer;">
              ${files.length} image(s) selected - Click to change
          </p>
      </div>
  `;
  
  const previewGrid = uploadArea.querySelector('.upload-preview-grid');
  
  Array.from(files).forEach(file => {
    if (file.type.startsWith('image/')) {
      const reader = new FileReader();
      reader.onload = e => {
        const imgWrapper = document.createElement('div');
        imgWrapper.style.cssText = 'position: relative; width: 80px; height: 80px; overflow: hidden; border-radius: 8px; border: 2px solid #ddd; cursor: pointer;';
        
        const img = document.createElement('img');
        img.src = e.target.result;
        img.style.cssText = 'width: 100%; height: 100%; object-fit: cover;';
        
        imgWrapper.appendChild(img);
        previewGrid.appendChild(imgWrapper);
      };
      reader.readAsDataURL(file);
    }
  });
}

// ====== AJAX Submission for Marketplace ======
document.getElementById('marketplaceForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);

    try {
        const res = await fetch('itemoffer.php', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });
        const data = await safeJsonResponse(res);
        alert(data.message || 'No message from server');
        if (data.status === 'success') {
            form.reset();
            document.getElementById('createPostModal').style.display = 'none';
        }
    } catch (err) {
        alert('❌ Something went wrong.');
        console.error(err);
    }
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
    // ✅ FIX: Make clickable
    serviceUploadArea.addEventListener("click", function(e) {
        e.preventDefault();
        e.stopPropagation();
        serviceImageInput.click();
    });

    serviceUploadArea.addEventListener("dragover", function(e) {
        e.preventDefault();
        e.stopPropagation();
        serviceUploadArea.style.borderColor = '#ffc107';
        serviceUploadArea.style.backgroundColor = '#fff9e6';
    });

    serviceUploadArea.addEventListener("dragleave", function(e) {
        e.preventDefault();
        e.stopPropagation();
        serviceUploadArea.style.borderColor = '#ddd';
        serviceUploadArea.style.backgroundColor = '#f8f9fa';
    });

    serviceUploadArea.addEventListener("drop", function(e) {
        e.preventDefault();
        e.stopPropagation();
        serviceUploadArea.style.borderColor = '#ddd';
        serviceUploadArea.style.backgroundColor = '#f8f9fa';
        
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
  
  serviceUploadArea.innerHTML = '';
  
  const previewContainer = document.createElement('div');
  previewContainer.style.cssText = 'display: flex; flex-direction: column; align-items: center; justify-content: center; width: 100%;';
  
  const previewGrid = document.createElement('div');
  previewGrid.style.cssText = 'display: grid; grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); gap: 8px; padding: 10px; max-width: fit-content;';
  
  Array.from(files).forEach(file => {
    if (file.type.startsWith("image/")) {
      const reader = new FileReader();
      reader.onload = function(e) {
        const imgWrapper = document.createElement('div');
        imgWrapper.style.cssText = 'position: relative; width: 80px; height: 80px; overflow: hidden; border-radius: 8px; border: 2px solid #ddd;';
        
        const img = document.createElement("img");
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
  serviceUploadArea.appendChild(previewContainer);
  
  serviceUploadArea.onclick = function(e) {
    e.preventDefault();
    serviceImageInput.click();
  };
}

// ====== Service Form AJAX Submission ======
document.getElementById("serviceForm").addEventListener("submit", async function(e) {
    e.preventDefault();
    const formData = new FormData(this);

    try {
        const res = await fetch("serviceOffer.php", {
            method: "POST",
            body: formData,
            credentials: 'same-origin'
        });
        const result = await safeJsonResponse(res);
        alert(result.message || 'No message from server');

        if (result.status === "success") {
            this.reset();
            document.getElementById("serviceImagePreview")?.innerHTML = "";
            document.getElementById("portfolioItems")?.innerHTML = "";
            document.getElementById("createPostModal").style.display = "none";
        }
    } catch (err) {
        alert('Error: ' + (err.message || err));
    }
});
</script>

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
    <script src="../assets/create-post.js"></script>
    <script src="../assets//notifications.js"></script>
    
    <!-- Dynamic Services Filtering -->
    <script>
    (function() {
        const sortBy = document.getElementById('servicesSortBy');
        const category = document.getElementById('servicesCategory');
        const priceRange = document.getElementById('servicesPriceRange');
        const deliveryTime = document.getElementById('servicesDeliveryTime');
        const servicesGrid = document.getElementById('servicesGrid');
        const searchInput = document.getElementById('globalSearch');
        const searchBtn = document.getElementById('searchBtn');

        function loadServices() {
            const filters = {
                sort: sortBy.value,
                category: category.value,
                price: priceRange.value,
                delivery: deliveryTime.value,
                search: searchInput.value.trim()
            };

            const params = new URLSearchParams(filters);
            
            fetch(`fetch_services.php?${params.toString()}`)
                .then(res => res.json())
                .then data => {
                    if (data.status === 'success') {
                        renderServices(data.services);
                    } else {
                        servicesGrid.innerHTML = `<div style='text-align:center; padding: 20px; color: #666;'>${data.message}</div>`;
                    }
                })
                .catch(err => {
                    console.error('Error loading services:', err);
                    servicesGrid.innerHTML = '<div style="text-align:center; padding: 20px; color: red;">❌ Failed to load services</div>';
                });
        }

        function renderServices(services) {
            if (services.length === 0) {
                servicesGrid.innerHTML = '<div style="text-align:center; padding: 20px;">⚠️ No services found matching your filters.</div>';
                return;
            }

            servicesGrid.innerHTML = services.map(srv => {
                const avgRating = srv.avg_rating ? parseFloat(srv.avg_rating).toFixed(1) : '0.0';
                const ratingCount = srv.rating_count || 0;
                const categoryDisplay = srv.serviceCategory.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                const deliveryDisplay = srv.serviceDuration.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                const description = srv.serviceDescription.length > 120 
                    ? srv.serviceDescription.substring(0, 120) + '...' 
                    : srv.serviceDescription;
                
                return `
                    <div class="service-card" data-service='${JSON.stringify(srv).replace(/'/g, "&apos;")}'>
                        <div class="service-card-content">
                            <h3 class="service-card-title">${escapeHtml(srv.serviceTitle)}</h3>
                            <p class="service-card-provider">by ${escapeHtml(srv.first_name + ' ' + srv.last_name)}</p>
                            
                            <p class="service-card-description">${escapeHtml(description)}</p>
                            
                            <div class="service-card-footer">
                                <div class="service-card-price">
                                    <span class="service-price-label">Starting at</span>
                                    <span class="service-price-amount">₱${parseFloat(srv.startingPrice).toFixed(0)}/hr</span>
                                </div>
                                
                                <div class="service-card-meta">
                                    <div class="service-card-rating">
                                        <span class="rating-star">★</span>
                                        <span class="rating-value">${avgRating}</span>
                                        <span class="rating-count">(${ratingCount})</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="service-card-badges">
                                <span class="service-badge service-badge-category">${categoryDisplay}</span>
                                <span class="service-badge service-badge-delivery">${deliveryDisplay}</span>
                            </div>
                            
                            <button class="btn-primary view-service-btn service-view-btn">View Details</button>
                        </div>
                    </div>
                `;
            }).join('');

            // Reattach event listeners for new cards
            attachServiceCardListeners();
        }

        function attachServiceCardListeners() {
            document.querySelectorAll('.view-service-btn').forEach(btn => {
                btn.addEventListener('click', e => {
                    const card = e.target.closest('.service-card');
                    const service = parseServiceFromCard(card);
                    if (!service) return;
                    openServiceModal(service);
                });
            });
        }

        function openServiceModal(service) {
            // ...existing code...
            currentService = service;
            document.getElementById('serviceTitle').innerText = service.serviceTitle;
            document.getElementById('servicePrice').innerText = '₱' + parseFloat(service.startingPrice || 0).toFixed(2);
            document.getElementById('serviceDescription').innerText = service.serviceDescription || 'No description.';
            document.getElementById('serviceDate').innerText = 'Listed on ' + new Date(service.posted_at).toLocaleDateString();
            document.getElementById('serviceProviderName').innerText = service.first_name + ' ' + service.last_name;
            document.getElementById('serviceProviderDepartment').innerText = service.department || 'N/A';
            document.getElementById('serviceChatAvailability').innerText = service.contactChat || 'Not available';
            document.getElementById('serviceMeetupAvailability').innerText = service.contactMeetup || 'Not available';
            document.getElementById('serviceDeliveryTime').innerText = 'Delivery: ' + (service.serviceDuration || 'Not specified');
            document.getElementById('serviceRatingCount').innerText = `(${service.seller_vouches || 0} vouches)`;

            // Set provider avatar
            const avatarEl = document.getElementById('serviceProviderAvatar');
            if (avatarEl) {
                let imgSrc = service.profile_image || '';
                imgSrc = imgSrc.trim();
                if (imgSrc && !/^https?:\/\//i.test(imgSrc)) {
                    // Relative path - prefix with ../
                    if (!imgSrc.startsWith('/') && !imgSrc.startsWith('../')) {
                        imgSrc = '../' + imgSrc;
                    }
                }
               
                avatarEl.src = imgSrc || '../assets/Images/profile-icon.png';
            }

            // ✅ Portfolio gallery - use DocumentFragment for batch DOM insertion
            const gallery = document.getElementById('portfolioGallery');
            gallery.innerHTML = '';
            
            if (service.serviceImages) {
                const fragment = document.createDocumentFragment();
                const images = service.serviceImages.split(',');
                
                images.forEach(img => {
                    const imgPath = img.trim();
                    if (imgPath) {
                        const imgEl = document.createElement('img');
                        imgEl.src = imgPath.startsWith('http') || imgPath !== '' ? imgPath : '../assets/Images/placeholder.png';
                        imgEl.alt = service.serviceTitle;
                        imgEl.style.cssText = 'width: 100%; height: 200px; object-fit: contain; border-radius: 8px; background: white; padding: 10px;';
                        imgEl.loading = 'lazy'; // ✅ Lazy load images
                        fragment.appendChild(imgEl);
                    }
                });
                
                gallery.appendChild(fragment);
            } else {
                gallery.innerHTML = '<p style="text-align: center; color: #666;">No portfolio images available</p>';
            }

            // Set provider avatar
            const providerAvatar = document.getElementById('serviceProviderAvatar');
            providerAvatar.src = service.profile_image || '../assets/Images/profile-icon.png';

            // ✅ Show modal with proper display and animation
            serviceModal.style.display = 'flex';
            // Force reflow before adding animation class
            void serviceModal.offsetWidth;
            serviceModal.classList.add('modal-show');
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Attach filter listeners
        sortBy.addEventListener('change', loadServices);
        category.addEventListener('change', loadServices);
        priceRange.addEventListener('change', loadServices);
        deliveryTime.addEventListener('change', loadServices);
        searchBtn.addEventListener('click', loadServices);
        searchInput.addEventListener('keypress', e => {
            if (e.key === 'Enter') loadServices();
        });

        // Initial load is handled by PHP, but you can call loadServices() if you want dynamic initial load
    })();
    </script>
</body>

</html>