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
    // Until the email address is confirmed, the account only shows the "check your email" page
    if ($user['email_verified_at'] === null && email_verification_required()) redirect('verify-email');
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

// ---------- Email verification & password reset ----------

/** Parents must confirm their email before using the account (only when email sending is set up). */
function email_verification_required(): bool
{
    return (bool)config('require_email_verification', true) && mail_configured();
}

/** Create a one-time link token; only its SHA-256 hash is stored. Older unused ones of the same kind stop working. */
function email_token_create(int $userId, string $purpose, int $ttlSeconds): string
{
    $raw = random_token();
    q('DELETE FROM email_tokens WHERE user_id = ? AND purpose = ? AND used_at IS NULL', [$userId, $purpose]);
    q('INSERT INTO email_tokens (user_id, purpose, token_hash, expires_at) VALUES (?, ?, ?, ?)',
      [$userId, $purpose, hash('sha256', $raw), date('Y-m-d H:i:s', time() + $ttlSeconds)]);
    return $raw;
}

/** The valid (unused, unexpired) token row with its user, or null. $consume marks it used. */
function email_token_find(string $raw, string $purpose, bool $consume = false): ?array
{
    $row = q_one('SELECT t.*, u.email, u.name, u.role, u.lang, u.is_active FROM email_tokens t JOIN users u ON u.id = t.user_id
                  WHERE t.token_hash = ? AND t.purpose = ? AND t.used_at IS NULL AND t.expires_at > NOW()',
                 [hash('sha256', $raw), $purpose]);
    if (!$row || !$row['is_active']) return null;
    if ($consume) q('UPDATE email_tokens SET used_at = NOW() WHERE id = ?', [$row['id']]);
    return $row;
}

function send_verification_email(array $user): bool
{
    $lang = $user['lang'] ?: current_lang();
    $link = site_base() . '/verify-email/' . email_token_create((int)$user['id'], 'verify', 48 * 3600);
    [$html, $text] = email_body($lang, t('mail.verify_title', [], $lang),
        [t('mail.hello', ['name' => explode(' ', $user['name'])[0]], $lang), t('mail.verify_text', [], $lang)],
        [t('mail.verify_btn', [], $lang), $link], t('mail.verify_footer', [], $lang));
    return send_mail($user['email'], t('mail.verify_subject', [], $lang), $html, $text);
}

function send_password_reset_email(array $user): bool
{
    $lang = $user['lang'] ?: current_lang();
    $link = site_base() . '/reset-password/' . email_token_create((int)$user['id'], 'reset', 3600);
    [$html, $text] = email_body($lang, t('mail.reset_title', [], $lang),
        [t('mail.hello', ['name' => explode(' ', $user['name'])[0]], $lang), t('mail.reset_text', [], $lang)],
        [t('mail.reset_btn', [], $lang), $link], t('mail.reset_footer', [], $lang));
    return send_mail($user['email'], t('mail.reset_subject', [], $lang), $html, $text);
}
