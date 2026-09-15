CREATE TABLE bank_accounts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  bank_name VARCHAR(120) NOT NULL,
  account_number VARCHAR(80) NOT NULL,
  opening_balance DECIMAL(12,2) NOT NULL DEFAULT 0,
  branch_id INT UNSIGNED NOT NULL,
  created_by INT UNSIGNED NOT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (branch_id) REFERENCES branches(id),
  FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;
CREATE TABLE bank_transactions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  bank_account_id INT UNSIGNED NOT NULL,
  transaction_type ENUM('Payment','Income','Expense') NOT NULL,
  source_table VARCHAR(30) NOT NULL,
  source_id INT UNSIGNED NOT NULL,
  amount_in DECIMAL(12,2) NOT NULL DEFAULT 0,
  amount_out DECIMAL(12,2) NOT NULL DEFAULT 0,
  transaction_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (bank_account_id) REFERENCES bank_accounts(id) ON DELETE RESTRICT,
  INDEX idx_bank_transaction_account_date (bank_account_id,transaction_date)
) ENGINE=InnoDB;

ALTER TABLE payments ADD COLUMN bank_account_id INT UNSIGNED NULL AFTER general_invoice_id;
ALTER TABLE payments ADD CONSTRAINT fk_payments_bank_account FOREIGN KEY (bank_account_id) REFERENCES bank_accounts(id) ON DELETE SET NULL;
ALTER TABLE expenses ADD COLUMN bank_account_id INT UNSIGNED NULL AFTER branch_id;
ALTER TABLE expenses ADD CONSTRAINT fk_expenses_bank_account FOREIGN KEY (bank_account_id) REFERENCES bank_accounts(id) ON DELETE SET NULL;
