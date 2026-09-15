-- CargoSom: User permissions (fine-grained access control)
-- Run once:  mysql -u root cargosom < database/migrate_permissions.sql
ALTER TABLE users ADD COLUMN permissions TEXT NULL AFTER role;
-- permissions stores a JSON array of permission keys, e.g. ["shipments.view","payments.record"]
-- NULL / empty  => the user falls back to their ROLE_PERMISSIONS defaults (see bootstrap.php)
-- Admin role    => automatically has every permission (can() returns true)