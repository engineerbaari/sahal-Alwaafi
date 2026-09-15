-- Run once for an existing CargoSom database.
CREATE TABLE branches (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    code VARCHAR(20) NOT NULL UNIQUE,
    address VARCHAR(180),
    phone VARCHAR(40),
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO branches(name,code,address) VALUES ('Main Office','MAIN','Mogadishu');
SET @main_branch = LAST_INSERT_ID();

ALTER TABLE users ADD COLUMN branch_id INT UNSIGNED NULL AFTER role;
UPDATE users SET branch_id=@main_branch WHERE branch_id IS NULL;
ALTER TABLE users MODIFY branch_id INT UNSIGNED NOT NULL;
ALTER TABLE users ADD CONSTRAINT fk_users_branch FOREIGN KEY (branch_id) REFERENCES branches(id);

ALTER TABLE shipments ADD COLUMN branch_id INT UNSIGNED NULL AFTER customer_id;
UPDATE shipments SET branch_id=@main_branch WHERE branch_id IS NULL;
ALTER TABLE shipments MODIFY branch_id INT UNSIGNED NOT NULL;
ALTER TABLE shipments ADD CONSTRAINT fk_shipments_branch FOREIGN KEY (branch_id) REFERENCES branches(id);
ALTER TABLE shipments ADD INDEX idx_ship_branch (branch_id);

ALTER TABLE expenses ADD COLUMN branch_id INT UNSIGNED NULL AFTER shipment_id;
UPDATE expenses SET branch_id=@main_branch WHERE branch_id IS NULL;
ALTER TABLE expenses MODIFY branch_id INT UNSIGNED NOT NULL;
ALTER TABLE expenses ADD CONSTRAINT fk_expenses_branch FOREIGN KEY (branch_id) REFERENCES branches(id);

ALTER TABLE income_entries ADD COLUMN branch_id INT UNSIGNED NULL AFTER shipment_id;
UPDATE income_entries SET branch_id=@main_branch WHERE branch_id IS NULL;
ALTER TABLE income_entries MODIFY branch_id INT UNSIGNED NOT NULL;
ALTER TABLE income_entries ADD CONSTRAINT fk_income_branch FOREIGN KEY (branch_id) REFERENCES branches(id);
