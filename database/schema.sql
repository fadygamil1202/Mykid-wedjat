-- MyKid Wedjat — MySQL schema (MySQL 5.7+/8, MariaDB 10.4+)
SET NAMES utf8mb4;

-- role: parent (customer) or admin (MyKid Wedjat staff)
CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  role ENUM('parent','admin') NOT NULL DEFAULT 'parent',
  name VARCHAR(190) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  email_verified_at DATETIME NULL,      -- set when the user clicks the link in the verification email
  phone VARCHAR(40) NULL,
  password_hash VARCHAR(255) NOT NULL,
  lang CHAR(2) NOT NULL DEFAULT 'ar',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  last_login_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- One-time links sent by email (verify address, reset password). Only a SHA-256 hash is stored.
CREATE TABLE email_tokens (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  purpose ENUM('verify','reset') NOT NULL,
  token_hash CHAR(64) NOT NULL UNIQUE,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user_purpose (user_id, purpose),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE children (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  parent_id INT UNSIGNED NOT NULL,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  date_of_birth DATE NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (parent_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- What the emergency page shows. The parent picks which fields are visible.
CREATE TABLE emergency_profiles (
  child_id INT UNSIGNED PRIMARY KEY,
  blood_type VARCHAR(5) NULL,
  allergies TEXT NULL,          -- JSON: [{"name":"Peanuts","severity":"severe"}]
  conditions TEXT NULL,
  medications TEXT NULL,
  care_notes TEXT NULL,
  show_age TINYINT(1) NOT NULL DEFAULT 1,
  show_blood_type TINYINT(1) NOT NULL DEFAULT 1,
  show_allergies TINYINT(1) NOT NULL DEFAULT 1,
  show_conditions TINYINT(1) NOT NULL DEFAULT 1,
  show_medications TINYINT(1) NOT NULL DEFAULT 1,
  show_care_notes TINYINT(1) NOT NULL DEFAULT 1,
  consent_at DATETIME NULL,     -- when the parent consented to the public page
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (child_id) REFERENCES children(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- People the finder can call. Numbers are never printed on the public page.
CREATE TABLE emergency_contacts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  child_id INT UNSIGNED NOT NULL,
  name VARCHAR(100) NOT NULL,
  relation VARCHAR(50) NULL,
  phone VARCHAR(40) NOT NULL,
  sort_order TINYINT UNSIGNED NOT NULL DEFAULT 0,
  FOREIGN KEY (child_id) REFERENCES children(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sellable items: NFC card, bracelet, necklace.
CREATE TABLE products (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(80) NOT NULL UNIQUE,
  type ENUM('card','bracelet','necklace') NOT NULL,
  name_en VARCHAR(150) NOT NULL,
  name_ar VARCHAR(150) NOT NULL,
  description_en TEXT NULL,
  description_ar TEXT NULL,
  price DECIMAL(10,2) NOT NULL,
  image VARCHAR(255) NULL,      -- path under public/, e.g. assets/products/card.svg or uploads/abc.jpg
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE plans (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(30) NOT NULL UNIQUE,  -- monthly, yearly
  name_en VARCHAR(100) NOT NULL,
  name_ar VARCHAR(100) NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  interval_months TINYINT UNSIGNED NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- One subscription covers all children of the parent account.
-- Active = status 'active' AND ends_at in the future.
CREATE TABLE subscriptions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  plan_id INT UNSIGNED NOT NULL,
  status ENUM('pending','active','cancelled') NOT NULL DEFAULT 'pending',
  starts_at DATETIME NULL,
  ends_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user_status (user_id, status, ends_at),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (plan_id) REFERENCES plans(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE orders (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  plan_id INT UNSIGNED NULL,          -- subscription bought together with the products
  subscription_id INT UNSIGNED NULL,
  status ENUM('pending_payment','paid','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending_payment',
  subtotal DECIMAL(10,2) NOT NULL,    -- products only
  plan_price DECIMAL(10,2) NOT NULL DEFAULT 0,
  shipping_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
  discount DECIMAL(10,2) NOT NULL DEFAULT 0,
  total DECIMAL(10,2) NOT NULL,
  ship_name VARCHAR(150) NOT NULL,
  ship_phone VARCHAR(40) NOT NULL,
  ship_governorate VARCHAR(60) NOT NULL,
  ship_city VARCHAR(100) NOT NULL,
  ship_address VARCHAR(500) NOT NULL,
  notes VARCHAR(500) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (plan_id) REFERENCES plans(id),
  FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Each item is linked to the child whose emergency page the device opens.
CREATE TABLE order_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  child_id INT UNSIGNED NULL,
  qty TINYINT UNSIGNED NOT NULL DEFAULT 1,
  unit_price DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id),
  FOREIGN KEY (child_id) REFERENCES children(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- A physical device (card / bracelet / necklace). Its random token is the
-- link written to the NFC chip and the QR code. Only admins ever see it.
CREATE TABLE tags (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  child_id INT UNSIGNED NOT NULL,
  type ENUM('card','bracelet','necklace') NOT NULL,
  token CHAR(32) NOT NULL UNIQUE,
  status ENUM('active','locked','replaced') NOT NULL DEFAULT 'active',
  order_item_id INT UNSIGNED NULL,
  replaces_tag_id INT UNSIGNED NULL,
  locked_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (child_id) REFERENCES children(id) ON DELETE CASCADE,
  FOREIGN KEY (order_item_id) REFERENCES order_items(id) ON DELETE SET NULL,
  FOREIGN KEY (replaces_tag_id) REFERENCES tags(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Parent asks for a new device (lost, stolen, damaged).
CREATE TABLE tag_requests (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tag_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  reason ENUM('lost','stolen','damaged','other') NOT NULL,
  notes VARCHAR(500) NULL,
  status ENUM('pending','completed','rejected') NOT NULL DEFAULT 'pending',
  new_tag_id INT UNSIGNED NULL,
  handled_by INT UNSIGNED NULL,
  handled_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (new_tag_id) REFERENCES tags(id) ON DELETE SET NULL,
  FOREIGN KEY (handled_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Discount codes created by admins (see Admin > Promo codes).
CREATE TABLE promo_codes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(40) NOT NULL UNIQUE,                 -- stored uppercase, e.g. WELCOME20
  description VARCHAR(190) NULL,                    -- admin note, never shown to parents
  discount_type ENUM('percent','fixed') NOT NULL,
  discount_value DECIMAL(10,2) NOT NULL,            -- 20 = 20% or 20 EGP
  applies_to ENUM('all','subscription','order') NOT NULL DEFAULT 'all',
  min_amount DECIMAL(10,2) NULL,                    -- only for charges of at least this much
  max_uses INT UNSIGNED NULL,                       -- total uses for everyone (NULL = unlimited)
  max_uses_per_user INT UNSIGNED NULL DEFAULT 1,    -- uses per parent (NULL = unlimited)
  starts_at DATETIME NULL,
  ends_at DATETIME NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Every charge goes through here. kind + ref_id point at the subscription,
-- order or tag request being paid. 'manual' until a gateway is connected.
CREATE TABLE payments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  kind ENUM('subscription','order','replacement') NOT NULL,
  ref_id INT UNSIGNED NOT NULL,
  amount DECIMAL(10,2) NOT NULL,          -- what the parent pays, after any discount
  original_amount DECIMAL(10,2) NULL,     -- price before the promo code
  discount DECIMAL(10,2) NOT NULL DEFAULT 0,
  promo_code_id INT UNSIGNED NULL,
  promo_code VARCHAR(40) NULL,
  currency CHAR(3) NOT NULL DEFAULT 'EGP',
  provider VARCHAR(30) NOT NULL DEFAULT 'manual',
  provider_ref VARCHAR(190) NULL,
  status ENUM('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
  paid_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_ref (kind, ref_id),
  INDEX idx_status (status, created_at),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (promo_code_id) REFERENCES promo_codes(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE scan_logs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tag_id INT UNSIGNED NOT NULL,
  child_id INT UNSIGNED NOT NULL,
  action ENUM('view','call') NOT NULL DEFAULT 'view',
  ip_hash CHAR(64) NOT NULL,
  user_agent VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_child_time (child_id, created_at),
  FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE,
  FOREIGN KEY (child_id) REFERENCES children(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notifications (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  message VARCHAR(500) NOT NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE rate_limits (
  bucket VARCHAR(100) NOT NULL,
  ip_hash CHAR(64) NOT NULL,
  window_start DATETIME NOT NULL,
  hits INT UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (bucket, ip_hash, window_start)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Starting catalogue and plans (edit them later in the admin pages)
INSERT INTO plans (code, name_en, name_ar, price, interval_months) VALUES
  ('monthly', 'Monthly', 'شهري', 20.00, 1),
  ('yearly',  'Yearly',  'سنوي', 200.00, 12);

INSERT INTO products (slug, type, name_en, name_ar, description_en, description_ar, price, image, sort_order) VALUES
  ('nfc-card', 'card', 'Wedjat NFC Card', 'كارت Wedjat الذكي',
   'Wallet-size card with an NFC chip and QR code. Slips into a school bag or pocket. Tap with any phone to open your child\'s emergency page.',
   'كارت بحجم المحفظة بشريحة NFC وكود QR. يوضع في الشنطة أو الجيب. أي موبايل يلمسه يفتح صفحة الطوارئ الخاصة بطفلك.',
   150.00, 'assets/products/card.svg', 1),
  ('nfc-bracelet', 'bracelet', 'Wedjat Bracelet', 'سوار Wedjat',
   'Soft, waterproof silicone bracelet with a hidden NFC chip. Made for active kids, from age 3.',
   'سوار سيليكون ناعم ومقاوم للماء بشريحة NFC مخفية. مناسب للأطفال النشيطين من عمر ٣ سنوات.',
   250.00, 'assets/products/bracelet.svg', 2),
  ('nfc-necklace', 'necklace', 'Wedjat Necklace', 'قلادة Wedjat',
   'Light pendant on a breakaway safety cord, with an NFC chip and an engraved QR code on the back.',
   'قلادة خفيفة بحبل أمان ينفصل عند الشد، بها شريحة NFC وكود QR محفور على الظهر.',
   300.00, 'assets/products/necklace.svg', 3);
