<?php
session_name('ADMIN_SESSION');
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../loginreg/login.php");
    exit();
}

$item_id = intval($_GET['item_id'] ?? 0);
$report_id = intval($_GET['report_id'] ?? 0);

if ($item_id <= 0 || $report_id <= 0) {
    header("Location: dashboard.php#reports");
    exit();
}

// Fetch item details
$itemQuery = "SELECT m.*, u.first_name, u.last_name, u.tip_email, u.department 
              FROM marketplace_items m 
              JOIN userdata u ON m.user_id = u.id 
              WHERE m.item_id = ?";
$stmt = $conn->prepare($itemQuery);
$stmt->bind_param("i", $item_id);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();

if (!$item) {
    header("Location: dashboard.php#reports&error=item_not_found");
    exit();
}

// Fetch report details
$reportQuery = "SELECT r.*, u.username as reporter_name 
                FROM reports r 
                LEFT JOIN userdata u ON r.user_id = u.id 
                WHERE r.id = ?";
$stmt = $conn->prepare($reportQuery);
$stmt->bind_param("i", $report_id);
$stmt->execute();
$report = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Reported Item - Admin</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="../assets/admin.css">
</head>
<body>
    <div style="max-width: 1200px; margin: 20px auto; padding: 20px;">
        <div style="margin-bottom: 20px;">
            <a href="dashboard.php#reports" style="color: #007bff; text-decoration: none;">← Back to Reports</a>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
            <!-- Item Details -->
            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                <h2>Reported Item Details</h2>
                
                <div style="margin: 20px 0;">
                    <?php if (!empty($item['productImg'])): ?>
                        <?php
                        // ✅ FIX: Handle image path properly
                        $imgPath = $item['productImg'];
                        // If path doesn't start with http or ../, add ../
                        if (!str_starts_with($imgPath, 'http') && !str_starts_with($imgPath, '../')) {
                            $imgPath = '../' . $imgPath;
                        }
                        // Check if file exists
                        $imageExists = str_starts_with($imgPath, 'http') || file_exists($imgPath);
                        ?>
                        <?php if ($imageExists): ?>
                            <img src="<?= htmlspecialchars($imgPath) ?>" 
                                 alt="<?= htmlspecialchars($item['productName']) ?>" 
                                 style="width: 100%; max-height: 400px; object-fit: contain; border-radius: 8px; background: #f5f5f5; padding: 10px;">
                        <?php else: ?>
                            <div style="width: 100%; height: 300px; background: #f5f5f5; display: flex; align-items: center; justify-content: center; border-radius: 8px;">
                                <p style="color: #999;">Image file not found: <?= htmlspecialchars($item['productImg']) ?></p>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div style="width: 100%; height: 300px; background: #f5f5f5; display: flex; align-items: center; justify-content: center; border-radius: 8px;">
                            <p style="color: #999;">No image available</p>
                        </div>
                    <?php endif; ?>
                </div>

                <div style="margin-top: 20px;">
                    <h3><?= htmlspecialchars($item['productName']) ?></h3>
                    <p style="font-size: 1.5rem; color: #007bff; font-weight: 600;">₱<?= number_format($item['productPrice'], 2) ?></p>
                    
                    <div style="margin: 15px 0;">
                        <strong>Category:</strong> <?= ucwords(str_replace('_', ' ', $item['productCategory'])) ?><br>
                        <strong>Condition:</strong> <?= ucwords($item['productCondition']) ?><br>
                        <strong>Posted:</strong> <?= date('M d, Y', strtotime($item['posted_at'])) ?>
                    </div>

                    <div style="margin: 15px 0;">
                        <strong>Description:</strong>
                        <p><?= nl2br(htmlspecialchars($item['productDescription'])) ?></p>
                    </div>

                    <div style="margin: 15px 0; padding: 10px; background: #f8f9fa; border-radius: 6px;">
                        <strong>Seller Information:</strong><br>
                        Name: <?= htmlspecialchars($item['first_name'] . ' ' . $item['last_name']) ?><br>
                        Email: <?= htmlspecialchars($item['tip_email']) ?><br>
                        Department: <?= htmlspecialchars($item['department']) ?>
                    </div>
                </div>
            </div>

            <!-- Report Details & Actions -->
            <div>
                <div style="background: #fff3cd; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                    <h3 style="color: #856404;">Report Details</h3>
                    <p><strong>Reported by:</strong> <?= htmlspecialchars($report['reporter_name'] ?? 'Unknown') ?></p>
                    <p><strong>Reason:</strong> <?= ucwords(str_replace('_', ' ', $report['reason'])) ?></p>
                    <p><strong>Description:</strong><br><?= nl2br(htmlspecialchars($report['description'])) ?></p>
                    <p><strong>Reported on:</strong> <?= date('M d, Y g:i A', strtotime($report['created_at'])) ?></p>
                </div>

                <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <h3>Admin Actions</h3>
                    
                    <form action="handle_report_action.php" method="GET" style="margin-top: 20px;">
                        <input type="hidden" name="action" value="view_item">
                        <input type="hidden" name="report_id" value="<?= $report_id ?>">
                        
                        <button type="submit" 
                                style="width: 100%; padding: 12px; background: #ffc107; color: #333; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; margin-bottom: 10px;"
                                onclick="return confirm('Send warning to seller?')">
                            ⚠️ Send Warning to Seller
                        </button>
                    </form>

                    <button onclick="if(confirm('⚠️ Delete this item permanently?')) window.location.href='handle_report_action.php?action=delete_item&report_id=<?= $report_id ?>';"
                            style="width: 100%; padding: 12px; background: #dc3545; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; margin-bottom: 10px;">
                        🗑️ Delete Item Permanently
                    </button>

                    <button onclick="if(confirm('Dismiss this report?')) window.location.href='handle_report_action.php?action=dismiss&report_id=<?= $report_id ?>';"
                            style="width: 100%; padding: 12px; background: #6c757d; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;">
                        ❌ Dismiss Report
                    </button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
