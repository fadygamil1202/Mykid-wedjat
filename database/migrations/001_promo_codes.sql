-- Promo codes. Run once on an existing database (phpMyAdmin > SQL tab).
-- New installs get this from schema.sql automatically.
SET NAMES utf8mb4;

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

-- A payment remembers its price before the discount and which code was used
ALTER TABLE payments
  ADD COLUMN original_amount DECIMAL(10,2) NULL AFTER amount,
  ADD COLUMN discount DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER original_amount,
  ADD COLUMN promo_code_id INT UNSIGNED NULL AFTER discount,
  ADD COLUMN promo_code VARCHAR(40) NULL AFTER promo_code_id,
  ADD CONSTRAINT fk_payments_promo FOREIGN KEY (promo_code_id) REFERENCES promo_codes(id) ON DELETE SET NULL;

ALTER TABLE orders
  ADD COLUMN discount DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER shipping_fee;
