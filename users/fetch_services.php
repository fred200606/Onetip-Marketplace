<?php
session_start();
include '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

// Get filter parameters
$sort = $_GET['sort'] ?? 'newest';
$category = $_GET['category'] ?? 'all';
$price = $_GET['price'] ?? 'all';
$delivery = $_GET['delivery'] ?? 'all';
$search = $_GET['search'] ?? '';

// Base query
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
        (SELECT COUNT(*) FROM vouches WHERE seller_id = s.user_id) as seller_vouches,
        (SELECT AVG(rating) FROM ratings WHERE item_id = s.id AND item_type = 'service') as avg_rating,
        (SELECT COUNT(*) FROM ratings WHERE item_id = s.id AND item_type = 'service') as rating_count
    FROM service_offers s
    JOIN userdata u ON s.user_id = u.id
    WHERE s.status = 'available'
";

// Apply category filter
if ($category !== 'all') {
    $query .= " AND s.serviceCategory = '" . mysqli_real_escape_string($conn, $category) . "'";
}

// Apply price range filter
if ($price !== 'all') {
    switch ($price) {
        case '0-50':
            $query .= " AND s.startingPrice BETWEEN 0 AND 50";
            break;
        case '50-100':
            $query .= " AND s.startingPrice BETWEEN 50 AND 100";
            break;
        case '100-200':
            $query .= " AND s.startingPrice BETWEEN 100 AND 200";
            break;
        case '200-500':
            $query .= " AND s.startingPrice BETWEEN 200 AND 500";
            break;
        case '500+':
            $query .= " AND s.startingPrice >= 500";
            break;
    }
}

// Apply delivery time filter
if ($delivery !== 'all') {
    $query .= " AND s.serviceDuration = '" . mysqli_real_escape_string($conn, $delivery) . "'";
}

// Apply search filter
if (!empty($search)) {
    $searchTerm = mysqli_real_escape_string($conn, $search);
    $query .= " AND (s.serviceTitle LIKE '%$searchTerm%' OR s.serviceDescription LIKE '%$searchTerm%')";
}

// Apply sorting
switch ($sort) {
    case 'oldest':
        $query .= " ORDER BY s.posted_at ASC";
        break;
    case 'price_low':
        $query .= " ORDER BY s.startingPrice ASC";
        break;
    case 'price_high':
        $query .= " ORDER BY s.startingPrice DESC";
        break;
    case 'rating':
        $query .= " ORDER BY seller_vouches DESC";
        break;
    case 'popular':
        $query .= " ORDER BY seller_vouches DESC, s.posted_at DESC";
        break;
    case 'newest':
    default:
        $query .= " ORDER BY s.posted_at DESC";
        break;
}

$query .= " LIMIT 12";

$result = mysqli_query($conn, $query);

if (!$result) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . mysqli_error($conn)]);
    exit();
}

$services = [];
while ($row = mysqli_fetch_assoc($result)) {
    $services[] = $row;
}

echo json_encode([
    'status' => 'success',
    'services' => $services,
    'count' => count($services)
]);

mysqli_free_result($result);
mysqli_close($conn);
?>
