<?php
session_start();
if (!isset($_SESSION['user'])) { header('Location:../index.php'); exit; }
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../includes/permissions.php';
requirePermission('quotations_manage');
$customers=$conn->query('SELECT id,name,phone FROM customers ORDER BY name');
$factory=$conn->query("SELECT id,product_name,default_price FROM factory_products WHERE status='Active' ORDER BY product_name");
if(empty($_SESSION['quotation_csrf']))$_SESSION['quotation_csrf']=bin2hex(random_bytes(32));
$page_title='New quotation';
require_once __DIR__.'/../includes/header.php';
require_once __DIR__.'/../includes/sidebar.php';
?>
<div class="page-hero"><div><h1 class="page-title">New quotation</h1><p class="page-subtitle">Dimensions calculate the billable square feet automatically.</p></div></div>
<form action="save.php" method="post" id="quoteForm">
<input type="hidden" name="csrf_token" value="<?=htmlspecialchars($_SESSION['quotation_csrf']);?>">
<div class="card mb-4"><div class="card-body"><div class="row g-3">
<div class="col-md-6"><label class="form-label">Customer</label><select name="customer_id" class="form-select" required><option value="">Choose customer</option><?php while($c=$customers->fetch_assoc()):?><option value="<?=$c['id'];?>"><?=htmlspecialchars($c['name'].' · '.$c['phone']);?></option><?php endwhile;?></select></div>
<div class="col-md-3"><label class="form-label">Quotation date</label><input type="date" name="quote_date" value="<?=date('Y-m-d');?>" class="form-control" required></div>
<div class="col-md-3"><label class="form-label">Valid until</label><input type="date" name="valid_until" value="<?=date('Y-m-d',strtotime('+30 days'));?>" class="form-control"></div>
</div></div></div>
<div class="card mb-4"><div class="card-header d-flex justify-content-between align-items-center"><span>Items</span><button type="button" id="addRow" class="btn btn-sm btn-outline-primary"><i class="fa fa-plus"></i> Add item</button></div><div class="table-responsive"><table class="table align-middle"><thead><tr><th style="min-width:220px">Product</th><th>Width mm</th><th>Height mm</th><th>Qty</th><th>Sqft</th><th>Rate / sqft</th><th>Total</th><th></th></tr></thead><tbody id="items"></tbody></table></div></div>
<div class="row g-4"><div class="col-lg-7"><div class="card card-body"><label class="form-label">Notes</label><textarea name="notes" rows="4" class="form-control"></textarea></div></div><div class="col-lg-5"><div class="card card-body"><div class="d-flex justify-content-between mb-3"><span>Subtotal</span><strong id="subtotalText">0.00</strong></div><div class="mb-3"><label class="form-label">Discount</label><input type="number" min="0" step="0.01" value="0" name="discount" id="discount" class="form-control"></div><div class="d-flex justify-content-between fs-5 border-top pt-3"><span>Total</span><strong id="totalText">0.00</strong></div><button class="btn btn-primary w-100 mt-4"><i class="fa fa-save"></i> Save quotation</button></div></div></div>
</form>
<template id="itemTemplate"><tr><td><select name="factory_product_id[]" class="form-select product" required><option value="">Choose product</option><?php while($p=$factory->fetch_assoc()):?><option value="<?=$p['id'];?>" data-price="<?=$p['default_price'];?>"><?=htmlspecialchars($p['product_name']);?></option><?php endwhile;?></select><input name="description[]" class="form-control mt-2" placeholder="Description (optional)"></td><td><input type="number" min="0" step="0.01" name="width_mm[]" class="form-control width" required></td><td><input type="number" min="0" step="0.01" name="height_mm[]" class="form-control height" required></td><td><input type="number" min="1" name="quantity[]" value="1" class="form-control qty" required></td><td><span class="sqft">0.00</span><input type="hidden" name="sqft[]" class="sqftInput"></td><td><input type="number" min="0" step="0.01" name="unit_price[]" class="form-control rate" required></td><td class="fw-bold"><span class="lineTotal">0.00</span><input type="hidden" name="line_total[]" class="lineTotalInput"></td><td><button type="button" class="btn btn-sm btn-outline-danger remove"><i class="fa fa-times"></i></button></td></tr></template>
<script>
(()=>{const body=document.getElementById('items'),template=document.getElementById('itemTemplate'),discount=document.getElementById('discount');
function total(){let sum=0;body.querySelectorAll('.lineTotalInput').forEach(i=>sum+=Number(i.value)||0);document.getElementById('subtotalText').textContent=sum.toFixed(2);document.getElementById('totalText').textContent=Math.max(0,sum-(Number(discount.value)||0)).toFixed(2)}
function calc(row){const w=Number(row.querySelector('.width').value)||0,h=Number(row.querySelector('.height').value)||0,q=Number(row.querySelector('.qty').value)||0,rate=Number(row.querySelector('.rate').value)||0;const wf=Math.ceil((w/304.8)*2)/2,hf=Math.ceil((h/304.8)*2)/2,sq=wf*hf*q,line=sq*rate;row.querySelector('.sqft').textContent=sq.toFixed(2);row.querySelector('.sqftInput').value=sq.toFixed(2);row.querySelector('.lineTotal').textContent=line.toFixed(2);row.querySelector('.lineTotalInput').value=line.toFixed(2);total()}
function add(){const row=template.content.firstElementChild.cloneNode(true);row.addEventListener('input',()=>calc(row));row.querySelector('.product').addEventListener('change',e=>{row.querySelector('.rate').value=e.target.selectedOptions[0]?.dataset.price||0;calc(row)});row.querySelector('.remove').addEventListener('click',()=>{row.remove();total()});body.appendChild(row)}
document.getElementById('addRow').addEventListener('click',add);discount.addEventListener('input',total);add();})();
</script>
<?php require_once __DIR__.'/../includes/footer.php';?>
