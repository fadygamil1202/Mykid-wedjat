<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';
foreach (glob(BASE_PATH . '/src/controllers/*.php') as $file) {
    require $file;
}

$method = $_SERVER['REQUEST_METHOD'];
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
// Strip the subfolder the app lives in (and /public or /index.php if typed directly)
if (BASE_URL !== '' && str_starts_with($path, BASE_URL)) {
    $path = substr($path, strlen(BASE_URL));
}
$path = preg_replace('#^/public(?=/|$)#', '', $path);
$path = preg_replace('#^/index\.php(?=/|$)#', '', $path);
$path = rtrim($path, '/') ?: '/';

// [method, pattern, handler]
$routes = [
    // Public site
    ['GET',  '#^/$#',                                 'home'],
    ['GET',  '#^/pricing$#',                          'pricing'],
    ['GET',  '#^/shop$#',                             'shop_index'],
    ['GET',  '#^/shop/([a-z0-9-]+)$#',                'shop_product'],
    ['GET',  '#^/login$#',                            'login_form'],
    ['POST', '#^/login$#',                            'login_submit'],
    ['GET',  '#^/register$#',                         'register_form'],
    ['POST', '#^/register$#',                         'register_submit'],
    ['POST', '#^/logout$#',                           'logout_submit'],
    ['GET',  '#^/lang/(en|ar)$#',                     'switch_lang'],
    ['GET',  '#^/install$#',                          'install_form'],
    ['POST', '#^/install$#',                          'install_submit'],

    // Emergency page opened by the NFC chip / QR code
    ['GET',  '#^/e/([a-f0-9]{32})$#',                 'public_emergency'],
    ['POST', '#^/e/([a-f0-9]{32})/call/(\d+)$#',      'public_call'],

    // Parent area
    ['GET',  '#^/dashboard$#',                        'dashboard'],
    ['GET',  '#^/notifications$#',                    'notifications'],
    ['GET',  '#^/account$#',                          'account_form'],
    ['POST', '#^/account$#',                          'account_submit'],
    ['GET',  '#^/children/new$#',                     'child_new'],
    ['POST', '#^/children$#',                         'child_create'],
    ['GET',  '#^/children/(\d+)$#',                   'child_show'],
    ['GET',  '#^/children/(\d+)/edit$#',              'child_edit'],
    ['POST', '#^/children/(\d+)$#',                   'child_update'],
    ['POST', '#^/children/(\d+)/delete$#',            'child_delete'],
    ['GET',  '#^/children/(\d+)/emergency$#',         'emergency_edit'],
    ['POST', '#^/children/(\d+)/emergency$#',         'emergency_update'],
    ['GET',  '#^/children/(\d+)/card$#',              'child_card'],
    ['GET',  '#^/children/(\d+)/preview$#',           'child_preview'],
    ['POST', '#^/devices/(\d+)/lock$#',               'tag_lock'],
    ['POST', '#^/devices/(\d+)/unlock$#',             'tag_unlock'],
    ['GET',  '#^/devices/(\d+)/replace$#',            'tag_replace_form'],
    ['POST', '#^/devices/(\d+)/replace$#',            'tag_replace_submit'],
    ['GET',  '#^/subscription$#',                     'subscription_page'],
    ['POST', '#^/subscription$#',                     'subscription_start'],
    ['GET',  '#^/payments/(\d+)$#',                   'payment_show'],
    ['GET',  '#^/cart$#',                             'cart_show'],
    ['POST', '#^/cart$#',                             'cart_add'],
    ['POST', '#^/cart/([a-f0-9]{8})/remove$#',        'cart_remove'],
    ['GET',  '#^/checkout$#',                         'checkout_form'],
    ['POST', '#^/checkout$#',                         'checkout_submit'],
    ['GET',  '#^/orders$#',                           'orders_index'],
    ['GET',  '#^/orders/(\d+)$#',                     'order_show'],

    // Administration
    ['GET',  '#^/admin$#',                            'admin_dashboard'],
    ['GET',  '#^/admin/users$#',                      'admin_users'],
    ['POST', '#^/admin/users$#',                      'admin_user_create'],
    ['GET',  '#^/admin/users/(\d+)$#',                'admin_user_show'],
    ['POST', '#^/admin/users/(\d+)/toggle$#',         'admin_user_toggle'],
    ['POST', '#^/admin/users/(\d+)/grant$#',          'admin_user_grant'],
    ['GET',  '#^/admin/payments$#',                   'admin_payments'],
    ['POST', '#^/admin/payments/(\d+)/(paid|failed|refunded)$#', 'admin_payment_status'],
    ['GET',  '#^/admin/subscriptions$#',              'admin_subscriptions'],
    ['POST', '#^/admin/subscriptions/(\d+)/cancel$#', 'admin_subscription_cancel'],
    ['GET',  '#^/admin/orders$#',                     'admin_orders'],
    ['GET',  '#^/admin/orders/(\d+)$#',               'admin_order_show'],
    ['POST', '#^/admin/orders/(\d+)/status$#',        'admin_order_status'],
    ['POST', '#^/admin/order-items/(\d+)/issue$#',    'admin_item_issue'],
    ['GET',  '#^/admin/devices$#',                    'admin_tags'],
    ['GET',  '#^/admin/devices/(\d+)$#',              'admin_tag_show'],
    ['POST', '#^/admin/devices/(\d+)/(active|locked)$#', 'admin_tag_status'],
    ['GET',  '#^/admin/requests$#',                   'admin_requests'],
    ['POST', '#^/admin/requests/(\d+)/(complete|reject)$#', 'admin_request_handle'],
    ['GET',  '#^/admin/products$#',                   'admin_products'],
    ['GET',  '#^/admin/products/new$#',               'admin_product_new'],
    ['POST', '#^/admin/products$#',                   'admin_product_create'],
    ['GET',  '#^/admin/products/(\d+)$#',             'admin_product_edit'],
    ['POST', '#^/admin/products/(\d+)$#',             'admin_product_update'],
    ['GET',  '#^/admin/plans$#',                      'admin_plans'],
    ['POST', '#^/admin/plans/(\d+)$#',                'admin_plan_update'],
];

foreach ($routes as [$m, $pattern, $handler]) {
    if (preg_match($pattern, $path, $matches)) {
        if ($m !== $method) continue;
        array_shift($matches);
        if ($method === 'POST') verify_csrf();
        // Cast each captured value to the handler's declared parameter type (int ids, string tokens)
        $params = (new ReflectionFunction($handler))->getParameters();
        $args = [];
        foreach ($matches as $i => $v) {
            $type = isset($params[$i]) ? (string)$params[$i]->getType() : 'string';
            $args[] = $type === 'int' ? (int)$v : $v;
        }
        $handler(...$args);
        exit;
    }
}

abort(404);
