<?php
declare(strict_types=1);

function home(): void
{
    view('home', [
        'title'    => t('home.title'),
        'plans'    => plans(),
        'products' => q_all('SELECT * FROM products WHERE is_active=1 ORDER BY sort_order, id LIMIT 3'),
    ]);
}

function pricing(): void
{
    view('pricing', ['title' => t('nav.pricing'), 'plans' => plans()]);
}

function login_form(): void
{
    if ($u = current_user()) redirect(home_path($u));
    view('auth/login', ['title' => t('login.title')]);
}

function after_login_redirect(): never
{
    $user = current_user() ?? q_one('SELECT * FROM users WHERE id=?', [$_SESSION['user_id']]);
    $next = $_SESSION['after_login'] ?? '';
    unset($_SESSION['after_login']);
    // Only paths inside this site, and a parent is never sent into /admin
    if (is_string($next) && preg_match('#^/[a-z0-9/_-]*$#i', $next)) {
        $next = BASE_URL !== '' && str_starts_with($next, BASE_URL) ? substr($next, strlen(BASE_URL)) : $next;
        if ($user['role'] === 'admin' || !str_starts_with($next, '/admin')) {
            redirect(ltrim($next, '/'));
        }
    }
    redirect(home_path($user));
}

function login_submit(): void
{
    if (!rate_limit('login', (int)config('login_rate_limit', 10), 900)) {
        flash('error', t('login.too_many'));
        redirect('login');
    }
    if (attempt_login(input('email'), (string)($_POST['password'] ?? ''))) {
        after_login_redirect();
    }
    flash('error', t('login.invalid'));
    redirect('login');
}

function register_form(): void
{
    if ($u = current_user()) redirect(home_path($u));
    view('auth/register', ['title' => t('register.title'), 'old' => $_SESSION['old'] ?? []]);
    unset($_SESSION['old']);
}

function register_submit(): void
{
    if (!rate_limit('register', (int)config('login_rate_limit', 10), 900)) {
        flash('error', t('login.too_many'));
        redirect('register');
    }
    $name = input('name');
    $email = strtolower(input('email'));
    $phone = input('phone');
    $password = (string)($_POST['password'] ?? '');
    $_SESSION['old'] = ['name' => $name, 'email' => $email, 'phone' => $phone];

    $error = null;
    if ($name === '' || mb_strlen($name) > 190) $error = t('error.required', ['field' => t('field.name')]);
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $error = t('error.email');
    elseif (!valid_phone($phone)) $error = t('error.phone');
    elseif (mb_strlen($password) < 8) $error = t('account.password_short');
    elseif ($password !== (string)($_POST['password_confirm'] ?? '')) $error = t('register.mismatch');
    elseif (!isset($_POST['terms'])) $error = t('register.accept_terms');
    elseif (q_one('SELECT 1 FROM users WHERE email=?', [$email])) $error = t('register.email_taken');

    if ($error) {
        flash('error', $error);
        redirect('register');
    }
    q("INSERT INTO users (role, name, email, phone, password_hash, lang) VALUES ('parent', ?, ?, ?, ?, ?)",
      [$name, $email, $phone, password_hash($password, PASSWORD_DEFAULT), current_lang()]);
    unset($_SESSION['old']);
    login_user((int)db()->lastInsertId());
    flash('success', t('register.welcome'));
    redirect('children/new');
}

function logout_submit(): void
{
    logout();
    redirect('');
}

function switch_lang(string $lang): void
{
    set_lang($lang);
    $back = $_SERVER['HTTP_REFERER'] ?? '/';
    $path = parse_url($back, PHP_URL_PATH) ?: '/';
    // only redirect within this site
    header('Location: ' . (str_starts_with($path, '/') && !str_starts_with($path, '//') ? $path : '/'));
    exit;
}

// ---------- One-time web installer ----------

function install_allowed(): bool
{
    if ((string)config('install_key', '') === '') return false;
    try {
        return !q_one("SELECT 1 FROM users WHERE role='admin' LIMIT 1");
    } catch (PDOException) {
        return true; // tables not created yet
    }
}

function install_form(): void
{
    if (!install_allowed()) abort(404);
    view('auth/install', ['title' => t('install.title')]);
}

function install_submit(): void
{
    if (!install_allowed()) abort(404);
    if (!hash_equals((string)config('install_key'), (string)($_POST['install_key'] ?? ''))) {
        flash('error', t('install.bad_key'));
        redirect('install');
    }
    $name = input('name');
    $email = strtolower(input('email'));
    $password = (string)($_POST['password'] ?? '');
    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($password) < 10) {
        flash('error', t('install.invalid'));
        redirect('install');
    }
    $hasTables = true;
    try { q('SELECT 1 FROM users LIMIT 1'); } catch (PDOException) { $hasTables = false; }
    if (!$hasTables) {
        $sql = file_get_contents(BASE_PATH . '/database/schema.sql');
        foreach (explode(";\n", $sql) as $stmt) {
            $code = trim(preg_replace('/^\s*--.*$/m', '', $stmt));
            if ($code !== '') db()->exec($code);
        }
    }
    q("INSERT INTO users (role, name, email, password_hash, lang) VALUES ('admin', ?, ?, ?, ?)",
      [$name, $email, password_hash($password, PASSWORD_DEFAULT), current_lang()]);
    login_user((int)db()->lastInsertId());
    flash('success', t('install.done'));
    redirect('admin');
}

// ---------- Signed-in pages shared by every role ----------

function dashboard(): void
{
    $user = require_parent();
    $children = q_all("SELECT c.*,
            (SELECT COUNT(*) FROM tags t WHERE t.child_id=c.id AND t.status='active') AS active_tags,
            (SELECT COUNT(*) FROM tags t WHERE t.child_id=c.id AND t.status='locked') AS locked_tags,
            (SELECT consent_at FROM emergency_profiles p WHERE p.child_id=c.id) AS consent_at
        FROM children c WHERE c.parent_id=? ORDER BY c.first_name", [$user['id']]);
    $scans = q_all('SELECT l.*, c.first_name, t.type FROM scan_logs l JOIN children c ON c.id=l.child_id
                    JOIN tags t ON t.id=l.tag_id WHERE c.parent_id=? ORDER BY l.created_at DESC LIMIT 5', [$user['id']]);
    view('parent/dashboard', [
        'title'    => t('nav.dashboard'),
        'user'     => $user,
        'children' => $children,
        'sub'      => active_subscription((int)$user['id']),
        'scans'    => $scans,
        'unread'   => q_one('SELECT COUNT(*) n FROM notifications WHERE user_id=? AND is_read=0', [$user['id']])['n'],
        'openOrders' => q_one("SELECT COUNT(*) n FROM orders WHERE user_id=? AND status NOT IN ('delivered','cancelled')", [$user['id']])['n'],
    ]);
}

function notifications(): void
{
    $user = require_login();
    $items = q_all('SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 100', [$user['id']]);
    q('UPDATE notifications SET is_read=1 WHERE user_id=?', [$user['id']]);
    view('notifications', ['title' => t('nav.notifications'), 'items' => $items]);
}

function account_form(): void
{
    $user = require_login();
    view('account', ['title' => t('nav.account'), 'user' => $user]);
}

function account_submit(): void
{
    $user = require_login();
    $name = input('name');
    $phone = input('phone');
    if ($name === '') {
        flash('error', t('error.required', ['field' => t('field.name')]));
        redirect('account');
    }
    if ($phone !== '' && !valid_phone($phone)) {
        flash('error', t('error.phone'));
        redirect('account');
    }
    q('UPDATE users SET name=?, phone=? WHERE id=?', [$name, $phone ?: null, $user['id']]);

    $new = (string)($_POST['new_password'] ?? '');
    if ($new !== '') {
        if (!password_verify((string)($_POST['current_password'] ?? ''), $user['password_hash'])) {
            flash('error', t('account.wrong_password'));
            redirect('account');
        }
        if (mb_strlen($new) < 8) {
            flash('error', t('account.password_short'));
            redirect('account');
        }
        q('UPDATE users SET password_hash=? WHERE id=?', [password_hash($new, PASSWORD_DEFAULT), $user['id']]);
    }
    flash('success', t('saved'));
    redirect('account');
}
