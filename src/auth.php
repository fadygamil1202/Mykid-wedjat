<?php
declare(strict_types=1);

function current_user(): ?array
{
    static $user = false;
    if ($user === false) {
        $id = $_SESSION['user_id'] ?? null;
        $user = $id ? q_one('SELECT * FROM users WHERE id = ? AND is_active = 1', [$id]) : null;
    }
    return $user;
}

function attempt_login(string $email, string $password): bool
{
    $user = q_one('SELECT * FROM users WHERE email = ? AND is_active = 1', [strtolower($email)]);
    if (!$user || !password_verify($password, $user['password_hash'])) {
        return false;
    }
    if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
        q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), $user['id']]);
    }
    login_user((int)$user['id']);
    return true;
}

function login_user(int $id): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = $id;
    q('UPDATE users SET last_login_at = NOW() WHERE id = ?', [$id]);
    if (!isset($_SESSION['lang'])) {
        $_SESSION['lang'] = q_one('SELECT lang FROM users WHERE id = ?', [$id])['lang'] ?? current_lang();
    }
}

function logout(): void
{
    $lang = $_SESSION['lang'] ?? null;
    $_SESSION = [];
    session_regenerate_id(true);
    if ($lang) $_SESSION['lang'] = $lang;
}

function require_login(): array
{
    $user = current_user();
    if (!$user) {
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'GET') {
            $_SESSION['after_login'] = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        }
        redirect('login');
    }
    return $user;
}

function require_parent(): array
{
    $user = require_login();
    if ($user['role'] !== 'parent') redirect('admin');
    return $user;
}

function require_admin(): array
{
    $user = require_login();
    if ($user['role'] !== 'admin') abort(403);
    return $user;
}

/** Where to go after signing in: the page that asked for login, or the user's home. */
function home_path(array $user): string
{
    return $user['role'] === 'admin' ? 'admin' : 'dashboard';
}
