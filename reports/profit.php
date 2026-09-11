<?php
session_start();
if(!isset($_SESSION['user'])){header('Location:../index.php');exit;}
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../includes/permissions.php';
requirePermission('reports_view');
$month=preg_match('/^\d{4}-\d{2}$/',$_GET['month']??'')?$_GET['month']:date('Y-m');
$stmt=$conn->prepare("SELECT COALESCE(SUM(grand_total),0) total FROM invoices WHERE DATE_FORMAT(created_at,'%Y-%m')=? AND invoice_status<>'Cancelled'");$stmt->bind_param('s',$month);$stmt->execute();$revenue=(float)$stmt->get_result()->fetch_assoc()['total'];
$stmt=$conn->prepare("SELECT COALESCE(SUM(amount),0) total FROM expenses WHERE DATE_FORMAT(expense_date,'%Y-%m')=?");$stmt->bind_param('s',$month);$stmt->execute();$expenses=(float)$stmt->get_result()->fetch_assoc()['total'];
$stmt=$conn->prepare("SELECT COALESCE(SUM(amount),0) total FROM labour_transactions WHERE DATE_FORMAT(transaction_date,'%Y-%m')=? AND type IN ('Cash Payment','Saving Withdrawal')");$stmt->bind_param('s',$month);$stmt->execute();$labour=(float)$stmt->get_result()->fetch_assoc()['total'];
$net=$revenue-$expenses-$labour;
$page_title='Profit report';require_once __DIR__.'/../includes/header.php';require_once __DIR__.'/../includes/sidebar.php';
?>
<div class="page-hero"><div><div class="page-kicker">Reports</div><h1 class="page-title">Monthly profit</h1><p class="page-subtitle">Sales less operating expenses and paid labour transactions.</p></div><form method="get"><input type="month" class="form-control" name="month" value="<?=htmlspecialchars($month);?>" onchange="this.form.submit()"></form></div>
<div class="row g-3">
<div class="col-md-3"><div class="card metric-card"><div class="card-body"><div class="metric-label">Invoice revenue</div><div class="metric-value text-success"><?=number_format($revenue,2);?></div></div></div></div>
<div class="col-md-3"><div class="card metric-card"><div class="card-body"><div class="metric-label">Operating expenses</div><div class="metric-value text-danger"><?=number_format($expenses,2);?></div></div></div></div>
<div class="col-md-3"><div class="card metric-card"><div class="card-body"><div class="metric-label">Labour paid</div><div class="metric-value text-warning"><?=number_format($labour,2);?></div></div></div></div>
<div class="col-md-3"><div class="card metric-card"><div class="card-body"><div class="metric-label">Estimated net</div><div class="metric-value <?=$net>=0?'text-primary':'text-danger';?>"><?=number_format($net,2);?></div></div></div></div>
</div>
<div class="alert alert-info mt-4"><i class="fa fa-circle-info me-2"></i>This is an operating estimate. Product purchase cost is not deducted again because purchases can be recorded as expenses.</div>
<?php require_once __DIR__.'/../includes/footer.php';?>
