-- Email verification and password reset by email.
-- Run once on an existing database (phpMyAdmin > SQL tab). New installs get this from schema.sql.
ALTER TABLE users ADD COLUMN email_verified_at DATETIME NULL AFTER email;

-- Everyone who registered before this change is treated as verified, so nobody is locked out
UPDATE users SET email_verified_at = created_at WHERE email_verified_at IS NULL;

-- One-time links sent by email. Only a SHA-256 hash of the token is stored.
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
