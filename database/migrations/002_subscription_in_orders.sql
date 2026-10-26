-- Lets a shop order also carry a subscription, so both are paid in one payment.
-- Run once on an existing database (phpMyAdmin > SQL tab). New installs get this from schema.sql.
ALTER TABLE orders
  ADD COLUMN plan_id INT UNSIGNED NULL AFTER user_id,
  ADD COLUMN plan_price DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER subtotal,
  ADD COLUMN subscription_id INT UNSIGNED NULL AFTER plan_id,
  ADD CONSTRAINT fk_orders_plan FOREIGN KEY (plan_id) REFERENCES plans(id),
  ADD CONSTRAINT fk_orders_subscription FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE SET NULL;
