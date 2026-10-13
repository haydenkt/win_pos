<?php
ini_set('display_errors','0');
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
function reply(int $status,array $body): never { http_response_code($status); echo json_encode($body); exit; }
if (!isset($_SESSION['user'],$_SESSION['user_id'])) reply(401,['error'=>'Sign in to the POS in this browser, then return and connect again.']);
try {
    require __DIR__.'/../../config/database.php';
    require __DIR__.'/../../includes/permissions.php';
    require __DIR__.'/service.php';
    if (!hasPermission('site_survey_manage')) reply(403,['error'=>'Your account needs permission to manage site surveys.']);
    $userId=(int)$_SESSION['user_id'];
    if ($_SERVER['REQUEST_METHOD']==='GET') {
        $_SESSION['survey_mobile_csrf'] ??= bin2hex(random_bytes(32));
        $customers=$conn->query('SELECT id,name,phone,address FROM customers ORDER BY name')->fetch_all(MYSQLI_ASSOC);
        reply(200,['user_id'=>$userId,'name'=>(string)$_SESSION['user'],'csrf'=>$_SESSION['survey_mobile_csrf'],'customers'=>$customers]);
    }
    if ($_SERVER['REQUEST_METHOD']!=='POST') reply(405,['error'=>'Method not allowed.']);
    if (empty($_SESSION['survey_mobile_csrf']) || !hash_equals($_SESSION['survey_mobile_csrf'],$_SERVER['HTTP_X_SURVEY_CSRF']??'')) reply(403,['error'=>'Session expired. Connect again before syncing.']);
    if ((int)($_SERVER['CONTENT_LENGTH']??0)>6000000) reply(413,['error'=>'Survey upload is too large.']);
    $raw=file_get_contents('php://input',false,null,0,6000001);
    if (strlen($raw)>6000000) reply(413,['error'=>'Survey upload is too large.']);
    $payload=json_decode($raw,true,32,JSON_THROW_ON_ERROR);
    if (!is_array($payload) || (int)($payload['owner']??0)!==$userId) reply(403,['error'=>'Sign in with the account that created this draft.']);
    $data=surveyMobileValidate($payload);
    $conn->begin_transaction();
    $id=surveyMobilePersist($conn,$data,$userId,hash('sha256',$raw));
    $conn->commit();
    reply(200,['survey_id'=>$id]);
} catch (InvalidArgumentException|JsonException $e) {
    if (isset($conn)) $conn->rollback();
    reply(422,['error'=>$e->getMessage()]);
} catch (Throwable $e) {
    if (isset($conn)) $conn->rollback();
    error_log('Mobile survey sync: '.$e->getMessage());
    reply(503,['error'=>'The server could not complete this request. Your local draft is safe; retry when connected.']);
}
