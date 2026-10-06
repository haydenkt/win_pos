<?php
// Command-line only: imports the reviewed source bundle without touching POS stock or prices.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/schema.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
ensureSupplierPriceSchema($conn);
function importRows(mysqli $conn, string $prefix, array $rows): void {
    foreach (array_chunk($rows, 100) as $chunk) {
        $groups=[]; $values=[];
        foreach ($chunk as $row) { $groups[]='('.implode(',',array_fill(0,count($row),'?')).')'; array_push($values,...$row); }
        $stmt=$conn->prepare($prefix.implode(',',$groups));
        $stmt->bind_param(str_repeat('s',count($values)),...$values); $stmt->execute();
    }
}
$guard = '<?php http_response_code(404); exit; ?>' . "\n";
$bundle = json_decode(substr(file_get_contents(__DIR__ . '/../storage/supplier_prices/catalog.json.php'),strlen($guard)), true, 512, JSON_THROW_ON_ERROR);
foreach ($bundle as $doc) {
    $check = $conn->prepare('SELECT id, sha256, item_count FROM supplier_price_documents WHERE slug=?');
    $check->bind_param('s', $doc['slug']); $check->execute();
    $existing = $check->get_result()->fetch_assoc();
    if ($existing) {
        if ($existing['sha256'] !== $doc['sha256'] || (int)$existing['item_count'] !== count($doc['items'])) {
            throw new RuntimeException('Existing import differs; use a new document version.');
        }
        echo $doc['title'] . ': already imported' . PHP_EOL; continue;
    }
    $conn->begin_transaction();
    try {
        $pageCount=count($doc['pages']); $itemCount=count($doc['items']);
        $stmt=$conn->prepare('INSERT INTO supplier_price_documents (slug,title,filename,effective_date,sha256,page_count,item_count) VALUES (?,?,?,?,?,?,?)');
        $stmt->bind_param('sssssii',$doc['slug'],$doc['title'],$doc['filename'],$doc['effective_date'],$doc['sha256'],$pageCount,$itemCount); $stmt->execute();
        $documentId=$conn->insert_id;
        $pageRows=[];
        foreach ($doc['pages'] as $page) {
            $tables=json_encode($page['tables'],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
            $pageRows[]=[$documentId,$page['number'],$page['text'],$tables];
        }
        importRows($conn,'INSERT INTO supplier_price_pages (document_id,page_number,page_text,tables_json) VALUES ',$pageRows);
        $itemRows=[];
        foreach ($doc['items'] as $item) {
            $specs=json_encode($item['specs'],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
            $prices=json_encode($item['prices'],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
            $raw=json_encode(['headers'=>$item['headers'],'cells'=>$item['raw_cells']],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
            $flags=json_encode($item['flags'],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
            $itemRows[]=[$documentId,$item['page'],$item['table'],$item['row'],$item['category'],$item['code'],$item['description'],$specs,$prices,$raw,$flags,$item['search']];
        }
        importRows($conn,'INSERT INTO supplier_price_items (document_id,page_number,table_number,source_row_number,category,product_code,description,specs_json,prices_json,raw_json,flags_json,search_text) VALUES ',$itemRows);
        $conn->commit();
        echo $doc['title'] . ': imported ' . $itemCount . ' rows / ' . $pageCount . ' pages' . PHP_EOL;
    } catch (Throwable $error) { $conn->rollback(); throw $error; }
}
