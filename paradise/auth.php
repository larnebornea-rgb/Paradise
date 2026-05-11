<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'login':
        handleLogin();
        break;
    case 'register':
        handleRegister();
        break;
    case 'logout':
        handleLogout();
        break;
    case 'check':
        checkSession();
        break;
    case 'profile':
        getProfile();
        break;
    case 'update_profile':
        updateProfile();
        break;
    case 'change_password':
        changePassword();
        break;
    case 'list_users':
        listUsers();
        break;
    case 'set_user_status':
        setUserStatus();
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

function logLoginActivity(?int $userId, string $email, ?string $role, string $status): void {
    $db = getDB();
    $stmt = $db->prepare("
        INSERT INTO login_activities (user_id, email, role, status, ip_address)
        VALUES (?, ?, ?, ?, ?)
    ");
    $ip = getClientIp();
    $stmt->bind_param('issss', $userId, $email, $role, $status, $ip);
    $stmt->execute();
    $stmt->close();
    $db->close();
}

function handleLogin(): void {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        echo json_encode(['success' => false, 'message' => 'Email and password are required']);
        return;
    }

    $db = getDB();
    $stmt = $db->prepare("
        SELECT id, full_name, email, password, role, account_status, display_theme, phone, is_guest
        FROM users
        WHERE email = ?
        LIMIT 1
    ");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $db->close();

    if (!$user || !password_verify($password, $user['password'])) {
        logLoginActivity(null, $email, null, 'failed');
        echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
        return;
    }

    if (($user['account_status'] ?? 'active') !== 'active') {
        logLoginActivity((int)$user['id'], $email, $user['role'], 'blocked');
        echo json_encode(['success' => false, 'message' => 'This account is inactive. Please contact the administrator.']);
        return;
    }

    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['display_theme'] = $user['display_theme'] ?: 'teal';
    $_SESSION['phone'] = $user['phone'] ?? '';
    $_SESSION['is_guest'] = (int)($user['is_guest'] ?? 0);

    logLoginActivity((int)$user['id'], $email, $user['role'], 'success');

    echo json_encode([
        'success' => true,
        'role' => $user['role'],
        'name' => $user['full_name'],
        'theme' => $_SESSION['display_theme'],
        'redirect' => $user['role'] === 'admin' ? 'dashboard.php' : 'browse.php'
    ]);
}

function handleRegister(): void {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');

    if ($fullName === '' || $email === '' || $password === '') {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        return;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email address']);
        return;
    }
    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
        return;
    }
    if ($password !== $confirm) {
        echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
        return;
    }

    $db = getDB();
    $check = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $check->bind_param('s', $email);
    $check->execute();
    $exists = $check->get_result()->num_rows > 0;
    $check->close();

    if ($exists) {
        $db->close();
        echo json_encode(['success' => false, 'message' => 'Email already registered']);
        return;
    }

    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare("
        INSERT INTO users (full_name, email, password, role, account_status, display_theme, phone, is_guest)
        VALUES (?, ?, ?, 'customer', 'active', 'teal', ?, 0)
    ");
    $stmt->bind_param('ssss', $fullName, $email, $hashed, $phone);
    $ok = $stmt->execute();
    $stmt->close();
    $db->close();

    echo json_encode($ok
        ? ['success' => true, 'message' => 'Account created successfully']
        : ['success' => false, 'message' => 'Registration failed. Please try again.']
    );
}

function handleLogout(): void {
    session_destroy();
    echo json_encode(['success' => true]);
}

function checkSession(): void {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['logged_in' => false]);
        return;
    }

    echo json_encode([
        'logged_in' => true,
        'role' => $_SESSION['role'],
        'name' => $_SESSION['full_name'],
        'email' => $_SESSION['email'],
        'theme' => $_SESSION['display_theme'] ?? 'teal',
        'phone' => $_SESSION['phone'] ?? ''
    ]);
}

function getProfile(): void {
    requireLogin();

    $db = getDB();
    $stmt = $db->prepare("
        SELECT id, full_name, email, role, account_status, display_theme, phone, is_guest
        FROM users
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $db->close();

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Profile not found']);
        return;
    }

    echo json_encode(['success' => true, 'profile' => $user]);
}

function updateProfile(): void {
    requireLogin();

    $fullName = trim($_POST['full_name'] ?? '');
    $theme = trim($_POST['display_theme'] ?? 'teal');
    $phone = trim($_POST['phone'] ?? '');
    $allowedThemes = ['teal', 'sunset', 'ocean'];

    if ($fullName === '') {
        echo json_encode(['success' => false, 'message' => 'Full name is required']);
        return;
    }
    if (!in_array($theme, $allowedThemes, true)) {
        $theme = 'teal';
    }

    $db = getDB();
    $stmt = $db->prepare("
        UPDATE users
        SET full_name = ?, display_theme = ?, phone = ?
        WHERE id = ?
    ");
    $stmt->bind_param('sssi', $fullName, $theme, $phone, $_SESSION['user_id']);
    $ok = $stmt->execute();
    $stmt->close();
    $db->close();

    if (!$ok) {
        echo json_encode(['success' => false, 'message' => 'Could not update profile']);
        return;
    }

    $_SESSION['full_name'] = $fullName;
    $_SESSION['display_theme'] = $theme;
    $_SESSION['phone'] = $phone;

    echo json_encode([
        'success' => true,
        'message' => 'Profile updated successfully',
        'profile' => [
            'full_name' => $fullName,
            'display_theme' => $theme,
            'phone' => $phone
        ]
    ]);
}

function changePassword(): void {
    requireLogin();

    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
        echo json_encode(['success' => false, 'message' => 'Please complete all password fields']);
        return;
    }
    if (strlen($newPassword) < 6) {
        echo json_encode(['success' => false, 'message' => 'New password must be at least 6 characters']);
        return;
    }
    if ($newPassword !== $confirmPassword) {
        echo json_encode(['success' => false, 'message' => 'New passwords do not match']);
        return;
    }

    $db = getDB();
    $stmt = $db->prepare("SELECT password FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user || !password_verify($currentPassword, $user['password'])) {
        $db->close();
        echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
        return;
    }

    $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
    $update = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
    $update->bind_param('si', $hashed, $_SESSION['user_id']);
    $ok = $update->execute();
    $update->close();
    $db->close();

    echo json_encode($ok
        ? ['success' => true, 'message' => 'Password changed successfully']
        : ['success' => false, 'message' => 'Could not change password']
    );
}

function listUsers(): void {
    requireAdmin();

    $search = trim($_GET['search'] ?? $_POST['search'] ?? '');
    $db = getDB();

    if ($search !== '') {
        $like = '%' . $search . '%';
        $stmt = $db->prepare("
            SELECT id, full_name, email, phone, role, account_status, display_theme, is_guest, created_at
            FROM users
            WHERE role = 'customer'
              AND (full_name LIKE ? OR email LIKE ? OR phone LIKE ?)
            ORDER BY created_at DESC
        ");
        $stmt->bind_param('sss', $like, $like, $like);
    } else {
        $stmt = $db->prepare("
            SELECT id, full_name, email, phone, role, account_status, display_theme, is_guest, created_at
            FROM users
            WHERE role = 'customer'
            ORDER BY created_at DESC
        ");
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    $stmt->close();
    $db->close();

    echo json_encode(['success' => true, 'users' => $users]);
}

function setUserStatus(): void {
    requireAdmin();

    $userId = (int)($_POST['user_id'] ?? 0);
    $status = trim($_POST['status'] ?? 'active');
    if ($userId <= 0 || !in_array($status, ['active', 'inactive'], true)) {
        echo json_encode(['success' => false, 'message' => 'Invalid user status update']);
        return;
    }

    $db = getDB();
    $stmt = $db->prepare("UPDATE users SET account_status = ? WHERE id = ? AND role = 'customer'");
    $stmt->bind_param('si', $status, $userId);
    $ok = $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();
    $db->close();

    echo json_encode(($ok && $affected >= 0)
        ? ['success' => true, 'message' => 'Customer account updated']
        : ['success' => false, 'message' => 'Could not update customer status']
    );
}
?>
