<?php
declare(strict_types=1);

/*
 * "Pay online": one entry point for whichever gateway config.php selects
 * ('online_gateway' => 'xpay' or 'paymob'). Each gateway module provides
 * <gateway>_enabled() and <gateway>_checkout_url($payment).
 */

const ONLINE_GATEWAYS = ['xpay' => 'XPay', 'paymob' => 'Paymob'];

/** The active online gateway key, or null when online payment is off or not configured. */
function online_gateway(): ?string
{
    $g = (string)config('online_gateway', 'paymob');
    return isset(ONLINE_GATEWAYS[$g]) && ($g . '_enabled')() ? $g : null;
}

function online_gateway_name(): string
{
    return ONLINE_GATEWAYS[online_gateway() ?? ''] ?? '';
}

/** Smallest amount the active gateway accepts (XPay: 50 EGP for every method). */
function online_min_amount(): float
{
    $g = online_gateway();
    return $g ? (float)config("{$g}.min_amount", $g === 'xpay' ? 50 : 0) : 0;
}

/** Can this payment be paid online? (gateway on, and amount at or above its minimum) */
function online_available(array $payment): bool
{
    return online_gateway() !== null && (float)$payment['amount'] > 0 && (float)$payment['amount'] >= online_min_amount();
}

/** Show the manual transfer instructions: always when this payment can't be paid online. */
function manual_payment_enabled(array $payment): bool
{
    return !online_available($payment) || (bool)config('payment.manual', config('paymob.manual', true));
}

/** POST /payments/{id}/pay: create the gateway's checkout and send the parent there. */
function payment_online_start(int $id): void
{
    $user = require_parent();
    $payment = q_one("SELECT * FROM payments WHERE id = ? AND user_id = ? AND status = 'pending'", [$id, $user['id']]);
    $gateway = online_gateway();
    if (!$payment || !online_available($payment)) redirect("payments/{$id}");
    try {
        $url = ($gateway . '_checkout_url')($payment);
    } catch (Throwable $ex) {
        error_log((string)$ex);
        flash('error', t('online.start_failed') . (config('debug', false) ? ' — ' . $ex->getMessage() : ''));
        redirect("payments/{$id}");
    }
    $_SESSION['online_payment'] = $id; // to find the payment again when the browser comes back
    $_SESSION['online_url'] = $url;
    // Go through our own page instead of redirecting the form off-site: browsers block a form
    // submission whose redirect ends on another site (CSP form-action).
    redirect("payments/{$id}/pay");
}

/** GET /payments/{id}/pay: "Taking you to …" page that opens the checkout as a normal link. */
function payment_online_go(int $id): void
{
    require_parent();
    $url = (string)($_SESSION['online_url'] ?? '');
    unset($_SESSION['online_url']);
    if (!preg_match('#^https?://#', $url)) redirect("payments/{$id}");
    view('billing/online_redirect', ['title' => t('pay.title'), 'url' => $url, 'paymentId' => $id], 'layout_plain');
}
