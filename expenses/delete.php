<?php
session_start();
if (!isset($_SESSION['user'])) { header('Location: ../index.php'); exit; }
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../includes/permissions.php';
require_once __DIR__.'/../includes/audit.php';
requirePermission('expenses_manage');
if ($_SERVER['REQUEST_METHOD']!=='POST' || empty($_SESSION['expense_csrf']) || !hash_equals($_SESSION['expense_csrf'],$_POST['csrf_token'] ?? '')) { $_SESSION['error']='The request expired.'; header('Location:index.php'); exit; }
$id=(int)($_POST['id'] ?? 0);
$stmt=$conn->prepare('SELECT * FROM expenses WHERE id=?'); $stmt->bind_param('i',$id); $stmt->execute(); $row=$stmt->get_result()->fetch_assoc();
if ($row) { $del=$conn->prepare('DELETE FROM expenses WHERE id=?'); $del->bind_param('i',$id); if($del->execute()){ auditLog($conn,'DELETE','expense',$id,'Deleted expense: '.$row['category'],$row,null); $_SESSION['success']='Expense deleted.'; } }
header('Location:index.php');
