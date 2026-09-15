-- Track when a payment-plan instalment was actually collected.
ALTER TABLE payment_plans ADD COLUMN paid_at DATETIME NULL AFTER status;