<?php
include "../config/db.php";

$message = "";

// Handle approve/reject actions
if (isset($_GET['action'], $_GET['id'], $_GET['type'])) {
    $action = $_GET['action'];
    $id = intval($_GET['id']); // use numeric ID
    $type = $_GET['type'];

    /* =========================
       ✅ PRODUCT APPROVAL SECTION
       ========================= */
    if ($type === 'product') {
        if ($action === 'approve') {
            $conn->begin_transaction();

            try {
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

                // Move image if exists
                if ($imgPath && file_exists($imgPath)) {
                    $fileName = basename($imgPath);
                    $newDir = "uploads/marketplace/";
                    $newPath = $newDir . $fileName;
                    if (!is_dir($newDir)) mkdir($newDir, 0777, true);

                    if (rename($imgPath, $newPath)) {
                        // Use item_id here, not id
                        $update = $conn->prepare("UPDATE marketplace_items SET productImg = ? WHERE item_id = LAST_INSERT_ID()");
                        $update->bind_param("s", $newPath);
                        $update->execute();
                    }
                }

                // Delete from pending_post
                $delete = $conn->prepare("DELETE FROM pending_post WHERE id = ?");
                $delete->bind_param("i", $id);
                $delete->execute();

                $conn->commit();
                $message = "<div style='color: green;'>✅ Product approved and moved to marketplace!</div>";
            } catch (Exception $e) {
                $conn->rollback();
                $message = "<div style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        } elseif ($action === 'reject') {
            $stmt = $conn->prepare("UPDATE pending_post SET status='rejected' WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $message = "<div style='color: red;'>❌ Product rejected!</div>";
        }
    }

    /* =========================
   💼 SERVICE APPROVAL SECTION
   ========================= */
elseif ($type === 'service') {
    if ($action === 'approve') {
        $conn->begin_transaction();

        try {
            // 1️⃣ Insert the approved service into service_offers
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

            // Get the new service_offer ID
            $newServiceId = $conn->insert_id;

            // 2️⃣ Move portfolio items from pending → approved
            $copyPortfolioSQL = "
                UPDATE service_portfolio
                SET service_offer_id = ?, pending_service_id = NULL
                WHERE pending_service_id = ?
            ";
            $pstmt = $conn->prepare($copyPortfolioSQL);
            $pstmt->bind_param("ii", $newServiceId, $id);
            $pstmt->execute();

            // 3️⃣ Move service images to approved folder
            $imgQuery = $conn->prepare("SELECT serviceImages FROM pending_services WHERE id = ?");
            $imgQuery->bind_param("i", $id);
            $imgQuery->execute();
            $imgResult = $imgQuery->get_result();
            $imgRow = $imgResult->fetch_assoc();
            $imgPaths = $imgRow['serviceImages'] ?? '';

            if ($imgPaths) {
                $images = explode(',', $imgPaths);
                $newPaths = [];
                $newDir = "uploads/services/";
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

            // 4️⃣ Delete from pending_services (portfolio stays linked to service_offer_id)
            $delete = $conn->prepare("DELETE FROM pending_services WHERE id = ?");
            $delete->bind_param("i", $id);
            $delete->execute();

            $conn->commit();
            $message = "<div style='color: green;'>✅ Service approved and moved to service offers (portfolio kept)!</div>";
        } catch (Exception $e) {
            $conn->rollback();
            $message = "<div style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    } elseif ($action === 'reject') {
        $stmt = $conn->prepare("UPDATE pending_services SET status='rejected' WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $message = "<div style='color: red;'>❌ Service rejected!</div>";
    }
}
}

/* =========================
   📦 FETCH DATA (JOIN WITH USERDATA)
   ========================= */
$pendingPosts = [];
$pendingServices = [];

// ✅ Join pending_post with userdata
$query1 = "
    SELECT p.*, u.username, u.first_name, u.last_name, u.tip_email
    FROM pending_post p
    JOIN userdata u ON p.user_id = u.id
    ORDER BY p.submitted_at DESC
";
$result1 = $conn->query($query1);
if ($result1) $pendingPosts = $result1->fetch_all(MYSQLI_ASSOC);

// ✅ Join pending_services with userdata
$query2 = "
    SELECT s.*, u.username, u.first_name, u.last_name, u.tip_email
    FROM pending_services s
    JOIN userdata u ON s.user_id = u.id
    ORDER BY s.submitted_at DESC
";
$result2 = $conn->query($query2);
if ($result2) $pendingServices = $result2->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard - Pending Approvals</title>
<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th, td { padding: 10px; border: 1px solid #ddd; text-align: left; vertical-align: middle; }
    th { background: #f4f4f4; }
    a.button { padding: 5px 10px; border-radius: 5px; text-decoration: none; color: white; }
    a.approve { background: green; }
    a.reject { background: red; }
    img.thumbnail { width: 80px; height: auto; border-radius: 5px; }
    h1 { text-align: center; }
    h2 { margin-top: 40px; }
</style>
</head>
<body>
<h1>Admin Dashboard</h1>

<?php if (!empty($message)) echo $message; ?>

<!-- Pending Marketplace Items -->
<h2>🛒 Pending Marketplace Items</h2>
<table>
<tr>
    <th>ID</th><th>Image</th><th>Name</th><th>Price</th><th>Category</th>
    <th>Submitted By</th><th>Status</th><th>Submitted At</th><th>Actions</th>
</tr>
<?php if (count($pendingPosts) > 0): ?>
<?php foreach ($pendingPosts as $row): ?>
<tr>
    <td><?= htmlspecialchars($row['id']) ?></td>
    <td><?php if(!empty($row['productImg']) && file_exists($row['productImg'])): ?>
        <img class="thumbnail" src="<?= htmlspecialchars($row['productImg']) ?>">
        <?php else: ?>N/A<?php endif; ?></td>
    <td><?= htmlspecialchars($row['productName']) ?></td>
    <td><?= htmlspecialchars($row['productPrice']) ?></td>
    <td><?= htmlspecialchars($row['productCategory']) ?></td>
    <td><?= htmlspecialchars($row['first_name'] . " " . $row['last_name']) ?><br><small><?= htmlspecialchars($row['tip_email']) ?></small></td>
    <td><?= htmlspecialchars($row['status']) ?></td>
    <td><?= htmlspecialchars($row['submitted_at']) ?></td>
    <td><?php if($row['status'] === 'pending'): ?>
        <a class="button approve" href="?action=approve&id=<?= urlencode($row['id']) ?>&type=product">Approve</a>
        <a class="button reject" href="?action=reject&id=<?= urlencode($row['id']) ?>&type=product">Reject</a>
        <?php else: ?>N/A<?php endif; ?></td>
</tr>
<?php endforeach; ?>
<?php else: ?><tr><td colspan="9" style="text-align:center;">No pending items.</td></tr><?php endif; ?>
</table>

<!-- Pending Service Offers -->
<h2>💼 Pending Service Offers</h2>
<table>
<tr>
    <th>ID</th><th>Images</th><th>Title</th><th>Category</th><th>Rate</th>
    <th>Submitted By</th><th>Status</th><th>Submitted At</th><th>Actions</th>
</tr>
<?php if (count($pendingServices) > 0): ?>
<?php foreach ($pendingServices as $srv): ?>
<tr>
    <td><?= htmlspecialchars($srv['id']) ?></td>
    <td><?php 
        $imgs = explode(',', $srv['serviceImages']);
        if (!empty($imgs[0]) && file_exists(trim($imgs[0]))): ?>
            <img class="thumbnail" src="<?= htmlspecialchars(trim($imgs[0])) ?>">
        <?php else: ?>N/A<?php endif; ?></td>
    <td><?= htmlspecialchars($srv['serviceTitle']) ?></td>
    <td><?= htmlspecialchars($srv['serviceCategory']) ?></td>
    <td><?= htmlspecialchars($srv['startingPrice']) ?></td>
    <td><?= htmlspecialchars($srv['first_name'] . " " . $srv['last_name']) ?><br><small><?= htmlspecialchars($srv['tip_email']) ?></small></td>
    <td><?= htmlspecialchars($srv['status']) ?></td>
    <td><?= htmlspecialchars($srv['submitted_at']) ?></td>
    <td><?php if($srv['status'] === 'pending'): ?>
        <a class="button approve" href="?action=approve&id=<?= urlencode($srv['id']) ?>&type=service">Approve</a>
        <a class="button reject" href="?action=reject&id=<?= urlencode($srv['id']) ?>&type=service">Reject</a>
        <?php else: ?>N/A<?php endif; ?></td>
</tr>
<?php endforeach; ?>
<?php else: ?><tr><td colspan="9" style="text-align:center;">No pending services.</td></tr><?php endif; ?>
</table>
</body>
</html>
