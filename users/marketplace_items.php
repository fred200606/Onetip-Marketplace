<?php
include "../config/db.php"; // PDO connection
    
try {
    // Fetch all approved marketplace items
    $items = $pdo->query("SELECT * FROM marketplace_items ORDER BY posted_at DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("<div style='color: red;'>❌ Database error: " . $e->getMessage() . "</div>");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Marketplace Items</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; vertical-align: middle; }
        th { background: #f4f4f4; }
        img.thumbnail { width: 100px; height: auto; border-radius: 5px; }
        h1 { text-align: center; }
    </style>
</head>
<body>
    <h1>Marketplace Items</h1>
    <table>
        <tr>
            <th>ID</th>
            <th>Image</th>
            <th>Product Name</th>
            <th>Price</th>
            <th>Category</th>
            <th>Condition</th>
            <th>Seller Bio</th>
            <th>Posted At</th>
        </tr>
        <?php if (count($items) > 0): ?>
            <?php foreach ($items as $item): ?>
            <tr>
                <td><?= htmlspecialchars($item['product_id']) ?></td>
                <td>
                    <?php if (!empty($item['productImg']) && file_exists($item['productImg'])): ?>
                        <img class="thumbnail" src="<?= htmlspecialchars($item['productImg']) ?>" alt="Product Image">
                    <?php else: ?>
                        N/A
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($item['productName']) ?></td>
                <td><?= htmlspecialchars($item['productPrice']) ?></td>
                <td><?= htmlspecialchars($item['productCategory']) ?></td>
                <td><?= htmlspecialchars($item['productCondition']) ?></td>
                <td><?= htmlspecialchars($item['sellerBio']) ?></td>
                <td><?= htmlspecialchars($item['posted_at']) ?></td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="8" style="text-align:center;">No items available.</td></tr>
        <?php endif; ?>
    </table>
</body>
</html>