-- Account numbers and customer transaction ledger.
ALTER TABLE users ADD COLUMN account_no VARCHAR(30) NULL UNIQUE AFTER email;
UPDATE users SET account_no=CONCAT('CS-', LPAD(id,6,'0')) WHERE account_no IS NULL OR account_no='';

CREATE TABLE account_transactions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  transaction_type ENUM('Invoice','Payment','Adjustment') NOT NULL,
  reference_no VARCHAR(80) NOT NULL,
  description VARCHAR(255) NOT NULL,
  debit DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  credit DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  transaction_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_account_transaction_user_date (user_id, transaction_date)
) ENGINE=InnoDB;
