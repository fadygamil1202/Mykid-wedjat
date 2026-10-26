<?php
declare(strict_types=1);

// ---------- Children ----------

function validate_child_input(): ?array
{
    $first = input('first_name');
    $last = input('last_name');
    $dob = input('date_of_birth');
    if ($first === '' || $last === '' || mb_strlen($first) > 100 || mb_strlen($last) > 100) {
        flash('error', t('error.required', ['field' => t('field.name')]));
        return null;
    }
    if ($dob !== '' && (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob) || strtotime($dob) === false || strtotime($dob) > time())) {
        flash('error', t('error.invalid_date'));
        return null;
    }
    return ['first_name' => $first, 'last_name' => $last, 'date_of_birth' => $dob ?: null];
}

function child_new(): void
{
    require_parent();
    view('children/form', ['title' => t('children.new'), 'child' => null]);
}

function child_create(): void
{
    $user = require_parent();
    $d = validate_child_input();
    if (!$d) redirect('children/new');
    if (q_one('SELECT COUNT(*) n FROM children WHERE parent_id=?', [$user['id']])['n'] >= 10) {
        flash('error', t('children.limit'));
        redirect('dashboard');
    }
    q('INSERT INTO children (parent_id, first_name, last_name, date_of_birth) VALUES (?,?,?,?)',
      [$user['id'], $d['first_name'], $d['last_name'], $d['date_of_birth']]);
    $id = (int)db()->lastInsertId();
    q('INSERT INTO emergency_profiles (child_id) VALUES (?)', [$id]);
    if ($user['phone']) {
        q('INSERT INTO emergency_contacts (child_id, name, relation, phone) VALUES (?,?,?,?)',
          [$id, $user['name'], t('children.parent_relation'), $user['phone']]);
    }
    flash('success', t('children.created'));
    redirect("children/{$id}/emergency");
}

function child_show(int $id): void
{
    $user = require_parent();
    $child = own_child($user, $id);
    $tags = q_all("SELECT t.*,
            (SELECT COUNT(*) FROM scan_logs l WHERE l.tag_id=t.id) AS scans,
            (SELECT MAX(created_at) FROM scan_logs l WHERE l.tag_id=t.id) AS last_scan,
            (SELECT status FROM tag_requests r WHERE r.tag_id=t.id ORDER BY r.id DESC LIMIT 1) AS request_status
        FROM tags t WHERE t.child_id=? AND t.status<>'replaced' ORDER BY t.created_at DESC", [$id]);
    view('children/show', [
        'title'    => $child['first_name'],
        'child'    => $child,
        'profile'  => emergency_profile($id),
        'contacts' => emergency_contacts($id),
        'tags'     => $tags,
        'scans'    => q_all('SELECT l.*, t.type FROM scan_logs l JOIN tags t ON t.id=l.tag_id
                             WHERE l.child_id=? ORDER BY l.created_at DESC LIMIT 15', [$id]),
        'sub'      => active_subscription((int)$user['id']),
    ]);
}

function child_edit(int $id): void
{
    $user = require_parent();
    view('children/form', ['title' => t('children.edit'), 'child' => own_child($user, $id)]);
}

function child_update(int $id): void
{
    $user = require_parent();
    own_child($user, $id);
    $d = validate_child_input();
    if (!$d) redirect("children/{$id}/edit");
    q('UPDATE children SET first_name=?, last_name=?, date_of_birth=? WHERE id=?',
      [$d['first_name'], $d['last_name'], $d['date_of_birth'], $id]);
    flash('success', t('saved'));
    redirect("children/{$id}");
}

function child_delete(int $id): void
{
    $user = require_parent();
    own_child($user, $id);
    q('DELETE FROM children WHERE id=?', [$id]);
    flash('success', t('children.deleted'));
    redirect('dashboard');
}

// ---------- Emergency information ----------

function emergency_edit(int $id): void
{
    $user = require_parent();
    $child = own_child($user, $id);
    view('children/emergency', [
        'title'    => t('emergency.edit_title'),
        'child'    => $child,
        'profile'  => emergency_profile($id),
        'contacts' => emergency_contacts($id),
    ]);
}

function emergency_update(int $id): void
{
    $user = require_parent();
    own_child($user, $id);

    // Contacts: up to 3 rows; a row needs a name and a valid phone
    $contacts = [];
    $names = (array)($_POST['contact_name'] ?? []);
    for ($i = 0; $i < 3; $i++) {
        $name = trim((string)($names[$i] ?? ''));
        $phone = trim((string)($_POST['contact_phone'][$i] ?? ''));
        $relation = trim((string)($_POST['contact_relation'][$i] ?? ''));
        if ($name === '' && $phone === '') continue;
        if ($name === '' || !valid_phone($phone)) {
            flash('error', t('emergency.contact_invalid', ['n' => $i + 1]));
            redirect("children/{$id}/emergency");
        }
        $contacts[] = [mb_substr($name, 0, 100), mb_substr($relation, 0, 50), $phone];
    }
    if (!$contacts) {
        flash('error', t('emergency.contact_required'));
        redirect("children/{$id}/emergency");
    }

    $blood = input('blood_type');
    if (!in_array($blood, BLOOD_TYPES, true)) $blood = null;
    $flags = [];
    foreach (['show_age', 'show_blood_type', 'show_allergies', 'show_conditions', 'show_medications', 'show_care_notes'] as $f) {
        $flags[$f] = isset($_POST[$f]) ? 1 : 0;
    }
    $consentAt = emergency_profile($id)['consent_at'] ?? null;
    if (!$consentAt && isset($_POST['consent'])) $consentAt = date('Y-m-d H:i:s');

    $pdo = db();
    $pdo->beginTransaction();
    q('UPDATE emergency_profiles SET blood_type=?, allergies=?, conditions=?, medications=?, care_notes=?,
       show_age=?, show_blood_type=?, show_allergies=?, show_conditions=?, show_medications=?, show_care_notes=?,
       consent_at=? WHERE child_id=?', [
        $blood,
        json_encode(parse_allergies(input('allergies')), JSON_UNESCAPED_UNICODE),
        mb_substr(input('conditions'), 0, 1000) ?: null,
        mb_substr(input('medications'), 0, 1000) ?: null,
        mb_substr(input('care_notes'), 0, 1000) ?: null,
        $flags['show_age'], $flags['show_blood_type'], $flags['show_allergies'], $flags['show_conditions'],
        $flags['show_medications'], $flags['show_care_notes'], $consentAt, $id,
    ]);
    q('DELETE FROM emergency_contacts WHERE child_id=?', [$id]);
    foreach ($contacts as $i => [$name, $relation, $phone]) {
        q('INSERT INTO emergency_contacts (child_id, name, relation, phone, sort_order) VALUES (?,?,?,?,?)',
          [$id, $name, $relation ?: null, $phone, $i]);
    }
    $pdo->commit();

    flash('success', $consentAt ? t('saved') : t('emergency.saved_no_consent'));
    redirect("children/{$id}");
}

/** How the card looks. The QR code is never sent to the parent's browser. */
function child_card(int $id): void
{
    $user = require_parent();
    $child = own_child($user, $id);
    view('children/card', [
        'title'   => t('card.title'),
        'child'   => $child,
        'profile' => emergency_profile($id),
    ]);
}

/** The emergency page exactly as a finder would see it, without exposing any device link. */
function child_preview(int $id): void
{
    $user = require_parent();
    $child = own_child($user, $id);
    $data = emergency_page_data($child, (int)$user['id']);
    view('public/emergency', $data + ['title' => t('public.title'), 'token' => '', 'preview' => true], 'layout_plain');
}

// ---------- Devices: lock / unlock / replace ----------

function tag_lock(int $id): void
{
    $user = require_parent();
    $tag = own_tag($user, $id);
    if ($tag['status'] === 'active') {
        q("UPDATE tags SET status='locked', locked_at=NOW() WHERE id=?", [$id]);
        flash('success', t('tags.locked_msg'));
    }
    redirect("children/{$tag['child_id']}");
}

function tag_unlock(int $id): void
{
    $user = require_parent();
    $tag = own_tag($user, $id);
    $pending = q_one("SELECT 1 FROM tag_requests WHERE tag_id=? AND status='pending'", [$id]);
    if ($tag['status'] === 'locked' && !$pending) {
        q("UPDATE tags SET status='active', locked_at=NULL WHERE id=?", [$id]);
        flash('success', t('tags.unlocked_msg'));
    } elseif ($pending) {
        flash('error', t('tags.unlock_pending'));
    }
    redirect("children/{$tag['child_id']}");
}

function tag_replace_form(int $id): void
{
    $user = require_parent();
    $tag = own_tag($user, $id);
    if ($tag['status'] === 'replaced') abort(404);
    if (q_one("SELECT 1 FROM tag_requests WHERE tag_id=? AND status='pending'", [$id])) {
        flash('info', t('tags.request_exists'));
        redirect("children/{$tag['child_id']}");
    }
    view('children/replace', ['title' => t('tags.replace_title'), 'tag' => $tag, 'fee' => (float)config('replacement_fee', 0)]);
}

function tag_replace_submit(int $id): void
{
    $user = require_parent();
    $tag = own_tag($user, $id);
    if ($tag['status'] === 'replaced' || q_one("SELECT 1 FROM tag_requests WHERE tag_id=? AND status='pending'", [$id])) {
        redirect("children/{$tag['child_id']}");
    }
    $reason = input('reason');
    if (!in_array($reason, REQUEST_REASONS, true)) $reason = 'other';

    // The old device stops working right away: a lost or stolen device must never open the page.
    q("UPDATE tags SET status='locked', locked_at=COALESCE(locked_at, NOW()) WHERE id=?", [$id]);
    q('INSERT INTO tag_requests (tag_id, user_id, reason, notes) VALUES (?,?,?,?)',
      [$id, $user['id'], $reason, mb_substr(input('notes'), 0, 500) ?: null]);
    $requestId = (int)db()->lastInsertId();
    notify_admins('notify.admin_request', ['name' => $tag['first_name'], 'type' => '@tag.' . $tag['type']]);

    $fee = (float)config('replacement_fee', 0);
    if ($fee > 0) {
        $paymentId = payment_create((int)$user['id'], 'replacement', $requestId, $fee);
        flash('success', t('tags.request_sent_pay'));
        redirect("payments/{$paymentId}");
    }
    flash('success', t('tags.request_sent'));
    redirect("children/{$tag['child_id']}");
}
