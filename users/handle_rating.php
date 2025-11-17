<?php
session_name('USER_SESSION');
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not authenticated']);
    exit;
}

require_once __DIR__ . '/../config/db.php';

// Helper: check if a column exists in the current database/table
function columnExists($conn, $table, $column) {
    $dbRes = mysqli_query($conn, "SELECT DATABASE() as db");
    $dbRow = $dbRes ? mysqli_fetch_assoc($dbRes) : null;
    $db = $dbRow['db'] ?? '';
    $sql = "SELECT COUNT(*) AS cnt FROM information_schema.columns WHERE table_schema = ? AND table_name = ? AND column_name = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param('sss', $db, $table, $column);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();
        return intval($row['cnt']) > 0;
    }
    return false;
}

// Map expected logical columns to actual columns in ratings table
$table = 'ratings';
$possibleRaterCols   = ['rater_id', 'rater', 'user_id', 'rating_by', 'author_id'];
$possibleItemTypeCols = ['item_type', 'type'];
$possibleItemIdCols   = ['item_id', 'item', 'target_id', 'offer_id'];
$possibleRatingCols   = ['rating', 'score', 'value'];
$possibleReviewCols   = ['review', 'comment', 'notes'];

$found = [];
foreach ($possibleRaterCols as $c)    if (columnExists($conn, $table, $c)) { $found['rater'] = $c; break; }
foreach ($possibleItemTypeCols as $c)  if (columnExists($conn, $table, $c)) { $found['item_type'] = $c; break; }
foreach ($possibleItemIdCols as $c)    if (columnExists($conn, $table, $c)) { $found['item_id'] = $c; break; }
foreach ($possibleRatingCols as $c)    if (columnExists($conn, $table, $c)) { $found['rating'] = $c; break; }
foreach ($possibleReviewCols as $c)    if (columnExists($conn, $table, $c)) { $found['review'] = $c; break; }

// minimal required columns
if (empty($found['rater']) || empty($found['item_type']) || empty($found['item_id']) || empty($found['rating'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Ratings table schema mismatch. Missing expected columns.',
        'found' => $found
    ]);
    exit;
}

// Inputs
$rater_id = intval($_SESSION['user_id']);
$item_type = isset($_POST['item_type']) ? trim($_POST['item_type']) : '';
$item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
$rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
$review = isset($_POST['review']) ? trim($_POST['review']) : '';

if ($item_type === '' || $item_id <= 0 || $rating < 1 || $rating > 5) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input.']);
    exit;
}

// If service, get seller and prevent rating own service
$seller_id = null;
if ($item_type === 'service') {
    $q = "SELECT user_id FROM service_offers WHERE id = ? LIMIT 1";
    if ($stmt = $conn->prepare($q)) {
        $stmt->bind_param("i", $item_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();
        if (!$row) {
            echo json_encode(['status' => 'error', 'message' => 'Service not found.']);
            exit;
        }
        $seller_id = intval($row['user_id']);
    }
}
if (!is_null($seller_id) && $seller_id === $rater_id) {
    echo json_encode(['status' => 'error', 'message' => 'You cannot rate your own service.']);
    exit;
}

// Safe column names
$raterCol = $found['rater'];
$itemTypeCol = $found['item_type'];
$itemIdCol = $found['item_id'];
$ratingCol = $found['rating'];
$reviewCol = $found['review'] ?? null;

// Check existing rating by this user for this item
$checkSql = "SELECT id FROM `$table` WHERE `$itemTypeCol` = ? AND `$itemIdCol` = ? AND `$raterCol` = ? LIMIT 1";
if ($stmt = $conn->prepare($checkSql)) {
    $stmt->bind_param("sii", $item_type, $item_id, $rater_id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($existingId);
        $stmt->fetch();
        $stmt->close();

        // Update existing
        if ($reviewCol) {
            $upd = "UPDATE `$table` SET `$ratingCol` = ?, `$reviewCol` = ?, `updated_at` = NOW() WHERE id = ?";
            if ($ustmt = $conn->prepare($upd)) {
                $ustmt->bind_param("isi", $rating, $review, $existingId);
                $ok = $ustmt->execute();
                $ustmt->close();
                if (!$ok) {
                    echo json_encode(['status' => 'error', 'message' => 'Failed to update rating.']);
                    exit;
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Database error preparing update.']);
                exit;
            }
        } else {
            $upd = "UPDATE `$table` SET `$ratingCol` = ?, `updated_at` = NOW() WHERE id = ?";
            if ($ustmt = $conn->prepare($upd)) {
                $ustmt->bind_param("ii", $rating, $existingId);
                $ok = $ustmt->execute();
                $ustmt->close();
                if (!$ok) {
                    echo json_encode(['status' => 'error', 'message' => 'Failed to update rating.']);
                    exit;
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Database error preparing update.']);
                exit;
            }
        }
    } else {
        $stmt->close();
        // Insert new rating
        if ($reviewCol) {
            $ins = "INSERT INTO `$table` (`$itemTypeCol`, `$itemIdCol`, `$raterCol`, `$ratingCol`, `$reviewCol`, `created_at`) VALUES (?, ?, ?, ?, ?, NOW())";
            if ($istmt = $conn->prepare($ins)) {
                $istmt->bind_param("siiss", $item_type, $item_id, $rater_id, $rating, $review);
                if (!$istmt->execute()) {
                    $istmt->close();
                    echo json_encode(['status' => 'error', 'message' => 'Failed to insert rating.']);
                    exit;
                }
                $istmt->close();
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Database error preparing insert.']);
                exit;
            }
        } else {
            $ins = "INSERT INTO `$table` (`$itemTypeCol`, `$itemIdCol`, `$raterCol`, `$ratingCol`, `created_at`) VALUES (?, ?, ?, ?, NOW())";
            if ($istmt = $conn->prepare($ins)) {
                $istmt->bind_param("siii", $item_type, $item_id, $rater_id, $rating);
                if (!$istmt->execute()) {
                    $istmt->close();
                    echo json_encode(['status' => 'error', 'message' => 'Failed to insert rating.']);
                    exit;
                }
                $istmt->close();
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Database error preparing insert.']);
                exit;
            }
        }
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Database error preparing check.']);
    exit;
}

// Recalculate average and count using the detected rating/item columns
$calc = "SELECT AVG(`$ratingCol`) AS avg_rating, COUNT(*) AS rating_count FROM `$table` WHERE `$itemTypeCol` = ? AND `$itemIdCol` = ?";
if ($cstmt = $conn->prepare($calc)) {
    $cstmt->bind_param("si", $item_type, $item_id);
    $cstmt->execute();
    $res = $cstmt->get_result();
    $row = $res->fetch_assoc();
    $cstmt->close();
    $avg = isset($row['avg_rating']) ? round(floatval($row['avg_rating']), 2) : 0;
    $count = isset($row['rating_count']) ? intval($row['rating_count']) : 0;

    echo json_encode([
        'status' => 'success',
        'message' => 'Rating saved.',
        'avg_rating' => $avg,
        'rating_count' => $count
    ]);
    exit;
} else {
    echo json_encode(['status' => 'success', 'message' => 'Rating saved.']);
    exit;
}
?>
