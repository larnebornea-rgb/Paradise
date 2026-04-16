<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'book':
        bookCottage();
        break;
    case 'my_bookings':
        myBookings();
        break;
    case 'cancel':
        cancelBooking();
        break;
    case 'all':
        allBookings();
        break;
    case 'confirm':
        confirmBooking();
        break;
    case 'availability':
        checkAvailability();
        break;
    case 'stats':
        getStats();
        break;
    default:
        echo json_encode(['error' => 'Invalid action']);
}

function bookCottage() {
    $cottage_id = (int)($_POST['cottage_id'] ?? 0);
    $check_in = $_POST['check_in'] ?? '';
    $check_out = $_POST['check_out'] ?? '';

    if (!$cottage_id || !$check_in || !$check_out) {
        echo json_encode(['success' => false, 'message' => 'Missing booking details']);
        return;
    }

    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM cottages WHERE id = ? AND is_available = 1");
    $stmt->bind_param('i', $cottage_id);
    $stmt->execute();
    $cottage = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$cottage) {
        echo json_encode(['success' => false, 'message' => 'Cottage not found']);
        $db->close();
        return;
    }

    try {
        $in = new DateTime($check_in);
        $out = new DateTime($check_out);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Invalid dates']);
        $db->close();
        return;
    }

    $days = (int)$in->diff($out)->days;
    if ($out <= $in || $days <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid dates']);
        $db->close();
        return;
    }

    $overlap = $db->prepare("
        SELECT id
        FROM reservations
        WHERE cottage_id = ?
          AND status IN ('pending', 'confirmed')
          AND check_in < ?
          AND check_out > ?
        LIMIT 1
    ");
    $overlap->bind_param('iss', $cottage_id, $check_out, $check_in);
    $overlap->execute();
    $overlap->store_result();

    if ($overlap->num_rows > 0) {
        $overlap->close();
        echo json_encode(['success' => false, 'message' => 'Selected dates are no longer available']);
        $db->close();
        return;
    }
    $overlap->close();

    $total = (float)$cottage['price'] * $days;
    $booking_id = 'BKG-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
    $user_id = $_SESSION['user_id'];

    $stmt = $db->prepare("INSERT INTO reservations (booking_id, user_id, cottage_id, check_in, check_out, total_amount) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('siissd', $booking_id, $user_id, $cottage_id, $check_in, $check_out, $total);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'booking_id' => $booking_id, 'total' => $total]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Booking failed']);
    }
    $stmt->close();
    $db->close();
}

function myBookings() {
    $user_id = $_SESSION['user_id'];
    $db = getDB();
    $stmt = $db->prepare("
        SELECT r.*, c.name AS cottage_name, c.type AS cottage_type, c.image_url
        FROM reservations r
        JOIN cottages c ON r.cottage_id = c.id
        WHERE r.user_id = ?
        ORDER BY r.created_at DESC
    ");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $bookings = [];
    while ($row = $result->fetch_assoc()) $bookings[] = $row;
    $stmt->close();
    $db->close();
    echo json_encode(['success' => true, 'bookings' => $bookings]);
}

function cancelBooking() {
    $booking_id = $_POST['booking_id'] ?? '';
    $user_id = $_SESSION['user_id'];
    $db = getDB();

    if ($_SESSION['role'] === 'admin') {
        $stmt = $db->prepare("UPDATE reservations SET status='cancelled' WHERE booking_id=?");
        $stmt->bind_param('s', $booking_id);
    } else {
        $stmt = $db->prepare("UPDATE reservations SET status='cancelled' WHERE booking_id=? AND user_id=?");
        $stmt->bind_param('si', $booking_id, $user_id);
    }

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Could not cancel booking']);
    }
    $stmt->close();
    $db->close();
}

function allBookings() {
    if ($_SESSION['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    $db = getDB();
    $result = $db->query("
        SELECT r.*, c.name AS cottage_name, c.type AS cottage_type, u.full_name AS customer_name, u.email AS customer_email
        FROM reservations r
        JOIN cottages c ON r.cottage_id = c.id
        JOIN users u ON r.user_id = u.id
        ORDER BY r.created_at DESC
    ");
    $bookings = [];
    while ($row = $result->fetch_assoc()) $bookings[] = $row;
    $db->close();
    echo json_encode(['success' => true, 'bookings' => $bookings]);
}

function confirmBooking() {
    if ($_SESSION['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    $booking_id = $_POST['booking_id'] ?? '';
    $db = getDB();
    $stmt = $db->prepare("UPDATE reservations SET status='confirmed' WHERE booking_id=?");
    $stmt->bind_param('s', $booking_id);
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Could not confirm booking']);
    }
    $stmt->close();
    $db->close();
}

function checkAvailability() {
    $cottage_id = (int)($_POST['cottage_id'] ?? $_GET['cottage_id'] ?? 0);
    $check_in = $_POST['check_in'] ?? $_GET['check_in'] ?? '';
    $check_out = $_POST['check_out'] ?? $_GET['check_out'] ?? '';

    if ($cottage_id <= 0 || $check_in === '' || $check_out === '') {
        echo json_encode(['success' => false, 'message' => 'Missing availability details']);
        return;
    }

    try {
        $in = new DateTime($check_in);
        $out = new DateTime($check_out);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Invalid dates']);
        return;
    }

    if ($out <= $in) {
        echo json_encode(['success' => false, 'message' => 'Invalid dates']);
        return;
    }

    $db = getDB();
    $stmt = $db->prepare("
        SELECT COUNT(*) AS overlap_count
        FROM reservations
        WHERE cottage_id = ?
          AND status IN ('pending', 'confirmed')
          AND check_in < ?
          AND check_out > ?
    ");
    $stmt->bind_param('iss', $cottage_id, $check_out, $check_in);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $db->close();

    $available = ((int)($result['overlap_count'] ?? 0)) === 0;

    echo json_encode([
        'success' => true,
        'available' => $available,
        'status' => $available ? 'available' : 'unavailable',
        'message' => $available ? 'Selected dates are available' : 'Selected dates are unavailable'
    ]);
}

function getStats() {
    if ($_SESSION['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    $db = getDB();
    $stats = [];

    $r = $db->query("SELECT COUNT(*) as total FROM reservations");
    $stats['total'] = $r->fetch_assoc()['total'];

    $r = $db->query("SELECT COUNT(*) as pending FROM reservations WHERE status='pending'");
    $stats['pending'] = $r->fetch_assoc()['pending'];

    $r = $db->query("SELECT COUNT(*) as confirmed FROM reservations WHERE status='confirmed'");
    $stats['confirmed'] = $r->fetch_assoc()['confirmed'];

    $r = $db->query("SELECT COUNT(*) as cancelled FROM reservations WHERE status='cancelled'");
    $stats['cancelled'] = $r->fetch_assoc()['cancelled'];

    $r = $db->query("SELECT COALESCE(SUM(total_amount),0) as revenue FROM reservations WHERE status='confirmed'");
    $stats['revenue'] = $r->fetch_assoc()['revenue'];

    $r = $db->query("SELECT COUNT(*) as today FROM reservations WHERE DATE(created_at)=CURDATE()");
    $stats['today'] = $r->fetch_assoc()['today'];

    $r = $db->query("SELECT type, COUNT(*) as count FROM cottages GROUP BY type");
    $stats['cottages'] = [];
    while ($row = $r->fetch_assoc()) $stats['cottages'][] = $row;

    $db->close();
    echo json_encode(['success' => true, 'stats' => $stats]);
}
?>
