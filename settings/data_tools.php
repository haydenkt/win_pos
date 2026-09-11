<?php
session_start();
if(!isset($_SESSION['user'])){header('Location:../index.php');exit;}
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../includes/permissions.php';
requirePermission('backup_manage');
if(empty($_SESSION['data_tools_csrf']))$_SESSION['data_tools_csrf']=bin2hex(random_bytes(32));
$page_title='Backup & reset';
require_once __DIR__.'/../includes/header.php';
require_once __DIR__.'/../includes/sidebar.php';
?>
<div class="page-hero"><div><div class="page-kicker">System</div><h1 class="page-title">Backup & demo reset</h1><p class="page-subtitle">Protect business records, restore a saved copy, or clear training transactions.</p></div></div>
<?php if(!empty($_SESSION['success'])):?><div class="alert alert-success"><?=htmlspecialchars($_SESSION['success']);unset($_SESSION['success']);?></div><?php endif;?>
<?php if(!empty($_SESSION['error'])):?><div class="alert alert-danger"><?=htmlspecialchars($_SESSION['error']);unset($_SESSION['error']);?></div><?php endif;?>
<div class="row g-4">
<div class="col-lg-4"><div class="card h-100"><div class="card-body"><span class="theme-option-icon"><i class="fa fa-download"></i></span><h2 class="h5">Download backup</h2><p class="text-muted">Save customers, sales, products, factory setup, labour and finance records as one JSON file.</p><a href="download_backup.php" class="btn btn-primary"><i class="fa fa-download"></i> Download now</a></div></div></div>
<div class="col-lg-4"><div class="card h-100"><div class="card-body"><span class="theme-option-icon"><i class="fa fa-upload"></i></span><h2 class="h5">Restore backup</h2><p class="text-muted">This would replace all current business records. It is disabled until the owner explicitly approves that data replacement.</p><button class="btn btn-outline-secondary" disabled><i class="fa fa-lock"></i> Approval required</button></div></div></div>
<div class="col-lg-4"><div class="card h-100 border-danger"><div class="card-body"><span class="theme-option-icon text-danger"><i class="fa fa-eraser"></i></span><h2 class="h5">Clear demo transactions</h2><p class="text-muted">This would permanently delete customers, sales, payments, expenses, surveys, quotations, orders, returns and labour activity. It is disabled until the owner explicitly approves that deletion.</p><button class="btn btn-outline-danger" disabled><i class="fa fa-lock"></i> Approval required</button></div></div></div>
</div>
<?php require_once __DIR__.'/../includes/footer.php';?>
