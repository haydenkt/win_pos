<?php
session_start();
if(!isset($_SESSION['user'])){header('Location:../index.php');exit;}
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../includes/permissions.php';
require_once __DIR__.'/../includes/audit.php';
requirePermission('quotations_manage');
if($_SERVER['REQUEST_METHOD']!=='POST'||empty($_SESSION['quotation_csrf'])||!hash_equals($_SESSION['quotation_csrf'],$_POST['csrf_token']??'')){$_SESSION['error']='The request expired.';header('Location:index.php');exit;}
$customer=(int)($_POST['customer_id']??0);$date=$_POST['quote_date']??'';$valid=$_POST['valid_until']??null;$discount=max(0,(float)($_POST['discount']??0));$notes=trim($_POST['notes']??'');$products=$_POST['factory_product_id']??[];
if($customer<=0||!preg_match('/^\d{4}-\d{2}-\d{2}$/',$date)||!count($products)){$_SESSION['error']='Customer, date and at least one item are required.';header('Location:add.php');exit;}
$conn->begin_transaction();
try{
 $result=$conn->query('SELECT COALESCE(MAX(id),0)+1 next_no FROM quotations FOR UPDATE');$next=(int)$result->fetch_assoc()['next_no'];$quoteNo='QUO-'.date('Y').'-'.str_pad((string)$next,6,'0',STR_PAD_LEFT);
 $subtotal=0;$items=[];
 foreach($products as $i=>$productId){$productId=(int)$productId;$w=max(0,(float)($_POST['width_mm'][$i]??0));$h=max(0,(float)($_POST['height_mm'][$i]??0));$qty=max(1,(int)($_POST['quantity'][$i]??1));$rate=max(0,(float)($_POST['unit_price'][$i]??0));$wf=ceil(($w/304.8)*2)/2;$hf=ceil(($h/304.8)*2)/2;$sqft=$wf*$hf*$qty;$line=$sqft*$rate;if($productId<=0)continue;$items[]=[$productId,trim($_POST['description'][$i]??''),$w,$h,$qty,$sqft,$rate,$line];$subtotal+=$line;}
 if(!count($items))throw new Exception('At least one valid item is required.');$total=max(0,$subtotal-$discount);$user=(int)($_SESSION['user_id']??0);
 $stmt=$conn->prepare('INSERT INTO quotations (quote_no,customer_id,quote_date,valid_until,subtotal,discount,total,notes,created_by) VALUES (?,?,?,?,?,?,?,?,?)');$stmt->bind_param('sissdddsi',$quoteNo,$customer,$date,$valid,$subtotal,$discount,$total,$notes,$user);if(!$stmt->execute())throw new Exception($stmt->error);$quoteId=$conn->insert_id;
 $lookup=$conn->prepare('SELECT product_name FROM factory_products WHERE id=?');$insert=$conn->prepare('INSERT INTO quotation_items (quotation_id,factory_product_id,product_name,description,width_mm,height_mm,quantity,sqft,unit_price,total) VALUES (?,?,?,?,?,?,?,?,?,?)');
 foreach($items as $item){[$pid,$desc,$w,$h,$qty,$sqft,$rate,$line]=$item;$lookup->bind_param('i',$pid);$lookup->execute();$p=$lookup->get_result()->fetch_assoc();if(!$p)throw new Exception('A selected factory product was not found.');$name=$p['product_name'];$insert->bind_param('iissddiddd',$quoteId,$pid,$name,$desc,$w,$h,$qty,$sqft,$rate,$line);if(!$insert->execute())throw new Exception($insert->error);}
 auditLog($conn,'CREATE','quotation',$quoteId,'Created quotation '.$quoteNo,null,['customer_id'=>$customer,'total'=>$total]);$conn->commit();$_SESSION['success']='Quotation '.$quoteNo.' created.';header('Location:view.php?id='.$quoteId);exit;
}catch(Throwable $e){$conn->rollback();$_SESSION['error']='Quotation could not be saved: '.$e->getMessage();header('Location:add.php');exit;}
