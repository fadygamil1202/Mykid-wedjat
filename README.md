# MyKid Wedjat

A plain-PHP + MySQL website where parents create emergency profiles for their children and buy NFC devices (card, bracelet, necklace). When someone taps a device or scans its QR code, the child's emergency page opens: call buttons for the family, blood type, allergies, conditions, medications and care notes. Only the fields the parent chose are shown.

Arabic and English throughout (right-to-left in Arabic). The public page follows the phone's language. It is built mobile-first for Android and iPhone, with a bottom tab bar, safe-area support and an "Add to Home Screen" icon.

## What parents can do

- **Sign up and log in**, then add their children.
- **Fill in emergency info**: up to 3 contacts, blood type, allergies, conditions, medications and care notes, and choose what is visible.
- **See the card design** (front and back). The **QR code is never shown to parents**: the card preview shows a locked placeholder, and the device link is never sent to the parent's browser.
- **Preview the emergency page** exactly as a finder sees it.
- **Lock a device** at once (for example a lost bracelet), unlock it, or **request a new one**. Requesting a new one locks the old device for good. When the admin issues the replacement, the old link is dead.
- **Subscribe** monthly (20 EGP) or yearly (200 EGP). One subscription covers all their children.
- **Shop** for the NFC card, bracelet and necklace. Each item is linked to a child. Orders go through a cart, checkout with delivery address, and payment.
- Get **notifications** on every scan or call (in the app, and by email if `mail_from` is set).

Without an active subscription (or before the parent ticks the consent box), the emergency page still shows the first name and the call buttons, never a blank page. Medical details appear only with an active subscription and consent.

## What admins can do (`/admin`)

- **Dashboard**: parents, children, active subscribers, active devices, revenue this month and all time, a 6-month revenue chart, scans, and a to-do row (payments to confirm, orders to prepare, replacement requests, subscriptions ending this week).
- **Payments**: mark paid, failed or refunded. Marking paid activates the subscription or order.
- **Orders**: change status (the parent is notified) and **issue devices**. Issuing creates the device and its secret link.
- **Devices**: for each device, the NFC link to write to the chip, the **real QR code**, and a printable card front and back. Lock or unlock it.
- **Replacements**: issue a new device (the old one is retired) or reject the request.
- **Users**: search parents, see their children, devices, payments and orders, disable an account, give a free subscription, add other admins.
- **Products and plans**: edit names, descriptions, prices, photos and visibility.

## Payments (gateway later)

Every charge is a row in `payments`. Until a gateway is connected, the parent sees the transfer instructions from `config.php` (InstaPay or Vodafone Cash) and an admin confirms the payment. To connect Paymob, Fawry, Kashier or another gateway, see the comment at the top of `src/billing.php`:

1. return the gateway's checkout URL from `payment_checkout_url()`;
2. add a callback route that verifies the gateway's signature, then calls `payment_mark_paid($paymentId, $reference)`.

That one function activates subscriptions and orders, so nothing else has to change.

## Install on Hostinger

1. **hPanel → Websites → your site → Advanced → PHP Configuration**: choose **PHP 8.1 or newer** (8.2 or 8.3 recommended).
2. **hPanel → Databases → MySQL Databases**: create a database and user. Note the names: Hostinger adds a prefix, such as `u123456789_mykid`.
3. **hPanel → Files → File Manager**: open `public_html` and upload **all** the project files and folders as they are (`public/`, `src/`, `views/`, `lang/`, `database/`, `.htaccess`, `index.php` and the rest). Uploading a zip and using *Extract* is easiest. Keep the folder structure. The root `.htaccess` sends every request into `public/`, so `config.php`, `src/` and the rest can never be opened from the web.
4. In File Manager, copy `config.example.php` to **`config.php`** and edit it:
   - the `db` block, with the database, user and password from step 2 (host `localhost`);
   - `app_url`: your domain with `https://`, for example `https://mykidwedjat.com`. NFC links are built from it, so set it **before** issuing any device;
   - `app_key`: any long random text;
   - `install_key`: any long random text (needed once);
   - `payment` instructions, `support_phone`, `shipping_fee`, `replacement_fee`;
   - `mail_from`, such as `no-reply@yourdomain.com` (create that mailbox in hPanel → Emails), to get email notifications.
5. Open **`https://yourdomain.com/install`**, enter the install key, and create your admin account. The tables, the 3 products and the 2 plans are created automatically.
6. Set `install_key` back to `''` in `config.php`. The installer also stops working once an admin exists.
7. **hPanel → Security → SSL**: make sure SSL is active, and turn on **Force HTTPS**.

Alternatively, import `database/schema.sql` in phpMyAdmin, then open `/install` to create the admin.

### Local testing

```bash
php -S localhost:8000 -t public public/router.php
```

## Producing a device

1. Parent orders → admin confirms payment → **Orders → Issue devices**.
2. Open each device and write the link to the NFC chip as a *URL record* (NTAG213/215/216) with an app like **NFC Tools**. Lock the chip in the app so it can't be rewritten.
3. Print the card, or engrave or print the QR on the bracelet or necklace tag.
4. Ship it and set the order to *Shipped*, then *Delivered*.

## Brand

Colours and fonts are defined once, at the top of `public/assets/app.css`: lapis blue `#1B3F8F`, Wedjat gold `#F2B544`, Nile turquoise `#2EC4B6`, emergency coral `#E5484D`, on warm sand `#FBF7EF`. Headings use **Baloo Bhaijaan 2** and body text uses **Cairo**. Both support Arabic and Latin. The logo is `public/assets/logo.svg`. Replace it, plus `public/favicon.svg` and `public/assets/apple-touch-icon.png` (180×180), with your final logo files.

## Security built in

- Passwords hashed with `password_hash`. Sessions are HTTP-only and SameSite, and regenerated on login.
- CSRF token on every form, prepared statements everywhere, all output escaped.
- Device links use 128-bit random tokens (not IDs), are visible to admins only, can be locked or replaced, and are `noindex`.
- Phone numbers are never printed on the public page. "Call" goes through the server, which logs it and notifies the parent.
- Rate limits: public page 20 views/min per IP, login and sign-up 10 attempts per 15 min.
- Scan logs store IPs only as a keyed hash.
- Content-Security-Policy and other security headers on every response. Uploaded images are type-checked and renamed, and nothing but images can be served from `public/uploads/`.

## Before going live

- **Legal review** against Egypt's Personal Data Protection Law (151/2020). Children's health data is sensitive, and the app records the parent's consent with a date.
- **Terms and privacy pages** (the sign-up checkbox refers to them).
- **Database backups** (hPanel → Files → Backups).
- **Full number masking** (optional): today the finder's phone dialer sees the number once the call starts. A telephony provider can hide it. See `public_call()` in `src/controllers/public.php`.

## Project layout

```
public/            web root: index.php router, assets, uploads, .htaccess
src/               bootstrap, database, auth, access rules, billing, helpers
src/controllers/   auth & account, children & devices, shop & billing, public page, admin
views/             page templates (parent, children, shop, billing, orders, public, admin)
lang/              en.php and ar.php
database/          schema.sql (tables + starting products and plans)
```

## Promo codes

Admins manage them at **Admin → Promo codes** (in the ☰ menu): percentage or fixed amount, for everything / subscriptions / shop orders, optional minimum amount, total and per-parent use limits, start and end dates, pause and resume. Parents type a code at checkout or on the payment page. A 100% code marks the payment as paid straight away. A use counts while the payment is waiting or paid; declining the payment frees it again. Used codes can't be deleted (they are paused instead), so payment history stays readable.

**Existing database:** run `database/migrations/001_promo_codes.sql` once in phpMyAdmin before uploading the new files.

## Subscription in the cart

Parents can add a plan to the cart (from the plans page, or from the "Add a subscription" box in the cart and at checkout). One plan at most.

- **Products and plan together:** one order and one payment. When the payment is confirmed the order is paid and the subscription starts. Declining or refunding cancels both.
- **Plan on its own:** "Pay now" in the cart goes straight to the payment, with no delivery address.
- **Promo codes:** a combined payment counts as a shop order, so codes for "Everything" or "Shop orders" work on it; codes for "Subscriptions" only work when the plan is paid on its own.

**Existing database:** run `database/migrations/002_subscription_in_orders.sql` once in phpMyAdmin (after `001_promo_codes.sql`) before uploading the new files.
