-- Run once on an existing CargoSom database to enable multi-branch operation.
CREATE TABLE branches (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    code VARCHAR(20) NOT NULL UNIQUE,
    phone VARCHAR(40) NULL,
    address VARCHAR(255) NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO branches(name,code,address) VALUES ('Head Office','HQ','Main office');

ALTER TABLE users ADD COLUMN branch_id INT UNSIGNED NULL AFTER role;
UPDATE users SET branch_id=1 WHERE branch_id IS NULL;
ALTER TABLE users MODIFY branch_id INT UNSIGNED NOT NULL;
ALTER TABLE users ADD CONSTRAINT fk_users_branch FOREIGN KEY (branch_id) REFERENCES branches(id);

ALTER TABLE shipments ADD COLUMN branch_id INT UNSIGNED NULL AFTER created_by;
UPDATE shipments SET branch_id=1 WHERE branch_id IS NULL;
ALTER TABLE shipments MODIFY branch_id INT UNSIGNED NOT NULL;
ALTER TABLE shipments ADD CONSTRAINT fk_shipments_branch FOREIGN KEY (branch_id) REFERENCES branches(id);
ALTER TABLE shipments ADD INDEX idx_ship_branch (branch_id);

ALTER TABLE expenses ADD COLUMN branch_id INT UNSIGNED NULL AFTER recorded_by;
UPDATE expenses SET branch_id=1 WHERE branch_id IS NULL;
ALTER TABLE expenses MODIFY branch_id INT UNSIGNED NOT NULL;
ALTER TABLE expenses ADD CONSTRAINT fk_expenses_branch FOREIGN KEY (branch_id) REFERENCES branches(id);

ALTER TABLE income_entries ADD COLUMN branch_id INT UNSIGNED NULL AFTER recorded_by;
UPDATE income_entries SET branch_id=1 WHERE branch_id IS NULL;
ALTER TABLE income_entries MODIFY branch_id INT UNSIGNED NOT NULL;
ALTER TABLE income_entries ADD CONSTRAINT fk_income_branch FOREIGN KEY (branch_id) REFERENCES branches(id);
