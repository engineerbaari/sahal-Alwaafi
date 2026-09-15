-- Add the receiver's phone number to the shipment register form.
ALTER TABLE shipments ADD COLUMN receiver_phone VARCHAR(50) NULL AFTER receiver;