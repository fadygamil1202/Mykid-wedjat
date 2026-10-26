<?php
declare(strict_types=1);

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    return BASE_HREF . '/' . ltrim($path, '/');
}

/** Full site address, from app_url in config or detected from the request. */
function site_base(): string
{
    $base = rtrim((string)config('app_url', ''), '/');
    if ($base === '') {
        $https = ($_SERVER['HTTPS'] ?? '') === 'on' || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
        $base = ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . BASE_HREF;
    }
    return $base;
}

/** Link written to the NFC chip / QR code. Only ever shown on admin pages. */
function public_link(string $token): string
{
    return site_base() . '/e/' . $token;
}

/** Prefix root-relative links (href="/x", action="/x", src="/x") with the app's subfolder. */
function with_base(string $html): string
{
    if (BASE_HREF === '') return $html;
    return preg_replace('#\b(href|action|src)="/(?!/)#', '$1="' . BASE_HREF . '/', $html);
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function take_flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

// ---------- CSRF ----------
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $sent = $_POST['_csrf'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        http_response_code(403);
        exit(t('error.csrf'));
    }
}

// ---------- Views ----------
function view(string $template, array $data = [], string $layout = 'layout'): void
{
    extract($data, EXTR_SKIP);
    ob_start();
    require BASE_PATH . "/views/{$template}.php";
    $content = ob_get_clean();
    ob_start();
    require BASE_PATH . "/views/{$layout}.php";
    echo with_base(ob_get_clean());
}

function abort(int $code, ?string $message = null): never
{
    http_response_code($code);
    $message ??= t('error.' . $code);
    view('error', ['code' => $code, 'message' => $message, 'title' => (string)$code]);
    exit;
}

/** Inline SVG icon from views/partials/icons.php */
function icon(string $name): string
{
    static $icons = null;
    $icons ??= require BASE_PATH . '/views/partials/icons.php';
    $path = $icons[$name] ?? '';
    return '<svg class="icon" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $path . '</svg>';
}

// ---------- Misc ----------
function random_token(): string
{
    return bin2hex(random_bytes(16)); // 32 hex chars, 128 bits
}

function client_ip_hash(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    return hash_hmac('sha256', $ip, (string)config('app_key', 'key'));
}

/** Returns false when the caller exceeded $limit hits within $windowSeconds. */
function rate_limit(string $bucket, int $limit, int $windowSeconds): bool
{
    $window = date('Y-m-d H:i:s', intdiv(time(), $windowSeconds) * $windowSeconds);
    $ip = client_ip_hash();
    q('INSERT INTO rate_limits (bucket, ip_hash, window_start, hits) VALUES (?,?,?,1)
       ON DUPLICATE KEY UPDATE hits = hits + 1', [$bucket, $ip, $window]);
    $row = q_one('SELECT hits FROM rate_limits WHERE bucket=? AND ip_hash=? AND window_start=?', [$bucket, $ip, $window]);
    if (random_int(1, 100) === 1) {
        q('DELETE FROM rate_limits WHERE window_start < (NOW() - INTERVAL 1 DAY)');
    }
    return (int)$row['hits'] <= $limit;
}

function age_from_dob(?string $dob): ?int
{
    if (!$dob) return null;
    try {
        return (new DateTime($dob))->diff(new DateTime('today'))->y;
    } catch (Exception) {
        return null;
    }
}

function input(string $key, string $default = ''): string
{
    $v = $_POST[$key] ?? $default;
    return is_string($v) ? trim($v) : $default;
}

function input_int(string $key): ?int
{
    $v = $_POST[$key] ?? null;
    return (is_string($v) && ctype_digit($v)) ? (int)$v : null;
}

function query_str(string $key): string
{
    $v = $_GET[$key] ?? '';
    return is_string($v) ? trim($v) : '';
}

function format_dt(?string $dt): string
{
    return $dt ? date('Y-m-d H:i', strtotime($dt)) : '';
}

function format_date(?string $dt): string
{
    return $dt ? date('Y-m-d', strtotime($dt)) : '';
}

function money($amount): string
{
    $n = (float)$amount;
    $txt = number_format($n, floor($n) == $n ? 0 : 2);
    return is_rtl() ? $txt . ' ج.م' : $txt . ' ' . config('currency', 'EGP');
}

/** Pick the _en / _ar column of a row for the current language. */
function loc(array $row, string $field): string
{
    return (string)($row[$field . '_' . current_lang()] ?? $row[$field . '_en'] ?? '');
}

function valid_phone(string $phone): bool
{
    return (bool)preg_match('/^\+?[0-9 ()-]{7,20}$/', $phone);
}

function clean_phone(string $phone): string
{
    return preg_replace('/[^0-9+]/', '', $phone);
}

function mask_email(string $email): string
{
    [$name, $domain] = array_pad(explode('@', $email, 2), 2, '');
    return mb_substr($name, 0, 2) . '•••@' . $domain;
}

const BLOOD_TYPES = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
const SEVERITIES = ['mild', 'moderate', 'severe'];
const TAG_TYPES = ['card', 'bracelet', 'necklace'];
const ORDER_STATUSES = ['pending_payment', 'paid', 'processing', 'shipped', 'delivered', 'cancelled'];
const REQUEST_REASONS = ['lost', 'stolen', 'damaged', 'other'];
const GOVERNORATES = ['Cairo', 'Giza', 'Alexandria', 'Qalyubia', 'Sharqia', 'Dakahlia', 'Gharbia', 'Monufia', 'Beheira',
    'Kafr El Sheikh', 'Damietta', 'Port Said', 'Ismailia', 'Suez', 'Faiyum', 'Beni Suef', 'Minya', 'Asyut', 'Sohag',
    'Qena', 'Luxor', 'Aswan', 'Red Sea', 'New Valley', 'Matrouh', 'North Sinai', 'South Sinai'];

/** Parse "Peanuts|severe" lines into allergy records. */
function parse_allergies(string $text): array
{
    $out = [];
    foreach (preg_split('/\R/', $text) as $line) {
        $line = trim($line);
        if ($line === '') continue;
        [$name, $sev] = array_pad(array_map('trim', explode('|', $line, 2)), 2, 'moderate');
        $sev = ['خفيفة' => 'mild', 'متوسطة' => 'moderate', 'شديدة' => 'severe'][$sev] ?? strtolower($sev);
        if (!in_array($sev, SEVERITIES, true)) $sev = 'moderate';
        $out[] = ['name' => mb_substr($name, 0, 100), 'severity' => $sev];
    }
    return array_slice($out, 0, 20);
}

function allergies_to_text(?string $json): string
{
    $items = json_decode($json ?? '[]', true) ?: [];
    return implode("\n", array_map(fn($a) => $a['name'] . '|' . $a['severity'], $items));
}

/**
 * In-app notification in the user's own language, plus an email when mail_from is set.
 * $key is a translation key; a var value starting with "@" is itself a translation key.
 */
function notify(int $userId, string $key, array $vars = []): void
{
    $user = q_one('SELECT email, lang, is_active FROM users WHERE id=?', [$userId]);
    if (!$user) return;
    foreach ($vars as $k => $v) {
        if (is_string($v) && str_starts_with($v, '@')) $vars[$k] = t(substr($v, 1), [], $user['lang']);
    }
    $message = mb_substr(t($key, $vars, $user['lang']), 0, 500);
    q('INSERT INTO notifications (user_id, message) VALUES (?, ?)', [$userId, $message]);
    $from = (string)config('mail_from', '');
    if ($from === '' || !$user['is_active']) return;
    $subject = '=?UTF-8?B?' . base64_encode(t('app.name', [], $user['lang'])) . '?=';
    $headers = "From: {$from}\r\nContent-Type: text/plain; charset=UTF-8\r\n";
    @mail($user['email'], $subject, $message . "\n\n" . site_base() . '/notifications', $headers);
}

function notify_admins(string $key, array $vars = []): void
{
    foreach (q_all("SELECT id FROM users WHERE role='admin' AND is_active=1") as $a) {
        notify((int)$a['id'], $key, $vars);
    }
}

/**
 * Save an uploaded product image into public/uploads. Returns the stored path
 * (relative to public/) or null when nothing valid was uploaded.
 */
function save_upload(string $field): ?string
{
    $f = $_FILES[$field] ?? null;
    if (!$f || ($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || $f['size'] > 3 * 1024 * 1024) return null;
    $types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    if (!isset($types[$mime]) || !getimagesize($f['tmp_name'])) return null;
    $name = 'uploads/' . bin2hex(random_bytes(10)) . '.' . $types[$mime];
    return move_uploaded_file($f['tmp_name'], BASE_PATH . '/public/' . $name) ? $name : null;
}

// ---------- Cart (kept in the session until checkout) ----------
function cart(): array
{
    return $_SESSION['cart'] ?? [];
}

function cart_count(): int
{
    return array_sum(array_column(cart(), 'qty'));
}

/** Cart lines joined with product and child rows; drops anything no longer valid. */
function cart_lines(array $user): array
{
    $lines = [];
    foreach (cart() as $key => $item) {
        $p = q_one('SELECT * FROM products WHERE id=? AND is_active=1', [$item['product_id']]);
        $c = q_one('SELECT id, first_name FROM children WHERE id=? AND parent_id=?', [$item['child_id'], $user['id']]);
        if (!$p || !$c) {
            unset($_SESSION['cart'][$key]);
            continue;
        }
        $lines[$key] = ['product' => $p, 'child' => $c, 'qty' => (int)$item['qty'], 'total' => $p['price'] * $item['qty']];
    }
    return $lines;
}
