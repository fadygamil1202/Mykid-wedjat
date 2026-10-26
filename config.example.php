<?php
// Copy this file to config.php and fill in your values. Never commit config.php.
return [
    // Full address of the site, used in NFC and QR links, e.g. 'https://mykidwedjat.com'.
    // Leave empty to detect it automatically (fine for local testing).
    'app_url'   => '',
    'app_key'   => 'change-me-to-a-long-random-string', // used to hash IPs in scan logs

    // Needed once, to open /install and create the tables and the first admin.
    // Set it to any long random text, then empty it again after installing.
    'install_key' => '',

    'db' => [
        'host'     => 'localhost',
        'port'     => 3306,
        'name'     => 'u123456789_mykid',   // Hostinger: hPanel > Databases > MySQL Databases
        'user'     => 'u123456789_mykid',
        'password' => 'secret',
    ],

    // Shop
    'currency'        => 'EGP',
    'shipping_fee'    => 50,     // flat fee per order
    'replacement_fee' => 0,      // charged when a parent asks for a new device (0 = free)

    // Until a payment gateway is connected, parents see these instructions and an
    // admin marks the payment as paid from Admin > Payments.
    'payment' => [
        'provider' => 'manual',
        'instructions_en' => 'Send the amount by InstaPay or Vodafone Cash to 01000000000 and write your payment number in the note. We activate it within 24 hours.',
        'instructions_ar' => 'حوّل المبلغ عن طريق InstaPay أو فودافون كاش على الرقم 01000000000 واكتب رقم الدفع في الملاحظة. سيتم التفعيل خلال ٢٤ ساعة.',
    ],

    // Support contact shown on the site
    'support_phone' => '+201279792658',
    'support_email' => 'support@example.com',

    // Email notifications through PHP mail() (works on Hostinger). Leave 'from' empty to turn off.
    'mail_from' => '',

    // Show the technical error message on the error page. Turn on only while fixing a problem.
    'debug' => false,

    // Public emergency page: max views per IP per minute
    'public_rate_limit' => 20,
    // Login / sign-up attempts per IP per 15 minutes
    'login_rate_limit' => 10,
];
