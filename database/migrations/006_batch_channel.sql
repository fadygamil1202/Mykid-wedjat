-- A batch either feeds the website's (online) stock, or is made for stores and events.
-- Run once on an existing database (phpMyAdmin > SQL tab), AFTER 005_device_batches.sql.
ALTER TABLE device_batches ADD COLUMN online TINYINT(1) NOT NULL DEFAULT 1 AFTER qty;
