ALTER TABLE expenses ADD COLUMN IF NOT EXISTS supplier VARCHAR(150) NULL AFTER category;
ALTER TABLE expenses ADD COLUMN IF NOT EXISTS receipt_path VARCHAR(255) NULL AFTER description;
ALTER TABLE expenses ADD COLUMN IF NOT EXISTS created_by INT NULL AFTER receipt_path;

ALTER TABLE stock_history ADD COLUMN IF NOT EXISTS balance_after DECIMAL(12,2) NULL AFTER quantity;
ALTER TABLE stock_history ADD COLUMN IF NOT EXISTS source_type VARCHAR(40) NULL AFTER note;
ALTER TABLE stock_history ADD COLUMN IF NOT EXISTS source_id INT NULL AFTER source_type;
ALTER TABLE stock_history ADD COLUMN IF NOT EXISTS user_id INT NULL AFTER source_id;

CREATE TABLE IF NOT EXISTS quotations (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    quote_no VARCHAR(50) NOT NULL UNIQUE,
    customer_id INT NOT NULL,
    quote_date DATE NOT NULL,
    valid_until DATE NULL,
    status ENUM('Draft','Sent','Accepted','Rejected','Converted') NOT NULL DEFAULT 'Draft',
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
    discount DECIMAL(12,2) NOT NULL DEFAULT 0,
    total DECIMAL(12,2) NOT NULL DEFAULT 0,
    notes TEXT NULL,
    order_id INT NULL,
    invoice_id INT NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_quotations_customer (customer_id),
    INDEX idx_quotations_status (status)
);

CREATE TABLE IF NOT EXISTS quotation_items (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    quotation_id INT NOT NULL,
    factory_product_id INT NULL,
    product_name VARCHAR(150) NOT NULL,
    description TEXT NULL,
    width_mm DECIMAL(10,2) NOT NULL DEFAULT 0,
    height_mm DECIMAL(10,2) NOT NULL DEFAULT 0,
    quantity INT NOT NULL DEFAULT 1,
    sqft DECIMAL(12,2) NOT NULL DEFAULT 0,
    unit_price DECIMAL(12,2) NOT NULL DEFAULT 0,
    total DECIMAL(12,2) NOT NULL DEFAULT 0,
    INDEX idx_quotation_items_quote (quotation_id)
);

CREATE TABLE IF NOT EXISTS audit_logs (
    id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    username VARCHAR(100) NULL,
    action VARCHAR(40) NOT NULL,
    entity_type VARCHAR(60) NOT NULL,
    entity_id VARCHAR(60) NULL,
    summary VARCHAR(255) NOT NULL,
    old_data LONGTEXT NULL,
    new_data LONGTEXT NULL,
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_entity (entity_type, entity_id),
    INDEX idx_audit_created (created_at),
    INDEX idx_audit_user (user_id)
);

INSERT INTO permissions (permission_name, description) VALUES
('orders_view', 'View factory orders'),
('orders_manage', 'Create and manage factory orders'),
('quotations_view', 'View and print quotations'),
('quotations_manage', 'Create quotations and convert them to orders or invoices'),
('factory_view', 'View factory products, categories, glass and material types'),
('factory_manage', 'Create and manage factory products and setup data'),
('products_view', 'View inventory products'),
('products_manage', 'Create and manage inventory products'),
('inventory_view', 'View stock movements'),
('inventory_manage', 'Manage stock quantities and movements'),
('returned_inventory_view', 'View returned inventory'),
('expenses_view', 'View expenses'),
('expenses_manage', 'Create, edit and delete expenses'),
('settings_view', 'View system and company settings'),
('users_view', 'View users and roles'),
('audit_view', 'View the audit log'),
('backup_manage', 'Download backups and use data tools')
ON DUPLICATE KEY UPDATE description=VALUES(description);
