<?php
// CLI-only integration test. All test records are rolled back.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__.'/service.php';
$payload=['id'=>'test-'.bin2hex(random_bytes(16)),'customer_id'=>0,'name'=>'Mobile survey rollback test','phone'=>'','address'=>'Test only','notes'=>'','survey_date'=>'2026-10-12','items'=>[['item_name'=>'Window','width'=>1200,'height'=>1500,'quantity'=>2]],'photos'=>[]];
$valid=surveyMobileValidate($payload);
foreach (['width'=>0,'height'=>-1,'quantity'=>1.5] as $key=>$value) {
    $bad=$payload;$bad['items'][0][$key]=$value;
    try { surveyMobileValidate($bad); throw new RuntimeException('Invalid measurement accepted'); } catch (InvalidArgumentException $expected) {}
}
$bad=$payload;$bad['survey_date']='2026-02-31';
try {surveyMobileValidate($bad);throw new RuntimeException('Invalid date accepted');}catch(InvalidArgumentException $expected){}
$bad=$payload;$bad['photos']=['data:image/jpeg;base64,'.base64_encode('not an image')];
try {surveyMobileValidate($bad);throw new RuntimeException('Invalid photo accepted');}catch(InvalidArgumentException $expected){}
require __DIR__.'/../../config/database.php';
$conn->begin_transaction();
try {
    $hash=hash('sha256',json_encode($payload));
    $id=surveyMobilePersist($conn,$valid,0,$hash);
    $again=surveyMobilePersist($conn,$valid,0,$hash);
    if ($id!==$again) throw new RuntimeException('Retry duplicated survey');
    try {surveyMobilePersist($conn,$valid,1,$hash);throw new RuntimeException('Owner mismatch accepted');}catch(InvalidArgumentException $expected){}
    try {surveyMobilePersist($conn,$valid,0,str_repeat('0',64));throw new RuntimeException('Changed payload accepted');}catch(InvalidArgumentException $expected){}
    $stmt=$conn->prepare('SELECT width,height,quantity FROM survey_measurements WHERE survey_id=?');$stmt->execute([$id]);$rows=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    if (count($rows)!==1 || (float)$rows[0]['width']!==1200.0 || (int)$rows[0]['quantity']!==2) throw new RuntimeException('Measurement persistence mismatch');
} finally {$conn->rollback();}
$stmt=$conn->prepare('SELECT draft_id FROM survey_mobile_sync WHERE draft_id=?');$stmt->execute([$valid['id']]);
if ($stmt->get_result()->num_rows!==0) throw new RuntimeException('Test receipt not rolled back');
echo "Passed validation, persistence, duplicate retry, owner/hash checks, and rollback. No test records retained.\n";
