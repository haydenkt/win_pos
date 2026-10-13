<?php
session_start();
if (!isset($_SESSION['user'])) { http_response_code(401); exit; }
require __DIR__.'/../../config/database.php';
require __DIR__.'/../../includes/permissions.php';
if (!hasPermission('site_survey_view')) { http_response_code(403); exit; }
$stmt=$conn->prepare('SELECT p.image_data FROM survey_mobile_photos p JOIN site_surveys s ON s.id=p.survey_id WHERE p.id=?');
$stmt->execute([(int)($_GET['id']??0)]);
$photo=$stmt->get_result()->fetch_assoc();
if (!$photo) { http_response_code(404); exit; }
header('Content-Type: image/jpeg'); header('X-Content-Type-Options: nosniff'); header('Cache-Control: private, no-store');
echo $photo['image_data'];
