<?php
declare(strict_types=1);

/*
 * Stock management.
 *
 *   products.stock   NULL = not tracked (unlimited); a number = units available to sell.
 *
 * An order reserves its units at checkout (orders.stock_reserved = 1). Payment turns the
 * reservation into a sale; a cancelled, declined or expired order gives the units back.
 * Unpaid orders expire after 'stock.hold_minutes' (15). Starting an online payment restarts
 * the timer, and a cash/Fawry-style payment that finishes later holds for 'stock.async_hold_hours'.
 * Every change is written to stock_movements.
 */

function low_stock_threshold(): int
{
    return (int)config('stock.low_threshold', 5);
}

/** How many units of a product can still be sold (null = unlimited). */
function product_available(array $product): ?int
{
    return $product['stock'] === null ? null : max(0, (int)$product['stock']);
}

/** Log one stock change and warn admins when a product runs low or out. */
function stock_log(int $productId, int $change, int $after, string $reason, array $extra = []): void
{
    q('INSERT INTO stock_movements (product_id, change_qty, stock_after, reason, order_id, tag_request_id, user_id, note)
       VALUES (?,?,?,?,?,?,?,?)', [
        $productId, $change, $after, $reason, $extra['order_id'] ?? null, $extra['tag_request_id'] ?? null,
        $extra['user_id'] ?? null, isset($extra['note']) ? mb_substr((string)$extra['note'], 0, 190) : null,
    ]);
    if ($change < 0) {
        $before = $after - $change;
        $name = q_one('SELECT name_en FROM products WHERE id = ?', [$productId])['name_en'] ?? '#' . $productId;
        if ($after <= 0 && $before > 0) {
            notify_admins('notify.admin_out_of_stock', ['product' => $name]);
        } elseif ($after <= low_stock_threshold() && $before > low_stock_threshold()) {
            notify_admins('notify.admin_low_stock', ['product' => $name, 'n' => $after]);
        }
    }
}

/**
 * Take $qty units of a product. Must run inside a transaction. Locks the product row,
 * so two buyers can never take the same last unit. Returns false if not enough stock.
 */
function stock_take(int $productId, int $qty, string $reason, array $extra = []): bool
{
    $p = q_one('SELECT id, stock FROM products WHERE id = ? FOR UPDATE', [$productId]);
    if (!$p) return false;
    if ($p['stock'] === null) return true; // not tracked
    if ((int)$p['stock'] < $qty) return false;
    $after = (int)$p['stock'] - $qty;
    q('UPDATE products SET stock = ? WHERE id = ?', [$after, $productId]);
    stock_log($productId, -$qty, $after, $reason, $extra);
    return true;
}

/** Give units back (must run inside a transaction). */
function stock_put(int $productId, int $qty, string $reason, array $extra = []): void
{
    $p = q_one('SELECT id, stock FROM products WHERE id = ? FOR UPDATE', [$productId]);
    if (!$p || $p['stock'] === null) return;
    $after = (int)$p['stock'] + $qty;
    q('UPDATE products SET stock = ? WHERE id = ?', [$after, $productId]);
    stock_log($productId, $qty, $after, $reason, $extra);
}

/** Units of each product in an order: [product_id => qty]. */
function order_quantities(int $orderId): array
{
    $out = [];
    foreach (q_all('SELECT product_id, qty FROM order_items WHERE order_id = ?', [$orderId]) as $i) {
        $out[(int)$i['product_id']] = ($out[(int)$i['product_id']] ?? 0) + (int)$i['qty'];
    }
    return $out;
}

/**
 * Reserve all units of an order (inside the caller's transaction). Returns null on success,
 * or the product row that ran out. Nothing is taken unless everything is available.
 */
function order_reserve_stock(int $orderId): ?array
{
    $qtys = order_quantities($orderId);
    ksort($qtys); // always lock in the same order, so two checkouts can't deadlock
    foreach ($qtys as $productId => $qty) {
        $p = q_one('SELECT * FROM products WHERE id = ? FOR UPDATE', [$productId]);
        if ($p && $p['stock'] !== null && (int)$p['stock'] < $qty) return $p;
    }
    foreach ($qtys as $productId => $qty) {
        stock_take($productId, $qty, 'order', ['order_id' => $orderId]);
    }
    q('UPDATE orders SET stock_reserved = 1, reserved_until = ? WHERE id = ?',
      [date('Y-m-d H:i:s', time() + 60 * (int)config('stock.hold_minutes', 15)), $orderId]);
    return null;
}

/** Give an order's units back (safe to call twice; runs in its own transaction if none is open). */
function order_release_stock(int $orderId): void
{
    $own = !db()->inTransaction();
    if ($own) db()->beginTransaction();
    try {
        $o = q_one('SELECT id, stock_reserved FROM orders WHERE id = ? FOR UPDATE', [$orderId]);
        if ($o && $o['stock_reserved']) {
            foreach (order_quantities($orderId) as $productId => $qty) {
                stock_put($productId, $qty, 'order_released', ['order_id' => $orderId]);
            }
            q('UPDATE orders SET stock_reserved = 0, reserved_until = NULL WHERE id = ?', [$orderId]);
        }
        if ($own) db()->commit();
    } catch (Throwable $ex) {
        if ($own) db()->rollBack();
        throw $ex;
    }
}

/** Keep an unpaid order's units while the parent is paying (or waiting to pay at a Fawry outlet). */
function order_extend_hold(int $orderId, int $seconds): void
{
    q("UPDATE orders SET reserved_until = GREATEST(COALESCE(reserved_until, NOW()), ?)
       WHERE id = ? AND status = 'pending_payment' AND stock_reserved = 1",
      [date('Y-m-d H:i:s', time() + $seconds), $orderId]);
}

/** Extend the hold of the order behind a payment, if it is an order payment. */
function payment_extend_hold(array $payment, int $seconds): void
{
    if ($payment['kind'] === 'order') order_extend_hold((int)$payment['ref_id'], $seconds);
}

/**
 * Cancel unpaid orders whose hold has run out and give their units back.
 * Cheap; called on shop, cart, checkout and admin pages, so no cron job is needed.
 */
function stock_release_expired(): void
{
    $rows = q_all("SELECT o.id, p.id AS payment_id FROM orders o
                   LEFT JOIN payments p ON p.kind = 'order' AND p.ref_id = o.id AND p.status = 'pending'
                   WHERE o.status = 'pending_payment' AND o.stock_reserved = 1 AND o.reserved_until < NOW()
                   LIMIT 20");
    foreach ($rows as $r) {
        if ($r['payment_id']) {
            // Cancels the order (and a subscription bought with it), releases the units, tells the parent
            payment_mark_unpaid((int)$r['payment_id'], 'failed', 'notify.order_expired');
        } else {
            q("UPDATE orders SET status = 'cancelled' WHERE id = ? AND status = 'pending_payment'", [$r['id']]);
            order_release_stock((int)$r['id']);
        }
    }
}

/** Product that a replacement device of this type is taken from (first visible one of that type). */
function product_for_type(string $type): ?array
{
    return q_one('SELECT * FROM products WHERE type = ? ORDER BY is_active DESC, sort_order, id LIMIT 1', [$type]);
}
