<?php
declare(strict_types=1);

/*
 * Devices made in batches.
 *
 * Stock here is the website (online) stock: it drops through orders (reserved at checkout).
 *
 *   1. Admin > Devices > Generate: N unassigned devices of a product, each with a secret link
 *      (token, written to the NFC chip / QR) and a random serial printed on the device.
 *      The batch downloads as a CSV. An "online" batch adds N to stock; a batch made for
 *      stores and events doesn't touch it.
 *   2. Devices are shipped unassigned; the parent activates each one with its serial
 *      (never changes stock: an online sale was counted at checkout, a store sale isn't online stock).
 *   3. Replacement (−1), reset of a returned device (+1, then admin must allow activation again)
 *      and write-off (−1) change stock only for devices from online batches.
 */

/** Serial alphabet without look-alikes (no 0/O, 1/I/L). 10 characters ≈ 50 bits. */
const SERIAL_ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
const SERIAL_LENGTH = 10;

function serial_new(): string
{
    $s = '';
    for ($i = 0; $i < SERIAL_LENGTH; $i++) $s .= SERIAL_ALPHABET[random_int(0, strlen(SERIAL_ALPHABET) - 1)];
    return $s;
}

/** K7QM3XRP9D -> K7QM-3XRP-9D */
function serial_format(?string $serial): string
{
    return $serial ? implode('-', str_split($serial, 4)) : '—';
}

/**
 * The device someone typed or pasted: a serial (any case, with or without dashes/spaces),
 * or the device's link (what a phone shows after scanning its QR code).
 */
function device_find(string $input): ?array
{
    $input = trim($input);
    if (preg_match('#/e/([a-f0-9]{32})#', $input, $m)) {
        return q_one('SELECT * FROM tags WHERE token = ?', [$m[1]]);
    }
    $serial = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $input));
    if (strlen($serial) !== SERIAL_LENGTH) return null;
    return q_one('SELECT * FROM tags WHERE serial = ?', [$serial]);
}

/** Give devices made before batches existed a serial number. */
function devices_backfill_serials(): void
{
    foreach (q_all('SELECT id FROM tags WHERE serial IS NULL LIMIT 500') as $t) {
        do {
            $serial = serial_new();
        } while (q_one('SELECT 1 FROM tags WHERE serial = ?', [$serial]));
        q('UPDATE tags SET serial = ? WHERE id = ? AND serial IS NULL', [$serial, $t['id']]);
    }
}

/** Does this device belong to the website stock? (devices made before batches existed do) */
function device_online(array $tag): bool
{
    return !$tag['batch_id'] || (bool)(q_one('SELECT online FROM device_batches WHERE id = ?', [$tag['batch_id']])['online'] ?? 1);
}

/**
 * Create a batch of unassigned devices. An online batch adds them to the product's stock;
 * a batch for stores and events doesn't. Returns the batch id.
 */
function device_batch_create(array $product, int $qty, string $note, int $adminId, bool $online = true): int
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        q('INSERT INTO device_batches (product_id, qty, online, note, created_by) VALUES (?,?,?,?,?)',
          [$product['id'], $qty, $online ? 1 : 0, mb_substr($note, 0, 190) ?: null, $adminId]);
        $batchId = (int)$pdo->lastInsertId();
        for ($i = 0; $i < $qty; $i++) {
            // Retry on the (very unlikely) clash with an existing serial or token
            for ($try = 0; ; $try++) {
                try {
                    q("INSERT INTO tags (type, product_id, token, serial, batch_id, status) VALUES (?,?,?,?,?,'unassigned')",
                      [$product['type'], $product['id'], random_token(), serial_new(), $batchId]);
                    break;
                } catch (PDOException $ex) {
                    if ($try >= 5 || ($ex->errorInfo[1] ?? 0) !== 1062) throw $ex;
                }
            }
        }
        if ($online) {
            // Devices are the online stock: a product starts being tracked with its first online batch
            q('UPDATE products SET stock = COALESCE(stock, 0) WHERE id = ?', [$product['id']]);
            stock_put((int)$product['id'], $qty, 'produced', ['user_id' => $adminId, 'note' => t('devices.batch_n', ['id' => $batchId], 'en')]);
        }
        $pdo->commit();
        return $batchId;
    } catch (Throwable $ex) {
        $pdo->rollBack();
        throw $ex;
    }
}

/**
 * Take one unit off a product's stock for a device leaving the shelf outside an order
 * (activation, replacement, void). The device physically exists, so this never refuses:
 * if units are held by unpaid orders the stock can go below zero, and admins are told.
 */
function stock_take_device(?int $productId, string $reason, array $extra): void
{
    if (!$productId) return;
    $p = q_one('SELECT id, stock, name_en FROM products WHERE id = ? FOR UPDATE', [$productId]);
    if (!$p || $p['stock'] === null) return;
    $after = (int)$p['stock'] - 1;
    q('UPDATE products SET stock = ? WHERE id = ?', [$after, $productId]);
    stock_log($productId, -1, $after, $reason, $extra);
    if ($after < 0) notify_admins('notify.admin_oversold', ['product' => $p['name_en'], 'n' => -$after]);
}

/**
 * Put an unassigned device on a child. $how: 'activated' or 'order' never change stock
 * (an online sale was counted at checkout); 'replacement' takes one unit if the device is
 * from an online batch. Returns an error key or null.
 */
function device_assign(array $tag, int $childId, string $how, array $extra = []): ?string
{
    $pdo = db();
    $own = !$pdo->inTransaction();
    if ($own) $pdo->beginTransaction();
    try {
        $tag = q_one('SELECT * FROM tags WHERE id = ? FOR UPDATE', [$tag['id']]);
        if (!$tag || $tag['status'] !== 'unassigned') {
            if ($own) $pdo->rollBack();
            return 'devices.not_on_shelf';
        }
        q("UPDATE tags SET child_id = ?, status = 'active', assigned_at = NOW(), locked_at = NULL,
           order_item_id = ?, replaces_tag_id = ? WHERE id = ?",
          [$childId, $extra['order_item_id'] ?? null, $extra['replaces_tag_id'] ?? null, $tag['id']]);
        if ($how === 'replacement' && device_online($tag)) {
            stock_take_device($tag['product_id'] ? (int)$tag['product_id'] : null, $how,
                ['tag_id' => $tag['id'], 'tag_request_id' => $extra['tag_request_id'] ?? null, 'user_id' => $extra['user_id'] ?? null]);
        }
        if ($own) $pdo->commit();
        return null;
    } catch (Throwable $ex) {
        if ($own) $pdo->rollBack();
        throw $ex;
    }
}

/** A returned device goes back on the shelf; the old family's link and history are removed. */
function device_reset(int $tagId, int $adminId): void
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $tag = q_one("SELECT * FROM tags WHERE id = ? AND status IN ('active','locked','replaced') FOR UPDATE", [$tagId]);
        if ($tag) {
            q('DELETE FROM scan_logs WHERE tag_id = ?', [$tagId]);
            q("UPDATE tags SET child_id = NULL, status = 'unassigned', self_activation = 0, order_item_id = NULL,
               replaces_tag_id = NULL, assigned_at = NULL, locked_at = NULL WHERE id = ?", [$tagId]);
            if ($tag['product_id'] && device_online($tag)) {
                q('UPDATE products SET stock = COALESCE(stock, 0) WHERE id = ?', [$tag['product_id']]);
                stock_put((int)$tag['product_id'], 1, 'returned', ['tag_id' => $tagId, 'user_id' => $adminId]);
            }
        }
        $pdo->commit();
    } catch (Throwable $ex) {
        $pdo->rollBack();
        throw $ex;
    }
}

/** Write off a damaged or missing device that is still on the shelf. */
function device_void(int $tagId, int $adminId): void
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $tag = q_one("SELECT * FROM tags WHERE id = ? AND status = 'unassigned' FOR UPDATE", [$tagId]);
        if ($tag) {
            q("UPDATE tags SET status = 'void' WHERE id = ?", [$tagId]);
            if (device_online($tag)) stock_take_device($tag['product_id'] ? (int)$tag['product_id'] : null, 'void', ['tag_id' => $tagId, 'user_id' => $adminId]);
        }
        $pdo->commit();
    } catch (Throwable $ex) {
        $pdo->rollBack();
        throw $ex;
    }
}
