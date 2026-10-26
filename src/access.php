<?php
declare(strict_types=1);

/*
 * Access rules:
 *  - parent: only their own children, devices, orders and payments
 *  - admin:  everything (through the /admin pages)
 * The public emergency page is reached only through a device's random token.
 */

/** The parent's own child, or 404 (never reveal that someone else's child exists). */
function own_child(array $user, int $id): array
{
    $child = q_one('SELECT * FROM children WHERE id = ? AND parent_id = ?', [$id, $user['id']]);
    if (!$child) abort(404);
    return $child;
}

function own_tag(array $user, int $id): array
{
    $tag = q_one('SELECT t.*, c.first_name, c.parent_id FROM tags t JOIN children c ON c.id = t.child_id
                  WHERE t.id = ? AND c.parent_id = ?', [$id, $user['id']]);
    if (!$tag) abort(404);
    return $tag;
}

function own_order(array $user, int $id): array
{
    $order = q_one('SELECT * FROM orders WHERE id = ? AND user_id = ?', [$id, $user['id']]);
    if (!$order) abort(404);
    return $order;
}

function emergency_profile(int $childId): array
{
    return q_one('SELECT * FROM emergency_profiles WHERE child_id = ?', [$childId]) ?? [];
}

function emergency_contacts(int $childId): array
{
    return q_all('SELECT * FROM emergency_contacts WHERE child_id = ? ORDER BY sort_order, id', [$childId]);
}
