<?php
session_start();
if (!isset($_SESSION['user'])) { header('Location: ../index.php'); exit; }
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../includes/permissions.php';
require_once __DIR__.'/../includes/audit.php';
requirePermission('expenses_manage');
if ($_SERVER['REQUEST_METHOD']!=='POST' || empty($_SESSION['expense_csrf']) || !hash_equals($_SESSION['expense_csrf'], $_POST['csrf_token'] ?? '')) { $_SESSION['error']='The request expired. Please try again.'; header('Location:index.php'); exit; }

$date=$_POST['expense_date'] ?? '';
$category=trim($_POST['category'] ?? '');
$supplier=trim($_POST['supplier'] ?? '');
$amount=(float)($_POST['amount'] ?? 0);
$description=trim($_POST['description'] ?? '');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/',$date) || $category==='' || $amount<=0) { $_SESSION['error']='Date, category and a positive amount are required.'; header('Location:index.php'); exit; }

$receipt=null;
if (!empty($_FILES['receipt']['name']) && $_FILES['receipt']['error']!==UPLOAD_ERR_NO_FILE) {
    if ($_FILES['receipt']['error']!==UPLOAD_ERR_OK || $_FILES['receipt']['size']>5*1024*1024) { $_SESSION['error']='The receipt could not be uploaded or is larger than 5 MB.'; header('Location:index.php'); exit; }
    $allowed=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','application/pdf'=>'pdf'];
    $mime=(new finfo(FILEINFO_MIME_TYPE))->file($_FILES['receipt']['tmp_name']);
    if (!isset($allowed[$mime])) { $_SESSION['error']='Receipt must be JPG, PNG, WebP or PDF.'; header('Location:index.php'); exit; }
    $dir=__DIR__.'/../uploads/expense_receipts';
    if (!is_dir($dir)) mkdir($dir,0755,true);
    $receipt=bin2hex(random_bytes(16)).'.'.$allowed[$mime];
    if (!move_uploaded_file($_FILES['receipt']['tmp_name'],$dir.'/'.$receipt)) { $_SESSION['error']='The receipt could not be saved.'; header('Location:index.php'); exit; }
}

$user=(int)($_SESSION['user_id'] ?? 0);
$stmt=$conn->prepare('INSERT INTO expenses (expense_date,category,supplier,amount,description,receipt_path,created_by) VALUES (?,?,?,?,?,?,?)');
$stmt->bind_param('sssdssi',$date,$category,$supplier,$amount,$description,$receipt,$user);
if ($stmt->execute()) { $id=$conn->insert_id; auditLog($conn,'CREATE','expense',$id,'Added expense: '.$category,null,['amount'=>$amount,'date'=>$date]); $_SESSION['success']='Expense added.'; } else { $_SESSION['error']='Expense could not be saved.'; }
header('Location:index.php?month='.substr($date,0,7));
