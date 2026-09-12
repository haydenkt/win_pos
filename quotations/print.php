<?php
session_start();

if (!isset($_SESSION['user'])) {
    header('Location:../index.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../includes/setting.php';
requirePermission('quotations_view');

$id = (int) ($_GET['id'] ?? 0);
$stmt = $conn->prepare('SELECT q.*, c.name customer_name, c.phone customer_phone, c.address customer_address FROM quotations q JOIN customers c ON c.id=q.customer_id WHERE q.id=?');
$stmt->bind_param('i', $id);
$stmt->execute();
$quote = $stmt->get_result()->fetch_assoc();

if (!$quote) {
    http_response_code(404);
    die('Quotation not found.');
}

$stmt = $conn->prepare('SELECT * FROM quotation_items WHERE quotation_id=? ORDER BY id');
$stmt->bind_param('i', $id);
$stmt->execute();
$items = $stmt->get_result();

$companyName = trim((string) getSetting($conn, 'company', 'company_name')) ?: 'Win POS';
$companyAddress = trim((string) getSetting($conn, 'company', 'address'));
$companyPhone = trim((string) getSetting($conn, 'company', 'phone'));
$companyEmail = trim((string) getSetting($conn, 'company', 'email'));
$companyLogo = trim((string) getSetting($conn, 'company', 'logo'));
$showLogo = $companyLogo !== '' && file_exists(__DIR__ . '/../uploads/logo/' . $companyLogo);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?=htmlspecialchars($quote['quote_no']);?> · Quotation</title>
    <style>
        *{box-sizing:border-box}body{margin:0;background:#eef2f6;color:#172033;font-family:Arial,sans-serif;font-size:13px}.actions{max-width:900px;margin:20px auto 0;display:flex;justify-content:flex-end;gap:10px}.btn{border:0;border-radius:8px;padding:10px 16px;background:#0d6efd;color:#fff;font-weight:700;cursor:pointer;text-decoration:none}.btn-light{background:#fff;color:#172033;border:1px solid #d9e0e8}.sheet{width:210mm;min-height:297mm;margin:18px auto;padding:18mm;background:#fff;box-shadow:0 10px 35px rgba(20,35,60,.12)}.header{display:flex;justify-content:space-between;gap:30px;padding-bottom:24px;border-bottom:3px solid #1e7f55}.brand{display:flex;align-items:center;gap:16px}.logo{width:74px;height:74px;object-fit:contain}.brand h1{margin:0 0 6px;font-size:24px}.muted{color:#667085;line-height:1.55}.document-title{text-align:right}.document-title h2{margin:0;color:#1e7f55;font-size:29px;letter-spacing:1px}.document-title strong{display:block;margin-top:8px;font-size:15px}.info-grid{display:grid;grid-template-columns:1.2fr .8fr;gap:24px;margin:26px 0}.label{display:block;color:#667085;font-size:11px;text-transform:uppercase;letter-spacing:.7px;margin-bottom:5px}.customer{padding:16px;border-radius:10px;background:#f7faf8}.meta{display:grid;grid-template-columns:1fr 1fr;gap:15px}.meta div{text-align:right}table{width:100%;border-collapse:collapse}th{padding:11px 8px;background:#1e7f55;color:#fff;text-align:left;font-size:11px;text-transform:uppercase}td{padding:11px 8px;border-bottom:1px solid #e6e9ee;vertical-align:top}.number{text-align:right;white-space:nowrap}.item-note{display:block;margin-top:4px;color:#667085;font-size:11px}.totals{width:42%;margin:18px 0 0 auto}.totals td{padding:8px}.totals .grand td{font-size:16px;font-weight:700;border-top:2px solid #1e7f55;border-bottom:0}.notes{margin-top:30px;padding:15px;border:1px solid #dde4ea;border-radius:8px;white-space:pre-wrap}.footer{margin-top:55px;padding-top:15px;border-top:1px solid #dde4ea;color:#667085;text-align:center;font-size:11px}@media print{body{background:#fff}.actions{display:none}.sheet{width:auto;min-height:auto;margin:0;padding:12mm;box-shadow:none}@page{size:A4;margin:0}}
    </style>
</head>
<body>
<div class="actions"><a class="btn btn-light" href="view.php?id=<?=$id;?>">Back</a><button class="btn" onclick="window.print()">Print quotation</button></div>
<main class="sheet">
    <header class="header">
        <div class="brand">
            <?php if ($showLogo): ?><img class="logo" src="/uploads/logo/<?=htmlspecialchars($companyLogo);?>" alt="Company logo"><?php endif; ?>
            <div><h1><?=htmlspecialchars($companyName);?></h1><div class="muted"><?=nl2br(htmlspecialchars($companyAddress));?><?php if ($companyPhone): ?><br><?=htmlspecialchars($companyPhone);?><?php endif; ?><?php if ($companyEmail): ?><br><?=htmlspecialchars($companyEmail);?><?php endif; ?></div></div>
        </div>
        <div class="document-title"><h2>QUOTATION</h2><strong><?=htmlspecialchars($quote['quote_no']);?></strong></div>
    </header>
    <section class="info-grid">
        <div class="customer"><span class="label">Quotation for</span><strong><?=htmlspecialchars($quote['customer_name']);?></strong><div class="muted"><?php if ($quote['customer_phone']): ?><?=htmlspecialchars($quote['customer_phone']);?><br><?php endif; ?><?=nl2br(htmlspecialchars($quote['customer_address']));?></div></div>
        <div class="meta"><div><span class="label">Quotation date</span><strong><?=date('d M Y', strtotime($quote['quote_date']));?></strong></div><div><span class="label">Valid until</span><strong><?=$quote['valid_until'] ? date('d M Y', strtotime($quote['valid_until'])) : '—';?></strong></div></div>
    </section>
    <table>
        <thead><tr><th>#</th><th>Product</th><th>Dimensions</th><th class="number">Qty</th><th class="number">Sqft</th><th class="number">Rate</th><th class="number">Amount</th></tr></thead>
        <tbody>
        <?php $number = 1; while ($item = $items->fetch_assoc()): ?>
            <tr><td><?=$number++;?></td><td><strong><?=htmlspecialchars($item['product_name']);?></strong><?php if ($item['description']): ?><span class="item-note"><?=htmlspecialchars($item['description']);?></span><?php endif; ?></td><td><?=number_format((float) $item['width_mm'], 0);?> × <?=number_format((float) $item['height_mm'], 0);?> mm</td><td class="number"><?=$item['quantity'];?></td><td class="number"><?=number_format((float) $item['sqft'], 2);?></td><td class="number"><?=number_format((float) $item['unit_price'], 2);?></td><td class="number"><?=number_format((float) $item['total'], 2);?></td></tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    <table class="totals"><tr><td>Subtotal</td><td class="number"><?=number_format((float) $quote['subtotal'], 2);?></td></tr><tr><td>Discount</td><td class="number">- <?=number_format((float) $quote['discount'], 2);?></td></tr><tr class="grand"><td>Total</td><td class="number"><?=number_format((float) $quote['total'], 2);?></td></tr></table>
    <?php if ($quote['notes']): ?><section class="notes"><span class="label">Notes</span><?=htmlspecialchars($quote['notes']);?></section><?php endif; ?>
    <footer class="footer">Thank you for the opportunity to prepare this quotation. Prices are valid until the date shown above.</footer>
</main>
</body>
</html>
