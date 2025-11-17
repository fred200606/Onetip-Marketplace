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
    $db = mysqli_real_escape_string($conn, mysqli_query($conn, "SELECT DATABASE()")->fetch_row()[0]);
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

// Map expected logical columns to actual columns in vouches table
$table = 'vouches';
$possibleSellerCols = ['seller_id', 'seller', 'target_id', 'target_user_id', 'user_id'];
$possibleBuyerCols  = ['buyer_id', 'buyer', 'from_id', 'from_user_id', 'user_id', 'vouched_by'];
$possibleItemTypeCols = ['item_type', 'type'];
$possibleItemIdCols   = ['item_id', 'item', 'target_item_id', 'offer_id'];

$found = [];

// find seller column
foreach ($possibleSellerCols as $c) {
    if (columnExists($conn, $table, $c)) { $found['seller'] = $c; break; }
}
// find buyer column
foreach ($possibleBuyerCols as $c) {
    if (columnExists($conn, $table, $c)) { $found['buyer'] = $c; break; }
}
// find item_type column
foreach ($possibleItemTypeCols as $c) {
    if (columnExists($conn, $table, $c)) { $found['item_type'] = $c; break; }
}
// find item_id column
foreach ($possibleItemIdCols as $c) {
    if (columnExists($conn, $table, $c)) { $found['item_id'] = $c; break; }
}

if (empty($found['seller']) || empty($found['buyer']) || empty($found['item_type']) || empty($found['item_id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Vouch table schema mismatch. Missing expected columns in vouches table.',
        'missing' => [
            'seller' => $found['seller'] ?? null,
            'buyer'  => $found['buyer'] ?? null,
            'item_type' => $found['item_type'] ?? null,
            'item_id'   => $found['item_id'] ?? null
        ]
    ]);
    exit;
}

// Input
$buyer_id = intval($_SESSION['user_id']);
$seller_id = isset($_POST['seller_id']) ? intval($_POST['seller_id']) : 0;
$item_type = isset($_POST['item_type']) ? trim($_POST['item_type']) : '';
$item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;

if (!$seller_id || $buyer_id === $seller_id) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid seller or cannot vouch for yourself.']);
    exit;
}

if ($item_type === '') $item_type = 'service'; // default

// Build safe SQL using validated column names (they are not user input)
$selCol = $found['seller'];
$buyCol = $found['buyer'];
$typeCol = $found['item_type'];
$itemCol = $found['item_id'];

// Check duplicate
$checkSql = "SELECT id FROM `$table` WHERE `$selCol` = ? AND `$buyCol` = ? AND `$typeCol` = ? AND `$itemCol` = ? LIMIT 1";
if ($stmt = $conn->prepare($checkSql)) {
    $stmt->bind_param("iisi", $seller_id, $buyer_id, $item_type, $item_id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->close();
        echo json_encode(['status' => 'error', 'message' => 'You already vouched for this provider.']);
        exit;
    }
    $stmt->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Database error preparing duplicate check.']);
    exit;
}

// Insert vouch
$now = date('Y-m-d H:i:s');
$insSql = "INSERT INTO `$table` (`$selCol`, `$buyCol`, `$typeCol`, `$itemCol`, `created_at`) VALUES (?, ?, ?, ?, ?)";
if ($stmt = $conn->prepare($insSql)) {
    $stmt->bind_param("iisis", $seller_id, $buyer_id, $item_type, $item_id, $now);
    if ($stmt->execute()) {
        $stmt->close();
        // Recalculate total vouches for this seller
        $cntSql = "SELECT COUNT(*) AS total FROM `$table` WHERE `$selCol` = ?";
        if ($cstmt = $conn->prepare($cntSql)) {
            $cstmt->bind_param("i", $seller_id);
            $cstmt->execute();
            $res = $cstmt->get_result();
            $row = $res->fetch_assoc();
            $total = intval($row['total'] ?? 0);
            $cstmt->close();

            echo json_encode(['status' => 'success', 'message' => 'Vouch added', 'total_vouches' => $total]);
            exit;
        } else {
            echo json_encode(['status' => 'success', 'message' => 'Vouch added', 'total_vouches' => 0]);
            exit;
        }
    } else {
        $stmt->close();
        echo json_encode(['status' => 'error', 'message' => 'Failed to add vouch.']);
        exit;
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Database error preparing insert.']);
    exit;
}
?>
