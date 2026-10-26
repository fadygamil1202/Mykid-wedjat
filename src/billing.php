<?php
declare(strict_types=1);

/*
 * Subscriptions, orders and payments.
 *
 * Every charge is a row in `payments`. Today the provider is 'manual': the parent
 * sees transfer instructions and an admin marks the payment as paid. To connect a
 * gateway (Paymob, Fawry, Kashier, ...):
 *   1. In payment_checkout_url(), create the gateway's checkout session and return its URL.
 *   2. Add a callback route that verifies the gateway's signature, then calls
 *      payment_mark_paid($paymentId, $gatewayReference).
 * payment_mark_paid() is the single place that activates subscriptions and orders.
 */

function plans(bool $activeOnly = true): array
{
    return q_all('SELECT * FROM plans' . ($activeOnly ? ' WHERE is_active = 1' : '') . ' ORDER BY interval_months');
}

/** The subscription currently in force for this parent, or null. */
function active_subscription(int $userId): ?array
{
    return q_one("SELECT s.*, p.code, p.name_en, p.name_ar FROM subscriptions s JOIN plans p ON p.id = s.plan_id
                  WHERE s.user_id = ? AND s.status = 'active' AND s.ends_at > NOW()
                  ORDER BY s.ends_at DESC LIMIT 1", [$userId]);
}

function payment_create(int $userId, string $kind, int $refId, float $amount): int
{
    q('INSERT INTO payments (user_id, kind, ref_id, amount, currency, provider) VALUES (?,?,?,?,?,?)',
      [$userId, $kind, $refId, $amount, config('currency', 'EGP'), config('payment.provider', 'manual')]);
    return (int)db()->lastInsertId();
}

/** Where to send the parent to pay. Returns null while payments are manual. */
function payment_checkout_url(array $payment): ?string
{
    return null;
}

/** Mark a payment as paid and activate what it pays for. Safe to call twice. */
function payment_mark_paid(int $paymentId, ?string $providerRef = null): bool
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $pay = q_one('SELECT * FROM payments WHERE id = ? FOR UPDATE', [$paymentId]);
        if (!$pay || $pay['status'] === 'paid') {
            $pdo->rollBack();
            return false;
        }
        q("UPDATE payments SET status = 'paid', paid_at = NOW(), provider_ref = COALESCE(?, provider_ref) WHERE id = ?",
          [$providerRef, $paymentId]);

        if ($pay['kind'] === 'subscription') {
            subscription_activate((int)$pay['ref_id']);
            notify((int)$pay['user_id'], 'notify.sub_active');
        } elseif ($pay['kind'] === 'order') {
            q("UPDATE orders SET status = 'paid' WHERE id = ? AND status = 'pending_payment'", [$pay['ref_id']]);
            notify((int)$pay['user_id'], 'notify.order_paid', ['id' => $pay['ref_id']]);
        }
        $pdo->commit();
        return true;
    } catch (Throwable $ex) {
        $pdo->rollBack();
        throw $ex;
    }
}

/** Start the subscription now, or right after the current one ends (renewal). */
function subscription_activate(int $subscriptionId): void
{
    $sub = q_one('SELECT s.*, p.interval_months FROM subscriptions s JOIN plans p ON p.id = s.plan_id WHERE s.id = ?', [$subscriptionId]);
    if (!$sub || $sub['status'] === 'active') return;
    $current = active_subscription((int)$sub['user_id']);
    $start = $current ? new DateTime($current['ends_at']) : new DateTime();
    $end = (clone $start)->modify('+' . (int)$sub['interval_months'] . ' months');
    q("UPDATE subscriptions SET status = 'active', starts_at = ?, ends_at = ? WHERE id = ?",
      [$start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s'), $subscriptionId]);
}

/** Create a device for a child. The token is what goes on the NFC chip and QR code. */
function tag_create(int $childId, string $type, ?int $orderItemId = null, ?int $replacesTagId = null): int
{
    q('INSERT INTO tags (child_id, type, token, order_item_id, replaces_tag_id) VALUES (?,?,?,?,?)',
      [$childId, $type, random_token(), $orderItemId, $replacesTagId]);
    return (int)db()->lastInsertId();
}

/** Display state of a subscription row: active, expired, pending or cancelled. */
function sub_state(array $s): string
{
    if ($s['status'] === 'active') {
        return strtotime((string)$s['ends_at']) > time() ? 'active' : 'expired';
    }
    return $s['status'];
}
