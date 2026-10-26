<?php
declare(strict_types=1);

/*
 * Paymob Unified Checkout (redirection), Egypt.
 *
 *   1. paymob_checkout_url()  creates an Intention for a waiting payment and returns the
 *                             Unified Checkout link the parent is sent to (cards + wallets).
 *   2. paymob_callback()      Transaction Processed POST callback (server to server).
 *                             This is the source of truth: HMAC-verified, then
 *                             payment_mark_paid() activates the subscription or order.
 *   3. paymob_return()        Browser redirect back after paying (GET). Also HMAC-verified;
 *                             it can confirm the payment too (idempotent), then shows the result.
 *
 * Docs: https://developers.paymob.com/paymob-docs/intention-apis/create-intention
 *       https://developers.paymob.com/paymob-docs/checkout-experiences/unified-checkout-redirection
 *       https://developers.paymob.com/paymob-docs/webhook-callbacks-and-hmac/hmac
 */

/** The 20 fields signed by Paymob's transaction HMAC, in lexicographic order. */
const PAYMOB_HMAC_FIELDS = [
    'amount_cents', 'created_at', 'currency', 'error_occured', 'has_parent_transaction', 'id',
    'integration_id', 'is_3d_secure', 'is_auth', 'is_capture', 'is_refunded', 'is_standalone_payment',
    'is_voided', 'order.id', 'owner', 'pending', 'source_data.pan', 'source_data.sub_type',
    'source_data.type', 'success',
];

function paymob_enabled(): bool
{
    return (bool)config('paymob.enabled', false)
        && config('paymob.secret_key') && config('paymob.public_key') && config('paymob.hmac_secret')
        && config('paymob.integration_ids');
}

function paymob_base(): string
{
    return rtrim((string)config('paymob.base_url', 'https://accept.paymob.com'), '/');
}

/** Value of a dotted key ("order.id") in a nested array. */
function paymob_get(array $data, string $key)
{
    foreach (explode('.', $key) as $part) {
        if (!is_array($data) || !array_key_exists($part, $data)) return null;
        $data = $data[$part];
    }
    return $data;
}

/** Paymob signs booleans as "true"/"false" and missing values as an empty string. */
function paymob_str($v): string
{
    if (is_bool($v)) return $v ? 'true' : 'false';
    return $v === null ? '' : (string)$v;
}

/**
 * Check the HMAC of a transaction. $obj is the POST callback's "obj", or the flat GET
 * query of the redirect (where "order.id" arrives as "order").
 */
function paymob_hmac_valid(array $obj, string $hmac, bool $fromRedirect = false): bool
{
    $secret = (string)config('paymob.hmac_secret', '');
    if ($secret === '' || $hmac === '') return false;
    $concat = '';
    foreach (PAYMOB_HMAC_FIELDS as $field) {
        $value = $fromRedirect
            ? ($obj[$field === 'order.id' ? 'order' : $field] ?? null)
            : paymob_get($obj, $field);
        $concat .= paymob_str($value);
    }
    return hash_equals(hash_hmac('sha512', $concat, $secret), strtolower($hmac));
}

/** Our payment id from the special_reference "MKW-<payment id>-<time>". */
function paymob_payment_id(?string $reference): ?int
{
    return ($reference && preg_match('/^MKW-(\d+)-/', $reference, $m)) ? (int)$m[1] : null;
}

function paymob_request(string $path, array $body): array
{
    $ch = curl_init(paymob_base() . $path);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_HTTPHEADER     => ['Authorization: Token ' . config('paymob.secret_key'), 'Content-Type: application/json'],
        CURLOPT_POSTFIELDS     => json_encode($body, JSON_UNESCAPED_UNICODE),
    ]);
    $raw = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    $data = is_string($raw) ? json_decode($raw, true) : null;
    if ($status < 200 || $status >= 300 || !is_array($data)) {
        throw new RuntimeException("Paymob {$path} failed ({$status}) {$error} " . mb_substr((string)$raw, 0, 500));
    }
    return $data;
}

/**
 * Create an Intention for a waiting payment and return the Unified Checkout URL.
 * A new intention is created on every click, so a changed amount (promo code) is always respected.
 */
function paymob_checkout_url(array $payment): string
{
    $user = q_one('SELECT * FROM users WHERE id = ?', [$payment['user_id']]);
    $order = $payment['kind'] === 'order' ? q_one('SELECT * FROM orders WHERE id = ?', [$payment['ref_id']]) : null;
    $cents = (int)round((float)$payment['amount'] * 100);

    [$first, $last] = array_pad(explode(' ', trim($order['ship_name'] ?? $user['name']), 2), 2, '');
    $billing = [
        'first_name'   => mb_substr($first ?: 'Customer', 0, 50),
        'last_name'    => mb_substr($last ?: '-', 0, 50),
        'email'        => $user['email'],
        'phone_number' => clean_phone((string)($order['ship_phone'] ?? $user['phone'] ?? '')) ?: '+201000000000',
        'street'       => mb_substr($order['ship_address'] ?? 'NA', 0, 100),
        'city'         => $order['ship_city'] ?? 'NA',
        'state'        => $order['ship_governorate'] ?? 'NA',
        'country'      => 'EG',
        'apartment'    => 'NA', 'floor' => 'NA', 'building' => 'NA',
    ];
    $intention = paymob_request('/v1/intention/', [
        'amount'            => $cents,
        'currency'          => config('currency', 'EGP'),
        'payment_methods'   => array_map('intval', (array)config('paymob.integration_ids')),
        // One line for the whole payment, so items always add up to the amount
        'items'             => [[
            'name'        => 'MyKid Wedjat MKW-' . $payment['id'],
            'amount'      => $cents,
            'description' => t('pay.kind.' . $payment['kind'], [], 'en'),
            'quantity'    => 1,
        ]],
        'billing_data'      => $billing,
        'customer'          => ['first_name' => $billing['first_name'], 'last_name' => $billing['last_name'], 'email' => $billing['email']],
        'special_reference' => 'MKW-' . $payment['id'] . '-' . time(),
        'notification_url'  => site_base() . '/paymob/callback',
        'redirection_url'   => site_base() . '/paymob/return',
        'extras'            => ['payment_id' => (int)$payment['id']],
    ]);
    if (empty($intention['client_secret'])) {
        throw new RuntimeException('Paymob intention without client_secret: ' . mb_substr(json_encode($intention), 0, 500));
    }
    return paymob_base() . '/unifiedcheckout/?' . http_build_query([
        'publicKey' => config('paymob.public_key'), 'clientSecret' => $intention['client_secret'],
    ]);
}

/**
 * Apply a verified transaction to our payment. Returns the payment id it belongs to, or null.
 * Safe to call more than once for the same transaction (Paymob retries callbacks).
 */
function paymob_apply_transaction(array $txn, ?string $reference): ?int
{
    $paymentId = paymob_payment_id($reference);
    $pay = $paymentId ? q_one('SELECT * FROM payments WHERE id = ?', [$paymentId]) : null;
    if (!$pay) {
        error_log('Paymob: transaction ' . paymob_str($txn['id'] ?? '') . " for unknown reference {$reference}");
        return null;
    }
    $bool = fn($v) => $v === true || $v === 'true';
    $txnId = 'paymob:' . paymob_str($txn['id'] ?? '');

    if (($bool($txn['is_refunded'] ?? false) || $bool($txn['is_voided'] ?? false)) && $pay['status'] === 'paid') {
        payment_mark_unpaid($paymentId, 'refunded');
    } elseif ($bool($txn['success'] ?? false) && !$bool($txn['pending'] ?? false)) {
        // Only accept the exact amount and currency of this payment
        $cents = (int)round((float)$pay['amount'] * 100);
        if ((int)($txn['amount_cents'] ?? -1) !== $cents || strtoupper(paymob_str($txn['currency'] ?? '')) !== strtoupper($pay['currency'])) {
            error_log("Paymob: amount/currency mismatch for MKW-{$paymentId}: " . paymob_str($txn['amount_cents'] ?? '') . ' ' . paymob_str($txn['currency'] ?? ''));
            return $paymentId;
        }
        q("UPDATE payments SET provider = 'paymob' WHERE id = ?", [$paymentId]);
        payment_mark_paid($paymentId, $txnId);
    }
    // A declined card leaves the payment waiting, so the parent can try again
    return $paymentId;
}

// ---------- Routes ----------

/** POST /payments/{id}/paymob: send the parent to Paymob's checkout. */
function payment_paymob_start(int $id): void
{
    $user = require_parent();
    $payment = q_one("SELECT * FROM payments WHERE id = ? AND user_id = ? AND status = 'pending'", [$id, $user['id']]);
    if (!$payment || !paymob_enabled() || (float)$payment['amount'] <= 0) redirect("payments/{$id}");
    try {
        $url = paymob_checkout_url($payment);
    } catch (Throwable $ex) {
        error_log((string)$ex);
        flash('error', t('paymob.start_failed') . (config('debug', false) ? ' — ' . $ex->getMessage() : ''));
        redirect("payments/{$id}");
    }
    $_SESSION['paymob_payment'] = $id; // to find the payment again when the browser comes back
    $_SESSION['paymob_url'] = $url;
    // Go through our own page instead of redirecting the form to Paymob: browsers block a
    // form submission that ends on another site (CSP form-action), which looked like "nothing happens".
    redirect("payments/{$id}/paymob");
}

/** GET /payments/{id}/paymob: "Taking you to Paymob…" page that opens the checkout as a normal link. */
function payment_paymob_go(int $id): void
{
    require_parent();
    $url = (string)($_SESSION['paymob_url'] ?? '');
    unset($_SESSION['paymob_url']);
    if (!str_starts_with($url, paymob_base() . '/')) redirect("payments/{$id}");
    view('billing/paymob_redirect', ['title' => t('pay.title'), 'url' => $url, 'paymentId' => $id], 'layout_plain');
}

/** POST /paymob/callback: Transaction Processed callback from Paymob (no CSRF, no session). */
function paymob_callback(): void
{
    $body = json_decode((string)file_get_contents('php://input'), true);
    $obj = is_array($body) ? ($body['obj'] ?? null) : null;
    $hmac = (string)($_GET['hmac'] ?? ($body['hmac'] ?? ''));
    if (!is_array($obj) || ($body['type'] ?? 'TRANSACTION') !== 'TRANSACTION' || !paymob_hmac_valid($obj, $hmac)) {
        error_log('Paymob callback rejected (bad HMAC or payload)');
        http_response_code(is_array($obj) ? 401 : 200); // non-transaction callbacks are acknowledged and ignored
        exit;
    }
    paymob_apply_transaction($obj, paymob_get($obj, 'order.merchant_order_id') ?? paymob_get($obj, 'special_reference'));
    http_response_code(200);
    echo 'OK';
    exit;
}

/**
 * The raw query string as key => value. $_GET can't be used: PHP turns the dots in
 * Paymob's keys ("source_data.pan") into underscores, which would break the HMAC check.
 */
function paymob_query(): array
{
    $out = [];
    foreach (explode('&', (string)($_SERVER['QUERY_STRING'] ?? '')) as $pair) {
        if ($pair === '') continue;
        [$k, $v] = array_pad(explode('=', $pair, 2), 2, '');
        $out[urldecode($k)] = urldecode($v);
    }
    return $out;
}

/** GET /paymob/return: the parent's browser comes back from Paymob. */
function paymob_return(): void
{
    $q = paymob_query();
    $verified = paymob_hmac_valid($q, (string)($q['hmac'] ?? ''), true);
    $paymentId = null;
    if ($verified) {
        $paymentId = paymob_apply_transaction($q, $q['merchant_order_id'] ?? null);
    }
    $paymentId ??= (int)($_SESSION['paymob_payment'] ?? 0) ?: null;
    unset($_SESSION['paymob_payment']);
    if (!$paymentId) redirect(current_user() ? 'dashboard' : 'login');

    // The payment page itself shows "paid"; only explain the other outcomes, and only from a signed redirect
    $paid = q_one("SELECT 1 FROM payments WHERE id = ? AND status = 'paid'", [$paymentId]);
    if (!$paid && $verified) {
        $waiting = ($q['success'] ?? '') === 'true' || ($q['pending'] ?? '') === 'true';
        flash($waiting ? 'info' : 'error', t($waiting ? 'paymob.confirming' : 'paymob.declined'));
    }
    redirect("payments/{$paymentId}");
}
