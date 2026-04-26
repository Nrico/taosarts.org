<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/db.php';
function current_user(): ?array { return $_SESSION['user'] ?? null; }
function require_login(): void {
    if (!current_user()) redirect('login.php');
}
function login_user(string $email, string $password): bool {
    $stmt = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user'] = ['id'=>$user['id'], 'name'=>$user['name'], 'email'=>$user['email'], 'role'=>$user['role']];
        return true;
    }
    return false;
}
function logout_user(): void { session_destroy(); }
