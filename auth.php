<?php
// auth.php — Authentication, User Registration & Session Controller
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/db.php';
$pdo = get_db_connection();

// Support both JSON body and standard POST parameters
$inputData = [];
$rawInput = file_get_contents('php://input');
if (!empty($rawInput)) {
    $decoded = json_decode($rawInput, true);
    if (is_array($decoded)) {
        $inputData = $decoded;
    }
}
$req = array_merge($_GET, $_POST, $inputData);
$action = $req['action'] ?? '';

// Helper to return JSON
function json_response($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// -------------------------------------------------------------
// 1. REGISTER NEW FACULTY (CHOOSE USERNAME & PASSWORD)
// -------------------------------------------------------------
if ($action === 'register') {
    $username = trim($req['username'] ?? '');
    $password = $req['password'] ?? '';
    $fullName = trim($req['full_name'] ?? '');
    $email = trim($req['email'] ?? '');
    $department = trim($req['department'] ?? 'Dept. of Physics, The Heritage College');

    if (empty($username) || empty($password)) {
        json_response(['success' => false, 'error' => 'Please provide both username and password.'], 400);
    }

    if (strlen($username) < 3 || strlen($username) > 30) {
        json_response(['success' => false, 'error' => 'Username must be between 3 and 30 characters long.'], 400);
    }

    if (!preg_match('/^[a-zA-Z0-9_.-]+$/', $username)) {
        json_response(['success' => false, 'error' => 'Username can only contain letters, numbers, dots, dashes, and underscores.'], 400);
    }

    if (strlen($password) < 4) {
        json_response(['success' => false, 'error' => 'Password must be at least 4 characters long.'], 400);
    }

    if (empty($fullName)) {
        $fullName = 'Prof. ' . ucfirst($username);
    }

    // Check if username already exists
    $checkStmt = $pdo->prepare("SELECT `id` FROM `users` WHERE LOWER(`username`) = LOWER(:username)");
    $checkStmt->execute([':username' => $username]);
    if ($checkStmt->fetch()) {
        json_response(['success' => false, 'error' => "The username '{$username}' is already registered. Please choose another username."], 409);
    }

    // Hash password securely with Bcrypt
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    try {
        $insert = $pdo->prepare("
            INSERT INTO `users` (`username`, `password_hash`, `full_name`, `email`, `department`)
            VALUES (:username, :password_hash, :full_name, :email, :department)
        ");
        $insert->execute([
            ':username' => $username,
            ':password_hash' => $passwordHash,
            ':full_name' => $fullName,
            ':email' => $email,
            ':department' => $department
        ]);
        $newUserId = (int)$pdo->lastInsertId();

        // Seed default semester rosters for student attendance roll call
        seed_user_rosters($pdo, $newUserId);

        // DO NOT seed any demo activity logs! Under user login there should not be any demo data.
        
        // Establish logged in session
        $_SESSION['user_id'] = $newUserId;

        $user = [
            'id' => $newUserId,
            'username' => $username,
            'full_name' => $fullName,
            'email' => $email,
            'department' => $department
        ];

        json_response([
            'success' => true,
            'message' => "Welcome, {$fullName}! Your faculty account has been created successfully.",
            'user' => $user
        ]);
    } catch (PDOException $e) {
        json_response(['success' => false, 'error' => 'Database error during registration: ' . $e->getMessage()], 500);
    }
}

// -------------------------------------------------------------
// 2. LOGIN FACULTY (VERIFY USERNAME & PASSWORD)
// -------------------------------------------------------------
if ($action === 'login') {
    $username = trim($req['username'] ?? '');
    $password = $req['password'] ?? '';

    if (empty($username) || empty($password)) {
        json_response(['success' => false, 'error' => 'Please enter your username and password.'], 400);
    }

    $stmt = $pdo->prepare("
        SELECT `id`, `username`, `password_hash`, `full_name`, `email`, `department`
        FROM `users`
        WHERE LOWER(`username`) = LOWER(:u1) OR LOWER(`email`) = LOWER(:u2)
        LIMIT 1
    ");
    $stmt->execute([':u1' => $username, ':u2' => $username]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        json_response(['success' => false, 'error' => 'Invalid username or password. Please try again.'], 401);
    }

    // Set PHP session
    $_SESSION['user_id'] = (int)$user['id'];
    unset($user['password_hash']);

    json_response([
        'success' => true,
        'message' => "Welcome back, {$user['full_name']}!",
        'user' => $user
    ]);
}

// -------------------------------------------------------------
// 3. CHECK SESSION
// -------------------------------------------------------------
if ($action === 'check_session') {
    $userId = $_SESSION['user_id'] ?? null;
    if ($userId) {
        $stmt = $pdo->prepare("SELECT `id`, `username`, `full_name`, `email`, `department` FROM `users` WHERE `id` = :id LIMIT 1");
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch();
        if ($user) {
            json_response(['logged_in' => true, 'user' => $user]);
        }
    }
    json_response(['logged_in' => false]);
}

// -------------------------------------------------------------
// 4. LOGOUT
// -------------------------------------------------------------
if ($action === 'logout') {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    json_response(['success' => true, 'message' => 'Signed out successfully.']);
}

// -------------------------------------------------------------
// 5. VERIFY PASSWORD (FOR CONFIRMING SENSITIVE ACTIONS)
// -------------------------------------------------------------
if ($action === 'verify_password') {
    $userId = $_SESSION['user_id'] ?? null;
    if (!$userId) {
        json_response(['success' => false, 'error' => 'Unauthorized. Please sign in.'], 401);
    }

    $password = $req['password'] ?? '';
    if (empty($password)) {
        json_response(['success' => false, 'error' => 'Password is required.'], 400);
    }

    $stmt = $pdo->prepare("SELECT `password_hash` FROM `users` WHERE `id` = :id");
    $stmt->execute([':id' => $userId]);
    $uRow = $stmt->fetch();

    if ($uRow && password_verify($password, $uRow['password_hash'])) {
        json_response(['success' => true, 'verified' => true]);
    } else {
        json_response(['success' => false, 'verified' => false, 'error' => 'Incorrect password.'], 403);
    }
}

// -------------------------------------------------------------
// 6. UPDATE PROFILE
// -------------------------------------------------------------
if ($action === 'update_profile') {
    $userId = $_SESSION['user_id'] ?? null;
    if (!$userId) {
        json_response(['success' => false, 'error' => 'Unauthorized. Please sign in.'], 401);
    }

    $fullName = trim($req['full_name'] ?? '');
    $department = trim($req['department'] ?? '');
    $email = trim($req['email'] ?? '');
    $currentPassword = $req['current_password'] ?? '';
    $newPassword = $req['new_password'] ?? '';

    if (empty($fullName)) {
        json_response(['success' => false, 'error' => 'Full name cannot be empty.'], 400);
    }

    // If changing password, must verify current password
    $params = [
        ':full_name' => $fullName,
        ':department' => $department,
        ':email' => $email,
        ':id' => $userId
    ];
    $sql = "UPDATE `users` SET `full_name` = :full_name, `department` = :department, `email` = :email";

    if (!empty($newPassword)) {
        if (strlen($newPassword) < 4) {
            json_response(['success' => false, 'error' => 'New password must be at least 4 characters long.'], 400);
        }
        if (empty($currentPassword)) {
            json_response(['success' => false, 'error' => 'Please enter your current password to set a new password.'], 400);
        }

        $checkStmt = $pdo->prepare("SELECT `password_hash` FROM `users` WHERE `id` = :id");
        $checkStmt->execute([':id' => $userId]);
        $uRow = $checkStmt->fetch();
        if (!$uRow || !password_verify($currentPassword, $uRow['password_hash'])) {
            json_response(['success' => false, 'error' => 'Current password is incorrect.'], 403);
        }

        $sql .= ", `password_hash` = :password_hash";
        $params[':password_hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
    }

    $sql .= " WHERE `id` = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $getStmt = $pdo->prepare("SELECT `id`, `username`, `full_name`, `email`, `department` FROM `users` WHERE `id` = :id");
    $getStmt->execute([':id' => $userId]);
    $updatedUser = $getStmt->fetch();

    json_response([
        'success' => true,
        'message' => 'Profile updated successfully.',
        'user' => $updatedUser
    ]);
}

// Fallback
json_response(['success' => false, 'error' => 'Invalid or unknown action.'], 400);
