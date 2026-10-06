<?php
session_start();

if (!isset($_SESSION['user'])) {
    header('Location:../index.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/schema.php';
requirePermission('factory_view');
ensureMaterialCalculationSchema($conn);

$templateRows = $conn->query("SELECT * FROM material_calculation_templates WHERE status='Active' ORDER BY name");
$calculatorTemplates = [];
while ($template = $templateRows->fetch_assoc()) {
    $templateId = (int) $template['id'];
    $stmt = $conn->prepare('SELECT * FROM material_calculation_formulas WHERE template_id=? ORDER BY sort_order, id');
    $stmt->bind_param('i', $templateId);
    $stmt->execute();
    $formulas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    foreach ($formulas as &$formula) {
        $formula['label'] = materialFormulaLabel($formula);
    }
    unset($formula);
    $calculatorTemplates[] = [
        'id' => $templateId,
        'name' => $template['name'],
        'description' => $template['description'],
        'formulas' => $formulas,
    ];
}

$page_title = 'Material calculation';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<style>
    .material-input-card .form-control { font-size: 1.1rem; font-weight: 700; }
    .material-result-value { color: var(--bs-primary); font-size: 1.05rem; font-weight: 800; white-space: nowrap; }
    .material-formula { color: var(--bs-secondary-color); font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 0.8rem; }
    .size-input { min-width: 82px; }
    .size-breakdown { min-width: 170px; font-size: .86rem; color: var(--bs-secondary-color); }
    .size-breakdown div + div { margin-top: .25rem; }
    .material-total-card { background: linear-gradient(135deg, var(--bs-primary), #0b5ed7); color: #fff; }
    .material-total-card .text-body-secondary { color: rgba(255,255,255,.75) !important; }
    @media print {
        .sidebar, .navbar, .mobile-menu-button, .sidebar-backdrop, .no-print { display: none !important; }
        main { margin: 0 !important; padding: 0 !important; }
        .card { box-shadow: none !important; break-inside: avoid; }
        .page-hero { padding-top: 0; }
    }
</style>

<section class="page-hero">
    <div>
        <div class="page-kicker">Factory</div>
        <h1 class="page-title">Material Calculation</h1>
        <p class="page-subtitle">Add all finished sizes and quantities to calculate their combined material and piece requirements.</p>
    </div>
    <div class="d-flex flex-wrap gap-2 no-print">
        <?php if (hasPermission('factory_manage')): ?><a href="setup.php" class="btn btn-outline-primary"><i class="fa fa-gear me-1"></i> Calculation setup</a><?php endif; ?>
        <button type="button" class="btn btn-outline-primary" id="printCalculation"><i class="fa fa-print me-1"></i> Print</button>
    </div>
</section>

<div class="row g-4">
    <div class="col-xl-5">
        <div class="card material-input-card h-100">
            <div class="card-header">
                <h2 class="h5 mb-0"><i class="fa fa-ruler-combined me-2"></i>Finished sizes</h2>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-3">
                    <div class="col-12">
                        <label class="form-label" for="productTemplate">Product type</label>
                        <select id="productTemplate" class="form-select" <?=$calculatorTemplates ? '' : 'disabled';?>>
                            <?php foreach ($calculatorTemplates as $calculatorTemplate): ?><option value="<?=$calculatorTemplate['id'];?>"><?=htmlspecialchars($calculatorTemplate['name']);?></option><?php endforeach; ?>
                        </select>
                        <div class="form-text" id="templateDescription"></div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-2" style="min-width:650px">
                        <thead><tr><th style="width:42px">#</th><th>Width (ft)</th><th>Height (ft)</th><th>Qty</th><th>Heading</th><th>Heading height</th><th style="width:42px"></th></tr></thead>
                        <tbody id="sizeRows"></tbody>
                    </table>
                </div>

                <div class="d-flex flex-wrap gap-2 mt-3 no-print">
                    <button type="button" class="btn btn-primary flex-grow-1" id="addSize">
                        <i class="fa fa-plus me-1"></i> Add size
                    </button>
                    <button type="button" class="btn btn-light" id="resetCalculation">
                        <i class="fa fa-rotate-left me-1"></i> Reset
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-7">
        <div class="card mb-4">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h2 class="h5 mb-0"><i class="fa fa-list-check me-2"></i>Required materials</h2>
                <span class="badge text-bg-primary" id="sizeBadge">1 size · 1 item</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Material</th>
                            <th>Formula</th>
                            <th>Size breakdown</th>
                            <th class="text-end">Total required</th>
                            <th class="text-end">Pieces</th>
                        </tr>
                    </thead>
                        <tbody id="materialRows"></tbody>
                </table>
            </div>
        </div>

        <div class="card material-total-card">
            <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <div class="text-body-secondary small text-uppercase">Totals by unit</div>
                    <div class="small">Combined requirements for the selected product</div>
                </div>
                <div class="d-flex flex-wrap justify-content-end gap-3" id="unitTotals"></div>
            </div>
        </div>
    </div>
</div>

<template id="sizeRowTemplate">
    <tr class="size-row">
        <td class="size-number fw-bold text-body-secondary"></td>
        <td><input type="number" class="form-control form-control-sm size-input size-width" min="0.5" step="0.5" value="3"></td>
        <td><input type="number" class="form-control form-control-sm size-input size-height" min="0.5" step="0.5" value="4"></td>
        <td><input type="number" class="form-control form-control-sm size-input size-quantity" min="1" step="1" value="1"></td>
        <td class="text-center"><input type="checkbox" class="form-check-input size-has-heading" title="This window has a top heading or fixed-glass section"></td>
        <td><input type="number" class="form-control form-control-sm size-input size-heading-height" min="0.5" step="0.5" value="1.5" disabled></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger remove-size" title="Delete size" aria-label="Delete size"><i class="fa fa-trash"></i></button></td>
    </tr>
</template>

<script>
(() => {
    const templates = <?=json_encode($calculatorTemplates, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);?>;
    const templateSelect = document.getElementById('productTemplate');
    const sizeRows = document.getElementById('sizeRows');
    const sizeRowTemplate = document.getElementById('sizeRowTemplate');
    const materialRows = document.getElementById('materialRows');
    const unitTotals = document.getElementById('unitTotals');

    const displayNumber = (value) => {
        const rounded = Math.round((value + Number.EPSILON) * 100) / 100;
        return new Intl.NumberFormat('en-US', { maximumFractionDigits: 2 }).format(rounded);
    };

    const displayResult = (value, unit) => `${displayNumber(value)}${unit.toLowerCase() === 'ft' ? '′' : ` ${unit}`}`;
    const selectedTemplate = () => templates.find((template) => String(template.id) === templateSelect.value) || templates[0] || null;

    const renumberSizes = () => {
        sizeRows.querySelectorAll('.size-row').forEach((row, index) => {
            row.querySelector('.size-number').textContent = index + 1;
            row.querySelector('.size-width').setAttribute('aria-label', `Size ${index + 1} width in feet`);
            row.querySelector('.size-height').setAttribute('aria-label', `Size ${index + 1} height in feet`);
            row.querySelector('.size-quantity').setAttribute('aria-label', `Size ${index + 1} quantity`);
            row.querySelector('.size-has-heading').setAttribute('aria-label', `Size ${index + 1} has heading`);
            row.querySelector('.size-heading-height').setAttribute('aria-label', `Size ${index + 1} heading height in feet`);
        });
    };

    const addSize = (width = 3, height = 4, quantity = 1) => {
        const row = sizeRowTemplate.content.firstElementChild.cloneNode(true);
        row.querySelector('.size-width').value = width;
        row.querySelector('.size-height').value = height;
        row.querySelector('.size-quantity').value = quantity;
        row.querySelectorAll('input').forEach((input) => {
            input.addEventListener('input', calculate);
            input.addEventListener('change', calculate);
        });
        row.querySelector('.size-has-heading').addEventListener('change', (event) => {
            row.querySelector('.size-heading-height').disabled = !event.target.checked;
        });
        row.querySelector('.remove-size').addEventListener('click', () => {
            if (sizeRows.children.length === 1) {
                row.querySelector('.size-width').value = '3';
                row.querySelector('.size-height').value = '4';
                row.querySelector('.size-quantity').value = '1';
            } else {
                row.remove();
            }
            renumberSizes();
            calculate();
        });
        sizeRows.appendChild(row);
        renumberSizes();
        calculate();
    };

    const getSizes = () => [...sizeRows.querySelectorAll('.size-row')].map((row) => ({
        width: Math.max(0, Number(row.querySelector('.size-width').value) || 0),
        height: Math.max(0, Number(row.querySelector('.size-height').value) || 0),
        quantity: Math.max(1, Math.floor(Number(row.querySelector('.size-quantity').value) || 1)),
        hasHeading: row.querySelector('.size-has-heading').checked,
        headingHeight: Math.max(0, Number(row.querySelector('.size-heading-height').value) || 0),
    })).filter((size) => size.width > 0 && size.height > 0);

    const calculate = () => {
        const sizes = getSizes();
        const template = selectedTemplate();
        materialRows.replaceChildren();
        unitTotals.replaceChildren();

        if (!template) {
            const row = document.createElement('tr');
            const cell = document.createElement('td');
            cell.colSpan = 5;
            cell.className = 'text-center text-body-secondary py-5';
            cell.textContent = 'No active product templates. Add one in Calculation Setup.';
            row.appendChild(cell);
            materialRows.appendChild(row);
            document.getElementById('templateDescription').textContent = '';
            document.getElementById('sizeBadge').textContent = 'No product template';
            return;
        }

        const totals = {};
        let totalPieces = 0;
        template.formulas.forEach((formula) => {
            const unit = formula.unit_label || 'ft';
            const breakdown = sizes.map((size) => {
                const headingHeight = size.hasHeading ? Math.min(size.height, size.headingHeight) : 0;
                const lowerHeight = Math.max(0, size.height - headingHeight);
                const headingRule = formula.heading_rule || 'overall';
                let perItem = 0;
                if (headingRule === 'crossbar_only') {
                    perItem = size.hasHeading ? size.width : 0;
                } else {
                    const calculationHeight = size.hasHeading && (headingRule === 'lower' || headingRule === 'lower_plus_perimeter')
                        ? lowerHeight
                        : size.height;
                    perItem = (Number(formula.width_factor) * size.width)
                        + (Number(formula.height_factor) * calculationHeight)
                        + (Number(formula.area_factor) * size.width * calculationHeight)
                        + Number(formula.fixed_amount);
                    if (size.hasHeading && headingRule === 'lower_plus_perimeter') {
                        perItem += (2 * size.width) + (2 * headingHeight);
                    }
                }
                return { ...size, headingHeight, perItem, subtotal: perItem * size.quantity };
            });
            const total = breakdown.reduce((sum, size) => sum + size.subtotal, 0);
            if ((formula.heading_rule || 'overall') === 'crossbar_only' && total <= 0) return;
            const usablePerPiece = Math.max(0, Number(formula.usable_length_per_piece) || 0);
            const pieces = usablePerPiece > 0 && total > 0 ? Math.ceil((total - 0.0000001) / usablePerPiece) : null;
            totals[unit] = (totals[unit] || 0) + total;
            if (pieces !== null) totalPieces += pieces;

            const row = document.createElement('tr');
            const materialCell = document.createElement('td');
            const name = document.createElement('strong');
            name.textContent = formula.material_name;
            materialCell.appendChild(name);
            if (formula.material_name_en) {
                const english = document.createElement('small');
                english.className = 'd-block text-body-secondary';
                english.textContent = formula.material_name_en;
                materialCell.appendChild(english);
            }
            const formulaCell = document.createElement('td');
            const formulaLabel = document.createElement('span');
            formulaLabel.className = 'material-formula';
            formulaLabel.textContent = formula.label;
            formulaCell.appendChild(formulaLabel);
            const eachCell = document.createElement('td');
            eachCell.className = 'size-breakdown';
            breakdown.forEach((size) => {
                const line = document.createElement('div');
                const headingText = size.hasHeading ? ` · heading ${displayNumber(size.headingHeight)}′` : '';
                line.textContent = `${displayNumber(size.width)}′×${displayNumber(size.height)}′${headingText} × ${size.quantity} = ${displayResult(size.subtotal, unit)}`;
                eachCell.appendChild(line);
            });
            const totalCell = document.createElement('td');
            totalCell.className = 'text-end material-result-value';
            totalCell.textContent = displayResult(total, unit);
            const piecesCell = document.createElement('td');
            piecesCell.className = 'text-end';
            if (pieces === null) {
                piecesCell.textContent = '—';
            } else {
                const pieceValue = document.createElement('strong');
                pieceValue.className = 'material-result-value';
                pieceValue.textContent = `${pieces} ${pieces === 1 ? 'pc' : 'pcs'}`;
                const pieceRule = document.createElement('small');
                pieceRule.className = 'd-block text-body-secondary';
                pieceRule.textContent = `${displayResult(usablePerPiece, unit)} usable / pc`;
                piecesCell.append(pieceValue, pieceRule);
            }
            row.append(materialCell, formulaCell, eachCell, totalCell, piecesCell);
            materialRows.appendChild(row);
        });

        Object.entries(totals).forEach(([unit, total]) => {
            const value = document.createElement('div');
            value.className = 'fs-2 fw-bold';
            value.textContent = displayResult(total, unit);
            unitTotals.appendChild(value);
        });
        if (totalPieces > 0) {
            const pieceValue = document.createElement('div');
            pieceValue.className = 'fs-2 fw-bold';
            pieceValue.textContent = `${totalPieces} pcs`;
            unitTotals.appendChild(pieceValue);
        }

        document.getElementById('templateDescription').textContent = template.description || '';
        const itemCount = sizes.reduce((sum, size) => sum + size.quantity, 0);
        document.getElementById('sizeBadge').textContent = `${template.name} · ${sizes.length} ${sizes.length === 1 ? 'size' : 'sizes'} · ${itemCount} ${itemCount === 1 ? 'item' : 'items'}`;
    };

    templateSelect.addEventListener('change', calculate);
    document.getElementById('addSize').addEventListener('click', () => addSize());

    document.getElementById('resetCalculation').addEventListener('click', () => {
        sizeRows.replaceChildren();
        addSize(3, 4, 1);
    });
    document.getElementById('printCalculation').addEventListener('click', () => window.print());
    addSize(3, 4, 1);
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
