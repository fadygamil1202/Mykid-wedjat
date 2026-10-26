<?php
declare(strict_types=1);

/**
 * What the emergency page shows for a child.
 * Medical details need an active subscription and the parent's consent; without
 * them the page still shows the first name and the call buttons, never a blank page.
 */
function emergency_page_data(array $child, int $parentId): array
{
    $p = emergency_profile((int)$child['id']);
    $subscribed = (bool)active_subscription($parentId);
    $full = $subscribed && !empty($p['consent_at']);
    return [
        'child'      => $child,
        'p'          => $p,
        'full'       => $full,
        'subscribed' => $subscribed,
        'age'        => ($full && $p['show_age']) ? age_from_dob($child['date_of_birth']) : null,
        'allergies'  => ($full && $p['show_allergies']) ? (json_decode($p['allergies'] ?? '[]', true) ?: []) : [],
        'contacts'   => q_all('SELECT id, name, relation FROM emergency_contacts WHERE child_id=? ORDER BY sort_order, id', [$child['id']]),
    ];
}

function load_tag_by_token(string $token): array
{
    if (!rate_limit('public', (int)config('public_rate_limit', 20), 60)) {
        abort(429);
    }
    $tag = q_one('SELECT t.*, c.first_name, c.date_of_birth, c.parent_id FROM tags t
                  JOIN children c ON c.id = t.child_id WHERE t.token = ?', [$token]);
    if (!$tag) abort(404, t('public.not_found'));
    return $tag;
}

function log_scan(array $tag, string $action): void
{
    q('INSERT INTO scan_logs (tag_id, child_id, action, ip_hash, user_agent) VALUES (?,?,?,?,?)', [
        $tag['id'], $tag['child_id'], $action, client_ip_hash(), mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
    ]);
}

function public_emergency(string $token): void
{
    $tag = load_tag_by_token($token);
    $parentId = (int)$tag['parent_id'];

    // Log every scan; tell the parent at most once per 10 minutes per device.
    $recent = q_one("SELECT 1 FROM scan_logs WHERE tag_id=? AND action='view' AND created_at > NOW() - INTERVAL 10 MINUTE", [$tag['id']]);
    log_scan($tag, 'view');
    if (!$recent) {
        $key = $tag['status'] === 'active' ? 'notify.scan' : 'notify.scan_locked';
        notify($parentId, $key, ['name' => $tag['first_name'], 'type' => '@tag.' . $tag['type'], 'time' => date('H:i')]);
    }

    header('X-Robots-Tag: noindex, nofollow');
    header('Cache-Control: no-store');
    if ($tag['status'] !== 'active') {
        view('public/locked', ['title' => t('public.title')], 'layout_plain');
        return;
    }
    $child = ['id' => $tag['child_id'], 'first_name' => $tag['first_name'], 'date_of_birth' => $tag['date_of_birth']];
    view('public/emergency', emergency_page_data($child, $parentId) + [
        'title' => t('public.title'), 'token' => $token, 'preview' => false,
    ], 'layout_plain');
}

/**
 * "Call": the number is never printed on the page. The tap is logged, the parent
 * is notified, then the phone's dialer opens.
 */
function public_call(string $token, int $contactId): void
{
    $tag = load_tag_by_token($token);
    if ($tag['status'] !== 'active') abort(404, t('public.not_found'));
    $contact = q_one('SELECT * FROM emergency_contacts WHERE id=? AND child_id=?', [$contactId, $tag['child_id']]);
    if (!$contact) abort(404, t('public.no_contact'));

    log_scan($tag, 'call');
    notify((int)$tag['parent_id'], 'notify.call', ['name' => $tag['first_name'], 'contact' => $contact['name'], 'time' => date('H:i')]);
    header('Location: tel:' . clean_phone($contact['phone']));
    exit;
}
