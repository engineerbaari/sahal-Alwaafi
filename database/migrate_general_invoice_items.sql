-- Run once to enable multiple line items on General Invoices.
CREATE TABLE general_invoice_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    general_invoice_id INT UNSIGNED NOT NULL,
    description VARCHAR(255) NOT NULL,
    kg DECIMAL(10,2) NOT NULL,
    rate DECIMAL(12,2) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (general_invoice_id) REFERENCES general_invoices(id) ON DELETE CASCADE,
    INDEX idx_general_invoice_item (general_invoice_id)
) ENGINE=InnoDB;
