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
    default:
        echo json_encode(['error' => 'Invalid action']);
}

function handleLogin() {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        echo json_encode(['success' => false, 'message' => 'Email and password are required']);
        return;
    }

    $db = getDB();
    $stmt = $db->prepare("SELECT id, full_name, email, password, role FROM users WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    $db->close();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];

        echo json_encode([
            'success' => true,
            'role' => $user['role'],
            'name' => $user['full_name'],
            'redirect' => $user['role'] === 'admin' ? 'dashboard.php' : 'browse.php'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
    }
}

function handleRegister() {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!$full_name || !$email || !$password) {
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
    $check = $db->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param('s', $email);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) {
        $check->close();
        $db->close();
        echo json_encode(['success' => false, 'message' => 'Email already registered']);
        return;
    }
    $check->close();

    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare("INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, 'customer')");
    $stmt->bind_param('sss', $full_name, $email, $hashed);

    if ($stmt->execute()) {
        $stmt->close();
        $db->close();
        echo json_encode(['success' => true, 'message' => 'Account created successfully']);
    } else {
        $stmt->close();
        $db->close();
        echo json_encode(['success' => false, 'message' => 'Registration failed. Please try again.']);
    }
}

function handleLogout() {
    session_destroy();
    echo json_encode(['success' => true]);
}

function checkSession() {
    if (isset($_SESSION['user_id'])) {
        echo json_encode([
            'logged_in' => true,
            'role' => $_SESSION['role'],
            'name' => $_SESSION['full_name']
        ]);
    } else {
        echo json_encode(['logged_in' => false]);
    }
}
?>
