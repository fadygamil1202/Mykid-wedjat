<?php
declare(strict_types=1);

// ---------- Products ----------

function shop_index(): void
{
    view('shop/index', [
        'title'    => t('nav.shop'),
        'products' => q_all('SELECT * FROM products WHERE is_active=1 ORDER BY sort_order, id'),
    ]);
}

function shop_product(string $slug): void
{
    $product = q_one('SELECT * FROM products WHERE slug=? AND is_active=1', [$slug]);
    if (!$product) abort(404);
    $user = current_user();
    $children = ($user && $user['role'] === 'parent')
        ? q_all('SELECT id, first_name FROM children WHERE parent_id=? ORDER BY first_name', [$user['id']]) : [];
    view('shop/product', ['title' => loc($product, 'name'), 'product' => $product, 'user' => $user, 'children' => $children]);
}

// ---------- Cart ----------

function cart_show(): void
{
    $user = require_parent();
    $lines = cart_lines($user);
    $plan = cart_plan();
    view('shop/cart', [
        'title' => t('cart.title'), 'lines' => $lines, 'plan' => $plan, 'plans' => plans(),
        'subtotal' => array_sum(array_column($lines, 'total')), 'planPrice' => $plan ? (float)$plan['price'] : 0,
        'shipping' => $lines ? (float)config('shipping_fee', 0) : 0,
        'sub' => active_subscription((int)$user['id']),
    ]);
}

/** Put a subscription plan in the cart (replaces any plan already there). */
function cart_plan_add(): void
{
    require_parent();
    $planId = input_int('plan_id');
    if (!$planId || !q_one('SELECT 1 FROM plans WHERE id=? AND is_active=1', [$planId])) abort(400);
    $_SESSION['cart_plan'] = $planId;
    flash('success', t('cart.plan_added'));
    $back = input('back');
    redirect(in_array($back, ['checkout', 'cart'], true) ? $back : 'cart');
}

function cart_plan_remove(): void
{
    require_parent();
    unset($_SESSION['cart_plan']);
    $back = input('back');
    redirect(in_array($back, ['checkout', 'cart'], true) ? $back : 'cart');
}

function cart_add(): void
{
    $user = require_parent();
    $productId = input_int('product_id');
    $childId = input_int('child_id');
    $qty = max(1, min(5, input_int('qty') ?? 1));
    $product = $productId ? q_one('SELECT * FROM products WHERE id=? AND is_active=1', [$productId]) : null;
    if (!$product) abort(404);
    if (!$childId || !q_one('SELECT 1 FROM children WHERE id=? AND parent_id=?', [$childId, $user['id']])) {
        flash('error', t('cart.pick_child'));
        redirect('shop/' . $product['slug']);
    }
    $key = substr(md5($productId . ':' . $childId), 0, 8);
    $current = $_SESSION['cart'][$key]['qty'] ?? 0;
    $_SESSION['cart'][$key] = ['product_id' => $productId, 'child_id' => $childId, 'qty' => min(5, $current + $qty)];
    flash('success', t('cart.added'));
    redirect('cart');
}

function cart_remove(string $key): void
{
    require_parent();
    unset($_SESSION['cart'][$key]);
    redirect('cart');
}

// ---------- Checkout ----------

function checkout_form(): void
{
    $user = require_parent();
    $lines = cart_lines($user);
    if (!$lines) redirect('cart'); // a subscription on its own is paid from the cart, no address needed
    $plan = cart_plan();
    $last = q_one('SELECT * FROM orders WHERE user_id=? ORDER BY id DESC LIMIT 1', [$user['id']]);
    view('shop/checkout', [
        'title' => t('checkout.title'), 'user' => $user, 'lines' => $lines,
        'subtotal' => array_sum(array_column($lines, 'total')), 'plan' => $plan, 'plans' => plans(),
        'planPrice' => $plan ? (float)$plan['price'] : 0,
        'shipping' => (float)config('shipping_fee', 0), 'last' => $last,
        'sub' => active_subscription((int)$user['id']),
    ]);
}

function checkout_submit(): void
{
    $user = require_parent();
    $lines = cart_lines($user);
    if (!$lines) redirect('cart');

    $ship = [
        'name' => input('ship_name'), 'phone' => input('ship_phone'), 'governorate' => input('ship_governorate'),
        'city' => input('ship_city'), 'address' => input('ship_address'),
    ];
    if ($ship['name'] === '' || $ship['city'] === '' || $ship['address'] === ''
        || !valid_phone($ship['phone']) || !in_array($ship['governorate'], GOVERNORATES, true)) {
        flash('error', t('checkout.invalid'));
        redirect('checkout');
    }
    $subtotal = array_sum(array_column($lines, 'total'));
    $shipping = (float)config('shipping_fee', 0);
    $plan = cart_plan();
    $planPrice = $plan ? (float)$plan['price'] : 0;

    $pdo = db();
    $pdo->beginTransaction();
    // The subscription waits as 'pending' and starts when the order's payment is confirmed
    $subId = null;
    if ($plan) {
        q('INSERT INTO subscriptions (user_id, plan_id) VALUES (?, ?)', [$user['id'], $plan['id']]);
        $subId = (int)$pdo->lastInsertId();
    }
    q('INSERT INTO orders (user_id, plan_id, subscription_id, subtotal, plan_price, shipping_fee, total, ship_name, ship_phone, ship_governorate, ship_city, ship_address, notes)
       VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)', [
        $user['id'], $plan['id'] ?? null, $subId, $subtotal, $planPrice, $shipping, $subtotal + $planPrice + $shipping,
        mb_substr($ship['name'], 0, 150), $ship['phone'], $ship['governorate'],
        mb_substr($ship['city'], 0, 100), mb_substr($ship['address'], 0, 500), mb_substr(input('notes'), 0, 500) ?: null,
    ]);
    $orderId = (int)$pdo->lastInsertId();
    foreach ($lines as $line) {
        q('INSERT INTO order_items (order_id, product_id, child_id, qty, unit_price) VALUES (?,?,?,?,?)',
          [$orderId, $line['product']['id'], $line['child']['id'], $line['qty'], $line['product']['price']]);
    }
    $paymentId = payment_create((int)$user['id'], 'order', $orderId, $subtotal + $planPrice + $shipping);
    $pdo->commit();

    unset($_SESSION['cart'], $_SESSION['cart_plan']);
    notify_admins('notify.admin_order', ['id' => $orderId, 'name' => $user['name']]);
    flash('success', t('checkout.placed', ['id' => $orderId]));
    // A refused code doesn't stop the order: the parent can fix it on the payment page
    if (($code = input('promo_code')) !== '') {
        $error = payment_apply_promo($paymentId, $code);
        flash($error ? 'error' : 'success', $error ? t($error) : t('promo.applied'));
    }
    redirect("payments/{$paymentId}");
}

function orders_index(): void
{
    $user = require_parent();
    view('orders/index', [
        'title'  => t('nav.orders'),
        'orders' => q_all('SELECT o.*, (SELECT SUM(qty) FROM order_items i WHERE i.order_id=o.id) items
                           FROM orders o WHERE o.user_id=? ORDER BY o.id DESC', [$user['id']]),
    ]);
}

function order_show(int $id): void
{
    $user = require_parent();
    $order = own_order($user, $id);
    $order += q_one('SELECT name_en plan_name_en, name_ar plan_name_ar FROM plans WHERE id=?', [$order['plan_id'] ?? 0])
        ?? ['plan_name_en' => null, 'plan_name_ar' => null];
    view('orders/show', [
        'title'   => t('orders.number', ['id' => $id]),
        'order'   => $order,
        'items'   => q_all('SELECT i.*, p.name_en, p.name_ar, p.image, c.first_name FROM order_items i
                            JOIN products p ON p.id=i.product_id LEFT JOIN children c ON c.id=i.child_id
                            WHERE i.order_id=?', [$id]),
        'payment' => q_one("SELECT * FROM payments WHERE kind='order' AND ref_id=? ORDER BY id DESC LIMIT 1", [$id]),
    ]);
}

// ---------- Subscription ----------

function subscription_page(): void
{
    $user = require_parent();
    view('billing/subscription', [
        'title'   => t('nav.subscription'),
        'plans'   => plans(),
        'sub'     => active_subscription((int)$user['id']),
        'pending' => q_one("SELECT p.* FROM payments p WHERE p.user_id=? AND p.kind='subscription' AND p.status='pending'
                            ORDER BY p.id DESC LIMIT 1", [$user['id']]),
        'history' => q_all('SELECT s.*, p.name_en, p.name_ar, p.price FROM subscriptions s JOIN plans p ON p.id=s.plan_id
                            WHERE s.user_id=? ORDER BY s.id DESC LIMIT 12', [$user['id']]),
    ]);
}

function subscription_start(): void
{
    $user = require_parent();
    $planId = input_int('plan_id');
    $plan = $planId ? q_one('SELECT * FROM plans WHERE id=? AND is_active=1', [$planId]) : null;
    if (!$plan) abort(400);

    // One open payment at a time: reuse it if the parent picks the same plan again
    $pending = q_one("SELECT pay.*, s.plan_id FROM payments pay JOIN subscriptions s ON s.id=pay.ref_id
                      WHERE pay.user_id=? AND pay.kind='subscription' AND pay.status='pending'", [$user['id']]);
    if ($pending && (int)$pending['plan_id'] === (int)$plan['id']) redirect("payments/{$pending['id']}");
    if ($pending) {
        q("UPDATE payments SET status='failed' WHERE id=?", [$pending['id']]);
        q("UPDATE subscriptions SET status='cancelled' WHERE id=? AND status='pending'", [$pending['ref_id']]);
    }

    q('INSERT INTO subscriptions (user_id, plan_id) VALUES (?, ?)', [$user['id'], $plan['id']]);
    $paymentId = payment_create((int)$user['id'], 'subscription', (int)db()->lastInsertId(), (float)$plan['price']);
    unset($_SESSION['cart_plan']);
    redirect("payments/{$paymentId}");
}

function payment_show(int $id): void
{
    $user = require_parent();
    $payment = q_one('SELECT * FROM payments WHERE id=? AND user_id=?', [$id, $user['id']]);
    if (!$payment) abort(404);
    if ($payment['status'] === 'pending' && ($url = payment_checkout_url($payment))) {
        header('Location: ' . $url);
        exit;
    }
    $what = match ($payment['kind']) {
        'subscription' => (function () use ($payment) {
            $s = q_one('SELECT p.* FROM subscriptions s JOIN plans p ON p.id=s.plan_id WHERE s.id=?', [$payment['ref_id']]);
            return t('pay.for_plan', ['plan' => $s ? loc($s, 'name') : '']);
        })(),
        'order' => t('orders.number', ['id' => $payment['ref_id']]),
        default => t('pay.for_replacement'),
    };
    view('billing/payment', ['title' => t('pay.title'), 'payment' => $payment, 'what' => $what]);
}

function payment_promo_apply(int $id): void
{
    $user = require_parent();
    if (!q_one('SELECT 1 FROM payments WHERE id=? AND user_id=?', [$id, $user['id']])) abort(404);
    // Codes can be guessed, so limit attempts like logins
    if (!rate_limit('promo', 15, 900)) {
        flash('error', t('login.too_many'));
        redirect("payments/{$id}");
    }
    $error = input('promo_code') === '' ? 'promo.invalid' : payment_apply_promo($id, input('promo_code'));
    flash($error ? 'error' : 'success', $error ? t($error) : t('promo.applied'));
    redirect("payments/{$id}");
}

function payment_promo_remove(int $id): void
{
    $user = require_parent();
    if (!q_one('SELECT 1 FROM payments WHERE id=? AND user_id=?', [$id, $user['id']])) abort(404);
    payment_remove_promo($id);
    flash('success', t('promo.removed'));
    redirect("payments/{$id}");
}
