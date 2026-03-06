<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        // Check if it's an AJAX request
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            http_response_code(401);
            echo json_encode(['error' => 'Please log in to continue']);
            exit;
        }
        header('Location: /login.php');
        exit;
    }
}

function requireGuest() {
    if (isLoggedIn()) {
        header('Location: /dashboard.php');
        exit;
    }
}

function getCurrentUser() {
    return $_SESSION['user'] ?? null;
}

function getUsersFile() {
    return __DIR__ . '/../data/users.json';
}

function loadUsers() {
    $file = getUsersFile();
    if (!file_exists($file)) {
        if (!is_dir(dirname($file))) {
            mkdir(dirname($file), 0755, true);
        }
        file_put_contents($file, json_encode([]));
        return [];
    }
    return json_decode(file_get_contents($file), true) ?? [];
}

function saveUsers($users) {
    file_put_contents(getUsersFile(), json_encode($users, JSON_PRETTY_PRINT));
}

function findUserByEmail($email) {
    $users = loadUsers();
    foreach ($users as $user) {
        if (strtolower($user['email']) === strtolower($email)) {
            return $user;
        }
    }
    return null;
}

function registerUser($name, $email, $password, $phone, $profilePic) {
    $users = loadUsers();
    $users[] = [
        'id'         => uniqid('u_', true),
        'name'       => $name,
        'email'      => strtolower($email),
        'password'   => password_hash($password, PASSWORD_DEFAULT),
        'phone'      => $phone,
        'profile_pic'=> $profilePic,
        'created_at' => date('Y-m-d H:i:s'),
        'cart'       => [],
        'orders'     => [],
    ];
    saveUsers($users);
    return end($users);
}

function updateUser($userId, $data) {
    $users = loadUsers();
    foreach ($users as &$user) {
        if ($user['id'] === $userId) {
            $user = array_merge($user, $data);
            break;
        }
    }
    saveUsers($users);
}
?>
