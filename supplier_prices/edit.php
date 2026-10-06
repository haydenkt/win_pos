<?php
require_once __DIR__.'/common.php';
if (!spCanEdit()) { http_response_code(403); exit('You do not have permission to edit supplier names.'); }
$type=($_GET['type']??'item')==='document'?'document':'item';
$id=(int)($_GET['id']??0);
$table=$type==='document'?'supplier_price_documents':'supplier_price_items';
$field=$type==='document'?'title':'description';
$record=spQuery($conn,"SELECT * FROM $table WHERE id=?",[$id])->fetch_assoc();
if (!$record) { http_response_code(404); exit('Supplier record not found.'); }
if (empty($_SESSION['supplier_names_csrf'])) $_SESSION['supplier_names_csrf']=bin2hex(random_bytes(32));
$name=$record[$field]; $error='';
$back=$type==='document'?'index.php':'view.php?id='.$id;
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $name=is_string($_POST['name']??null)?trim($_POST['name']):'';
    $token=$_POST['csrf_token']??'';
    if (!is_string($token) || !hash_equals($_SESSION['supplier_names_csrf'],$token)) {
        http_response_code(403); $error='The request expired. Please reload and try again.';
    } elseif ($name==='' || !mb_check_encoding($name,'UTF-8') || mb_strlen($name)>($type==='document'?255:2000)) {
        $error='Enter a name of 1–'.($type==='document'?255:2000).' characters.';
    } else {
        try {
            $conn->begin_transaction();
            $current=spQuery($conn,"SELECT * FROM $table WHERE id=? FOR UPDATE",[$id])->fetch_assoc();
            if (!$current) throw new RuntimeException('Record not found.');
            if (!hash_equals(hash('sha256',$current[$field]),(string)($_POST['version']??''))) {
                throw new RuntimeException('This name was changed in another window. Reload before saving.');
            }
            $stmt=$conn->prepare("UPDATE $table SET $field=? WHERE id=?");
            $stmt->bind_param('si',$name,$id); $stmt->execute();
            if ($current[$field]!==$name) {
                require_once __DIR__.'/../includes/audit.php';
                auditLog($conn,'UPDATE','supplier_price_'.$type,$id,'Updated supplier catalogue name',[$field=>$current[$field]],[$field=>$name]);
            }
            $conn->commit();
            header('Location: '.$back.($type==='document'?'?saved=1':'&saved=1')); exit;
        } catch (Throwable $exception) {
            $conn->rollback();
            $error=$exception instanceof RuntimeException && !($exception instanceof mysqli_sql_exception)
                ? $exception->getMessage() : 'The name could not be saved. Please try again.';
        }
    }
}
$page_title=$type==='document'?'Rename price list':'Edit supplier product name';
require_once __DIR__.'/../includes/header.php'; require_once __DIR__.'/../includes/sidebar.php';
?>
<a class="content-back" href="<?=spEscape($back);?>"><i class="fa fa-arrow-left"></i> Back to <?=$type==='document'?'Supplier price lists':'Product details';?></a>
<section class="page-hero"><div><h1 class="page-title"><?=spEscape($page_title);?></h1><p class="page-subtitle"><?=$type==='document'?'Choose the name you want to show for this catalogue.':'Correct the Burmese or English name shown for this product. Saved names can be searched immediately.';?></p></div></section>
<?php if($error): ?><div class="alert alert-danger"><?=spEscape($error);?></div><?php endif;?>
<div class="row g-4"><div class="col-lg-6"><form method="post" class="card"><div class="card-body">
<input type="hidden" name="csrf_token" value="<?=spEscape($_SESSION['supplier_names_csrf']);?>">
<input type="hidden" name="version" value="<?=spEscape(hash('sha256',$record[$field]));?>">
<?php if($type==='item'): ?><p><strong><?=spEscape($record['product_code']?:'Uncoded item');?></strong><br><span class="text-body-secondary"><?=spEscape($record['category']);?></span></p><?php endif;?>
<label for="supplierName" class="form-label"><?=$type==='document'?'Price-list name':'Product name / description';?></label>
<textarea id="supplierName" name="name" class="form-control" rows="4" maxlength="<?=$type==='document'?255:2000;?>" required><?=spEscape($name);?></textarea>
<div class="d-flex gap-2 mt-4"><button class="btn btn-primary"><i class="fa fa-save me-1"></i> Save name</button><a class="btn btn-light" href="<?=spEscape($back);?>">Cancel</a></div>
</div></form></div>
<?php if($type==='item'): ?><div class="col-lg-6"><div class="card"><div class="card-header">Original supplier page</div><div class="card-body"><a href="source.php?id=<?=$record['document_id'];?>#page=<?=$record['page_number'];?>" target="_blank" rel="noopener"><img class="img-fluid" src="source.php?id=<?=$record['document_id'];?>&amp;page=<?=$record['page_number'];?>" alt="Original supplier page"></a></div></div></div><?php endif;?></div>
<?php require_once __DIR__.'/../includes/footer.php'; ?>
