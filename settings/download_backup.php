<?php
session_start();if(!isset($_SESSION['user'])){header('Location:../index.php');exit;}
require_once __DIR__.'/../config/database.php';require_once __DIR__.'/../includes/permissions.php';require_once __DIR__.'/../includes/data_tables.php';requirePermission('backup_manage');
$data=['format'=>'win-pos-backup','version'=>1,'created_at'=>date(DATE_ATOM),'tables'=>[]];
foreach(winPosBackupTables() as $table){
    $result=$conn->query('SELECT * FROM `'.$table.'`');$data['tables'][$table]=[];while($row=$result->fetch_assoc())$data['tables'][$table][]=$row;
}
$filename='win-pos-backup-'.date('Y-m-d-His').'.json';header('Content-Type: application/json');header('Content-Disposition: attachment; filename="'.$filename.'"');header('X-Content-Type-Options: nosniff');echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;
