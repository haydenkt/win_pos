CREATE TABLE IF NOT EXISTS material_calculation_templates (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description VARCHAR(255) NULL,
    status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_material_template_name (name)
);

CREATE TABLE IF NOT EXISTS material_calculation_formulas (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    template_id INT NOT NULL,
    material_name VARCHAR(150) NOT NULL,
    material_name_en VARCHAR(150) NULL,
    unit_label VARCHAR(20) NOT NULL DEFAULT 'ft',
    width_factor DECIMAL(12,4) NOT NULL DEFAULT 0,
    height_factor DECIMAL(12,4) NOT NULL DEFAULT 0,
    area_factor DECIMAL(12,4) NOT NULL DEFAULT 0,
    fixed_amount DECIMAL(12,4) NOT NULL DEFAULT 0,
    usable_length_per_piece DECIMAL(12,4) NOT NULL DEFAULT 18,
    heading_rule VARCHAR(30) NOT NULL DEFAULT 'overall',
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_material_formula_template (template_id)
);

CREATE TABLE IF NOT EXISTS material_calculation_meta (
    meta_key VARCHAR(100) NOT NULL PRIMARY KEY,
    meta_value VARCHAR(255) NULL
);
