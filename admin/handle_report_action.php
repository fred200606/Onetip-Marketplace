<?php
session_name('ADMIN_SESSION');
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../loginreg/login.php");
    exit();
}

$admin_id = $_SESSION['user_id'];
$admin_name = $_SESSION['username'];
$action = $_GET['action'] ?? '';
$report_id = intval($_GET['report_id'] ?? 0);

if ($report_id <= 0 || !in_array($action, ['view_item', 'delete_item', 'dismiss'])) {
    header("Location: dashboard.php?section=reports&error=invalid");
    exit();
}

// Get report details
$reportQuery = "SELECT * FROM reports WHERE id = ?";
$stmt = $conn->prepare($reportQuery);
$stmt->bind_param("i", $report_id);
$stmt->execute();
$report = $stmt->get_result()->fetch_assoc();

if (!$report) {
    header("Location: dashboard.php?section=reports&error=not_found");
    exit();
}

$conn->begin_transaction();

try {
    switch ($action) {
        case 'view_item':
            // Send warning notification to seller instead of email
            $warningTitle = "⚠️ Warning: Report Received for Your Listing";
            $warningMessage = "Your listing '{$report['item_name']}' has been reported.\n\n";
            $warningMessage .= "Reason: " . ucwords(str_replace('_', ' ', $report['reason'])) . "\n";
            if (!empty($report['description'])) {
                $warningMessage .= "Details: " . $report['description'] . "\n";
            }
            $warningMessage .= "\nPlease review our terms of service and ensure your listing complies with our guidelines. If this issue persists, your listing may be removed.";
            
            // Insert notification
            $notifQuery = "INSERT INTO notifications (user_id, type, title, message, related_id, related_type) 
                          VALUES (?, 'warning', ?, ?, ?, ?)";
            $stmt = $conn->prepare($notifQuery);
            $stmt->bind_param("issis", $report['seller_id'], $warningTitle, $warningMessage, $report['item_id'], $report['item_type']);
            $stmt->execute();
            
            // ✅ Update report status to "notified"
            $updateReport = "UPDATE reports SET status = 'notified', resolved_by = ?, resolved_at = NOW(), action_taken = 'warning_sent' WHERE id = ?";
            $stmt = $conn->prepare($updateReport);
            $stmt->bind_param("ii", $admin_id, $report_id);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to update report status");
            }
            
            // Log activity
            $logQuery = "INSERT INTO admin_activity (admin_id, admin_name, action_type, target_type, target_id, target_name, target_email, details) 
                         VALUES (?, ?, 'send_warning', ?, ?, ?, ?, ?)";
            $details = "Warning sent for report ID: {$report_id}, Reason: {$report['reason']}";
            $stmt = $conn->prepare($logQuery);
            $stmt->bind_param("isssiss", $admin_id, $admin_name, $report['item_type'], $report['item_id'], $report['item_name'], $report['seller_email'], $details);
            $stmt->execute();
            
            $conn->commit();
            
            // ✅ Redirect back to reports with success message
            header("Location: dashboard.php#reports&warning_sent=1");
            exit();
            break;
            
        case 'delete_item':
            // Delete the reported item
            if ($report['item_type'] === 'marketplace') {
                $deleteQuery = "DELETE FROM marketplace_items WHERE item_id = ?";
            } else {
                $deleteQuery = "DELETE FROM service_offers WHERE id = ?";
            }
            $stmt = $conn->prepare($deleteQuery);
            $stmt->bind_param("i", $report['item_id']);
            $stmt->execute();
            
            // Update report status
            $updateReport = "UPDATE reports SET status = 'resolved', resolved_by = ?, resolved_at = NOW(), action_taken = 'item_deleted' WHERE id = ?";
            $stmt = $conn->prepare($updateReport);
            $stmt->bind_param("ii", $admin_id, $report_id);
            $stmt->execute();
            
            // Send notification to seller
            $notifTitle = "❌ Your Listing Has Been Removed";
            $notifMessage = "Your listing '{$report['item_name']}' has been removed due to violations of our terms of service.\n\n";
            $notifMessage .= "Reason: " . ucwords(str_replace('_', ' ', $report['reason'])) . "\n";
            if (!empty($report['description'])) {
                $notifMessage .= "Details: " . $report['description'] . "\n";
            }
            $notifMessage .= "\nIf you believe this was done in error, please contact us.";
            
            $notifQuery = "INSERT INTO notifications (user_id, type, title, message, related_id, related_type) 
                          VALUES (?, 'post_removed', ?, ?, ?, ?)";
            $stmt = $conn->prepare($notifQuery);
            $stmt->bind_param("issis", $report['seller_id'], $notifTitle, $notifMessage, $report['item_id'], $report['item_type']);
            $stmt->execute();
            
            // Log activity
            $logQuery = "INSERT INTO admin_activity (admin_id, admin_name, action_type, target_type, target_id, target_name, target_email, details) 
                         VALUES (?, ?, 'delete_reported_item', ?, ?, ?, ?, ?)";
            $details = "Item deleted due to report ID: {$report_id}, Reason: {$report['reason']}";
            $stmt = $conn->prepare($logQuery);
            $stmt->bind_param("isssiss", $admin_id, $admin_name, $report['item_type'], $report['item_id'], $report['item_name'], $report['seller_email'], $details);
            $stmt->execute();
            
            $conn->commit();
            header("Location: dashboard.php?section=reports&success=item_deleted");
            break;
            
        case 'dismiss':
            // Dismiss report as invalid
            $updateReport = "UPDATE reports SET status = 'dismissed', resolved_by = ?, resolved_at = NOW(), action_taken = 'dismissed' WHERE id = ?";
            $stmt = $conn->prepare($updateReport);
            $stmt->bind_param("ii", $admin_id, $report_id);
            $stmt->execute();
            
            $conn->commit();
            header("Location: dashboard.php?section=reports&success=report_dismissed");
            break;
    }
} catch (Exception $e) {
    $conn->rollback();
    error_log("Report action error: " . $e->getMessage());
    header("Location: dashboard.php#reports&error=action_failed");
}
?>
