<?php
declare(strict_types=1);

function admin_dashboard(): void
{
    require_admin();
    $n = fn(string $sql) => (float)(q_one($sql)['n'] ?? 0);
    view('admin/dashboard', [
        'title' => t('admin.dashboard'),
        'stats' => [
            'parents'   => $n("SELECT COUNT(*) n FROM users WHERE role='parent'"),
            'children'  => $n('SELECT COUNT(*) n FROM children'),
            'subs'      => $n("SELECT COUNT(DISTINCT user_id) n FROM subscriptions WHERE status='active' AND ends_at > NOW()"),
            'devices'   => $n("SELECT COUNT(*) n FROM tags WHERE status='active'"),
            'revenue'   => $n("SELECT COALESCE(SUM(amount),0) n FROM payments WHERE status='paid' AND paid_at >= DATE_FORMAT(NOW(), '%Y-%m-01')"),
            'revenue_all' => $n("SELECT COALESCE(SUM(amount),0) n FROM payments WHERE status='paid'"),
            'scans'     => $n('SELECT COUNT(*) n FROM scan_logs WHERE created_at > NOW() - INTERVAL 7 DAY'),
            'new_parents' => $n("SELECT COUNT(*) n FROM users WHERE role='parent' AND created_at > NOW() - INTERVAL 30 DAY"),
        ],
        'todo' => [
            'payments' => $n("SELECT COUNT(*) n FROM payments WHERE status='pending'"),
            'orders'   => $n("SELECT COUNT(*) n FROM orders WHERE status IN ('paid','processing')"),
            'requests' => $n("SELECT COUNT(*) n FROM tag_requests WHERE status='pending'"),
            'expiring' => $n("SELECT COUNT(*) n FROM subscriptions WHERE status='active' AND ends_at BETWEEN NOW() AND NOW() + INTERVAL 7 DAY"),
        ],
        'monthly' => q_all("SELECT DATE_FORMAT(paid_at, '%Y-%m') m, SUM(amount) total FROM payments
                            WHERE status='paid' AND paid_at >= DATE_FORMAT(NOW() - INTERVAL 5 MONTH, '%Y-%m-01')
                            GROUP BY m ORDER BY m"),
        'orders' => q_all('SELECT o.*, u.name FROM orders o JOIN users u ON u.id=o.user_id ORDER BY o.id DESC LIMIT 6'),
        'payments' => q_all("SELECT p.*, u.name FROM payments p JOIN users u ON u.id=p.user_id
                             WHERE p.status='pending' ORDER BY p.id DESC LIMIT 6"),
        'scans' => q_all('SELECT l.*, c.first_name, c.last_name, t.type, t.status tag_status FROM scan_logs l
                          JOIN children c ON c.id=l.child_id JOIN tags t ON t.id=l.tag_id ORDER BY l.created_at DESC LIMIT 8'),
    ]);
}

// ---------- Users ----------

function admin_users(): void
{
    require_admin();
    $search = query_str('q');
    $role = query_str('role') === 'admin' ? 'admin' : 'parent';
    $sql = "SELECT u.*, (SELECT COUNT(*) FROM children c WHERE c.parent_id=u.id) kids,
                   (SELECT MAX(ends_at) FROM subscriptions s WHERE s.user_id=u.id AND s.status='active') sub_ends
            FROM users u WHERE u.role=?";
    $params = [$role];
    if ($search !== '') {
        $sql .= ' AND (u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)';
        array_push($params, "%{$search}%", "%{$search}%", "%{$search}%");
    }
    view('admin/users', [
        'title' => t('admin.users'), 'search' => $search, 'role' => $role,
        'users' => q_all($sql . ' ORDER BY u.created_at DESC LIMIT 200', $params),
    ]);
}

function admin_user_create(): void
{
    require_admin();
    $name = input('name');
    $email = strtolower(input('email'));
    $password = (string)($_POST['password'] ?? '');
    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($password) < 10) {
        flash('error', t('admin.admin_invalid'));
        redirect('admin/users?role=admin');
    }
    if (q_one('SELECT 1 FROM users WHERE email=?', [$email])) {
        flash('error', t('register.email_taken'));
        redirect('admin/users?role=admin');
    }
    q("INSERT INTO users (role, name, email, password_hash, lang) VALUES ('admin', ?, ?, ?, ?)",
      [$name, $email, password_hash($password, PASSWORD_DEFAULT), current_lang()]);
    flash('success', t('admin.admin_created'));
    redirect('admin/users?role=admin');
}

function admin_user_show(int $id): void
{
    require_admin();
    $u = q_one('SELECT * FROM users WHERE id=?', [$id]);
    if (!$u) abort(404);
    view('admin/user', [
        'title'    => $u['name'],
        'u'        => $u,
        'sub'      => active_subscription($id),
        'plans'    => plans(false),
        'children' => q_all('SELECT * FROM children WHERE parent_id=? ORDER BY first_name', [$id]),
        'tags'     => q_all('SELECT t.*, c.first_name FROM tags t JOIN children c ON c.id=t.child_id
                             WHERE c.parent_id=? ORDER BY t.id DESC', [$id]),
        'subs'     => q_all('SELECT s.*, p.name_en, p.name_ar FROM subscriptions s JOIN plans p ON p.id=s.plan_id
                             WHERE s.user_id=? ORDER BY s.id DESC', [$id]),
        'payments' => q_all('SELECT * FROM payments WHERE user_id=? ORDER BY id DESC LIMIT 30', [$id]),
        'orders'   => q_all('SELECT * FROM orders WHERE user_id=? ORDER BY id DESC', [$id]),
    ]);
}

function admin_user_toggle(int $id): void
{
    $admin = require_admin();
    if ($id === (int)$admin['id']) {
        flash('error', t('admin.cant_disable_self'));
    } else {
        q('UPDATE users SET is_active = 1 - is_active WHERE id=?', [$id]);
        flash('success', t('saved'));
    }
    redirect("admin/users/{$id}");
}

/** Give a parent a subscription without payment (gift, support case, testing). */
function admin_user_grant(int $id): void
{
    require_admin();
    $planId = input_int('plan_id');
    $u = q_one("SELECT id FROM users WHERE id=? AND role='parent'", [$id]);
    if (!$u || !$planId || !q_one('SELECT 1 FROM plans WHERE id=?', [$planId])) abort(400);
    q('INSERT INTO subscriptions (user_id, plan_id) VALUES (?, ?)', [$id, $planId]);
    subscription_activate((int)db()->lastInsertId());
    notify($id, 'notify.sub_active');
    flash('success', t('admin.granted'));
    redirect("admin/users/{$id}");
}

// ---------- Payments & subscriptions ----------

function admin_payments(): void
{
    require_admin();
    $status = query_str('status');
    $sql = 'SELECT p.*, u.name, u.email FROM payments p JOIN users u ON u.id=p.user_id';
    $params = [];
    if (in_array($status, ['pending', 'paid', 'failed', 'refunded'], true)) {
        $sql .= ' WHERE p.status=?';
        $params[] = $status;
    }
    view('admin/payments', [
        'title' => t('admin.payments'), 'status' => $status,
        'payments' => q_all($sql . ' ORDER BY p.id DESC LIMIT 200', $params),
    ]);
}

function admin_payment_status(int $id, string $status): void
{
    require_admin();
    $pay = q_one('SELECT * FROM payments WHERE id=?', [$id]);
    if (!$pay) abort(404);
    if ($status === 'paid') {
        payment_mark_paid($id, input('provider_ref') ?: null);
    } else {
        payment_mark_unpaid($id, $status);
    }
    flash('success', t('saved'));
    $back = $_POST['back'] ?? '';
    redirect(is_string($back) && preg_match('#^admin[a-z0-9/?=_&-]*$#', $back) ? $back : 'admin/payments');
}

function admin_subscriptions(): void
{
    require_admin();
    view('admin/subscriptions', [
        'title' => t('admin.subscriptions'),
        'subs'  => q_all("SELECT s.*, p.name_en, p.name_ar, p.price, u.name, u.email FROM subscriptions s
                          JOIN plans p ON p.id=s.plan_id JOIN users u ON u.id=s.user_id
                          WHERE s.status<>'pending' OR s.created_at > NOW() - INTERVAL 30 DAY
                          ORDER BY (s.status='active' AND s.ends_at > NOW()) DESC, s.ends_at ASC LIMIT 300"),
    ]);
}

function admin_subscription_cancel(int $id): void
{
    require_admin();
    q("UPDATE subscriptions SET status='cancelled' WHERE id=?", [$id]);
    flash('success', t('saved'));
    redirect('admin/subscriptions');
}

// ---------- Orders ----------

function admin_orders(): void
{
    require_admin();
    $status = query_str('status');
    $sql = 'SELECT o.*, u.name, (SELECT SUM(qty) FROM order_items i WHERE i.order_id=o.id) items
            FROM orders o JOIN users u ON u.id=o.user_id';
    $params = [];
    if (in_array($status, ORDER_STATUSES, true)) {
        $sql .= ' WHERE o.status=?';
        $params[] = $status;
    }
    view('admin/orders', ['title' => t('admin.orders'), 'status' => $status, 'orders' => q_all($sql . ' ORDER BY o.id DESC LIMIT 200', $params)]);
}

function admin_order_show(int $id): void
{
    require_admin();
    $order = q_one('SELECT o.*, u.name, u.email, u.phone FROM orders o JOIN users u ON u.id=o.user_id WHERE o.id=?', [$id]);
    if (!$order) abort(404);
    $items = q_all('SELECT i.*, p.name_en, p.name_ar, p.type, c.first_name, c.last_name,
                    (SELECT COUNT(*) FROM tags t WHERE t.order_item_id=i.id) issued
                    FROM order_items i JOIN products p ON p.id=i.product_id LEFT JOIN children c ON c.id=i.child_id
                    WHERE i.order_id=?', [$id]);
    foreach ($items as &$item) {
        $item['tags'] = q_all('SELECT id, status FROM tags WHERE order_item_id=?', [$item['id']]);
    }
    view('admin/order', [
        'title' => t('orders.number', ['id' => $id]), 'order' => $order, 'items' => $items,
        'payment' => q_one("SELECT * FROM payments WHERE kind='order' AND ref_id=? ORDER BY id DESC LIMIT 1", [$id]),
    ]);
}

function admin_order_status(int $id): void
{
    require_admin();
    $order = q_one('SELECT * FROM orders WHERE id=?', [$id]);
    $status = input('status');
    if (!$order || !in_array($status, ORDER_STATUSES, true)) abort(400);
    q('UPDATE orders SET status=? WHERE id=?', [$status, $id]);
    if ($status !== $order['status']) {
        notify((int)$order['user_id'], 'notify.order_status', ['id' => $id, 'status' => '@order.' . $status]);
    }
    flash('success', t('saved'));
    redirect("admin/orders/{$id}");
}

/** Create the devices for an order line (one per unit), ready to be written and printed. */
function admin_item_issue(int $itemId): void
{
    require_admin();
    $item = q_one('SELECT i.*, p.type FROM order_items i JOIN products p ON p.id=i.product_id WHERE i.id=?', [$itemId]);
    if (!$item) abort(404);
    if (!$item['child_id']) {
        flash('error', t('admin.item_no_child'));
        redirect("admin/orders/{$item['order_id']}");
    }
    $issued = (int)q_one('SELECT COUNT(*) n FROM tags WHERE order_item_id=?', [$itemId])['n'];
    for ($i = $issued; $i < (int)$item['qty']; $i++) {
        tag_create((int)$item['child_id'], $item['type'], $itemId);
    }
    q("UPDATE orders SET status='processing' WHERE id=? AND status='paid'", [$item['order_id']]);
    flash('success', t('admin.issued'));
    redirect("admin/orders/{$item['order_id']}");
}

// ---------- Devices ----------

function admin_tags(): void
{
    require_admin();
    $search = query_str('q');
    $sql = 'SELECT t.*, c.first_name, c.last_name, u.name parent_name, u.id parent_id,
            (SELECT COUNT(*) FROM scan_logs l WHERE l.tag_id=t.id) scans
            FROM tags t JOIN children c ON c.id=t.child_id JOIN users u ON u.id=c.parent_id';
    $params = [];
    if ($search !== '') {
        $sql .= ' WHERE CONCAT(c.first_name, " ", c.last_name) LIKE ? OR u.name LIKE ? OR u.email LIKE ? OR t.id = ?';
        array_push($params, "%{$search}%", "%{$search}%", "%{$search}%", ctype_digit($search) ? (int)$search : 0);
    }
    view('admin/tags', ['title' => t('admin.devices'), 'search' => $search, 'tags' => q_all($sql . ' ORDER BY t.id DESC LIMIT 200', $params)]);
}

function admin_tag_show(int $id): void
{
    require_admin();
    $tag = q_one('SELECT t.*, c.first_name, c.last_name, u.name parent_name, u.id parent_id
                  FROM tags t JOIN children c ON c.id=t.child_id JOIN users u ON u.id=c.parent_id WHERE t.id=?', [$id]);
    if (!$tag) abort(404);
    view('admin/tag', [
        'title' => t('admin.device_n', ['id' => $id]), 'tag' => $tag, 'link' => public_link($tag['token']),
        'profile' => emergency_profile((int)$tag['child_id']),
        'scans' => q_all('SELECT * FROM scan_logs WHERE tag_id=? ORDER BY created_at DESC LIMIT 20', [$id]),
    ]);
}

function admin_tag_status(int $id, string $status): void
{
    require_admin();
    q("UPDATE tags SET status=?, locked_at=IF(?='locked', NOW(), NULL) WHERE id=? AND status<>'replaced'", [$status, $status, $id]);
    flash('success', t('saved'));
    redirect("admin/devices/{$id}");
}

// ---------- Replacement requests ----------

function admin_requests(): void
{
    require_admin();
    $status = query_str('status') ?: 'pending';
    view('admin/requests', [
        'title' => t('admin.requests'), 'status' => $status,
        'requests' => q_all("SELECT r.*, t.type, t.child_id, c.first_name, c.last_name, u.name parent_name,
                             (SELECT status FROM payments p WHERE p.kind='replacement' AND p.ref_id=r.id ORDER BY p.id DESC LIMIT 1) pay_status
                             FROM tag_requests r JOIN tags t ON t.id=r.tag_id JOIN children c ON c.id=t.child_id
                             JOIN users u ON u.id=r.user_id WHERE r.status=? ORDER BY r.id DESC LIMIT 200",
                            [in_array($status, ['pending', 'completed', 'rejected'], true) ? $status : 'pending']),
    ]);
}

function admin_request_handle(int $id, string $action): void
{
    $admin = require_admin();
    $req = q_one("SELECT r.*, t.child_id, t.type, c.first_name FROM tag_requests r JOIN tags t ON t.id=r.tag_id
                  JOIN children c ON c.id=t.child_id WHERE r.id=? AND r.status='pending'", [$id]);
    if (!$req) abort(404);
    if ($action === 'complete') {
        $newTag = tag_create((int)$req['child_id'], $req['type'], null, (int)$req['tag_id']);
        q("UPDATE tags SET status='replaced' WHERE id=?", [$req['tag_id']]);
        q("UPDATE tag_requests SET status='completed', new_tag_id=?, handled_by=?, handled_at=NOW() WHERE id=?",
          [$newTag, $admin['id'], $id]);
        notify((int)$req['user_id'], 'notify.replaced', ['name' => $req['first_name'], 'type' => '@tag.' . $req['type']]);
        flash('success', t('admin.request_done'));
        redirect("admin/devices/{$newTag}");
    }
    q("UPDATE tag_requests SET status='rejected', handled_by=?, handled_at=NOW() WHERE id=?", [$admin['id'], $id]);
    notify((int)$req['user_id'], 'notify.request_rejected', ['name' => $req['first_name']]);
    flash('success', t('saved'));
    redirect('admin/requests');
}

// ---------- Products & plans ----------

function admin_products(): void
{
    require_admin();
    view('admin/products', ['title' => t('admin.products'), 'products' => q_all('SELECT * FROM products ORDER BY sort_order, id')]);
}

function admin_product_new(): void
{
    require_admin();
    view('admin/product_form', ['title' => t('admin.product_new'), 'product' => null]);
}

function product_input(): ?array
{
    $d = [
        'slug' => strtolower(input('slug')), 'type' => input('type'),
        'name_en' => input('name_en'), 'name_ar' => input('name_ar'),
        'description_en' => input('description_en') ?: null, 'description_ar' => input('description_ar') ?: null,
        'price' => filter_var(input('price'), FILTER_VALIDATE_FLOAT),
        'is_active' => isset($_POST['is_active']) ? 1 : 0, 'sort_order' => (int)input('sort_order', '0'),
    ];
    if (!preg_match('/^[a-z0-9-]{2,80}$/', $d['slug']) || !in_array($d['type'], TAG_TYPES, true)
        || $d['name_en'] === '' || $d['name_ar'] === '' || $d['price'] === false || $d['price'] < 0) {
        flash('error', t('admin.product_invalid'));
        return null;
    }
    return $d;
}

function admin_product_create(): void
{
    require_admin();
    $d = product_input();
    if (!$d) redirect('admin/products/new');
    if (q_one('SELECT 1 FROM products WHERE slug=?', [$d['slug']])) {
        flash('error', t('admin.slug_taken'));
        redirect('admin/products/new');
    }
    $d['image'] = save_upload('image') ?? 'assets/products/' . $d['type'] . '.svg';
    q('INSERT INTO products (' . implode(',', array_keys($d)) . ') VALUES (' . rtrim(str_repeat('?,', count($d)), ',') . ')', array_values($d));
    flash('success', t('saved'));
    redirect('admin/products');
}

function admin_product_edit(int $id): void
{
    require_admin();
    $product = q_one('SELECT * FROM products WHERE id=?', [$id]);
    if (!$product) abort(404);
    view('admin/product_form', ['title' => t('admin.product_edit'), 'product' => $product]);
}

function admin_product_update(int $id): void
{
    require_admin();
    $product = q_one('SELECT * FROM products WHERE id=?', [$id]);
    if (!$product) abort(404);
    $d = product_input();
    if (!$d) redirect("admin/products/{$id}");
    if (q_one('SELECT 1 FROM products WHERE slug=? AND id<>?', [$d['slug'], $id])) {
        flash('error', t('admin.slug_taken'));
        redirect("admin/products/{$id}");
    }
    if ($img = save_upload('image')) {
        if (str_starts_with((string)$product['image'], 'uploads/')) @unlink(BASE_PATH . '/public/' . $product['image']);
        $d['image'] = $img;
    }
    $set = implode(',', array_map(fn($k) => "$k=?", array_keys($d)));
    q("UPDATE products SET {$set} WHERE id=?", [...array_values($d), $id]);
    flash('success', t('saved'));
    redirect('admin/products');
}

function admin_plans(): void
{
    require_admin();
    view('admin/plans', ['title' => t('admin.plans'), 'plans' => plans(false)]);
}

function admin_plan_update(int $id): void
{
    require_admin();
    $price = filter_var(input('price'), FILTER_VALIDATE_FLOAT);
    if ($price === false || $price < 0 || input('name_en') === '' || input('name_ar') === '') {
        flash('error', t('error.invalid'));
        redirect('admin/plans');
    }
    q('UPDATE plans SET name_en=?, name_ar=?, price=?, is_active=? WHERE id=?',
      [input('name_en'), input('name_ar'), $price, isset($_POST['is_active']) ? 1 : 0, $id]);
    flash('success', t('saved'));
    redirect('admin/plans');
}
