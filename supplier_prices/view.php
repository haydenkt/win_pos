<?php
require_once __DIR__.'/common.php';
$item=spQuery($conn,'SELECT i.*,d.title document_title,d.effective_date FROM supplier_price_items i JOIN supplier_price_documents d ON d.id=i.document_id WHERE i.id=?',[(int)($_GET['id']??0)])->fetch_assoc();
if(!$item){http_response_code(404);exit('Supplier item not found.');}
$specs=json_decode($item['specs_json'],true); $prices=json_decode($item['prices_json'],true); $flags=json_decode($item['flags_json'],true);
$page_title='Supplier product details';
require_once __DIR__.'/../includes/header.php'; require_once __DIR__.'/../includes/sidebar.php';
?>
<section class="page-hero"><div><div class="page-kicker"><?=spEscape($item['category']);?></div><h1 class="page-title"><?=spEscape($item['product_code']?:'Uncoded item');?></h1><p class="page-subtitle"><?=spEscape($item['description']);?></p></div><a class="btn btn-outline-primary" target="_blank" rel="noopener" href="source.php?id=<?=$item['document_id'];?>#page=<?=$item['page_number'];?>"><i class="fa fa-file-pdf me-1"></i> Original PDF · page <?=$item['page_number'];?></a></section>
<p class="text-body-secondary"><?=spEscape($item['document_title']);?> · Price list dated <?=date('d M Y',strtotime($item['effective_date']));?></p>
<?php if(isset($_GET['saved'])): ?><div class="alert alert-success">Product name saved.</div><?php endif;?>
<?php if(spCanEdit()): ?><a class="btn btn-primary mb-3" href="edit.php?id=<?=$item['id'];?>"><i class="fa fa-pen me-1"></i> Edit name</a><?php endif;?>
<?php foreach($flags as $flag): ?><div class="alert alert-warning py-2 small"><?=spEscape($flag);?></div><?php endforeach;?>
<div class="row g-4"><div class="col-lg-5"><div class="card mb-4"><div class="card-header"><h2 class="h5 mb-0">Listed prices</h2></div><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Colour / price type</th><th class="text-end">Price</th></tr></thead><tbody><?php foreach($prices as $price): ?><tr><td><?=spEscape($price['label']);?></td><td class="text-end fw-bold"><?=spEscape(spPrice($price));?></td></tr><?php endforeach;?></tbody></table></div></div>
<div class="card"><div class="card-header"><h2 class="h5 mb-0">Specifications as supplied</h2></div><div class="card-body"><?php foreach($specs as $spec): ?><div class="row py-2 border-bottom"><div class="col-5 text-body-secondary"><?=spEscape($spec['label']);?></div><div class="col-7" style="overflow-wrap:anywhere"><?=spEscape($spec['value']);?></div></div><?php endforeach;?><p class="small text-body-secondary mt-3 mb-0">Drawings and image-only dimensions are preserved on the original page. A dash or “Not listed” means the supplier did not provide a numeric price.</p></div></div></div>
<div class="col-lg-7"><div class="card"><div class="card-header"><h2 class="h5 mb-0">Original page · <?=$item['page_number'];?></h2></div><div class="card-body"><a href="source.php?id=<?=$item['document_id'];?>#page=<?=$item['page_number'];?>" target="_blank" rel="noopener"><img class="img-fluid rounded border" src="source.php?id=<?=$item['document_id'];?>&amp;page=<?=$item['page_number'];?>" alt="Original supplier price list page <?=$item['page_number'];?>"></a><p class="small text-body-secondary mt-2 mb-0">Open the PDF to zoom in on profile drawings and dimensions.</p></div></div></div></div>
<?php require_once __DIR__.'/../includes/footer.php'; ?>
