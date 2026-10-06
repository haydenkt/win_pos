<?php
session_start();
if (!isset($_SESSION['user'])) { header('Location: /index.php'); exit; }
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/permissions.php';
if (!hasPermission('factory_view') && !hasPermission('products_view')) { http_response_code(403); exit('You do not have permission to view supplier price lists.'); }
function spEscape($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function spCanEdit(): bool { return hasPermission('factory_manage') || hasPermission('products_manage'); }
function spQuery(mysqli $conn, string $sql, array $params=[]): mysqli_result {
    $stmt=$conn->prepare($sql);
    if ($params) $stmt->bind_param(str_repeat('s',count($params)),...$params);
    $stmt->execute(); return $stmt->get_result();
}
function spPrice(array $price): string {
    return $price['amount'] === null ? ($price['raw'] ?: 'Not listed') : number_format((float)$price['amount'], 0);
}
