<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? 'list';

switch ($action) {
    case 'list':
        listCottages();
        break;
    case 'get':
        getCottage($_GET['id'] ?? 0);
        break;
    case 'add':
        addCottage();
        break;
    case 'update':
        updateCottage();
        break;
    case 'delete':
        deleteCottage();
        break;
    default:
        echo json_encode(['error' => 'Invalid action']);
}

function listCottages() {
    $db = getDB();
    syncCottageImages($db);
    seedAdditionalCottages($db);

    $result = $db->query("SELECT * FROM cottages WHERE is_available = 1 ORDER BY id ASC");
    $cottages = [];
    while ($row = $result->fetch_assoc()) {
        $cottages[] = $row;
    }
    $db->close();
    echo json_encode(['success' => true, 'cottages' => $cottages]);
}

function getCottage($id) {
    $db = getDB();
    syncCottageImages($db);

    $stmt = $db->prepare("SELECT * FROM cottages WHERE id = ? AND is_available = 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $cottage = $result->fetch_assoc();
    $stmt->close();
    $db->close();

    if ($cottage) {
        echo json_encode(['success' => true, 'cottage' => $cottage]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Cottage not found']);
    }
}

function ensureAdmin() {
    if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
}

function normalizeType(string $type): string {
    $valid = ['Small', 'Medium', 'Large', 'Family'];
    return in_array($type, $valid, true) ? $type : '';
}

function normalizeUnit(string $unit): string {
    $valid = ['day', 'night', 'event'];
    return in_array($unit, $valid, true) ? $unit : '';
}

function addCottage() {
    ensureAdmin();

    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $type = normalizeType(trim($_POST['type'] ?? ''));
    $capacity = (int)($_POST['capacity'] ?? 0);
    $price = (float)($_POST['price'] ?? 0);
    $pricingUnit = normalizeUnit(trim($_POST['pricing_unit'] ?? ''));
    $amenities = trim($_POST['amenities'] ?? '');
    $imageUrl = trim($_POST['image_url'] ?? '');

    if ($name === '' || $type === '' || $capacity <= 0 || $price <= 0 || $pricingUnit === '') {
        echo json_encode(['success' => false, 'message' => 'Invalid cottage details']);
        return;
    }

    $db = getDB();
    $check = $db->prepare("SELECT id FROM cottages WHERE name = ? LIMIT 1");
    $check->bind_param('s', $name);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $check->close();
        $db->close();
        echo json_encode(['success' => false, 'message' => 'Cottage name already exists']);
        return;
    }
    $check->close();

    $stmt = $db->prepare("
        INSERT INTO cottages (name, description, type, capacity, price, pricing_unit, amenities, image_url, is_available)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
    ");
    $stmt->bind_param('sssidsss', $name, $description, $type, $capacity, $price, $pricingUnit, $amenities, $imageUrl);
    $ok = $stmt->execute();
    $stmt->close();
    $db->close();

    echo json_encode($ok
        ? ['success' => true, 'message' => 'Cottage added']
        : ['success' => false, 'message' => 'Failed to add cottage']
    );
}

function updateCottage() {
    ensureAdmin();

    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $type = normalizeType(trim($_POST['type'] ?? ''));
    $capacity = (int)($_POST['capacity'] ?? 0);
    $price = (float)($_POST['price'] ?? 0);
    $pricingUnit = normalizeUnit(trim($_POST['pricing_unit'] ?? ''));
    $amenities = trim($_POST['amenities'] ?? '');
    $imageUrl = trim($_POST['image_url'] ?? '');
    $isAvailable = isset($_POST['is_available']) ? (int)$_POST['is_available'] : 1;
    $isAvailable = $isAvailable === 0 ? 0 : 1;

    if ($id <= 0 || $name === '' || $type === '' || $capacity <= 0 || $price <= 0 || $pricingUnit === '') {
        echo json_encode(['success' => false, 'message' => 'Invalid cottage details']);
        return;
    }

    $db = getDB();
    $check = $db->prepare("SELECT id FROM cottages WHERE name = ? AND id <> ? LIMIT 1");
    $check->bind_param('si', $name, $id);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $check->close();
        $db->close();
        echo json_encode(['success' => false, 'message' => 'Cottage name already used']);
        return;
    }
    $check->close();

    $stmt = $db->prepare("
        UPDATE cottages
        SET name = ?, description = ?, type = ?, capacity = ?, price = ?, pricing_unit = ?, amenities = ?, image_url = ?, is_available = ?
        WHERE id = ?
    ");
    $stmt->bind_param('sssidsssii', $name, $description, $type, $capacity, $price, $pricingUnit, $amenities, $imageUrl, $isAvailable, $id);
    $ok = $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();
    $db->close();

    echo json_encode(($ok && $affected >= 0)
        ? ['success' => true, 'message' => 'Cottage updated']
        : ['success' => false, 'message' => 'Failed to update cottage']
    );
}

function deleteCottage() {
    ensureAdmin();

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid cottage id']);
        return;
    }

    $db = getDB();
    $stmt = $db->prepare("UPDATE cottages SET is_available = 0 WHERE id = ?");
    $stmt->bind_param('i', $id);
    $ok = $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();
    $db->close();

    echo json_encode(($ok && $affected > 0)
        ? ['success' => true, 'message' => 'Cottage deleted']
        : ['success' => false, 'message' => 'Could not delete cottage']
    );
}

function syncCottageImages(mysqli $db) {
    $imageMap = [
        'Paradise Nook Cottage' => resolveCottageImage(
            'paradise-nook-cottage.jpg',
            'https://images.unsplash.com/photo-1564078516393-cf04bd966897?w=1200'
        ),
        'Paradise Family Haven' => resolveCottageImage(
            'paradise-family-haven.jpg',
            'https://images.unsplash.com/photo-1570214476695-19bd4c8f48d0?w=1200'
        ),
        'Paradise Barkada Villa' => resolveCottageImage(
            'paradise-barkada-villa.jpg',
            'https://images.unsplash.com/photo-1580587771525-78b9dba3b914?w=1200'
        ),
        'Paradise Mini Kubo' => resolveCottageImage(
            'paradise-mini-kubo.jpg',
            'https://images.unsplash.com/photo-1576941089067-2de3c901e126?w=1200'
        ),
        'Paradise Comfort Kubo' => resolveCottageImage(
            'paradise-comfort-kubo.jpg',
            'https://images.unsplash.com/photo-1572120360610-d971b9d7767c?w=1200'
        ),
        'Paradise Grand Hall' => resolveCottageImage(
            'paradise-grand-hall.jpg',
            'https://images.unsplash.com/photo-1519167758481-83f550bb49b3?w=1200'
        ),
        'Paradise Sunset Loft' => resolveCottageImage(
            'paradise-sunset-loft.jpg',
            'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?w=1200'
        ),
        'Paradise Bamboo Retreat' => resolveCottageImage(
            'paradise-bamboo-retreat.jpg',
            'https://images.unsplash.com/photo-1554995207-c18c203602cb?w=1200'
        ),
        'Paradise Ocean View Suite' => resolveCottageImage(
            'paradise-ocean-view-suite.jpg',
            'https://images.unsplash.com/photo-1499793983690-e29da59ef1c2?w=1200'
        ),
        'Paradise Garden Villa' => resolveCottageImage(
            'paradise-garden-villa.jpg',
            'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?w=1200'
        ),
        'Paradise Poolside Haven' => resolveCottageImage(
            'paradise-poolside-haven.jpg',
            'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=1200'
        )
    ];

    $stmt = $db->prepare("UPDATE cottages SET image_url = ? WHERE name = ?");
    foreach ($imageMap as $name => $imageUrl) {
        $stmt->bind_param('ss', $imageUrl, $name);
        $stmt->execute();
    }
    $stmt->close();
}

function resolveCottageImage(string $fileName, string $remoteUrl): string {
    $baseName = preg_replace('/\.(jpg|jpeg|webp)$/i', '', $fileName);
    $candidates = [
        $baseName . '.jpg',
        $baseName . '.jpeg',
        $baseName . '.webp'
    ];

    foreach ($candidates as $candidate) {
        $localDiskPath = __DIR__ . '/assets/cottages/' . $candidate;
        if (file_exists($localDiskPath)) {
            return '/paradise/assets/cottages/' . $candidate;
        }
    }

    return $remoteUrl;
}

function seedAdditionalCottages(mysqli $db) {
    $newCottages = [
        [
            'name' => 'Paradise Sunset Loft',
            'description' => 'Loft-style cottage with sunset deck view',
            'type' => 'Medium',
            'capacity' => 4,
            'price' => 6500.00,
            'pricing_unit' => 'night',
            'amenities' => 'WiFi, AC, Balcony, Coffee Station',
            'image_url' => '/paradise/assets/cottages/paradise-sunset-loft.jpg'
        ],
        [
            'name' => 'Paradise Bamboo Retreat',
            'description' => 'Nature-inspired bamboo cottage near the garden',
            'type' => 'Small',
            'capacity' => 2,
            'price' => 3800.00,
            'pricing_unit' => 'night',
            'amenities' => 'Fan, Garden View, Hammock, Mini Bar',
            'image_url' => '/paradise/assets/cottages/paradise-bamboo-retreat.jpg'
        ],
        [
            'name' => 'Paradise Ocean View Suite',
            'description' => 'Premium suite with full ocean-facing windows',
            'type' => 'Family',
            'capacity' => 8,
            'price' => 14500.00,
            'pricing_unit' => 'night',
            'amenities' => 'WiFi, AC, Ocean View, Kitchenette, Bathtub',
            'image_url' => '/paradise/assets/cottages/paradise-ocean-view-suite.jpg'
        ],
        [
            'name' => 'Paradise Garden Villa',
            'description' => 'Private villa surrounded by tropical greens',
            'type' => 'Large',
            'capacity' => 10,
            'price' => 17000.00,
            'pricing_unit' => 'night',
            'amenities' => 'WiFi, AC, Private Garden, BBQ Area, Parking',
            'image_url' => '/paradise/assets/cottages/paradise-garden-villa.jpg'
        ],
        [
            'name' => 'Paradise Poolside Haven',
            'description' => 'Relaxing cottage with direct poolside access',
            'type' => 'Medium',
            'capacity' => 5,
            'price' => 7800.00,
            'pricing_unit' => 'night',
            'amenities' => 'WiFi, AC, Pool Access, Outdoor Seating',
            'image_url' => '/paradise/assets/cottages/paradise-poolside-haven.jpg'
        ]
    ];

    $check = $db->prepare("SELECT id FROM cottages WHERE name = ? LIMIT 1");
    $insert = $db->prepare("
        INSERT INTO cottages (name, description, type, capacity, price, pricing_unit, amenities, image_url, is_available)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
    ");

    foreach ($newCottages as $cottage) {
        $name = $cottage['name'];
        $check->bind_param('s', $name);
        $check->execute();
        $checkResult = $check->get_result();
        if ($checkResult->num_rows > 0) {
            continue;
        }

        $description = $cottage['description'];
        $type = $cottage['type'];
        $capacity = $cottage['capacity'];
        $price = $cottage['price'];
        $pricingUnit = $cottage['pricing_unit'];
        $amenities = $cottage['amenities'];
        $imageUrl = $cottage['image_url'];

        $insert->bind_param(
            'sssidsss',
            $name,
            $description,
            $type,
            $capacity,
            $price,
            $pricingUnit,
            $amenities,
            $imageUrl
        );
        $insert->execute();
    }

    $check->close();
    $insert->close();
}
?>
