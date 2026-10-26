<?php
declare(strict_types=1);

/*
 * XPay Hosted Checkout (https://docs.xpay.app/en/integrate/integration-patterns/hosted-checkout)
 *
 *   1. xpay_checkout_url()  POST /checkout/sessions for a waiting payment; returns the hosted page URL.
 *   2. xpay_webhook()       checkout.session.completed / async_payment_succeeded, verified with the
 *                           XPay-Signature header (HMAC-SHA256 of "<t>.<raw body>", 5-minute tolerance).
 *                           This is the source of truth: payment_mark_paid() activates the purchase.
 *   3. xpay_return()        The customer comes back with no query parameters. We read the session
 *                           from XPay's API (server to server, trustworthy) to show the right message,
 *                           and confirm it there too if the webhook hasn't arrived yet. Idempotent.
 *
 * Test mode is decided by the key: sk_test_* creates cs_test_* sessions. Success test card:
 * 5123 4500 0000 0008, expiry 01/39.
 */

function xpay_enabled(): bool
{
    return (bool)config('xpay.enabled', false) && config('xpay.secret_key') && config('xpay.webhook_secret');
}

function xpay_api(string $method, string $path, ?array $body = null, array $headers = []): array
{
    $ch = curl_init(rtrim((string)config('xpay.base_url', 'https://api.xpay.app'), '/') . $path);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_HTTPHEADER     => array_merge(['Authorization: Bearer ' . config('xpay.secret_key'), 'Content-Type: application/json'], $headers),
    ]);
    if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    $raw = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    $data = is_string($raw) ? json_decode($raw, true) : null;
    if ($status < 200 || $status >= 300 || !is_array($data)) {
        throw new RuntimeException("XPay {$method} {$path} failed ({$status}) {$error} " . mb_substr((string)$raw, 0, 500));
    }
    return $data;
}

/** Create a Checkout Session for a waiting payment and return XPay's hosted checkout URL. */
function xpay_checkout_url(array $payment): string
{
    $session = xpay_api('POST', '/checkout/sessions', [
        'afterCompletion' => [
            'type'     => 'redirect',
            // XPay replaces {CHECKOUT_SESSION_ID} with the session's id
            'redirect' => ['url' => site_base() . '/xpay/return/{CHECKOUT_SESSION_ID}'],
        ],
        // One line for the whole payment (after promo codes), so the total is always exact
        'lineItems' => [[
            'priceData' => [
                'currency'    => config('currency', 'EGP'),
                'unitAmount'  => (int)round((float)$payment['amount'] * 100), // minor units
                'productData' => ['name' => 'MyKid Wedjat MKW-' . $payment['id'] . ' · ' . t('pay.kind.' . $payment['kind'], [], 'en')],
            ],
            'quantity' => 1,
        ]],
        'metadata' => ['payment_id' => (string)$payment['id']],
    ], ['Idempotency-Key: mkw-' . $payment['id'] . '-' . bin2hex(random_bytes(8))]);

    if (empty($session['url']) || empty($session['id'])) {
        throw new RuntimeException('XPay session without url/id: ' . mb_substr(json_encode($session), 0, 500));
    }
    // Remember the latest session, so the return page can find the payment even without metadata
    q('UPDATE payments SET provider_ref = ? WHERE id = ?', ['xpay:' . $session['id'], $payment['id']]);
    return (string)$session['url'];
}

/** Verify the XPay-Signature header ("t=...,v1=...") against the raw body. */
function xpay_signature_valid(string $rawBody, string $header, int $tolerance = 300): bool
{
    $secret = (string)config('xpay.webhook_secret', '');
    $t = null;
    $sigs = [];
    foreach (explode(',', $header) as $part) {
        [$k, $v] = array_pad(explode('=', trim($part), 2), 2, '');
        if ($k === 't') $t = $v;
        if ($k === 'v1') $sigs[] = $v;
    }
    if ($secret === '' || $t === null || !ctype_digit($t) || !$sigs || abs(time() - (int)$t) > $tolerance) return false;
    $expected = hash_hmac('sha256', $t . '.' . $rawBody, $secret);
    foreach ($sigs as $sig) {
        if (hash_equals($expected, $sig)) return true;
    }
    return false;
}

/**
 * Apply a Checkout Session to our payment: paid sessions with the exact amount and currency
 * mark it paid. Returns our payment id, or null if the session isn't ours.
 */
function xpay_apply_session(array $session): ?int
{
    $paymentId = (int)($session['metadata']['payment_id'] ?? 0);
    $pay = $paymentId
        ? q_one('SELECT * FROM payments WHERE id = ?', [$paymentId])
        : q_one('SELECT * FROM payments WHERE provider_ref = ?', ['xpay:' . ($session['id'] ?? '')]);
    if (!$pay) {
        error_log('XPay: session ' . ($session['id'] ?? '?') . ' does not match any payment');
        return null;
    }
    if (($session['paymentStatus'] ?? '') === 'paid') {
        $cents = (int)round((float)$pay['amount'] * 100);
        if ((int)($session['amountTotal'] ?? -1) !== $cents || strtoupper((string)($session['currency'] ?? '')) !== strtoupper($pay['currency'])) {
            error_log("XPay: amount/currency mismatch for MKW-{$pay['id']}: " . ($session['amountTotal'] ?? '') . ' ' . ($session['currency'] ?? ''));
            return (int)$pay['id'];
        }
        q("UPDATE payments SET provider = 'xpay' WHERE id = ?", [$pay['id']]);
        // Keep the Payment Intent id: refunds from code need it
        $ref = 'xpay:' . ($session['id'] ?? '') . (isset($session['paymentIntent']['id']) ? ' ' . $session['paymentIntent']['id'] : '');
        payment_mark_paid((int)$pay['id'], $ref);
    }
    return (int)$pay['id'];
}

/** POST /xpay/webhook (no CSRF, no session: verified by signature). */
function xpay_webhook(): void
{
    $raw = (string)file_get_contents('php://input');
    if (!xpay_signature_valid($raw, (string)($_SERVER['HTTP_XPAY_SIGNATURE'] ?? ''))) {
        error_log('XPay webhook rejected (bad signature or old timestamp)');
        http_response_code(400);
        exit('invalid signature');
    }
    $event = json_decode($raw, true);
    $type = (string)($event['type'] ?? '');
    $session = $event['data']['object'] ?? null;
    if (is_array($session) && in_array($type, ['checkout.session.completed', 'checkout.session.async_payment_succeeded'], true)) {
        // Safe on retries: payment_mark_paid() does nothing for an already paid payment
        xpay_apply_session($session);
    } elseif (is_array($session) && $type === 'checkout.session.async_payment_failed') {
        error_log('XPay: async payment failed for session ' . ($session['id'] ?? '?'));
    }
    http_response_code(200);
    exit('ok');
}

/** GET /xpay/return/{session id}: the customer is back from XPay's hosted page. */
function xpay_return(string $sessionId): void
{
    $paymentId = null;
    $session = null;
    try {
        $session = xpay_api('GET', '/checkout/sessions/' . rawurlencode($sessionId));
        $paymentId = xpay_apply_session($session);
    } catch (Throwable $ex) {
        error_log((string)$ex);
    }
    $paymentId ??= (int)($_SESSION['online_payment'] ?? 0) ?: null;
    unset($_SESSION['online_payment']);
    if (!$paymentId) redirect(current_user() ? 'dashboard' : 'login');

    $paid = q_one("SELECT 1 FROM payments WHERE id = ? AND status = 'paid'", [$paymentId]);
    if (!$paid && $session && ($session['status'] ?? '') === 'complete') {
        // e.g. Fawry: the session is complete but the money arrives later (async_payment_succeeded)
        flash('info', t('online.confirming'));
    }
    redirect("payments/{$paymentId}");
}
