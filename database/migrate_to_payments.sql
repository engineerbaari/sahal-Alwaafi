-- Run once on databases created by earlier CargoSom releases.
-- The rename preserves payment rows, indexes, and foreign-key relationships.
RENAME TABLE shipment_payments TO payments;
