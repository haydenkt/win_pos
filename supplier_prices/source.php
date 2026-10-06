<?php
require_once __DIR__ . '/common.php';
$doc=spQuery($conn,'SELECT * FROM supplier_price_documents WHERE id=?',[(int)($_GET['id']??0)])->fetch_assoc();
if (!$doc || !preg_match('/^[a-z0-9-]+$/D',$doc['slug'])) { http_response_code(404); exit('Source not found.'); }
$page=(int)($_GET['page']??0);
if ($page < 0 || $page > (int)$doc['page_count']) { http_response_code(404); exit; }
$path=__DIR__.'/../storage/supplier_prices/'.$doc['slug'].'/'.($page ? 'page-'.$page.'.jpg.php' : 'source.pdf.php');
if (!is_file($path)) { http_response_code(404); exit('Source file unavailable.'); }
session_write_close();
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=3600');
header('Content-Type: '.($page ? 'image/jpeg' : 'application/pdf'));
header('Content-Disposition: inline; filename="'.($page ? 'page-'.$page.'.jpg' : $doc['slug'].'.pdf').'"');
$stream=fopen($path,'rb');
fseek($stream,strlen('<?php http_response_code(404); exit; ?>' . "\n"));
fpassthru($stream); fclose($stream);
