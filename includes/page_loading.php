<?php
// Only safe, read-only page requests get a lightweight first response.
// POSTs, exports, printing, mobile/offline, and add/edit/save actions stay native.
if (PHP_SAPI === 'cli' || ($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') return;
$loadingRoutes = [
    '/dashboard.php' => 'Dashboard',
    '/invoices/index.php' => 'Invoices', '/invoices/view.php' => 'Invoice details',
    '/quotations/index.php' => 'Quotations', '/quotations/view.php' => 'Quotation details',
    '/orders/index.php' => 'Orders', '/orders/view.php' => 'Order details',
    '/customers/index.php' => 'Customers', '/products/index.php' => 'Products',
    '/factory_products/index.php' => 'Factory products', '/glass/index.php' => 'Glass',
    '/payments/index.php' => 'Payments', '/expenses/index.php' => 'Expenses',
    '/site_survey/index.php' => 'Site surveys', '/site_survey/view.php' => 'Survey details',
    '/supplier_prices/index.php' => 'Supplier price lists',
    '/inventory/returned.php' => 'Returned inventory',
    '/reports/index.php' => 'Reports', '/reports/sales.php' => 'Sales report',
    '/reports/payments.php' => 'Payment report', '/reports/customers.php' => 'Customer report',
    '/reports/profit.php' => 'Profit report',
    '/labour/index.php' => 'Labour', '/labour/workers/index.php' => 'Workers',
    '/labour/records/index.php' => 'Daily records', '/labour/transactions/index.php' => 'Transactions',
    '/labour/summary/index.php' => 'Labour summary', '/settings/index.php' => 'Settings',
];
$loadingPath = $_SERVER['SCRIPT_NAME'] ?? '';
if (!isset($loadingRoutes[$loadingPath]) || headers_sent()) return;
header('Cache-Control: no-store, private');
header('Vary: X-WinPOS-Content');
if (($_SERVER['HTTP_X_WINPOS_CONTENT'] ?? '') === '1') return;
// The follow-up request must be able to acquire the session immediately.
if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
$loadingTitle = $loadingRoutes[$loadingPath];
header('Content-Type: text/html; charset=UTF-8');
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=htmlspecialchars($loadingTitle);?> · Win POS</title>
<script>
try { document.documentElement.dataset.themeMode = localStorage.getItem('winposThemeMode') || 'system'; } catch (_) {}
</script>
<link rel="stylesheet" href="/assets/css/page-loading.css?v=1">
<script src="/assets/js/page-loading.js?v=1" defer></script>
</head>
<body>
<aside class="loading-sidebar" aria-hidden="true"><strong>Win POS</strong><span>Business workspace</span><div class="placeholder"></div><div class="placeholder"></div><div class="placeholder"></div><div class="placeholder"></div><div class="placeholder"></div></aside>
<main class="loading-main" id="loadingMain" aria-busy="true">
<header>WORKSPACE</header>
<h1><?=htmlspecialchars($loadingTitle);?></h1>
<section class="loading-panel">
<div class="loading-status" role="status" aria-live="polite"><span class="loading-spinner" id="loadingSpinner" aria-hidden="true"></span><p id="loadingMessage">Loading your data…</p></div>
<p class="loading-hint" id="loadingHint">Please wait while Win POS connects.</p>
<div class="loading-skeleton" id="loadingSkeleton" aria-hidden="true"><div class="placeholder"></div><div class="placeholder"></div><div class="placeholder"></div><div class="placeholder"></div></div>
<button id="loadingRetry" type="button" hidden>Try again</button>
<noscript>JavaScript is needed for this loading view. Enable JavaScript and reload the page.</noscript>
</section>
</main>
</body>
</html>
<?php exit;
