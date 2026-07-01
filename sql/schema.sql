-- StockFlow schema
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS sale_items;
DROP TABLE IF EXISTS purchase_items;
DROP TABLE IF EXISTS sales;
DROP TABLE IF EXISTS purchases;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS customers;
DROP TABLE IF EXISTS suppliers;
DROP TABLE IF EXISTS taxtypes;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    name VARCHAR(120) NOT NULL,
    role ENUM('admin','staff') NOT NULL DEFAULT 'staff',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE products (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `sku` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `category` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `unit` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pcs',
  `hsn` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `cost_price` decimal(12,2) NOT NULL DEFAULT '0.00',
  `sale_price` decimal(12,2) NOT NULL DEFAULT '0.00',
  `taxtype_id` int unsigned NOT NULL,
  `stock` decimal(12,2) NOT NULL DEFAULT '0.00',
  `reorder_level` decimal(12,2) NOT NULL DEFAULT '0.00',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sku` (`sku`),
  KEY `idx_products_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE customers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(190) NOT NULL,
    email VARCHAR(190) DEFAULT NULL,
    phone VARCHAR(40) DEFAULT NULL,
    address VARCHAR(255) DEFAULT NULL,
    balance DECIMAL(12,2) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_customers_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE suppliers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(190) NOT NULL,
    email VARCHAR(190) DEFAULT NULL,
    phone VARCHAR(40) DEFAULT NULL,
    address VARCHAR(255) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_suppliers_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE sales (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_no VARCHAR(32) NOT NULL UNIQUE,
    customer_id INT UNSIGNED DEFAULT NULL,
    customer_name VARCHAR(190) NOT NULL,
    sale_date DATE NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
    discount DECIMAL(12,2) NOT NULL DEFAULT 0,
    tax DECIMAL(12,2) NOT NULL DEFAULT 0,
    total DECIMAL(12,2) NOT NULL DEFAULT 0,
    paid DECIMAL(12,2) NOT NULL DEFAULT 0,
    balance DECIMAL(12,2) NOT NULL DEFAULT 0,
    status ENUM('unpaid','partial','paid') NOT NULL DEFAULT 'unpaid',
    notes TEXT,
    created_by INT UNSIGNED DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `invoice_no` (`invoice_no`),
    INDEX idx_sales_date (sale_date),
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE sale_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sale_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    sku VARCHAR(64) NOT NULL,
    name VARCHAR(190) NOT NULL,
    qty DECIMAL(12,2) NOT NULL,
    price DECIMAL(12,2) NOT NULL,
    total DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE purchases (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ref_no VARCHAR(32) NOT NULL UNIQUE,
    supplier_id INT UNSIGNED DEFAULT NULL,
    supplier_name VARCHAR(190) NOT NULL,
    purchase_date DATE NOT NULL,
    supplier_inv_no VARCHAR(70) DEFAULT NULL,
    supplier_inv_date DATE DEFAULT NULL,
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
    tax DECIMAL(12,2) NOT NULL DEFAULT 0,
    total DECIMAL(12,2) NOT NULL DEFAULT 0,
    notes TEXT,
    created_by INT UNSIGNED DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_purchases_date (purchase_date),
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE purchase_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    purchase_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    sku VARCHAR(64) NOT NULL,
    name VARCHAR(190) NOT NULL,
    qty DECIMAL(12,2) NOT NULL,
    price DECIMAL(12,2) NOT NULL,
    total DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (purchase_id) REFERENCES purchases(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- Company settings (single-row table, always id = 1)
CREATE TABLE companies (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(190) NOT NULL DEFAULT '',
    company_address VARCHAR(255) DEFAULT '',
    company_phone VARCHAR(40) DEFAULT '',
    company_email VARCHAR(190) DEFAULT '',
    owner_name VARCHAR(120) DEFAULT '',
    company_gst_no VARCHAR(40) DEFAULT '',
    company_gst_validity DATE DEFAULT NULL,
    company_tradelicenseno VARCHAR(60) DEFAULT '',
    tradelicensevalidity DATE DEFAULT NULL,
    company_website VARCHAR(190) DEFAULT '',
    company_pan VARCHAR(40) DEFAULT '',
    company_logo VARCHAR(255) DEFAULT '',
    company_emailhost VARCHAR(190) DEFAULT '',
    company_smtpauth TINYINT(1) NOT NULL DEFAULT 0,
    company_security ENUM('ssl','tls') DEFAULT 'tls',
    company_ssl_port INT UNSIGNED DEFAULT 587,
    company_emailuser VARCHAR(190) DEFAULT '',
    company_emailpassword VARCHAR(255) DEFAULT '',
    company_fromemailid VARCHAR(190) DEFAULT '',
    company_fromemailname VARCHAR(120) DEFAULT '',
    company_replytoemailid VARCHAR(190) DEFAULT '',
    company_replytoemailname VARCHAR(120) DEFAULT '',
    company_noreplyemailid VARCHAR(190) DEFAULT '',
    company_noreplyemailname VARCHAR(120) DEFAULT '',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed an initial company row
INSERT INTO companies (id, company_name) VALUES (1, '');

-- Tax types
DROP TABLE IF EXISTS taxtypes;

CREATE TABLE taxtypes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    undergroup ENUM('Duties & Taxes','Others') NOT NULL DEFAULT 'Duties & Taxes',
    typeofduty ENUM('GST','Others') NOT NULL DEFAULT 'GST',
    taxname VARCHAR(120) NOT NULL,
    percentage DECIMAL(5,2) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO taxtypes (undergroup, typeofduty, taxname, percentage) VALUES
('Duties & Taxes','GST','GST 0%',0),
('Duties & Taxes','GST','GST 3%',3),
('Duties & Taxes','GST','GST 5%',5),
('Duties & Taxes','GST','GST 12%',12),
('Duties & Taxes','GST','GST 18%',18),
('Duties & Taxes','GST','GST 28%',28);

-- Seed admin (password: admin123)
-- Hash generated with: password_hash('admin123', PASSWORD_BCRYPT)
INSERT INTO users (email, password_hash, name, role)
VALUES ('admin@stockflow.test', '$2y$10$wH8nC1m3RnVbX2VpQjV2Z.6Z7QkA5sP3o5K8jHbN3vS5jK2K8L7Sa', 'Administrator', 'admin');
