<?php
// ✅ Use role-specific session name
session_name('ADMIN_SESSION');
session_start();
require '../config/db.php';

// ✅ Check if admin is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
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

$message = "";
$toastColor = "";

// ✅ Get admin info from session
$adminId = $_SESSION['user_id'];
$adminName = $_SESSION['username'];

// ✅ Handle post actions FIRST (before any other queries)
if (isset($_GET['action'], $_GET['id'], $_GET['type'])) {
    $action = $_GET['action'];
    $id = intval($_GET['id']);
    $type = $_GET['type'];

    /* =========================
       ✅ PRODUCT APPROVAL SECTION
       ========================= */
    if ($type === 'product') {
        if ($action === 'approve') {
            $conn->begin_transaction();

            try {
                // Get product details before moving
                $productQuery = $conn->prepare("
                    SELECT p.*, u.username, u.tip_email 
                    FROM pending_post p 
                    JOIN userdata u ON p.user_id = u.id 
                    WHERE p.id = ?
                ");
                $productQuery->bind_param("i", $id);
                $productQuery->execute();
                $productData = $productQuery->get_result()->fetch_assoc();

                // Copy from pending_post → marketplace_items (include user_id)
                $insertSQL = "
                    INSERT INTO marketplace_items
                    (user_id, productName, productPrice, productCategory, productCondition, productDescription, productImg, sellerBio, sellerFacebook, sellerEmail, sellerChat, sellerMeetup, posted_at)
                    SELECT user_id, productName, productPrice, productCategory, productCondition, productDescription, productImg, sellerBio, sellerFacebook, sellerEmail, sellerChat, sellerMeetup, submitted_at
                    FROM pending_post
                    WHERE id = ?
                ";
                $stmt = $conn->prepare($insertSQL);
                $stmt->bind_param("i", $id);
                $stmt->execute();

                // Move image if exists
                $imgQuery = $conn->prepare("SELECT productImg FROM pending_post WHERE id = ?");
                $imgQuery->bind_param("i", $id);
                $imgQuery->execute();
                $imgResult = $imgQuery->get_result();
                $imgRow = $imgResult->fetch_assoc();
                $imgPath = $imgRow['productImg'] ?? null;

                if ($imgPath && file_exists($imgPath)) {
                    $fileName = basename($imgPath);
                    $newDir = "../uploads/marketplace/";
                    $newPath = $newDir . $fileName;
                    if (!is_dir($newDir)) mkdir($newDir, 0777, true);

                    if (rename($imgPath, $newPath)) {
                        $update = $conn->prepare("UPDATE marketplace_items SET productImg = ? WHERE item_id = LAST_INSERT_ID()");
                        $update->bind_param("s", $newPath);
                        $update->execute();
                    }
                }

                // Delete from pending_post
                $delete = $conn->prepare("DELETE FROM pending_post WHERE id = ?");
                $delete->bind_param("i", $id);
                $delete->execute();

                // ✅ Log approval activity - FIXED parameter count
                $logQuery = $conn->prepare("
                    INSERT INTO admin_activity 
                    (admin_id, admin_name, action_type, target_type, target_id, target_name, target_email, reason, details) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $actionType = 'approve_product';
                $targetType = 'marketplace';
                $targetName = $productData['productName'];
                $targetEmail = $productData['tip_email'];
                $reason = 'Approved';
                $details = "Product: {$productData['productName']}, Price: ₱{$productData['productPrice']}, Category: {$productData['productCategory']}, Status: Approved";
                
                $logQuery->bind_param("isssissss", $adminId, $adminName, $actionType, $targetType, $id, $targetName, $targetEmail, $reason, $details);
                $logQuery->execute();
                
                // ✅ Send notification to user
                $notifTitle = "Post Approved ✅";
                $notifMessage = "Your marketplace item '{$productData['productName']}' has been approved and is now live!";
                $notifQuery = $conn->prepare("INSERT INTO notifications (user_id, type, title, message, related_id, related_type) VALUES (?, 'post_approved', ?, ?, ?, 'marketplace')");
                $notifQuery->bind_param("issi", $productData['user_id'], $notifTitle, $notifMessage, $id);
                $notifQuery->execute();

                $conn->commit();
                $message = "✅ Product approved and moved to marketplace!";
                $toastColor = "green";
            } catch (Exception $e) {
                $conn->rollback();
                $message = "❌ Error: " . htmlspecialchars($e->getMessage());
                $toastColor = "red";
            }
        } elseif ($action === 'reject') {
            $conn->begin_transaction();
            
            try {
                // Get product details for logging
                $productQuery = $conn->prepare("
                    SELECT p.*, u.username, u.tip_email 
                    FROM pending_post p 
                    JOIN userdata u ON p.user_id = u.id 
                    WHERE p.id = ?
                ");
                $productQuery->bind_param("i", $id);
                $productQuery->execute();
                $productData = $productQuery->get_result()->fetch_assoc();
                
                // ✅ DELETE rejected product instead of updating status
                $stmt = $conn->prepare("DELETE FROM pending_post WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                
                // ✅ Log rejection activity - FIXED parameter count
                $logQuery = $conn->prepare("
                    INSERT INTO admin_activity 
                    (admin_id, admin_name, action_type, target_type, target_id, target_name, target_email, reason, details) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $actionType = 'reject_product';
                $targetType = 'marketplace';
                $targetName = $productData['productName'];
                $targetEmail = $productData['tip_email'];
                $reason = 'Rejected';
                $details = "Product: {$productData['productName']}, Price: ₱{$productData['productPrice']}, Category: {$productData['productCategory']}, Status: Rejected and Removed";
                
                $logQuery->bind_param("isssissss", $adminId, $adminName, $actionType, $targetType, $id, $targetName, $targetEmail, $reason, $details);
                $logQuery->execute();
                
                // ✅ Send notification to user
                $notifTitle = "Post Rejected ❌";
                $notifMessage = "Your marketplace item '{$productData['productName']}' was rejected and removed. Please review our guidelines before resubmitting.";
                $notifQuery = $conn->prepare("INSERT INTO notifications (user_id, type, title, message, related_id, related_type) VALUES (?, 'post_rejected', ?, ?, ?, 'marketplace')");
                $notifQuery->bind_param("issi", $productData['user_id'], $notifTitle, $notifMessage, $id);
                $notifQuery->execute();
                
                $conn->commit();
                $message = "❌ Product rejected and removed!";
                $toastColor = "red";
            } catch (Exception $e) {
                $conn->rollback();
                $message = "❌ Error: " . htmlspecialchars($e->getMessage());
                $toastColor = "red";
            }
        }
    }

    /* =========================
       💼 SERVICE APPROVAL SECTION
       ========================= */
    elseif ($type === 'service') {
        if ($action === 'approve') {
            $conn->begin_transaction();

            try {
                // Get service details before moving
                $serviceQuery = $conn->prepare("
                    SELECT s.*, u.username, u.tip_email 
                    FROM pending_services s 
                    JOIN userdata u ON s.user_id = u.id 
                    WHERE s.id = ?
                ");
                $serviceQuery->bind_param("i", $id);
                $serviceQuery->execute();
                $serviceData = $serviceQuery->get_result()->fetch_assoc();

                // Copy from pending_services → service_offers (include user_id)
                $insertSQL = "
                    INSERT INTO service_offers
                    (user_id, serviceTitle, serviceCategory, startingPrice, serviceDescription, serviceDuration, serviceImages, providerBio, contactFacebook, contactEmail, contactChat, contactMeetup, posted_at)
                    SELECT user_id, serviceTitle, serviceCategory, startingPrice, serviceDescription, serviceDuration, serviceImages, providerBio, contactFacebook, contactEmail, contactChat, contactMeetup, submitted_at
                    FROM pending_services
                    WHERE id = ?
                ";
                $stmt = $conn->prepare($insertSQL);
                $stmt->bind_param("i", $id);
                $stmt->execute();

                $newServiceId = $conn->insert_id;

                // Move portfolio items
                $copyPortfolioSQL = "
                    UPDATE service_portfolio
                    SET service_offer_id = ?, pending_service_id = NULL
                    WHERE pending_service_id = ?
                ";
                $pstmt = $conn->prepare($copyPortfolioSQL);
                $pstmt->bind_param("ii", $newServiceId, $id);
                $pstmt->execute();

                // Move service images
                $imgQuery = $conn->prepare("SELECT serviceImages FROM pending_services WHERE id = ?");
                $imgQuery->bind_param("i", $id);
                $imgQuery->execute();
                $imgResult = $imgQuery->get_result();
                $imgRow = $imgResult->fetch_assoc();
                $imgPaths = $imgRow['serviceImages'] ?? '';

                if ($imgPaths) {
                    $images = explode(',', $imgPaths);
                    $newPaths = [];
                    $newDir = "../uploads/services/";
                    if (!is_dir($newDir)) mkdir($newDir, 0777, true);

                    foreach ($images as $img) {
                        $img = trim($img);
                        if (file_exists($img)) {
                            $fileName = basename($img);
                            $newPath = $newDir . $fileName;
                            if (rename($img, $newPath)) $newPaths[] = $newPath;
                        }
                    }

                    if (!empty($newPaths)) {
                        $update = $conn->prepare("UPDATE service_offers SET serviceImages = ? WHERE id = ?");
                        $newPathsStr = implode(',', $newPaths);
                        $update->bind_param("si", $newPathsStr, $newServiceId);
                        $update->execute();
                    }
                }

                // Delete from pending_services
                $delete = $conn->prepare("DELETE FROM pending_services WHERE id = ?");
                $delete->bind_param("i", $id);
                $delete->execute();

                // ✅ Log service approval - FIXED parameter count
                $logQuery = $conn->prepare("
                    INSERT INTO admin_activity 
                    (admin_id, admin_name, action_type, target_type, target_id, target_name, target_email, reason, details) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $actionType = 'approve_service';
                $targetType = 'service';
                $targetName = $serviceData['serviceTitle'];
                $targetEmail = $serviceData['tip_email'];
                $reason = 'Approved';
                $details = "Service: {$serviceData['serviceTitle']}, Price: ₱{$serviceData['startingPrice']}, Category: {$serviceData['serviceCategory']}, Status: Approved";
                
                $logQuery->bind_param("isssissss", $adminId, $adminName, $actionType, $targetType, $id, $targetName, $targetEmail, $reason, $details);
                $logQuery->execute();
                
                // ✅ Send notification to user
                $notifTitle = "Service Approved ✅";
                $notifMessage = "Your service '{$serviceData['serviceTitle']}' has been approved and is now live!";
                $notifQuery = $conn->prepare("INSERT INTO notifications (user_id, type, title, message, related_id, related_type) VALUES (?, 'post_approved', ?, ?, ?, 'service')");
                $notifQuery->bind_param("issi", $serviceData['user_id'], $notifTitle, $notifMessage, $id);
                $notifQuery->execute();

                $conn->commit();
                $message = "✅ Service approved!";
                $toastColor = "green";
            } catch (Exception $e) {
                $conn->rollback();
                $message = "❌ Error: " . htmlspecialchars($e->getMessage());
                $toastColor = "red";
            }
        } elseif ($action === 'reject') {
            $conn->begin_transaction();
            
            try {
                // Get service details for logging
                $serviceQuery = $conn->prepare("
                    SELECT s.*, u.username, u.tip_email 
                    FROM pending_services s 
                    JOIN userdata u ON s.user_id = u.id 
                    WHERE s.id = ?
                ");
                $serviceQuery->bind_param("i", $id);
                $serviceQuery->execute();
                $serviceData = $serviceQuery->get_result()->fetch_assoc();
                
                // Delete associated portfolio items first (foreign key constraint)
                $deletePortfolio = $conn->prepare("DELETE FROM service_portfolio WHERE pending_service_id = ?");
                $deletePortfolio->bind_param("i", $id);
                $deletePortfolio->execute();
                
                // ✅ DELETE rejected service instead of updating status
                $stmt = $conn->prepare("DELETE FROM pending_services WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                
                // ✅ Log service rejection - FIXED parameter count
                $logQuery = $conn->prepare("
                    INSERT INTO admin_activity 
                    (admin_id, admin_name, action_type, target_type, target_id, target_name, target_email, reason, details) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $actionType = 'reject_service';
                $targetType = 'service';
                $targetName = $serviceData['serviceTitle'];
                $targetEmail = $serviceData['tip_email'];
                $reason = 'Rejected';
                $details = "Service: {$serviceData['serviceTitle']}, Price: ₱{$serviceData['startingPrice']}, Category: {$serviceData['serviceCategory']}, Status: Rejected and Removed";
                
                $logQuery->bind_param("isssissss", $adminId, $adminName, $actionType, $targetType, $id, $targetName, $targetEmail, $reason, $details);
                $logQuery->execute();
                
                // ✅ Send notification to user
                $notifTitle = "Service Rejected ❌";
                $notifMessage = "Your service '{$serviceData['serviceTitle']}' was rejected and removed. Please review our guidelines before resubmitting.";
                $notifQuery = $conn->prepare("INSERT INTO notifications (user_id, type, title, message, related_id, related_type) VALUES (?, 'post_rejected', ?, ?, ?, 'service')");
                $notifQuery->bind_param("issi", $serviceData['user_id'], $notifTitle, $notifMessage, $id);
                $notifQuery->execute();
                
                $conn->commit();
                $message = "❌ Service rejected and removed!";
                $toastColor = "red";
            } catch (Exception $e) {
                $conn->rollback();
                $message = "❌ Error: " . htmlspecialchars($e->getMessage());
                $toastColor = "red";
            }
        }
    }

    // Redirect to clear URL parameters
    header("Location: dashboard.php");
    exit;
}

// ✅ Fetch pending posts
$pendingPosts = [];
$pendingServices = [];

$query1 = "
    SELECT p.*, u.username, u.first_name, u.last_name, u.tip_email
    FROM pending_post p
    JOIN userdata u ON p.user_id = u.id
    ORDER BY p.submitted_at DESC
";
$result1 = $conn->query($query1);
if ($result1) $pendingPosts = $result1->fetch_all(MYSQLI_ASSOC);

$query2 = "
    SELECT s.*, u.username, u.first_name, u.last_name, u.tip_email
    FROM pending_services s
    JOIN userdata u ON s.user_id = u.id
    ORDER BY s.submitted_at DESC
";
$result2 = $conn->query($query2);
if ($result2) $pendingServices = $result2->fetch_all(MYSQLI_ASSOC);

// ✅ User management code
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// ✅ Handle user actions with reason logging
if (isset($_GET['useraction']) && isset($_GET['userid'])) {
    $action = $_GET['useraction'];
    $userId = (int)$_GET['userid'];
    $reason = $_GET['reason'] ?? 'No reason provided';
    $newStatus = '';
    
    // Get user details for logging
    $userQuery = $conn->prepare("SELECT username, tip_email FROM userdata WHERE id = ?");
    $userQuery->bind_param("i", $userId);
    $userQuery->execute();
    $userResult = $userQuery->get_result();
    $userData = $userResult->fetch_assoc();
    
    // ✅ Verify user data exists
    if (!$userData) {
        header("Location: dashboard.php?page=$page&error=user_not_found");
        exit;
    }
    
    switch ($action) {
        case 'ban':
            $newStatus = 'banned';
            $actionType = 'ban_user';
            $update = $conn->prepare("UPDATE userdata SET status = ?, ban_reason = ? WHERE id = ?");
            $update->bind_param("ssi", $newStatus, $reason, $userId);
            $update->execute();
            $update->close();
            break;
        case 'unban':
            $newStatus = 'active';
            $actionType = 'unban_user';
            $update = $conn->prepare("UPDATE userdata SET status = ?, ban_reason = NULL WHERE id = ?");
            $update->bind_param("si", $newStatus, $userId);
            $update->execute();
            $update->close();
            break;
        case 'suspend':
            $newStatus = 'suspended';
            $actionType = 'suspend_user';
            $suspendedUntil = date('Y-m-d', strtotime('+7 days'));
            $update = $conn->prepare("UPDATE userdata SET status = ?, suspend_until = ?, ban_reason = ? WHERE id = ?");
            $update->bind_param("sssi", $newStatus, $suspendedUntil, $reason, $userId);
            $update->execute();
            $update->close();
            break;
        case 'activate':
            $newStatus = 'active';
            $actionType = 'activate_user';
            $update = $conn->prepare("UPDATE userdata SET status = ?, ban_reason = NULL WHERE id = ?");
            $update->bind_param("si", $newStatus, $userId);
            $update->execute();
            $update->close();
            break;
    }

    // ✅ Log admin activity with verified admin session data
    $logQuery = $conn->prepare("
        INSERT INTO admin_activity 
        (admin_id, admin_name, action_type, target_type, target_id, target_name, target_email, reason, details) 
        VALUES (?, ?, ?, 'user', ?, ?, ?, ?, ?)
    ");
    $details = "Status changed to: " . $newStatus;
    $logQuery->bind_param("isssisss", $adminId, $adminName, $actionType, $userId, $userData['username'], $userData['tip_email'], $reason, $details);
    $logQuery->execute();
    $logQuery->close();

    header("Location: dashboard.php?page=$page");
    exit;
}

$query = "SELECT * FROM userdata WHERE role = 'student' ORDER BY id ASC LIMIT $limit OFFSET $offset";
$result = mysqli_query($conn, $query);

// ✅ Fetch recent admin activity
$activityQuery = "
    SELECT * FROM admin_activity 
    ORDER BY created_at DESC 
    LIMIT 20
";
$activityResult = $conn->query($activityQuery);
$recentActivities = [];
if ($activityResult) {
    $recentActivities = $activityResult->fetch_all(MYSQLI_ASSOC);
}

// ✅ FETCH DYNAMIC STATISTICS
$today = date('Y-m-d');

// Total Users
$totalUsersQuery = "SELECT COUNT(*) as total FROM userdata";
$totalUsersResult = $conn->query($totalUsersQuery);
$totalUsers = $totalUsersResult->fetch_assoc()['total'];

$newUsersTodayQuery = "SELECT COUNT(*) as total FROM userdata WHERE DATE(created_at) = '$today'";
$newUsersTodayResult = $conn->query($newUsersTodayQuery);
$newUsersToday = $newUsersTodayResult->fetch_assoc()['total'];

// Total Marketplace Items
$totalMarketplaceQuery = "SELECT COUNT(*) as total FROM marketplace_items WHERE status = 'active'";
$totalMarketplaceResult = $conn->query($totalMarketplaceQuery);
$totalMarketplace = $totalMarketplaceResult->fetch_assoc()['total'];

$newMarketplaceTodayQuery = "SELECT COUNT(*) as total FROM marketplace_items WHERE DATE(posted_at) = '$today' AND status = 'active'";
$newMarketplaceTodayResult = $conn->query($newMarketplaceTodayQuery);
$newMarketplaceToday = $newMarketplaceTodayResult->fetch_assoc()['total'];

// Total Services
$totalServicesQuery = "SELECT COUNT(*) as total FROM service_offers WHERE status = 'available'";
$totalServicesResult = $conn->query($totalServicesQuery);
$totalServices = $totalServicesResult->fetch_assoc()['total'];

$newServicesTodayQuery = "SELECT COUNT(*) as total FROM service_offers WHERE DATE(posted_at) = '$today' AND status = 'available'";
$newServicesTodayResult = $conn->query($newServicesTodayQuery);
$newServicesToday = $newServicesTodayResult->fetch_assoc()['total'];

// Pending Posts (Marketplace + Services)
$pendingPostsQuery = "SELECT COUNT(*) as total FROM pending_post WHERE status = 'pending'";
$pendingPostsResult = $conn->query($pendingPostsQuery);
$pendingPostsCount = $pendingPostsResult->fetch_assoc()['total'];

$pendingServicesQuery = "SELECT COUNT(*) as total FROM pending_services WHERE status = 'pending'";
$pendingServicesResult = $conn->query($pendingServicesQuery);
$pendingServicesCount = $pendingServicesResult->fetch_assoc()['total'];

$totalPending = $pendingPostsCount + $pendingServicesCount;
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ONE-TiP - Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="../assets/dashboard.css">
    <link rel="stylesheet" href="../assets/admin.css">
</head>
<body>

    
    <!-- Navigation Header -->
    <header class="main-header admin-header">
        <div class="header-container">
            <div class="logo-section">
                <img src="../assets/Images/LOGO-LONG.png" alt="ONE-TiP Admin" class="header-logo">
                <span class="admin-badge">ADMIN</span>
            </div>
            
            <!-- Global Search Bar -->
            <div class="search-section">
                <input type="text" id="globalSearch" placeholder="Search users, posts, reports..." class="search-input">
                <button type="button" class="search-btn" id="searchBtn">
                    <img src="../assets/Images/grey-search-bar.svg" alt="Search" class="search-icon">
                </button>
            </div>
            
            <!-- Admin Controls -->
            <div class="user-section">
                <div class="user-profile" id="userProfile">
                    <img src="../assets/Images/profile-icon.png" alt="Admin" class="profile-img" id="userAvatar">
                    <span class="username" id="displayUsername"><?= htmlspecialchars($_SESSION['username'] ?? 'Admin User'); ?></span>
                </div>
            </div>
        </div>
        
        <!-- Admin Navigation Tabs -->
        <nav class="nav-tabs admin-nav">
            <button class="nav-tab active" data-section="overview">Overview</button>
            <button class="nav-tab" data-section="posts">Posts Management</button>
            <button class="nav-tab" data-section="users">User Management</button>
            <button class="nav-tab" data-section="reports">Reports & Flags</button>
        </nav>
    </header>

    <!-- Overview Section -->
    <section id="overview" class="section-content active">
        <div class="admin-container">
            <h1>Admin Dashboard Overview</h1>
            
            <!-- Stats Grid -->
            <div class="admin-stats-grid">
                <div class="admin-stat-card">
                    <div class="stat-icon users-icon">
                        <img src="../assets/Images/users-icon.svg" alt="Users" class="icon-img">
                    </div>
                    <div class="stat-info">
                        <div class="stat-number" id="totalUsers"><?= number_format($totalUsers) ?></div>
                        <div class="stat-label">Total Users</div>
                        <div class="stat-sublabel" id="newUsersToday">+<?= $newUsersToday ?> today</div>
                    </div>
                </div>
                
                <div class="admin-stat-card">
                    <div class="stat-icon marketplace-icon">
                        <img src="../assets/Images/gear-icon.svg" alt="Marketplace" class="icon-img">
                    </div>
                    <div class="stat-info">
                        <div class="stat-number" id="totalProducts"><?= number_format($totalMarketplace) ?></div>
                        <div class="stat-label">Marketplace Items</div>
                        <div class="stat-sublabel" id="newProductsToday">+<?= $newMarketplaceToday ?> today</div>
                    </div>
                </div>
                
                <div class="admin-stat-card">
                    <div class="stat-icon services-icon">
                        <img src="../assets/Images/marketplace-icon.svg" alt="Services" class="icon-img">
                    </div>
                    <div class="stat-info">
                        <div class="stat-number" id="totalServices"><?= number_format($totalServices) ?></div>
                        <div class="stat-label">Services</div>
                        <div class="stat-sublabel" id="newServicesToday">+<?= $newServicesToday ?> today</div>
                    </div>
                </div>
                
                <div class="admin-stat-card pending">
                    <div class="stat-icon pending-icon">
                        <img src="../assets/Images/hourglass-icon.svg" alt="Pending" class="icon-img">
                    </div>
                    <div class="stat-info">
                        <div class="stat-number" id="pendingPosts"><?= $totalPending ?></div>
                        <div class="stat-label">Pending Posts</div>
                        <div class="stat-sublabel">Awaiting Review</div>
                    </div>
                </div>
                
                
            </div>
            
            <!-- Recent Activity -->
            <div class="admin-section">
                <h3>Recent Admin Activity</h3>
                <div class="activity-list" id="adminActivity">
                    <?php if (count($recentActivities) > 0): ?>
                        <?php foreach ($recentActivities as $activity): ?>
                            <div class="activity-item">
                                <div class="activity-content">
                                    <?php if (strpos($activity['action_type'], 'user') !== false): ?>
                                        <!-- User-related actions -->
                                        <div class="activity-text">
                                            <strong>Admin:</strong> <?= htmlspecialchars($activity['admin_name']) ?> | 
                                            <strong>User:</strong> <?= htmlspecialchars($activity['target_name']) ?> | 
                                            <strong>Email:</strong> <?= htmlspecialchars($activity['target_email']) ?>
                                        </div>
                                        <div class="activity-detail">
                                            <strong>Action:</strong> <?= ucwords(str_replace('_', ' ', $activity['action_type'])) ?> | 
                                            <strong>Cause:</strong> <?= htmlspecialchars($activity['reason']) ?>
                                        </div>
                                    <?php else: ?>
                                        <!-- Product/Service actions -->
                                        <div class="activity-text">
                                            <strong><?= ucwords(str_replace('_', ' ', $activity['action_type'])) ?></strong> by 
                                            <strong>Admin:</strong> <?= htmlspecialchars($activity['admin_name']) ?> | 
                                            <strong>ID:</strong> <?= $activity['target_id'] ?>
                                        </div>
                                        <div class="activity-detail">
                                            <?= htmlspecialchars($activity['details']) ?> | 
                                            <strong>Submitted by:</strong> <?= htmlspecialchars($activity['target_email']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="activity-time">
                                    <?= date('M d, Y h:i A', strtotime($activity['created_at'])) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-activity">No recent activity to display.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Posts Management Section -->
    <section id="posts" class="section-content">
        <div class="admin-container">
            <?php if (!empty($message)): ?>
                <div style="padding: 15px; margin-bottom: 20px; border-radius: 8px; background: <?= $toastColor === 'green' ? '#d4edda' : '#f8d7da' ?>; color: <?= $toastColor === 'green' ? '#155724' : '#721c24' ?>;">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <div class="section-header">
                <h2>Posts Management</h2>
            </div>

            <!-- Pending Marketplace Items -->
            <h3 style="margin-top: 30px;">🛒 Pending Marketplace Items</h3>
            <div class="posts-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Price</th>
                            <th>Category</th>
                            <th>Submitted By</th>
                            <th>Status</th>
                            <th>Submitted At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($pendingPosts) > 0): ?>
                            <?php foreach ($pendingPosts as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['id']) ?></td>
                                    <td>
                                        <?php if(!empty($row['productImg']) && file_exists($row['productImg'])): ?>
                                            <img src="<?= htmlspecialchars($row['productImg']) ?>" style="width: 80px; height: auto; border-radius: 5px;">
                                        <?php else: ?>
                                            N/A
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($row['productName']) ?></td>
                                    <td>₱<?= number_format($row['productPrice'], 2) ?></td>
                                    <td><?= htmlspecialchars($row['productCategory']) ?></td>
                                    <td>
                                        <?= htmlspecialchars($row['first_name'] . " " . $row['last_name']) ?><br>
                                        <small><?= htmlspecialchars($row['tip_email']) ?></small>
                                    </td>
                                    <td><span class="status-badge status-<?= $row['status'] ?>"><?= htmlspecialchars($row['status']) ?></span></td>
                                    <td><?= htmlspecialchars($row['submitted_at']) ?></td>
                                    <td>
                                        <?php if($row['status'] === 'pending'): ?>
                                            <a class="btn-action btn-approve" href="?action=approve&id=<?= $row['id'] ?>&type=product" onclick="return confirm('Approve this product?')">
                                                Approve
                                            </a>
                                            <a class="btn-action btn-reject" href="?action=reject&id=<?= $row['id'] ?>&type=product" onclick="return confirm('Reject this product?')">
                                                Reject
                                            </a>
                                        <?php else: ?>
                                            N/A
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="9" style="text-align:center;">No pending marketplace items.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pending Service Offers -->
            <h3 style="margin-top: 40px;">💼 Pending Service Offers</h3>
            <div class="posts-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Images</th>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Rate</th>
                            <th>Submitted By</th>
                            <th>Status</th>
                            <th>Submitted At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($pendingServices) > 0): ?>
                            <?php foreach ($pendingServices as $srv): ?>
                                <tr>
                                    <td><?= htmlspecialchars($srv['id']) ?></td>
                                    <td>
                                        <?php 
                                        $imgs = explode(',', $srv['serviceImages']);
                                        if (!empty($imgs[0]) && file_exists(trim($imgs[0]))): ?>
                                            <img src="<?= htmlspecialchars(trim($imgs[0])) ?>" style="width: 80px; height: auto; border-radius: 5px;">
                                        <?php else: ?>
                                            N/A
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($srv['serviceTitle']) ?></td>
                                    <td><?= htmlspecialchars($srv['serviceCategory']) ?></td>
                                    <td>₱<?= number_format($srv['startingPrice'], 2) ?></td>
                                    <td>
                                        <?= htmlspecialchars($srv['first_name'] . " " . $srv['last_name']) ?><br>
                                        <small><?= htmlspecialchars($srv['tip_email']) ?></small>
                                    </td>
                                    <td><span class="status-badge status-<?= $srv['status'] ?>"><?= htmlspecialchars($srv['status']) ?></span></td>
                                    <td><?= htmlspecialchars($srv['submitted_at']) ?></td>
                                    <td>
                                        <?php if($srv['status'] === 'pending'): ?>
                                            <a class="btn-action btn-approve" href="?action=approve&id=<?= $srv['id'] ?>&type=service" onclick="return confirm('Approve this service?')">
                                                Approve
                                            </a>
                                            <a class="btn-action btn-reject" href="?action=reject&id=<?= $srv['id'] ?>&type=service" onclick="return confirm('Reject this service?')">
                                                Reject
                                            </a>
                                        <?php else: ?>
                                            N/A
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="9" style="text-align:center;">No pending service offers.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <!-- User Management Section -->
    <section id="users" class="section-content">
        <div class="admin-container">
            <div class="section-header">
                <h2>User Management</h2>
                <div class="header-actions">
                    <input type="text" id="userSearch" placeholder="Search by name, email, or ID..." class="search-input-small">
                    <select id="userStatusFilter" class="filter-select">
                        <option value="all">All Users</option>
                        <option value="active">Active</option>
                        <option value="suspended">Suspended</option>
                        <option value="banned">Banned</option>
                    </select>
                </div>
            </div>
            
            <div class="users-table-container">
                <table class="admin-table" id="usersTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Department</th>
                            <th>Status</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                <?php
                if (mysqli_num_rows($result) > 0 ) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        echo "<tr>
                        <td>{$row['id']}</td>
                        <td>{$row['username']}</td>
                        <td>{$row['tip_email']}</td>
                        <td>{$row['role']}</td>
                        <td>{$row['department']}</td>
                        <td><span class='status-badge status-{$row['status']}'>{$row['status']}</span></td>
                        <td>{$row['created_at']}</td>
                        <td>";

                  if ($row['status'] == 'active') {
            echo "
            <button class='btn-action btn-ban' 
                onclick=\"showReasonModal('ban', {$row['id']}, '{$row['username']}', $page)\">
                Ban
            </button>
            <button class='btn-action btn-suspend' 
                onclick=\"showReasonModal('suspend', {$row['id']}, '{$row['username']}', $page)\">
                Suspend
            </button>
        ";
        } elseif ($row['status'] == 'banned') {
            echo "
                <button class='btn-action btn-activate' 
                    onclick=\"if(confirm('Unban {$row['username']}?')) window.location.href='?useraction=unban&userid={$row['id']}&page=$page&reason=Unbanned';\">
                    Unban
                </button>
            ";
        } elseif ($row['status'] == 'suspended') {
            echo "
                <button class='btn-action btn-activate' 
                    onclick=\"if(confirm('Activate {$row['username']}?')) window.location.href='?useraction=activate&userid={$row['id']}&page=$page&reason=Activated';\">
                    Activate
                </button>
            ";
    }

                    echo "</td>
                        </tr>";
                }
            } else {
                echo "<tr><td colspan='8'>No users found</td></tr>";
            }
                    ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <!-- Reports & Flags Section -->
    <section id="reports" class="section-content">
        <div class="admin-container">
            <div class="section-header">
                <h2>Reports & Flagged Content</h2>
                <div class="header-actions">
                    <select id="reportStatusFilter" class="filter-select">
                        <option value="pending">Pending Review</option>
                        <option value="notified">Seller Notified</option>
                        <option value="resolved">Resolved</option>
                        <option value="dismissed">Dismissed</option>
                    </select>
                    <select id="reportTypeFilter" class="filter-select">
                        <option value="all">All Types</option>
                        <option value="marketplace">Marketplace</option>
                        <option value="service">Services</option>
                    </select>
                </div>
            </div>
            
            <div class="reports-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Item</th>
                            <th>Type</th>
                            <th>Reported By</th>
                            <th>Reason</th>
                            <th>Description</th>
                            <th>Seller</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // ✅ FIX: Add default status if column doesn't exist, also check if reports table exists
                        $checkReportsTable = "SHOW TABLES LIKE 'reports'";
                        $tableCheck = $conn->query($checkReportsTable);
                        
                        if ($tableCheck && $tableCheck->num_rows > 0) {
                            $reportsQuery = "SELECT r.*, u.username as reporter_name 
                                             FROM reports r 
                                             LEFT JOIN userdata u ON r.user_id = u.id 
                                             ORDER BY r.created_at DESC";
                            $reportsResult = $conn->query($reportsQuery);
                            
                            if ($reportsResult && $reportsResult->num_rows > 0):
                                while ($report = $reportsResult->fetch_assoc()):
                                    // ✅ Set default status if empty
                                    $reportStatus = !empty($report['status']) ? $report['status'] : 'pending';
                        ?>
                            <tr>
                                <td><?= $report['id'] ?></td>
                                <td><?= htmlspecialchars($report['item_name'] ?? 'N/A') ?></td>
                                <td><span class="badge badge-<?= $report['item_type'] ?>"><?= ucfirst($report['item_type']) ?></span></td>
                                <td><?= htmlspecialchars($report['reporter_name'] ?? 'Unknown') ?></td>
                                <td><?= ucwords(str_replace('_', ' ', $report['reason'] ?? 'No reason')) ?></td>
                                <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars($report['description'] ?? '') ?></td>
                                <td><?= htmlspecialchars($report['seller_email'] ?? 'N/A') ?></td>
                                <td><span class="status-badge status-<?= $reportStatus ?>"><?= ucfirst($reportStatus) ?></span></td>
                                <td><?= date('M d, Y', strtotime($report['created_at'])) ?></td>
                                <td>
                                    <?php if ($reportStatus === 'pending'): ?>
                                        <div class="report-actions">
                                            <button class="btn-action btn-warning" onclick="window.location.href='<?= $report['item_type'] === 'marketplace' ? 'view_marketplace_item' : 'view_service_item' ?>.php?item_id=<?= $report['item_id'] ?>&report_id=<?= $report['id'] ?>';">
                                                👁️ View & Send Warning
                                            </button>
                                            <button class="btn-action btn-reject" onclick="if(confirm('⚠️ Delete this item permanently? This cannot be undone.')) window.location.href='handle_report_action.php?action=delete_item&report_id=<?= $report['id'] ?>';">
                                                🗑️ Delete Item
                                            </button>
                                            <button class="btn-action btn-secondary" onclick="if(confirm('Dismiss this report as invalid?')) window.location.href='handle_report_action.php?action=dismiss&report_id=<?= $report['id'] ?>';">
                                                ❌ Dismiss Report
                                            </button>
                                        </div>
                                    <?php elseif ($reportStatus === 'notified'): ?>
                                        <div class="report-actions">
                                            <button class="btn-action btn-reject" onclick="if(confirm('⚠️ Delete this item permanently? This cannot be undone.')) window.location.href='handle_report_action.php?action=delete_item&report_id=<?= $report['id'] ?>';">
                                                🗑️ Delete Item
                                            </button>
                                            <button class="btn-action btn-secondary" onclick="if(confirm('Dismiss this report?')) window.location.href='handle_report_action.php?action=dismiss&report_id=<?= $report['id'] ?>';">
                                                ❌ Dismiss
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <span style="color: #666; font-style: italic;"><?= ucfirst($reportStatus) ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php
                                endwhile;
                            else:
                        ?>
                            <tr><td colspan="10" style="text-align:center;">No reports found</td></tr>
                        <?php 
                            endif;
                        } else {
                        ?>
                            <tr><td colspan="10" style="text-align:center; color: #dc3545;">⚠️ Reports table doesn't exist. Please run the database setup script.</td></tr>
                        <?php
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <!-- Admin Settings Section -->
    <section id="settings" class="section-content">
        <div class="admin-container">
            <h2>Admin Settings</h2>
            
            <div class="settings-grid">
                <div class="settings-card">
                    <h3>Platform Settings</h3>
                    <div class="setting-item">
                        <label>Auto-approve posts</label>
                        <input type="checkbox" id="autoApprove">
                    </div>
                    <div class="setting-item">
                        <label>Require email verification</label>
                        <input type="checkbox" id="requireEmailVerification" checked>
                    </div>
                    <div class="setting-item">
                        <label>Max posts per user per day</label>
                        <input type="number" id="maxPostsPerDay" value="5" min="1" max="20">
                    </div>
                </div>
                
                <div class="settings-card">
                    <h3>Content Moderation</h3>
                    <div class="setting-item">
                        <label>Profanity filter</label>
                        <input type="checkbox" id="profanityFilter" checked>
                    </div>
                    <div class="setting-item">
                        <label>Auto-flag suspicious content</label>
                        <input type="checkbox" id="autoFlag" checked>
                    </div>
                    <div class="setting-item">
                        <label>Reports threshold for auto-removal</label>
                        <input type="number" id="reportsThreshold" value="3" min="1" max="10">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Post Details Modal -->
    <div id="postModal" class="modal admin-modal">
        <div class="modal-content admin-modal-content">
            <div class="modal-header">
                <h3 id="modalPostTitle">Post Details</h3>
                <span class="close" id="closePostModal">&times;</span>
            </div>
            <div class="modal-body" id="postModalBody">
                <!-- Post details will be loaded here -->
            </div>
            <div class="modal-footer">
                <button class="btn-approve" id="approvePostBtn">Approve</button>
                <button class="btn-reject" id="rejectPostBtn">Reject</button>
                <button class="btn-edit" id="editPostBtn">Edit</button>
                <button class="btn-delete" id="deletePostBtn">Delete</button>
            </div>
        </div>
    </div>

    <!-- User Details Modal -->
    <div id="userModal" class="modal admin-modal">
        <div class="modal-content admin-modal-content">
            <div class="modal-header">
                <h3 id="modalUserName">User Details</h3>
                <span class="close" id="closeUserModal">&times;</span>
            </div>
            <div class="modal-body" id="userModalBody">
                <!-- User details will be loaded here -->
            </div>
            <div class="modal-footer">
                <button class="btn-suspend" id="suspendUserBtn">Suspend</button>
                <button class="btn-ban" id="banUserBtn">Ban</button>
                <button class="btn-activate" id="activateUserBtn">Activate</button>
            </div>
        </div>
    </div>

    <!-- Admin Profile Dropdown -->
   <div id="userProfile" class="user-profile">
    <img src="../assets/Images/profile-icon.png" alt="Admin" class="profile-img">
    <span><?= htmlspecialchars($_SESSION['username'] ?? 'Admin User'); ?></span>
</div>

<div id="userProfileDropdown" class="profile-dropdown admin-dropdown">
    <div class="profile-header">
        <img src="../assets/Images/profile-icon.png" alt="Admin" class="dropdown-avatar">
        <div class="profile-details">
            <div class="profile-name"><?= htmlspecialchars($_SESSION['username'] ?? 'Admin User'); ?></div>
            <div class="profile-email"><?= htmlspecialchars($_SESSION['tip_email'] ?? 'admin@tip.edu.ph'); ?></div>
        </div>
    </div>
    <div class="profile-menu">
        <a href="#" class="menu-item" data-action="admin-settings">⚙️ Admin Settings</a>
        <a href="#" class="menu-item" data-action="system-logs">📋 System Logs</a>
        <a href="#" class="menu-item" data-action="backup">💾 Backup</a>
        <a href="#" class="menu-item" data-action="logout">🚪 Logout</a>
    </div>
</div>

    <script src="../assets/script/admin_dashboard.js"></script>
    <script src="../assets/script/admin_reason_modal.js"></script>

</body>
</html>

<!-- ✅ Reason Modal - COMPACT UI (Matching Image) -->
<div id="reasonModal" class="modal admin-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="reasonModalTitle">Suspend User - Provide Reason</h3>
            <span class="close" onclick="closeReasonModal()">×</span>
        </div>
        <div class="modal-body">
            <p id="reasonModalText">Please provide a reason for suspending this user (7 days):</p>
            
            <div class="user-info">
                <p><strong>User:</strong> <span id="modalUsername">username</span></p>
                <p><strong>Email:</strong> <span id="modalUserEmail">email@tip.edu.ph</span></p>
            </div>
            
            <textarea id="reasonInput" rows="3" placeholder="Enter reason..."></textarea>
        </div>
        <div class="modal-footer">
            <button class="btn-secondary" onclick="closeReasonModal()">Cancel</button>
            <button class="btn-primary" onclick="submitReason()">Confirm</button>
        </div>
    </div>
</div>

<script>
    // filepath: c:\xampp\htdocs\0neTip\admin\dashboard.php
    
    // ====== NAVIGATION TAB SWITCHING ======
    (function() {
        'use strict';
        
        console.log('Admin navigation script loaded');
        
        function initNavigation() {
            const navTabs = document.querySelectorAll('.admin-nav .nav-tab');
            const sections = document.querySelectorAll('.section-content');
            
            console.log('Found nav tabs:', navTabs.length);
            console.log('Found sections:', sections.length);
            
            if (navTabs.length === 0 || sections.length === 0) {
                console.error('❌ Navigation elements not found!');
                return;
            }
            
            // Add click handlers to each tab
            navTabs.forEach((tab, index) => {
                console.log(`Tab ${index}:`, tab.getAttribute('data-section'));
                
                tab.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const targetSection = this.getAttribute('data-section');
                    console.log('🔵 Tab clicked:', targetSection);
                    
                    if (!targetSection) {
                        console.error('❌ No data-section attribute');
                        return;
                    }
                    
                    // Remove active class from all tabs
                    navTabs.forEach(t => t.classList.remove('active'));
                    
                    // Remove active class from all sections
                    sections.forEach(s => s.classList.remove('active'));
                    
                    // Add active class to clicked tab
                    this.classList.add('active');
                    
                    // Show target section
                    const sectionToShow = document.getElementById(targetSection);
                    if (sectionToShow) {
                        sectionToShow.classList.add('active');
                        console.log('✅ Section shown:', targetSection);
                    } else {
                        console.error('❌ Section not found:', targetSection);
                    }
                });
            });
            
            // Ensure first section is visible on load
            if (!document.querySelector('.section-content.active')) {
                sections[0]?.classList.add('active');
                navTabs[0]?.classList.add('active');
                console.log('✅ First section activated by default');
            }
            
            // ✅ Handle URL hash for section navigation (e.g., #reports)
            function handleHashNavigation() {
                const hash = window.location.hash.substring(1); // Remove #
                const hashParts = hash.split('&');
                const section = hashParts[0] || 'overview';
                
                if (section) {
                    // Remove active from all
                    navTabs.forEach(t => t.classList.remove('active'));
                    sections.forEach(s => s.classList.remove('active'));
                    
                    // Find and activate the matching tab
                    const targetTab = Array.from(navTabs).find(t => t.getAttribute('data-section') === section);
                    const targetSection = document.getElementById(section);
                    
                    if (targetTab && targetSection) {
                        targetTab.classList.add('active');
                        targetSection.classList.add('active');
                        console.log('✅ Navigated to section via hash:', section);
                        
                        // Show success message if present
                        if (hash.includes('warning_sent=1')) {
                            setTimeout(() => {
                                alert('✅ Warning notification sent to seller successfully!');
                            }, 100);
                        }
                    }
                }
            }
            
            // Handle hash on page load
            handleHashNavigation();
            
            // Handle hash changes (back/forward button)
            window.addEventListener('hashchange', handleHashNavigation);
        }
        
        // Wait for DOM
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initNavigation);
        } else {
            initNavigation();
        }
    })();
    
    // ====== REASON MODAL FUNCTIONS ======
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
        
        const userRow = document.querySelector(`button[onclick*="showReasonModal('${action}', ${userId}"]`).closest('tr');
        currentUserEmail = userRow.cells[2].textContent;
        
        const actionText = action.charAt(0).toUpperCase() + action.slice(1);
        document.getElementById('reasonModalTitle').textContent = `${actionText} User - Provide Reason`;
        
        let durationText = action === 'suspend' ? ' (7 days)' : '';
        document.getElementById('reasonModalText').textContent = 
            `Please provide a reason for ${action}ing this user${durationText}:`;
        
        document.getElementById('modalUsername').textContent = username;
        document.getElementById('modalUserEmail').textContent = currentUserEmail;
        document.getElementById('reasonInput').value = '';
        document.getElementById('reasonModal').style.display = 'flex';
    }
    
    function closeReasonModal() {
        document.getElementById('reasonModal').style.display = 'none';
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
    
    // Close modal handlers
    window.onclick = function(event) {
        const modal = document.getElementById('reasonModal');
        if (event.target === modal) {
            closeReasonModal();
        }
    };
    
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeReasonModal();
        }
    });
</script>