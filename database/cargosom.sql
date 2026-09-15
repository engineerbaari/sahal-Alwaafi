-- CargoSom complete database: schema, relationships, settings, and demo data.
-- Import this single file into MySQL or phpMyAdmin.

CREATE DATABASE IF NOT EXISTS cargosom CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cargosom;

SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS shipment_awb_members,shipment_awbs,notifications,payments,general_invoices,shipment_invoices,income_entries,expenses,shipments,airlines,company_settings,users,branches;
SET FOREIGN_KEY_CHECKS=1;

CREATE TABLE branches (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    code VARCHAR(20) NOT NULL UNIQUE,
    address VARCHAR(180),
    phone VARCHAR(40),
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    phone VARCHAR(40),
    password VARCHAR(255) NOT NULL,
    role ENUM('super_admin','admin','staff','customer','accountant') NOT NULL DEFAULT 'customer',
    branch_id INT UNSIGNED NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    last_login DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (branch_id) REFERENCES branches(id)
) ENGINE=InnoDB;

CREATE TABLE airlines (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    code VARCHAR(10) NOT NULL UNIQUE,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE shipments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shipment_date DATE NOT NULL,
    awb VARCHAR(50) NOT NULL UNIQUE,
    mark VARCHAR(80),
    pcs INT UNSIGNED NOT NULL DEFAULT 1,
    kg DECIMAL(12,2) NOT NULL,
    rate DECIMAL(12,2) NOT NULL,
    total DECIMAL(12,2) GENERATED ALWAYS AS (kg * rate) STORED,
    paid_dxb DECIMAL(12,2) NOT NULL DEFAULT 0,
    balance_mgq DECIMAL(12,2) GENERATED ALWAYS AS ((kg * rate) - paid_dxb + extra) STORED,
    extra DECIMAL(12,2) NOT NULL DEFAULT 0,
    sender VARCHAR(150) NOT NULL,
    tell VARCHAR(50),
    receiver VARCHAR(150) NOT NULL,
    description VARCHAR(255) NOT NULL,
    payment_receive ENUM('Unpaid','Partially Paid','Paid') NOT NULL DEFAULT 'Unpaid',
    airline_id INT UNSIGNED NOT NULL,
    fly_awb_no VARCHAR(80),
    status ENUM('Pending','In Transit','Arrived','Delivered','Cancelled') NOT NULL DEFAULT 'Pending',
    customer_id INT UNSIGNED NULL,
    branch_id INT UNSIGNED NOT NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (airline_id) REFERENCES airlines(id),
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (branch_id) REFERENCES branches(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_ship_date (shipment_date),
    INDEX idx_ship_airline (airline_id),
    INDEX idx_ship_payment (payment_receive),
    INDEX idx_ship_sender (sender),
    INDEX idx_ship_fly_awb (fly_awb_no)
    ,INDEX idx_ship_branch (branch_id)
) ENGINE=InnoDB;

CREATE TABLE shipment_awbs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    awb_no VARCHAR(80) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL DEFAULT '',
    airline_id INT UNSIGNED NULL,
    notes VARCHAR(255) NOT NULL DEFAULT '',
    branch_id INT UNSIGNED NOT NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (airline_id) REFERENCES airlines(id) ON DELETE SET NULL,
    FOREIGN KEY (branch_id) REFERENCES branches(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_awb_branch (branch_id),
    INDEX idx_awb_created (created_at)
) ENGINE=InnoDB;

CREATE TABLE shipment_awb_members (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    awb_id INT UNSIGNED NOT NULL,
    shipment_id INT UNSIGNED NOT NULL UNIQUE,
    added_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (awb_id) REFERENCES shipment_awbs(id) ON DELETE CASCADE,
    FOREIGN KEY (shipment_id) REFERENCES shipments(id) ON DELETE CASCADE,
    FOREIGN KEY (added_by) REFERENCES users(id),
    INDEX idx_member_awb (awb_id),
    INDEX idx_member_shipment (shipment_id)
) ENGINE=InnoDB;

CREATE TABLE payments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shipment_id INT UNSIGNED NULL,
    general_invoice_id INT UNSIGNED NULL,
    amount DECIMAL(12,2) NOT NULL,
    location ENUM('DXB','MGQ') NOT NULL DEFAULT 'DXB',
    payment_method ENUM('Cash','Bank Transfer','Mobile Money','Card','Other') NOT NULL,
    reference_no VARCHAR(80),
    notes VARCHAR(255),
    received_by INT UNSIGNED NOT NULL,
    paid_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (shipment_id) REFERENCES shipments(id) ON DELETE CASCADE,
    FOREIGN KEY (received_by) REFERENCES users(id),
    INDEX idx_payment_date (paid_at)
) ENGINE=InnoDB;

CREATE TABLE expenses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category ENUM('Transport','Customs','Fuel','Warehouse','Salaries','Maintenance','Other') NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    expense_date DATE NOT NULL,
    vendor VARCHAR(150),
    description VARCHAR(255) NOT NULL,
    shipment_id INT UNSIGNED NULL,
    branch_id INT UNSIGNED NOT NULL,
    recorded_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (shipment_id) REFERENCES shipments(id) ON DELETE SET NULL,
    FOREIGN KEY (branch_id) REFERENCES branches(id),
    FOREIGN KEY (recorded_by) REFERENCES users(id),
    INDEX idx_expense_date (expense_date)
) ENGINE=InnoDB;

CREATE TABLE income_entries (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    income_date DATE NOT NULL,
    category ENUM('Cargo Service','Delivery','Storage','Handling','Commission','Other') NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    shipment_id INT UNSIGNED NULL,
    branch_id INT UNSIGNED NOT NULL,
    payer VARCHAR(150),
    payment_method ENUM('Cash','Bank Transfer','Mobile Money','Card','Other') NOT NULL,
    reference_no VARCHAR(80),
    description VARCHAR(255) NOT NULL,
    recorded_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (shipment_id) REFERENCES shipments(id) ON DELETE SET NULL,
    FOREIGN KEY (branch_id) REFERENCES branches(id),
    FOREIGN KEY (recorded_by) REFERENCES users(id),
    INDEX idx_income_date (income_date)
) ENGINE=InnoDB;

CREATE TABLE shipment_invoices (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_no VARCHAR(30) NOT NULL UNIQUE,
    shipment_id INT UNSIGNED NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL,
    discount_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    tax_rate DECIMAL(6,4) NOT NULL DEFAULT 0,
    tax_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    total DECIMAL(12,2) NOT NULL,
    issue_date DATE NOT NULL,
    due_date DATE NOT NULL,
    status ENUM('Draft','Issued','Partially Paid','Paid','Overdue','Cancelled') NOT NULL DEFAULT 'Issued',
    notes TEXT,
    created_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (shipment_id) REFERENCES shipments(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_invoice_due (due_date),
    INDEX idx_invoice_status (status)
) ENGINE=InnoDB;

CREATE TABLE general_invoices (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_no VARCHAR(30) NOT NULL UNIQUE,
    document_type ENUM('Invoice','Quotation') NOT NULL DEFAULT 'Invoice',
    receiver VARCHAR(150) NOT NULL,
    phone VARCHAR(50),
    description VARCHAR(255) NOT NULL,
    kg DECIMAL(10,2) NOT NULL DEFAULT 0,
    rate DECIMAL(12,2) NOT NULL DEFAULT 0,
    amount DECIMAL(12,2) NOT NULL,
    issue_date DATE NOT NULL,
    due_date DATE NOT NULL,
    status ENUM('Draft','Issued','Paid','Overdue','Cancelled') NOT NULL DEFAULT 'Issued',
    notes TEXT,
    branch_id INT UNSIGNED NOT NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (branch_id) REFERENCES branches(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_general_invoice_branch (branch_id),
    INDEX idx_general_invoice_due (due_date)
) ENGINE=InnoDB;

ALTER TABLE payments
    ADD CONSTRAINT fk_payments_general_invoice
        FOREIGN KEY (general_invoice_id) REFERENCES general_invoices(id) ON DELETE CASCADE,
    ADD INDEX idx_payments_general_invoice (general_invoice_id);

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

CREATE TABLE notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    subject VARCHAR(160) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE company_settings (
    setting_key VARCHAR(80) PRIMARY KEY,
    setting_value TEXT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO branches(name,code,address,phone) VALUES
('Main Office','MAIN','Mogadishu','+252 61 000 0000'),
('Dubai Office','DXB','Dubai','+971 50 000 0000');

-- All demo accounts use the password: password
INSERT INTO users(name,email,phone,password,role,branch_id) VALUES
('System Admin','admin@cargosom.test','+252 61 000 0001','$2y$10$0VR8Fezu/tOhE7kQSphgYe.nNNfLTSEIXYrP4aN/zXjz/tap1dQC6','super_admin',1),
('Operations Staff','staff@cargosom.test','+252 61 000 0002','$2y$10$0VR8Fezu/tOhE7kQSphgYe.nNNfLTSEIXYrP4aN/zXjz/tap1dQC6','staff',1),
('Finance Officer','accountant@cargosom.test','+252 61 000 0003','$2y$10$0VR8Fezu/tOhE7kQSphgYe.nNNfLTSEIXYrP4aN/zXjz/tap1dQC6','accountant',1),
('Amina Hassan','customer@cargosom.test','+252 61 000 0004','$2y$10$0VR8Fezu/tOhE7kQSphgYe.nNNfLTSEIXYrP4aN/zXjz/tap1dQC6','customer',1);

INSERT INTO airlines(name,code) VALUES
('Emirates SkyCargo','EK'),('Qatar Airways Cargo','QR'),('Turkish Cargo','TK'),('Ethiopian Cargo','ET'),('Flydubai Cargo','FZ');

INSERT INTO shipments(shipment_date,awb,mark,pcs,kg,rate,paid_dxb,extra,sender,tell,receiver,description,payment_receive,airline_id,fly_awb_no,status,customer_id,branch_id,created_by)
VALUES(CURDATE(),'176-58392014','AHMED',8,142.50,3.20,250,15,'Ahmed Trading','+971 50 123 4567','Amina Hassan','Electronics','Partially Paid',1,'EK-883410','In Transit',4,1,2);

INSERT INTO payments(shipment_id,amount,location,payment_method,reference_no,received_by,paid_at)
VALUES(1,250,'DXB','Bank Transfer','DXB-25001',3,NOW());

INSERT INTO expenses(category,amount,expense_date,vendor,description,shipment_id,branch_id,recorded_by) VALUES
('Transport',320,CURDATE(),'OceanLink','Sea freight allocation',1,1,3),
('Customs',125,CURDATE(),'Customs Authority','Import processing',1,1,3);

INSERT INTO shipment_invoices(invoice_no,shipment_id,subtotal,total,issue_date,due_date,status,created_by)
VALUES('INV-2026-00001',1,456,456,CURDATE(),DATE_ADD(CURDATE(),INTERVAL 14 DAY),'Partially Paid',3);

INSERT INTO company_settings(setting_key,setting_value) VALUES
('company_name','CargoSom'),('company_tagline','Cargo Agency'),('date_format','d M Y'),('timezone','Africa/Nairobi'),('logo_path','');
