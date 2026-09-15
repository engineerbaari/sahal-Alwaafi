-- Run once on existing CargoSom databases.
ALTER TABLE shipment_invoices
  ADD COLUMN discount_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER subtotal;

ALTER TABLE general_invoices
  ADD COLUMN document_type ENUM('Invoice','Quotation') NOT NULL DEFAULT 'Invoice' AFTER invoice_no,
  ADD INDEX idx_general_invoice_document_type (document_type);
