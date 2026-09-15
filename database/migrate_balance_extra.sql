-- The shipment balance must include Extra so the record-payment "max" and
-- reports match the true invoice total (kg×rate + extra − paid).
ALTER TABLE shipments MODIFY COLUMN balance_mgq DECIMAL(12,2) GENERATED ALWAYS AS (kg*rate+extra-paid_dxb) STORED;