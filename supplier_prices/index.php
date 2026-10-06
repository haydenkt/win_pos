<?php
require_once __DIR__ . '/common.php';
$search=trim((string)($_GET['q']??''));
$document=(int)($_GET['document']??0);
$category=trim((string)($_GET['category']??''));
$page=max(1,(int)($_GET['page']??1)); $perPage=30;
$documents=spQuery($conn,'SELECT * FROM supplier_price_documents ORDER BY effective_date DESC')->fetch_all(MYSQLI_ASSOC);
$where=['1=1']; $params=[];
if ($document) { $where[]='i.document_id=?'; $params[]=$document; }
if ($category!=='') { $where[]='i.category=?'; $params[]=$category; }
foreach (array_slice(preg_split('/\s+/u',$search,-1,PREG_SPLIT_NO_EMPTY),0,10) as $term) {
    $where[]='(i.search_text LIKE ? OR i.description LIKE ?)';
    $pattern='%'.addcslashes($term,'_%\\').'%'; array_push($params,$pattern,$pattern);
}
$condition=implode(' AND ',$where);
$total=(int)spQuery($conn,'SELECT COUNT(*) n FROM supplier_price_items i WHERE '.$condition,$params)->fetch_assoc()['n'];
$pages=max(1,(int)ceil($total/$perPage)); $page=min($page,$pages); $offset=($page-1)*$perPage;
$items=spQuery($conn,"SELECT i.*, d.title document_title,d.effective_date FROM supplier_price_items i JOIN supplier_price_documents d ON d.id=i.document_id WHERE $condition ORDER BY d.effective_date DESC,i.page_number,i.table_number,i.source_row_number LIMIT $perPage OFFSET $offset",$params);
$categories=spQuery($conn,'SELECT DISTINCT category FROM supplier_price_items'.($document?' WHERE document_id=?':'').' ORDER BY category',$document?[$document]:[])->fetch_all(MYSQLI_ASSOC);
$totalItems=array_sum(array_column($documents,'item_count')); $totalPages=array_sum(array_column($documents,'page_count'));
$page_title='Supplier price lists';
require_once __DIR__.'/../includes/header.php';
require_once __DIR__.'/../includes/sidebar.php';
function spPageLink(int $number): string { $q=$_GET; $q['page']=$number; return '?'.http_build_query($q); }
?>
<style>
.supplier-code { font-size:1.05rem; font-weight:750; }
.supplier-description { max-width:390px; line-height:1.7; }
.supplier-prices { min-width:165px; }
.supplier-price-line { display:flex; justify-content:space-between; gap:1rem; font-size:.85rem; }
.supplier-price-line strong { white-space:nowrap; }
.supplier-preview { text-decoration:none; }
.supplier-stat { font-size:1.8rem; font-weight:750; }
@media(max-width:767px) { .supplier-table thead {display:none;} .supplier-table,.supplier-table tbody,.supplier-table tr,.supplier-table td {display:block;} .supplier-table tr {padding:.8rem; border-bottom:1px solid var(--bs-border-color);} .supplier-table td {border:0; padding:.35rem;} .supplier-description {max-width:none;} }
</style>
<section class="page-hero"><div><div class="page-kicker">Supplier catalogue</div><h1 class="page-title">Supplier Price Lists</h1><p class="page-subtitle">Find supplier products, specifications, and prices from the original catalogues.</p></div><div class="text-end"><div class="supplier-stat"><?=number_format($totalItems);?></div><span class="text-body-secondary">items · <?=$totalPages;?> source pages</span></div></section>
<div class="row g-3 mb-4">
<?php if(isset($_GET['saved'])): ?><div class="col-12"><div class="alert alert-success">Price-list name saved.</div></div><?php endif;?>
<?php foreach($documents as $doc): ?>
<div class="col-md-6"><div class="card h-100"><div class="card-body"><div class="d-flex justify-content-between gap-3"><div><h2 class="h6 mb-1"><?=spEscape($doc['title']);?></h2><span class="small text-body-secondary">Price list dated <?=date('d M Y',strtotime($doc['effective_date']));?> · <?=number_format($doc['item_count']);?> items</span></div><div class="d-flex flex-wrap gap-2 align-self-start"><?php if(spCanEdit()): ?><a class="btn btn-sm btn-outline-primary" href="edit.php?type=document&amp;id=<?=$doc['id'];?>">Rename</a><?php endif;?><a class="btn btn-sm btn-outline-primary" href="source.php?id=<?=$doc['id'];?>" target="_blank" rel="noopener">PDF <i class="fa fa-arrow-up-right-from-square"></i></a></div></div></div></div></div>
<?php endforeach; ?>
</div>
<form method="get" class="card mb-4"><div class="card-body"><div class="row g-3 align-items-end">
<div class="col-lg-5"><label for="supplierSearch" class="form-label">Search products</label><input id="supplierSearch" type="search" name="q" class="form-control" value="<?=spEscape($search);?>" placeholder="Code, series, size, grade, or description…"></div>
<div class="col-lg-3"><label for="documentFilter" class="form-label">Price list</label><select id="documentFilter" name="document" class="form-select" onchange="this.form.elements.category.value='';this.form.submit()"><option value="0">All price lists</option><?php foreach($documents as $doc): ?><option value="<?=$doc['id'];?>" <?=$document===(int)$doc['id']?'selected':'';?>><?=spEscape($doc['title']);?></option><?php endforeach;?></select></div>
<div class="col-lg-4"><label for="categoryFilter" class="form-label">Series / category</label><select id="categoryFilter" name="category" class="form-select"><option value="">All series</option><?php foreach($categories as $cat): ?><option value="<?=spEscape($cat['category']);?>" <?=$category===$cat['category']?'selected':'';?>><?=spEscape($cat['category']);?></option><?php endforeach;?></select></div>
<div class="col-12 d-flex gap-2"><button class="btn btn-primary"><i class="fa fa-search me-1"></i> Search</button><a class="btn btn-light" href="index.php">Clear</a><span class="ms-auto align-self-center small text-body-secondary"><?=number_format($total);?> results</span></div>
</div></div></form>
<p class="small text-body-secondary">Prices are shown as printed in the dated catalogues. Colours and retail / wholesale prices are separate. Open an item to see its drawing and original specifications.</p>
<div class="card"><div class="table-responsive"><table class="table supplier-table align-middle mb-0"><thead><tr><th>Product / series</th><th>Specifications</th><th>Listed prices</th><th>Source</th></tr></thead><tbody>
<?php while($item=$items->fetch_assoc()): $specs=json_decode($item['specs_json'],true); $prices=json_decode($item['prices_json'],true); $flags=json_decode($item['flags_json'],true); ?>
<tr><td><a class="supplier-code" href="view.php?id=<?=$item['id'];?>"><?=spEscape($item['product_code']?:'Uncoded item');?></a><div class="small text-body-secondary mt-1"><?=spEscape($item['category']);?></div><?php if(spCanEdit()): ?><a class="btn btn-sm btn-outline-primary mt-2" href="edit.php?id=<?=$item['id'];?>"><i class="fa fa-pen me-1"></i> Edit name</a><?php endif;?></td>
<td class="supplier-description"><div><?=spEscape($item['description']);?></div><div class="small text-body-secondary"><?php $displaySpecs=array_filter($specs,fn($s)=>!preg_match('/code|^item|descript|brand|grade/i',$s['label'])); foreach($displaySpecs as $spec): ?><span class="d-inline-block me-2"><?=spEscape($spec['label']);?>: <?=spEscape($spec['value']);?></span><?php endforeach;?></div></td>
<td class="supplier-prices"><?php foreach($prices as $price): if($price['raw']==='')continue; ?><div class="supplier-price-line"><span class="text-body-secondary"><?=spEscape($price['label']);?></span><strong><?=spEscape(spPrice($price));?></strong></div><?php endforeach;?><?php if(!array_filter($prices,fn($p)=>$p['raw']!=='')): ?><span class="text-body-secondary">Price not listed</span><?php endif;?></td>
<td><div class="small text-body-secondary mb-2"><?=date('d M Y',strtotime($item['effective_date']));?> · Page <?=$item['page_number'];?></div><a class="btn btn-sm btn-outline-primary" href="view.php?id=<?=$item['id'];?>">View details</a><?php if(array_filter($flags,fn($f)=>!str_starts_with($f,'Myanmar'))): ?><div class="small text-warning mt-1">Check source details</div><?php endif;?></td></tr>
<?php endwhile; ?>
<?php if(!$total): ?><tr><td colspan="4" class="text-center py-5 text-body-secondary">No matching products. Try a shorter code or clear the filters.</td></tr><?php endif;?></tbody></table></div></div>
<div class="d-flex align-items-center justify-content-between my-4"><span class="small text-body-secondary">Page <?=$page;?> of <?=$pages;?></span><div class="d-flex gap-2"><?php if($page>1): ?><a class="btn btn-outline-primary" href="<?=spEscape(spPageLink($page-1));?>">Previous</a><?php endif;?><?php if($page<$pages): ?><a class="btn btn-outline-primary" href="<?=spEscape(spPageLink($page+1));?>">Next</a><?php endif;?></div></div>
<?php require_once __DIR__.'/../includes/footer.php'; ?>
