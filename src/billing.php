<?php
declare(strict_types=1);

/*
 * Subscriptions, orders and payments.
 *
 * Every charge is a row in `payments`. Parents pay online through Paymob (src/paymob.php)
 * or, if enabled, by manual transfer that an admin confirms. Either way
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

// ---------- Promo codes ----------

/** Times a code is used: payments still waiting or already paid (declined ones free the use again). */
function promo_uses(int $promoId, ?int $userId = null, int $exceptPaymentId = 0): int
{
    $sql = "SELECT COUNT(*) n FROM payments WHERE promo_code_id = ? AND status IN ('pending','paid') AND id <> ?";
    $params = [$promoId, $exceptPaymentId];
    if ($userId) {
        $sql .= ' AND user_id = ?';
        $params[] = $userId;
    }
    return (int)q_one($sql, $params)['n'];
}

/** Discount the code gives on this payment, or a translation key explaining why it can't be used. */
function promo_check(?array $promo, array $payment): float|string
{
    $base = (float)($payment['original_amount'] ?? $payment['amount']);
    if (!$promo || !$promo['is_active']) return 'promo.invalid';
    if ($promo['starts_at'] && strtotime($promo['starts_at']) > time()) return 'promo.not_started';
    if ($promo['ends_at'] && strtotime($promo['ends_at']) < time()) return 'promo.expired';
    if ($promo['applies_to'] !== 'all' && $promo['applies_to'] !== $payment['kind']) return 'promo.wrong_kind';
    if ($promo['min_amount'] !== null && $base < (float)$promo['min_amount']) return 'promo.min_amount';
    if ($promo['max_uses'] !== null && promo_uses((int)$promo['id'], null, (int)$payment['id']) >= (int)$promo['max_uses']) return 'promo.used_up';
    if ($promo['max_uses_per_user'] !== null
        && promo_uses((int)$promo['id'], (int)$payment['user_id'], (int)$payment['id']) >= (int)$promo['max_uses_per_user']) return 'promo.already_used';

    $discount = $promo['discount_type'] === 'percent'
        ? round($base * (float)$promo['discount_value'] / 100, 2)
        : (float)$promo['discount_value'];
    return min($discount, $base);
}

/**
 * Apply a code to a waiting payment (replaces any code already on it).
 * Returns null on success or a translation key with the reason it was refused.
 * A 100% discount marks the payment as paid straight away.
 */
function payment_apply_promo(int $paymentId, string $code): ?string
{
    $pay = q_one("SELECT * FROM payments WHERE id = ? AND status = 'pending'", [$paymentId]);
    if (!$pay) return 'promo.invalid';
    $promo = q_one('SELECT * FROM promo_codes WHERE code = ?', [strtoupper(trim($code))]);
    $result = promo_check($promo, $pay);
    if (is_string($result)) return $result;

    $base = (float)($pay['original_amount'] ?? $pay['amount']);
    payment_set_discount($pay, $base, $result, (int)$promo['id'], $promo['code']);
    if ($base - $result <= 0) payment_mark_paid($paymentId, 'PROMO ' . $promo['code']);
    return null;
}

function payment_remove_promo(int $paymentId): void
{
    $pay = q_one("SELECT * FROM payments WHERE id = ? AND status = 'pending'", [$paymentId]);
    if ($pay && $pay['promo_code_id'] !== null) {
        payment_set_discount($pay, (float)($pay['original_amount'] ?? $pay['amount']), 0, null, null);
    }
}

/** Store the discount on the payment, and on the order so its total matches what is paid. */
function payment_set_discount(array $pay, float $base, float $discount, ?int $promoId, ?string $code): void
{
    q('UPDATE payments SET original_amount = ?, discount = ?, amount = ?, promo_code_id = ?, promo_code = ? WHERE id = ?',
      [$base, $discount, $base - $discount, $promoId, $code, $pay['id']]);
    if ($pay['kind'] === 'order') {
        q('UPDATE orders SET discount = ?, total = subtotal + plan_price + shipping_fee - ? WHERE id = ?', [$discount, $discount, $pay['ref_id']]);
    }
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
            // A subscription bought in the same order starts now
            $subId = q_one('SELECT subscription_id FROM orders WHERE id = ?', [$pay['ref_id']])['subscription_id'] ?? null;
            if ($subId) {
                subscription_activate((int)$subId);
                notify((int)$pay['user_id'], 'notify.sub_active');
            }
        }
        $pdo->commit();
        return true;
    } catch (Throwable $ex) {
        $pdo->rollBack();
        throw $ex;
    }
}

/**
 * Decline ('failed') a pending payment or refund ('refunded') a paid one, and cancel
 * what it was for: the subscription, the order (unless already shipped) or the
 * replacement request. The parent is notified.
 */
function payment_mark_unpaid(int $paymentId, string $status): bool
{
    $pay = q_one('SELECT * FROM payments WHERE id = ?', [$paymentId]);
    if (!$pay) return false;
    $allowed = ($status === 'failed' && $pay['status'] === 'pending') || ($status === 'refunded' && $pay['status'] === 'paid');
    if (!$allowed) return false;

    q('UPDATE payments SET status = ? WHERE id = ?', [$status, $paymentId]);
    if ($pay['kind'] === 'subscription') {
        q("UPDATE subscriptions SET status = 'cancelled' WHERE id = ?", [$pay['ref_id']]);
    } elseif ($pay['kind'] === 'order') {
        q("UPDATE orders SET status = 'cancelled' WHERE id = ? AND status IN ('pending_payment','paid','processing')", [$pay['ref_id']]);
        q("UPDATE subscriptions s JOIN orders o ON o.subscription_id = s.id SET s.status = 'cancelled' WHERE o.id = ?", [$pay['ref_id']]);
    } else {
        q("UPDATE tag_requests SET status = 'rejected', handled_at = NOW() WHERE id = ? AND status = 'pending'", [$pay['ref_id']]);
    }
    notify((int)$pay['user_id'], $status === 'failed' ? 'notify.payment_failed' : 'notify.payment_refunded',
           ['ref' => 'MKW-' . $paymentId, 'kind' => '@pay.kind.' . $pay['kind']]);
    return true;
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
