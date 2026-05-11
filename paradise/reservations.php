<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'book':
        requireLogin();
        bookCottage(false, false);
        break;
    case 'book_guest':
        bookCottage(true, false);
        break;
    case 'walk_in_book':
        requireAdmin();
        bookCottage(true, true);
        break;
    case 'my_bookings':
        requireLogin();
        myBookings();
        break;
    case 'cancel':
        requireLogin();
        cancelBooking();
        break;
    case 'all':
        requireAdmin();
        allBookings();
        break;
    case 'confirm':
        requireAdmin();
        confirmBooking();
        break;
    case 'availability':
        checkAvailability();
        break;
    case 'stats':
        requireAdmin();
        getStats();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function requireLogin(): void {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
        exit;
    }
}

function requireAdmin(): void {
    requireLogin();
    if (($_SESSION['role'] ?? '') !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
}

function fetchCottage(mysqli $db, int $cottageId): ?array {
    $stmt = $db->prepare("SELECT * FROM cottages WHERE id = ? AND is_available = 1 LIMIT 1");
    $stmt->bind_param('i', $cottageId);
    $stmt->execute();
    $cottage = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();
    return $cottage;
}

function validateDateTime(string $date, string $time): ?DateTime {
    if ($date === '') {
        return null;
    }

    $candidate = trim($date . ' ' . ($time !== '' ? $time : '00:00'));
    $formats = ['Y-m-d H:i', 'Y-m-d H:i:s'];

    foreach ($formats as $format) {
        $parsed = DateTime::createFromFormat($format, $candidate);
        if ($parsed instanceof DateTime) {
            return $parsed;
        }
    }

    try {
        return new DateTime($candidate);
    } catch (Exception $exception) {
        return null;
    }
}

function calculateBookingUnits(array $cottage, DateTime $checkIn, DateTime $checkOut): int {
    $unit = $cottage['pricing_unit'] ?? 'night';
    if ($unit === 'event') {
        return 1;
    }

    $days = (int)$checkIn->diff($checkOut)->days;
    return max(1, $days);
}

function hasOverlap(mysqli $db, int $cottageId, string $checkInDate, string $checkOutDate): bool {
    $stmt = $db->prepare("
        SELECT COUNT(*) AS overlap_count
        FROM reservations
        WHERE cottage_id = ?
          AND status IN ('pending', 'confirmed')
          AND check_in < ?
          AND check_out > ?
    ");
    $stmt->bind_param('iss', $cottageId, $checkOutDate, $checkInDate);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return ((int)($row['overlap_count'] ?? 0)) > 0;
}

function createGuestUser(mysqli $db, string $fullName, string $email, string $phone): array {
    $cleanEmail = trim($email);
    if ($cleanEmail !== '' && filter_var($cleanEmail, FILTER_VALIDATE_EMAIL)) {
        $stmt = $db->prepare("SELECT id, full_name, email FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param('s', $cleanEmail);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($existing) {
            return [
                'user_id' => (int)$existing['id'],
                'email' => $existing['email'],
                'name' => $existing['full_name']
            ];
        }
    } else {
        $cleanEmail = 'guest.' . time() . '.' . random_int(100, 999) . '@guest.paradise.local';
    }

    $password = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
    $displayName = $fullName !== '' ? $fullName : 'Guest Customer';
    $stmt = $db->prepare("
        INSERT INTO users (full_name, email, password, role, account_status, display_theme, phone, is_guest)
        VALUES (?, ?, ?, 'customer', 'active', 'teal', ?, 1)
    ");
    $stmt->bind_param('ssss', $displayName, $cleanEmail, $password, $phone);
    $stmt->execute();
    $userId = (int)$stmt->insert_id;
    $stmt->close();

    return [
        'user_id' => $userId,
        'email' => $cleanEmail,
        'name' => $displayName
    ];
}

function bookCottage(bool $allowGuest, bool $isWalkIn): void {
    $cottageId = (int)($_POST['cottage_id'] ?? 0);
    $checkInDate = trim($_POST['check_in'] ?? '');
    $checkOutDate = trim($_POST['check_out'] ?? '');
    $checkInTime = trim($_POST['check_in_time'] ?? '14:00');
    $checkOutTime = trim($_POST['check_out_time'] ?? '12:00');
    $guestCount = max(1, (int)($_POST['guest_count'] ?? 1));
    $guestName = trim($_POST['guest_name'] ?? ($_SESSION['full_name'] ?? ''));
    $guestEmail = trim($_POST['guest_email'] ?? ($_SESSION['email'] ?? ''));
    $guestPhone = trim($_POST['guest_phone'] ?? ($_SESSION['phone'] ?? ''));

    if ($cottageId <= 0 || $checkInDate === '' || $checkOutDate === '') {
        echo json_encode(['success' => false, 'message' => 'Missing booking details']);
        return;
    }

    if (!$allowGuest && !isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Please sign in before booking']);
        return;
    }

    if ($allowGuest && $guestName === '') {
        echo json_encode(['success' => false, 'message' => 'Guest name is required']);
        return;
    }

    $db = getDB();
    $cottage = fetchCottage($db, $cottageId);
    if (!$cottage) {
        $db->close();
        echo json_encode(['success' => false, 'message' => 'Cottage not found']);
        return;
    }

    if ($guestCount > (int)$cottage['capacity']) {
        $db->close();
        echo json_encode(['success' => false, 'message' => 'Guest count exceeds cottage capacity']);
        return;
    }

    $checkIn = validateDateTime($checkInDate, $checkInTime);
    $checkOut = validateDateTime($checkOutDate, $checkOutTime);
    if (!$checkIn || !$checkOut || $checkOut <= $checkIn) {
        $db->close();
        echo json_encode(['success' => false, 'message' => 'Invalid booking dates or times']);
        return;
    }

    if (hasOverlap($db, $cottageId, $checkInDate, $checkOutDate)) {
        $db->close();
        echo json_encode(['success' => false, 'message' => 'Selected dates are unavailable']);
        return;
    }

    $units = calculateBookingUnits($cottage, $checkIn, $checkOut);
    $total = (float)$cottage['price'] * $units;
    $downPayment = round($total * 0.30, 2);
    $bookingId = 'BKG-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
    $paymentMethod = 'GCash QR';

    if ($allowGuest) {
        $guestUser = createGuestUser($db, $guestName, $guestEmail, $guestPhone);
        $userId = $guestUser['user_id'];
        $guestEmail = $guestUser['email'];
        $guestName = $guestUser['name'];
    } else {
        $userId = (int)$_SESSION['user_id'];
    }

    $stmt = $db->prepare("
        INSERT INTO reservations (
            booking_id, user_id, cottage_id, check_in, check_out, total_amount, status,
            guest_name, guest_email, guest_phone, guest_count, check_in_time, check_out_time,
            down_payment, payment_method, is_walk_in
        ) VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $walkIn = $isWalkIn ? 1 : 0;
    $stmt->bind_param(
        'siissdsssissdsi',
        $bookingId,
        $userId,
        $cottageId,
        $checkInDate,
        $checkOutDate,
        $total,
        $guestName,
        $guestEmail,
        $guestPhone,
        $guestCount,
        $checkInTime,
        $checkOutTime,
        $downPayment,
        $paymentMethod,
        $walkIn
    );

    $ok = $stmt->execute();
    $stmt->close();
    $db->close();

    echo json_encode($ok
        ? [
            'success' => true,
            'booking_id' => $bookingId,
            'total' => $total,
            'down_payment' => $downPayment,
            'message' => $isWalkIn ? 'Walk-in booking created successfully' : 'Booking created successfully'
        ]
        : ['success' => false, 'message' => 'Booking failed']
    );
}

function myBookings(): void {
    $userId = (int)$_SESSION['user_id'];
    $db = getDB();
    $stmt = $db->prepare("
        SELECT
            r.*,
            c.name AS cottage_name,
            c.type AS cottage_type,
            c.image_url,
            COALESCE(NULLIF(r.guest_name, ''), u.full_name) AS display_guest_name
        FROM reservations r
        JOIN cottages c ON r.cottage_id = c.id
        JOIN users u ON r.user_id = u.id
        WHERE r.user_id = ?
        ORDER BY r.created_at DESC
    ");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $bookings = [];
    while ($row = $result->fetch_assoc()) {
        $bookings[] = $row;
    }
    $stmt->close();
    $db->close();

    echo json_encode(['success' => true, 'bookings' => $bookings]);
}

function cancelBooking(): void {
    $bookingId = trim($_POST['booking_id'] ?? '');
    $db = getDB();

    if (($_SESSION['role'] ?? '') === 'admin') {
        $stmt = $db->prepare("UPDATE reservations SET status = 'cancelled' WHERE booking_id = ?");
        $stmt->bind_param('s', $bookingId);
    } else {
        $userId = (int)$_SESSION['user_id'];
        $stmt = $db->prepare("UPDATE reservations SET status = 'cancelled' WHERE booking_id = ? AND user_id = ?");
        $stmt->bind_param('si', $bookingId, $userId);
    }

    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();
    $db->close();

    echo json_encode($affected > 0
        ? ['success' => true]
        : ['success' => false, 'message' => 'Could not cancel booking']
    );
}

function allBookings(): void {
    $db = getDB();
    $result = $db->query("
        SELECT
            r.*,
            c.name AS cottage_name,
            c.type AS cottage_type,
            u.full_name AS customer_name,
            u.email AS customer_email
        FROM reservations r
        JOIN cottages c ON r.cottage_id = c.id
        JOIN users u ON r.user_id = u.id
        ORDER BY r.created_at DESC
    ");
    $bookings = [];
    while ($row = $result->fetch_assoc()) {
        $bookings[] = $row;
    }
    $db->close();

    echo json_encode(['success' => true, 'bookings' => $bookings]);
}

function confirmBooking(): void {
    $bookingId = trim($_POST['booking_id'] ?? '');
    $db = getDB();
    $stmt = $db->prepare("UPDATE reservations SET status = 'confirmed' WHERE booking_id = ?");
    $stmt->bind_param('s', $bookingId);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();
    $db->close();

    echo json_encode($affected > 0
        ? ['success' => true]
        : ['success' => false, 'message' => 'Could not confirm booking']
    );
}

function checkAvailability(): void {
    $cottageId = (int)($_POST['cottage_id'] ?? $_GET['cottage_id'] ?? 0);
    $checkIn = trim($_POST['check_in'] ?? $_GET['check_in'] ?? '');
    $checkOut = trim($_POST['check_out'] ?? $_GET['check_out'] ?? '');
    $guestCount = max(1, (int)($_POST['guest_count'] ?? $_GET['guest_count'] ?? 1));

    if ($cottageId <= 0 || $checkIn === '' || $checkOut === '') {
        echo json_encode(['success' => false, 'message' => 'Missing availability details']);
        return;
    }

    $inDate = validateDateTime($checkIn, '00:00');
    $outDate = validateDateTime($checkOut, '00:00');
    if (!$inDate || !$outDate || $outDate <= $inDate) {
        echo json_encode(['success' => false, 'message' => 'Invalid dates']);
        return;
    }

    $db = getDB();
    $cottage = fetchCottage($db, $cottageId);
    if (!$cottage) {
        $db->close();
        echo json_encode(['success' => false, 'message' => 'Cottage not found']);
        return;
    }

    if ($guestCount > (int)$cottage['capacity']) {
        $db->close();
        echo json_encode(['success' => true, 'available' => false, 'status' => 'unavailable', 'message' => 'Guest count exceeds cottage capacity']);
        return;
    }

    $available = !hasOverlap($db, $cottageId, $checkIn, $checkOut);
    $db->close();

    echo json_encode([
        'success' => true,
        'available' => $available,
        'status' => $available ? 'available' : 'unavailable',
        'message' => $available ? 'Selected dates are available' : 'Selected dates are unavailable'
    ]);
}

function getStats(): void {
    $db = getDB();
    $stats = [];

    $result = $db->query("SELECT COUNT(*) AS total FROM reservations");
    $stats['total'] = $result->fetch_assoc()['total'] ?? 0;

    $result = $db->query("SELECT COUNT(*) AS pending FROM reservations WHERE status = 'pending'");
    $stats['pending'] = $result->fetch_assoc()['pending'] ?? 0;

    $result = $db->query("SELECT COUNT(*) AS confirmed FROM reservations WHERE status = 'confirmed'");
    $stats['confirmed'] = $result->fetch_assoc()['confirmed'] ?? 0;

    $result = $db->query("SELECT COUNT(*) AS cancelled FROM reservations WHERE status = 'cancelled'");
    $stats['cancelled'] = $result->fetch_assoc()['cancelled'] ?? 0;

    $result = $db->query("SELECT COALESCE(SUM(total_amount), 0) AS revenue FROM reservations WHERE status = 'confirmed'");
    $stats['revenue'] = $result->fetch_assoc()['revenue'] ?? 0;

    $result = $db->query("SELECT COUNT(*) AS today FROM reservations WHERE DATE(created_at) = CURDATE()");
    $stats['today'] = $result->fetch_assoc()['today'] ?? 0;

    $result = $db->query("SELECT type, COUNT(*) AS count FROM cottages WHERE is_available = 1 GROUP BY type");
    $stats['cottages'] = [];
    while ($row = $result->fetch_assoc()) {
        $stats['cottages'][] = $row;
    }

    $db->close();
    echo json_encode(['success' => true, 'stats' => $stats]);
}
?>
