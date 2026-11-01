-- Stock management. Run once on an existing database (phpMyAdmin > SQL tab).
-- New installs get this from schema.sql.

-- NULL = stock not tracked (unlimited). A number = units available to sell.
ALTER TABLE products ADD COLUMN stock INT NULL AFTER price;

-- An order holds (reserves) its units from checkout until it is paid or released.
-- reserved_until: unpaid orders are cancelled after this time and their units go back.
ALTER TABLE orders
  ADD COLUMN stock_reserved TINYINT(1) NOT NULL DEFAULT 0 AFTER status,
  ADD COLUMN reserved_until DATETIME NULL AFTER stock_reserved,
  ADD INDEX idx_reservation (status, stock_reserved, reserved_until);

-- Every change to a product's stock, with the reason (audit trail).
CREATE TABLE stock_movements (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id INT UNSIGNED NOT NULL,
  change_qty INT NOT NULL,                 -- negative = out, positive = in
  stock_after INT NOT NULL,
  reason ENUM('order','order_released','replacement','received','correction') NOT NULL,
  order_id INT UNSIGNED NULL,
  tag_request_id INT UNSIGNED NULL,
  user_id INT UNSIGNED NULL,               -- admin who made a manual change
  note VARCHAR(190) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_product_time (product_id, created_at),
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
  FOREIGN KEY (tag_request_id) REFERENCES tag_requests(id) ON DELETE SET NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
