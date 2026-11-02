-- Devices made in batches ahead of time, assigned to a child later by serial number.
-- Run once on an existing database (phpMyAdmin > SQL tab), AFTER 004_stock.sql.
-- New installs get this from schema.sql.

CREATE TABLE device_batches (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id INT UNSIGNED NOT NULL,
  qty INT UNSIGNED NOT NULL,
  note VARCHAR(190) NULL,
  created_by INT UNSIGNED NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES products(id),
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- A device can now sit on the shelf without a child ('unassigned'), or be written off ('void').
ALTER TABLE tags
  MODIFY child_id INT UNSIGNED NULL,
  MODIFY status ENUM('unassigned','active','locked','replaced','void') NOT NULL DEFAULT 'unassigned',
  ADD COLUMN product_id INT UNSIGNED NULL AFTER type,
  ADD COLUMN serial CHAR(10) NULL UNIQUE AFTER token,
  ADD COLUMN batch_id INT UNSIGNED NULL AFTER serial,
  ADD COLUMN self_activation TINYINT(1) NOT NULL DEFAULT 1 AFTER batch_id,
  ADD COLUMN assigned_at DATETIME NULL AFTER locked_at,
  ADD CONSTRAINT fk_tags_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
  ADD CONSTRAINT fk_tags_batch FOREIGN KEY (batch_id) REFERENCES device_batches(id) ON DELETE SET NULL;

-- Existing devices: which product they are, and when they were assigned
UPDATE tags t JOIN order_items i ON i.id = t.order_item_id SET t.product_id = i.product_id WHERE t.product_id IS NULL;
UPDATE tags t JOIN (SELECT type, MIN(id) id FROM products GROUP BY type) p ON p.type = t.type
   SET t.product_id = p.id WHERE t.product_id IS NULL;
UPDATE tags SET assigned_at = created_at WHERE child_id IS NOT NULL AND assigned_at IS NULL;
-- (Existing devices get a serial number automatically the first time Admin > Devices is opened.)

ALTER TABLE stock_movements
  MODIFY reason ENUM('order','order_released','replacement','received','correction','produced','activated','returned','void') NOT NULL,
  ADD COLUMN tag_id INT UNSIGNED NULL AFTER tag_request_id,
  ADD CONSTRAINT fk_movements_tag FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE SET NULL;
