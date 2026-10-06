<?php

function ensureMaterialCalculationSchema(mysqli $conn): void
{
    $conn->query(
        "CREATE TABLE IF NOT EXISTS material_calculation_templates (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(150) NOT NULL,
            description VARCHAR(255) NULL,
            status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_material_template_name (name)
        )"
    );

    $conn->query(
        "CREATE TABLE IF NOT EXISTS material_calculation_formulas (
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
        )"
    );

    $pieceColumn = $conn->query("SHOW COLUMNS FROM material_calculation_formulas LIKE 'usable_length_per_piece'");
    if ($pieceColumn->num_rows === 0) {
        $conn->query('ALTER TABLE material_calculation_formulas ADD COLUMN usable_length_per_piece DECIMAL(12,4) NOT NULL DEFAULT 18 AFTER fixed_amount');
    }

    $headingColumn = $conn->query("SHOW COLUMNS FROM material_calculation_formulas LIKE 'heading_rule'");
    if ($headingColumn->num_rows === 0) {
        $conn->query("ALTER TABLE material_calculation_formulas ADD COLUMN heading_rule VARCHAR(30) NOT NULL DEFAULT 'overall' AFTER usable_length_per_piece");
    }

    $conn->query(
        "CREATE TABLE IF NOT EXISTS material_calculation_meta (
            meta_key VARCHAR(100) NOT NULL PRIMARY KEY,
            meta_value VARCHAR(255) NULL
        )"
    );

    $headingRulesApplied = $conn->query("SELECT meta_key FROM material_calculation_meta WHERE meta_key='heading_rules_v1' LIMIT 1")->fetch_assoc();
    if (!$headingRulesApplied) {
        $slideTemplate = $conn->query("SELECT id FROM material_calculation_templates WHERE name='U-PVC Slide' LIMIT 1")->fetch_assoc();
        if ($slideTemplate) {
            $slideTemplateId = (int) $slideTemplate['id'];
            $rules = [
                'Outer frame' => 'overall',
                'Sash / leaf' => 'lower',
                'Glass bead' => 'lower_plus_perimeter',
                'Sash side cover' => 'lower',
            ];
            $stmt = $conn->prepare('UPDATE material_calculation_formulas SET heading_rule=? WHERE template_id=? AND material_name_en=?');
            foreach ($rules as $englishName => $rule) {
                $stmt->bind_param('sis', $rule, $slideTemplateId, $englishName);
                $stmt->execute();
            }
            $exists = $conn->prepare("SELECT id FROM material_calculation_formulas WHERE template_id=? AND heading_rule='crossbar_only' LIMIT 1");
            $exists->bind_param('i', $slideTemplateId);
            $exists->execute();
            if (!$exists->get_result()->fetch_assoc()) {
                $materialName = 'Heading / transom';
                $englishName = '';
                $unit = 'ft';
                $zero = 0.0;
                $usablePerPiece = 18.0;
                $rule = 'crossbar_only';
                $sort = 5;
                $stmt = $conn->prepare('INSERT INTO material_calculation_formulas (template_id, material_name, material_name_en, unit_label, width_factor, height_factor, area_factor, fixed_amount, usable_length_per_piece, heading_rule, sort_order) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
                $stmt->bind_param('isssdddddsi', $slideTemplateId, $materialName, $englishName, $unit, $zero, $zero, $zero, $zero, $usablePerPiece, $rule, $sort);
                $stmt->execute();
            }
        }
        $conn->query("INSERT INTO material_calculation_meta (meta_key, meta_value) VALUES ('heading_rules_v1', '1')");
    }

    $seeded = $conn->query("SELECT meta_key FROM material_calculation_meta WHERE meta_key='starter_template_seeded' LIMIT 1")->fetch_assoc();
    if ($seeded) {
        return;
    }

    $count = (int) $conn->query('SELECT COUNT(*) total FROM material_calculation_templates')->fetch_assoc()['total'];
    if ($count > 0) {
        $conn->query("INSERT INTO material_calculation_meta (meta_key, meta_value) VALUES ('starter_template_seeded', '1')");
        return;
    }

    $conn->begin_transaction();
    try {
        $name = 'U-PVC Slide';
        $description = 'Standard U-PVC sliding product material calculation.';
        $stmt = $conn->prepare('INSERT INTO material_calculation_templates (name, description) VALUES (?, ?)');
        $stmt->bind_param('ss', $name, $description);
        $stmt->execute();
        $templateId = $conn->insert_id;

        $materials = [
            ['ကျည်းဘောင်', 'Outer frame', 'ft', 2, 2, 0, 0, 18, 'overall', 1],
            ['အရွက်', 'Sash / leaf', 'ft', 2, 4, 0, 0, 18, 'lower', 2],
            ['မှန်ကလစ်', 'Glass bead', 'ft', 2, 4, 0, 0, 18, 'lower_plus_perimeter', 3],
            ['အရွက်ဘေးကပ်', 'Sash side cover', 'ft', 0, 2, 0, 0, 18, 'lower', 4],
            ['Heading / transom', '', 'ft', 0, 0, 0, 0, 18, 'crossbar_only', 5],
        ];
        $stmt = $conn->prepare('INSERT INTO material_calculation_formulas (template_id, material_name, material_name_en, unit_label, width_factor, height_factor, area_factor, fixed_amount, usable_length_per_piece, heading_rule, sort_order) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
        foreach ($materials as $material) {
            [$materialName, $englishName, $unit, $width, $height, $area, $fixed, $usablePerPiece, $headingRule, $sort] = $material;
            $stmt->bind_param('isssdddddsi', $templateId, $materialName, $englishName, $unit, $width, $height, $area, $fixed, $usablePerPiece, $headingRule, $sort);
            $stmt->execute();
        }
        $conn->query("INSERT INTO material_calculation_meta (meta_key, meta_value) VALUES ('starter_template_seeded', '1')");
        $conn->commit();
    } catch (Throwable $error) {
        $conn->rollback();
        throw $error;
    }
}

function materialFormulaLabel(array $formula): string
{
    if (($formula['heading_rule'] ?? '') === 'crossbar_only') {
        return 'W (heading only)';
    }
    $parts = [];
    $values = [
        'width_factor' => 'W',
        'height_factor' => 'H',
        'area_factor' => 'W×H',
        'fixed_amount' => '',
    ];

    foreach ($values as $field => $symbol) {
        $value = (float) ($formula[$field] ?? 0);
        if (abs($value) < 0.00001) {
            continue;
        }
        $number = rtrim(rtrim(number_format($value, 4, '.', ''), '0'), '.');
        $parts[] = $symbol === '' ? $number : $number . $symbol;
    }

    $label = $parts ? implode(' + ', $parts) : '0';
    if (($formula['heading_rule'] ?? '') === 'lower') {
        $label .= ' · lower height';
    } elseif (($formula['heading_rule'] ?? '') === 'lower_plus_perimeter') {
        $label .= ' · lower + heading perimeter';
    }
    return $label;
}
