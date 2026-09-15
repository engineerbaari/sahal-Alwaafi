-- Run once if general_invoices already exists in your database.
ALTER TABLE general_invoices
    ADD COLUMN kg DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER description,
    ADD COLUMN rate DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER kg;

ALTER TABLE payments
    MODIFY shipment_id INT UNSIGNED NULL,
    ADD COLUMN general_invoice_id INT UNSIGNED NULL AFTER shipment_id,
    ADD CONSTRAINT fk_payments_general_invoice
        FOREIGN KEY (general_invoice_id) REFERENCES general_invoices(id) ON DELETE CASCADE,
    ADD INDEX idx_payments_general_invoice (general_invoice_id);
