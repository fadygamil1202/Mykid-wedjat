<?php
declare(strict_types=1);

// ---------- Confirm email ----------

/** GET /verify-email: "Check your email" page for a signed-in parent who hasn't confirmed yet. */
function verify_notice(): void
{
    $user = require_login();
    if ($user['email_verified_at'] !== null || !email_verification_required()) redirect(home_path($user));
    view('auth/verify_notice', ['title' => t('verify.title'), 'user' => $user]);
}

/** POST /verify-email/resend */
function verify_resend(): void
{
    $user = require_login();
    if ($user['email_verified_at'] !== null) redirect(home_path($user));
    if (!rate_limit('verify-mail:' . $user['id'], 5, 3600)) {
        flash('error', t('verify.too_many'));
    } else {
        flash(send_verification_email($user) ? 'success' : 'error', t(mail_configured() ? 'verify.sent' : 'mail.failed'));
    }
    redirect('verify-email');
}

/** POST /verify-email/change: fix a mistyped address before it is confirmed. */
function verify_change_email(): void
{
    $user = require_login();
    if ($user['email_verified_at'] !== null) redirect(home_path($user));
    $email = strtolower(input('email'));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash('error', t('error.email'));
    } elseif ($email !== $user['email'] && q_one('SELECT 1 FROM users WHERE email = ?', [$email])) {
        flash('error', t('register.email_taken'));
    } elseif (!rate_limit('verify-mail:' . $user['id'], 5, 3600)) {
        flash('error', t('verify.too_many'));
    } else {
        q('UPDATE users SET email = ? WHERE id = ?', [$email, $user['id']]);
        send_verification_email(['email' => $email] + $user);
        flash('success', t('verify.sent'));
    }
    redirect('verify-email');
}

/** GET /verify-email/{token}: the link from the email. */
function verify_email(string $token): void
{
    $row = email_token_find($token, 'verify', true);
    if (!$row) {
        flash('error', t('verify.link_invalid'));
        redirect(current_user() ? 'verify-email' : 'login');
    }
    q('UPDATE users SET email_verified_at = COALESCE(email_verified_at, NOW()) WHERE id = ?', [$row['user_id']]);
    flash('success', t('verify.done'));
    $user = current_user();
    if ($user && (int)$user['id'] === (int)$row['user_id']) {
        redirect(q_one('SELECT 1 FROM children WHERE parent_id = ?', [$user['id']]) ? home_path($user) : 'children/new');
    }
    redirect('login');
}

// ---------- Forgot / reset password ----------

function forgot_form(): void
{
    if ($u = current_user()) redirect(home_path($u));
    view('auth/forgot', ['title' => t('forgot.title')]);
}

/** Always shows the same message, so nobody can find out which emails have an account. */
function forgot_submit(): void
{
    if (!rate_limit('forgot', 5, 900)) {
        flash('error', t('login.too_many'));
        redirect('forgot-password');
    }
    $user = q_one('SELECT * FROM users WHERE email = ? AND is_active = 1', [strtolower(input('email'))]);
    if ($user) send_password_reset_email($user);
    flash('success', t('forgot.sent'));
    redirect('login');
}

function reset_form(string $token): void
{
    if (!email_token_find($token, 'reset')) {
        flash('error', t('reset.link_invalid'));
        redirect('forgot-password');
    }
    view('auth/reset', ['title' => t('reset.title'), 'token' => $token]);
}

function reset_submit(string $token): void
{
    $password = (string)($_POST['password'] ?? '');
    if (mb_strlen($password) < 8) {
        flash('error', t('account.password_short'));
        redirect("reset-password/{$token}");
    }
    if ($password !== (string)($_POST['password_confirm'] ?? '')) {
        flash('error', t('register.mismatch'));
        redirect("reset-password/{$token}");
    }
    $row = email_token_find($token, 'reset', true);
    if (!$row) {
        flash('error', t('reset.link_invalid'));
        redirect('forgot-password');
    }
    // The reset link proves they own the address, so it also counts as confirming it
    q('UPDATE users SET password_hash = ?, email_verified_at = COALESCE(email_verified_at, NOW()) WHERE id = ?',
      [password_hash($password, PASSWORD_DEFAULT), $row['user_id']]);
    q("DELETE FROM email_tokens WHERE user_id = ? AND purpose = 'reset' AND used_at IS NULL", [$row['user_id']]);
    login_user((int)$row['user_id']);
    flash('success', t('reset.done'));
    redirect($row['role'] === 'admin' ? 'admin' : 'dashboard');
}
